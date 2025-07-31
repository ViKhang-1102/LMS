<?php
/*
include_once '../includes/auth.php';
protectPage(['admin']); 

include_once '../config/db.php';

$currentUser = getCurrentUser($pdo);

try {
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.email, u.role, 
               p.module, p.can_view, p.can_edit, p.can_delete 
        FROM users u 
        LEFT JOIN permissions p ON u.id = p.user_id 
        ORDER BY u.username ASC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_GROUP); 
} catch (PDOException $e) {
    error_log("Error fetching users and permissions: " . $e->getMessage());
    $users = [];
}

$editUser = null;
$editPermissions = [];
if (isset($_GET['edit_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id = ?");
        $stmt->execute([$_GET['edit_id']]);
        $editUser = $stmt->fetch();
        
        $stmt = $pdo->prepare("SELECT module, can_view, can_edit, can_delete FROM permissions WHERE user_id = ?");
        $stmt->execute([$_GET['edit_id']]);
        $editPermissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching user for edit: " . $e->getMessage());
    }
}

$modules = ['courses', 'assignments', 'notifications', 'users'];

include_once '../includes/header.php';
?>

<h2 class="mb-4">Permission Management</h2>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger" role="alert">
        <?php
        if ($_GET['error'] == 'update_failed') {
            echo 'Failed to update permissions!';
        } else {
            echo 'An error occurred, please try again!';
        }
        ?>
    </div>
<?php elseif (isset($_GET['success'])): ?>
    <div class="alert alert-success" role="alert">
        Permissions updated successfully!
    </div>
<?php endif; ?>

<?php if ($editUser): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h3><i class="fas fa-user-shield"></i> Edit Permissions: <?php echo htmlspecialchars($editUser['username']); ?></h3>
        </div>
        <div class="card-body">
            <form action="/controllers/permission_action.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="user_id" value="<?php echo $editUser['id']; ?>">
                <div class="mb-3">
                    <label class="form-label">Role: <?php echo $editUser['role'] === 'admin' ? 'Administrator' : 'Student'; ?></label>
                </div>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Module</th>
                            <th>View</th>
                            <th>Edit</th>
                            <th>Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modules as $module): ?>
                            <?php
                            $perm = array_filter($editPermissions, function($p) use ($module) { return $p['module'] === $module; });
                            $perm = reset($perm) ?: ['can_view' => 0, 'can_edit' => 0, 'can_delete' => 0];
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars(ucfirst($module)); ?></td>
                                <td>
                                    <input type="checkbox" name="permissions[<?php echo $module; ?>][can_view]" value="1" <?php echo $perm['can_view'] ? 'checked' : ''; ?>>
                                </td>
                                <td>
                                    <input type="checkbox" name="permissions[<?php echo $module; ?>][can_edit]" value="1" <?php echo $perm['can_edit'] ? 'checked' : ''; ?>>
                                </td>
                                <td>
                                    <input type="checkbox" name="permissions[<?php echo $module; ?>][can_delete]" value="1" <?php echo $perm['can_delete'] ? 'checked' : ''; ?>>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Permissions</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <h3><i class="fas fa-users-cog"></i> User List and Permissions</h3>
    </div>
    <div class="card-body">
        <?php if (empty($users)): ?>
            <p>No users found.</p>
        <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Permissions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user_id => $user_data): ?>
                        <?php $user = reset($user_data); ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo $user['role'] === 'admin' ? 'Administrator' : 'Student'; ?></td>
                            <td>
                                <?php
                                $permissions = [];
                                foreach ($user_data as $perm) {
                                    if ($perm['module']) {
                                        $perm_str = $perm['module'] . ': ' . 
                                            ($perm['can_view'] ? 'View ' : '') . 
                                            ($perm['can_edit'] ? 'Edit ' : '') . 
                                            ($perm['can_delete'] ? 'Delete' : '');
                                        $permissions[] = $perm_str;
                                    }
                                }
                                echo htmlspecialchars(implode(', ', $permissions) ?: 'No permissions');
                                ?>
                            </td>
                            <td>
                                <a href="/admin/permissions.php?edit_id=<?php echo $user_id; ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php
include_once '../includes/footer.php';
?>
*/
