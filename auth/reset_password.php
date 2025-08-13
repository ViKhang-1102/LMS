<?php
include_once '../includes/auth.php';
include_once '../config/db.php';

if (isLoggedIn()) {
    header('Location: /pages/dashboard.php');
    exit();
}

$token = $_GET['token'] ?? '';
if (empty($token)) {
    header('Location: /auth/forgot_password.php?error=invalid_token');
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT user_id, expiry FROM password_resets WHERE token = ?");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if (!$reset || strtotime($reset['expiry']) < time()) {
        header('Location: /auth/forgot_password.php?error=invalid_token');
        exit();
    }
} catch (PDOException $e) {
    error_log("Reset password error: " . $e->getMessage());
    header('Location: /auth/forgot_password.php?error=server_error');
    exit();
}

include_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm mt-5">
            <div class="card-body">
                <h2 class="card-title text-center mb-4">Reset Password</h2>
                
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php
                        if ($_GET['error'] == 'password_mismatch') {
                            echo 'Password confirmation does not match!';
                        } elseif ($_GET['error'] == 'short_password') {
                            echo 'Password must be at least 6 characters long!';
                        } else {
                            echo 'An error occurred, please try again!';
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <form action="/controllers/reset_password.php" method="POST">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <div class="mb-3">
                        <label for="password" class="form-label"><i class="fas fa-lock"></i> New Password</label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="6" title="Password must be at least 6 characters long">
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label"><i class="fas fa-lock"></i> Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Password</button>
                    </div>
                    <div class="text-center">
                        <p>Back to <a href="/auth/login.php">Login</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include_once '../includes/footer.php';
?>