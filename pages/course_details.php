<?php
include_once __DIR__ . '/../includes/auth.php';
protectPage();
include_once '../config/db.php';
include_once '../models/Course.php';

$currentUser = getCurrentUser($pdo);
$courseModel = new Course($pdo);
$course = null;
$error = '';
$materials = [];
$assignments = [];

if (isset($_GET['course_id'])) {
    $courseId = intval($_GET['course_id']);
    try {
        $stmt = $pdo->prepare("
            SELECT c.id, c.title, c.description, c.price, c.created_at, c.image_path, u.username AS instructor_name
            FROM courses c
            JOIN users u ON c.instructor_id = u.id
            WHERE c.id = ?
        ");
        $stmt->execute([$courseId]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$course) {
            $error = "Course not found.";
        } else {
            $stmt = $pdo->prepare("SELECT file_path, link FROM course_materials WHERE course_id = ?");
            $stmt->execute([$courseId]);
            $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("SELECT * FROM assignments WHERE course_id = ? ORDER BY due_date ASC");
            $stmt->execute([$courseId]);
            $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Error fetching course details: " . $e->getMessage());
        $error = "Failed to load course details.";
    }
} else {
    $error = "No course ID provided.";
}

include_once '../includes/header.php';
?>

<div class="container mt-5">
    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
        <div class="alert alert-success">Assignment submitted successfully!</div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php elseif ($course): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h2><?php echo htmlspecialchars($course['title']); ?></h2>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if (!empty($course['image_path'])): ?>
                        <div class="col-md-5 mb-3">
                            <img src="<?php echo htmlspecialchars($course['image_path']); ?>" alt="Course Image" class="img-fluid rounded w-100" style="max-height: 280px; object-fit: cover;">
                        </div>
                    <?php endif; ?>

                    <div class="col-md-7">
                        <p><strong>Lesson:</strong> <?php echo htmlspecialchars($course['description']); ?></p>
                        <p><strong>Price:</strong> $<?php echo number_format($course['price'], 2); ?></p>
                        <p><strong>Instructor:</strong> <?php echo htmlspecialchars($course['instructor_name']); ?></p>
                        <p><strong>Created:</strong> <?php echo date('F j, Y', strtotime($course['created_at'])); ?></p>
                    </div>
                </div>

                <hr>
                <h4>Course Materials</h4>
                <?php if (!empty($materials)): ?>


                    <?php
$lessonCount = 1;
$linkCount = 1;
?>

<ul>
<?php foreach ($materials as $material): ?>
    <li>
        <?php if (!empty($material['file_path'])): ?>
            📄 <strong>Lesson <?php echo $lessonCount++; ?>:</strong>
            <a href="<?php echo htmlspecialchars($material['file_path']); ?>" target="_blank">Download file</a>
        <?php elseif (!empty($material['link'])): ?>
            🔗 <strong>Link <?php echo $linkCount++; ?>:</strong>
            <a href="<?php echo htmlspecialchars($material['link']); ?>" target="_blank">Open link (URL)</a>
        <?php endif; ?>
    </li>
<?php endforeach; ?>
</ul>




                <?php else: ?>
                    <p>No materials available for this course.</p>
                <?php endif; ?>

                <?php
                $isStudent = ($currentUser['role'] === 'student');
                $isEnrolled = $courseModel->isUserEnrolled($currentUser['id'], $course['id']);
                $hasPurchased = false;
                if ($isStudent && !$isEnrolled) {
                    $st = $pdo->prepare("SELECT 1 FROM purchases WHERE user_id = ? AND course_id = ?");
                    $st->execute([$currentUser['id'], $course['id']]);
                    $hasPurchased = (bool)$st->fetchColumn();
                }
                $canSubmit = $isStudent && ($isEnrolled || $hasPurchased);
                ?>

                <?php if (!empty($assignments)): ?>
                    <hr>
                    <h4>Assignments</h4>

                    <?php foreach ($assignments as $a): ?>
                        <div class="border rounded p-3 mb-3">
                            <h5 class="mb-1"><?php echo htmlspecialchars($a['title']); ?></h5>
                            <p class="mb-2"><?php echo nl2br(htmlspecialchars($a['description'] ?? '')); ?></p>
                            <p class="mb-2"><strong>Due:</strong> <?php echo date('F j, Y', strtotime($a['due_date'])); ?></p>

                            <?php
                            $st = $pdo->prepare("SELECT id, status, grade, file_path FROM submissions WHERE assignment_id = ? AND user_id = ? LIMIT 1");
                            $st->execute([$a['id'], $currentUser['id']]);
                            $mySub = $st->fetch(PDO::FETCH_ASSOC);
                            $isOverdue = (strtotime($a['due_date']) < time());
                            ?>

                            <?php if ($mySub): ?>
                                <div class="alert alert-info mb-0">
                                    You have submitted this assignment.
                                    <?php if (!empty($mySub['file_path'])): ?>
                                        <a href="<?php echo htmlspecialchars($mySub['file_path']); ?>" target="_blank">View file</a>
                                    <?php endif; ?>
                                    <?php if ($mySub['status'] === 'graded'): ?>
                                        – Grade: <strong><?php echo htmlspecialchars($mySub['grade']); ?></strong>
                                    <?php else: ?>
                                        – Status: <em>pending</em>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($canSubmit && !$isOverdue): ?>
                                <form action="/controllers/submit_assignment.php" method="POST" enctype="multipart/form-data" class="mt-2">
                                    <input type="hidden" name="assignment_id" value="<?php echo $a['id']; ?>">
                                    <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">

                                    <div class="mb-2">
                                        <label for="submission_file_<?php echo $a['id']; ?>" class="form-label">Upload PDF</label>
                                        <input type="file" id="submission_file_<?php echo $a['id']; ?>" name="assignment_file" class="form-control" accept="application/pdf" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm">Submit Assignment</button>
                                </form>
                            <?php else: ?>
                                <?php if (!$canSubmit): ?>
                                    <p class="text-muted mb-0"><em>You must enroll or purchase this course to submit.</em></p>
                                <?php elseif ($isOverdue): ?>
                                    <p class="text-danger mb-0"><em>Past due date — submissions are closed.</em></p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No assignments available for this course.</p>
                <?php endif; ?>
            </div>

            <div class="card-footer">
                <?php if ($currentUser && $courseModel->isUserEnrolled($currentUser['id'], $course['id'])): ?>
                    <a href="/pages/course.php?course_id=<?php echo $course['id']; ?>" class="btn btn-success">Go to Course</a>
                <?php endif; ?>
                <a href="/pages/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php include_once '../includes/footer.php'; ?>















