<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function protectPage($requiredRole = null) {
    if (!isLoggedIn()) {
        header('Location: /auth/login.php');
        exit();
    }
    if ($requiredRole) {
        if (is_array($requiredRole)) {
            if (!in_array($_SESSION['role'], $requiredRole)) {
                header('Location: /pages/dashboard.php?error=access_denied');
                exit();
            }
        } else {
            if ($_SESSION['role'] !== $requiredRole) {
                header('Location: /pages/dashboard.php?error=access_denied');
                exit();
            }
        }
    }
}

function getCurrentUser($pdo) {
    if (isLoggedIn()) {
        try {
            $stmt = $pdo->prepare("SELECT id, username, email, role, avatar_path FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error fetching user data: " . $e->getMessage());
            return null;
        }
    }
    return null;
}
?>