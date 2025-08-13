<?php
include_once '../includes/auth.php';
protectPage();

include_once '../config/db.php';

$currentUser = getCurrentUser($pdo);
$isAdmin = $currentUser['role'] === 'admin';
$isInstructor = $currentUser['role'] === 'instructor';

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

$filters = [
    'user_id' => $_GET['user_id'] ?? '',
    'course_id' => $_GET['course_id'] ?? '',
    'status' => $_GET['status'] ?? ''
];

$courses = [];
$users = [];
$submissions = [];

try {
    if ($isAdmin) {
        $stmt = $pdo->query("SELECT id, title FROM courses ORDER BY title ASC");
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->query("SELECT id, username FROM users WHERE role = 'student' ORDER BY username ASC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($isInstructor) {
        $stmt = $pdo->prepare("SELECT id, title FROM courses WHERE instructor_id = ? ORDER BY title ASC");
        $stmt->execute([$currentUser['id']]);
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare("SELECT c.id, c.title 
                               FROM courses c 
                               JOIN purchases p ON c.id = p.course_id 
                               WHERE p.user_id = ? 
                               ORDER BY c.title ASC");
        $stmt->execute([$currentUser['id']]);
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $query = "SELECT s.id AS submission_id, s.assignment_id, s.user_id, s.content, s.file_path, s.status, s.grade, 
                     s.submitted_at, u.username, c.title AS course_title, a.title AS assignment_title,
                     g.feedback, g.graded_at, c.id AS course_id
              FROM submissions s
              JOIN users u ON s.user_id = u.id
              JOIN assignments a ON s.assignment_id = a.id
              JOIN courses c ON a.course_id = c.id
              LEFT JOIN grades g ON s.id = g.submission_id
              WHERE 1=1";
    $params = [];
    
    if ($isInstructor) {
        $query .= " AND c.instructor_id = ?";
        $params[] = $currentUser['id'];
    } elseif (!$isAdmin) {
        $query .= " AND s.user_id = ?";
        $params[] = $currentUser['id'];
    }
    
    if ($filters['user_id'] && $isAdmin) {
        $query .= " AND s.user_id = ?";
        $params[] = $filters['user_id'];
    }
    if ($filters['course_id']) {
        $query .= " AND a.course_id = ?";
        $params[] = $filters['course_id'];
    }
    if ($filters['status'] === 'graded') {
        $query .= " AND s.status = 'graded'";
    } elseif ($filters['status'] === 'pending') {
        $query .= " AND s.status = 'pending'";
    }
    
    $query .= " ORDER BY s.submitted_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching data: " . $e->getMessage());
    $error = "Failed to load data.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grades</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include_once '../includes/header.php'; ?>
    <div class="container mt-5">
        <h1 class="mb-4">Grades Management</h1>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success === 'graded' ? 'Submission graded successfully!' : 'Action completed successfully!'; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo $error === 'grade_failed' ? 'Failed to grade submission!' : 'An error occurred.'; ?>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">
                <h3><i class="fas fa-filter"></i> Filter Submissions</h3>
            </div>
            <div class="card-body">
                <form method="GET" action="/pages/grades.php">
                    <div class="row">
                        <?php if ($isAdmin): ?>
                            <div class="col-md-4 mb-3">
                                <label for="user_id" class="form-label">User</label>
                                <select class="form-control" id="user_id" name="user_id">
                                    <option value="">All Users</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>" <?php echo $filters['user_id'] == $user['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($user['username']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <div class="col-md-4 mb-3">
                            <label for="course_id" class="form-label">Course</label>
                            <select class="form-control" id="course_id" name="course_id">
                                <option value="">All Courses</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>" <?php echo $filters['course_id'] == $course['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($course['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if ($isAdmin || $isInstructor): ?>
                            <div class="col-md-4 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="graded" <?php echo $filters['status'] === 'graded' ? 'selected' : ''; ?>>Graded</option>
                                    <option value="pending" <?php echo $filters['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h3><i class="fas fa-book"></i> Submissions</h3>
            </div>
            <div class="card-body">
                <?php if (empty($submissions)): ?>
                    <p>No submissions found.</p>
                <?php else: ?>
                    <style>
                        .table-custom th,
                        .table-custom td {
                            max-width: 0;
                            overflow: hidden;
                            text-overflow: ellipsis;
                            white-space: nowrap;
                        }
                        .table-custom th.course-column,
                        .table-custom td.course-column {
                            max-width: 150px;
                        }
                        .table-custom th.assignment-column,
                        .table-custom td.assignment-column {
                            max-width: 150px;
                        }
                        .table-custom th.user-column,
                        .table-custom td.user-column {
                            max-width: 100px;
                        }
                        .table-custom th.file-column,
                        .table-custom td.file-column {
                            max-width: 120px;
                        }
                        .table-custom th.submitted-at-column,
                        .table-custom td.submitted-at-column {
                            max-width: 120px;
                        }
                        .table-custom th.status-column,
                        .table-custom td.status-column {
                            max-width: 80px;
                        }
                        .table-custom th.grade-column,
                        .table-custom td.grade-column {
                            max-width: 80px;
                        }
                        .table-custom th.feedback-column,
                        .table-custom td.feedback-column {
                            max-width: 150px;
                        }
                        .table-custom a {
                            display: block;
                            overflow: hidden;
                            text-overflow: ellipsis;
                            white-space: nowrap;
                        }
                    </style>
                    <table class="table table-striped table-custom">
                        <thead>
                            <tr>
                                <th class="course-column">Course</th>
                                <th class="assignment-column">Assignment</th>
                                <?php if ($isAdmin): ?>
                                    <th class="user-column">User</th>
                                <?php endif; ?>
                                <th class="file-column">File</th>
                                <th class="submitted-at-column">Submitted At</th>
                                <th class="status-column">Status</th>
                                <th class="grade-column">Grade</th>
                                <th class="feedback-column">Feedback</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $submission): ?>
                                <tr>
                                    <td class="course-column"><?php echo htmlspecialchars($submission['course_title']); ?></td>
                                    <td class="assignment-column"><?php echo htmlspecialchars($submission['assignment_title']); ?></td>
                                    <?php if ($isAdmin): ?>
                                        <td class="user-column"><?php echo htmlspecialchars($submission['username'] ?? 'N/A'); ?></td>
                                    <?php endif; ?>
                                    <td class="file-column">
                                        <?php if ($submission['file_path'] && file_exists($_SERVER['DOCUMENT_ROOT'] . '/assets/uploads/assignments/' . basename($submission['file_path']))): ?>
                                            <a href="/assets/uploads/assignments/<?php echo htmlspecialchars(basename($submission['file_path'])); ?>" target="_blank">
                                                <?php 
                                                    $file_name = basename($submission['file_path']);
                                                    echo strlen($file_name) > 12 ? htmlspecialchars(substr($file_name, 0, 9) . '...') : htmlspecialchars($file_name);
                                                ?>
                                            </a>
                                        <?php elseif ($submission['file_path']): ?>
                                            <span class="text-warning"><?php echo htmlspecialchars(substr(basename($submission['file_path']), 0, 9) . (strlen(basename($submission['file_path'])) > 12 ? '...' : '')); ?></span>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td class="submitted-at-column"><?php echo date('d/m/Y H:i', strtotime($submission['submitted_at'])); ?></td>
                                    <td class="status-column"><?php echo ucfirst($submission['status']); ?></td>
                                    <td class="grade-column">
                                        <?php echo $submission['grade'] ? number_format($submission['grade'], 2) : 'N/A'; ?>
                                        <?php if (($isAdmin || $isInstructor) && $submission['status'] === 'pending'): ?>
                                            <button type="button" class="btn btn-sm btn-success ms-2" data-bs-toggle="modal" data-bs-target="#gradeModal<?php echo $submission['submission_id']; ?>">Grade</button>
                                        <?php endif; ?>
                                    </td>
                                    <td class="feedback-column"><?php echo htmlspecialchars($submission['feedback'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($isAdmin || $isInstructor): ?>
            <?php foreach ($submissions as $submission): ?>
                <?php if ($submission['status'] === 'pending'): ?>
                    <div class="modal fade" id="gradeModal<?php echo $submission['submission_id']; ?>" tabindex="-1" aria-labelledby="gradeModalLabel<?php echo $submission['submission_id']; ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="gradeModalLabel<?php echo $submission['submission_id']; ?>">Grade Submission</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form action="/controllers/grading_action.php" method="POST">
                                        <input type="hidden" name="action" value="grade">
                                        <input type="hidden" name="submission_id" value="<?php echo $submission['submission_id']; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $submission['user_id']; ?>">
                                        <input type="hidden" name="course_id" value="<?php echo $submission['course_id']; ?>">
                                        <div class="mb-3">
                                            <label for="grade<?php echo $submission['submission_id']; ?>" class="form-label">Grade (0-100)</label>
                                            <input type="number" class="form-control" id="grade<?php echo $submission['submission_id']; ?>" name="grade" min="0" max="100" step="0.01" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="feedback<?php echo $submission['submission_id']; ?>" class="form-label">Feedback</label>
                                            <textarea class="form-control" id="feedback<?php echo $submission['submission_id']; ?>" name="feedback" rows="4"></textarea>
                                        </div>
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">Submit Grade</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include_once '../includes/footer.php'; ?>
</body>
</html>