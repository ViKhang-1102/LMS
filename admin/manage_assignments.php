<?php
include_once '../includes/auth.php';
protectPage(['admin']);

include_once '../config/db.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['assignment_id'])) {
        $assignmentId = intval($_POST['assignment_id']);
        try {
            $stmt = $pdo->prepare("DELETE FROM assignments WHERE id = ?");
            $stmt->execute([$assignmentId]);
            if ($stmt->rowCount() === 0) {
                header('Location: manage_assignments.php?error=delete_failed_no_assignment');
                exit;
            }
            header('Location: manage_assignments.php?success=deleted');
            exit;
        } catch (PDOException $e) {
            error_log("Error deleting assignment: " . $e->getMessage());
            header('Location: manage_assignments.php?error=delete_failed&message=' . urlencode($e->getMessage()));
            exit;
        }
    }

    $stmt = $pdo->prepare("
        SELECT a.id, a.title, c.title AS course_title, a.due_date, a.created_at
        FROM assignments a
        LEFT JOIN courses c ON a.course_id = c.id
        ORDER BY a.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recentAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching recent assignments: " . $e->getMessage());
    $recentAssignments = [];
}

include_once '../includes/header.php';
?>

<h2 class="mb-4">Manage Assignments</h2>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger" role="alert">
        <?php
        if ($_GET['error'] == 'delete_failed') {
            echo 'Failed to delete assignment: ' . (isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'Unknown error');
        } elseif ($_GET['error'] == 'delete_failed_no_assignment') {
            echo 'Cannot delete assignment: Assignment does not exist.';
        } else {
            echo 'An error occurred, please try again!';
        }
        ?>
    </div>
<?php elseif (isset($_GET['success'])): ?>
    <div class="alert alert-success" role="alert">
        <?php
        if ($_GET['success'] == 'deleted') {
            echo 'Assignment has been deleted!';
        }
        ?>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <h3><i class="fas fa-tasks"></i> Recent Assignments</h3>
    </div>
    <div class="card-body">
        <?php if (empty($recentAssignments)): ?>
            <p>No assignments found.</p>
        <?php else: ?>
            <table class="table table-striped align-middle text-center">
                <thead>
                    <tr>
                        <th style="width: 30%; text-align: left;">Title</th>
                        <th style="width: 20%;">Course</th>
                        <th style="width: 15%;">Due Date</th>
                        <th style="width: 20%;">Created At</th>
                        <th style="width: 15%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentAssignments as $assignment): ?>
                        <tr>
                            <td style="text-align: left;"><?php echo htmlspecialchars($assignment['title']); ?></td>
                            <td><?php echo htmlspecialchars($assignment['course_title'] ?? 'Unknown Course'); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($assignment['due_date'])); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($assignment['created_at'])); ?></td>
                            <td>
                                <form action="manage_assignments.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete this assignment?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
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