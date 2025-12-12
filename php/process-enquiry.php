<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Include PHPMailer
require_once 'phpmailer/PHPMailer.php';
require_once 'phpmailer/SMTP.php';
require_once 'phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

try {
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }

    // Validate and sanitize input data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Validation
    $errors = [];

    if (empty($name)) {
        $errors[] = 'Name is required';
    }

    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }

    if (empty($message)) {
        $errors[] = 'Message is required';
    }

    // Basic spam prevention
    if (strlen($message) > 1000) {
        $errors[] = 'Message is too long (max 1000 characters)';
    }

    if (!empty($errors)) {
        throw new Exception(implode(', ', $errors));
    }

    // Prepare email content
    $emailSubject = 'Quick Enquiry from ' . $name;
    
    $emailBody = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #1a237e; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .field { margin-bottom: 15px; }
            .label { font-weight: bold; color: #1a237e; }
            .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
            .urgent { background: #ff6b35; color: white; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Quick Enquiry</h2>
            </div>
            <div class='urgent'>
                <strong>Quick Enquiry - Requires Fast Response</strong>
            </div>
            <div class='content'>
                <div class='field'>
                    <div class='label'>Name:</div>
                    <div>{$name}</div>
                </div>
                <div class='field'>
                    <div class='label'>Email:</div>
                    <div>{$email}</div>
                </div>
                <div class='field'>
                    <div class='label'>Message:</div>
                    <div>" . nl2br(htmlspecialchars($message)) . "</div>
                </div>
                <div class='field'>
                    <div class='label'>Submitted:</div>
                    <div>" . date('Y-m-d H:i:s') . "</div>
                </div>
                <div class='field'>
                    <div class='label'>IP Address:</div>
                    <div>" . $_SERVER['REMOTE_ADDR'] . "</div>
                </div>
            </div>
            <div class='footer'>
                <p>This is a quick enquiry from the SmartResume navbar.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    // Create PHPMailer instance
    $mail = new PHPMailer(true);

    // Server settings
    $mail->isSMTP();
    $mail->Host = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['SMTP_USERNAME'] ?? 'your-email@gmail.com';
    $mail->Password = $_ENV['SMTP_PASSWORD'] ?? 'your-app-password';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $_ENV['SMTP_PORT'] ?? 587;

    // Recipients
    $mail->setFrom($_ENV['FROM_EMAIL'] ?? 'noreply@smartresume.com', 'SmartResume Quick Enquiry');
    $mail->addAddress($_ENV['ENQUIRY_EMAIL'] ?? 'enquiry@smartresume.com', 'SmartResume Enquiries');
    $mail->addReplyTo($email, $name);

    // High priority for quick enquiries
    $mail->Priority = 1;
    $mail->addCustomHeader('X-Priority', '1');

    // Content
    $mail->isHTML(true);
    $mail->Subject = $emailSubject;
    $mail->Body = $emailBody;

    // Send email
    $mail->send();

    // Send instant confirmation to user
    sendQuickConfirmation($email, $name);

    // Optional: Save to database for tracking
    // saveEnquiryToDatabase($name, $email, $message);

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Your enquiry has been sent successfully! We will respond quickly.'
    ]);

} catch (Exception $e) {
    // Log error for debugging
    error_log('Quick enquiry error: ' . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Send quick confirmation email to user
 */
function sendQuickConfirmation($userEmail, $userName) {
    try {
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USERNAME'] ?? 'your-email@gmail.com';
        $mail->Password = $_ENV['SMTP_PASSWORD'] ?? 'your-app-password';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['SMTP_PORT'] ?? 587;

        // Recipients
        $mail->setFrom($_ENV['FROM_EMAIL'] ?? 'noreply@smartresume.com', 'SmartResume');
        $mail->addAddress($userEmail, $userName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Quick Enquiry Received - SmartResume';
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #1a237e; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
                .quick-notice { background: #ff6b35; color: white; padding: 15px; border-radius: 5px; margin: 20px 0; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Enquiry Received!</h2>
                </div>
                <div class='content'>
                    <p>Hi {$userName},</p>
                    <p>Thank you for your quick enquiry! We've received your message and will respond as soon as possible.</p>
                    <div class='quick-notice'>
                        <strong>⚡ Quick Response Guarantee</strong><br>
                        We aim to respond to all enquiries within 2-4 hours during business hours.
                    </div>
                    <p>While you wait, you can:</p>
                    <ul>
                        <li>Build your resume with our <a href='https://smartresume.com/resume-builder.html'>Resume Builder</a></li>
                        <li>Get instant feedback with our <a href='https://smartresume.com/resume-analyzer.html'>Resume Analyzer</a></li>
                        <li>Explore our <a href='https://smartresume.com/gallery.html'>Template Gallery</a></li>
                    </ul>
                    <p>Best regards,<br>The SmartResume Team</p>
                </div>
                <div class='footer'>
                    <p>SmartResume - Professional resume building made easy</p>
                    <p>Need immediate help? Check our <a href='https://smartresume.com/contact.html'>FAQ section</a></p>
                </div>
            </div>
        </body>
        </html>
        ";

        $mail->send();
    } catch (Exception $e) {
        // Log but don't fail the main process
        error_log('Quick confirmation email error: ' . $e->getMessage());
    }
}

/**
 * Save enquiry to database (implement if needed)
 */
function saveEnquiryToDatabase($name, $email, $message) {
    // Implement database saving logic here if needed
    // Example using PDO:
    /*
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=smartresume', $username, $password);
        $stmt = $pdo->prepare("INSERT INTO enquiries (name, email, message, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$name, $email, $message]);
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
    }
    */
}
?>
