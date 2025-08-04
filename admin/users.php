<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('error_log', 'C:/xampp/htdocs/Projects/LMS/logs/php_errors.log');

include_once '../includes/auth.php';
protectPage(['admin']);
include_once '../config/db.php';

try {
    $pdo->exec("SET time_zone = '+07:00'");
} catch (PDOException $e) {
    error_log("Error setting timezone: " . $e->getMessage());
    die("Failed to set timezone. Please check database configuration.");
}

if (!$pdo) {
    error_log("PDO connection is not initialized.");
    die("Database connection failed. Please check db.php configuration.");
}

$currentUser = getCurrentUser($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['user_id'])) {
    $userId = intval($_POST['user_id']);
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        if ($stmt->rowCount() === 0) {
            header('Location: users.php?error=delete_failed_no_user');
            exit;
        }
        header('Location: users.php?success=deleted');
        exit;
    } catch (PDOException $e) {
        error_log("Error deleting user: " . $e->getMessage());
        header('Location: users.php?error=delete_failed&message=' . urlencode($e->getMessage()));
        exit;
    }
}

try {
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.email, u.role, u.created_at, u.avatar_path
        FROM users u
        WHERE u.role IN ('student', 'instructor')
        ORDER BY u.role ASC, u.username ASC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching users: " . $e->getMessage());
    $users = [];
}

include_once '../includes/header.php';
?>

<h2 class="mb-4">User Management</h2>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger" role="alert">
        <?php
        if ($_GET['error'] == 'delete_failed') {
            echo 'Failed to delete user: ' . (isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'Unknown error');
        } elseif ($_GET['error'] == 'delete_failed_no_user') {
            echo 'Cannot delete user: User does not exist.';
        } else {
            echo 'An error occurred, please try again!';
        }
        ?>
    </div>
<?php elseif (isset($_GET['success'])): ?>
    <div class="alert alert-success" role="alert">
        <?php
        if ($_GET['success'] == 'deleted') {
            echo 'User has been deleted!';
        }
        ?>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <h3><i class="fas fa-users"></i> User List (Students & Instructors)</h3>
    </div>
    <div class="card-body">
        <?php if (empty($users)): ?>
            <p>No students or instructors found.</p>
        <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Avatar</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                            <td>
                                <img src="<?php echo htmlspecialchars($user['avatar_path'] ?? '/assets/images/default_avatar.jpg'); ?>" 
                                     alt="Avatar" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                            <td>
                                <form action="users.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete this user?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php include_once '../includes/footer.php'; ?>