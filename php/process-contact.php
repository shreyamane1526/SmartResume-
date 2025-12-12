<?php
// Initialize response array
$response = array('success' => false, 'message' => '');

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Include database connection
    require_once 'config.php';
    
    // Validate and sanitize input data
    $firstName = mysqli_real_escape_string($conn, trim($_POST['firstName']));
    $lastName = mysqli_real_escape_string($conn, trim($_POST['lastName']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone = isset($_POST['phone']) ? mysqli_real_escape_string($conn, trim($_POST['phone'])) : '';
    $company = isset($_POST['company']) ? mysqli_real_escape_string($conn, trim($_POST['company'])) : '';
    $subject = mysqli_real_escape_string($conn, trim($_POST['subject']));
    $message = mysqli_real_escape_string($conn, trim($_POST['message']));
    $newsletter = isset($_POST['newsletter']) ? 1 : 0;
    
    // Basic validation
    if (empty($firstName) || empty($lastName) || empty($email) || empty($subject) || empty($message)) {
        $response['message'] = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Please enter a valid email address.';
    } else {
        // Create contact_messages table if not exists
        $createTable = "CREATE TABLE IF NOT EXISTS contact_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(50),
            company VARCHAR(100),
            subject VARCHAR(100) NOT NULL,
            message TEXT NOT NULL,
            newsletter TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if (mysqli_query($conn, $createTable)) {
            // Insert data into the database
            $sql = "INSERT INTO contact_messages (first_name, last_name, email, phone, company, subject, message, newsletter) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssssssi", $firstName, $lastName, $email, $phone, $company, $subject, $message, $newsletter);
            
            if (mysqli_stmt_execute($stmt)) {
                $response['success'] = true;
                $response['message'] = 'Your message has been sent successfully! We will get back to you soon.';
            } else {
                $response['message'] = 'Error: ' . mysqli_error($conn);
            }
            
            mysqli_stmt_close($stmt);
        } else {
            $response['message'] = 'Database error: ' . mysqli_error($conn);
        }
    }
    
    // Close database connection
    mysqli_close($conn);
} else {
    $response['message'] = 'Invalid request method.';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
    $stmt = $conn->prepare("INSERT INTO contacts (firstName, lastName, email, phone, company, subject, message, subscribe, ip_address) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("sssssssis", $firstName, $lastName, $email, $phone, $company, $subject, $message, $subscribe, $ip_address);
    
    if ($stmt->execute()) {
        // Send email notification if desired
        sendNotificationEmail($firstName, $lastName, $email, $subject, $message);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Thank you for your message! We will get back to you soon.'
        ]);
    } else {
        throw new Exception("Database error: " . $stmt->error);
    }
    
} catch (Exception $e) {
    error_log("Contact form error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while processing your request. Please try again later.'
    ]);
}

/**
 * Send notification email to administrators
 */
function sendNotificationEmail($firstName, $lastName, $email, $subject, $message) {
    // This is optional - implement if you want email notifications
    // You can use PHPMailer or mail() function
    
    // Simple example with mail() function:
    $to = "admin@smartresume.com";
    $subject = "New Contact Form Submission: $subject";
    
    $body = "New contact form submission:\n\n";
    $body .= "Name: $firstName $lastName\n";
    $body .= "Email: $email\n";
    $body .= "Subject: $subject\n";
    $body .= "Message: $message\n";
    
    $headers = "From: noreply@smartresume.com";
    
    // Uncomment to enable email sending
    // mail($to, $subject, $body, $headers);
}
?>
                    <strong>Newsletter Subscription:</strong> " . ($newsletter ? 'Yes' : 'No') . "
                </div>
                <div class='field'>
                    <strong>Message:</strong><br>
                    " . nl2br(htmlspecialchars($message)) . "
                </div>
            </div>
            <div class='footer'>
                This message was sent from the SmartResume contact form.
            </div>
        </div>
    </body>
    </html>";
    
    $mail->Body = $htmlBody;
    
    // Plain text version
    $textBody = "New Contact Form Submission\n\n";
    $textBody .= "Name: " . $firstName . ' ' . $lastName . "\n";
    $textBody .= "Email: " . $email . "\n";
    $textBody .= "Phone: " . ($phone ?: 'Not provided') . "\n";
    $textBody .= "Subject: " . $subject . "\n";
    $textBody .= "Newsletter: " . ($newsletter ? 'Yes' :<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to user

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
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $newsletter = isset($_POST['newsletter']) ? 'Yes' : 'No';
    $privacy = isset($_POST['privacy']) ? true : false;

    // Validation
    $errors = [];

    if (empty($firstName)) {
        $errors[] = 'First name is required';
    }

    if (empty($lastName)) {
        $errors[] = 'Last name is required';
    }

    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }

    if (empty($subject)) {
        $errors[] = 'Subject is required';
    }

    if (empty($message)) {
        $errors[] = 'Message is required';
    }

    if (!$privacy) {
        $errors[] = 'You must agree to the Privacy Policy and Terms of Service';
    }

    // Basic spam prevention
    if (strlen($message) > 5000) {
        $errors[] = 'Message is too long';
    }

    if (!empty($errors)) {
        throw new Exception(implode(', ', $errors));
    }

    // Prepare email content
    $fullName = $firstName . ' ' . $lastName;
    $emailSubject = 'Contact Form - ' . $subject;
    
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
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>New Contact Form Submission</h2>
            </div>
            <div class='content'>
                <div class='field'>
                    <div class='label'>Name:</div>
                    <div>{$fullName}</div>
                </div>
                <div class='field'>
                    <div class='label'>Email:</div>
                    <div>{$email}</div>
                </div>
                <div class='field'>
                    <div class='label'>Phone:</div>
                    <div>" . (!empty($phone) ? $phone : 'Not provided') . "</div>
                </div>
                <div class='field'>
                    <div class='label'>Subject:</div>
                    <div>{$subject}</div>
                </div>
                <div class='field'>
                    <div class='label'>Newsletter Subscription:</div>
                    <div>{$newsletter}</div>
                </div>
                <div class='field'>
                    <div class='label'>Message:</div>
                    <div>" . nl2br(htmlspecialchars($message)) . "</div>
                </div>
                <div class='field'>
                    <div class='label'>Submitted:</div>
                    <div>" . date('Y-m-d H:i:s') . "</div>
                </div>
            </div>
            <div class='footer'>
                <p>This message was sent from the SmartResume contact form.</p>
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
    $mail->setFrom($_ENV['FROM_EMAIL'] ?? 'noreply@smartresume.com', 'SmartResume Contact Form');
    $mail->addAddress($_ENV['CONTACT_EMAIL'] ?? 'support@smartresume.com', 'SmartResume Support');
    $mail->addReplyTo($email, $fullName);

    // Content
    $mail->isHTML(true);
    $mail->Subject = $emailSubject;
    $mail->Body = $emailBody;

    // Send email
    $mail->send();

    // Optional: Save to database (implement if needed)
    // saveContactToDatabase($firstName, $lastName, $email, $phone, $subject, $message, $newsletter);

    // Send confirmation email to user
    sendConfirmationEmail($email, $fullName);

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Your message has been sent successfully! We will get back to you soon.'
    ]);

} catch (Exception $e) {
