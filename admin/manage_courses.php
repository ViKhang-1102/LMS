<?php
include_once '../includes/auth.php';
protectPage(['admin']); 

include_once '../config/db.php';

$currentUser = getCurrentUser($pdo);

try {
    $stmt = $pdo->prepare("SELECT c.id, c.title, c.description, c.price, c.created_at, u.username AS instructor 
                           FROM courses c 
                           JOIN users u ON c.instructor_id = u.id 
                           ORDER BY c.created_at DESC");
    $stmt->execute();
    $courses = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching courses: " . $e->getMessage());
    $courses = [];
}

$editCourse = null;
if (isset($_GET['edit_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT id, title, description, price, instructor_id FROM courses WHERE id = ?");
        $stmt->execute([$_GET['edit_id']]);
        $editCourse = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching course for edit: " . $e->getMessage());
    }
}

try {
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE role = 'admin'");
    $stmt->execute();
    $instructors = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching instructors: " . $e->getMessage());
    $instructors = [];
}

include_once '../includes/header.php';
?>

<h2 class="mb-4">Manage Courses</h2>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger" role="alert">
        <?php
        if ($_GET['error'] == 'create_failed') {
            echo 'Failed to create course!';
        } elseif ($_GET['error'] == 'update_failed') {
            echo 'Failed to update course!';
        } elseif ($_GET['error'] == 'delete_failed') {
            echo 'Failed to delete course!';
        } else {
            echo 'An error occurred. Please try again!';
        }
        ?>
    </div>
<?php elseif (isset($_GET['success'])): ?>
    <div class="alert alert-success" role="alert">
        <?php
        if ($_GET['success'] == 'created') {
            echo 'Course created successfully!';
        } elseif ($_GET['success'] == 'updated') {
            echo 'Course updated successfully!';
        } elseif ($_GET['success'] == 'deleted') {
            echo 'Course deleted successfully!';
        }
        ?>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <h3><i class="fas fa-plus-circle"></i> <?php echo $editCourse ? 'Edit Course' : 'Create New Course'; ?></h3>
    </div>
    <div class="card-body">
        <form action="/controllers/course_action.php" method="POST">
            <?php if ($editCourse): ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="course_id" value="<?php echo $editCourse['id']; ?>">
            <?php else: ?>
                <input type="hidden" name="action" value="create">
            <?php endif; ?>
            <div class="mb-3">
                <label for="title" class="form-label">Course Title</label>
                <input type="text" class="form-control" id="title" name="title" required value="<?php echo $editCourse ? htmlspecialchars($editCourse['title']) : ''; ?>">
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4"><?php echo $editCourse ? htmlspecialchars($editCourse['description']) : ''; ?></textarea>
            </div>
            <div class="mb-3">
                <label for="price" class="form-label">Price (0 for free)</label>
                <input type="number" class="form-control" id="price" name="price" required min="0" value="<?php echo $editCourse ? htmlspecialchars($editCourse['price']) : '0'; ?>">
            </div>
            <div class="mb-3">
                <label for="instructor_id" class="form-label">Instructor</label>
                <select class="form-control" id="instructor_id" name="instructor_id" required>
                    <option value="">Select Instructor</option>
                    <?php foreach ($instructors as $instructor): ?>
                        <option value="<?php echo $instructor['id']; ?>" <?php echo $editCourse && $editCourse['instructor_id'] == $instructor['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($instructor['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $editCourse ? 'Update' : 'Create'; ?></button>
            </div>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h3><i class="fas fa-book"></i> Course List</h3>
    </div>
    <div class="card-body">
        <?php if (empty($courses)): ?>
            <p>No courses found.</p>
        <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Instructor</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($courses as $course): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($course['title']); ?></td>
                            <td><?php echo htmlspecialchars($course['description'] ?: 'No description'); ?></td>
                            <td><?php echo $course['price'] == 0 ? 'Free' : number_format($course['price']) . ' VND'; ?></td>
                            <td><?php echo htmlspecialchars($course['instructor']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($course['created_at'])); ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="/admin/manage_courses.php?edit_id=<?php echo $course['id']; ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Edit</a>
                                    <form action="/controllers/course_action.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this course?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php
include_once '../includes/footer.php';
?>