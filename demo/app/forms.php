<?php
declare(strict_types=1);

/** Mailer: Gmail/SMTP via PHPMailer when configured, else PHP mail(). */
function send_mail(string $to, string $subject, string $html, ?string $replyTo = null): bool
{
    $smtp = smtp_settings();
    if ($smtp) {
        require_once __DIR__ . '/lib/PHPMailer/Exception.php';
        require_once __DIR__ . '/lib/PHPMailer/PHPMailer.php';
        require_once __DIR__ . '/lib/PHPMailer/SMTP.php';
        try {
            $m = new PHPMailer\PHPMailer\PHPMailer(true);
            $m->isSMTP();
            $m->Host = $smtp['host'];
            $m->SMTPAuth = true;
            $m->Username = $smtp['user'];
            $m->Password = $smtp['pass'];
            $m->Port = $smtp['port'];
            $m->SMTPSecure = $smtp['port'] === 465
                ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $m->CharSet = 'UTF-8';
            $m->setFrom($smtp['fromEmail'], $smtp['fromName']);
            $m->addAddress($to);
            if ($replyTo) $m->addReplyTo($replyTo);
            $m->isHTML(true);
            $m->Subject = $subject;
            $m->Body = $html;
            $m->send();
            return true;
        } catch (Throwable $e) {
            error_log('[smtp] ' . $e->getMessage());
            return false;
        }
    }
    // Fallback: PHP mail()
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . SITE_NAME . ' <' . ADMIN_EMAIL . '>',
    ];
    if ($replyTo) $headers[] = 'Reply-To: ' . $replyTo;
    try {
        return @mail($to, $subject, $html, implode("\r\n", $headers));
    } catch (Throwable $e) {
        error_log('[mail] ' . $e->getMessage());
        return false;
    }
}

function handle_enquiry(): void
{
    if (!csrf_check()) { redirect('cart'); }
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $message = trim((string)($_POST['message'] ?? ''));
    $items = json_decode((string)($_POST['items'] ?? '[]'), true);
    if (!is_array($items)) $items = [];

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$items) {
        redirect('checkout?err=1');
    }

    // normalise items for storage/email
    $clean = [];
    foreach ($items as $i) {
        $clean[] = [
            'sku' => (string)($i['sku'] ?? ''),
            'title' => (string)($i['title'] ?? ''),
            'qty' => (int)($i['qty'] ?? 1),
        ];
    }

    try {
        q_exec(
            'INSERT INTO enquiries (name, email, phone, items, message) VALUES (?,?,?,?,?)',
            [$name, $email, $phone ?: null, json_encode($clean), $message ?: null]
        );
        log_event('enquiry', "Enquiry received from $name", 'info', ['email' => $email, 'items' => count($clean)]);
    } catch (Throwable $e) {
        log_event('enquiry', "Enquiry DB save failed for $email", 'error', ['error' => $e->getMessage()]);
    }

    $vars = ['name' => $name, 'email' => $email, 'phone' => $phone ?: '—', 'message' => $message ?: '', 'items' => $clean];
    $adm = render_email('enquiry_admin', $vars);
    $sentAdmin = $adm ? send_mail(ADMIN_EMAIL, $adm['subject'], $adm['html'], $email) : false;
    $cust = render_email('enquiry_customer', $vars);
    if ($cust) send_mail($email, $cust['subject'], $cust['html']);
    log_event('email', 'Enquiry emails — admin: ' . ($sentAdmin ? 'sent' : 'not sent'), $sentAdmin ? 'info' : 'warn', ['email' => $email]);

    redirect('checkout?sent=1');
}

function handle_contact(): void
{
    if (!csrf_check()) { redirect('contact'); }
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $message = trim((string)($_POST['message'] ?? ''));
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '') {
        redirect('contact?err=1');
    }
    $vars = ['name' => $name, 'email' => $email, 'phone' => $phone ?: '—', 'message' => $message];
    $adm = render_email('contact_admin', $vars);
    $sent = $adm ? send_mail(ADMIN_EMAIL, $adm['subject'], $adm['html'], $email) : false;
    log_event('contact', "Contact message from $name", $sent ? 'info' : 'warn', ['email' => $email, 'emailed' => $sent]);
    redirect('contact?sent=1');
}
