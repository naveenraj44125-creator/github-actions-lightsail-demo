<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$message = '';

// Handle user role update
if ($_POST['action'] ?? '' === 'update_role' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $new_role = sanitizeInput($_POST['new_role'] ?? '');
    
    if ($user_id > 0 && in_array($new_role, ['admin', 'employee'])) {
        // Prevent admin from demoting themselves
        if ($user_id == $_SESSION['user_id'] && $new_role !== 'admin') {
            $message = 'You cannot change your own admin role.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->execute([$new_role, $user_id]);
                $message = 'User role updated successfully!';
            } catch (PDOException $e) {
                $message = 'Error updating user role. Please try again.';
            }
        }
    }
}

// Handle user deletion
if ($_POST['action'] ?? '' === 'delete' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $user_id = (int)($_POST['user_id'] ?? 0);
    
    if ($user_id > 0) {
        // Prevent admin from deleting themselves
        if ($user_id == $_SESSION['user_id']) {
            $message = 'You cannot delete your own account.';
        } else {
            try {
                // Delete user's votes and comments first
                $pdo->prepare("DELETE FROM votes WHERE user_id = ?")->execute([$user_id]);
                $pdo->prepare("DELETE FROM comments WHERE user_id = ?")->execute([$user_id]);
                
                // Update projects created by this user to be owned by current admin
                $pdo->prepare("UPDATE projects SET created_by = ? WHERE created_by = ?")->execute([$_SESSION['user_id'], $user_id]);
                
                // Delete the user
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                
                $message = 'User deleted successfully!';
            } catch (PDOException $e) {
                $message = 'Error deleting user. Please try again.';
            }
        }
    }
}

// Fetch all users with statistics
$stmt = $pdo->prepare("
    SELECT u.*, 
           COUNT(DISTINCT p.id) as project_count,
           COUNT(DISTINCT v.id) as vote_count,
           COUNT(DISTINCT c.id) as comment_count
    FROM users u 
    LEFT JOIN projects p ON u.id = p.created_by
    LEFT JOIN votes v ON u.id = v.user_id
    LEFT JOIN comments c ON u.id = c.user_id
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$stmt->execute();
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Lightsail QBR Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        .main-content {
            padding: 2rem 0;
        }
        .admin-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .admin-header {
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 2rem;
            text-align: center;
        }
        .admin-nav {
            background: rgba(248, 249, 250, 0.9);
            border-radius: 15px;
            padding: 1rem;
            margin-bottom: 2rem;
        }
        .admin-nav .nav-link {
            color: #495057;
            font-weight: 500;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .admin-nav .nav-link:hover {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        .admin-nav .nav-link.active {
            background: #dc3545;
            color: white;
        }
        .user-row {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: transform 0.3s ease;
        }
        .user-row:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .role-badge {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
        }
        .role-admin { background: #dc3545; color: white; }
        .role-employee { background: #17a2b8; color: white; }
        .btn-sm-custom {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 15px;
        }
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../index.php">
                <i class="fab fa-aws text-warning me-2"></i>Lightsail QBR
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user-shield me-1"></i>Admin Panel
                </span>
                <a class="nav-link" href="../index.php">
                    <i class="fas fa-home me-1"></i>Dashboard
                </a>
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="container">
            <!-- Admin Navigation -->
            <div class="admin-nav">
                <ul class="nav nav-pills justify-content-center">
                    <li class="nav-item">
                        <a class="nav-link" href="add-project.php">
                            <i class="fas fa-plus me-2"></i>Add Project
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage-projects.php">
                            <i class="fas fa-tasks me-2"></i>Manage Projects
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="users.php">
                            <i class="fas fa-users me-2"></i>Manage Users
                        </a>
                    </li>
                </ul>
            </div>

            <div class="admin-card">
                <div class="admin-header">
                    <h2 class="mb-0">
                        <i class="fas fa-users me-2"></i>Manage Users
                    </h2>
                    <p class="mb-0 mt-2 opacity-75">View and manage all registered users</p>
                </div>
                
                <div class="p-4">
                    <?php if ($message): ?>
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($users)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">No Users Found</h4>
                            <p class="text-muted">No registered users in the system.</p>
                        </div>
                    <?php else: ?>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2 text-primary"></i>
                                All Users (<?php echo count($users); ?>)
                            </h5>
                            <div class="text-muted small">
                                <i class="fas fa-info-circle me-1"></i>
                                Admins: <?php echo count(array_filter($users, fn($u) => $u['role'] === 'admin')); ?> | 
                                Employees: <?php echo count(array_filter($users, fn($u) => $u['role'] === 'employee')); ?>
                            </div>
                        </div>

                        <?php foreach ($users as $user): ?>
                            <div class="user-row">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-3">
                                                <?php echo strtoupper(substr($user['full_name'], 0, 2)); ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-1 fw-bold">
                                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                                    <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                        <span class="badge bg-primary ms-2">You</span>
                                                    <?php endif; ?>
                                                </h6>
                                                <p class="text-muted mb-1 small">
                                                    <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($user['email']); ?>
                                                </p>
                                                <p class="text-muted mb-1 small">
                                                    <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($user['username']); ?>
                                                    <span class="ms-2">
                                                        <i class="fas fa-building me-1"></i><?php echo htmlspecialchars($user['department']); ?>
                                                    </span>
                                                </p>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="role-badge role-<?php echo $user['role']; ?>">
                                                        <i class="fas fa-<?php echo $user['role'] === 'admin' ? 'user-shield' : 'user'; ?> me-1"></i>
                                                        <?php echo ucfirst($user['role']); ?>
                                                    </span>
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        Joined <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <div class="row">
                                                <div class="col-4">
                                                    <div class="text-success">
                                                        <i class="fas fa-project-diagram fa-lg"></i>
                                                        <div class="fw-bold"><?php echo $user['project_count']; ?></div>
                                                        <small class="text-muted">Projects</small>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="text-primary">
                                                        <i class="fas fa-thumbs-up fa-lg"></i>
                                                        <div class="fw-bold"><?php echo $user['vote_count']; ?></div>
                                                        <small class="text-muted">Votes</small>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="text-info">
                                                        <i class="fas fa-comments fa-lg"></i>
                                                        <div class="fw-bold"><?php echo $user['comment_count']; ?></div>
                                                        <small class="text-muted">Comments</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <div class="d-flex flex-column gap-2">
                                            <!-- Role Update -->
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <form method="POST" class="d-flex gap-1">
                                                    <input type="hidden" name="action" value="update_role">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                    <select name="new_role" class="form-select form-select-sm" onchange="this.form.submit()">
                                                        <option value="employee" <?php echo $user['role'] === 'employee' ? 'selected' : ''; ?>>Employee</option>
                                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                    </select>
                                                </form>
                                                
                                                <div class="d-flex gap-1">
                                                    <button type="button" class="btn btn-outline-danger btn-sm-custom flex-fill" 
                                                            onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name'], ENT_QUOTES); ?>')">
                                                        <i class="fas fa-trash me-1"></i>Delete User
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-center text-muted small">
                                                    <i class="fas fa-lock me-1"></i>
                                                    Cannot modify your own account
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle text-danger me-2"></i>Confirm User Deletion
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the user "<span id="userName"></span>"?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-warning me-2"></i>
                        <strong>Warning:</strong> This action cannot be undone. The user's votes and comments will be deleted, 
                        but their projects will be transferred to you.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" class="d-inline" id="deleteForm">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" id="deleteUserId">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Delete User
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(userId, userName) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('userName').textContent = userName;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>
</body>
</html>
