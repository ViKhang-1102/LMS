<?php
/*
include_once '../includes/auth.php';
protectPage();
include_once '../config/db.php';

$currentUser = getCurrentUser($pdo);
$isAdmin = $currentUser['role'] === 'admin';
$course_id = $_POST['course_id'] ?? 0;
$redirectUrl = "/pages/forum.php?course_id=$course_id";

if (!isset($_POST['action']) || !in_array($_POST['action'], ['send_message', 'delete_message', 'reset_messages'])) {
    header("Location: $redirectUrl?error=invalid_action");
    exit();
}

try {
    if ($_POST['action'] === 'send_message') {
        $content = trim($_POST['content'] ?? '');
        if (!$content) {
            header("Location: $redirectUrl?error=Message content is required.");
            exit();
        }
        $stmt = $pdo->prepare("INSERT INTO forum_messages (course_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$course_id, $currentUser['id'], $content]);
        header("Location: $redirectUrl?success=message_sent");
        exit();
    } elseif ($_POST['action'] === 'delete_message') {
        $message_id = $_POST['message_id'] ?? 0;
        $stmt = $pdo->prepare("SELECT user_id, created_at FROM forum_messages WHERE id = ? AND course_id = ?");
        $stmt->execute([$message_id, $course_id]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($message) {
            $oneHourAgo = date('Y-m-d H:i:s', strtotime('-1 hour'));
            if ($isAdmin || ($message['user_id'] == $currentUser['id'] && $message['created_at'] >= $oneHourAgo)) {
                $stmt = $pdo->prepare("UPDATE forum_messages SET deleted_at = NOW() WHERE id = ?");
                $stmt->execute([$message_id]);
                header("Location: $redirectUrl?success=message_deleted");
                exit();
            } else {
                header("Location: $redirectUrl?error=You can only delete your own messages within 1 hour, or you lack permission.");
                exit();
            }
        } else {
            header("Location: $redirectUrl?error=Message not found.");
            exit();
        }
    } elseif ($_POST['action'] === 'reset_messages' && $isAdmin) {
        $stmt = $pdo->prepare("UPDATE forum_messages SET deleted_at = NOW() WHERE course_id = ?");
        $stmt->execute([$course_id]);
        header("Location: $redirectUrl?success=messages_reset");
        exit();
    } else {
        header("Location: $redirectUrl?error=Unauthorized action.");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error in forum_action: " . $e->getMessage());
    header("Location: $redirectUrl?error=An error occurred. Please try again.");
    exit();
}
?>
*/