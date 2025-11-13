<?php
// ===============================================
// test_mail.php
// -----------------------------------------------
// This script tests PHPMailer using your .env Gmail config.
// Place this file in the ROOT folder (same level as /config).
// Then run: http://localhost/rfid_monitoring_system/test_mail.php
// ===============================================

// Load mailer
require_once __DIR__ . '/config/mailer.php';

// === Test Parameters ===
// Change this to your personal email to receive the test message
$to = 'bardagz329@gmail.com'; // ← Replace with your email (e.g., bardagz329@gmail.com)
$subject = '✅ RFID Monitoring System - Test Email';
$body = '
    <h2>RFID Monitoring System Test</h2>
    <p>This is a <b>test email</b> from your RFID Door Lock & Monitoring System.</p>
    <p>If you received this, your <b>PHPMailer + Gmail configuration</b> works correctly!</p>
    <hr>
    <small>Sent on ' . date('Y-m-d H:i:s') . ' from your localhost environment.</small>
';

// === Send Mail ===
echo "<pre>";
if (sendMail($to, $subject, $body)) {
    echo "✅ Email sent successfully to: {$to}\n";
} else {
    echo "❌ Failed to send email. Check your logs or .env configuration.\n";
}
echo "</pre>";
