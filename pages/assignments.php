<?php
include_once '../includes/auth.php';
protectPage();

include_once '../config/db.php';

$currentUser = getCurrentUser($pdo);
$isInstructor = $currentUser['role'] === 'instructor' || $currentUser['role'] === 'admin';

try {
    if ($isInstructor) {
        $stmt = $pdo->prepare("SELECT id, title FROM courses WHERE instructor_id = ?");
        $stmt->execute([$currentUser['id']]);
    } else {
        $stmt = $pdo->prepare("SELECT c.id, c.title FROM courses c JOIN enrolled_courses ec ON c.id = ec.course_id WHERE ec.user_id = ?");
        $stmt->execute([$currentUser['id']]);
    }
    $courses = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching courses: " . $e->getMessage());
    $courses = [];
}

$editAssignment = null;
if (isset($_GET['edit_id']) && $isInstructor) {
    try {
        $stmt = $pdo->prepare("SELECT a.id, a.title, a.description, a.due_date, a.course_id FROM assignments a JOIN courses c ON a.course_id = c.id WHERE a.id = ? AND c.instructor_id = ?");
        $stmt->execute([$_GET['edit_id'], $currentUser['id']]);
        $editAssignment = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching assignment for edit: " . $e->getMessage());
    }
}

$course_id = $_GET['course_id'] ?? '';
try {
    $query = "SELECT a.id, a.title, a.description, a.due_date, a.course_id, c.title AS course_title, s.id AS submission_id, s.status 
              FROM assignments a 
              JOIN courses c ON a.course_id = c.id 
              LEFT JOIN submissions s ON a.id = s.assignment_id AND s.user_id = ?
              WHERE 1=1";
    $params = [$currentUser['id']];
    if ($course_id) {
        $query .= " AND a.course_id = ?";
        $params[] = $course_id;
    } elseif ($isInstructor) {
        $query .= " AND c.instructor_id = ?";
        $params[] = $currentUser['id'];
    }
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $assignments = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching assignments: " . $e->getMessage());
    $assignments = [];
}

include_once '../includes/header.php';
?>

<div class="container mt-5">
    <h1 class="text-center mb-4">Assignment Management</h1>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger" role="alert">
            <?php
            switch ($_GET['error']) {
                case 'create_failed': echo 'Failed to create assignment!'; break;
                case 'edit_failed': echo 'Failed to update assignment!'; break;
                case 'delete_failed': echo 'Failed to delete assignment!'; break;
                case 'upload_failed': echo 'Failed to submit assignment!'; break;
                default: echo 'An error occurred, please try again!';
            }
            ?>
        </div>
    <?php elseif (isset($_GET['success'])): ?>
        <div class="alert alert-success" role="alert">
            <?php
            switch ($_GET['success']) {
                case 'created': echo 'Assignment created successfully!'; break;
                case 'edited': echo 'Assignment updated successfully!'; break;
                case 'deleted': echo 'Assignment deleted successfully!'; break;
                case 'submitted': echo 'Assignment submitted successfully!'; break;
            }
            ?>
        </div>
    <?php endif; ?>

    <?php if ($isInstructor): ?>
        <div class="card mb-4 shadow-sm">
            <div class="card-header">
                <h3><i class="fas fa-plus-circle"></i> <?php echo $editAssignment ? 'Edit Assignment' : 'Create New Assignment'; ?></h3>
            </div>
            <div class="card-body">
                <form action="/controllers/assignment_action.php" method="POST">
                    <?php if ($editAssignment): ?>
                        <input type="hidden" name="assignment_id" value="<?php echo $editAssignment['id']; ?>">
                        <input type="hidden" name="action" value="edit">
                    <?php else: ?>
                        <input type="hidden" name="action" value="create">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="course_id" class="form-label">Select Course</label>
                        <select class="form-control" id="course_id" name="course_id" required>
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>" <?php echo $editAssignment && $course['id'] == $editAssignment['course_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="title" class="form-label">Assignment Title</label>
                        <input type="text" class="form-control" id="title" name="title" required value="<?php echo $editAssignment ? htmlspecialchars($editAssignment['title']) : ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4"><?php echo $editAssignment ? htmlspecialchars($editAssignment['description']) : ''; ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="due_date" class="form-label">Due Date</label>
                        <input type="datetime-local" class="form-control" id="due_date" name="due_date"
                               <?php echo ($editAssignment && !$editAssignment['due_date']) ? 'disabled' : 'required'; ?>
                               value="<?php echo ($editAssignment && $editAssignment['due_date']) ? date('Y-m-d\TH:i', strtotime($editAssignment['due_date'])) : ''; ?>">
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" value="1" id="no_due_date" name="no_due_date" <?php echo ($editAssignment && !$editAssignment['due_date']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="no_due_date">
                                No Due Date
                            </label>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $editAssignment ? 'Update' : 'Create'; ?></button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            document.getElementById('no_due_date').addEventListener('change', function () {
                const dueDate = document.getElementById('due_date');
                if (this.checked) {
                    dueDate.disabled = true;
                    dueDate.removeAttribute('required');
                    dueDate.value = '';
                } else {
                    dueDate.disabled = false;
                    dueDate.setAttribute('required', 'required');
                }
            });
        </script>
    <?php endif; ?>

<div class="card mb-4 shadow-sm">
        <div class="card-header">
            <h3><i class="fas fa-book"></i> Assignment List</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="/pages/assignments.php" class="mb-4">
                <div class="row">
                    <div class="col-md-6">
                        <label for="course_filter" class="form-label">Filter by Course</label>
                        <select class="form-control" id="course_filter" name="course_id" onchange="this.form.submit()">
                            <option value="">All Courses</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>" <?php echo $course_id == $course['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>

            <?php if (empty($assignments)): ?>
                <p>No assignments available.</p>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($assignments as $assignment): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($assignment['title']); ?></h5>
                                    <p class="card-text"><strong>Course:</strong> <?php echo htmlspecialchars($assignment['course_title']); ?></p>
                                    <p class="card-text"><strong>Description:</strong> <?php echo htmlspecialchars($assignment['description'] ?: 'No description'); ?></p>
                                    <p class="card-text"><strong>Due Date:</strong> <?php echo date('d/m/Y H:i', strtotime($assignment['due_date'])); ?></p>
                                    <p class="card-text"><strong>Status:</strong> 
                                        <?php
                                        if ($isInstructor) {
                                            echo 'N/A';
                                        } else {
                                            echo $assignment['submission_id'] ? ($assignment['status'] === 'graded' ? 'Graded' : 'Submitted') : 'Not submitted';
                                        }
                                        ?>
                                    </p>
                                    <?php if ($isInstructor): ?>
                                        <div class="btn-group mt-2">
                                            <a href="/pages/assignments.php?edit_id=<?php echo $assignment['id']; ?>" class="btn btn-warning"><i class="fas fa-edit"></i> Edit</a>
                                            <form action="/controllers/assignment_action.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this assignment?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                                <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Delete</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!$isInstructor && !$assignment['submission_id'] && strtotime($assignment['due_date']) >= time()): ?>
                                        <form action="/controllers/assignment_action.php" method="POST" enctype="multipart/form-data" class="mt-3">
                                            <input type="hidden" name="action" value="submit">
                                            <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                            <div class="mb-3">
                                                <label for="submission_file_<?php echo $assignment['id']; ?>" class="form-label">Upload Assignment</label>
                                                <input type="file" class="form-control" id="submission_file_<?php echo $assignment['id']; ?>" name="submission_file" accept=".pdf,.doc,.docx">
                                            </div>
                                            <div class="mb-3">
                                                <label for="submission_link_<?php echo $assignment['id']; ?>" class="form-label">Or enter submission link</label>
                                                <input type="url" class="form-control" id="submission_link_<?php echo $assignment['id']; ?>" name="submission_link" placeholder="Enter URL">
                                            </div>
                                            <div class="d-grid">
                                                <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Submit Assignment</button>
                                            </div>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>


<script src="/assets/js/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php
include_once '../includes/footer.php';
?>