<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

if (!function_exists('auth_ensure_reply_notification_columns')) {
    function auth_ensure_reply_notification_columns(mysqli $conn): void
    {
        $result = $conn->query("SHOW COLUMNS FROM contact_messages LIKE 'user_seen_at'");
        if ($result && $result->num_rows === 0) {
            $conn->query("ALTER TABLE contact_messages ADD COLUMN user_seen_at DATETIME NULL AFTER replied_at");
        }
    }
}

if (!function_exists('auth_mark_contact_replies_seen')) {
    function auth_mark_contact_replies_seen(mysqli $conn, string $email): void
    {
        auth_ensure_reply_notification_columns($conn);

        $stmt = $conn->prepare('UPDATE contact_messages SET user_seen_at = NOW() WHERE email = ? AND reply_message IS NOT NULL AND user_seen_at IS NULL');
        if (!$stmt) {
            return;
        }

        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->close();
    }
}

auth_require_login();
auth_ensure_reply_notification_columns($conn);

$currentUser = auth_user() ?? [];
$email = (string) ($currentUser['email'] ?? '');

if ($email !== '') {
    auth_mark_contact_replies_seen($conn, $email);
}

$items = [];
if ($email !== '') {
    $stmt = $conn->prepare('SELECT id, name, email, subject, message, reply_message, replied_at, created_at FROM contact_messages WHERE email = ? AND reply_message IS NOT NULL ORDER BY COALESCE(replied_at, created_at) DESC');
    if ($stmt) {
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Tin nhắn quản trị</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/nav.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="bg-white rounded shadow-sm p-4 p-md-5">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-1">Tin nhắn từ quản trị viên</h1>
                        <p class="text-muted mb-0">Các phản hồi được gửi tới email tài khoản của bạn.</p>
                    </div>
                    <a href="contact.php" class="btn btn-outline-primary">Gửi liên hệ mới</a>
                </div>

                <?php if (!$items): ?>
                    <div class="alert alert-light border mb-0">Chưa có tin nhắn nào từ quản trị viên.</div>
                <?php else: ?>
                    <div class="accordion" id="replyAccordion">
                        <?php foreach ($items as $index => $item): ?>
                            <div class="accordion-item mb-3 border rounded" id="reply-<?php echo (int) $item['id']; ?>">
                                <h2 class="accordion-header" id="heading-<?php echo (int) $item['id']; ?>">
                                    <button class="accordion-button <?php echo $index === 0 ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo (int) $item['id']; ?>" aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" aria-controls="collapse-<?php echo (int) $item['id']; ?>">
                                        <?php echo htmlspecialchars($item['subject'] ?? 'Không có tiêu đề', ENT_QUOTES, 'UTF-8'); ?>
                                    </button>
                                </h2>
                                <div id="collapse-<?php echo (int) $item['id']; ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" aria-labelledby="heading-<?php echo (int) $item['id']; ?>" data-bs-parent="#replyAccordion">
                                    <div class="accordion-body">
                                        <div class="mb-3">
                                            <div class="small text-muted mb-1">Nội dung bạn đã gửi</div>
                                            <div class="p-3 bg-light rounded border"><?php echo nl2br(htmlspecialchars($item['message'] ?? '', ENT_QUOTES, 'UTF-8')); ?></div>
                                        </div>
                                        <div>
                                            <div class="small text-muted mb-1">Phản hồi của quản trị viên</div>
                                            <div class="p-3 bg-primary bg-opacity-10 rounded border border-primary">
                                                <?php echo nl2br(htmlspecialchars($item['reply_message'] ?? '', ENT_QUOTES, 'UTF-8')); ?>
                                            </div>
                                            <?php if (!empty($item['replied_at'])): ?>
                                                <div class="small text-muted mt-2">Thời gian phản hồi: <?php echo htmlspecialchars($item['replied_at'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>