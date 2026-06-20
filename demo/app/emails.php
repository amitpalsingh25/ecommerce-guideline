<?php
declare(strict_types=1);

/** Email template definitions (defaults). Admin overrides stored in setting('emails'). */
function email_defaults(): array
{
    return [
        'enquiry_admin' => [
            'label' => 'New enquiry — to admin',
            'to' => 'admin',
            'subject' => 'New enquiry from {name}',
            'heading' => 'New enquiry',
            'body' => "<p><strong>Name:</strong> {name}<br><strong>Email:</strong> {email}<br><strong>Phone:</strong> {phone}</p><p>{message}</p><h3>Requested items</h3>{items}",
        ],
        'enquiry_customer' => [
            'label' => 'Enquiry received — to customer',
            'to' => 'customer',
            'subject' => "We've received your enquiry — {site}",
            'heading' => 'Thanks, {name}',
            'body' => "<p>We've received your enquiry and will reply with pricing and availability, usually within a business day.</p><h3>Your list</h3>{items}<p>If anything's urgent, call us on {phone_co}.</p>",
        ],
        'contact_admin' => [
            'label' => 'Contact form — to admin',
            'to' => 'admin',
            'subject' => 'Contact form: {name}',
            'heading' => 'Contact form message',
            'body' => "<p><strong>Name:</strong> {name}<br><strong>Email:</strong> {email}<br><strong>Phone:</strong> {phone}</p><p>{message}</p>",
        ],
        'order_admin' => [
            'label' => 'New order — to admin',
            'to' => 'admin',
            'subject' => 'New order #{order} — {site}',
            'heading' => 'New paid order #{order}',
            'body' => "<p><strong>Customer:</strong> {name} ({email})<br><strong>Phone:</strong> {phone}</p><p><strong>Total:</strong> {total}</p><h3>Items</h3>{items}",
        ],
        'order_customer' => [
            'label' => 'Order confirmation — to customer',
            'to' => 'customer',
            'subject' => 'Your order is confirmed — {site}',
            'heading' => 'Thank you, {name}',
            'body' => "<p>Your order <strong>#{order}</strong> is confirmed. A receipt is below — total <strong>{total}</strong>.</p>{items}<p>We'll be in touch about dispatch. Questions? Call {phone_co}.</p>",
        ],
    ];
}

/** Merged template (defaults + saved override) with enabled flag. */
function email_template(string $id): ?array
{
    $defs = email_defaults();
    if (!isset($defs[$id])) return null;
    $saved = (array)setting('emails', []);
    $s = (array)($saved[$id] ?? []);
    return [
        'label' => $defs[$id]['label'],
        'to' => $defs[$id]['to'],
        'enabled' => array_key_exists('enabled', $s) ? (bool)$s['enabled'] : true,
        'subject' => $s['subject'] ?? $defs[$id]['subject'],
        'heading' => $s['heading'] ?? $defs[$id]['heading'],
        'body' => $s['body'] ?? $defs[$id]['body'],
    ];
}

/** Build an HTML items table from [{sku,title,qty}]. */
function email_items_table(array $items): string
{
    if (!$items) return '';
    $rows = '';
    foreach ($items as $i) {
        $rows .= '<tr>'
            . '<td style="padding:6px 10px;border-bottom:1px solid #eee;font-family:monospace;font-size:12px">' . e((string)($i['sku'] ?? '')) . '</td>'
            . '<td style="padding:6px 10px;border-bottom:1px solid #eee">' . e((string)($i['title'] ?? '')) . '</td>'
            . '<td style="padding:6px 10px;border-bottom:1px solid #eee;text-align:right">' . (int)($i['qty'] ?? 1) . '</td></tr>';
    }
    return '<table style="width:100%;border-collapse:collapse;margin:8px 0 16px">'
        . '<thead><tr><th style="text-align:left;padding:6px 10px;border-bottom:2px solid #eee;font-size:12px">SKU</th><th style="text-align:left;padding:6px 10px;border-bottom:2px solid #eee;font-size:12px">Item</th><th style="text-align:right;padding:6px 10px;border-bottom:2px solid #eee;font-size:12px">Qty</th></tr></thead>'
        . '<tbody>' . $rows . '</tbody></table>';
}

/** Replace {placeholders} in a string. {items} handled by caller's vars. */
function email_replace(string $tpl, array $vars): string
{
    $map = [];
    foreach ($vars as $k => $v) {
        if ($k === 'items') continue;
        $map['{' . $k . '}'] = is_string($v) ? nl2br(e($v)) : (string)$v;
    }
    $map['{items}'] = isset($vars['items']) && is_array($vars['items']) ? email_items_table($vars['items']) : '';
    return strtr($tpl, $map);
}

/** Branded HTML email shell. */
function email_wrap(string $heading, string $bodyHtml): string
{
    $t = theme();
    $b = branding();
    $flameDeep = $t['flameDeep'];
    $logo = '';
    if (!empty($b['logoUrl'])) {
        $src = str_starts_with($b['logoUrl'], 'http') ? $b['logoUrl'] : rtrim(SITE_URL, '/') . $b['logoUrl'];
        $logo = '<img src="' . e($src) . '" alt="' . e(SITE_NAME) . '" width="200" style="height:auto;max-height:48px;width:auto;display:inline-block">';
    } else {
        $logo = '<span style="color:#fff;font-size:22px;font-weight:800;letter-spacing:-.01em">' . e(SITE_NAME) . '</span>';
    }
    $mail = 'mailto:' . CO_EMAIL;
    $tel = 'tel:' . preg_replace('/\s+/', '', CO_PHONE);

    return '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
        . '<body style="margin:0;padding:0;background:#efe9e2;-webkit-font-smoothing:antialiased">'
        . '<div style="background:#efe9e2;padding:32px 16px;font-family:\'Segoe UI\',Arial,Helvetica,sans-serif">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 8px 30px rgba(23,18,15,.08)">'
        // header band
        . '<tr><td style="background:' . e($t['ink']) . ';padding:30px 28px;text-align:center">' . $logo . '</td></tr>'
        // flame accent bar
        . '<tr><td style="height:4px;background:linear-gradient(90deg,' . e($t['flameDeep']) . ' 0%,' . e($t['flame']) . ' 55%,' . e($t['ember']) . ' 100%);font-size:0;line-height:0">&nbsp;</td></tr>'
        // body
        . '<tr><td style="padding:34px 36px;color:#2a2420;line-height:1.65;font-size:15px">'
        . '<div style="font-family:\'Courier New\',monospace;font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:' . e($t['flame']) . ';margin:0 0 8px;font-weight:700">' . e(SITE_NAME) . '</div>'
        . '<h1 style="margin:0 0 18px;color:' . e($flameDeep) . ';font-size:24px;line-height:1.25;font-weight:800;letter-spacing:-.01em">' . $heading . '</h1>'
        . '<div style="color:#3a322c">' . $bodyHtml . '</div>'
        . '</td></tr>'
        // footer
        . '<tr><td style="padding:22px 28px;background:#17120F;color:#b9aea3;font-size:12px;line-height:1.7;text-align:center">'
        . '<div style="color:#fff;font-weight:700;font-size:13px;margin-bottom:4px">' . e(SITE_NAME) . '</div>'
        . e(CO_ADDRESS) . '<br>'
        . '<a href="' . e($tel) . '" style="color:' . e($t['ember']) . ';text-decoration:none">' . e(CO_PHONE) . '</a>'
        . ' &nbsp;·&nbsp; <a href="' . e($mail) . '" style="color:' . e($t['ember']) . ';text-decoration:none">' . e(CO_EMAIL) . '</a>'
        . '</td></tr>'
        . '</table></div></body></html>';
}

/** Sample data for previewing a template in the admin. */
function email_sample_vars(): array
{
    return [
        'name' => 'Jordan Mason',
        'email' => 'jordan.mason@example.com.au',
        'phone' => '0412 345 678',
        'message' => "Hi, I'd like a quote for the items below for a commercial fit-out. What's your lead time on the ABE extinguishers?",
        'order' => '1042',
        'total' => '$1,284.50',
        'items' => [
            ['sku' => 'EXT-ABE-45', 'title' => '4.5kg ABE Dry Chemical Extinguisher', 'qty' => 6],
            ['sku' => 'EXT-CO2-50', 'title' => '5.0kg CO₂ Extinguisher', 'qty' => 2],
            ['sku' => 'HR-SWING-19', 'title' => 'Swing Arm Fire Hose Reel 19mm', 'qty' => 3],
        ],
    ];
}

/** Render a template. Returns ['subject','html'] or null if disabled (unless $force). */
function render_email(string $id, array $vars, bool $force = false): ?array
{
    $t = email_template($id);
    if (!$t || (!$t['enabled'] && !$force)) return null;
    $vars += ['site' => SITE_NAME, 'phone_co' => CO_PHONE, 'email_co' => CO_EMAIL, 'address' => CO_ADDRESS];
    return [
        'subject' => email_replace($t['subject'], $vars),
        'html' => email_wrap(email_replace($t['heading'], $vars), email_replace($t['body'], $vars)),
    ];
}
