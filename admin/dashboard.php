<?php
include_once '../includes/auth.php';
protectPage(['admin']); 

include_once '../config/db.php';

$currentUser = getCurrentUser($pdo);

try {
    $stmt = $pdo->query("SELECT COUNT(*) AS total_users FROM users");
    $totalUsers = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) AS total_courses FROM courses");
    $totalCourses = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) AS total_assignments FROM assignments");
    $totalAssignments = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $recentUsers = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT id, title, description, created_at FROM courses ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $recentCourses = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching admin dashboard data: " . $e->getMessage());
    $totalUsers = $totalCourses = $totalAssignments = 0;
    $recentUsers = $recentCourses = [];
}

include_once '../includes/header.php';
?>

<h2 class="mb-4">Admin Dashboard</h2>
<p>Welcome, <?php echo htmlspecialchars($currentUser['username']); ?>!</p>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-users"></i> Total Users</h5>
                <p class="card-text display-4"><?php echo $totalUsers; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-book"></i> Total Courses</h5>
                <p class="card-text display-4"><?php echo $totalCourses; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-tasks"></i> Total Assignments</h5>
                <p class="card-text display-4"><?php echo $totalAssignments; ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h3><i class="fas fa-link"></i> Quick Links</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <a href="/admin/users.php" class="btn btn-primary w-100 mb-2"><i class="fas fa-users-cog"></i> Manage Users</a>
            </div>
            <div class="col-md-4">
                <a href="/admin/manage_courses.php" class="btn btn-primary w-100 mb-2"><i class="fas fa-book"></i> Manage Courses</a>
            </div>
            <div class="col-md-4">
                <a href="/admin/manage_assignments.php" class="btn btn-primary w-100 mb-2"><i class="fas fa-tasks"></i> Manage Assignments</a>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h3><i class="fas fa-chart-bar"></i> System Statistics</h3>
    </div>
    <div class="card-body">
        <canvas id="systemStatsChart" height="100"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    const ctx = document.getElementById('systemStatsChart').getContext('2d');
    const systemStatsChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Users', 'Courses', 'Assignments'],
            datasets: [{
                label: 'System Statistics',
                data: [<?php echo $totalUsers; ?>, <?php echo $totalCourses; ?>, <?php echo $totalAssignments; ?>],
                backgroundColor: ['#007bff', '#28a745', '#ffc107'],
                borderColor: ['#0056b3', '#218838', '#e0a800'],
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php
include_once '../includes/footer.php';
?>