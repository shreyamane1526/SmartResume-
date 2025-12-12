<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging
// error_reporting(E_ALL);
// ini_set('display_errors', 0);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
// Include required libraries
require_once 'tcpdf/tcpdf.php';
require_once 'phpmailer/PHPMailer.php';
require_once 'phpmailer/SMTP.php';
require_once 'phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Database configuration for admin panel tracking
function getDbConnection() {
    $host = 'localhost';
    $dbname = 'smartresume_db';
    $username = 'root';
    $password = '';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        error_log('Database connection failed: ' . $e->getMessage());
        return null;
    }
}

// Track resume activity
function trackResumeActivity($data, $action, $filePath = null, $fileSize = null) {
    $pdo = getDbConnection();
    if (!$pdo) return;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO resume_history (
                user_name, 
                user_email, 
                job_role, 
                template_used, 
                resume_data, 
                pdf_file_path, 
                action_type, 
                ip_address, 
                user_agent, 
                file_size
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $userName = trim(($data['personalInfo']['firstName'] ?? '') . ' ' . ($data['personalInfo']['lastName'] ?? ''));
        $userEmail = $data['personalInfo']['email'] ?? '';
        $jobRole = $data['jobRole']['name'] ?? 'Unknown';
        $template = $data['jobRole']['template'] ?? 'default';
        $resumeData = json_encode($data);
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt->execute([
            $userName,
            $userEmail,
            $jobRole,
            $template,
            $resumeData,
            $filePath,
            $action,
            $ipAddress,
            $userAgent,
            $fileSize
        ]);
        
    } catch(Exception $e) {
        error_log('Failed to track resume activity: ' . $e->getMessage());
    }
}

try {
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }

    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception('Invalid JSON data');
    }

    // Validate required data
    if (!isset($data['personalInfo']) || !isset($data['jobRole'])) {
        throw new Exception('Missing required resume data');
    }

    $personalInfo = $data['personalInfo'];
    $jobRole = $data['jobRole'];
    $experience = $data['experience'] ?? [];
    $education = $data['education'] ?? [];
    $skills = $data['skills'] ?? [];
    $certifications = $data['certifications'] ?? [];
    $languages = $data['languages'] ?? [];
    $action = $data['action'] ?? 'download';

    // Validate personal info
    if (empty($personalInfo['firstName']) || empty($personalInfo['lastName']) || empty($personalInfo['email'])) {
        throw new Exception('First name, last name, and email are required');
    }

    // Generate PDF
    $pdfContent = generateResumePDF($data);
    $fileSize = strlen($pdfContent);

    if ($action === 'email') {
        // Send via email
        $emailResult = emailResume($pdfContent, $personalInfo);
        
        // Track email action
        trackResumeActivity($data, 'emailed', null, $fileSize);
        
        echo json_encode($emailResult);
    } else {
        // Track download action
        trackResumeActivity($data, 'downloaded', null, $fileSize);
        
        // Return PDF for download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $personalInfo['firstName'] . '_' . $personalInfo['lastName'] . '_Resume.pdf"');
        echo $pdfContent;
    }

} catch (Exception $e) {
    // Log error for debugging
    error_log('Resume generation error: ' . $e->getMessage());
    
    // Return error response
    if ($action === 'email') {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Generate PDF resume using TCPDF
 */
function generateResumePDF($data) {
    $personalInfo = $data['personalInfo'];
    $jobRole = $data['jobRole'];
    $experience = $data['experience'] ?? [];
    $education = $data['education'] ?? [];
    $skills = $data['skills'] ?? [];
    $certifications = $data['certifications'] ?? [];
    $languages = $data['languages'] ?? [];

    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator('SmartResume');
    $pdf->SetAuthor($personalInfo['firstName'] . ' ' . $personalInfo['lastName']);
    $pdf->SetTitle($personalInfo['firstName'] . ' ' . $personalInfo['lastName'] . ' - Resume');
    $pdf->SetSubject('Professional Resume');

    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Set margins
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);

    // Add a page
    $pdf->AddPage();

    // Set font
    $pdf->SetFont('helvetica', '', 12);

    // Get role-specific template
    $template = getRoleTemplate($jobRole, $data);
    
    // Write HTML content
    $pdf->writeHTML($template, true, false, true, false, '');

    // Return PDF content
    return $pdf->Output('', 'S');
}

/**
 * Get role-specific template
 */
function getRoleTemplate($jobRole, $data) {
    $personalInfo = $data['personalInfo'];
    $experience = $data['experience'] ?? [];
    $education = $data['education'] ?? [];
    $skills = $data['skills'] ?? [];
    $certifications = $data['certifications'] ?? [];
    $languages = $data['languages'] ?? [];

    // Define role-specific colors and styling
    $roleColors = [
        'full-stack-developer' => '#1a237e',
        'data-analyst' => '#2e7d32',
        'mobile-developer' => '#e65100',
        'backend-developer' => '#4a148c',
        'frontend-developer' => '#c62828',
        'default' => '#1a237e'
    ];

    $primaryColor = $roleColors[$jobRole['id'] ?? 'default'] ?? $roleColors['default'];
    $fullName = $personalInfo['firstName'] . ' ' . $personalInfo['lastName'];

    // Build contact info
    $contactInfo = [];
    if (!empty($personalInfo['email'])) $contactInfo[] = $personalInfo['email'];
    if (!empty($personalInfo['phone'])) $contactInfo[] = $personalInfo['phone'];
    if (!empty($personalInfo['address'])) $contactInfo[] = $personalInfo['address'];
    if (!empty($personalInfo['linkedin'])) $contactInfo[] = 'LinkedIn: ' . $personalInfo['linkedin'];
    if (!empty($personalInfo['website'])) $contactInfo[] = 'Portfolio: ' . $personalInfo['website'];

    $html = '
    <style>
        .header { background-color: ' . $primaryColor . '; color: white; padding: 20px; margin-bottom: 20px; }
        .name { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
        .title { font-size: 16px; margin-bottom: 10px; }
        .contact { font-size: 10px; }
        .section-title { color: ' . $primaryColor . '; font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 10px; border-bottom: 2px solid ' . $primaryColor . '; padding-bottom: 5px; }
        .item { margin-bottom: 15px; }
        .item-title { font-weight: bold; font-size: 12px; }
        .item-subtitle { font-style: italic; color: #666; font-size: 11px; }
        .item-date { color: #888; font-size: 10px; }
        .skills-grid { display: table; width: 100%; }
        .skill-item { background-color: #f0f0f0; padding: 5px 10px; margin: 2px; border-radius: 3px; font-size: 10px; display: inline-block; }
        .two-column { width: 48%; float: left; }
        .right-column { margin-left: 52%; }
    </style>

    <div class="header">
        <div class="name">' . htmlspecialchars($fullName) . '</div>
        <div class="title">' . htmlspecialchars($jobRole['name'] ?? 'Professional') . '</div>
        <div class="contact">' . implode(' • ', array_map('htmlspecialchars', $contactInfo)) . '</div>
    </div>';

    // Professional Objective
    if (!empty($personalInfo['objective'])) {
        $html .= '
        <div class="section-title">Professional Objective</div>
        <div style="margin-bottom: 20px; text-align: justify; font-size: 11px;">' . nl2br(htmlspecialchars($personalInfo['objective'])) . '</div>';
    }

    // Experience
    if (!empty($experience)) {
        $html .= '<div class="section-title">Work Experience</div>';
        foreach ($experience as $exp) {
            if (!empty($exp['title']) && !empty($exp['company'])) {
                $endDate = $exp['current'] ? 'Present' : formatDate($exp['endDate']);
                $dateRange = formatDate($exp['startDate']) . ' - ' . $endDate;
                
                $html .= '
                <div class="item">
                    <div class="item-title">' . htmlspecialchars($exp['title']) . '</div>
                    <div class="item-subtitle">' . htmlspecialchars($exp['company']) . '</div>
                    <div class="item-date">' . htmlspecialchars($dateRange) . '</div>';
                
                if (!empty($exp['description'])) {
                    $html .= '<div style="margin-top: 5px; font-size: 10px; text-align: justify;">' . nl2br(htmlspecialchars($exp['description'])) . '</div>';
                }
                
                $html .= '</div>';
            }
        }
    }

    // Education
    if (!empty($education)) {
        $html .= '<div class="section-title">Education</div>';
        foreach ($education as $edu) {
            if (!empty($edu['degree']) && !empty($edu['institution'])) {
                $html .= '
                <div class="item">
                    <div class="item-title">' . htmlspecialchars($edu['degree']) . '</div>
                    <div class="item-subtitle">' . htmlspecialchars($edu['institution']);
                
                if (!empty($edu['year'])) {
                    $html .= ' (' . htmlspecialchars($edu['year']) . ')';
                }
                
                $html .= '</div>';
                
                if (!empty($edu['gpa'])) {
                    $html .= '<div class="item-date">GPA: ' . htmlspecialchars($edu['gpa']) . '</div>';
                }
                
                $html .= '</div>';
            }
        }
    }

    // Skills
    if (!empty($skills['technical']) || !empty($skills['soft'])) {
        $html .= '<div class="section-title">Skills</div>';
        
        if (!empty($skills['technical'])) {
            $html .= '<div style="margin-bottom: 10px;">
                <div style="font-weight: bold; font-size: 12px; margin-bottom: 5px;">Technical Skills</div>
                <div>';
            foreach ($skills['technical'] as $skill) {
                $html .= '<span class="skill-item">' . htmlspecialchars($skill) . '</span>';
            }
            $html .= '</div></div>';
        }
        
        if (!empty($skills['soft'])) {
            $html .= '<div style="margin-bottom: 10px;">
                <div style="font-weight: bold; font-size: 12px; margin-bottom: 5px;">Soft Skills</div>
                <div>';
            foreach ($skills['soft'] as $skill) {
                $html .= '<span class="skill-item">' . htmlspecialchars($skill) . '</span>';
            }
            $html .= '</div></div>';
        }
    }

    // Certifications
    if (!empty($certifications)) {
        $html .= '<div class="section-title">Certifications</div>';
        foreach ($certifications as $cert) {
            if (!empty($cert['name'])) {
                $html .= '
                <div class="item">
                    <div class="item-title">' . htmlspecialchars($cert['name']) . '</div>';
                
                if (!empty($cert['issuer']) || !empty($cert['year'])) {
                    $html .= '<div class="item-subtitle">';
                    if (!empty($cert['issuer'])) {
                        $html .= htmlspecialchars($cert['issuer']);
                    }
                    if (!empty($cert['year'])) {
                        $html .= ' (' . htmlspecialchars($cert['year']) . ')';
                    }
                    $html .= '</div>';
                }
                
                $html .= '</div>';
            }
        }
    }

    // Languages
    if (!empty($languages)) {
        $html .= '<div class="section-title">Languages</div>';
        foreach ($languages as $lang) {
            if (!empty($lang['name'])) {
                $html .= '
                <div class="item">
                    <div class="item-title">' . htmlspecialchars($lang['name']);
                if (!empty($lang['level'])) {
                    $html .= ' - ' . ucfirst(htmlspecialchars($lang['level']));
                }
                $html .= '</div></div>';
            }
        }
    }

    return $html;
}

/**
 * Email resume to user
 */
function emailResume($pdfContent, $personalInfo) {
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USERNAME'] ?? 'your-email@gmail.com';
        $mail->Password = $_ENV['SMTP_PASSWORD'] ?? 'your-app-password';
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['SMTP_PORT'] ?? 587;

        // Recipients
        $mail->setFrom($_ENV['FROM_EMAIL'] ?? 'noreply@smartresume.com', 'SmartResume');
        $mail->addAddress($personalInfo['email'], $personalInfo['firstName'] . ' ' . $personalInfo['lastName']);

        // Attachment
        $filename = $personalInfo['firstName'] . '_' . $personalInfo['lastName'] . '_Resume.pdf';
        $mail->addStringAttachment($pdfContent, $filename);

        // Email content
        $mail->isHTML(true);
        $mail->subject = 'Your SmartResume - ' . $personalInfo['firstName'] . ' ' . $personalInfo['lastName'];
        $mail->body = '
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #1a237e; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
                .btn { background: #ff6b35; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>Your Professional Resume is Ready!</h2>
                </div>
                <div class="content">
                    <p>Dear ' . htmlspecialchars($personalInfo['firstName']) . ',</p>
                    <p>Your professional resume has been successfully generated and is attached to this email.</p>
                    <p><strong>Resume Details:</strong></p>
                    <ul>
                        <li>Format: PDF</li>
                        <li>Generated: ' . date('Y-m-d H:i:s') . '</li>
                        <li>Filename: ' . htmlspecialchars($filename) . '</li>
                    </ul>
                    <p>Next Steps:</p>
                    <ul>
                        <li>Review your resume for any final adjustments</li>
                        <li>Customize it further for specific job applications</li>
                        <li>Use our Resume Analyzer for additional feedback</li>
                    </ul>
                    <p>
                        <a href="https://smartresume.com/resume-analyzer.html" class="btn">Analyze This Resume</a>
                        <a href="https://smartresume.com/resume-builder.html" class="btn">Create Another Resume</a>
                    </p>
                    <p>Best of luck with your job search!</p>
                    <p>Best regards,<br>The SmartResume Team</p>
                </div>
                <div class="footer">
                    <p>SmartResume - Professional resume building made easy</p>
                </div>
            </div>
        </body>
        </html>
        ';

        $mail->send();

        return [
            'success' => true,
            'message' => 'Resume has been sent to your email successfully!'
        ];

    } catch (Exception $e) {
        error_log('Email error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to send email: ' . $e->getMessage()
        ];
    }
}


/**
 * Format date for display
 */
function formatDate($dateString) {
    if (empty($dateString)) return '';
    
    try {
        $date = new DateTime($dateString . '-01');
        return $date->format('M Y');
    } catch (Exception $e) {
        return $dateString;
    }
}
?>
