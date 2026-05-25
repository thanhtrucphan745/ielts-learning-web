<?php
require_once __DIR__ . '/_layout.php';

$message = '';
$replyId = (int) ($_GET['reply'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_reply'])) {
    $questionId = (int) ($_POST['question_id'] ?? 0);
    $answerContent = trim((string) ($_POST['answer_content'] ?? ''));

    if ($questionId <= 0 || $answerContent === '') {
        $message = 'Vui lòng nhập nội dung trả lời.';
    } else {
        $stmt = $conn->prepare("UPDATE student_questions SET answer_content = ?, status = 'answered', answered_at = NOW() WHERE id = ? AND teacher_id = ?");
        if ($stmt) {
            $stmt->bind_param('sii', $answerContent, $questionId, $teacherCurrentUserId);
            $stmt->execute();
            $affectedRows = $stmt->affected_rows;
            $stmt->close();

            if ($affectedRows > 0) {
                $message = 'Đã lưu câu trả lời.';
                $replyId = 0;
            } else {
                $message = 'Không thể cập nhật thắc mắc này.';
            }
        }
    }
}

$statusFilter = trim((string) ($_GET['status'] ?? 'all'));
$questions = [];

$sql = 'SELECT q.id, q.student_id, q.teacher_id, q.title, q.question_content, q.answer_content, q.status, q.created_at, q.answered_at, u.name AS student_name, u.username AS student_username FROM student_questions q INNER JOIN users u ON u.id = q.student_id INNER JOIN teacher_students ts ON ts.student_id = q.student_id AND ts.teacher_id = q.teacher_id WHERE q.teacher_id = ?';
if (in_array($statusFilter, ['pending', 'answered'], true)) {
    $sql .= ' AND q.status = ?';
}
$sql .= ' ORDER BY q.created_at DESC, q.id DESC';

$stmt = $conn->prepare($sql);
if ($stmt) {
    if (in_array($statusFilter, ['pending', 'answered'], true)) {
        $stmt->bind_param('is', $teacherCurrentUserId, $statusFilter);
    } else {
        $stmt->bind_param('i', $teacherCurrentUserId);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $questions[] = $row;
        }
    }
    $stmt->close();
}

$pendingCount = 0;
$answeredCount = 0;
$stmt = $conn->prepare("SELECT SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_total, SUM(CASE WHEN status = 'answered' THEN 1 ELSE 0 END) AS answered_total FROM student_questions WHERE teacher_id = ?");
if ($stmt) {
    $stmt->bind_param('i', $teacherCurrentUserId);
    $stmt->execute();
    $row = $stmt->get_result()?->fetch_assoc() ?: [];
    $pendingCount = (int) ($row['pending_total'] ?? 0);
    $answeredCount = (int) ($row['answered_total'] ?? 0);
    $stmt->close();
}

teacher_render_header('Thắc mắc', 'Xử lý câu hỏi của học sinh được gán', 'questions');
?>

<?php if ($message !== ''): ?>
    <div class="alert alert-info teacher-card mb-4"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<div class="teacher-grid mb-4">
    <div class="teacher-col-6"><div class="teacher-card teacher-stat h-100"><div class="label">Thắc mắc chờ trả lời</div><div class="value"><?php echo (int) $pendingCount; ?></div></div></div>
    <div class="teacher-col-6"><div class="teacher-card teacher-stat h-100"><div class="label">Thắc mắc đã trả lời</div><div class="value"><?php echo (int) $answeredCount; ?></div></div></div>
</div>

<div class="teacher-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h3 class="teacher-section-title mb-0">Danh sách thắc mắc</h3>
        <form method="get" class="d-flex gap-2 flex-wrap">
            <select name="status" class="form-select form-select-sm">
                <option value="all"<?php echo $statusFilter === 'all' ? ' selected' : ''; ?>>Tất cả</option>
                <option value="pending"<?php echo $statusFilter === 'pending' ? ' selected' : ''; ?>>Chờ trả lời</option>
                <option value="answered"<?php echo $statusFilter === 'answered' ? ' selected' : ''; ?>>Đã trả lời</option>
            </select>
            <button class="btn btn-sm btn-outline-primary" type="submit">Lọc</button>
        </form>
    </div>

    <?php if (!$questions): ?>
        <div class="teacher-empty">Chưa có thắc mắc nào phù hợp bộ lọc.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table teacher-table align-middle">
                <thead>
                    <tr>
                        <th>Học sinh</th>
                        <th>Tiêu đề câu hỏi</th>
                        <th>Trạng thái</th>
                        <th>Ngày gửi</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($questions as $question): ?>
                        <?php [$statusLabel, $statusClass] = teacher_status_meta((string) ($question['status'] ?? 'pending')); ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?php echo htmlspecialchars((string) ($question['student_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="teacher-muted small"><?php echo htmlspecialchars((string) ($question['student_username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars((string) ($question['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><span class="teacher-chip <?php echo htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><?php echo htmlspecialchars((string) ($question['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <a href="?reply=<?php echo (int) $question['id']; ?>" class="btn btn-sm btn-outline-primary"><?php echo ((string) ($question['status'] ?? '') === 'answered') ? 'Xem' : 'Trả lời'; ?></a>
                            </td>
                        </tr>
                        <?php if ($replyId === (int) $question['id']): ?>
                            <tr>
                                <td colspan="5">
                                    <div class="p-3 border rounded-4 bg-light">
                                        <div class="row g-3">
                                            <div class="col-lg-6">
                                                <div class="teacher-muted small mb-1">Nội dung câu hỏi</div>
                                                <div class="p-3 bg-white border rounded-3"><?php echo nl2br(htmlspecialchars((string) ($question['question_content'] ?? ''), ENT_QUOTES, 'UTF-8')); ?></div>
                                            </div>
                                            <div class="col-lg-6">
                                                <form method="post">
                                                    <input type="hidden" name="save_reply" value="1">
                                                    <input type="hidden" name="question_id" value="<?php echo (int) $question['id']; ?>">
                                                    <div class="teacher-muted small mb-1">Trả lời</div>
                                                    <textarea name="answer_content" rows="6" class="form-control" placeholder="Nhập phản hồi cho học sinh..." required><?php echo htmlspecialchars((string) ($question['answer_content'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                                    <div class="d-flex gap-2 mt-3 flex-wrap">
                                                        <button class="btn btn-primary" type="submit">Lưu trả lời</button>
                                                        <a href="questions.php" class="btn btn-outline-secondary">Hủy</a>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php teacher_render_footer(); ?>