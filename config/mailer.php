<?php
/**
 * config/mailer.php
 * PHPMailer configuration using .env values
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- Load dependencies ---
require_once __DIR__ . '/db.php'; // loads $env from .env

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
} else {
    die("Composer autoload not found. Run `composer install` first.");
}

/**
 * Send an email using PHPMailer
 *
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body HTML email body
 * @param string $altBody (optional) Plain text version
 * @return bool
 */
function sendMail($to, $subject, $body, $altBody = '') {
    global $env;

    $mail = new PHPMailer(true);

    try {
        // --- SMTP Configuration ---
        $mail->isSMTP();
        $mail->Host       = $env['MAIL_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $env['MAIL_USERNAME'] ?? '';
        $mail->Password   = $env['MAIL_PASSWORD'] ?? '';
        $mail->SMTPSecure = $env['MAIL_ENCRYPTION'] ?? PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = !empty($env['MAIL_PORT']) ? (int)$env['MAIL_PORT'] : 587;

        // Optional: enable verbose debugging
        // $mail->SMTPDebug = 2;
        // $mail->Debugoutput = 'error_log';

        // --- Sender Info ---
        $mail->setFrom(
            $env['MAIL_FROM_ADDRESS'] ?? $env['MAIL_USERNAME'],
            $env['MAIL_FROM_NAME'] ?? 'RFID Monitoring System'
        );

        // --- Recipient ---
        $mail->addAddress($to);

        // --- Content ---
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $altBody ?: strip_tags($body);

        // --- Send Email ---
        $mail->send();
        return true;
    } catch (Exception $e) {
        $logPath = dirname(__DIR__) . '/logs/system.log';
        if (!file_exists(dirname($logPath))) {
            mkdir(dirname($logPath), 0777, true);
        }

        $logMsg = '[' . date('Y-m-d H:i:s') . '] Mailer Error: ' . $mail->ErrorInfo . PHP_EOL;
        file_put_contents($logPath, $logMsg, FILE_APPEND);
        return false;
    }
}
