<?php
require_once __DIR__ . '/_layout.php';

$students = [];

$stmt = $conn->prepare('SELECT u.id, u.name, u.email, u.username, COALESCE(us.current_streak, 0) AS current_streak, COALESCE(sess.total_sessions, 0) AS total_sessions, sess.latest_score, sess.last_activity_at FROM teacher_students ts INNER JOIN users u ON u.id = ts.student_id AND u.role = 2 LEFT JOIN user_streaks us ON us.user_id = u.id LEFT JOIN (SELECT ss.user_id, COUNT(*) AS total_sessions, (SELECT score FROM study_sessions WHERE user_id = ss.user_id ORDER BY created_at DESC, id DESC LIMIT 1) AS latest_score, MAX(created_at) AS last_activity_at FROM study_sessions ss GROUP BY ss.user_id) sess ON sess.user_id = u.id WHERE ts.teacher_id = ? ORDER BY ts.created_at DESC, u.id DESC');
if ($stmt) {
    $stmt->bind_param('i', $teacherCurrentUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
    }
    $stmt->close();
}

teacher_render_header('Tiến độ học sinh', 'Theo dõi tiến độ của học sinh được gán', 'progress');
?>

<div class="teacher-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="teacher-section-title mb-0">Danh sách tiến độ học sinh</h3>
        <span class="teacher-muted">Tổng: <?php echo (int) count($students); ?></span>
    </div>

    <?php if (!$students): ?>
        <div class="teacher-empty">Chưa có học sinh nào được gán.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table teacher-table align-middle">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Tên học sinh</th>
                        <th>Email</th>
                        <th>Username</th>
                        <th>Streak hiện tại</th>
                        <th>Tổng bài đã làm</th>
                        <th>Điểm gần nhất</th>
                        <th>Lần hoạt động gần nhất</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $index => $student): ?>
                        <tr>
                            <td><?php echo (int) ($index + 1); ?></td>
                            <td><?php echo htmlspecialchars((string) ($student['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($student['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($student['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo (int) ($student['current_streak'] ?? 0); ?> ngày</td>
                            <td><?php echo ((int) ($student['total_sessions'] ?? 0) > 0) ? (int) $student['total_sessions'] : 'Chưa có dữ liệu'; ?></td>
                            <td><?php echo ($student['latest_score'] !== null) ? htmlspecialchars((string) $student['latest_score'], ENT_QUOTES, 'UTF-8') : 'Chưa có dữ liệu'; ?></td>
                            <td><?php echo !empty($student['last_activity_at']) ? htmlspecialchars((string) $student['last_activity_at'], ENT_QUOTES, 'UTF-8') : 'Chưa có dữ liệu'; ?></td>
                            <td><a href="student_detail.php?id=<?php echo (int) $student['id']; ?>" class="btn btn-sm btn-outline-primary">Xem chi tiết</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php teacher_render_footer(); ?>