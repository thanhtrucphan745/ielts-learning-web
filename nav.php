<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/streak.php';

auth_start_session();
$currentUser = auth_user();
$streakStatus = [
    'currentStreak' => 0,
    'bestStreak' => 0,
];
$replyCount = 0;
$replyItems = [];

if ($currentUser && isset($currentUser['id'])) {
    $streakStatus = streak_get_status($conn, (int) $currentUser['id']);
    $replyCount = auth_contact_reply_count($conn, (string) ($currentUser['email'] ?? ''));
    $replyItems = auth_contact_reply_items($conn, (string) ($currentUser['email'] ?? ''), 5);
}

$currentPath = $_SERVER['SCRIPT_NAME'] ?? '';
function nav_active($paths) {
    global $currentPath;
    foreach ((array) $paths as $p) {
        if ($p !== '' && strpos($currentPath, $p) !== false) return 'active';
    }
    return '';
}
?>

<!-- Navbar (shared) -->
<nav class="navbar navbar-expand-lg bg-white navbar-light shadow sticky-top p-0">

    <a href="index.php" class="navbar-brand d-flex align-items-center px-4 px-lg-5">
        <h2 class="m-0 text-primary"><i class="fa fa-book me-3"></i>IELTS WEB</h2>
    </a>

    <button type="button" class="navbar-toggler me-4" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarCollapse">
        <div class="navbar-nav ms-auto p-4 p-lg-0">
            <a href="index.php" class="nav-item nav-link <?php echo nav_active(['index.php','/']); ?>">Trang chủ</a>
            <a href="about.php" class="nav-item nav-link <?php echo nav_active(['about.php']); ?>">Giới thiệu</a>

            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle <?php echo nav_active(['courses.php']); ?>" data-bs-toggle="dropdown">Khóa học</a>
                <div class="dropdown-menu fade-down m-0">
                    <a href="courses.php?band=0-3.5" class="dropdown-item">Band 0 - 3.5</a>
                    <a href="courses.php?band=3.5-4.5" class="dropdown-item">Band 3.5 - 4.5</a>
                    <a href="courses.php?band=4.5-5.5" class="dropdown-item">Band 4.5 - 5.5</a>
                    <a href="courses.php?band=5.5-6.0" class="dropdown-item">Band 5.5 - 6.0</a>
                </div>
            </div>

            <div class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle <?php echo nav_active(['team.php','testimonial.php','flashcard.php','study_plan.php','chat.php','ielts_tips.php']); ?>" data-bs-toggle="dropdown">Trang</a>
                <div class="dropdown-menu fade-down m-0">
                    <a href="team.php" class="dropdown-item">Đội ngũ</a>
                    <a href="testimonial.php" class="dropdown-item">Nhận xét</a>
                    <a href="flashcard.php" class="dropdown-item">Flashcard</a>
                    <a href="study_plan.php" class="dropdown-item">Lộ trình học</a>
                    <a href="chat.php" class="dropdown-item">AI Chat</a>
                    <a href="pages/ielts_tips.php" class="dropdown-item">IELTS Tips</a>
                </div>
            </div>

            <a href="contact.php" class="nav-item nav-link <?php echo nav_active(['contact.php']); ?>">Liên hệ</a>
        </div>

        <div class="d-none d-lg-flex align-items-center gap-2 px-4 px-lg-5">
            <?php if ($currentUser): ?>
                <div class="dropdown me-2">
                    <a class="btn btn-sm btn-light position-relative rounded-circle d-flex align-items-center justify-content-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="width:42px;height:42px;">
                        <i class="fa fa-bell text-primary"></i>
                        <?php if ($replyCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?php echo (int) $replyCount; ?></span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="min-width: 320px;">
                        <li class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">Tin nhắn quản trị</span>
                            <a href="notifications.php" class="small text-primary text-decoration-none">Xem tất cả</a>
                        </li>
                        <?php if (!$replyItems): ?>
                            <li><span class="dropdown-item-text text-muted small">Chưa có phản hồi mới.</span></li>
                        <?php else: ?>
                            <?php foreach ($replyItems as $replyItem): ?>
                                <li>
                                    <a class="dropdown-item py-2" href="notifications.php#reply-<?php echo (int) $replyItem['id']; ?>">
                                        <div class="small text-muted mb-1"><?php echo htmlspecialchars($replyItem['subject'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="text-dark small" style="white-space: normal;">
                                            <?php echo htmlspecialchars(mb_strimwidth((string) ($replyItem['reply_message'] ?? ''), 0, 80, '...'), ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="dropdown profile-dropdown">
                    <a class="d-flex align-items-center text-decoration-none dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php if (!empty($currentUser['avatar'])): ?>
                            <img src="<?php echo htmlspecialchars($currentUser['avatar'], ENT_QUOTES, 'UTF-8'); ?>" alt="Avatar" class="rounded-circle me-2" style="width:44px;height:44px;object-fit:cover;">
                        <?php else: ?>
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" style="width:44px;height:44px;font-weight:700;">
                                <?php echo htmlspecialchars(strtoupper(mb_substr($currentUser['name'] ?? $currentUser['username'] ?? 'U',0,1,'UTF-8')), ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                        <span class="fw-bold text-dark">Hi, <?php echo htmlspecialchars($currentUser['name'] ?? $currentUser['username'] ?? 'User', ENT_QUOTES, 'UTF-8'); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                        <li class="dropdown-header text-uppercase text-muted small"><?php echo htmlspecialchars(auth_role_label((int) ($currentUser['role'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></li>
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle me-2 text-primary"></i>Hồ sơ cá nhân</a></li>
                        <?php if ((int) ($currentUser['role'] ?? 0) === 2): ?>
                            <li><a class="dropdown-item" href="my_results.php"><i class="fas fa-book-open me-2 text-primary"></i>Bài đã làm</a></li>
                            <li><a class="dropdown-item" href="my_speaking_results.php"><i class="fas fa-microphone me-2 text-primary"></i>Kết quả Speaking</a></li>
                        <?php endif; ?>
                        <li><span class="dropdown-item-text"><i class="fas fa-fire me-2 text-warning"></i>Chuỗi học: <?php echo (int) $streakStatus['currentStreak']; ?> ngày</span></li>
                        <?php if ((int) ($currentUser['role'] ?? 0) === 1): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item fw-semibold text-primary" href="admin/index.php"><i class="fas fa-shield-alt me-2"></i>Quản trị</a></li>
                        <?php elseif ((int) ($currentUser['role'] ?? 0) === 3): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item fw-semibold text-warning" href="teacher/dashboard.php"><i class="fas fa-chalkboard-teacher me-2"></i>Giảng viên</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-power-off me-2"></i>Đăng xuất</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline-primary py-2 px-4">Đăng nhập</a>
                <a href="register.php" class="btn btn-primary py-2 px-4">Đăng ký ngay<i class="fa fa-arrow-right ms-3"></i></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="d-none d-lg-flex align-items-center px-4 px-lg-5 flex-shrink-0">
        <?php if ($currentUser): ?>
            <span class="btn btn-sm btn-outline-primary rounded-pill text-nowrap" aria-label="Chuỗi học hiện tại">
                <i class="fa fa-fire me-1"></i> Chuỗi học: <?php echo (int) $streakStatus['currentStreak']; ?> ngày
            </span>
        <?php else: ?>
            <a href="login.php" class="btn btn-sm btn-outline-secondary rounded-pill text-nowrap" aria-label="Đăng nhập để xem chuỗi học">
                <i class="fa fa-fire me-1"></i> Chuỗi học: đăng nhập để xem
            </a>
        <?php endif; ?>
    </div>

</nav>

<!-- Global Chat Button (shared) -->
<style>
    .global-chat-btn {
        position: fixed;
        right: 20px;
        bottom: 20px;
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: #0d6efd;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 6px 18px rgba(13,110,253,0.3);
        z-index: 1050;
        cursor: pointer;
    }
    .global-chat-btn:hover { transform: translateY(-2px); }
</style>
<div class="global-chat-btn" id="globalChatBtn" title="Chat AI">
    <i class="fa fa-comments" aria-hidden="true"></i>
</div>
<script>
    (function(){
        var btn = document.getElementById('globalChatBtn');
        if (!btn) return;
        btn.addEventListener('click', function(){
            window.location.href = 'chat.php';
        });
    })();
</script>
