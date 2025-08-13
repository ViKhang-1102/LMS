<?php
include_once '../config/db.php';
include_once '../includes/auth.php';

if (isLoggedIn()) {
    header('Location: /pages/dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'student';

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (!preg_match('/^[a-zA-Z0-9]{4,20}$/', $username)) {
        $error = 'Username should contain only letters and numbers, 4-20 characters.';
    } elseif (!in_array($role, ['student', 'instructor'])) {
        $error = 'Invalid role selected.'; 
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = 'Username or email already exists.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $email, $hashedPassword, $role]);
                $success = 'Registration successful! Please login.';
            }
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $error = 'An error occurred. Please try again.';
        }
    }
}

include_once '../includes/header.php';
?>

<div class="container mt-5">
    <h1 class="text-center mb-4">Register</h1>
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <h3 class="text-center mb-4">Create an Account</h3>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required 
                                   pattern="[a-zA-Z0-9]{4,20}" title="Username should contain only letters and numbers, 4-20 characters">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <!--
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="student">Student</option>
                                <option value="instructor">Instructor</option>
                            </select>
                        </div>
                        -->
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="6">
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Register</button>
                        </div>
                        <div class="text-center mt-3">
                            <a href="/auth/login.php">Already have an account? Login</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include_once '../includes/footer.php';
?>