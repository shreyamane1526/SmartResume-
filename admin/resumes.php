<?php
require_once 'php/verify-token.php';
require_once 'php/database-config.php';

requireLogin();

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Get search parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

try {
    $pdo = DatabaseConfig::getConnection();
    
    // Build the query
    $query = 'SELECT * FROM resume_history WHERE 1=1';
    $countQuery = 'SELECT COUNT(*) FROM resume_history WHERE 1=1';
    $params = [];
    
    if (!empty($search)) {
        $query .= ' AND (user_name LIKE ? OR user_email LIKE ? OR job_role LIKE ?)';
        $countQuery .= ' AND (user_name LIKE ? OR user_email LIKE ? OR job_role LIKE ?)';
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($dateFrom)) {
        $query .= ' AND created_at >= ?';
        $countQuery .= ' AND created_at >= ?';
        $params[] = $dateFrom . ' 00:00:00';
    }
    
    if (!empty($dateTo)) {
        $query .= ' AND created_at <= ?';
        $countQuery .= ' AND created_at <= ?';
        $params[] = $dateTo . ' 23:59:59';
    }
    
    // Get total count
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalResumes = $stmt->fetchColumn();
    $totalPages = ceil($totalResumes / $perPage);
    
    // Add pagination to the query
    $query .= ' ORDER BY created_at DESC LIMIT ?, ?';
    $params[] = $offset;
    $params[] = $perPage;
    
    // Get resumes
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $resumes = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resume Management - SmartResume Admin</title>
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
        .table-container {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .table th {
            background-color: #f8f9fa;
        }
        .navbar-admin {
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 10px 20px;
        }
        .search-form {
            background-color: #fff;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
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
                <a class="nav-link" href="index.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="resumes.php">
                    <i class="fas fa-file-alt"></i> Resumes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="contacts.php">
                    <i class="fas fa-envelope"></i> Contact Messages
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
                <h4 class="m-0">Resume Management</h4>
            </div>
            <div>
                <span class="me-3"><?php echo htmlspecialchars($_SESSION['admin_user']['full_name']); ?></span>
                <a href="php/logout.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </nav>

        <!-- Search Form -->
        <div class="search-form">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, email, job role...">
                </div>
                <div class="col-md-3">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                </div>
                <div class="col-md-3">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </form>
        </div>

        <!-- Resumes Table -->
        <div class="table-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Resumes (<?php echo $totalResumes; ?>)</h5>
                <div>
                    <a href="export-resumes.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>" class="btn btn-sm btn-success">
                        <i class="fas fa-file-excel"></i> Export to CSV
                    </a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Job Role</th>
                            <th>Template</th>
                            <th>Action Type</th>
                            <th>Date</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resumes as $resume): ?>
                        <tr>
                            <td><?php echo $resume['id']; ?></td>
                            <td><?php echo htmlspecialchars($resume['user_name']); ?></td>
                            <td><?php echo htmlspecialchars($resume['user_email']); ?></td>
                            <td><?php echo htmlspecialchars($resume['job_role']); ?></td>
                            <td><?php echo htmlspecialchars($resume['template_used']); ?></td>
                            <td>
                                <?php if ($resume['action_type'] == 'created'): ?>
                                <span class="badge bg-success">Created</span>
                                <?php elseif ($resume['action_type'] == 'viewed'): ?>
                                <span class="badge bg-info">Viewed</span>
                                <?php elseif ($resume['action_type'] == 'downloaded'): ?>
                                <span class="badge bg-primary">Downloaded</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y H:i', strtotime($resume['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($resume['ip_address'] ?? 'N/A'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($resumes)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No resumes found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($dateFrom) ? '&date_from='.urlencode($dateFrom) : ''; ?><?php echo !empty($dateTo) ? '&date_to='.urlencode($dateTo) : ''; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($dateFrom) ? '&date_from='.urlencode($dateFrom) : ''; ?><?php echo !empty($dateTo) ? '&date_to='.urlencode($dateTo) : ''; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($dateFrom) ? '&date_from='.urlencode($dateFrom) : ''; ?><?php echo !empty($dateTo) ? '&date_to='.urlencode($dateTo) : ''; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
