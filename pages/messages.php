<?php
include_once '../includes/auth.php';
protectPage();

include_once '../config/db.php';
$currentUser = getCurrentUser($pdo);

$stmt = $pdo->prepare("SELECT id, username FROM users WHERE id != ?");
$stmt->execute([$currentUser['id']]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$filterUserId = isset($_GET['filter_user']) ? intval($_GET['filter_user']) : 0;
$filterCondition = '';
$filterParams = [];

if ($filterUserId > 0) {
    $filterCondition = "AND ((sender_id = :me AND recipient_id = :them) OR (sender_id = :them AND recipient_id = :me))";
    $filterParams = ['me' => $currentUser['id'], 'them' => $filterUserId];
}

$limit = 10;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$countSql = "
    SELECT COUNT(*) FROM messages
    WHERE (sender_id = :me OR recipient_id = :me)
    $filterCondition
";
$stmt = $pdo->prepare($countSql);
$stmt->execute(array_merge(['me' => $currentUser['id']], $filterParams));
$totalMessages = $stmt->fetchColumn();
$totalPages = ceil($totalMessages / $limit);

$sql = "
    SELECT m.*, u.username AS sender_name
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE (m.sender_id = :me OR m.recipient_id = :me)
    $filterCondition
    ORDER BY m.created_at DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute(array_merge(['me' => $currentUser['id']], $filterParams));
$messages = $stmt->fetchAll();
?>

<?php include_once '../includes/header.php'; ?>
<div class="container mt-4">
    <h2>Messages</h2>

    <form action="/controllers/message_action.php" method="POST" class="card p-3 mb-4" enctype="multipart/form-data">
        <input type="hidden" name="action" value="send">
        <div class="mb-3">
            <label for="recipient_id" class="form-label">Recipient</label>
            <select name="recipient_id" id="recipient_id" class="form-control" required>
                <option value="">-- Select user --</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="message" class="form-label">Message</label>
            <textarea name="message" id="message" rows="3" class="form-control"></textarea>
        </div>
        <div class="mb-3">
            <label for="image" class="form-label">Attach Image (optional)</label>
            <input type="file" name="image" id="image" class="form-control" accept="image/*">
        </div>
        <button type="submit" class="btn btn-primary">Send Message</button>
    </form>

    <form method="GET" class="mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-6">
                <label for="filter_user" class="form-label">Filter by User</label>
                <select name="filter_user" id="filter_user" class="form-control">
                    <option value="0">-- All users --</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id'] ?>" <?= ($user['id'] == $filterUserId) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['username']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
            </div>
        </div>
    </form>

    <div class="list-group">
        <?php if (empty($messages)): ?>
            <div class="list-group-item">No messages found.</div>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
                <div class="list-group-item">
                    <strong><?= htmlspecialchars($msg['sender_name']) ?>:</strong>
                    <?= nl2br(htmlspecialchars($msg['content'])) ?>
                    <small class="text-muted d-block">Sent at: <?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?></small>

                    <?php if (!empty($msg['image_path'])): ?>
                        <div class="mt-2">
                            <img src="/<?= htmlspecialchars($msg['image_path']) ?>" alt="Attached Image" class="img-thumbnail mt-2" style="max-width: 200px; height: auto;">

                        </div>
                    <?php endif; ?>

                    <?php if ($msg['sender_id'] === $currentUser['id'] && (time() - strtotime($msg['created_at']) <= 3600)): ?>
                        <form method="POST" action="/controllers/message_action.php" onsubmit="return confirm('Delete this message?');" class="mt-2">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page - 1 ?>&filter_user=<?= $filterUserId ?>">Trang trước</a>
            </li>
            <li class="page-item disabled">
                <span class="page-link">Trang <?= $page ?> / <?= $totalPages ?></span>
            </li>
            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page + 1 ?>&filter_user=<?= $filterUserId ?>">Trang sau</a>
            </li>
        </ul>
    </nav>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php include_once '../includes/footer.php'; ?>
