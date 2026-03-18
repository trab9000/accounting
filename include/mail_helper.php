<?php
/**
 * Mail helper — wraps PHPMailer with Gmail SMTP.
 * Falls back to PHP mail() if SMTP credentials are not configured.
 *
 * Usage: phpbmsMail($to, $subject, $body, $from, $attachment)
 *   $to         — email address string (comma-separated for multiple)
 *   $subject    — subject line
 *   $body       — plain text or HTML body
 *   $from       — from address, e.g. "Name <email>" or just "email"
 *   $attachment — optional array: ['data'=>'...', 'name'=>'file.pdf', 'type'=>'application/pdf']
 *
 * Returns true on success, false on failure.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once(dirname(__FILE__)."/phpmailer/Exception.php");
require_once(dirname(__FILE__)."/phpmailer/PHPMailer.php");
require_once(dirname(__FILE__)."/phpmailer/SMTP.php");

function phpbmsMail($to, $subject, $body, $from = "", $attachment = null) {

    // If no SMTP credentials configured, fall back to mail()
    if (!defined("SMTP_USERNAME") || !defined("SMTP_PASSWORD")) {
        return @mail($to, $subject, $body, $from ? "From: ".$from : "");
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = "smtp.gmail.com";
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Parse From address
        $fromEmail = SMTP_USERNAME;
        $fromName  = "";
        if ($from) {
            if (preg_match('/^(.*?)\s*<(.+?)>$/', $from, $m)) {
                $fromName  = trim($m[1]);
                $fromEmail = trim($m[2]);
            } elseif (filter_var(trim($from), FILTER_VALIDATE_EMAIL)) {
                $fromEmail = trim($from);
            }
        }
        $mail->setFrom(SMTP_USERNAME, $fromName ?: $fromEmail);
        if ($fromEmail !== SMTP_USERNAME) {
            $mail->addReplyTo($fromEmail, $fromName);
        }

        // Handle multiple recipients
        foreach (explode(",", $to) as $addr) {
            $addr = trim($addr);
            if ($addr) $mail->addAddress($addr);
        }

        // Always BCC the admin
        $mail->addBCC(SMTP_USERNAME);

        $mail->Subject = $subject;

        // Detect HTML
        if (preg_match('/<[^>]+>/', $body)) {
            $mail->isHTML(true);
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);
        } else {
            $mail->isHTML(false);
            $mail->Body = $body;
        }

        // Optional attachment
        if ($attachment && isset($attachment['data']) && isset($attachment['name'])) {
            $type = isset($attachment['type']) ? $attachment['type'] : 'application/octet-stream';
            $mail->addStringAttachment($attachment['data'], $attachment['name'], PHPMailer::ENCODING_BASE64, $type);
        }

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("phpbmsMail error: " . $mail->ErrorInfo);
        return false;
    }
}
