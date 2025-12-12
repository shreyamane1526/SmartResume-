<?php
session_start();
require_once '../includes/db_connection.php';

// Check if user is logged in (simple check, improve this in production)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Handle contact deletion
if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    try {
        $stmt = $conn->prepare("DELETE FROM contacts WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "Contact deleted successfully!";
        } else {
            $_SESSION['error_msg'] = "Failed to delete contact: " . $stmt->error;
        }
    } catch (Exception $e) {
        $_SESSION['error_msg'] = "Error: " . $e->getMessage();
    }
    
    header('Location: contacts.php');
    exit();
}

// Handle marking as read
if (isset($_GET['mark_read']) && !empty($_GET['mark_read'])) {
    $contact_id = intval($_GET['mark_read']);
    
    try {
        $stmt = $conn->prepare("UPDATE contacts SET is_read = 1 WHERE id = ?");
        $stmt->bind_param("i", $contact_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "Contact marked as read!";
        } else {
            $_SESSION['error_msg'] = "Failed to update contact: " . $stmt->error;
        }
    } catch (Exception $e) {
        $_SESSION['error_msg'] = "Error: " . $e->getMessage();
    }
    
    header('Location: contacts.php');
    exit();
}

// Fetch all contacts with pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

try {
    // Get total count for pagination
    $count_result = $conn->query("SELECT COUNT(*) as total FROM contacts");
    $total_contacts = $count_result->fetch_assoc()['total'];
    $total_pages = ceil($total_contacts / $limit);
    
    // Get contacts for current page
    $result = $conn->query("SELECT * FROM contacts ORDER BY created_at DESC LIMIT $offset, $limit");
    $contacts = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $contacts[] = $row;
        }
    }
} catch (Exception $e) {
    $_SESSION['error_msg'] = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Contact Management</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include('includes/sidebar.php'); ?>
        
        <div class="content-area">
            <div class="content-header">
                <h2>Contact Management</h2>
            </div>
            
            <?php if (isset($_SESSION['success_msg'])): ?>
                <div class="alert alert-success">
                    <?php 
                        echo $_SESSION['success_msg']; 
                        unset($_SESSION['success_msg']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_msg'])): ?>
                <div class="alert alert-danger">
                    <?php 
                        echo $_SESSION['error_msg']; 
                        unset($_SESSION['error_msg']);
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="data-container">
                <?php if (!empty($contacts)): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Subject</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contacts as $contact): ?>
                                <tr class="<?php echo $contact['is_read'] ? '' : 'unread-row'; ?>">
                                    <td><?php echo $contact['id']; ?></td>
                                    <td><?php echo htmlspecialchars($contact['firstName'] . ' ' . $contact['lastName']); ?></td>
                                    <td><?php echo htmlspecialchars($contact['email']); ?></td>
                                    <td><?php echo htmlspecialchars($contact['subject']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($contact['created_at'])); ?></td>
                                    <td>
                                        <?php if ($contact['is_read']): ?>
                                            <span class="status-read">Read</span>
                                        <?php else: ?>
                                            <span class="status-unread">Unread</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions">
                                        <a href="view_contact.php?id=<?php echo $contact['id']; ?>" class="btn-view" title="View"><i class="fas fa-eye"></i></a>
                                        <?php if (!$contact['is_read']): ?>
                                            <a href="contacts.php?mark_read=<?php echo $contact['id']; ?>" class="btn-mark-read" title="Mark as Read"><i class="fas fa-check"></i></a>
                                        <?php endif; ?>
                                        <a href="contacts.php?delete_id=<?php echo $contact['id']; ?>" class="btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this contact?');"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo ($page - 1); ?>" class="page-link">&laquo; Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>" class="page-link <?php echo ($page == $i) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo ($page + 1); ?>" class="page-link">Next &raquo;</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="no-data">
                        <p>No contacts found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/admin.js"></script>
</body>
</html>
