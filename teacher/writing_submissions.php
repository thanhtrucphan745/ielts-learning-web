<?php
require_once __DIR__ . '/_layout.php';

$submissions = [];
if (ensure_skill_uploads_table($conn) && ensure_writing_submissions_table($conn)) {
    $sql = 'SELECT ws.id, ws.test_id, ws.student_id, u.name AS student_name, u.username AS student_username, su.title AS test_title, ws.status, ws.score, ws.created_at, ws.graded_at FROM writing_submissions ws INNER JOIN users u ON u.id = ws.student_id AND u.role = 2 INNER JOIN skill_uploads su ON su.id = ws.test_id AND su.skill = ? INNER JOIN teacher_students ts ON ts.student_id = ws.student_id AND ts.teacher_id = ? ORDER BY ws.created_at DESC';
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $skill = 'writing';
        $stmt->bind_param('si', $skill, $teacherCurrentUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $submissions[] = $row;
            }
        }
        $stmt->close();
    }
}

teacher_render_header('Bài nộp Writing', 'Xem và chấm bài writing của học sinh', 'writing_submissions');
?>

<div class="teacher-card p-4">
    <?php if (empty($submissions)): ?>
        <div class="teacher-empty">Chưa có bài Writing nào được nộp từ học sinh của bạn.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table teacher-table align-middle">
                <thead>
                    <tr>
                        <th>Học sinh</th>
                        <th>Đề Writing</th>
                        <th>Ngày nộp</th>
                        <th>Trạng thái</th>
                        <th>Điểm</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $submission): ?>
                        <?php
                            $status = strtolower(trim((string) ($submission['status'] ?? 'submitted')));
                            $statusLabel = $status === 'graded' ? 'Đã chấm' : 'Chờ chấm';
                            $statusClass = $status === 'graded' ? 'answered' : 'pending';
                        ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?php echo htmlspecialchars((string) ($submission['student_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="teacher-muted small"><?php echo htmlspecialchars((string) ($submission['student_username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars((string) ($submission['test_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($submission['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><span class="teacher-chip <?php echo htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><?php echo $submission['score'] !== null ? htmlspecialchars((string) $submission['score'], ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                            <td><a href="grade_writing.php?id=<?php echo (int) $submission['id']; ?>" class="btn btn-sm btn-outline-primary"><?php echo $status === 'graded' ? 'Xem' : 'Chấm bài'; ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php teacher_render_footer(); ?>
