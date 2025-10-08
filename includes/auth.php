<?php
// Authentication functions for Lightsail QBR Application

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: index.php');
        exit();
    }
}

function login($username, $password) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id, username, password, role, email FROM users WHERE username = ? AND active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            
            // Update last login
            $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$user['id']]);
            
            return true;
        }
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

function logout() {
    session_destroy();
    header('Location: index.php');
    exit();
}

function register($username, $email, $password, $role = 'employee') {
    global $pdo;
    
    try {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            return false; // User already exists
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
        return $stmt->execute([$username, $email, $hashedPassword, $role]);
    } catch (PDOException $e) {
        return false;
    }
}

function getUserById($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id, username, email, role, created_at, last_login FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return false;
    }
}

function updateUserProfile($userId, $email, $newPassword = null) {
    global $pdo;
    
    try {
        if ($newPassword) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET email = ?, password = ? WHERE id = ?");
            return $stmt->execute([$email, $hashedPassword, $userId]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
            return $stmt->execute([$email, $userId]);
        }
    } catch (PDOException $e) {
        return false;
    }
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePassword($password) {
    // Password must be at least 8 characters long
    return strlen($password) >= 8;
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
