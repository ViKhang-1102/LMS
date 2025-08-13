<?php
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
$totalAssignments = 0;
$totalSubmissions = 0;
$totalPurchases = 0;
$submissionStats = [];
$courseBuyStats = [];
$totalPrice3Months = 0;
$priceByRoleStats = [];

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $totalUsers = $stmt->fetchColumn();
    $totalCourses = count($courseModel->getAllCourses(true));

    $stmt = $pdo->query("SELECT COUNT(*) FROM assignments");
    $totalAssignments = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM submissions");
    $totalSubmissions = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM submissions GROUP BY status");
    $submissionStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $stmt = $pdo->query("SELECT COUNT(*) FROM purchases");
    $totalPurchases = $stmt->fetchColumn();

    $stmt = $pdo->query("
        SELECT c.title AS course_title, COUNT(p.id) AS buy_count
        FROM purchases p
        JOIN courses c ON p.course_id = c.id
        GROUP BY c.id
        ORDER BY buy_count DESC
        LIMIT 10
    ");
    $courseBuyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $threeMonthsAgo = date('Y-m-d H:i:s', strtotime('-3 months'));
    $stmt = $pdo->prepare("
        SELECT u.username, u.role, SUM(c.price) as total_price
        FROM purchases p
        JOIN courses c ON p.course_id = c.id
        JOIN users u ON c.instructor_id = u.id
        WHERE p.purchased_at >= ?
        GROUP BY u.id, u.username, u.role
        ORDER BY total_price DESC
    ");
    $stmt->execute([$threeMonthsAgo]);
    $priceByInstructorStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalPrice3Months = array_sum(array_column($priceByInstructorStats, 'total_price'));

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

    <div class="row row-cols-1 row-cols-md-3 row-cols-lg-6 g-3 mb-4">
        <div class="col"><div class="card text-center"><div class="card-body"><h6>Total Users</h6><p class="fw-bold"><?php echo $totalUsers; ?></p></div></div></div>
        <div class="col"><div class="card text-center"><div class="card-body"><h6>Total Courses</h6><p class="fw-bold"><?php echo $totalCourses; ?></p></div></div></div>
        <div class="col"><div class="card text-center"><div class="card-body"><h6>Total Assignments</h6><p class="fw-bold"><?php echo $totalAssignments; ?></p></div></div></div>
        <div class="col"><div class="card text-center"><div class="card-body"><h6>Total Submissions</h6><p class="fw-bold"><?php echo $totalSubmissions; ?></p></div></div></div>
        <div class="col"><div class="card text-center"><div class="card-body"><h6>Total Purchases</h6><p class="fw-bold"><?php echo $totalPurchases; ?></p></div></div></div>
        <div class="col"><div class="card text-center"><div class="card-body"><h6>Total Price (3 Months)</h6><p class="fw-bold">$<?php echo number_format($totalPrice3Months, 2); ?></p></div></div></div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-light"><strong>Submission Status</strong></div>
                <div class="card-body"><canvas id="submissionChart"></canvas></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-light"><strong>Top Courses Purchased</strong></div>
                <div class="card-body"><canvas id="buyChart"></canvas></div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-light"><strong>Total Price by Instructor (3 Months)</strong></div>
                <div class="card-body" style="height:350px; display:flex; align-items:center; justify-content:center;">
                    <canvas id="priceChart" style="max-height:320px; max-width:100%;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-light"><strong>Details of Top Courses Purchased</strong></div>
                <div class="card-body p-2">
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-bordered table-hover table-sm mb-0">
                            <thead class="table-primary">
                                <tr><th>Course</th><th>Buy Count</th></tr>
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
            </div>
        </div>
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
    options: { scales: { y: { beginAtZero: true } } }
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
    options: { scales: { y: { beginAtZero: true } } }
});

const priceChart = new Chart(document.getElementById('priceChart'), {
    type: 'pie',
    data: {
        labels: [<?php echo implode(',', array_map(fn($p) => "'" . addslashes($p['username'] . ' (' . $p['role'] . ')') . "'", $priceByInstructorStats)); ?>],
        datasets: [{
            label: 'Total Price',
            data: [<?php echo implode(',', array_map(fn($p) => $p['total_price'] ?? 0, $priceByInstructorStats)); ?>],
            backgroundColor: ['#ff6384', '#36a2eb', '#ffcd56', '#4bc0c0', '#9966ff', '#ff9f40', '#e67e22', '#2ecc71', '#e84393', '#00b894']
        }]
    },
    options: { responsive: true, plugins: { legend: { position: 'top' } } }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
