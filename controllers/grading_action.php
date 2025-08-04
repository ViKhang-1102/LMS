<?php
include_once '../includes/auth.php';
protectPage(['admin', 'instructor']);

include_once '../config/db.php';

$currentUser = getCurrentUser($pdo);
$action = $_POST['action'] ?? '';
$successUrl = '/pages/grades.php?success=graded';
$errorUrl = '/pages/grades.php?error=grade_failed';

if ($action !== 'grade') {
    header("Location: $errorUrl");
    exit();
}

try {
    $submissionId = intval($_POST['submission_id'] ?? 0);
    $userId = intval($_POST['user_id'] ?? 0);
    $courseId = intval($_POST['course_id'] ?? 0);
    $grade = floatval($_POST['grade'] ?? -1);
    $feedback = trim($_POST['feedback'] ?? '');

    if ($submissionId <= 0 || $userId <= 0 || $courseId <= 0 || $grade < 0 || $grade > 100) {
        header("Location: $errorUrl");
        exit();
    }

    $query = "SELECT s.id, s.status, a.course_id 
              FROM submissions s 
              JOIN assignments a ON s.assignment_id = a.id 
              JOIN courses c ON a.course_id = c.id 
              WHERE s.id = ? AND s.user_id = ?";
    $params = [$submissionId, $userId];
    
    if ($currentUser['role'] === 'instructor') {
        $query .= " AND c.instructor_id = ?";
        $params[] = $currentUser['id'];
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$submission || $submission['status'] === 'graded') {
        header("Location: $errorUrl");
        exit();
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE submissions 
                           SET status = 'graded', grade = ?, updated_at = NOW() 
                           WHERE id = ?");
    $stmt->execute([$grade, $submissionId]);

    $stmt = $pdo->prepare("
        INSERT INTO grades (submission_id, user_id, course_id, grade, feedback, graded_at)
        VALUES (?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE grade = ?, feedback = ?, graded_at = NOW()
    ");
    $stmt->execute([$submissionId, $userId, $courseId, $grade, $feedback, $grade, $feedback]);

    $pdo->commit();
    header("Location: $successUrl");
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Error grading submission: " . $e->getMessage());
    header("Location: $errorUrl");
}

exit();