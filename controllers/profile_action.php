<?php
include_once '../includes/auth.php';
protectPage();
include_once '../config/db.php';

$currentUser = getCurrentUser($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $avatar = $_FILES['avatar'] ?? null;

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: /pages/profile.php?error=invalid_email');
            exit();
        }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $currentUser['id']]);
        if ($stmt->fetch()) {
            header('Location: /pages/profile.php?error=email_taken');
            exit();
        }

        $avatarPath = $currentUser['avatar_path'] ?? null;
        if ($avatar && $avatar['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png'];
            if (!in_array($avatar['type'], $allowedTypes) || $avatar['size'] > 2 * 1024 * 1024) {
                header('Location: /pages/profile.php?error=upload_failed');
                exit();
            }

            $uploadDir = __DIR__ . '/../assets/images/avatars/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            if ($avatarPath && file_exists(__DIR__ . '/..' . $avatarPath)) {
                unlink(__DIR__ . '/..' . $avatarPath);
            }

            $ext = pathinfo($avatar['name'], PATHINFO_EXTENSION);
            $newName = 'avatar_' . $currentUser['id'] . '_' . time() . '.' . $ext;
            $targetPath = $uploadDir . $newName;

            if (move_uploaded_file($avatar['tmp_name'], $targetPath)) {
                $avatarPath = '/assets/images/avatars/' . $newName;
            } else {
                error_log("Failed to upload avatar for user {$currentUser['id']}");
                header('Location: /pages/profile.php?error=upload_failed');
                exit();
            }
        }

        $params = [$username, $email, $avatarPath, $currentUser['id']];
        $sql = "UPDATE users SET username = ?, email = ?, avatar_path = ? WHERE id = ?";
        if (!empty($password)) {
            if ($password !== $confirm_password) {
                header('Location: /pages/profile.php?error=password_mismatch');
                exit();
            }
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET username = ?, email = ?, avatar_path = ?, password = ? WHERE id = ?";
            $params = [$username, $email, $avatarPath, $hashedPassword, $currentUser['id']];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        header('Location: /pages/profile.php?success=Profile updated successfully');
        exit();
    } catch (PDOException $e) {
        error_log("Profile update error: " . $e->getMessage());
        header('Location: /pages/profile.php?error=update_failed');
        exit();
    }
}

header('Location: /pages/profile.php');
?>