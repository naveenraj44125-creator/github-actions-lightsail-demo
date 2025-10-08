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

// Handle project deletion
if ($_POST['action'] ?? '' === 'delete' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $project_id = (int)($_POST['project_id'] ?? 0);
    if ($project_id > 0) {
        try {
            // Delete related votes and comments first
            $pdo->prepare("DELETE FROM votes WHERE project_id = ?")->execute([$project_id]);
            $pdo->prepare("DELETE FROM comments WHERE project_id = ?")->execute([$project_id]);
            
            // Delete the project
            $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
            $stmt->execute([$project_id]);
            
            $message = 'Project deleted successfully!';
        } catch (PDOException $e) {
            $message = 'Error deleting project. Please try again.';
        }
    }
}

// Handle status update
if ($_POST['action'] ?? '' === 'update_status' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $project_id = (int)($_POST['project_id'] ?? 0);
    $new_status = sanitizeInput($_POST['new_status'] ?? '');
    
    if ($project_id > 0 && !empty($new_status)) {
        try {
            $stmt = $pdo->prepare("UPDATE projects SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $project_id]);
            $message = 'Project status updated successfully!';
        } catch (PDOException $e) {
            $message = 'Error updating project status. Please try again.';
        }
    }
}

// Fetch all projects with statistics
$stmt = $pdo->prepare("
    SELECT p.*, u.full_name as created_by_name,
           COUNT(DISTINCT v.id) as vote_count,
           COUNT(DISTINCT c.id) as comment_count
    FROM projects p 
    LEFT JOIN users u ON p.created_by = u.id
    LEFT JOIN votes v ON p.id = v.project_id
    LEFT JOIN comments c ON p.id = c.project_id
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$stmt->execute();
$projects = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Projects - Lightsail QBR Admin</title>
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
        .project-row {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: transform 0.3s ease;
        }
        .project-row:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .priority-badge {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
        }
        .priority-high { background: #dc3545; color: white; }
        .priority-medium { background: #ffc107; color: #212529; }
        .priority-low { background: #28a745; color: white; }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
        }
        .status-planning { background: #17a2b8; color: white; }
        .status-in-progress { background: #ffc107; color: #212529; }
        .status-completed { background: #28a745; color: white; }
        .status-on-hold { background: #6c757d; color: white; }
        .btn-sm-custom {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 15px;
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
                        <a class="nav-link active" href="manage-projects.php">
                            <i class="fas fa-tasks me-2"></i>Manage Projects
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users me-2"></i>Manage Users
                        </a>
                    </li>
                </ul>
            </div>

            <div class="admin-card">
                <div class="admin-header">
                    <h2 class="mb-0">
                        <i class="fas fa-tasks me-2"></i>Manage Projects
                    </h2>
                    <p class="mb-0 mt-2 opacity-75">View, edit, and manage all QBR projects</p>
                </div>
                
                <div class="p-4">
                    <?php if ($message): ?>
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($projects)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">No Projects Found</h4>
                            <p class="text-muted">Get started by adding your first project.</p>
                            <a href="add-project.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Add First Project
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2 text-primary"></i>
                                All Projects (<?php echo count($projects); ?>)
                            </h5>
                            <a href="add-project.php" class="btn btn-success btn-sm-custom">
                                <i class="fas fa-plus me-2"></i>Add New Project
                            </a>
                        </div>

                        <?php foreach ($projects as $project): ?>
                            <div class="project-row">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h6 class="mb-2 fw-bold">
                                            <a href="../project-details.php?id=<?php echo $project['id']; ?>" 
                                               class="text-decoration-none text-dark">
                                                <?php echo htmlspecialchars($project['title']); ?>
                                            </a>
                                        </h6>
                                        <p class="text-muted mb-2 small">
                                            <?php echo htmlspecialchars(substr($project['description'], 0, 100)); ?>
                                            <?php if (strlen($project['description']) > 100): ?>...<?php endif; ?>
                                        </p>
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="priority-badge priority-<?php echo strtolower($project['priority']); ?>">
                                                <?php echo ucfirst($project['priority']); ?>
                                            </span>
                                            <span class="status-badge status-<?php echo str_replace(' ', '-', strtolower($project['status'])); ?>">
                                                <?php echo $project['status']; ?>
                                            </span>
                                        </div>
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($project['created_by_name']); ?>
                                            <span class="ms-2">
                                                <i class="fas fa-calendar me-1"></i><?php echo date('M j, Y', strtotime($project['created_at'])); ?>
                                            </span>
                                        </small>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="text-primary">
                                                        <i class="fas fa-thumbs-up fa-lg"></i>
                                                        <div class="fw-bold"><?php echo $project['vote_count']; ?></div>
                                                        <small class="text-muted">Votes</small>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="text-info">
                                                        <i class="fas fa-comments fa-lg"></i>
                                                        <div class="fw-bold"><?php echo $project['comment_count']; ?></div>
                                                        <small class="text-muted">Comments</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <div class="d-flex flex-column gap-2">
                                            <!-- Quick Status Update -->
                                            <form method="POST" class="d-flex gap-1">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <select name="new_status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                    <option value="Planning" <?php echo $project['status'] === 'Planning' ? 'selected' : ''; ?>>Planning</option>
                                                    <option value="In Progress" <?php echo $project['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                                    <option value="Completed" <?php echo $project['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                                    <option value="On Hold" <?php echo $project['status'] === 'On Hold' ? 'selected' : ''; ?>>On Hold</option>
                                                </select>
                                            </form>
                                            
                                            <div class="d-flex gap-1">
                                                <a href="../project-details.php?id=<?php echo $project['id']; ?>" 
                                                   class="btn btn-outline-primary btn-sm-custom flex-fill">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-danger btn-sm-custom" 
                                                        onclick="confirmDelete(<?php echo $project['id']; ?>, '<?php echo htmlspecialchars($project['title'], ENT_QUOTES); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
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
                        <i class="fas fa-exclamation-triangle text-danger me-2"></i>Confirm Deletion
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the project "<span id="projectTitle"></span>"?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-warning me-2"></i>
                        <strong>Warning:</strong> This action cannot be undone. All votes and comments for this project will also be deleted.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" class="d-inline" id="deleteForm">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="project_id" id="deleteProjectId">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Delete Project
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(projectId, projectTitle) {
            document.getElementById('deleteProjectId').value = projectId;
            document.getElementById('projectTitle').textContent = projectTitle;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>
</body>
</html>
