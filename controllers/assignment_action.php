<?php
include_once '../includes/auth.php';
protectPage();

include_once '../config/db.php';

$currentUser = getCurrentUser($pdo);

$action = $_POST['action'] ?? '';
$redirectUrl = $_SERVER['HTTP_REFERER'] ?? '/pages/assignments.php';

if (!in_array($action, ['create', 'edit', 'submit', 'delete'])) {
    header("Location: $redirectUrl?error=invalid_action");
    exit();
}

$isInstructor = in_array($currentUser['role'], ['admin', 'instructor']);

try {
    if ($action === 'create') {
        if (!$isInstructor) {
            header("Location: $redirectUrl?error=unauthorized");
            exit();
        }

        $course_id = intval($_POST['course_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $due_date = $_POST['due_date'] ?? null;
        $no_due_date = isset($_POST['no_due_date']);

        if ($course_id <= 0 || empty($title)) {
            header("Location: $redirectUrl?error=create_failed");
            exit();
        }

        $stmt = $pdo->prepare("SELECT instructor_id FROM courses WHERE id = ?");
        $stmt->execute([$course_id]);
        $course = $stmt->fetch();
        if (!$course || $course['instructor_id'] != $currentUser['id']) {
            header("Location: $redirectUrl?error=unauthorized");
            exit();
        }

        $stmt = $pdo->prepare("INSERT INTO assignments (course_id, title, description, due_date, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$course_id, $title, $description, $no_due_date ? null : $due_date]);

        header("Location: $redirectUrl?success=created");
        exit();

    } elseif ($action === 'edit') {
        if (!$isInstructor) {
            header("Location: $redirectUrl?error=unauthorized");
            exit();
        }

        $assignment_id = intval($_POST['assignment_id'] ?? 0);
        $course_id = intval($_POST['course_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $due_date = $_POST['due_date'] ?? null;
        $no_due_date = isset($_POST['no_due_date']);

        if ($assignment_id <= 0 || $course_id <= 0 || empty($title)) {
            header("Location: $redirectUrl?error=edit_failed");
            exit();
        }

        $stmt = $pdo->prepare("SELECT a.course_id FROM assignments a JOIN courses c ON a.course_id = c.id WHERE a.id = ? AND c.instructor_id = ?");
        $stmt->execute([$assignment_id, $currentUser['id']]);
        if (!$stmt->fetch()) {
            header("Location: $redirectUrl?error=unauthorized");
            exit();
        }

        $stmt = $pdo->prepare("UPDATE assignments SET course_id = ?, title = ?, description = ?, due_date = ? WHERE id = ?");
        $stmt->execute([$course_id, $title, $description, $no_due_date ? null : $due_date, $assignment_id]);

        header("Location: $redirectUrl?success=edited");
        exit();

    } elseif ($action === 'submit') {
        if ($isInstructor) {
            header("Location: $redirectUrl?error=unauthorized");
            exit();
        }

        $assignment_id = intval($_POST['assignment_id'] ?? 0);
        $submission_link = trim($_POST['submission_link'] ?? '');

        if ($assignment_id <= 0) {
            header("Location: $redirectUrl?error=upload_failed");
            exit();
        }

        $stmt = $pdo->prepare("SELECT course_id, due_date FROM assignments WHERE id = ?");
        $stmt->execute([$assignment_id]);
        $assignment = $stmt->fetch();
        if (!$assignment) {
            header("Location: $redirectUrl?error=upload_failed");
            exit();
        }

        if ($assignment['due_date'] && strtotime($assignment['due_date']) < time()) {
            header("Location: $redirectUrl?error=upload_failed");
            exit();
        }

        $stmt = $pdo->prepare("SELECT id FROM enrolled_courses WHERE course_id = ? AND user_id = ?");
        $stmt->execute([$assignment['course_id'], $currentUser['id']]);
        if (!$stmt->fetch()) {
            header("Location: $redirectUrl?error=unauthorized");
            exit();
        }

        $stmt = $pdo->prepare("SELECT id FROM submissions WHERE assignment_id = ? AND user_id = ?");
        $stmt->execute([$assignment_id, $currentUser['id']]);
        if ($stmt->fetch()) {
            header("Location: $redirectUrl?error=already_submitted");
            exit();
        }

        $file_path = null;
        if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['submission_file'];
            $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            $max_size = 5 * 1024 * 1024;

            $upload_dir = __DIR__ . '/../assets/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            if (!is_writable($upload_dir)) {
                error_log("Upload dir not writable");
                header("Location: $redirectUrl?error=upload_failed");
                exit();
            }

            if (!in_array($file['type'], $allowed_types) || $file['size'] > $max_size) {
                error_log("File validation failed");
                header("Location: $redirectUrl?error=upload_failed");
                exit();
            }

            $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $file_name = 'submission_' . $assignment_id . '_' . time() . '.' . $file_ext;
            $target_path = $upload_dir . $file_name;

            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                $file_path = '/assets/uploads/' . $file_name;
            } else {
                header("Location: $redirectUrl?error=upload_failed");
                exit();
            }
        } elseif (empty($submission_link)) {
            header("Location: $redirectUrl?error=upload_failed");
            exit();
        }

        $stmt = $pdo->prepare("INSERT INTO submissions (assignment_id, user_id, file_path, link, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
        $stmt->execute([$assignment_id, $currentUser['id'], $file_path, $submission_link ?: null]);

        header("Location: $redirectUrl?success=submitted");
        exit();

    } elseif ($action === 'delete') {
        if (!$isInstructor) {
            header("Location: $redirectUrl?error=unauthorized");
            exit();
        }

        $assignment_id = intval($_POST['assignment_id'] ?? 0);
        if ($assignment_id <= 0) {
            header("Location: $redirectUrl?error=delete_failed");
            exit();
        }

        $stmt = $pdo->prepare("SELECT c.instructor_id FROM assignments a JOIN courses c ON a.course_id = c.id WHERE a.id = ?");
        $stmt->execute([$assignment_id]);
        $course = $stmt->fetch();
        if (!$course || $course['instructor_id'] != $currentUser['id']) {
            header("Location: $redirectUrl?error=unauthorized");
            exit();
        }

        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM submissions WHERE assignment_id = ?")->execute([$assignment_id]);
        $pdo->prepare("DELETE FROM assignments WHERE id = ?")->execute([$assignment_id]);
        $pdo->commit();

        header("Location: $redirectUrl?success=deleted");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error in assignment_action: " . $e->getMessage());
    $failParam = match ($action) {
        'create' => 'create_failed',
        'edit' => 'edit_failed',
        'submit' => 'upload_failed',
        default => 'delete_failed'
    };
    header("Location: $redirectUrl?error=$failParam");
    exit();
}
?>