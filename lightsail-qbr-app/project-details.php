<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$project_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Fetch project details
$stmt = $pdo->prepare("
    SELECT p.*, u.full_name as created_by_name,
           COUNT(DISTINCT v.id) as vote_count,
           COUNT(DISTINCT c.id) as comment_count,
           EXISTS(SELECT 1 FROM votes WHERE project_id = p.id AND user_id = ?) as user_voted
    FROM projects p 
    LEFT JOIN users u ON p.created_by = u.id
    LEFT JOIN votes v ON p.id = v.project_id
    LEFT JOIN comments c ON p.id = c.project_id
    WHERE p.id = ?
    GROUP BY p.id
");
$stmt->execute([$user_id, $project_id]);
$project = $stmt->fetch();

if (!$project) {
    header('Location: index.php?error=project_not_found');
    exit();
}

// Fetch comments for this project
$stmt = $pdo->prepare("
    SELECT c.*, u.full_name, u.department 
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.project_id = ? 
    ORDER BY c.created_at DESC
");
$stmt->execute([$project_id]);
$comments = $stmt->fetchAll();

// Handle voting
if ($_POST['action'] ?? '' === 'vote' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    if (!$project['user_voted']) {
        $stmt = $pdo->prepare("INSERT INTO votes (project_id, user_id) VALUES (?, ?)");
        $stmt->execute([$project_id, $user_id]);
        header("Location: project-details.php?id=$project_id&voted=1");
        exit();
    }
}

// Handle comment submission
if ($_POST['action'] ?? '' === 'comment' && validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $comment_text = sanitizeInput($_POST['comment_text'] ?? '');
    if (!empty($comment_text)) {
        $stmt = $pdo->prepare("INSERT INTO comments (project_id, user_id, comment_text) VALUES (?, ?, ?)");
        $stmt->execute([$project_id, $user_id, $comment_text]);
        header("Location: project-details.php?id=$project_id&commented=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['title']); ?> - Lightsail QBR</title>
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
        .project-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
        }
        .project-header {
            background: linear-gradient(135deg, #ff9a56 0%, #ff6b6b 100%);
            color: white;
            padding: 2rem;
        }
        .vote-section {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 1.5rem;
            margin: 1rem 0;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .comment-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .btn-vote {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            border-radius: 25px;
            padding: 10px 25px;
            font-weight: 600;
            transition: transform 0.3s ease;
        }
        .btn-vote:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }
        .btn-vote:disabled {
            background: #6c757d;
            transform: none;
            box-shadow: none;
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
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fab fa-aws text-warning me-2"></i>Lightsail QBR
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="container">
            <?php if (isset($_GET['voted'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>Thank you for your vote!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['commented'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-comment me-2"></i>Your comment has been added!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="project-card">
                <div class="project-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="mb-2"><?php echo htmlspecialchars($project['title']); ?></h1>
                            <p class="mb-0 opacity-75">
                                <i class="fas fa-user me-2"></i>Created by <?php echo htmlspecialchars($project['created_by_name']); ?>
                                <span class="ms-3">
                                    <i class="fas fa-calendar me-2"></i><?php echo date('M j, Y', strtotime($project['created_at'])); ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <div class="mb-2">
                                <span class="priority-badge priority-<?php echo strtolower($project['priority']); ?>">
                                    <?php echo ucfirst($project['priority']); ?> Priority
                                </span>
                            </div>
                            <div>
                                <span class="status-badge status-<?php echo str_replace(' ', '-', strtolower($project['status'])); ?>">
                                    <?php echo $project['status']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-4">
                    <!-- Project Description -->
                    <div class="mb-4">
                        <h5><i class="fas fa-info-circle me-2 text-primary"></i>Project Description</h5>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
                    </div>

                    <!-- Project Details -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6><i class="fas fa-calendar-alt me-2 text-info"></i>Timeline</h6>
                            <p class="text-muted mb-1">
                                <strong>Start:</strong> <?php echo $project['start_date'] ? date('M j, Y', strtotime($project['start_date'])) : 'Not set'; ?>
                            </p>
                            <p class="text-muted">
                                <strong>End:</strong> <?php echo $project['end_date'] ? date('M j, Y', strtotime($project['end_date'])) : 'Not set'; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-chart-line me-2 text-success"></i>Statistics</h6>
                            <p class="text-muted mb-1">
                                <i class="fas fa-thumbs-up me-2"></i><?php echo $project['vote_count']; ?> votes
                            </p>
                            <p class="text-muted">
                                <i class="fas fa-comments me-2"></i><?php echo $project['comment_count']; ?> comments
                            </p>
                        </div>
                    </div>

                    <!-- Voting Section -->
                    <div class="vote-section">
                        <h5><i class="fas fa-thumbs-up me-2 text-success"></i>Vote for this Project</h5>
                        <?php if ($project['user_voted']): ?>
                            <div class="alert alert-info mb-3">
                                <i class="fas fa-check-circle me-2"></i>You have already voted for this project.
                            </div>
                            <button class="btn btn-vote text-white" disabled>
                                <i class="fas fa-check me-2"></i>Already Voted
                            </button>
                        <?php else: ?>
                            <p class="text-muted mb-3">Show your support for this project by casting your vote!</p>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="vote">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <button type="submit" class="btn btn-vote text-white">
                                    <i class="fas fa-thumbs-up me-2"></i>Vote for this Project
                                </button>
                            </form>
                        <?php endif; ?>
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-users me-1"></i><?php echo $project['vote_count']; ?> people have voted for this project
                            </small>
                        </div>
                    </div>

                    <!-- Comments Section -->
                    <div class="mt-4">
                        <h5><i class="fas fa-comments me-2 text-primary"></i>Comments & Discussion</h5>
                        
                        <!-- Add Comment Form -->
                        <div class="comment-card">
                            <form method="POST">
                                <input type="hidden" name="action" value="comment">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <div class="mb-3">
                                    <label for="comment_text" class="form-label">Add your comment:</label>
                                    <textarea class="form-control" id="comment_text" name="comment_text" rows="3" 
                                              placeholder="Share your thoughts about this project..." required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Post Comment
                                </button>
                            </form>
                        </div>

                        <!-- Display Comments -->
                        <?php if (empty($comments)): ?>
                            <div class="comment-card text-center">
                                <i class="fas fa-comment-slash fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">No comments yet</h6>
                                <p class="text-muted">Be the first to share your thoughts about this project!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment-card">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($comment['full_name']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($comment['department']); ?> • 
                                                <?php echo date('M j, Y \a\t g:i A', strtotime($comment['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
