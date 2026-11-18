<?php
// Email Configuration - Centralized email settings
// Location: /config/email.php

require_once __DIR__ . '/config.php';

class EmailService {
    private $isLocal;
    private $adminEmail;
    private $siteName;
    private $siteUrl;
    
    public function __construct() {
        $this->isLocal = Environment::isLocal();
        $this->adminEmail = getenv('ADMIN_EMAIL') ?: 'admin@umojawetu.com';
        $this->siteName = getenv('SITE_NAME') ?: 'Umojawetu Platform';
        $this->siteUrl = Environment::getConfig()['site_url'];
    }
    
    /**
     * Send email using mail() function (local) or SMTP (production)
     * For local development, emails are logged to file instead of sent
     */
    public function send($to, $subject, $body, $isHtml = true) {
        $headers = $isHtml ? "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n" : "";
        $headers .= "From: {$this->siteName} <noreply@{$this->siteUrl}>\r\n";
        $headers .= "Reply-To: {$this->adminEmail}\r\n";
        
        if ($this->isLocal) {
            // Log email instead of sending (for development)
            return $this->logEmail($to, $subject, $body);
        } else {
            // Actually send email (production)
            return mail($to, $subject, $body, $headers);
        }
    }
    
    /**
     * Log email to file for local development
     */
    private function logEmail($to, $subject, $body) {
        $logDir = __DIR__ . '/../storage/logs';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $logFile = $logDir . '/emails.log';
        $timestamp = date('Y-m-d H:i:s');
        
        // Strip HTML tags for log readability
        $plainBody = strip_tags($body);
        
        $logEntry = "========================================\n";
        $logEntry .= "TIME: $timestamp\n";
        $logEntry .= "TO: $to\n";
        $logEntry .= "SUBJECT: $subject\n";
        $logEntry .= "BODY:\n$plainBody\n";
        $logEntry .= "========================================\n\n";
        
        file_put_contents($logFile, $logEntry, FILE_APPEND);
        return true;
    }
    
    /**
     * Send booking confirmation email
     */
    public function sendBookingConfirmation($bookingRef, $customerName, $customerEmail, $eventTitle, $eventDate, $tickets, $totalAmount) {
        $subject = "Booking Confirmation - {$this->siteName}";
        
        $body = $this->getBookingConfirmationTemplate($bookingRef, $customerName, $eventTitle, $eventDate, $tickets, $totalAmount);
        
        // Send to customer
        $this->send($customerEmail, $subject, $body);
        
        // Also notify admin
        $adminBody = "<h2>New Booking!</h2>
                      <p><strong>Booking Reference:</strong> $bookingRef</p>
                      <p><strong>Customer:</strong> $customerName ($customerEmail)</p>
                      <p><strong>Event:</strong> $eventTitle</p>
                      <p><strong>Tickets:</strong> " . json_encode($tickets) . "</p>
                      <p><strong>Total:</strong> £$totalAmount</p>";
        $this->send($this->adminEmail, "New Booking: $bookingRef", $adminBody);
        
        return true;
    }
    
    /**
     * Send payment receipt
     */
    public function sendPaymentReceipt($bookingRef, $customerName, $customerEmail, $amount, $paymentMethod, $transactionId) {
        $subject = "Payment Receipt - {$this->siteName}";
        
        $body = "<!DOCTYPE html>
        <html>
        <head><style>body{font-family:Arial,sans-serif;}</style></head>
        <body>
            <h2>Payment Receipt</h2>
            <p>Dear $customerName,</p>
            <p>Thank you for your payment. Your transaction has been completed successfully.</p>
            <hr>
            <p><strong>Booking Reference:</strong> $bookingRef</p>
            <p><strong>Amount Paid:</strong> £$amount</p>
            <p><strong>Payment Method:</strong> $paymentMethod</p>
            <p><strong>Transaction ID:</strong> $transactionId</p>
            <hr>
            <p>You can check your booking status at: <a href='{$this->siteUrl}/public/status.php'>{$this->siteUrl}/public/status.php</a></p>
            <p>Thank you for your support!</p>
            <p>Best regards,<br>{$this->siteName} Team</p>
        </body>
        </html>";
        
        return $this->send($customerEmail, $subject, $body);
    }
    
    /**
     * Send request status update
     */
    public function sendRequestStatusUpdate($requestRef, $customerName, $customerEmail, $status, $notes = '') {
        $subject = "Request Update - {$this->siteName}";
        
        $statusText = ucfirst($status);
        $statusColor = $status == 'approved' ? 'green' : ($status == 'rejected' ? 'red' : 'orange');
        
        $body = "<!DOCTYPE html>
        <html>
        <head><style>body{font-family:Arial,sans-serif;}</style></head>
        <body>
            <h2>Request Status Update</h2>
            <p>Dear $customerName,</p>
            <p>Your request <strong>$requestRef</strong> has been <strong style='color:$statusColor'>$statusText</strong>.</p>
            " . ($notes ? "<p><strong>Notes:</strong> $notes</p>" : "") . "
            <p>You can check your request status at: <a href='{$this->siteUrl}/public/status.php'>{$this->siteUrl}/public/status.php</a></p>
            <p>Best regards,<br>{$this->siteName} Team</p>
        </body>
        </html>";
        
        return $this->send($customerEmail, $subject, $body);
    }
    
    /**
     * Send pledge confirmation
     */
    public function sendPledgeConfirmation($pledgeRef, $pledgerName, $pledgerEmail, $amount, $paymentMethod) {
        $subject = "Pledge Confirmation - {$this->siteName}";
        
        $body = "<!DOCTYPE html>
        <html>
        <head><style>body{font-family:Arial,sans-serif;}</style></head>
        <body>
            <h2>Thank You for Your Pledge!</h2>
            <p>Dear $pledgerName,</p>
            <p>Thank you for your generous pledge of <strong>£$amount</strong> to support our community.</p>
            <hr>
            <p><strong>Pledge Reference:</strong> $pledgeRef</p>
            <p><strong>Payment Method:</strong> $paymentMethod</p>
            <hr>
            <p>Your name will appear on our <a href='{$this->siteUrl}/public/pledge-wall.php'>Pledge Wall</a> once your payment is processed.</p>
            <p>Thank you for making a difference!</p>
            <p>Best regards,<br>{$this->siteName} Team</p>
        </body>
        </html>";
        
        return $this->send($pledgerEmail, $subject, $body);
    }
    
    /**
     * Get booking confirmation email template
     */
    private function getBookingConfirmationTemplate($bookingRef, $customerName, $eventTitle, $eventDate, $tickets, $totalAmount) {
        $ticketHtml = "<ul>";
        if ($tickets['adult'] > 0) $ticketHtml .= "<li>👤 Adults: {$tickets['adult']}</li>";
        if ($tickets['child'] > 0) $ticketHtml .= "<li>👶 Children: {$tickets['child']}</li>";
        if (($tickets['vip'] ?? 0) > 0) $ticketHtml .= "<li>⭐ VIP: {$tickets['vip']}</li>";
        $ticketHtml .= "</ul>";
        
        return "<!DOCTYPE html>
        <html>
        <head><style>body{font-family:Arial,sans-serif;}</style></head>
        <body>
            <div style='max-width:600px;margin:0 auto;padding:20px;border:1px solid #ddd;border-radius:10px;'>
                <div style='text-align:center;border-bottom:2px solid #e74c3c;padding-bottom:10px;'>
                    <h2 style='color:#e74c3c;'>Booking Confirmed!</h2>
                </div>
                <p>Dear $customerName,</p>
                <p>Your booking has been confirmed. Please find your booking details below:</p>
                <hr>
                <p><strong>Booking Reference:</strong> $bookingRef</p>
                <p><strong>Event:</strong> $eventTitle</p>
                <p><strong>Date:</strong> $eventDate</p>
                <p><strong>Tickets:</strong></p>
                $ticketHtml
                <p><strong>Total Amount:</strong> £$totalAmount</p>
                <hr>
                <p>You can check your booking status at: <a href='{$this->siteUrl}/public/status.php'>{$this->siteUrl}/public/status.php</a></p>
                <p>We look forward to seeing you at the event!</p>
                <p>Best regards,<br>{$this->siteName} Team</p>
                <hr>
                <p style='font-size:12px;color:#999;'>This is an automated message. Please do not reply to this email.</p>
            </div>
        </body>
        </html>";
    }
}

// Create global instance
$emailService = new EmailService();
?>