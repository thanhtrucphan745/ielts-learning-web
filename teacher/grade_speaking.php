<?php
require_once __DIR__ . '/_layout.php';

$submissionId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$message = '';
$submission = null;
$payload = null;

if ($submissionId <= 0) {
    header('Location: speaking_submissions.php');
    exit;
}

if (ensure_skill_uploads_table($conn) && ensure_speaking_submissions_table($conn)) {
    $sql = 'SELECT ss.id, ss.test_id, ss.student_id, ss.answer_text, ss.audio_filename, ss.audio_original_name, ss.audio_mime, ss.audio_size, ss.score, ss.feedback, ss.status, ss.created_at, ss.graded_at, u.name AS student_name, u.username AS student_username, su.title AS test_title, su.description AS test_description, su.filename AS test_filename FROM speaking_submissions ss INNER JOIN users u ON u.id = ss.student_id AND u.role = 2 INNER JOIN skill_uploads su ON su.id = ss.test_id AND su.skill = ? INNER JOIN teacher_students ts ON ts.student_id = ss.student_id AND ts.teacher_id = ? WHERE ss.id = ? LIMIT 1';
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $skill = 'speaking';
        $stmt->bind_param('sii', $skill, $teacherCurrentUserId, $submissionId);
        $stmt->execute();
        $submission = $stmt->get_result()?->fetch_assoc();
        $stmt->close();
    }
}

if (!$submission) {
    header('Location: speaking_submissions.php');
    exit;
}

$jsonPath = __DIR__ . '/../uploads/speaking/' . ($submission['test_filename'] ?? '');
if (is_file($jsonPath) && is_readable($jsonPath)) {
    $raw = @file_get_contents($jsonPath);
    $payload = json_decode((string) $raw, true);
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
        if ($score > 10) {
            $score = 10;
        }

        $stmt = $conn->prepare('UPDATE speaking_submissions SET score = ?, feedback = ?, status = ?, graded_at = NOW() WHERE id = ?');
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

function html_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

teacher_render_header('Chấm bài Speaking', 'Đánh giá bài Speaking của học sinh', 'speaking_submissions');
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
        <div class="fw-bold mb-2">Đề Speaking</div>
        <div class="mb-2"><?php echo html_escape((string) ($submission['test_title'] ?? '')); ?></div>
        <div class="text-muted mb-3"><?php echo html_escape((string) ($submission['test_description'] ?? '')); ?></div>
        <?php if (is_array($payload) && !empty($payload['parts'])): ?>
            <?php foreach ($payload['parts'] as $part): ?>
                <div class="mb-3 bg-light border rounded-3 p-3">
                    <div class="fw-semibold mb-2"><?php echo html_escape((string) ($part['part'] ?? '')); ?></div>
                    <?php if (!empty($part['questions']) && is_array($part['questions'])): ?>
                        <div class="mb-2"><strong>Questions</strong><ol class="ps-3 mb-0">
                            <?php foreach ($part['questions'] as $question): ?>
                                <li><?php echo html_escape((string) $question); ?></li>
                            <?php endforeach; ?>
                        </ol></div>
                    <?php endif; ?>
                    <?php if (isset($part['cue_card']) && trim((string) $part['cue_card']) !== ''): ?>
                        <div class="mb-2"><strong>Cue Card</strong><div class="bg-white border rounded-3 p-3"><?php echo nl2br(html_escape((string) $part['cue_card'])); ?></div></div>
                    <?php endif; ?>
                    <?php if (!empty($part['points']) && is_array($part['points'])): ?>
                        <div class="mb-0"><strong>Points</strong><ul class="ps-3 mb-0">
                            <?php foreach ($part['points'] as $point): ?>
                                <li><?php echo html_escape((string) $point); ?></li>
                            <?php endforeach; ?>
                        </ul></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="mb-4">
        <div class="fw-bold mb-2">Câu trả lời học sinh</div>
        <?php if (trim((string) ($submission['answer_text'] ?? '')) !== ''): ?>
            <div class="bg-white border rounded-3 p-3" style="white-space:pre-wrap;word-break:break-word;"><?php echo html_escape((string) ($submission['answer_text'] ?? '')); ?></div>
        <?php else: ?>
            <div class="text-muted">Học sinh không gửi câu trả lời text.</div>
        <?php endif; ?>
    </div>

    <div class="mb-4">
        <div class="fw-bold mb-2">Audio submission</div>
        <?php if (!empty($submission['audio_filename'])): ?>
            <audio controls class="w-100 mb-2">
                <source src="../uploads/speaking/submissions/<?php echo rawurlencode((string) $submission['audio_filename']); ?>" type="audio/mpeg">
                <source src="../uploads/speaking/submissions/<?php echo rawurlencode((string) $submission['audio_filename']); ?>" type="audio/ogg">
                Trình duyệt không hỗ trợ audio tag.
            </audio>
            <div class="small text-muted">Tên gốc: <?php echo html_escape((string) ($submission['audio_original_name'] ?? '')); ?></div>
        <?php else: ?>
            <div class="text-muted">Học sinh không upload audio.</div>
        <?php endif; ?>
    </div>

    <form method="post" action="grade_speaking.php?id=<?php echo (int) $submissionId; ?>">
        <div class="mb-3">
            <label class="form-label">Score</label>
            <input type="number" name="score" min="0" max="10" class="form-control" value="<?php echo isset($_POST['score']) ? html_escape((string) $_POST['score']) : html_escape((string) ($submission['score'] ?? '')); ?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Feedback</label>
            <textarea name="feedback" class="form-control" rows="5" required><?php echo isset($_POST['feedback']) ? html_escape((string) $_POST['feedback']) : html_escape((string) ($submission['feedback'] ?? '')); ?></textarea>
        </div>
        <button class="btn btn-primary" type="submit">Lưu chấm điểm</button>
        <a class="btn btn-outline-secondary ms-2" href="speaking_submissions.php">Quay lại</a>
    </form>
</div>

<?php teacher_render_footer(); ?>
