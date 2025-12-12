<?php
session_start();
require_once '../includes/db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Check if contact ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_msg'] = "No contact ID provided!";
    header('Location: contacts.php');
    exit();
}

$contact_id = intval($_GET['id']);

// Fetch contact details
try {
    $stmt = $conn->prepare("SELECT * FROM contacts WHERE id = ?");
    $stmt->bind_param("i", $contact_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error_msg'] = "Contact not found!";
        header('Location: contacts.php');
        exit();
    }
    
    $contact = $result->fetch_assoc();
    
    // Mark as read if not already
    if (!$contact['is_read']) {
        $update_stmt = $conn->prepare("UPDATE contacts SET is_read = 1 WHERE id = ?");
        $update_stmt->bind_param("i", $contact_id);
        $update_stmt->execute();
    }
    
} catch (Exception $e) {
    $_SESSION['error_msg'] = "Database error: " . $e->getMessage();
    header('Location: contacts.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Contact - Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include('includes/sidebar.php'); ?>
        
        <div class="content-area">
            <div class="content-header">
                <h2>View Contact</h2>
                <div class="header-actions">
                    <a href="contacts.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Contacts</a>
                </div>
            </div>
            
            <div class="data-container">
                <div class="contact-details">
                    <div class="contact-header">
                        <h3><?php echo htmlspecialchars($contact['firstName'] . ' ' . $contact['lastName']); ?></h3>
                        <span class="contact-date"><?php echo date('F d, Y - h:i A', strtotime($contact['created_at'])); ?></span>
                    </div>
                    
                    <div class="contact-info-grid">
                        <div class="info-group">
                            <label>Email:</label>
                            <div class="info-value">
                                <a href="mailto:<?php echo htmlspecialchars($contact['email']); ?>">
                                    <?php echo htmlspecialchars($contact['email']); ?>
                                </a>
                            </div>
                        </div>
                        
                        <div class="info-group">
                            <label>Phone:</label>
                            <div class="info-value">
                                <?php echo !empty($contact['phone']) ? htmlspecialchars($contact['phone']) : 'Not provided'; ?>
                            </div>
                        </div>
                        
                        <div class="info-group">
                            <label>Company:</label>
                            <div class="info-value">
                                <?php echo !empty($contact['company']) ? htmlspecialchars($contact['company']) : 'Not provided'; ?>
                            </div>
                        </div>
                        
                        <div class="info-group">
                            <label>Subject:</label>
                            <div class="info-value">
                                <?php echo htmlspecialchars($contact['subject']); ?>
                            </div>
                        </div>
                        
                        <div class="info-group">
                            <label>Newsletter:</label>
                            <div class="info-value">
                                <?php echo $contact['subscribe'] ? 'Yes' : 'No'; ?>
                            </div>
                        </div>
                        
                        <div class="info-group">
                            <label>IP Address:</label>
                            <div class="info-value">
                                <?php echo htmlspecialchars($contact['ip_address']); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="message-container">
                        <label>Message:</label>
                        <div class="message-content">
                            <?php echo nl2br(htmlspecialchars($contact['message'])); ?>
                        </div>
                    </div>
                    
                    <div class="contact-actions">
                        <a href="mailto:<?php echo htmlspecialchars($contact['email']); ?>" class="btn btn-primary">
                            <i class="fas fa-reply"></i> Reply via Email
                        </a>
                        <a href="contacts.php?delete_id=<?php echo $contact['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this contact?');">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/admin.js"></script>
</body>
</html>
