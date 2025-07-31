<?php 
include_once '../includes/auth.php';

if (isLoggedIn()) {
    header('Location: /pages/dashboard.php');
    exit();
}

include_once '../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm mt-5">
            <div class="card-body">
                <h2 class="card-title text-center mb-4">Forgot Password</h2>
                
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php
                        if ($_GET['error'] == 'email_not_found') {
                            echo 'Email does not exist in the system!';
                        } elseif ($_GET['error'] == 'server_error') {
                            echo 'An error occurred, please try again!';
                        }
                        ?>
                    </div>
                <?php elseif (isset($_GET['success'])): ?>
                    <div class="alert alert-success" role="alert">
                        A password reset link has been sent to your email!
                    </div>
                <?php endif; ?>

                <form action="/controllers/forgot_password.php" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label"><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" class="form-control" id="email" name="email" required placeholder="Enter your email">
                    </div>
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send Request</button>
                    </div>
                    <div class="text-center">
                        <p>Back to <a href="/auth/login.php">Login</a> or <a href="/auth/register.php">Register</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include_once '../includes/footer.php';
?>
