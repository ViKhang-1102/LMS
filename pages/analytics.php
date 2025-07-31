<?php
/*
include_once '../includes/auth.php';
protectPage(['admin', 'instructor']);

include_once '../config/db.php';
include_once '../models/User.php';
include_once '../models/Course.php';
include_once '../models/Assignment.php';
include_once '../models/Grade.php';
include_once '../models/Payment.php';

$currentUser = getCurrentUser($pdo);

$userModel = new User($pdo);
$courseModel = new Course($pdo);
$assignmentModel = new Assignment($pdo);
$gradeModel = new Grade($pdo);
$paymentModel = new Payment($pdo);

$totalUsers = 0;
$totalCourses = 0;
$totalSubmissions = 0;
$totalPayments = 0;
$submissionStats = [];
$paymentStats = [];
$averageGrades = [];

try {
    if ($currentUser['role'] === 'admin') {
        $totalUsers = count($userModel->getAllUsers());
        $totalCourses = count($courseModel->getAllCourses(true));
    } else {
        $courses = $courseModel->getAllCourses(false);
        $totalCourses = count(array_filter($courses, function($course) use ($currentUser) {
            return $course['instructor_id'] == $currentUser['id'];
        }));
    }
    // Truy vấn trực tiếp số user từ bảng users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $totalUsers = $stmt->fetchColumn();
    $totalCourses = count($courseModel->getAllCourses(true));

    if ($currentUser['role'] === 'admin') {
        $stmt = $pdo->query("SELECT COUNT(*) FROM submissions");
        $totalSubmissions = $stmt->fetchColumn();
        $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM submissions GROUP BY status");
        $submissionStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } else {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM submissions s 
            JOIN assignments a ON s.assignment_id = a.id 
            WHERE a.course_id IN (SELECT id FROM courses WHERE instructor_id = ?)
        ");
        $stmt->execute([$currentUser['id']]);
        $totalSubmissions = $stmt->fetchColumn();
        $stmt = $pdo->prepare("
            SELECT s.status, COUNT(*) as count 
            FROM submissions s 
            JOIN assignments a ON s.assignment_id = a.id 
            WHERE a.course_id IN (SELECT id FROM courses WHERE instructor_id = ?) 
            GROUP BY s.status
        ");
        $stmt->execute([$currentUser['id']]);
        $submissionStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    if ($currentUser['role'] === 'admin') {
        $totalPayments = count($paymentModel->getAllPayments());
        $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM payments GROUP BY status");
        $paymentStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    $courses = $currentUser['role'] === 'admin' ? $courseModel->getAllCourses(true) : 
        array_filter($courseModel->getAllCourses(false), function($course) use ($currentUser) {
            return $course['instructor_id'] == $currentUser['id'];
        });
    foreach ($courses as $course) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM purchases WHERE course_id = ?
        ");
        $stmt->execute([$course['id']]);
        $buyCount = $stmt->fetchColumn();
        $courseBuyStats[] = [
            'course_title' => $course['title'],
            'buy_count' => (int)$buyCount
        ];
    }
} catch (PDOException $e) {
    error_log("Error fetching analytics: " . $e->getMessage());
    $error = "Failed to load analytics data.";
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <?php include_once '../includes/header.php'; ?>

    <div class="container mt-5">
        <h1 class="mb-4">Analytics Dashboard</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

            <div class="col-md-12 mb-4">
                <h3>Top Courses Bought</h3>
                <canvas id="buyChart"></canvas>
            </div>
                        <div class="card-body">
        <h3>Top Courses Bought Details</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Buy Count</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($courseBuyStats)): ?>
                    <tr><td colspan="2">No purchases available.</td></tr>
                <?php else: ?>
                    <?php foreach ($courseBuyStats as $stat): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($stat['course_title']); ?></td>
                            <td><?php echo $stat['buy_count']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
            </div>
            <?php if ($currentUser['role'] === 'admin'): ?>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="card-title">Total Payments</h5>
                            <p class="card-text"><?php echo $totalPayments; ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <h3>Submission Status</h3>
                <canvas id="submissionChart"></canvas>
            </div>
            <?php if ($currentUser['role'] === 'admin'): ?>
                <div class="col-md-6 mb-4">
                    <h3>Payment Status</h3>
                    <canvas id="paymentChart"></canvas>
                </div>
            <?php endif; ?>
            <div class="col-md-12 mb-4">
                <h3>Average Grades per Course</h3>
                <canvas id="gradeChart"></canvas>
            </div>
        </div>

        <h3>Average Grades Details</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Average Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($averageGrades)): ?>
                    <tr><td colspan="2">No graded submissions available.</td></tr>
                <?php else: ?>
                    <?php foreach ($averageGrades as $grade): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($grade['course_title']); ?></td>
                            <td><?php echo $grade['avg_grade']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        const submissionChart = new Chart(document.getElementById('submissionChart'), {
            type: 'bar',
            data: {
                labels: ['Pending', 'Graded'],
                datasets: [{
                    label: 'Submissions',
                    data: [<?php echo $submissionStats['pending'] ?? 0; ?>, <?php echo $submissionStats['graded'] ?? 0; ?>],
                    backgroundColor: ['#007bff', '#28a745']
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        <?php if ($currentUser['role'] === 'admin'): ?>
        const paymentChart = new Chart(document.getElementById('paymentChart'), {
            type: 'pie',
            data: {
                labels: ['Pending', 'Completed', 'Failed', 'Refunded'],
                datasets: [{
                    label: 'Payments',
                    data: [
                        <?php echo $paymentStats['pending'] ?? 0; ?>,
                        <?php echo $paymentStats['completed'] ?? 0; ?>,
                        <?php echo $paymentStats['failed'] ?? 0; ?>,
                        <?php echo $paymentStats['refunded'] ?? 0; ?>
                    ],
                    backgroundColor: ['#007bff', '#28a745', '#dc3545', '#ffc107']
                }]
            }
        });
        <?php endif; ?>

        const buyChart = new Chart(document.getElementById('buyChart'), {
            type: 'bar',
            data: {
                labels: [<?php echo implode(',', array_map(function($g) { return "'".addslashes($g['course_title'])."'"; }, $courseBuyStats)); ?>],
                datasets: [{
                    label: 'Buy Count',
                    data: [<?php echo implode(',', array_column($courseBuyStats, 'buy_count')); ?>],
                    backgroundColor: '#007bff'
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
*/








include_once '../includes/auth.php';
protectPage(['admin']); 

include_once '../config/db.php';
include_once '../models/User.php';
include_once '../models/Course.php';
include_once '../models/Assignment.php';
include_once '../models/Grade.php';

$currentUser = getCurrentUser($pdo);

$userModel = new User($pdo);
$courseModel = new Course($pdo);
$assignmentModel = new Assignment($pdo);
$gradeModel = new Grade($pdo);

$totalUsers = 0;
$totalCourses = 0;
$totalSubmissions = 0;
$totalPurchases = 0;
$submissionStats = [];
$courseBuyStats = [];

try {
    // Tổng số người dùng và khóa học
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $totalUsers = $stmt->fetchColumn();
    $totalCourses = count($courseModel->getAllCourses(true));

    // Tổng bài nộp
    $stmt = $pdo->query("SELECT COUNT(*) FROM submissions");
    $totalSubmissions = $stmt->fetchColumn();

    // Trạng thái bài nộp
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM submissions GROUP BY status");
    $submissionStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Tổng lượt mua khóa học (từ bảng purchases)
    $stmt = $pdo->query("SELECT COUNT(*) FROM purchases");
    $totalPurchases = $stmt->fetchColumn();

    // Top khóa học được mua nhiều nhất
    $stmt = $pdo->query("
        SELECT c.title AS course_title, COUNT(p.id) AS buy_count
        FROM purchases p
        JOIN courses c ON p.course_id = c.id
        GROUP BY c.id
        ORDER BY buy_count DESC
        LIMIT 10
    ");
    $courseBuyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching analytics: " . $e->getMessage());
    $error = "Failed to load analytics data.";
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<?php include_once '../includes/header.php'; ?>

<div class="container mt-5">
    <h1 class="mb-4">Analytics Dashboard</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Total Users</h5>
                    <p class="card-text"><?php echo $totalUsers; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Total Courses</h5>
                    <p class="card-text"><?php echo $totalCourses; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Total Submissions</h5>
                    <p class="card-text"><?php echo $totalSubmissions; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title">Total Purchases</h5>
                    <p class="card-text"><?php echo $totalPurchases; ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <h3>Submission Status</h3>
            <canvas id="submissionChart"></canvas>
        </div>
        <div class="col-md-6">
            <h3>Top courses purchased</h3>
            <canvas id="buyChart"></canvas>
        </div>
    </div>

    <div class="card-body">
        <h3>Details of top courses purchased</h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Buy Count</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($courseBuyStats)): ?>
                    <tr><td colspan="2">No purchases available.</td></tr>
                <?php else: ?>
                    <?php foreach ($courseBuyStats as $stat): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($stat['course_title']); ?></td>
                            <td><?php echo $stat['buy_count']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const submissionChart = new Chart(document.getElementById('submissionChart'), {
    type: 'bar',
    data: {
        labels: ['Pending', 'Graded'],
        datasets: [{
            label: 'Submissions',
            data: [<?php echo $submissionStats['pending'] ?? 0; ?>, <?php echo $submissionStats['graded'] ?? 0; ?>],
            backgroundColor: ['#007bff', '#28a745']
        }]
    },
    options: {
        scales: {
            y: { beginAtZero: true }
        }
    }
});

const buyChart = new Chart(document.getElementById('buyChart'), {
    type: 'bar',
    data: {
        labels: [<?php echo implode(',', array_map(fn($c) => "'" . addslashes($c['course_title']) . "'", $courseBuyStats)); ?>],
        datasets: [{
            label: 'Buy Count',
            data: [<?php echo implode(',', array_column($courseBuyStats, 'buy_count')); ?>],
            backgroundColor: '#17a2b8'
        }]
    },
    options: {
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
