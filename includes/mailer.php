<?php
/**
 * SURAS — email notification helper
 *
 * Uses PHPMailer (installed via Composer or dropped into vendor/).
 * Toggle on/off from Admin → Settings without touching code.
 *
 * PHPMailer SMTP settings live in includes/config.php:
 *   define('MAIL_HOST',       'smtp.university.edu');
 *   define('MAIL_PORT',       587);
 *   define('MAIL_USERNAME',   'suras@university.edu');
 *   define('MAIL_PASSWORD',   'your-smtp-password');
 *   define('MAIL_ENCRYPTION', 'tls');   // 'tls' or 'ssl'
 *
 * If PHPMailer is not installed, the function logs to PHP's error log
 * and returns false silently — the system keeps running in-app only.
 */

if (defined('SURAS_MAILER_LOADED')) {
    return;
}
define('SURAS_MAILER_LOADED', true);

require_once __DIR__ . '/settings.php';

/**
 * Sends a plain-text + HTML email notification.
 * Returns true on success, false if disabled or on failure.
 */
function send_email_notification(string $toEmail, string $toName, string $subject, string $bodyText): bool
{
    // Only send if email notifications are enabled in settings.
    if ((int) get_setting('notify_email_enabled', '0') !== 1) {
        return false;
    }

    $fromEmail = get_setting('notify_from_email', 'noreply@university.edu');
    $fromName  = get_setting('notify_from_name',  'SURAS Resource System');

    // Try to load PHPMailer — support both Composer autoload and manual drop-in.
    $composerAutoload = __DIR__ . '/../vendor/autoload.php';
    $manualLoad       = __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';

    if (file_exists($composerAutoload)) {
        require_once $composerAutoload;
    } elseif (file_exists($manualLoad)) {
        require_once $manualLoad;
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
        require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
    } else {
        error_log('SURAS mailer: PHPMailer not found. Run: composer require phpmailer/phpmailer');
        return false;
    }

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = defined('MAIL_HOST')       ? MAIL_HOST       : 'localhost';
        $mail->SMTPAuth   = defined('MAIL_USERNAME')   && MAIL_USERNAME !== '';
        $mail->Username   = defined('MAIL_USERNAME')   ? MAIL_USERNAME   : '';
        $mail->Password   = defined('MAIL_PASSWORD')   ? MAIL_PASSWORD   : '';
        $mail->SMTPSecure = defined('MAIL_ENCRYPTION') ? MAIL_ENCRYPTION : 'tls';
        $mail->Port       = defined('MAIL_PORT')       ? MAIL_PORT       : 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail, $toName);
        $mail->Subject = $subject;

        // Plain text body
        $mail->Body    = $bodyText;

        // Simple HTML body
        $mail->isHTML(true);
        $mail->Body    = nl2br(htmlspecialchars($bodyText, ENT_QUOTES, 'UTF-8'));
        $mail->AltBody = $bodyText;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('SURAS mailer error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Convenience wrapper: look up the user's email + name, then send.
 * Called after create_notification() so the in-app record is always created first.
 */
function notify_user_by_email(int $userId, string $subject, string $bodyText): void
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT full_name, email FROM users WHERE id = :id');
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();
    if (!$user) {
        return;
    }
    send_email_notification($user['email'], $user['full_name'], $subject, $bodyText);
}
