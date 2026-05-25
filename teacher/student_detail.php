<?php
require_once __DIR__ . '/_layout.php';

$studentId = (int) ($_GET['id'] ?? 0);
$student = null;
$assignment = null;
$streakStatus = [
    'currentStreak' => 0,
    'bestStreak' => 0,
    'lastActiveDate' => null,
    'completedToday' => false,
    'needsReminder' => true,
    'missedYesterday' => false,
];
$recentQuestions = [];

if ($studentId > 0) {
    $stmt = $conn->prepare('SELECT u.id, u.name, u.email, u.username, u.phone, u.role, u.created_at, ts.created_at AS assigned_at FROM teacher_students ts INNER JOIN users u ON u.id = ts.student_id WHERE ts.teacher_id = ? AND ts.student_id = ? AND u.role = 2 LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('ii', $teacherCurrentUserId, $studentId);
        $stmt->execute();
        $student = $stmt->get_result()?->fetch_assoc() ?: null;
        $stmt->close();
    }

    if ($student) {
        $streakStatus = streak_get_status($conn, $studentId);

        $stmt = $conn->prepare('SELECT id, title, status, created_at, answered_at FROM student_questions WHERE teacher_id = ? AND student_id = ? ORDER BY created_at DESC, id DESC LIMIT 5');
        if ($stmt) {
            $stmt->bind_param('ii', $teacherCurrentUserId, $studentId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $recentQuestions[] = $row;
                }
            }
            $stmt->close();
        }
    }
}

teacher_render_header('Chi tiết học sinh', 'Xem thông tin cơ bản và streak', 'students');
?>

<?php if (!$student): ?>
    <div class="teacher-card p-4">
        <div class="teacher-empty">Không tìm thấy học sinh hoặc học sinh không thuộc giảng viên này.</div>
        <a href="students.php" class="btn btn-outline-primary">Quay lại danh sách</a>
    </div>
<?php else: ?>
    <div class="teacher-grid mb-4">
        <div class="teacher-col-6">
            <div class="teacher-card p-4 h-100">
                <h3 class="teacher-section-title">Thông tin cơ bản</h3>
                <div class="row g-3">
                    <div class="col-md-6"><div class="teacher-muted small">Họ và tên</div><div class="fw-semibold"><?php echo htmlspecialchars((string) ($student['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div></div>
                    <div class="col-md-6"><div class="teacher-muted small">Username</div><div class="fw-semibold"><?php echo htmlspecialchars((string) ($student['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div></div>
                    <div class="col-md-6"><div class="teacher-muted small">Email</div><div class="fw-semibold"><?php echo htmlspecialchars((string) ($student['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div></div>
                    <div class="col-md-6"><div class="teacher-muted small">Số điện thoại</div><div class="fw-semibold"><?php echo htmlspecialchars((string) ($student['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div></div>
                    <div class="col-md-6"><div class="teacher-muted small">Ngày tạo</div><div class="fw-semibold"><?php echo htmlspecialchars((string) ($student['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div></div>
                    <div class="col-md-6"><div class="teacher-muted small">Ngày được gán</div><div class="fw-semibold"><?php echo htmlspecialchars((string) ($student['assigned_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div></div>
                </div>
            </div>
        </div>
        <div class="teacher-col-6">
            <div class="teacher-card p-4 h-100">
                <h3 class="teacher-section-title">Chuỗi học</h3>
                <div class="teacher-grid">
                    <div class="teacher-col-6"><div class="teacher-stat"><div class="label">Hiện tại</div><div class="value"><?php echo (int) $streakStatus['currentStreak']; ?></div></div></div>
                    <div class="teacher-col-6"><div class="teacher-stat"><div class="label">Tốt nhất</div><div class="value"><?php echo (int) $streakStatus['bestStreak']; ?></div></div></div>
                </div>
                <div class="teacher-muted mt-3">Hoạt động gần nhất: <?php echo htmlspecialchars((string) ($streakStatus['lastActiveDate'] ?? 'Chưa có'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        </div>
    </div>

    <div class="teacher-card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="teacher-section-title mb-0">Thắc mắc gần đây</h3>
            <a href="questions.php" class="btn btn-sm btn-outline-primary">Đi tới thắc mắc</a>
        </div>
        <?php if (!$recentQuestions): ?>
            <div class="teacher-empty">Chưa có thắc mắc nào từ học sinh này.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table teacher-table align-middle">
                    <thead>
                        <tr>
                            <th>Tiêu đề</th>
                            <th>Trạng thái</th>
                            <th>Ngày gửi</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentQuestions as $question): ?>
                            <?php [$statusLabel, $statusClass] = teacher_status_meta((string) ($question['status'] ?? 'pending')); ?>
                            <tr>
                                <td><?php echo htmlspecialchars((string) ($question['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><span class="teacher-chip <?php echo htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td><?php echo htmlspecialchars((string) ($question['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><a href="questions.php?reply=<?php echo (int) $question['id']; ?>" class="btn btn-sm btn-outline-primary">Xem</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="d-flex gap-2">
        <a href="students.php" class="btn btn-outline-secondary">Quay lại</a>
        <a href="student_progress.php" class="btn btn-outline-primary">Tiến độ học sinh</a>
    </div>
<?php endif; ?>

<?php teacher_render_footer(); ?>