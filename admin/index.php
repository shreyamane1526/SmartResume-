<?php
require_once 'php/verify-token.php';
require_once 'php/database-config.php';

requireLogin();

// Get statistics
try {
    $pdo = DatabaseConfig::getConnection();
    
    // Count resumes
    $stmt = $pdo->query('SELECT COUNT(*) FROM resume_history');
    $resumeCount = $stmt->fetchColumn();
    
    // Count contacts
    $stmt = $pdo->query('SELECT COUNT(*) FROM contact_submissions');
    $contactCount = $stmt->fetchColumn();
    
    // Count unread contacts
    $stmt = $pdo->query('SELECT COUNT(*) FROM contact_submissions WHERE is_read = 0');
    $unreadCount = $stmt->fetchColumn();
    
    // Recent resumes
    $stmt = $pdo->query('SELECT * FROM resume_history ORDER BY created_at DESC LIMIT 5');
    $recentResumes = $stmt->fetchAll();
    
    // Recent contacts
    $stmt = $pdo->query('SELECT * FROM contact_submissions ORDER BY created_at DESC LIMIT 5');
    $recentContacts = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SmartResume</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Arial', sans-serif;
        }
        .admin-sidebar {
            background-color: #1a1a2e;
            color: #eaeaea;
            height: 100vh;
            position: fixed;
            padding-top: 20px;
        }
        .admin-sidebar .nav-link {
            color: #b6b7b9;
            padding: 10px 20px;
            margin: 5px 0;
            border-radius: 5px;
        }
        .admin-sidebar .nav-link:hover,
        .admin-sidebar .nav-link.active {
            background-color: #262650;
            color: #ffffff;
        }
        .admin-sidebar .nav-link i {
            width: 25px;
        }
        .admin-brand {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid #2a2a5a;
        }
        .admin-brand i {
            color: #4361ee;
        }
        .admin-content {
            margin-left: 250px;
            padding: 20px;
        }
        .stat-card {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-right: 15px;
        }
        .blue-bg {
            background-color: #e1f0ff;
            color: #4361ee;
        }
        .green-bg {
            background-color: #e3f9e7;
            color: #3fb77c;
        }
        .orange-bg {
            background-color: #fff4e6;
            color: #fd7e14;
        }
        .table-container {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .navbar-admin {
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 10px 20px;
        }
        .unread-badge {
            background-color: #dc3545;
        }
        @media (max-width: 768px) {
            .admin-sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .admin-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="admin-sidebar col-md-3 col-lg-2 d-md-block">
        <div class="admin-brand d-flex align-items-center">
            <i class="fas fa-file-alt me-2 fs-4"></i>
            <span class="fs-5">SmartResume</span>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="index.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="resumes.php">
                    <i class="fas fa-file-alt"></i> Resumes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="contacts.php">
                    <i class="fas fa-envelope"></i> Contact Messages
                    <?php if ($unreadCount > 0): ?>
                    <span class="badge rounded-pill unread-badge ms-2"><?php echo $unreadCount; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php if (isSuperAdmin()): ?>
            <li class="nav-item">
                <a class="nav-link" href="users.php">
                    <i class="fas fa-users"></i> Admin Users
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item mt-5">
                <a class="nav-link" href="php/logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="admin-content">
        <!-- Top Navbar -->
        <nav class="navbar-admin mb-4 rounded d-flex justify-content-between align-items-center">
            <div>
                <h4 class="m-0">Dashboard</h4>
            </div>
            <div>
                <span class="me-3"><?php echo htmlspecialchars($_SESSION['admin_user']['full_name']); ?></span>
                <a href="php/logout.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </nav>

        <!-- Stats Cards -->
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="stat-card d-flex align-items-center">
                    <div class="stat-icon blue-bg">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div>
                        <h5 class="mb-0"><?php echo number_format($resumeCount); ?></h5>
                        <p class="text-muted mb-0">Total Resumes</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="stat-card d-flex align-items-center">
                    <div class="stat-icon green-bg">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div>
                        <h5 class="mb-0"><?php echo number_format($contactCount); ?></h5>
                        <p class="text-muted mb-0">Contact Messages</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="stat-card d-flex align-items-center">
                    <div class="stat-icon orange-bg">
                        <i class="fas fa-envelope-open"></i>
                    </div>
                    <div>
                        <h5 class="mb-0"><?php echo number_format($unreadCount); ?></h5>
                        <p class="text-muted mb-0">Unread Messages</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Resumes -->
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Recent Resumes</h5>
                <a href="resumes.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Job Role</th>
                            <th>Template</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentResumes as $resume): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($resume['user_name']); ?></td>
                            <td><?php echo htmlspecialchars($resume['user_email']); ?></td>
                            <td><?php echo htmlspecialchars($resume['job_role']); ?></td>
                            <td><?php echo htmlspecialchars($resume['template_used']); ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($resume['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentResumes)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No resumes found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Contacts -->
        <div class="table-container mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Recent Contact Messages</h5>
                <a href="contacts.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Message</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentContacts as $contact): ?>
                        <tr class="<?php echo $contact['is_read'] ? '' : 'table-light'; ?>">
                            <td><?php echo htmlspecialchars($contact['name']); ?></td>
                            <td><?php echo htmlspecialchars($contact['email']); ?></td>
                            <td><?php echo htmlspecialchars(substr($contact['message'], 0, 50)) . (strlen($contact['message']) > 50 ? '...' : ''); ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($contact['created_at'])); ?></td>
                            <td>
                                <?php if ($contact['is_read']): ?>
                                <span class="badge bg-success">Read</span>
                                <?php else: ?>
                                <span class="badge bg-warning text-dark">Unread</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentContacts)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No contact messages found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
