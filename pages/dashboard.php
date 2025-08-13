<?php 
include_once '../includes/auth.php';
protectPage();

include_once '../config/db.php';

$currentUser = getCurrentUser($pdo);
if (!$currentUser || !isset($currentUser['id'])) {
    header('Location: /login.php');
    exit;
}

$isInstructor = $currentUser['role'] === 'instructor';
$isStudent = $currentUser['role'] === 'student';

try {
    if ($isStudent) {
        $stmt = $pdo->prepare("
            SELECT c.id, c.title, c.description, c.price
            FROM purchases p
            JOIN courses c ON p.course_id = c.id
            WHERE p.user_id = ?
            ORDER BY p.purchased_at DESC
        ");
        $stmt->execute([$currentUser['id']]);
        $purchasedCourses = $stmt->fetchAll();

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
        $gradeStats = [
            'Fail' => $gradeStatsRaw['Fail'] ?? 0,
            'Pass' => $gradeStatsRaw['Pass'] ?? 0,
            'Merit' => $gradeStatsRaw['Merit'] ?? 0,
            'Distinction' => $gradeStatsRaw['Distinction'] ?? 0
        ];
    } else {
        $purchasedCourses = [];
        $assignments = [];
        $gradeStats = ['Fail' => 0, 'Pass' => 0, 'Merit' => 0, 'Distinction' => 0];
    }

    if ($isInstructor) {
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total_courses FROM courses WHERE instructor_id = ?");
        $stmt->execute([$currentUser['id']]);
        $totalCourses = $stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT p.user_id) AS total_students
            FROM purchases p
            JOIN courses c ON p.course_id = c.id
            WHERE c.instructor_id = ?
        ");
        $stmt->execute([$currentUser['id']]);
        $totalStudents = $stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT c.id, c.title, COUNT(DISTINCT p.user_id) AS student_count
            FROM courses c
            LEFT JOIN purchases p ON c.id = p.course_id
            WHERE c.instructor_id = ?
            GROUP BY c.id, c.title
            ORDER BY c.title ASC
        ");
        $stmt->execute([$currentUser['id']]);
        $courseStudentStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS total_assignments
            FROM assignments a
            JOIN courses c ON a.course_id = c.id
            WHERE c.instructor_id = ?
        ");
        $stmt->execute([$currentUser['id']]);
        $totalAssignments = $stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT s.status, COUNT(*) AS count
            FROM submissions s
            JOIN assignments a ON s.assignment_id = a.id
            JOIN courses c ON a.course_id = c.id
            WHERE c.instructor_id = ?
            GROUP BY s.status
        ");
        $stmt->execute([$currentUser['id']]);
        $assignmentStatsRaw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $assignmentStats = [
            'graded' => $assignmentStatsRaw['graded'] ?? 0,
            'pending' => $assignmentStatsRaw['pending'] ?? 0
        ];

        $stmt = $pdo->prepare("
            SELECT DATE_FORMAT(p.purchased_at, '%Y-%m') AS month, SUM(c.price) AS revenue
            FROM purchases p
            JOIN courses c ON p.course_id = c.id
            WHERE c.instructor_id = ?
              AND p.purchased_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
            GROUP BY DATE_FORMAT(p.purchased_at, '%Y-%m')
            ORDER BY month DESC
        ");
        $stmt->execute([$currentUser['id']]);
        $revenueDataRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("
            SELECT SUM(c.price) AS total_revenue
            FROM purchases p
            JOIN courses c ON p.course_id = c.id
            WHERE c.instructor_id = ?
              AND p.purchased_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        ");
        $stmt->execute([$currentUser['id']]);
        $totalRevenue = $stmt->fetchColumn() ?: 0;

        $revenueData = [];
        $labels = [];
        $currentMonth = new DateTime();
        for ($i = 0; $i < 3; $i++) {
            $monthKey = $currentMonth->format('Y-m');
            $labels[] = $currentMonth->format('M Y');
            $revenueData[$monthKey] = 0;
            $currentMonth->modify('-1 month');
        }
        foreach ($revenueDataRaw as $row) {
            $revenueData[$row['month']] = (float)$row['revenue'];
        }
        $revenueValues = array_values($revenueData);
        $labels = array_reverse($labels);
        $revenueValues = array_reverse($revenueValues);
    } else {
        $totalCourses = 0;
        $totalStudents = 0;
        $totalAssignments = 0;
        $assignmentStats = ['graded' => 0, 'pending' => 0];
        $courseStudentStats = [];
        $totalRevenue = 0;
        $revenueData = [];
        $labels = [];
        $revenueValues = [];
    }
} catch (PDOException $e) {
    error_log("Error fetching dashboard data: " . $e->getMessage());
    $purchasedCourses = $assignments = [];
    $gradeStats = ['Fail' => 0, 'Pass' => 0, 'Merit' => 0, 'Distinction' => 0];
    $totalCourses = $totalStudents = $totalAssignments = $totalRevenue = 0;
    $assignmentStats = ['graded' => 0, 'pending' => 0];
    $courseStudentStats = [];
    $revenueData = [];
    $labels = [];
    $revenueValues = [];
}

include_once '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Welcome, <?php echo htmlspecialchars($currentUser['username']); ?>!</h2>

        <?php if ($isInstructor): ?>
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-book"></i> Total Courses</h5>
                            <p class="card-text display-4"><?php echo $totalCourses; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-users"></i> Total Students</h5>
                            <p class="card-text display-4"><?php echo $totalStudents; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h3><i class="fas fa-users"></i> Students per Course</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($courseStudentStats)): ?>
                        <p>No courses or students found.</p>
                    <?php else: ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Course Title</th>
                                    <th>Number of Students</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courseStudentStats as $course): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($course['title']); ?></td>
                                        <td><?php echo $course['student_count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h3><i class="fas fa-tasks"></i> Assignment Statistics</h3>
                </div>
                <div class="card-body">
                    <p><strong>Total Assignments:</strong> <?php echo $totalAssignments; ?></p>
                    <p><strong>Graded:</strong> <?php echo $assignmentStats['graded']; ?></p>
                    <p><strong>Pending:</strong> <?php echo $assignmentStats['pending']; ?></p>
                    <canvas id="assignmentChart" height="100"></canvas>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h3><i class="fas fa-dollar-sign"></i> Revenue (Last 3 Months)</h3>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="100"></canvas>
                    <p class="mt-3"><strong>Total Revenue:</strong> $<?php echo number_format($totalRevenue, 2); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($isStudent): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h3><i class="fas fa-book"></i> Purchased Courses</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($purchasedCourses)): ?>
                        <p>You have not purchased any courses yet.</p>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($purchasedCourses as $course): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card border-success">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                            <p class="card-text"><?php echo htmlspecialchars($course['description'] ?: 'No description'); ?></p>
                                            <p><strong>Price:</strong> <?php echo $course['price'] == 0 ? 'Free' : '$' . number_format($course['price'], 2); ?></p>
                                            <a href="/pages/course_details.php?course_id=<?php echo $course['id']; ?>" class="btn btn-outline-success btn-sm">View Details</a>
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
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        <?php if ($isStudent): ?>
            const completedCtx = document.getElementById('completedCoursesChart').getContext('2d');
            new Chart(completedCtx, {
                type: 'bar',
                data: {
                    labels: ['My Courses'],
                    datasets: [{
                        label: 'Courses',
                        data: [<?php echo count($purchasedCourses); ?>],
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
                        legend: { position: 'bottom' }
                    }
                }
            });
        <?php endif; ?>

        <?php if ($isInstructor): ?>
            const assignmentCtx = document.getElementById('assignmentChart').getContext('2d');
            new Chart(assignmentCtx, {
                type: 'bar',
                data: {
                    labels: ['Graded', 'Pending'],
                    datasets: [{
                        label: 'Assignment Status',
                        data: [<?php echo $assignmentStats['graded']; ?>, <?php echo $assignmentStats['pending']; ?>],
                        backgroundColor: ['#28a745', '#dc3545'],
                        borderColor: ['#218838', '#c82333'],
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });

            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($labels); ?>,
                    datasets: [{
                        label: 'Revenue ($)',
                        data: <?php echo json_encode($revenueValues); ?>,
                        fill: false,
                        borderColor: '#007bff',
                        backgroundColor: '#007bff',
                        tension: 0.1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Revenue ($)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Month'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Revenue: $' + context.parsed.y.toFixed(2);
                                }
                            }
                        }
                    }
                }
            });
        <?php endif; ?>
    </script>

    <?php include_once '../includes/footer.php'; ?>
</body>
</html>