<?php
require_once __DIR__ . '/_layout.php';

$assignedStudentCount = 0;
$uploadedLessonCount = 0;
$pendingQuestionCount = 0;
$answeredQuestionCount = 0;
$recentStudents = [];
$recentLessons = [];
$recentQuestions = [];
$teacherHasAssignments = teacher_table_exists($conn, 'teacher_students');

if ($teacherHasAssignments) {
    $stmt = $conn->prepare('SELECT COUNT(DISTINCT ts.student_id) AS total FROM teacher_students ts INNER JOIN users u ON u.id = ts.student_id WHERE ts.teacher_id = ? AND u.role = 2');
    if ($stmt) {
        $stmt->bind_param('i', $teacherCurrentUserId);
        $stmt->execute();
        $row = $stmt->get_result()?->fetch_assoc() ?: [];
        $assignedStudentCount = (int) ($row['total'] ?? 0);
        $stmt->close();
    }

    $stmt = $conn->prepare('SELECT u.id, u.name, u.email, u.username, COALESCE(us.current_streak, 0) AS current_streak, COALESCE(us.best_streak, 0) AS best_streak FROM teacher_students ts INNER JOIN users u ON u.id = ts.student_id AND u.role = 2 LEFT JOIN user_streaks us ON us.user_id = u.id WHERE ts.teacher_id = ? ORDER BY ts.created_at DESC, ts.id DESC LIMIT 5');
    if ($stmt) {
        $stmt->bind_param('i', $teacherCurrentUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $recentStudents[] = $row;
            }
        }
        $stmt->close();
    }
}

ensure_skill_uploads_table($conn);
$stmt = $conn->prepare('SELECT COUNT(*) AS total FROM skill_uploads WHERE uploaded_by = ?');
if ($stmt) {
    $stmt->bind_param('i', $teacherCurrentUserId);
    $stmt->execute();
    $row = $stmt->get_result()?->fetch_assoc() ?: [];
    $uploadedLessonCount = (int) ($row['total'] ?? 0);
    $stmt->close();
}

$stmt = $conn->prepare('SELECT id, skill, title, description, filename, original_name, created_at FROM skill_uploads WHERE uploaded_by = ? ORDER BY id DESC LIMIT 5');
if ($stmt) {
    $stmt->bind_param('i', $teacherCurrentUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recentLessons[] = $row;
        }
    }
    $stmt->close();
}

$stmt = $conn->prepare("SELECT SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_total, SUM(CASE WHEN status = 'answered' THEN 1 ELSE 0 END) AS answered_total FROM student_questions WHERE teacher_id = ?");
if ($stmt) {
    $stmt->bind_param('i', $teacherCurrentUserId);
    $stmt->execute();
    $row = $stmt->get_result()?->fetch_assoc() ?: [];
    $pendingQuestionCount = (int) ($row['pending_total'] ?? 0);
    $answeredQuestionCount = (int) ($row['answered_total'] ?? 0);
    $stmt->close();
}

$stmt = $conn->prepare('SELECT q.id, q.title, q.status, q.created_at, u.name AS student_name, u.username AS student_username FROM student_questions q INNER JOIN users u ON u.id = q.student_id INNER JOIN teacher_students ts ON ts.student_id = q.student_id AND ts.teacher_id = q.teacher_id WHERE q.teacher_id = ? ORDER BY q.created_at DESC, q.id DESC LIMIT 5');
if ($stmt) {
    $stmt->bind_param('i', $teacherCurrentUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recentQuestions[] = $row;
        }
    }
    $stmt->close();
}

teacher_render_header('Tổng quan giảng viên', 'Quản lý học sinh, bài học và thắc mắc', 'dashboard');
?>

<div class="teacher-grid mb-4">
    <div class="teacher-col-3">
        <div class="teacher-card teacher-stat h-100">
            <div class="label">Học sinh được gán</div>
            <div class="value"><?php echo (int) $assignedStudentCount; ?></div>
        </div>
    </div>
    <div class="teacher-col-3">
        <div class="teacher-card teacher-stat h-100">
            <div class="label">Bài học/Bài thi đã đăng</div>
            <div class="value"><?php echo (int) $uploadedLessonCount; ?></div>
        </div>
    </div>
    <div class="teacher-col-3">
        <div class="teacher-card teacher-stat h-100">
            <div class="label">Thắc mắc chưa trả lời</div>
            <div class="value"><?php echo (int) $pendingQuestionCount; ?></div>
        </div>
    </div>
    <div class="teacher-col-3">
        <div class="teacher-card teacher-stat h-100">
            <div class="label">Thắc mắc đã trả lời</div>
            <div class="value"><?php echo (int) $answeredQuestionCount; ?></div>
        </div>
    </div>
</div>

<div class="teacher-grid mb-4">
    <div class="teacher-col-6">
        <div class="teacher-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="teacher-section-title mb-0">Danh sách học sinh gần đây</h3>
                <a href="students.php" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
            </div>
            <?php if (!$recentStudents): ?>
                <div class="teacher-empty">Chưa có học sinh được gán.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table teacher-table align-middle">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Tên học sinh</th>
                                <th>Email</th>
                                <th>Username</th>
                                <th>Streak</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentStudents as $index => $student): ?>
                                <tr>
                                    <td><?php echo (int) ($index + 1); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($student['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($student['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($student['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo (int) ($student['current_streak'] ?? 0); ?> ngày</td>
                                    <td><a href="student_detail.php?id=<?php echo (int) $student['id']; ?>" class="btn btn-sm btn-outline-primary">Xem chi tiết</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="teacher-col-6">
        <div class="teacher-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="teacher-section-title mb-0">Bài học/Bài thi mới đăng</h3>
                <a href="lessons.php" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
            </div>
            <?php if (!$recentLessons): ?>
                <div class="teacher-empty">Chưa có bài học hoặc bài thi nào.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table teacher-table align-middle">
                        <thead>
                            <tr>
                                <th>Tiêu đề</th>
                                <th>Kỹ năng</th>
                                <th>Mô tả</th>
                                <th>Ngày upload</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentLessons as $lesson): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string) ($lesson['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars(teacher_skill_label((string) ($lesson['skill'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($lesson['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($lesson['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><a href="lessons.php?id=<?php echo (int) $lesson['id']; ?>" class="btn btn-sm btn-outline-primary">Xem</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="teacher-card p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="teacher-section-title mb-0">Thắc mắc mới nhất</h3>
        <a href="questions.php" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
    </div>
    <?php if (!$recentQuestions): ?>
        <div class="teacher-empty">Chưa có thắc mắc nào.</div>
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
                    <?php foreach ($recentQuestions as $question): ?>
                        <?php [$statusLabel, $statusClass] = teacher_status_meta((string) ($question['status'] ?? 'pending')); ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?php echo htmlspecialchars((string) ($question['student_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="teacher-muted small"><?php echo htmlspecialchars((string) ($question['student_username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars((string) ($question['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><span class="teacher-chip <?php echo htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><?php echo htmlspecialchars((string) ($question['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><a href="questions.php?reply=<?php echo (int) $question['id']; ?>" class="btn btn-sm btn-outline-primary"><?php echo ((string) ($question['status'] ?? '') === 'answered') ? 'Xem' : 'Trả lời'; ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php teacher_render_footer(); ?>