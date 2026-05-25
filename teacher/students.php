<?php
require_once __DIR__ . '/_layout.php';

$students = [];

$stmt = $conn->prepare('SELECT u.id, u.name, u.email, u.username, u.phone, COALESCE(us.current_streak, 0) AS current_streak, COALESCE(us.best_streak, 0) AS best_streak, ts.created_at AS assigned_at FROM teacher_students ts INNER JOIN users u ON u.id = ts.student_id AND u.role = 2 LEFT JOIN user_streaks us ON us.user_id = u.id WHERE ts.teacher_id = ? ORDER BY ts.created_at DESC, u.id DESC');
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

teacher_render_header('Học sinh', 'Toàn bộ học sinh được gán cho giảng viên', 'students');
?>

<div class="teacher-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="teacher-section-title mb-0">Danh sách học sinh</h3>
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
                        <th>Tên đăng nhập</th>
                        <th>Chuỗi học</th>
                        <th>Chuỗi tốt nhất</th>
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
                            <td><?php echo (int) ($student['best_streak'] ?? 0); ?> ngày</td>
                            <td><a href="student_detail.php?id=<?php echo (int) $student['id']; ?>" class="btn btn-sm btn-outline-primary">Xem chi tiết</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php teacher_render_footer(); ?>