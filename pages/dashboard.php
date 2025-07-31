<?php 
include_once '../includes/auth.php';
protectPage();

include_once '../config/db.php';

$currentUser = getCurrentUser($pdo);
if (!$currentUser || !isset($currentUser['id'])) {
    header('Location: /login.php');
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT c.id, c.title, c.description, c.price
        FROM purchases p
        JOIN courses c ON p.course_id = c.id
        WHERE p.user_id = ?
        ORDER BY p.purchased_at DESC
    ");
    $stmt->execute([$currentUser['id']]);
    $purchasedCourses = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching purchased courses: " . $e->getMessage());
    $purchasedCourses = [];
}

try {
    $stmt = $pdo->prepare("
        SELECT c.id, c.title, c.description, 0 AS progress
        FROM purchases p
        JOIN courses c ON p.course_id = c.id
        WHERE p.user_id = ?
        GROUP BY c.id, c.title, c.description
    ");
    $stmt->execute([$currentUser['id']]);
    $courses = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching courses: " . $e->getMessage());
    $courses = [];
}

try {
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.due_date, c.title AS course_title
        FROM assignments a
        JOIN courses c ON a.course_id = c.id
        JOIN purchases p ON c.id = p.course_id
        WHERE p.user_id = ?
          AND a.due_date >= NOW()
        ORDER BY a.due_date ASC
        LIMIT 5
    ");
    $stmt->execute([$currentUser['id']]);
    $assignments = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching assignments: " . $e->getMessage());
    $assignments = [];
}

/*
try {
    $stmt = $pdo->prepare("
        SELECT m.id, m.content, m.created_at, u.username AS sender
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.receiver_id = ?
        ORDER BY m.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$currentUser['id']]);
    $messages = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching messages: " . $e->getMessage());
    $messages = [];
}
*/

try {
    $stmt = $pdo->prepare("
        SELECT 
            CASE 
                WHEN g.grade < 65 THEN 'Fail'
                WHEN g.grade BETWEEN 65 AND 79 THEN 'Pass'
                WHEN g.grade BETWEEN 80 AND 89 THEN 'Merit'
                WHEN g.grade BETWEEN 90 AND 100 THEN 'Distinction'
                ELSE 'Unknown'
            END AS grade_category,
            COUNT(*) AS count
        FROM grades g
        JOIN submissions s ON g.submission_id = s.id
        WHERE s.user_id = ?
        GROUP BY grade_category
    ");
    $stmt->execute([$currentUser['id']]);
    $gradeStatsRaw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    error_log("Error fetching grade stats: " . $e->getMessage());
    $gradeStatsRaw = [];
}

$gradeStats = [
    'Fail' => $gradeStatsRaw['Fail'] ?? 0,
    'Pass' => $gradeStatsRaw['Pass'] ?? 0,
    'Merit' => $gradeStatsRaw['Merit'] ?? 0,
    'Distinction' => $gradeStatsRaw['Distinction'] ?? 0
];

include_once '../includes/header.php';
?>

<h2 class="mb-4">Welcome, <?php echo htmlspecialchars($currentUser['username']); ?>!</h2>

<div class="card mb-4">
    <div class="card-header">
        <h3><i class="fas fa-book"></i> Purchased Courses</h3>
    </div>
    <div class="card-body">
        <?php if (empty($courses)): ?>
            <?php if (!empty($purchasedCourses)): ?>
                <div class="row">
                    <?php foreach ($purchasedCourses as $course): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card border-success">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars($course['description']); ?></p>
                                    <p><strong>Price:</strong> <?php echo $course['price'] == 0 ? 'Free' : '$' . number_format($course['price'], 2); ?></p>
                                    <a href="/pages/course_details.php?course_id=<?php echo $course['id']; ?>" class="btn btn-outline-success btn-sm">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>You have not purchased any courses yet.</p>
            <?php endif; ?>
        <?php else: ?>
            <div class="row">
                <?php foreach ($courses as $course): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($course['description']); ?></p>
                                <a href="/pages/course_details.php?course_id=<?php echo $course['id']; ?>" class="btn btn-primary">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h3><i class="fas fa-chart-bar"></i> Learning Statistics</h3>
    </div>
    <div class="card-body row">
        <div class="col-md-6">
            <h5 class="text-center">My Courses</h5>
            <canvas id="completedCoursesChart" height="200"></canvas>
        </div>
        <div class="col-md-6">
            <h5 class="text-center">Grade Distribution</h5>
            <canvas id="gradePieChart" style="max-width: 100%; height: 200px;" class="d-block m-auto"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    const completedCtx = document.getElementById('completedCoursesChart').getContext('2d');
    new Chart(completedCtx, {
        type: 'bar',
        data: {
            labels: ['My Courses'],
            datasets: [{
                label: 'Courses',
                data: [<?php echo count($courses); ?>],
                backgroundColor: ['#007bff'],
                borderColor: ['#0056b3'],
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    const gradeCtx = document.getElementById('gradePieChart').getContext('2d');
    new Chart(gradeCtx, {
        type: 'pie',
        data: {
            labels: ['Fail', 'Pass', 'Merit', 'Distinction'],
            datasets: [{
                data: [
                    <?php echo $gradeStats['Fail']; ?>,
                    <?php echo $gradeStats['Pass']; ?>,
                    <?php echo $gradeStats['Merit']; ?>,
                    <?php echo $gradeStats['Distinction']; ?>
                ],
                backgroundColor: ['#dc3545', '#ffc107', '#17a2b8', '#28a745'],
                borderColor: ['#c82333', '#e0a800', '#117a8b', '#218838'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php include_once '../includes/footer.php'; ?>
