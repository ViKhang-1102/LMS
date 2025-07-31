<?php
include_once '../includes/auth.php';
protectPage();

include_once '../config/db.php';
$currentUser = getCurrentUser($pdo);
$action = $_POST['action'] ?? '';
$redirectUrl = '/pages/messages.php';

switch ($action) {
    case 'send':
        $recipient_id = intval($_POST['recipient_id'] ?? 0);
        $content = trim($_POST['message'] ?? '');
        $imagePath = null;

        if ($recipient_id && ($content || ($_FILES['image']['size'] ?? 0) > 0)) {
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

                if (in_array($ext, $allowedExtensions)) {
                    $uploadDir = '../assets/uploads/messages/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $filename = uniqid('msg_') . '.' . $ext;
                    $fullPath = $uploadDir . $filename;

                    if (move_uploaded_file($_FILES['image']['tmp_name'], $fullPath)) {
                        $imagePath = 'assets/uploads/messages/' . $filename;
                    }
                }
            }

            $stmt = $pdo->prepare("
                INSERT INTO messages (sender_id, recipient_id, content, image_path, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $currentUser['id'],
                $recipient_id,
                $content,
                $imagePath
            ]);
        }
        break;

    case 'delete':
        $message_id = intval($_POST['message_id'] ?? 0);

        $stmt = $pdo->prepare("SELECT sender_id, created_at, image_path FROM messages WHERE id = ?");
        $stmt->execute([$message_id]);
        $message = $stmt->fetch();

        if ($message && $message['sender_id'] == $currentUser['id']) {
            $timeDiff = time() - strtotime($message['created_at']);
            if ($timeDiff <= 3600) {
                if (!empty($message['image_path'])) {
                    $fileToDelete = '../' . $message['image_path'];
                    if (file_exists($fileToDelete)) {
                        unlink($fileToDelete);
                    }
                }

                $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
                $stmt->execute([$message_id]);
            }
        }
        break;

    default:
        break;
}

header("Location: $redirectUrl");
exit;
