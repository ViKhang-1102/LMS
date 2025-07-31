<?php
/*
include_once '../includes/auth.php';

protectPage();
include_once '../config/db.php';
include_once '../includes/header.php';

$course_id = $_GET['course_id'] ?? 0;
$currentUser = getCurrentUser($pdo);

try {
    $stmt = $pdo->prepare("SELECT title FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching course: " . $e->getMessage());
    $course = null;
}

try {
    $stmt = $pdo->prepare("
        SELECT ft.id, ft.title, ft.content, ft.created_at, u.username 
        FROM forum_threads ft 
        JOIN users u ON ft.user_id = u.id 
        WHERE ft.course_id = ?
        ORDER BY ft.created_at DESC
    ");
    $stmt->execute([$course_id]);
    $threads = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching threads: " . $e->getMessage());
    $threads = [];
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>

<div class="container mt-5">
    <h1 class="mb-4"><i class="fas fa-comments"></i> Forum: <?php echo htmlspecialchars($course['title'] ?? 'Course Not Found'); ?></h1>

    <?php if ($success === 'thread_created'): ?>
        <div class="alert alert-success">
            Thread created successfully!
        </div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="card mb-4 shadow-sm">
        <div class="card-header">
            <h3>Create New Thread</h3>
        </div>
        <div class="card-body">
            <form action="/controllers/forum_action.php" method="POST" id="threadForm">
                <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                <div class="mb-3">
                    <label for="thread_title" class="form-label"><i class="fas fa-heading"></i> Thread Title</label>
                    <input type="text" class="form-control" id="thread_title" name="thread_title" required>
                </div>
                <div class="mb-3">
                    <label for="thread_content" class="form-label"><i class="fas fa-comment"></i> Content</label>
                    <textarea class="form-control" id="thread_content" name="thread_content" rows="4" required></textarea>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Create Thread</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($threads)): ?>
        <div class="alert alert-info">
            No threads yet. Be the first to start a discussion!
        </div>
    <?php else: ?>
        <?php foreach ($threads as $thread): ?>
            <div class="card mb-3 shadow-sm">
                <div class="card-header">
                    <h4><?php echo htmlspecialchars($thread['title']); ?></h4>
                    <small>Posted by <?php echo htmlspecialchars($thread['username']); ?> on <?php echo date('d/m/Y H:i', strtotime($thread['created_at'])); ?></small>
                </div>
                <div class="card-body">
                    <p><?php echo htmlspecialchars($thread['content']); ?></p>
                    <a href="/pages/forum_thread.php?thread_id=<?php echo $thread['id']; ?>&course_id=<?php echo $course_id; ?>" class="btn btn-outline-primary btn-sm">View Thread</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
include_once '../includes/footer.php';
?>
*/
