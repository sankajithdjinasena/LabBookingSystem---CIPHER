<?php
/**
 * mailer.php — SURAS email notification helper using PHPMailer + Gmail SMTP.
 *
 * Requires: composer require phpmailer/phpmailer
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

// ─── SMTP Configuration ───────────────────────────────────────────────────────
defined('MAIL_HOST')       || define('MAIL_HOST',       'smtp.gmail.com');
defined('MAIL_PORT')       || define('MAIL_PORT',       587);
defined('MAIL_USERNAME')   || define('MAIL_USERNAME',   'predictrasusl@gmail.com');
defined('MAIL_PASSWORD')   || define('MAIL_PASSWORD',   '');
defined('MAIL_FROM')       || define('MAIL_FROM',       'predictrasusl@gmail.com');
defined('MAIL_FROM_NAME')  || define('MAIL_FROM_NAME',  'SURAS Team');
defined('MAIL_ENCRYPTION') || define('MAIL_ENCRYPTION', PHPMailer::ENCRYPTION_STARTTLS);
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Send an email notification.
 *
 * @param string $toEmail   Recipient email address.
 * @param string $toName    Recipient display name.
 * @param string $subject   Email subject line.
 * @param string $body      Plain-text email body.
 * @param string $htmlBody  Optional HTML version of the body.
 *
 * @return bool  TRUE on success, FALSE on failure (error logged).
 */
function send_email_notification(
    string $toEmail,
    string $toName,
    string $subject,
    string $body,
    string $htmlBody = ''
): bool {

    $mail = new PHPMailer(true);

    try {
        // ── Server settings ──────────────────────────────────────────────────
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION;
        $mail->Port       = MAIL_PORT;

        // Disable SSL certificate verification for local dev (remove in prod)
        // $mail->SMTPOptions = [
        //     'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
        // ];

        // ── Sender ───────────────────────────────────────────────────────────
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addReplyTo(MAIL_FROM, MAIL_FROM_NAME);

        // ── Recipient ────────────────────────────────────────────────────────
        $mail->addAddress($toEmail, $toName);

        // ── Content ──────────────────────────────────────────────────────────
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;

        if (!empty($htmlBody)) {
            $mail->isHTML(true);
            $mail->Body    = $htmlBody;
            $mail->AltBody = $body;   // plain-text fallback
        } else {
            $mail->isHTML(false);
            $mail->Body = $body;
        }

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('[SURAS Mailer] Failed to send to ' . $toEmail . ' — ' . $mail->ErrorInfo);
        return false;
    }
}
