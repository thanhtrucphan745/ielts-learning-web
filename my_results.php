<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

auth_start_session();
auth_require_login();
$currentUser = auth_user();
$role = (int) ($currentUser['role'] ?? 0);
if ($role !== 2) {
    if ($role === 1) {
        header('Location: admin/index.php');
        exit;
    }
    if ($role === 3) {
        header('Location: teacher/dashboard.php');
        exit;
    }
    header('Location: login.php');
    exit;
}

ensure_test_attempt_tables($conn);
$skillFilter = trim((string) ($_GET['skill'] ?? 'all'));
$skillFilter = in_array($skillFilter, ['all', 'reading', 'listening', 'writing', 'speaking'], true) ? $skillFilter : 'all';

$query = 'SELECT id, skill, test_title, score, total_questions, band_score, submitted_at FROM test_attempts WHERE student_id = ?';
$params = [(int) $currentUser['id']];
$types = 'i';
if ($skillFilter !== 'all') {
    $query .= ' AND skill = ?';
    $types .= 's';
    $params[] = $skillFilter;
}
$query .= ' ORDER BY submitted_at DESC, id DESC';

$attempts = [];
$stmt = $conn->prepare($query);
if ($stmt) {
    if ($skillFilter !== 'all') {
        $stmt->bind_param($types, ...$params);
    } else {
        $stmt->bind_param($types, $params[0]);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $attempts[] = $row;
        }
    }
    $stmt->close();
}

function skill_label(string $skill): string
{
    if ($skill === 'reading') {
        return 'Reading';
    }
    if ($skill === 'listening') {
        return 'Listening';
    }
    if ($skill === 'writing') {
        return 'Writing';
    }
    if ($skill === 'speaking') {
        return 'Speaking';
    }
    return ucfirst($skill);
}

function filter_button(string $value, string $text, string $activeSkill): string
{
    $active = $value === $activeSkill ? 'btn-primary' : 'btn-outline-primary';
    $url = $value === 'all' ? 'my_results.php' : 'my_results.php?skill=' . urlencode($value);
    return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" class="btn ' . $active . ' btn-sm">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</a>';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Kết quả của tôi - eLEARNING</title>
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
                    <h1 class="display-3 text-white animated slideInDown">Bài đã làm</h1>
                    <p class="text-white mb-0">Xem lại kết quả Reading và Listening của bạn.</p>
                    <nav aria-label="breadcrumb" class="mt-3">
                        <ol class="breadcrumb justify-content-center mb-0">
                            <li class="breadcrumb-item"><a class="text-white" href="index.php">Home</a></li>
                            <li class="breadcrumb-item text-white active" aria-current="page">Bài đã làm</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="container-xxl py-5">
        <div class="container">
            <div class="row justify-content-center mb-4">
                <div class="col-lg-10">
                    <div class="d-flex flex-wrap gap-2">
                        <?php echo filter_button('all', 'Tất cả', $skillFilter); ?>
                        <?php echo filter_button('reading', 'Reading', $skillFilter); ?>
                        <?php echo filter_button('listening', 'Listening', $skillFilter); ?>
                        <?php echo filter_button('writing', 'Writing', $skillFilter); ?>
                        <?php echo filter_button('speaking', 'Speaking', $skillFilter); ?>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <?php if (empty($attempts)): ?>
                        <div class="alert alert-info border-0 shadow-sm">Bạn chưa làm bài nào.</div>
                    <?php else: ?>
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>STT</th>
                                                <th>Kỹ năng</th>
                                                <th>Tên bài</th>
                                                <th>Điểm</th>
                                                <th>Band</th>
                                                <th>Ngày làm</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($attempts as $index => $attempt): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars((string) ($index + 1), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars(skill_label((string) ($attempt['skill'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars((string) ($attempt['test_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars((string) ($attempt['score'] ?? 0), ENT_QUOTES, 'UTF-8'); ?> / <?php echo htmlspecialchars((string) ($attempt['total_questions'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars((string) ($attempt['band_score'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars((string) ($attempt['submitted_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td class="text-end">
                                                        <a href="result_detail.php?id=<?php echo htmlspecialchars((string) ($attempt['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-outline-primary">Xem đáp án</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
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
