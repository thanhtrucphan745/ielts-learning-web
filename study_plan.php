<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/streak.php';

auth_start_session();
$currentUser = auth_user();
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$basePath = $basePath === '' ? '/' : $basePath . '/';
$displayName = $currentUser['name'] ?? ($currentUser['username'] ?? 'User');
$avatarText = strtoupper(mb_substr($displayName, 0, 1, 'UTF-8'));
$avatarPath = !empty($currentUser['avatar']) ? $currentUser['avatar'] : '';
$avatarUrl = $avatarPath !== '' ? $avatarPath : '';
$role = isset($currentUser['role']) ? (int) $currentUser['role'] : 0;
$streakStatus = [
    'currentStreak' => 0,
    'bestStreak' => 0,
    'needsReminder' => false,
    'missedYesterday' => false,
];

if ($currentUser && isset($currentUser['id'])) {
    $streakStatus = streak_get_status($conn, (int) $currentUser['id']);
}

$errors = [];
$planReady = false;

$currentBand = '4.0';
$targetBand = '5.5';
$weeks = '8';
$dailyMinutes = '90';
$weakSkills = [];

$skillLabels = [
    'reading' => 'Đọc',
    'listening' => 'Nghe',
    'writing' => 'Viết',
    'speaking' => 'Nói',
];

$taskLibrary = [
    'foundation' => [
        'reading' => ['Luyện skimming 2 đoạn đọc ngắn và ghi lại ý chính.', 'Học 20 từ vựng học thuật và đặt câu ví dụ.'],
        'listening' => ['Nghe 1 audio ngắn, tua lại 2 lần để nắm ý.', 'Chép chính tả 10-15 câu và đối chiếu đáp án.'],
        'writing' => ['Viết 1 đoạn 120-150 từ theo cấu trúc PEEL.', 'Sửa lỗi ngữ pháp cơ bản: thì, chia động từ, mẫu câu.'],
        'speaking' => ['Luyện nói Part 1 trong 3-4 phút theo chủ đề quen thuộc.', 'Ghi âm, nghe lại và đánh dấu lỗi phát âm để sửa.'],
    ],
    'development' => [
        'reading' => ['Làm 1 passage có giới hạn thời gian 20 phút.', 'Luyện dạng câu hỏi True/False/Not Given.'],
        'listening' => ['Làm 1 section listening trong 15 phút.', 'Tổng hợp lỗi sai theo dạng (số, tên riêng, distractor).'],
        'writing' => ['Viết Task 1 hoặc Task 2 theo đề IELTS mẫu.', 'Tự sửa bài theo checklist: task response, coherence, grammar.'],
        'speaking' => ['Luyện Part 2 với 1 cue card, nói đủ 2 phút.', 'Luyện Part 3: mở rộng ý, đưa ví dụ cụ thể.'],
    ],
    'exam' => [
        'reading' => ['Làm full reading test 60 phút, không dùng từ điển.', 'Phân tích 10 câu sai và ghi rõ lý do sai.'],
        'listening' => ['Làm full listening test 30 phút + 10 phút chuyển đáp án.', 'Luyện bắt từ khóa và dự đoán trước khi nghe.'],
        'writing' => ['Viết full Writing (Task 1 + Task 2) trong 60 phút.', 'Nâng cấp lexical resource: paraphrase + collocation.'],
        'speaking' => ['Mô phỏng speaking test 11-14 phút với đồng hồ bấm giờ.', 'Luyện fluency: hạn chế ngập ngừng, nói mạch lạc, có liên kết.'],
    ],
];

$allocation = [];
$weeklyPlan = [];
$weeklyHours = 0;
$milestoneText = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentBand = trim($_POST['current_band'] ?? $currentBand);
    $targetBand = trim($_POST['target_band'] ?? $targetBand);
    $weeks = trim($_POST['weeks'] ?? $weeks);
    $dailyMinutes = trim($_POST['daily_minutes'] ?? $dailyMinutes);
    $weakSkills = $_POST['weak_skills'] ?? [];

    $currentBandNum = (float) $currentBand;
    $targetBandNum = (float) $targetBand;
    $weeksNum = (int) $weeks;
    $dailyMinutesNum = (int) $dailyMinutes;

    if ($currentBandNum < 0 || $currentBandNum > 9) {
        $errors[] = 'Band hiện tại không hợp lệ.';
    }
    if ($targetBandNum < 1 || $targetBandNum > 9) {
        $errors[] = 'Band mục tiêu không hợp lệ.';
    }
    if ($targetBandNum <= $currentBandNum) {
        $errors[] = 'Band mục tiêu phải lớn hơn band hiện tại.';
    }
    if ($weeksNum < 2 || $weeksNum > 24) {
        $errors[] = 'Số tuần học nên trong khoảng 2-24 tuần.';
    }
    if ($dailyMinutesNum < 20 || $dailyMinutesNum > 300) {
        $errors[] = 'Số phút học mỗi ngày nên trong khoảng 20-300 phút.';
    }

    $weakSkills = array_values(array_intersect(array_keys($skillLabels), $weakSkills));

    if (!$errors) {
        $gap = $targetBandNum - $currentBandNum;
        $level = 'foundation';
        if ($targetBandNum >= 6 || $gap >= 1.5) {
            $level = 'exam';
        } elseif ($targetBandNum >= 5) {
            $level = 'development';
        }

        $weights = [
            'reading' => 1.0,
            'listening' => 1.0,
            'writing' => 1.0,
            'speaking' => 1.0,
        ];

        foreach ($weakSkills as $skill) {
            $weights[$skill] += 0.6;
        }

        if ($gap >= 1.5) {
            $weights['writing'] += 0.3;
            $weights['speaking'] += 0.3;
        }

        $totalWeight = array_sum($weights);
        $weeklyHours = round(($dailyMinutesNum * 7) / 60, 1);

        foreach ($weights as $skill => $value) {
            $minutes = (int) round(($dailyMinutesNum * $value) / $totalWeight);
            $allocation[$skill] = max(10, $minutes);
        }

        arsort($allocation);
        $prioritySkills = array_keys($allocation);

        for ($week = 1; $week <= $weeksNum; $week++) {
            $focusSkill = $prioritySkills[($week - 1) % count($prioritySkills)];
            $secondarySkill = $prioritySkills[$week % count($prioritySkills)];

            $weeklyPlan[] = [
                'week' => $week,
                'focus' => $focusSkill,
                'secondary' => $secondarySkill,
                'tasks' => [
                    $taskLibrary[$level][$focusSkill][0],
                    $taskLibrary[$level][$focusSkill][1],
                    $taskLibrary[$level][$secondarySkill][0],
                ],
            ];
        }

        $milestoneBand = min($targetBandNum, $currentBandNum + max(0.5, round(($targetBandNum - $currentBandNum) * 0.5, 1)));
        $milestoneText = 'Mốc giữa kỳ gợi ý: đạt khoảng band ' . number_format($milestoneBand, 1) . ' vào tuần ' . max(2, (int) floor($weeksNum / 2)) . '.';

        $planReady = true;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Lộ trình học cá nhân - eLEARNING</title>
    <base href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>">
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

    <?php if ($currentUser && !empty($streakStatus['needsReminder'])): ?>
        <div class="container mt-3">
            <div class="alert <?php echo !empty($streakStatus['missedYesterday']) ? 'alert-danger' : 'alert-warning'; ?> d-flex justify-content-between align-items-center" role="alert">
                <div>
                    <i class="fas fa-fire me-2"></i>
                    <?php if (!empty($streakStatus['missedYesterday'])): ?>
                        Bạn đã gián đoạn chuỗi học. Hoàn thành một bài ngay hôm nay để bắt đầu lại.
                    <?php else: ?>
                        Hôm nay bạn chưa hoàn thành mục tiêu học. Làm một bài test để giữ chuỗi.
                    <?php endif; ?>
                </div>
                <a href="reading.php" class="btn btn-sm btn-outline-dark">Học ngay</a>
            </div>
        </div>
    <?php endif; ?>

    <div class="container-fluid bg-primary py-5 mb-5 page-header">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-10 text-center">
                    <h1 class="display-5 text-white animated slideInDown">Lộ Trình Học Cá Nhân</h1>
                    <p class="text-white mb-0">Nhận kế hoạch theo tuần dựa trên band hiện tại, mục tiêu và quỹ thời gian của bạn.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container-xxl py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="bg-light rounded p-4 h-100">
                        <h4 class="mb-3">Tạo lộ trình</h4>
                        <p class="text-muted">Nhập thông tin học tập để hệ thống gợi ý phân bổ 4 kỹ năng và nhiệm vụ từng tuần.</p>

                        <?php if ($errors): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="study_plan.php" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Band hiện tại</label>
                                <input type="number" step="0.5" min="0" max="9" name="current_band" class="form-control" value="<?php echo htmlspecialchars($currentBand, ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Band mục tiêu</label>
                                <input type="number" step="0.5" min="1" max="9" name="target_band" class="form-control" value="<?php echo htmlspecialchars($targetBand, ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Số tuần học</label>
                                <input type="number" min="2" max="24" name="weeks" class="form-control" value="<?php echo htmlspecialchars($weeks, ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phút học/ngày</label>
                                <input type="number" min="20" max="300" name="daily_minutes" class="form-control" value="<?php echo htmlspecialchars($dailyMinutes, ENT_QUOTES, 'UTF-8'); ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label d-block">Kỹ năng yếu (chọn nhiều)</label>
                                <?php foreach ($skillLabels as $skillKey => $skillLabel): ?>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="weak_skills[]" value="<?php echo htmlspecialchars($skillKey, ENT_QUOTES, 'UTF-8'); ?>" id="weak_<?php echo htmlspecialchars($skillKey, ENT_QUOTES, 'UTF-8'); ?>" <?php echo in_array($skillKey, $weakSkills, true) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="weak_<?php echo htmlspecialchars($skillKey, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($skillLabel, ENT_QUOTES, 'UTF-8'); ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary px-4 py-2">Tạo lộ trình ngay</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="bg-white border rounded p-4 h-100 shadow-sm">
                        <?php if ($planReady): ?>
                            <h4 class="mb-3">Kế hoạch học của bạn</h4>
                            <p class="mb-2"><strong>Tổng giờ học mỗi tuần:</strong> <?php echo htmlspecialchars((string) $weeklyHours, ENT_QUOTES, 'UTF-8'); ?> giờ</p>
                            <p class="mb-3"><strong><?php echo htmlspecialchars($milestoneText, ENT_QUOTES, 'UTF-8'); ?></strong></p>

                            <h6 class="text-primary">Phân bổ mỗi ngày</h6>
                            <div class="row g-2 mb-4">
                                <?php foreach ($allocation as $skill => $minutes): ?>
                                    <div class="col-sm-6">
                                        <div class="border rounded p-2 d-flex justify-content-between">
                                            <span><?php echo htmlspecialchars($skillLabels[$skill], ENT_QUOTES, 'UTF-8'); ?></span>
                                            <strong><?php echo htmlspecialchars((string) $minutes, ENT_QUOTES, 'UTF-8'); ?> phút</strong>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <h6 class="text-primary">Lộ trình theo tuần</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tuần</th>
                                            <th>Trọng tâm</th>
                                            <th>Nhiệm vụ gợi ý</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($weeklyPlan as $item): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars((string) $item['week'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo htmlspecialchars($skillLabels[$item['focus']], ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($skillLabels[$item['secondary']], ENT_QUOTES, 'UTF-8'); ?></span>
                                                </td>
                                                <td>
                                                    <ul class="mb-0 ps-3">
                                                        <?php foreach ($item['tasks'] as $task): ?>
                                                            <li><?php echo htmlspecialchars($task, ENT_QUOTES, 'UTF-8'); ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fa fa-route fa-3x text-primary mb-3"></i>
                                <h5>Bạn chưa tạo lộ trình</h5>
                                <p class="text-muted mb-0">Nhập thông tin ở cột bên trái để nhận study plan theo mục tiêu band.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>
