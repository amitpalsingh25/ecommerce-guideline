<?php
declare(strict_types=1);

function ensure_orders_table(): void
{
    db()->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(191) NOT NULL,
        name VARCHAR(191) NULL,
        phone VARCHAR(50) NULL,
        items TEXT NULL,
        total DECIMAL(10,2) NOT NULL DEFAULT 0,
        status ENUM('pending','paid','failed','cancelled') NOT NULL DEFAULT 'pending',
        stripe_session VARCHAR(191) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/** Re-price cart items from the DB. Returns [lineItems, total]. */
function stripe_price_items(array $items): array
{
    $lines = [];
    $total = 0.0;
    foreach ($items as $i) {
        $sku = (string)($i['sku'] ?? '');
        $qty = max(1, (int)($i['qty'] ?? 1));
        if ($sku === '') continue;
        // variant by sku, else product by sku, else product by id (p<id>)
        $row = q_one('SELECT label AS title, price FROM product_variants WHERE sku = ?', [$sku]);
        if (!$row) $row = q_one('SELECT title, price FROM products WHERE sku = ?', [$sku]);
        if (!$row && preg_match('/^p(\d+)$/', $sku, $m)) $row = q_one('SELECT title, price FROM products WHERE id = ?', [(int)$m[1]]);
        if (!$row || $row['price'] === null) continue; // skip unpriced
        $price = (float)$row['price'];
        $title = (string)($i['title'] ?? $row['title']);
        $lines[] = ['title' => $title, 'price' => $price, 'qty' => $qty];
        $total += $price * $qty;
    }
    return [$lines, round($total, 2)];
}

/** Create a Stripe Checkout Session via the REST API (no SDK). Returns url|null. */
function stripe_create_session(array $lines, string $email, int $orderId): ?string
{
    $st = stripe_settings();
    if (!$st['enabled'] || $st['secretKey'] === '' || !$lines) return null;
    $base = rtrim(SITE_URL, '/');

    $fields = [
        'mode' => 'payment',
        'customer_email' => $email,
        'success_url' => $base . url('checkout?paid=' . $orderId),
        'cancel_url' => $base . url('cart'),
        'metadata[orderId]' => (string)$orderId,
    ];
    foreach ($lines as $idx => $l) {
        $fields["line_items[$idx][quantity]"] = $l['qty'];
        $fields["line_items[$idx][price_data][currency]"] = 'aud';
        $fields["line_items[$idx][price_data][unit_amount]"] = (int)round($l['price'] * 100);
        $fields["line_items[$idx][price_data][product_data][name]"] = $l['title'];
    }

    $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($fields),
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $st['secretKey']],
        CURLOPT_TIMEOUT => 30,
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($res === false) { log_event('stripe', 'Checkout session curl failed', 'error'); return null; }
    $j = json_decode($res, true);
    if ($code >= 400 || !isset($j['id'])) {
        log_event('stripe', 'Checkout session error', 'error', ['code' => $code, 'msg' => $j['error']['message'] ?? '']);
        return null;
    }
    q_exec('UPDATE orders SET stripe_session = ? WHERE id = ?', [$j['id'], $orderId]);
    log_event('stripe', "Checkout session created for order $orderId", 'info', ['session' => $j['id']]);
    return $j['url'] ?? null;
}

/** POST /stripe/checkout — build order, create session, redirect to Stripe. */
function handle_stripe_checkout(): void
{
    if (!csrf_check() || !payments_enabled()) redirect('cart');
    ensure_orders_table();
    $email = trim((string)($_POST['email'] ?? ''));
    $name = trim((string)($_POST['name'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $items = json_decode((string)($_POST['items'] ?? '[]'), true);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !is_array($items) || !$items) redirect('checkout?err=1');

    [$lines, $total] = stripe_price_items($items);
    if (!$lines) redirect('checkout?err=unpriced');

    $orderId = (int)q_exec(
        'INSERT INTO orders (email, name, phone, items, total, status) VALUES (?,?,?,?,?,?)',
        [$email, $name ?: null, $phone ?: null, json_encode($lines), $total, 'pending']
    );
    log_event('order', "Order created (pending): $orderId", 'info', ['email' => $email, 'total' => $total]);

    $url = stripe_create_session($lines, $email, $orderId);
    if (!$url) { q_exec("UPDATE orders SET status='failed' WHERE id=?", [$orderId]); redirect('checkout?err=stripe'); }
    redirect($url);
}

/** POST /stripe/webhook — verify signature, mark order paid. */
function handle_stripe_webhook(): void
{
    $st = stripe_settings();
    $payload = file_get_contents('php://input');
    $sig = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
    if ($st['webhookSecret'] !== '') {
        $parts = [];
        foreach (explode(',', $sig) as $kv) { $p = explode('=', $kv, 2); if (count($p) === 2) $parts[$p[0]] = $p[1]; }
        $t = $parts['t'] ?? ''; $v1 = $parts['v1'] ?? '';
        $expected = hash_hmac('sha256', $t . '.' . $payload, $st['webhookSecret']);
        if (!$t || !hash_equals($expected, $v1)) {
            http_response_code(400);
            log_event('stripe', 'Webhook signature verification failed', 'error');
            echo 'bad sig'; return;
        }
    }
    $event = json_decode($payload, true);
    if (($event['type'] ?? '') === 'checkout.session.completed') {
        $obj = $event['data']['object'] ?? [];
        $orderId = $obj['metadata']['orderId'] ?? null;
        ensure_orders_table();
        if ($orderId) q_exec("UPDATE orders SET status='paid' WHERE id=?", [(int)$orderId]);
        elseif (!empty($obj['id'])) q_exec("UPDATE orders SET status='paid' WHERE stripe_session=?", [$obj['id']]);
        log_event('order', "Order PAID: " . ($orderId ?? ($obj['id'] ?? '?')), 'info');

        $order = $orderId
            ? q_one('SELECT * FROM orders WHERE id=?', [(int)$orderId])
            : (!empty($obj['id']) ? q_one('SELECT * FROM orders WHERE stripe_session=?', [$obj['id']]) : null);
        if ($order) {
            $lines = json_decode((string)($order['items'] ?? '[]'), true) ?: [];
            $items = [];
            foreach ($lines as $l) {
                $items[] = ['sku' => (string)($l['sku'] ?? ''), 'title' => (string)($l['title'] ?? ''), 'qty' => (int)($l['qty'] ?? 1)];
            }
            $vars = [
                'order' => (string)$order['id'],
                'name' => (string)($order['name'] ?? ''),
                'email' => (string)$order['email'],
                'phone' => (string)($order['phone'] ?? '—') ?: '—',
                'total' => '$' . number_format((float)$order['total'], 2),
                'items' => $items,
            ];
            $adm = render_email('order_admin', $vars);
            if ($adm) send_mail(ADMIN_EMAIL, $adm['subject'], $adm['html'], (string)$order['email']);
            $cust = render_email('order_customer', $vars);
            if ($cust) send_mail((string)$order['email'], $cust['subject'], $cust['html']);
        }
    }
    echo 'ok';
}
