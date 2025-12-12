<?php
/**
 * Exception - Simplified version for SmartResume
 * This is a basic implementation of PHPMailer Exception functionality
 * For production use, please install the full PHPMailer library
 */

namespace PHPMailer\PHPMailer;

class Exception extends \Exception {
    public function __construct($message = '', $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
    
    public function errorMessage() {
        return '<strong>' . htmlspecialchars($this->getMessage()) . "</strong><br />\n";
    }
}
?>
