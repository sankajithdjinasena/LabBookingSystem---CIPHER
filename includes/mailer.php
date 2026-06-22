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
defined('MAIL_USERNAME')   || define('MAIL_USERNAME', $_ENV['MAIL_USERNAME'] ?? '');
defined('MAIL_PASSWORD')   || define('MAIL_PASSWORD', $_ENV['MAIL_PASSWORD'] ?? '');
defined('MAIL_FROM')       || define('MAIL_FROM',       'predictrasusl@gmail.com');
defined('MAIL_FROM_NAME')  || define('MAIL_FROM_NAME',  'SURAS Team');
defined('MAIL_ENCRYPTION') || define('MAIL_ENCRYPTION', PHPMailer::ENCRYPTION_STARTTLS);
// ─────────────────────────────────────────────────────────────────────────────

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

        // ── DEBUG: shows full SMTP conversation on screen (REMOVE AFTER FIXING)
        $mail->SMTPDebug   = SMTP::DEBUG_SERVER;
        $mail->Debugoutput = function($str, $level) {
            echo "<pre style='background:#111;color:#0f0;padding:4px;font-size:11px;margin:2px 0;'>"
               . htmlspecialchars($str) . "</pre>";
        };

        // ── SSL fix for XAMPP localhost (remove in production) ────────────────
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ]
        ];

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
            $mail->AltBody = $body;
        } else {
            $mail->isHTML(false);
            $mail->Body = $body;
        }

        $mail->send();
        return true;

    } catch (Exception $e) {
        // Show error on screen during dev
        echo "<div style='background:#ff000022;border:1px solid red;padding:10px;margin:10px;font-family:monospace;'>"
           . "<strong>Mailer Error:</strong> " . htmlspecialchars($mail->ErrorInfo)
           . "</div>";
        error_log('[SURAS Mailer] Failed to send to ' . $toEmail . ' — ' . $mail->ErrorInfo);
        return false;
    }
}
