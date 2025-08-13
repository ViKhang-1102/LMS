<?php
include_once '../includes/auth.php';
protectPage(['student']);
include_once '../config/db.php';

$currentUser = getCurrentUser($pdo);
$userId = (int)$currentUser['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assignmentId = intval($_POST['assignment_id'] ?? 0);
    $courseId     = intval($_POST['course_id'] ?? 0);
    $file         = $_FILES['assignment_file'] ?? null;

    if ($assignmentId <= 0 || $courseId <= 0 || !$file) {
        header("Location: ../pages/course_details.php?course_id={$courseId}&error=invalid_input");
        exit;
    }

    $stmt = $pdo->prepare("SELECT 1 FROM submissions WHERE assignment_id = ? AND user_id = ?");
    $stmt->execute([$assignmentId, $userId]);
    if ($stmt->fetch()) {
        header("Location: ../pages/course_details.php?course_id={$courseId}&error=already_submitted");
        exit;
    }

    $allowedTypes = ['application/pdf'];
    if ($file['error'] !== UPLOAD_ERR_OK || !in_array($file['type'], $allowedTypes) || $file['size'] > 5 * 1024 * 1024) {
        header("Location: ../pages/course_details.php?course_id={$courseId}&error=invalid_file");
        exit;
    }

    $uploadDir = __DIR__ . '/../assets/uploads/assignments/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext        = pathinfo($file['name'], PATHINFO_EXTENSION);
    $safeName   = preg_replace('/[^A-Za-z0-9_.-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
    $fileName   = "submission_{$assignmentId}_" . time() . "_{$safeName}.{$ext}";
    $targetPath = $uploadDir . $fileName;
    $webPath    = "/assets/uploads/assignments/" . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        error_log("Failed to save file: " . $targetPath);
        header("Location: ../pages/course_details.php?course_id={$courseId}&error=upload_failed");
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO submissions (assignment_id, user_id, file_path, status, submitted_at, updated_at)
        VALUES (?, ?, ?, 'pending', NOW(), NOW())
    ");
    $stmt->execute([$assignmentId, $userId, $webPath]);

    header("Location: ../pages/course_details.php?course_id={$courseId}&success=submitted");
    exit;
} else {
    header("Location: ../pages/dashboard.php");
    exit;
}


