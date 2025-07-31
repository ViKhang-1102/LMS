<?php
/*
include_once '../includes/auth.php';
protectPage(['admin']); 

include_once '../config/db.php';

$currentUser = getCurrentUser($pdo);

try {
    $stmt = $pdo->prepare("SELECT id, username FROM users ORDER BY username ASC");
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching users: " . $e->getMessage());
    $users = [];
}

include_once '../includes/header.php';
?>

<h2 class="mb-4">Send Notification</h2>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger" role="alert">
        <?php
        if ($_GET['error'] == 'send_failed') {
            echo 'Failed to send notification!';
        } elseif ($_GET['error'] == 'invalid_recipient') {
            echo 'Please choose a valid recipient!';
        } else {
            echo 'An error occurred, please try again!';
        }
        ?>
    </div>
<?php elseif (isset($_GET['success'])): ?>
    <div class="alert alert-success" role="alert">
        Notification sent successfully!
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <h3><i class="fas fa-bell"></i> Send New Notification</h3>
    </div>
    <div class="card-body">
        <form action="/controllers/notification_action.php" method="POST">
            <input type="hidden" name="action" value="send">
            <div class="mb-3">
                <label for="recipient_type" class="form-label">Send to</label>
                <select class="form-control" id="recipient_type" name="recipient_type" onchange="toggleRecipientOptions()" required>
                    <option value="">Select recipient type</option>
                    <option value="all">All Users</option>
                    <option value="role">By Role</option>
                    <option value="user">Specific User</option>
                </select>
            </div>
            <div class="mb-3" id="role_options" style="display: none;">
                <label for="role" class="form-label">Role</label>
                <select class="form-control" id="role" name="role">
                    <option value="user">Student</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
            <div class="mb-3" id="user_options" style="display: none;">
                <label for="user_id" class="form-label">Select User</label>
                <select class="form-control" id="user_id" name="user_id">
                    <option value="">Select a user</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="title" class="form-label">Notification Title</label>
                <input type="text" class="form-control" id="title" name="title" required>
            </div>
            <div class="mb-3">
                <label for="content" class="form-label">Notification Content</label>
                <textarea class="form-control" id="content" name="content" rows="5" required></textarea>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send Notification</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleRecipientOptions() {
    const recipientType = document.getElementById('recipient_type').value;
    document.getElementById('role_options').style.display = recipientType === 'role' ? 'block' : 'none';
    document.getElementById('user_options').style.display = recipientType === 'user' ? 'block' : 'none';
}
</script>

<?php
include_once '../includes/footer.php';
?>
*/