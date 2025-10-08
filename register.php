<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = sanitizeInput($_POST['full_name'] ?? '');
    $department = sanitizeInput($_POST['department'] ?? '');
    
    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            $error = 'Username or email already exists.';
        } else {
            // Register the user
            if (register($username, $email, $password, $full_name, $department)) {
                $success = 'Registration successful! You can now log in.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Lightsail QBR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .register-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .register-header {
            background: linear-gradient(135deg, #ff9a56 0%, #ff6b6b 100%);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 2rem;
            text-align: center;
        }
        .form-control {
            border: none;
            border-bottom: 2px solid #e9ecef;
            border-radius: 0;
            padding: 0.75rem 0;
            background: transparent;
            transition: border-color 0.3s ease;
        }
        .form-control:focus {
            border-bottom-color: #ff6b6b;
            box-shadow: none;
            background: transparent;
        }
        .btn-register {
            background: linear-gradient(135deg, #ff9a56 0%, #ff6b6b 100%);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: transform 0.3s ease;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
        }
        .input-group-text {
            background: transparent;
            border: none;
            border-bottom: 2px solid #e9ecef;
            border-radius: 0;
            color: #6c757d;
        }
        .aws-logo {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 24px;
            color: #ff9900;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="register-container">
                    <div class="register-header">
                        <div class="aws-logo">
                            <i class="fab fa-aws"></i>
                        </div>
                        <h2 class="mb-0">Join Lightsail QBR</h2>
                        <p class="mb-0 mt-2">Create your account to participate</p>
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
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-user"></i>
                                        </span>
                                        <input type="text" class="form-control" name="username" placeholder="Username" 
                                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-envelope"></i>
                                        </span>
                                        <input type="email" class="form-control" name="email" placeholder="Email Address" 
                                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-id-card"></i>
                                    </span>
                                    <input type="text" class="form-control" name="full_name" placeholder="Full Name" 
                                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-building"></i>
                                    </span>
                                    <select class="form-control" name="department" required>
                                        <option value="">Select Department</option>
                                        <option value="Engineering" <?php echo ($_POST['department'] ?? '') === 'Engineering' ? 'selected' : ''; ?>>Engineering</option>
                                        <option value="Product" <?php echo ($_POST['department'] ?? '') === 'Product' ? 'selected' : ''; ?>>Product</option>
                                        <option value="Marketing" <?php echo ($_POST['department'] ?? '') === 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
                                        <option value="Sales" <?php echo ($_POST['department'] ?? '') === 'Sales' ? 'selected' : ''; ?>>Sales</option>
                                        <option value="Operations" <?php echo ($_POST['department'] ?? '') === 'Operations' ? 'selected' : ''; ?>>Operations</option>
                                        <option value="Finance" <?php echo ($_POST['department'] ?? '') === 'Finance' ? 'selected' : ''; ?>>Finance</option>
                                        <option value="HR" <?php echo ($_POST['department'] ?? '') === 'HR' ? 'selected' : ''; ?>>Human Resources</option>
                                        <option value="Other" <?php echo ($_POST['department'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input type="password" class="form-control" name="password" placeholder="Password" required>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input type="password" class="form-control" name="confirm_password" placeholder="Confirm Password" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-register btn-lg text-white">
                                    <i class="fas fa-user-plus me-2"></i>Create Account
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center">
                            <p class="text-muted">Already have an account?</p>
                            <a href="login.php" class="btn btn-outline-secondary">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
