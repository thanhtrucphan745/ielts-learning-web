<?php
require_once __DIR__ . '/_layout.php';

ensure_skill_uploads_table($conn);

$lessons = [];
$viewId = (int) ($_GET['id'] ?? 0);

$stmt = $conn->prepare('SELECT id, skill, title, description, filename, original_name, created_at FROM skill_uploads WHERE uploaded_by = ? ORDER BY id DESC');
if ($stmt) {
    $stmt->bind_param('i', $teacherCurrentUserId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $lessons[] = $row;
        }
    }
    $stmt->close();
}

$selectedLesson = null;
if ($viewId > 0) {
    $stmt = $conn->prepare('SELECT id, skill, title, description, filename, original_name, mime, size, created_at FROM skill_uploads WHERE id = ? AND uploaded_by = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('ii', $viewId, $teacherCurrentUserId);
        $stmt->execute();
        $selectedLesson = $stmt->get_result()?->fetch_assoc() ?: null;
        $stmt->close();
    }
}

teacher_render_header('Bài học/Bài thi', 'Toàn bộ nội dung đã upload', 'lessons');
?>

<?php if ($selectedLesson): ?>
    <div class="teacher-card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h3 class="teacher-section-title mb-2"><?php echo htmlspecialchars((string) ($selectedLesson['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h3>
                <div class="teacher-muted mb-2">Kỹ năng: <?php echo htmlspecialchars(teacher_skill_label((string) ($selectedLesson['skill'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="teacher-muted"><?php echo htmlspecialchars((string) ($selectedLesson['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <a class="btn btn-outline-primary" href="../uploads/<?php echo rawurlencode((string) ($selectedLesson['skill'] ?? '')); ?>/<?php echo rawurlencode((string) ($selectedLesson['filename'] ?? '')); ?>" target="_blank" rel="noopener">Mở file</a>
        </div>
    </div>
<?php endif; ?>

<div class="teacher-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="teacher-section-title mb-0">Danh sách bài học/Bài thi</h3>
        <span class="teacher-muted">Tổng: <?php echo (int) count($lessons); ?></span>
    </div>

    <?php if (!$lessons): ?>
        <div class="teacher-empty">Chưa có nội dung nào được upload bởi giảng viên này.</div>
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
                    <?php foreach ($lessons as $lesson): ?>
                        <?php
                        $skillFolder = (string) ($lesson['skill'] ?? '');
                        $fileName = basename((string) ($lesson['filename'] ?? ''));
                        $filePath = __DIR__ . '/../uploads/' . $skillFolder . '/' . $fileName;
                        $fileUrl = '../uploads/' . rawurlencode($skillFolder) . '/' . rawurlencode($fileName);
                        $fileExists = $skillFolder !== '' && $fileName !== '' && is_file($filePath);
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string) ($lesson['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(teacher_skill_label($skillFolder), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td style="max-width:320px;white-space:normal;"><?php echo htmlspecialchars((string) ($lesson['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($lesson['created_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="?id=<?php echo (int) $lesson['id']; ?>" class="btn btn-sm btn-outline-primary">Xem</a>
                                    <?php if ($fileExists): ?>
                                        <a href="<?php echo htmlspecialchars($fileUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">Mở file</a>
                                    <?php else: ?>
                                        <span class="teacher-muted small align-self-center">File không khả dụng</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php teacher_render_footer(); ?>