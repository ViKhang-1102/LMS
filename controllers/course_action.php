<?php
include_once '../includes/auth.php';
protectPage();
include_once '../config/db.php';

$currentUser = getCurrentUser($pdo);
$isInstructor = $currentUser['role'] === 'instructor' || $currentUser['role'] === 'admin';

function handleCourseImageUpload($course_id) {
    if (!isset($_FILES['course_image']) || $_FILES['course_image']['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $image = $_FILES['course_image'];

    if (!in_array($image['type'], $allowedTypes) || $image['size'] > 2 * 1024 * 1024) {
        return null;
    }

    $uploadDir = __DIR__ . '/../assets/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext = pathinfo($image['name'], PATHINFO_EXTENSION);
    $filename = 'course_' . $course_id . '_' . time() . '.' . $ext;
    $destination = $uploadDir . $filename;

    if (move_uploaded_file($image['tmp_name'], $destination)) {
        return '/assets/uploads/' . $filename;
    }

    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' && $isInstructor) {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);

        if (empty($title)) {
            header('Location: /pages/courses.php?error=create_failed');
            exit();
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO courses (title, description, price, instructor_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $description, $price, $currentUser['id']]);
            $course_id = $pdo->lastInsertId();

            $image_path = handleCourseImageUpload($course_id);
            if ($image_path) {
                $updateStmt = $pdo->prepare("UPDATE courses SET image_path = ? WHERE id = ?");
                $updateStmt->execute([$image_path, $course_id]);
            }

            header('Location: /pages/courses.php?success=created');
            exit();
        } catch (PDOException $e) {
            error_log("Error creating course: " . $e->getMessage());
            header('Location: /pages/courses.php?error=create_failed');
            exit();
        }
    }

    if ($action === 'update' && $isInstructor) {
        $course_id = intval($_POST['course_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);

        if (empty($title) || $course_id <= 0) {
            header('Location: /pages/courses.php?error=update_failed');
            exit();
        }

        try {
            $stmt = $pdo->prepare("UPDATE courses SET title = ?, description = ?, price = ? WHERE id = ? AND instructor_id = ?");
            $stmt->execute([$title, $description, $price, $course_id, $currentUser['id']]);

            $image_path = handleCourseImageUpload($course_id);
            if ($image_path) {
                $updateStmt = $pdo->prepare("UPDATE courses SET image_path = ? WHERE id = ?");
                $updateStmt->execute([$image_path, $course_id]);
            }

            header('Location: /pages/courses.php?success=updated');
            exit();
        } catch (PDOException $e) {
            error_log("Error updating course: " . $e->getMessage());
            header('Location: /pages/courses.php?error=update_failed');
            exit();
        }
    }

    if ($action === 'delete' && $isInstructor) {
        $course_id = intval($_POST['course_id'] ?? 0);

        if ($course_id <= 0) {
            header('Location: /pages/courses.php?error=delete_failed');
            exit();
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ? AND instructor_id = ?");
            $stmt->execute([$course_id, $currentUser['id']]);
            header('Location: /pages/courses.php?success=deleted');
            exit();
        } catch (PDOException $e) {
            error_log("Error deleting course: " . $e->getMessage());
            header('Location: /pages/courses.php?error=delete_failed');
            exit();
        }
    }

    if ($action === 'upload_material' && $isInstructor) {
        $course_id = intval($_POST['course_id'] ?? 0);
        $uploadedAt = date('Y-m-d H:i:s');

        if ($course_id <= 0) {
            header('Location: /pages/courses.php?error=upload_failed');
            exit();
        }

        $stmt = $pdo->prepare("SELECT id FROM courses WHERE id = ? AND instructor_id = ?");
        $stmt->execute([$course_id, $currentUser['id']]);
        if (!$stmt->fetch()) {
            header('Location: /pages/courses.php?error=upload_failed');
            exit();
        }

        try {
            if (!empty($_FILES['material_files']['name'][0])) {
                $uploadDir = __DIR__ . '/../assets/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                foreach ($_FILES['material_files']['tmp_name'] as $index => $tmpName) {
                    if ($_FILES['material_files']['error'][$index] === UPLOAD_ERR_OK) {
                        $fileType = $_FILES['material_files']['type'][$index];
                        $fileSize = $_FILES['material_files']['size'][$index];

                        if ($fileType === 'application/pdf' && $fileSize <= 5 * 1024 * 1024) {
                            $fileExt = pathinfo($_FILES['material_files']['name'][$index], PATHINFO_EXTENSION);
                            $fileName = 'material_' . $course_id . '_' . time() . '_' . $index . '.' . $fileExt;
                            $targetPath = $uploadDir . $fileName;
                            $webPath = '/assets/uploads/' . $fileName;

                            if (move_uploaded_file($tmpName, $targetPath)) {
                                $stmt = $pdo->prepare("INSERT INTO course_materials (course_id, file_path, uploaded_at) VALUES (?, ?, ?)");
                                $stmt->execute([$course_id, $webPath, $uploadedAt]);
                            }
                        }
                    }
                }
            }

            if (!empty($_POST['material_links'])) {
                foreach ($_POST['material_links'] as $link) {
                    $link = trim($link);
                    if (filter_var($link, FILTER_VALIDATE_URL)) {
                        $stmt = $pdo->prepare("INSERT INTO course_materials (course_id, link, uploaded_at) VALUES (?, ?, ?)");
                        $stmt->execute([$course_id, $link, $uploadedAt]);
                    }
                }
            }

            header('Location: /pages/courses.php?success=uploaded');
            exit();
        } catch (PDOException $e) {
            error_log("Error uploading material: " . $e->getMessage());
            header('Location: /pages/courses.php?error=upload_failed');
            exit();
        }
    }
}

header('Location: /pages/courses.php');



