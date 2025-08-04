<?php
include_once __DIR__ . '/../includes/auth.php';
include_once '../includes/auth.php';

protectPage();
include_once '../config/db.php';

$currentUser = getCurrentUser($pdo);

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

try {
    $stmt = $pdo->prepare("SELECT username, email, avatar_path FROM users WHERE id = ?");
    $stmt->execute([$currentUser['id']]);
    $userProfile = $stmt->fetch();
    if (!$userProfile) {
        $userProfile = ['username' => $currentUser['username'], 'email' => $currentUser['email'], 'avatar_path' => null];
    }
} catch (PDOException $e) {
    error_log("Error fetching user profile: " . $e->getMessage());
    $userProfile = ['username' => $currentUser['username'], 'email' => $currentUser['email'], 'avatar_path' => null];
}

include_once '../includes/header.php';
?>

<div class="container mt-5">
    <h1 class="text-center mb-4">User Profile</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <?php
            switch ($error) {
                case 'update_failed':
                    echo 'Profile update failed!';
                    break;
                case 'email_taken':
                    echo 'Email is already taken!';
                    break;
                case 'password_mismatch':
                    echo 'Password confirmation does not match!';
                    break;
                case 'invalid_email':
                    echo 'Invalid email!';
                    break;
                case 'upload_failed':
                    echo 'Avatar upload failed! Only JPG/PNG, max 2MB.';
                    break;
                default:
                    echo 'An error occurred, please try again!';
            }
            ?>
        </div>
    <?php elseif ($success): ?>
        <div class="alert alert-success" role="alert">
            Profile updated successfully!
        </div>
    <?php endif; ?>

    <div class="card mb-4 shadow-sm">
        <div class="card-header">
            <h3><i class="fas fa-user"></i> Personal Information</h3>
        </div>
        <div class="card-body text-center">
            <div class="mb-3">
                <img src="<?php echo htmlspecialchars($userProfile['avatar_path'] ?? '/assets/images/admin.png'); ?>" 
                     alt="Avatar" class="rounded-circle profile-avatar" style="width: 150px; height: 150px; object-fit: cover;">
            </div>
            <p><strong>Username:</strong> <?php echo htmlspecialchars($userProfile['username']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($userProfile['email']); ?></p>
            <p><strong>Role:</strong> <?php echo htmlspecialchars(ucfirst($currentUser['role'])); ?></p>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-header">
            <h3><i class="fas fa-edit"></i> Edit Profile</h3>
        </div>
        <div class="card-body">
            <form action="/controllers/profile_action.php" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?php echo htmlspecialchars($userProfile['username']); ?>" 
                           required pattern="[a-zA-Z0-9]{4,20}" title="Username should contain only letters and numbers, 4-20 characters">
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($userProfile['email']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="avatar" class="form-label">Avatar (JPG, PNG, max 2MB)</label>
                    <input type="file" class="form-control" id="avatar" name="avatar" accept=".jpg,.jpeg,.png">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">New Password (leave blank to keep current)</label>
                    <input type="password" class="form-control" id="password" name="password" 
                           minlength="6" placeholder="Enter new password">
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                           minlength="6" placeholder="Confirm new password">
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php
include_once '../includes/footer.php';
?>