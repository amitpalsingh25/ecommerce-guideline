<?php
declare(strict_types=1);

/** Lazy-create the customers table. */
function ensure_customers_table(): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    db()->exec("CREATE TABLE IF NOT EXISTS customers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(191) NOT NULL,
        email VARCHAR(191) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        reset_token VARCHAR(64) NULL,
        reset_expires DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/** Centered auth-card shell. */
function auth_shell(string $heading, string $sub, string $inner): string
{
    return '<section class="auth-wrap"><div class="auth-card">'
        . '<h1>' . e($heading) . '</h1>'
        . ($sub ? '<p class="auth-sub">' . e($sub) . '</p>' : '')
        . $inner
        . '</div></section>';
}

function flash_get(): string
{
    $m = $_SESSION['flash'] ?? '';
    unset($_SESSION['flash']);
    return $m ? '<p class="form-ok">' . e($m) . '</p>' : '';
}

// ---------- Register ----------
function page_register(): void
{
    if (current_customer()) redirect('account');
    ensure_customers_table();
    $err = '';
    $name = $email = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!csrf_check()) redirect('register');
        $name = trim((string)($_POST['name'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $pw = (string)($_POST['password'] ?? '');
        $pw2 = (string)($_POST['password2'] ?? '');
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Enter your name and a valid email.';
        elseif (strlen($pw) < 8) $err = 'Password must be at least 8 characters.';
        elseif ($pw !== $pw2) $err = 'Passwords do not match.';
        elseif (q_one('SELECT id FROM customers WHERE email = ?', [$email])) $err = 'An account with that email already exists. Try logging in.';
        else {
            $id = (int)q_exec('INSERT INTO customers (name, email, password_hash) VALUES (?,?,?)', [$name, $email, password_hash($pw, PASSWORD_DEFAULT)]);
            customer_session_set(['id' => $id, 'email' => $email, 'name' => $name]);
            log_event('auth', "Customer registered: $email", 'info');
            $welcome = render_email('account_welcome', ['name' => $name, 'email' => $email]);
            if ($welcome) { require_once __DIR__ . '/forms.php'; send_mail($email, $welcome['subject'], $welcome['html']); }
            redirect(($_SESSION['after_login'] ?? '') ? ltrim($_SESSION['after_login'], '/') : 'account');
        }
    }
    $form = ($err ? '<p class="form-error">' . e($err) . '</p>' : '')
        . '<form method="post">' . csrf_field()
        . '<div class="field"><label>Name</label><input name="name" value="' . e($name) . '" required autofocus></div>'
        . '<div class="field"><label>Email</label><input name="email" type="email" value="' . e($email) . '" required></div>'
        . '<div class="field"><label>Password</label><input name="password" type="password" required minlength="8"></div>'
        . '<div class="field"><label>Confirm password</label><input name="password2" type="password" required></div>'
        . '<button class="btn btn-flame" style="width:100%;justify-content:center">Create account</button></form>'
        . '<p class="auth-alt">Already have an account? <a href="' . url('login') . '">Log in</a></p>';
    respond(auth_shell('Create your account', 'Save your details and track your enquiries.', $form), ['title' => 'Register · ' . SITE_NAME]);
}

// ---------- Login ----------
function page_login(): void
{
    if (current_customer()) redirect('account');
    ensure_customers_table();
    $err = '';
    $email = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!csrf_check()) redirect('login');
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $pw = (string)($_POST['password'] ?? '');
        $c = q_one('SELECT * FROM customers WHERE email = ?', [$email]);
        if ($c && password_verify($pw, $c['password_hash'])) {
            customer_session_set($c);
            log_event('auth', "Customer login: $email", 'info');
            $to = ($_SESSION['after_login'] ?? '');
            unset($_SESSION['after_login']);
            redirect($to ? ltrim($to, '/') : 'account');
        }
        $err = 'Incorrect email or password.';
        log_event('auth', "Customer login failed: $email", 'warn');
    }
    $form = ($err ? '<p class="form-error">' . e($err) . '</p>' : '') . flash_get()
        . '<form method="post">' . csrf_field()
        . '<div class="field"><label>Email</label><input name="email" type="email" value="' . e($email) . '" required autofocus></div>'
        . '<div class="field" style="margin-bottom:8px"><label>Password</label><input name="password" type="password" required></div>'
        . '<p style="text-align:right;margin:0 0 16px"><a href="' . url('forgot') . '" style="font-size:13px;color:var(--flame-deep)">Forgot password?</a></p>'
        . '<button class="btn btn-flame" style="width:100%;justify-content:center">Log in</button></form>'
        . '<p class="auth-alt">New here? <a href="' . url('register') . '">Create an account</a></p>';
    respond(auth_shell('Welcome back', 'Log in to your account.', $form), ['title' => 'Log in · ' . SITE_NAME]);
}

function page_logout(): void
{
    customer_logout();
    redirect('');
}

// ---------- Forgot / reset ----------
function page_forgot(): void
{
    ensure_customers_table();
    $done = false;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!csrf_check()) redirect('forgot');
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $c = q_one('SELECT * FROM customers WHERE email = ?', [$email]);
            if ($c) {
                $token = bin2hex(random_bytes(24));
                q_exec('UPDATE customers SET reset_token = ?, reset_expires = ? WHERE id = ?', [$token, date('Y-m-d H:i:s', time() + 3600), $c['id']]);
                $url = rtrim(SITE_URL, '/') . url('reset?token=' . $token);
                $mail = render_email('password_reset', ['name' => $c['name'], 'email' => $email, 'reset_url' => $url]);
                if ($mail) { require_once __DIR__ . '/forms.php'; send_mail($email, $mail['subject'], $mail['html']); }
                log_event('auth', "Password reset requested: $email", 'info');
            }
        }
        $done = true; // always show the same message (no account enumeration)
    }
    $inner = $done
        ? '<p class="form-ok">If an account exists for that email, we\'ve sent a reset link. Check your inbox.</p><p class="auth-alt"><a href="' . url('login') . '">Back to login</a></p>'
        : '<form method="post">' . csrf_field()
            . '<div class="field"><label>Email</label><input name="email" type="email" required autofocus></div>'
            . '<button class="btn btn-flame" style="width:100%;justify-content:center">Send reset link</button></form>'
            . '<p class="auth-alt"><a href="' . url('login') . '">Back to login</a></p>';
    respond(auth_shell('Reset your password', 'We\'ll email you a link to set a new password.', $inner), ['title' => 'Forgot password · ' . SITE_NAME]);
}

function page_reset(): void
{
    ensure_customers_table();
    $token = (string)($_GET['token'] ?? ($_POST['token'] ?? ''));
    $c = $token !== '' ? q_one('SELECT * FROM customers WHERE reset_token = ? AND reset_expires > ?', [$token, date('Y-m-d H:i:s')]) : null;
    if (!$c) {
        respond(auth_shell('Link expired', '', '<p class="form-error">This reset link is invalid or has expired.</p><p class="auth-alt"><a href="' . url('forgot') . '">Request a new one</a></p>'), ['title' => 'Reset · ' . SITE_NAME]);
        return;
    }
    $err = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!csrf_check()) redirect('login');
        $pw = (string)($_POST['password'] ?? '');
        $pw2 = (string)($_POST['password2'] ?? '');
        if (strlen($pw) < 8) $err = 'Password must be at least 8 characters.';
        elseif ($pw !== $pw2) $err = 'Passwords do not match.';
        else {
            q_exec('UPDATE customers SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?', [password_hash($pw, PASSWORD_DEFAULT), $c['id']]);
            customer_session_set($c);
            log_event('auth', "Password reset done: {$c['email']}", 'info');
            $_SESSION['flash'] = 'Your password has been updated.';
            redirect('account');
        }
    }
    $form = ($err ? '<p class="form-error">' . e($err) . '</p>' : '')
        . '<form method="post"><input type="hidden" name="token" value="' . e($token) . '">' . csrf_field()
        . '<div class="field"><label>New password</label><input name="password" type="password" required minlength="8" autofocus></div>'
        . '<div class="field"><label>Confirm password</label><input name="password2" type="password" required></div>'
        . '<button class="btn btn-flame" style="width:100%;justify-content:center">Set new password</button></form>';
    respond(auth_shell('Set a new password', 'For ' . $c['email'], $form), ['title' => 'Reset · ' . SITE_NAME]);
}

// ---------- Account area ----------
function page_account(): void
{
    require_customer();
    $cu = current_customer();
    $email = $cu['email'];

    $enq = q_all('SELECT * FROM enquiries WHERE email = ? ORDER BY created_at DESC LIMIT 50', [$email]);
    $orders = [];
    try { $orders = q_all('SELECT * FROM orders WHERE email = ? ORDER BY created_at DESC LIMIT 50', [$email]); } catch (Throwable $e) {}

    $enqRows = '';
    foreach ($enq as $en) {
        $items = json_arr($en['items']);
        $enqRows .= '<tr><td>#' . (int)$en['id'] . '</td><td>' . count($items) . ' items</td><td><span class="badge ' . e($en['status']) . '">' . e($en['status']) . '</span></td><td>' . e(fmt_date($en['created_at'])) . '</td></tr>';
    }
    $ordRows = '';
    foreach ($orders as $o) {
        $ordRows .= '<tr><td>#' . (int)$o['id'] . '</td><td>' . e(money($o['total']) ?? '—') . '</td><td><span class="badge ' . e($o['status']) . '">' . e($o['status']) . '</span></td><td>' . e(fmt_date($o['created_at'])) . '</td></tr>';
    }

    $ordersBlock = $ordRows
        ? '<div class="acct-card"><h3>Orders</h3><table class="acct-table"><thead><tr><th>Order</th><th>Total</th><th>Status</th><th>Date</th></tr></thead><tbody>' . $ordRows . '</tbody></table></div>'
        : '';
    $enqBlock = '<div class="acct-card"><h3>Your enquiries</h3>'
        . ($enqRows ? '<table class="acct-table"><thead><tr><th>Ref</th><th>Items</th><th>Status</th><th>Sent</th></tr></thead><tbody>' . $enqRows . '</tbody></table>' : '<p style="color:var(--muted)">No enquiries yet. <a href="' . url('products') . '">Browse the catalogue</a>.</p>')
        . '</div>';

    $h = '<section class="acct"><div class="wrap">'
        . '<div class="acct-head"><div><span class="eyebrow">Account</span><h1>Hi, ' . e($cu['name']) . '</h1><p style="color:var(--muted);margin:4px 0 0">' . e($email) . '</p></div>'
        . '<a href="' . url('logout') . '" class="btn btn-ghost">Log out</a></div>'
        . flash_get()
        . $ordersBlock . $enqBlock
        . '<div class="acct-card"><h3>Profile</h3>' . account_profile_form('') . '</div>'
        . '</div></section>';
    respond($h, ['title' => 'My account · ' . SITE_NAME]);
}

function account_profile_form(string $msg): string
{
    $cu = current_customer();
    return ($msg ? '<p class="form-ok">' . e($msg) . '</p>' : '')
        . '<form method="post" action="' . url('account/profile') . '" style="max-width:460px">' . csrf_field()
        . '<div class="field"><label>Name</label><input name="name" value="' . e($cu['name']) . '" required></div>'
        . '<div class="field"><label>New password (leave blank to keep)</label><input name="password" type="password" minlength="8"></div>'
        . '<button class="btn btn-flame">Save changes</button></form>';
}

function page_account_profile(): void
{
    require_customer();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check()) redirect('account');
    $cu = current_customer();
    $name = trim((string)($_POST['name'] ?? '')) ?: $cu['name'];
    $pw = (string)($_POST['password'] ?? '');
    if ($pw !== '' && strlen($pw) >= 8) {
        q_exec('UPDATE customers SET name = ?, password_hash = ? WHERE id = ?', [$name, password_hash($pw, PASSWORD_DEFAULT), $cu['id']]);
    } else {
        q_exec('UPDATE customers SET name = ? WHERE id = ?', [$name, $cu['id']]);
    }
    $_SESSION['customer']['name'] = $name;
    $_SESSION['flash'] = 'Profile updated.';
    redirect('account');
}
