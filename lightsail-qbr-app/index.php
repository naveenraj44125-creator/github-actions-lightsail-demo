<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && $_SESSION['role'] === 'admin';
$userName = $isLoggedIn ? $_SESSION['username'] : '';

// Get all projects for display
$projects = [];
try {
    $stmt = $pdo->query("SELECT p.*, u.username as created_by_name, 
                         (SELECT COUNT(*) FROM votes WHERE project_id = p.id) as vote_count,
                         (SELECT COUNT(*) FROM comments WHERE project_id = p.id) as comment_count
                         FROM projects p 
                         LEFT JOIN users u ON p.created_by = u.id 
                         ORDER BY p.created_at DESC");
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching projects: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lightsail Quarterly Business Review</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-cloud"></i> Lightsail QBR
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <?php if ($isAdmin): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/manage-projects.php">
                            <i class="fas fa-cog"></i> Manage Projects
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/users.php">
                            <i class="fas fa-users"></i> Users
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if ($isLoggedIn): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($userName); ?>
                            <?php if ($isAdmin): ?>
                                <span class="badge bg-warning text-dark">Admin</span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">
                            <i class="fas fa-user-plus"></i> Register
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <!-- Header Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-gradient-primary text-white">
                    <div class="card-body">
                        <h1 class="card-title">
                            <i class="fas fa-chart-line"></i> Lightsail Quarterly Business Review
                        </h1>
                        <p class="card-text">
                            Welcome to the Lightsail QBR project management system. Here you can view, vote, and comment on projects that will be discussed in our quarterly business review.
                        </p>
                        <?php if ($isAdmin): ?>
                        <a href="admin/add-project.php" class="btn btn-light">
                            <i class="fas fa-plus"></i> Add New Project
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-project-diagram fa-2x text-primary mb-2"></i>
                        <h5 class="card-title"><?php echo count($projects); ?></h5>
                        <p class="card-text">Total Projects</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-vote-yea fa-2x text-success mb-2"></i>
                        <h5 class="card-title">
                            <?php 
                            $totalVotes = 0;
                            foreach ($projects as $project) {
                                $totalVotes += $project['vote_count'];
                            }
                            echo $totalVotes;
                            ?>
                        </h5>
                        <p class="card-text">Total Votes</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-comments fa-2x text-info mb-2"></i>
                        <h5 class="card-title">
                            <?php 
                            $totalComments = 0;
                            foreach ($projects as $project) {
                                $totalComments += $project['comment_count'];
                            }
                            echo $totalComments;
                            ?>
                        </h5>
                        <p class="card-text">Total Comments</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-calendar fa-2x text-warning mb-2"></i>
                        <h5 class="card-title">Q4 2025</h5>
                        <p class="card-text">Current Quarter</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Projects Section -->
        <div class="row">
            <div class="col-12">
                <h2 class="mb-3">
                    <i class="fas fa-list"></i> QBR Projects
                </h2>
                
                <?php if (empty($projects)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No projects have been added yet.
                    <?php if ($isAdmin): ?>
                        <a href="admin/add-project.php" class="alert-link">Add the first project</a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach ($projects as $project): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 project-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span class="badge bg-<?php echo getPriorityColor($project['priority']); ?>">
                                    <?php echo ucfirst($project['priority']); ?> Priority
                                </span>
                                <small class="text-muted">
                                    <?php echo date('M j, Y', strtotime($project['created_at'])); ?>
                                </small>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($project['title']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars(substr($project['description'], 0, 100)) . '...'; ?></p>
                                <p class="text-muted small">
                                    <i class="fas fa-user"></i> Created by: <?php echo htmlspecialchars($project['created_by_name']); ?>
                                </p>
                            </div>
                            <div class="card-footer">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-success">
                                            <i class="fas fa-thumbs-up"></i> <?php echo $project['vote_count']; ?>
                                        </span>
                                        <span class="badge bg-info">
                                            <i class="fas fa-comment"></i> <?php echo $project['comment_count']; ?>
                                        </span>
                                    </div>
                                    <div>
                                        <a href="project-details.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <?php if ($isLoggedIn): ?>
                                        <button class="btn btn-sm btn-success vote-btn" data-project-id="<?php echo $project['id']; ?>">
                                            <i class="fas fa-thumbs-up"></i> Vote
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-cloud"></i> Lightsail QBR</h5>
                    <p>Quarterly Business Review Project Management System</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>&copy; 2025 AWS Lightsail Team. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>

<?php
function getPriorityColor($priority) {
    switch ($priority) {
        case 'high': return 'danger';
        case 'medium': return 'warning';
        case 'low': return 'success';
        default: return 'secondary';
    }
}
?>
