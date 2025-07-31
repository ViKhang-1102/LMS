<?php
include_once '../includes/auth.php';
protectPage(['student']);

include_once '../config/db.php';

$currentUser = getCurrentUser($pdo);

$action = $_POST['action'] ?? '';
$course_id = intval($_POST['course_id'] ?? 0);

if ($action === 'buy' && $course_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT id FROM purchases WHERE user_id = ? AND course_id = ?");
        $stmt->execute([$currentUser['id'], $course_id]);
        if ($stmt->fetch()) {
            header("Location: /pages/buy_course.php?error=already_bought");
            exit();
        }

        $stmt = $pdo->prepare("INSERT INTO purchases (user_id, course_id) VALUES (?, ?)");
        $stmt->execute([$currentUser['id'], $course_id]);

        header("Location: /pages/buy_course.php?success=1");
    } catch (PDOException $e) {
        error_log("Purchase error: " . $e->getMessage());
        header("Location: /pages/buy_course.php?error=fail");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $course_id = $_POST['course_id'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';
    $transaction_id = $_POST['transaction_id'] ?? '';

    header('Location: /pages/buy_course.php?course_id=' . $course_id . '&success=payment_created');
    exit();
} else {
    header('Location: /pages/courses.php');
    exit();
}

    