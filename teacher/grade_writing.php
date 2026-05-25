<?php
require_once __DIR__ . '/_layout.php';

$submissionId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$message = '';
$submission = null;
$payload = null;
$testDetails = null;

if ($submissionId <= 0) {
    header('Location: writing_submissions.php');
    exit;
}

if (ensure_skill_uploads_table($conn) && ensure_writing_submissions_table($conn)) {
    $sql = 'SELECT ws.id, ws.test_id, ws.student_id, ws.answer_text, ws.word_count, ws.score, ws.feedback, ws.status, ws.created_at, ws.graded_at, u.name AS student_name, u.username AS student_username, su.title AS test_title, su.description AS test_description, su.filename AS test_filename FROM writing_submissions ws INNER JOIN users u ON u.id = ws.student_id AND u.role = 2 INNER JOIN skill_uploads su ON su.id = ws.test_id AND su.skill = ? INNER JOIN teacher_students ts ON ts.student_id = ws.student_id AND ts.teacher_id = ? WHERE ws.id = ? LIMIT 1';
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $skill = 'writing';
        $stmt->bind_param('sii', $skill, $teacherCurrentUserId, $submissionId);
        $stmt->execute();
        $submission = $stmt->get_result()?->fetch_assoc();
        $stmt->close();
    }
}

if (!$submission) {
    header('Location: writing_submissions.php');
    exit;
}

$jsonPath = __DIR__ . '/../uploads/writing/' . ($submission['test_filename'] ?? '');
if (is_file($jsonPath) && is_readable($jsonPath)) {
    $jsonRaw = @file_get_contents($jsonPath);
    $payload = json_decode((string) $jsonRaw, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scoreValue = trim((string) ($_POST['score'] ?? ''));
    $feedback = trim((string) ($_POST['feedback'] ?? ''));
    if ($scoreValue === '') {
        $message = 'Vui lòng nhập điểm.';
    } elseif (!is_numeric($scoreValue)) {
        $message = 'Điểm phải là số.';
    } elseif ($feedback === '') {
        $message = 'Vui lòng nhập nhận xét.';
    } else {
        $score = (int) $scoreValue;
        if ($score < 0) {
            $score = 0;
        }
        if ($score > 9) {
            $score = 9;
        }

        $stmt = $conn->prepare('UPDATE writing_submissions SET score = ?, feedback = ?, status = ?, graded_at = NOW() WHERE id = ?');
        if ($stmt) {
            $status = 'graded';
            $stmt->bind_param('issi', $score, $feedback, $status, $submissionId);
            if ($stmt->execute()) {
                $message = 'Chấm bài thành công.';
                $submission['score'] = $score;
                $submission['feedback'] = $feedback;
                $submission['status'] = $status;
                $submission['graded_at'] = date('Y-m-d H:i:s');
            } else {
                $message = 'Không thể lưu điểm, thử lại sau.';
            }
            $stmt->close();
        } else {
            $message = 'Lỗi cập nhật dữ liệu.';
        }
    }
}

$testTitle = trim((string) ($submission['test_title'] ?? ''));
$testDescription = trim((string) ($submission['test_description'] ?? ''));
$prompt = is_array($payload) ? trim((string) ($payload['prompt'] ?? '')) : '';
$minWords = is_array($payload) && isset($payload['min_words']) ? (int) $payload['min_words'] : 0;
$maxWords = is_array($payload) && isset($payload['max_words']) ? (int) $payload['max_words'] : 0;
$criteria = is_array($payload['criteria'] ?? null) ? $payload['criteria'] : [];

function html_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

teacher_render_header('Chấm bài Writing', 'Đánh giá bài Writing của học sinh', 'writing_submissions');
?>

<div class="teacher-card p-4">
    <?php if ($message !== ''): ?>
        <div class="alert alert-info border-0 shadow-sm"><?php echo html_escape($message); ?></div>
    <?php endif; ?>

    <div class="mb-4">
        <div class="fw-bold mb-2">Học sinh</div>
        <div><?php echo html_escape((string) ($submission['student_name'] ?? '')); ?> (<?php echo html_escape((string) ($submission['student_username'] ?? '')); ?>)</div>
    </div>

    <div class="mb-4">
        <div class="fw-bold mb-2">Đề Writing</div>
        <div class="mb-2"><?php echo html_escape($testTitle); ?></div>
        <div class="text-muted mb-3"><?php echo html_escape($testDescription); ?></div>
        <?php if ($prompt !== ''): ?>
            <div class="mb-3"><strong>Prompt</strong><p><?php echo nl2br(html_escape($prompt)); ?></p></div>
        <?php endif; ?>
        <?php if ($minWords > 0 || $maxWords > 0 || !empty($criteria)): ?>
            <div class="row g-3 mb-3">
                <?php if ($minWords > 0): ?>
                    <div class="col-md-6"><div class="bg-light border rounded-3 p-3"><strong>Min words</strong><div><?php echo html_escape((string) $minWords); ?></div></div></div>
                <?php endif; ?>
                <?php if ($maxWords > 0): ?>
                    <div class="col-md-6"><div class="bg-light border rounded-3 p-3"><strong>Max words</strong><div><?php echo html_escape((string) $maxWords); ?></div></div></div>
                <?php endif; ?>
            </div>
            <?php if (!empty($criteria)): ?>
                <div class="bg-light border rounded-3 p-3 mb-4">
                    <strong>Criteria</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($criteria as $criterion): ?>
                            <li><?php echo html_escape((string) $criterion); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="mb-4">
        <div class="fw-bold mb-2">Bài làm học sinh</div>
        <div class="bg-white border rounded-3 p-3" style="white-space:pre-wrap;word-break:break-word;"><?php echo html_escape((string) ($submission['answer_text'] ?? '')); ?></div>
    </div>

    <form method="post" action="grade_writing.php?id=<?php echo (int) $submissionId; ?>">
        <div class="mb-3">
            <label class="form-label">Score</label>
            <input type="number" name="score" min="0" max="9" class="form-control" value="<?php echo isset($_POST['score']) ? html_escape((string) $_POST['score']) : html_escape((string) ($submission['score'] ?? '')); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Feedback</label>
            <textarea name="feedback" class="form-control" rows="5" required><?php echo isset($_POST['feedback']) ? html_escape((string) $_POST['feedback']) : html_escape((string) ($submission['feedback'] ?? '')); ?></textarea>
        </div>
        <button class="btn btn-primary" type="submit">Lưu chấm điểm</button>
        <a class="btn btn-outline-secondary ms-2" href="writing_submissions.php">Quay lại</a>
    </form>
</div>

<?php teacher_render_footer(); ?>
