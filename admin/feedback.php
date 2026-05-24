<?php
require_once __DIR__ . '/_layout.php';

admin_ensure_feedback_status_column($conn);

$message = '';

if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    if ($deleteId > 0) {
        $stmt = $conn->prepare('DELETE FROM contact_messages WHERE id = ?');
        if ($stmt) {
            $stmt->bind_param('i', $deleteId);
            $stmt->execute();
            $stmt->close();
            $message = 'Feedback deleted.';
        }
    }
}

if (isset($_GET['toggle_read'])) {
    $toggleId = (int) $_GET['toggle_read'];
    if ($toggleId > 0) {
        $stmt = $conn->prepare('UPDATE contact_messages SET is_read = IF(COALESCE(is_read,0)=1,0,1) WHERE id = ?');
        if ($stmt) {
            $stmt->bind_param('i', $toggleId);
            $stmt->execute();
            $stmt->close();
            $message = 'Feedback status updated.';
        }
    }
}

$feedbackItems = [];
$result = $conn->query('SELECT id, name, email, subject, message, COALESCE(is_read,0) AS is_read, created_at FROM contact_messages ORDER BY id DESC LIMIT 100');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $feedbackItems[] = $row;
    }
}

$counts = ['total' => 0, 'unread' => 0];
$result = $conn->query('SELECT COUNT(*) AS total, SUM(CASE WHEN COALESCE(is_read,0)=0 THEN 1 ELSE 0 END) AS unread FROM contact_messages');
if ($result) {
    $counts = $result->fetch_assoc() ?: $counts;
}

admin_render_header('Feedback Management', 'feedback', 'Review and handle contact messages');
if ($message):
?>
<div class="alert alert-info"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-6 mb-3"><div class="card border-left-primary shadow h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total messages</div><div class="h4 mb-0 font-weight-bold text-gray-800"><?php echo (int) ($counts['total'] ?? 0); ?></div></div></div></div>
    <div class="col-md-6 mb-3"><div class="card border-left-warning shadow h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Unread</div><div class="h4 mb-0 font-weight-bold text-gray-800"><?php echo (int) ($counts['unread'] ?? 0); ?></div></div></div></div>
</div>

<div class="card content-card shadow mb-4">
    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Contact messages</h6></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered align-middle" width="100%" cellspacing="0">
                <thead><tr><th>Sender</th><th>Subject</th><th>Message</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if (!$feedbackItems): ?>
                        <tr><td colspan="6" class="text-center text-muted">No feedback yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($feedbackItems as $feedback): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($feedback['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <div class="small text-muted"><?php echo htmlspecialchars($feedback['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($feedback['subject'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td style="max-width: 320px;"><?php echo nl2br(htmlspecialchars($feedback['message'] ?? '', ENT_QUOTES, 'UTF-8')); ?></td>
                                <td><span class="badge badge-<?php echo ((int) ($feedback['is_read'] ?? 0) === 1) ? 'success' : 'warning'; ?>"><?php echo ((int) ($feedback['is_read'] ?? 0) === 1) ? 'Read' : 'Unread'; ?></span></td>
                                <td><?php echo htmlspecialchars($feedback['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <a class="btn btn-sm btn-secondary" href="feedback.php?toggle_read=<?php echo (int) $feedback['id']; ?>"><?php echo ((int) ($feedback['is_read'] ?? 0) === 1) ? 'Mark unread' : 'Mark read'; ?></a>
                                    <a class="btn btn-sm btn-danger" href="feedback.php?delete=<?php echo (int) $feedback['id']; ?>" onclick="return confirm('Delete this message?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php admin_render_footer(); ?>
