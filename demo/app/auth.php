<?php
declare(strict_types=1);

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_admin(): bool
{
    $u = current_user();
    return $u !== null && ($u['role'] ?? '') === 'admin';
}

function require_admin(): void
{
    if (!is_admin()) {
        $_SESSION['after_login'] = current_path();
        redirect('admin/login');
    }
}

function attempt_login(string $email, string $password): bool
{
    $email = strtolower(trim($email));
    $user = q_one('SELECT * FROM users WHERE email = ?', [$email]);
    if (!$user || !password_verify($password, $user['password_hash'])) {
        log_event('auth', "Login failed: {$email}", 'warn', ['email' => $email]);
        return false;
    }
    $_SESSION['user'] = [
        'id'    => (int)$user['id'],
        'email' => $user['email'],
        'name'  => $user['name'],
        'role'  => $user['role'],
    ];
    log_event('auth', "Login success: {$email}", 'info', ['email' => $email, 'role' => $user['role']]);
    return true;
}

function logout(): void
{
    unset($_SESSION['user']);
}

// ---------- Customer accounts ----------
function current_customer(): ?array
{
    return $_SESSION['customer'] ?? null;
}

function require_customer(): void
{
    if (!current_customer()) {
        $_SESSION['after_login'] = current_path();
        redirect('login');
    }
}

function customer_session_set(array $c): void
{
    $_SESSION['customer'] = ['id' => (int)$c['id'], 'email' => $c['email'], 'name' => $c['name']];
}

function customer_logout(): void
{
    unset($_SESSION['customer']);
}
