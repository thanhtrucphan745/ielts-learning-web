<?php
require_once __DIR__ . '/_layout.php';

if (!function_exists('admin_ensure_feedback_reply_columns')) {
    function admin_ensure_feedback_reply_columns(mysqli $conn): void
    {
        $result = $conn->query("SHOW COLUMNS FROM contact_messages LIKE 'reply_message'");
        if ($result && $result->num_rows === 0) {
            $conn->query("ALTER TABLE contact_messages ADD COLUMN reply_message LONGTEXT NULL AFTER is_read");
        }

        $result = $conn->query("SHOW COLUMNS FROM contact_messages LIKE 'replied_at'");
        if ($result && $result->num_rows === 0) {
            $conn->query("ALTER TABLE contact_messages ADD COLUMN replied_at DATETIME NULL AFTER reply_message");
        }
    }
}

admin_ensure_feedback_status_column($conn);
admin_ensure_feedback_reply_columns($conn);

$message = '';
$statusFilter = trim((string) ($_GET['status'] ?? 'all'));
$replyId = (int) ($_GET['reply'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_feedback'])) {
    $feedbackId = (int) ($_POST['feedback_id'] ?? 0);
    $replyMessage = trim((string) ($_POST['reply_message'] ?? ''));

    if ($feedbackId <= 0 || $replyMessage === '') {
        $message = 'Vui lòng nhập nội dung trả lời.';
    } else {
        $stmt = $conn->prepare('UPDATE contact_messages SET reply_message = ?, replied_at = NOW(), is_read = 1 WHERE id = ?');
        if ($stmt) {
            $stmt->bind_param('si', $replyMessage, $feedbackId);
            $stmt->execute();
            $stmt->close();
            $message = 'Đã lưu câu trả lời.';
            $replyId = 0;
        }
    }
}

if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    if ($deleteId > 0) {
        $stmt = $conn->prepare('DELETE FROM contact_messages WHERE id = ?');
        if ($stmt) {
            $stmt->bind_param('i', $deleteId);
            $stmt->execute();
            $stmt->close();
            $message = 'Đã xóa phản hồi.';
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
            $message = 'Đã cập nhật trạng thái phản hồi.';
        }
    }
}

$feedbackItems = [];
$sql = 'SELECT id, name, email, subject, message, COALESCE(is_read,0) AS is_read, reply_message, replied_at, created_at FROM contact_messages';
if (in_array($statusFilter, ['read', 'unread'], true)) {
    $sql .= $statusFilter === 'read' ? ' WHERE COALESCE(is_read,0) = 1' : ' WHERE COALESCE(is_read,0) = 0';
}
$sql .= ' ORDER BY id DESC LIMIT 100';
$result = $conn->query($sql);
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

admin_render_header('Phản hồi', 'feedback', 'Xem và xử lý tin nhắn liên hệ');
if ($message):
?>
<div class="alert alert-info"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-6 mb-3"><div class="card border-left-primary shadow h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Tổng tin nhắn</div><div class="h4 mb-0 font-weight-bold text-gray-800"><?php echo (int) ($counts['total'] ?? 0); ?></div></div></div></div>
    <div class="col-md-6 mb-3"><div class="card border-left-warning shadow h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Chưa đọc</div><div class="h4 mb-0 font-weight-bold text-gray-800"><?php echo (int) ($counts['unread'] ?? 0); ?></div></div></div></div>
</div>

<div class="card content-card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center"><h6 class="m-0 font-weight-bold text-primary">Danh sách phản hồi</h6><form class="form-inline" method="get"><select name="status" class="form-control form-control-sm mr-2"><option value="all"<?php echo $statusFilter === 'all' ? ' selected' : ''; ?>>Tất cả</option><option value="unread"<?php echo $statusFilter === 'unread' ? ' selected' : ''; ?>>Chưa đọc</option><option value="read"<?php echo $statusFilter === 'read' ? ' selected' : ''; ?>>Đã đọc</option></select><button class="btn btn-sm btn-primary">Lọc</button></form></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered align-middle" width="100%" cellspacing="0">
                <thead><tr><th>Người gửi</th><th>Chủ đề</th><th>Nội dung</th><th>Trạng thái</th><th>Ngày gửi</th><th>Thao tác</th></tr></thead>
                <tbody>
                    <?php if (!$feedbackItems): ?>
                        <tr><td colspan="6" class="text-center text-muted">Chưa có phản hồi nào.</td></tr>
                    <?php else: ?>
                        <?php foreach ($feedbackItems as $feedback): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($feedback['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <div class="small text-muted"><?php echo htmlspecialchars($feedback['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($feedback['subject'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td style="max-width: 320px;">
                                    <?php echo nl2br(htmlspecialchars($feedback['message'] ?? '', ENT_QUOTES, 'UTF-8')); ?>
                                    <?php if (!empty($feedback['reply_message'])): ?>
                                        <div class="mt-3 p-3 bg-light border rounded">
                                            <div class="small text-muted mb-1">Câu trả lời của quản trị viên<?php if (!empty($feedback['replied_at'])): ?> - <?php echo htmlspecialchars($feedback['replied_at'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?></div>
                                            <div><?php echo nl2br(htmlspecialchars($feedback['reply_message'], ENT_QUOTES, 'UTF-8')); ?></div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-<?php echo ((int) ($feedback['is_read'] ?? 0) === 1) ? 'success' : 'warning'; ?>"><?php echo ((int) ($feedback['is_read'] ?? 0) === 1) ? 'Đã đọc' : 'Chưa đọc'; ?></span></td>
                                <td><?php echo htmlspecialchars($feedback['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <a class="btn btn-sm btn-primary mb-1" href="feedback.php?reply=<?php echo (int) $feedback['id']; ?>#reply-form">Trả lời</a>
                                    <a class="btn btn-sm btn-secondary mb-1" href="feedback.php?toggle_read=<?php echo (int) $feedback['id']; ?>"><?php echo ((int) ($feedback['is_read'] ?? 0) === 1) ? 'Chưa đọc' : 'Đã đọc'; ?></a>
                                    <a class="btn btn-sm btn-danger mb-1" href="feedback.php?delete=<?php echo (int) $feedback['id']; ?>" onclick="return confirm('Xóa tin nhắn này?');">Xóa</a>
                                </td>
                            </tr>
                            <?php if ($replyId === (int) $feedback['id']): ?>
                                <tr id="reply-form">
                                    <td colspan="6">
                                        <form method="post" class="p-3 border rounded bg-light">
                                            <input type="hidden" name="reply_feedback" value="1">
                                            <input type="hidden" name="feedback_id" value="<?php echo (int) $feedback['id']; ?>">
                                            <div class="form-group mb-2">
                                                <label class="font-weight-bold">Trả lời cho <?php echo htmlspecialchars($feedback['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></label>
                                                <textarea name="reply_message" rows="5" class="form-control" placeholder="Nhập nội dung trả lời..." required><?php echo htmlspecialchars((string) ($feedback['reply_message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                            </div>
                                            <button class="btn btn-primary">Lưu trả lời</button>
                                            <a class="btn btn-link" href="feedback.php">Hủy</a>
                                        </form>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php admin_render_footer(); ?>
