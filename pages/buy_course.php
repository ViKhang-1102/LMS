<?php
include_once '../includes/auth.php';
protectPage();

include_once '../config/db.php';
include_once '../models/Course.php';
include_once '../models/Payment.php';
include_once '../models/User.php';

$currentUser = getCurrentUser($pdo);

$courseModel = new Course($pdo);
$paymentModel = new Payment($pdo);
$userModel = new User($pdo);

$courseId = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$course = $courseModel->getCourseById($courseId);

if (!$course) {
    header("Location: /pages/courses.php?error=invalid_course");
    exit();
}

$stmt = $pdo->prepare("SELECT 1 FROM purchases WHERE user_id = ? AND course_id = ?");
$stmt->execute([$currentUser['id'], $courseId]);
$alreadyPurchased = $stmt->fetch();

$isEnrolled = $courseModel->isUserEnrolled($currentUser['id'], $courseId);
$hasPaid = $paymentModel->hasPaid($currentUser['id'], $courseId);

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy Course - <?php echo htmlspecialchars($course['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include_once '../includes/header.php'; ?>

<div class="container mt-5">
    <h1 class="mb-4">Buy Course: <?php echo htmlspecialchars($course['title']); ?></h1>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php
            switch ($success) {
                case 'payment_created': echo 'Payment initiated successfully! Please complete the payment process.'; break;
                case 'purchased': echo 'You have successfully purchased this course!'; break;
            }
            ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?php
            switch ($error) {
                case 'invalid_course': echo 'Invalid course!'; break;
                case 'already_enrolled': echo 'You are already enrolled in this course!'; break;
                case 'already_paid': echo 'You have already paid for this course!'; break;
                case 'already_purchased': echo 'You already purchased this course!'; break;
                case 'payment_failed': echo 'Failed to initiate payment!'; break;
                case 'unauthorized': echo 'You are not authorized to perform this action!'; break;
            }
            ?>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <h3><?php echo htmlspecialchars($course['title']); ?></h3>
            <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($course['description'])); ?></p>
            <p><strong>Price:</strong> $<?php echo number_format($course['price'], 2); ?></p>
            <p><strong>Instructor:</strong> <?php echo htmlspecialchars($course['instructor_name']); ?></p>
            <p><strong>Created At:</strong> <?php echo date('F j, Y', strtotime($course['created_at'])); ?></p>
        </div>
    </div>

    <?php if ($isEnrolled): ?>
        <div class="alert alert-info">
            You are already enrolled in this course. <a href="/pages/course.php?course_id=<?php echo $courseId; ?>" class="alert-link">Go to course</a>.
        </div>
    <?php elseif ($hasPaid): ?>
        <div class="alert alert-info">
            You have already paid for this course but are not yet enrolled. Contact support if needed.
        </div>
    <?php elseif ($alreadyPurchased): ?>
        <div class="alert alert-info">
            You have already purchased this course.
        </div>
    <?php else: ?>
        <div class="mb-5">
            <h3>Proceed to Payment</h3>
            <form action="/controllers/payment_action.php" method="POST" class="mb-4">
                <input type="hidden" name="action" value="create_payment">
                <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">
                <div class="mb-3">
                    <label for="payment_method" class="form-label">Payment Method</label>
                    <select class="form-control" id="payment_method" name="payment_method" required>
                        <option value="">Select a payment method</option>
                        <option value="credit_card">Credit Card</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="paypal">PayPal</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="transaction_id" class="form-label">Transaction ID (for testing)</label>
                    <input type="text" class="form-control" id="transaction_id" name="transaction_id" value="TEST_<?php echo time(); ?>" required>
                </div>
                <!--<p><strong>Amount:</strong> $<?php echo number_format($course['price'], 2); ?></p>-->
                <!--<button type="submit" class="btn btn-primary">Pay Now</button>-->
            </form>

            <form action="/controllers/payment_action.php" method="POST" onsubmit="return confirm('Do you want to purchase this course now?');">
                <input type="hidden" name="action" value="buy">
                <input type="hidden" name="course_id" value="<?php echo $courseId; ?>">
                <button type="submit" class="btn btn-success"><i class="fas fa-cart-plus"></i> Buy Now (Save to Dashboard)</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

