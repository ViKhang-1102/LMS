<?php
include_once '../includes/auth.php';
protectPage(['admin']);

include_once '../config/db.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['course_id'])) {
        $courseId = intval($_POST['course_id']);
        try {
            $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
            $stmt->execute([$courseId]);
            if ($stmt->rowCount() === 0) {
                header('Location: manage_courses.php?error=delete_failed_no_course');
                exit;
            }
            header('Location: manage_courses.php?success=deleted');
            exit;
        } catch (PDOException $e) {
            error_log("Error deleting course: " . $e->getMessage());
            header('Location: manage_courses.php?error=delete_failed&message=' . urlencode($e->getMessage()));
            exit;
        }
    }

    $stmt = $pdo->prepare("
        SELECT c.id, c.title, c.price, c.created_at, c.image_path,
               u.username AS instructor_name
        FROM courses c
        LEFT JOIN users u ON c.instructor_id = u.id
        ORDER BY c.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recentCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching recent courses: " . $e->getMessage());
    $recentCourses = [];
}

include_once '../includes/header.php';
?>

<h2 class="mb-4">Manage Courses</h2>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger" role="alert">
        <?php
        if ($_GET['error'] == 'delete_failed') {
            echo 'Failed to delete course: ' . (isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'Unknown error');
        } elseif ($_GET['error'] == 'delete_failed_no_course') {
            echo 'Cannot delete course: Course does not exist.';
        } else {
            echo 'An error occurred, please try again!';
        }
        ?>
    </div>
<?php elseif (isset($_GET['success'])): ?>
    <div class="alert alert-success" role="alert">
        <?php
        if ($_GET['success'] == 'deleted') {
            echo 'Course has been deleted!';
        }
        ?>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <h3><i class="fas fa-book"></i> Recent Courses</h3>
    </div>
    <div class="card-body">
        <?php if (empty($recentCourses)): ?>
            <p>No courses found.</p>
        <?php else: ?>
            <table class="table table-striped align-middle text-center">
                <thead>
                    <tr>
                        <th style="width: 25%; text-align: left;">Title</th>
                        <th style="width: 10%;">Price</th>
                        <th style="width: 15%;">Creator</th>
                        <th style="width: 15%;">Image</th>
                        <th style="width: 20%;">Created At</th>
                        <th style="width: 15%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentCourses as $course): ?>
                        <tr>
                            <td style="text-align: left;"><?php echo htmlspecialchars($course['title']); ?></td>
                            <td><?php echo '$' . number_format($course['price'], 2); ?></td>
                            <td><?php echo htmlspecialchars($course['instructor_name'] ?? 'Unknown Instructor'); ?></td>
                            <td>
                                <img src="<?php echo htmlspecialchars($course['image_path'] ?? '/assets/images/default_image.jpg'); ?>" 
                                     alt="Course Image" class="rounded" style="width: 60px; height: 60px; object-fit: cover;">
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($course['created_at'])); ?></td>
                            <td>
                                <form action="manage_courses.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete this course?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php include_once '../includes/footer.php'; ?>