<?php
/**
 * PHPMailer - Simplified version for SmartResume
 * This is a basic implementation of PHPMailer functionality
 * For production use, please install the full PHPMailer library
 */

namespace PHPMailer\PHPMailer;

class PHPMailer {
    const ENCRYPTION_STARTTLS = 'tls';
    const ENCRYPTION_SMTPS = 'ssl';
    
    public $Host = '';
    public $SMTPAuth = false;
    public $Username = '';
    public $Password = '';
    public $SMTPSecure = '';
    public $Port = 587;
    public $Priority = 3;
    
    private $from = [];
    private $to = [];
    private $replyTo = [];
    private $attachments = [];
    private $customHeaders = [];
    private $isHTML = false;
    private $subject = '';
    private $body = '';
    private $exceptions = false;
    
    public function __construct($exceptions = null) {
        if ($exceptions !== null) {
            $this->exceptions = $exceptions;
        }
    }
    
    public function isSMTP() {
        // Set mailer to use SMTP
        return true;
    }
    
    public function setFrom($address, $name = '') {
        $this->from = ['address' => $address, 'name' => $name];
    }
    
    public function addAddress($address, $name = '') {
        $this->to[] = ['address' => $address, 'name' => $name];
    }
    
    public function addReplyTo($address, $name = '') {
        $this->replyTo = ['address' => $address, 'name' => $name];
    }
    
    public function addAttachment($path, $name = '') {
        $this->attachments[] = ['path' => $path, 'name' => $name];
    }
    
    public function addStringAttachment($string, $filename) {
        $this->attachments[] = ['string' => $string, 'filename' => $filename];
    }
    
    public function addCustomHeader($name, $value = null) {
        if ($value === null) {
            $this->customHeaders[] = $name;
        } else {
            $this->customHeaders[] = $name . ': ' . $value;
        }
    }
    
    public function isHTML($isHtml = true) {
        $this->isHTML = $isHtml;
    }
    
    public function send() {
        try {
            // Basic validation
            if (empty($this->from['address'])) {
                throw new Exception('From address is required');
            }
            
            if (empty($this->to)) {
                throw new Exception('At least one recipient is required');
            }
            
            if (empty($this->subject)) {
                throw new Exception('Subject is required');
            }
            
            // Prepare headers
            $headers = [];
            $headers[] = 'From: ' . $this->formatAddress($this->from);
            
            if (!empty($this->replyTo)) {
                $headers[] = 'Reply-To: ' . $this->formatAddress($this->replyTo);
            }
            
            if ($this->isHTML) {
                $headers[] = 'Content-Type: text/html; charset=UTF-8';
            } else {
                $headers[] = 'Content-Type: text/plain; charset=UTF-8';
            }
            
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'X-Mailer: SmartResume PHPMailer';
            
            if ($this->Priority < 3) {
                $headers[] = 'X-Priority: ' . $this->Priority;
            }
            
            // Add custom headers
            foreach ($this->customHeaders as $header) {
                $headers[] = $header;
            }
            
            // Handle attachments
            $body = $this->body;
            if (!empty($this->attachments)) {
                $boundary = 'boundary_' . uniqid();
                $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';
                
                $body = $this->createMultipartBody($boundary);
            }
            
            // Send to all recipients
            $success = true;
            foreach ($this->to as $recipient) {
                $to = $this->formatAddress($recipient);
                
                if (!mail($to, $this->subject, $body, implode("\r\n", $headers))) {
                    $success = false;
                    if ($this->exceptions) {
                        throw new Exception('Failed to send email to: ' . $to);
                    }
                }
            }
            
            return $success;
            
        } catch (Exception $e) {
            if ($this->exceptions) {
                throw $e;
            }
            return false;
        }
    }
    
    private function formatAddress($address) {
        if (!empty($address['name'])) {
            return $address['name'] . ' <' . $address['address'] . '>';
        }
        return $address['address'];
    }
    
    private function createMultipartBody($boundary) {
        $body = "This is a multi-part message in MIME format.\r\n\r\n";
        
        // Add main body
        $body .= "--" . $boundary . "\r\n";
        if ($this->isHTML) {
            $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        } else {
            $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        }
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $this->body . "\r\n\r\n";
        
        // Add attachments
        foreach ($this->attachments as $attachment) {
            $body .= "--" . $boundary . "\r\n";
            
            if (isset($attachment['string'])) {
                // String attachment
                $content = $attachment['string'];
                $filename = $attachment['filename'];
                $body .= "Content-Type: application/octet-stream\r\n";
                $body .= "Content-Disposition: attachment; filename=\"" . $filename . "\"\r\n";
                $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $body .= chunk_split(base64_encode($content)) . "\r\n";
            } else {
                // File attachment
                $filename = !empty($attachment['name']) ? $attachment['name'] : basename($attachment['path']);
                if (file_exists($attachment['path'])) {
                    $content = file_get_contents($attachment['path']);
                    $body .= "Content-Type: application/octet-stream\r\n";
                    $body .= "Content-Disposition: attachment; filename=\"" . $filename . "\"\r\n";
                    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
                    $body .= chunk_split(base64_encode($content)) . "\r\n";
                }
            }
        }
        
        $body .= "--" . $boundary . "--\r\n";
        
        return $body;
    }
    
    public function __set($name, $value) {
        $this->$name = $value;
    }
    
    public function __get($name) {
        return isset($this->$name) ? $this->$name : null;
    }
}
?>
