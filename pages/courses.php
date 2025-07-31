<?php 
include_once '../includes/auth.php';

protectPage();
include_once '../config/db.php';

$currentUser = getCurrentUser($pdo);
$isInstructor = $currentUser['role'] === 'instructor' || $currentUser['role'] === 'admin';

try {
    if ($isInstructor) {
        $stmt = $pdo->prepare("SELECT id, title, description, price, image_path FROM courses WHERE instructor_id = ?");
        $stmt->execute([$currentUser['id']]);
    } else {
        $stmt = $pdo->prepare("SELECT c.id, c.title, c.description, c.price, c.image_path, ec.progress 
                               FROM courses c 
                               LEFT JOIN enrolled_courses ec ON c.id = ec.course_id AND ec.user_id = ?");
        $stmt->execute([$currentUser['id']]);
    }
    $courses = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching courses: " . $e->getMessage());
    $courses = [];
}

$editCourse = null;
if (isset($_GET['edit_id']) && $isInstructor) {
    try {
        $stmt = $pdo->prepare("SELECT id, title, description, price FROM courses WHERE id = ? AND instructor_id = ?");
        $stmt->execute([$_GET['edit_id'], $currentUser['id']]);
        $editCourse = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching course for edit: " . $e->getMessage());
    }
}

include_once '../includes/header.php';
?>

<div class="container mt-5">
    <h1 class="text-center mb-4">Course Management</h1>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger" role="alert">
            <?php
            switch ($_GET['error']) {
                case 'create_failed':
                    echo 'Failed to create course!';
                    break;
                case 'update_failed':
                    echo 'Failed to update course!';
                    break;
                case 'delete_failed':
                    echo 'Failed to delete course!';
                    break;
                case 'upload_failed':
                    echo 'Failed to upload material!';
                    break;
                default:
                    echo 'An error occurred, please try again!';
            }
            ?>
        </div>
    <?php elseif (isset($_GET['success'])): ?>
        <div class="alert alert-success" role="alert">
            <?php
            switch ($_GET['success']) {
                case 'created':
                    echo 'Course created successfully!';
                    break;
                case 'updated':
                    echo 'Course updated successfully!';
                    break;
                case 'deleted':
                    echo 'Course deleted successfully!';
                    break;
                case 'uploaded':
                    echo 'Material uploaded successfully!';
                    break;
            }
            ?>
        </div>
    <?php endif; ?>

    <?php if ($isInstructor): ?>
        <div class="card mb-4 shadow-sm">
            <div class="card-header">
                <h3><i class="fas fa-plus-circle"></i> <?php echo $editCourse ? 'Edit Course' : 'Create New Course'; ?></h3>
            </div>
            <div class="card-body">
                <form action="/controllers/course_action.php" method="POST" enctype="multipart/form-data">
                    <?php if ($editCourse): ?>
                        <input type="hidden" name="course_id" value="<?php echo $editCourse['id']; ?>">
                        <input type="hidden" name="action" value="update">
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
                        <label for="price" class="form-label">Price (0 if free)</label>
                        <input type="number" class="form-control" id="price" name="price" required min="0" value="<?php echo $editCourse ? htmlspecialchars($editCourse['price']) : '0'; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="course_image" class="form-label">Course Image (optional)</label>
                        <input type="file" class="form-control" id="course_image" name="course_image" accept="image/*">
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $editCourse ? 'Update' : 'Create'; ?></button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Learning Material Upload Card -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header">
            <h3><i class="fas fa-file-upload"></i> Add Learning Material</h3>
        </div>
        <div class="card-body">
            <form action="/controllers/course_action.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_material">
                <div class="mb-3">
                    <label for="course_id" class="form-label">Select Course</label>
                    <select class="form-control" id="course_id" name="course_id" required>
                        <option value="">Select a course</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="material_files" class="form-label">Material Files (PDFs)</label>
                    <input type="file" id="material_files" name="material_files[]" class="form-control" accept=".pdf" multiple>
                </div>
                <div class="mb-3">
                    <label class="form-label">External Links</label>
                    <div id="link-container">
                        <input type="text" name="material_links[]" class="form-control mb-2" placeholder="Enter document URL">
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addLinkField()">
                        <i class="fas fa-plus"></i> Add another link
                    </button>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-header">
            <h3><i class="fas fa-book"></i> Course List</h3>
        </div>
        <div class="card-body">
            <?php if (empty($courses)): ?>
                <p>No courses available.</p>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($courses as $course): ?>
                        <?php
                        $stmt = $pdo->prepare("SELECT id, file_path, link, uploaded_at FROM course_materials WHERE course_id = ?");
                        $stmt->execute([$course['id']]);
                        $materials = $stmt->fetchAll();
                        ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>

                                    <?php if (!empty($course['image_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($course['image_path']); ?>" class="img-fluid mb-3 rounded" style="max-height: 200px;" alt="Course Image">
                                    <?php endif; ?>

                                    <div id="desc-<?php echo $course['id']; ?>" class="card-text card-description">
                                        <?php echo htmlspecialchars($course['description']); ?>
                                    </div>

                                    <p class="card-text"><strong>Price:</strong> <?php echo $course['price'] == 0 ? 'Free' : number_format($course['price']) . '$'; ?></p>

                                    <?php if (!$isInstructor && isset($course['progress'])): ?>
                                        <div class="progress mb-2">
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo $course['progress']; ?>%;" aria-valuenow="<?php echo $course['progress']; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $course['progress']; ?>%</div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($materials)): ?>
                                        <h6 class="mt-3">Materials:</h6>
                                        <ul id="materials-<?php echo $course['id']; ?>" class="card-materials">
                                            <?php 
                                            $lessonCount = 1;
                                            $linkCount = 1;
                                            foreach ($materials as $material): ?>
                                                <?php if (!empty($material['file_path'])): ?>
                                                    <li>
                                                        📄 <strong>Lesson <?php echo $lessonCount++; ?>:</strong>
                                                        <a href="<?php echo htmlspecialchars($material['file_path']); ?>" target="_blank">Download</a>
                                                        <small>(<?php echo date('Y-m-d H:i', strtotime($material['uploaded_at'])); ?>)</small>
                                                    </li>
                                                <?php elseif (!empty($material['link'])): ?>
                                                    <li>
                                                        🔗 <strong>Link <?php echo $linkCount++; ?>:</strong>
                                                        <a href="<?php echo htmlspecialchars($material['link']); ?>" target="_blank">Visit</a>
                                                        <small>(<?php echo date('Y-m-d H:i', strtotime($material['uploaded_at'])); ?>)</small>
                                                    </li>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>

                                    <div class="show-toggle" id="toggle-btn-<?php echo $course['id']; ?>" onclick="toggleExpand('<?php echo $course['id']; ?>')">
                                        Show more
                                    </div>

                                    <?php if ($isInstructor): ?>
                                        <div class="btn-group mt-2">
                                            <a href="/pages/courses.php?edit_id=<?php echo $course['id']; ?>" class="btn btn-warning"><i class="fas fa-edit"></i> Edit</a>
                                            <form action="/controllers/course_action.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this course?');">
                                                <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Delete</button>
                                            </form>
                                        </div>
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

<style>
.card-description, .card-materials {
    max-height: 100px;
    overflow: hidden;
    transition: max-height 0.3s ease;
}
.card-description.expanded, .card-materials.expanded {
    max-height: 1000px;
}
.show-toggle {
    color: #007bff;
    cursor: pointer;
    font-size: 0.9em;
    margin-top: 5px;
    display: inline-block;
}
</style>

<script>
function addLinkField() {
    const container = document.getElementById('link-container');
    const input = document.createElement('input');
    input.type = 'text';
    input.name = 'material_links[]';
    input.placeholder = 'Enter document URL';
    input.className = 'form-control mb-2';
    container.appendChild(input);
}

function toggleExpand(id) {
    const desc = document.getElementById('desc-' + id);
    const materials = document.getElementById('materials-' + id);
    const btn = document.getElementById('toggle-btn-' + id);

    const expanded = desc.classList.toggle('expanded');
    if (materials) materials.classList.toggle('expanded');

    btn.textContent = expanded ? 'Show less' : 'Show more';
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include_once '../includes/footer.php'; ?>


