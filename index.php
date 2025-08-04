<?php
include_once 'config/db.php';
include_once 'includes/auth.php';
include_once 'models/Course.php';

$currentUser = getCurrentUser($pdo);
$courseModel = new Course($pdo);

$courses = [];
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

try {
    $stmt = $pdo->query("
        SELECT c.id, c.title, c.description, c.price, c.created_at, c.image_path, u.username AS instructor_name
        FROM courses c
        JOIN users u ON c.instructor_id = u.id
        WHERE c.is_public = 1
        ORDER BY c.created_at DESC
        LIMIT 6
    ");
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching courses: " . $e->getMessage());
    $error = "Failed to load courses.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .course-card {
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
        }
        .course-card:hover {
            transform: translateY(-4px);
        }
        .course-img {
            height: 180px;
            object-fit: cover;
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
        }
        .course-title {
            font-size: 1.1rem;
            font-weight: 600;
        }
        .course-desc {
            font-size: 0.9rem;
            color: #555;
        }
    </style>
</head>
<body>
    <?php include_once 'includes/header.php'; ?>

    <div class="container mt-5">
        <div class="text-center mb-5">
            <h1>Welcome to Our Learning Management System</h1>
            <p class="lead">Explore a wide range of courses to enhance your skills and knowledge.</p>
            <?php if ($currentUser): ?>
                <p>Hello, <strong><?php echo htmlspecialchars($currentUser['username']); ?></strong>! 
                <?php if ($currentUser['role'] === 'admin'): ?>
                    <a href="/pages/analytics.php" class="btn btn-primary">View Analytics</a>
                <?php else: ?>
                    <a href="/pages/dashboard.php" class="btn btn-primary">Browse Dashboard</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="/auth/login.php" class="btn btn-primary">Login</a>
                <a href="/pages/register.php" class="btn btn-secondary">Register</a>
            <?php endif; ?>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php
                switch ($success) {
                    case 'login_success': echo 'Logged in successfully!'; break;
                    case 'register_success': echo 'Registered successfully! Please log in.'; break;
                }
                ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php
                switch ($error) {
                    case 'load_failed': echo 'Failed to load courses.'; break;
                    default: echo 'An error occurred.'; break;
                }
                ?>
            </div>
        <?php endif; ?>

        <h2 class="mb-4">Featured Courses</h2>
        <div class="row">
            <?php if (empty($courses)): ?>
                <div class="col-12">
                    <p>No courses available at the moment.</p>
                </div>
            <?php else: ?>
                <?php foreach ($courses as $course): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card course-card h-100">
                            <?php if (!empty($course['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($course['image_path']); ?>" class="card-img-top course-img" alt="Course Image">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                <p class="course-desc"><?php echo htmlspecialchars(substr($course['description'], 0, 100)) . (strlen($course['description']) > 100 ? '...' : ''); ?></p>
                                <p class="mb-1"><strong>Price:</strong> $<?php echo number_format($course['price'], 2); ?></p>
                                <p class="mb-1"><strong>Creator:</strong> <?php echo htmlspecialchars($course['instructor_name']); ?></p>
                                <p class="mb-1"><small><strong>Created:</strong> <?php echo date('F j, Y', strtotime($course['created_at'])); ?></small></p>
                            </div>
                            <div class="card-footer bg-white border-top-0">
                                <?php if ($currentUser && $courseModel->isUserEnrolled($currentUser['id'], $course['id'])): ?>
                                    <a href="/pages/course.php?course_id=<?php echo $course['id']; ?>" class="btn btn-success w-100">Go to Course</a>
                                <?php else: ?>
                                    <a href="/pages/buy_course.php?course_id=<?php echo $course['id']; ?>" class="btn btn-primary w-100">View Details</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

