<?php
include_once '../includes/auth.php';
protectPage(['admin']);

include_once '../config/db.php';
include_once '../models/User.php';
include_once '../models/Course.php';
include_once '../models/Assignment.php';
include_once '../models/Grade.php';
include_once '../models/Payment.php';

header('Content-Type: application/json');

$currentUser = getCurrentUser($pdo);

$userModel = new User($pdo);
$courseModel = new Course($pdo);
$assignmentModel = new Assignment($pdo);
$gradeModel = new Grade($pdo);
$paymentModel = new Payment($pdo);

$chartType = $_GET['type'] ?? '';

$response = ['success' => false, 'data' => [], 'error' => ''];

try {
    switch ($chartType) {
        case 'submission_status':
            $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM submissions GROUP BY status");
            $submissionStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            $response['data'] = [
                'labels' => ['Pending', 'Graded'],
                'datasets' => [[
                    'label' => 'Submissions',
                    'data' => [
                        $submissionStats['pending'] ?? 0,
                        $submissionStats['graded'] ?? 0
                    ],
                    'backgroundColor' => ['#007bff', '#28a745']
                ]]
            ];
            $response['success'] = true;
            break;

        case 'payment_status':
            $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM payments GROUP BY status");
            $paymentStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            $response['data'] = [
                'labels' => ['Pending', 'Completed', 'Failed', 'Refunded'],
                'datasets' => [[
                    'label' => 'Payments',
                    'data' => [
                        $paymentStats['pending'] ?? 0,
                        $paymentStats['completed'] ?? 0,
                        $paymentStats['failed'] ?? 0,
                        $paymentStats['refunded'] ?? 0
                    ],
                    'backgroundColor' => ['#007bff', '#28a745', '#dc3545', '#ffc107']
                ]]
            ];
            $response['success'] = true;
            break;

        case 'top_purchases':
            $stmt = $pdo->query("
                SELECT c.title, COUNT(p.id) AS count
                FROM purchases p
                JOIN courses c ON p.course_id = c.id
                GROUP BY c.id
                ORDER BY count DESC
                LIMIT 10
            ");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $labels = array_column($rows, 'title');
            $data = array_column($rows, 'count');

            $response['data'] = [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Top Purchased Courses',
                    'data' => $data,
                    'backgroundColor' => '#17a2b8'
                ]]
            ];
            $response['success'] = true;
            break;

        default:
            throw new Exception('Invalid chart type');
    }
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
