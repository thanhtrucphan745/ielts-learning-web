<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

require_student();
$currentUser = auth_user() ?? [];
$studentId = (int) ($currentUser['id'] ?? 0);

$submissions = [];
if (isset($conn) && $conn instanceof mysqli && ensure_skill_uploads_table($conn) && ensure_speaking_submissions_table($conn)) {
    $stmt = $conn->prepare('SELECT ss.id, ss.test_id, ss.audio_filename, ss.score, ss.feedback, ss.status, ss.created_at, ss.graded_at, su.title FROM speaking_submissions ss INNER JOIN skill_uploads su ON su.id = ss.test_id AND su.skill = ? WHERE ss.student_id = ? ORDER BY ss.created_at DESC');
    if ($stmt) {
        $skill = 'speaking';
        $stmt->bind_param('si', $skill, $studentId);
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

function escape_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Kết quả Speaking của tôi - eLEARNING</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="img/favicon.ico" rel="icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/nav.php'; ?>

    <div class="container-fluid bg-primary py-5 mb-5 page-header">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-10 text-center">
                    <h1 class="display-3 text-white animated slideInDown">Kết quả Speaking</h1>
                    <p class="text-white mb-0">Danh sách bài Speaking đã nộp và kết quả chấm.</p>
                    <nav aria-label="breadcrumb" class="mt-3">
                        <ol class="breadcrumb justify-content-center mb-0">
                            <li class="breadcrumb-item"><a class="text-white" href="index.php">Home</a></li>
                            <li class="breadcrumb-item text-white active" aria-current="page">Kết quả Speaking</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="container-xxl py-5">
        <div class="container">
            <?php if (empty($submissions)): ?>
                <div class="alert alert-secondary">Bạn chưa nộp bài Speaking nào.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>Đề Speaking</th>
                                <th>Ngày nộp</th>
                                <th>Audio</th>
                                <th>Trạng thái</th>
                                <th>Điểm</th>
                                <th>Feedback</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $submission): ?>
                                <tr>
                                    <td><?php echo escape_html((string) ($submission['title'] ?? '')); ?></td>
                                    <td><?php echo escape_html((string) ($submission['created_at'] ?? '')); ?></td>
                                    <td><?php echo !empty($submission['audio_filename']) ? 'Có' : 'Không'; ?></td>
                                    <td><?php echo escape_html((string) ($submission['status'] === 'graded' ? 'Đã chấm' : 'Chờ giảng viên chấm')); ?></td>
                                    <td><?php echo $submission['score'] !== null ? escape_html((string) $submission['score']) : '-'; ?></td>
                                    <td><?php echo $submission['feedback'] !== null ? nl2br(escape_html((string) $submission['feedback'])) : '<em>Chờ giảng viên chấm.</em>'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="container-fluid bg-dark text-light footer pt-5 mt-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-lg-3 col-md-6">
                    <h4 class="text-white mb-3">Quick Link</h4>
                    <a class="btn btn-link" href="about.php">About Us</a>
                    <a class="btn btn-link" href="contact.php">Contact Us</a>
                </div>
            </div>
        </div>
    </div>

    <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>
