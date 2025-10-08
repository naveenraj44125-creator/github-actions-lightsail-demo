<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $priority = sanitizeInput($_POST['priority'] ?? '');
    $status = sanitizeInput($_POST['status'] ?? '');
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    
    // Validate inputs
    if (empty($title) || empty($description) || empty($priority) || empty($status)) {
        $error = 'Title, description, priority, and status are required.';
    } elseif ($start_date && $end_date && strtotime($start_date) > strtotime($end_date)) {
        $error = 'End date must be after start date.';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO projects (title, description, priority, status, start_date, end_date, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $title, 
                $description, 
                $priority, 
                $status, 
                $start_date ?: null, 
                $end_date ?: null, 
                $_SESSION['user_id']
            ]);
            $success = 'Project added successfully!';
            
            // Clear form data on success
            $_POST = [];
        } catch (PDOException $e) {
            $error = 'Failed to add project. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Project - Lightsail QBR Admin</title>
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
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            transition: border-color 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        .btn-admin {
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: transform 0.3s ease;
        }
        .btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
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
                        <a class="nav-link active" href="add-project.php">
                            <i class="fas fa-plus me-2"></i>Add Project
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage-projects.php">
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

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="admin-card">
                        <div class="admin-header">
                            <h2 class="mb-0">
                                <i class="fas fa-plus-circle me-2"></i>Add New Project
                            </h2>
                            <p class="mb-0 mt-2 opacity-75">Create a new project for the QBR presentation</p>
                        </div>
                        
                        <div class="p-4">
                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <?php echo htmlspecialchars($error); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($success): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <?php echo htmlspecialchars($success); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <div class="mb-4">
                                    <label for="title" class="form-label fw-bold">
                                        <i class="fas fa-heading me-2 text-primary"></i>Project Title *
                                    </label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           placeholder="Enter project title" 
                                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="description" class="form-label fw-bold">
                                        <i class="fas fa-align-left me-2 text-info"></i>Project Description *
                                    </label>
                                    <textarea class="form-control" id="description" name="description" rows="4" 
                                              placeholder="Describe the project objectives, scope, and expected outcomes..." required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label for="priority" class="form-label fw-bold">
                                            <i class="fas fa-exclamation-circle me-2 text-warning"></i>Priority *
                                        </label>
                                        <select class="form-select" id="priority" name="priority" required>
                                            <option value="">Select Priority</option>
                                            <option value="high" <?php echo ($_POST['priority'] ?? '') === 'high' ? 'selected' : ''; ?>>High Priority</option>
                                            <option value="medium" <?php echo ($_POST['priority'] ?? '') === 'medium' ? 'selected' : ''; ?>>Medium Priority</option>
                                            <option value="low" <?php echo ($_POST['priority'] ?? '') === 'low' ? 'selected' : ''; ?>>Low Priority</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="status" class="form-label fw-bold">
                                            <i class="fas fa-flag me-2 text-success"></i>Status *
                                        </label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="">Select Status</option>
                                            <option value="Planning" <?php echo ($_POST['status'] ?? '') === 'Planning' ? 'selected' : ''; ?>>Planning</option>
                                            <option value="In Progress" <?php echo ($_POST['status'] ?? '') === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="Completed" <?php echo ($_POST['status'] ?? '') === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="On Hold" <?php echo ($_POST['status'] ?? '') === 'On Hold' ? 'selected' : ''; ?>>On Hold</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label for="start_date" class="form-label fw-bold">
                                            <i class="fas fa-calendar-alt me-2 text-primary"></i>Start Date
                                        </label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" 
                                               value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="end_date" class="form-label fw-bold">
                                            <i class="fas fa-calendar-check me-2 text-danger"></i>End Date
                                        </label>
                                        <input type="date" class="form-control" id="end_date" name="end_date" 
                                               value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="manage-projects.php" class="btn btn-outline-secondary me-md-2">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-admin text-white">
                                        <i class="fas fa-save me-2"></i>Add Project
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-set end date to be at least start date
        document.getElementById('start_date').addEventListener('change', function() {
            const startDate = this.value;
            const endDateInput = document.getElementById('end_date');
            if (startDate && (!endDateInput.value || endDateInput.value < startDate)) {
                endDateInput.min = startDate;
            }
        });
    </script>
</body>
</html>
