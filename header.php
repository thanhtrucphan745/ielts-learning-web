<!DOCTYPE html>
<html>
<head>
    <title>IELTS Learning</title>

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

    // compute a project-root base href so pages inside subfolders (e.g. /pages/) load assets correctly
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    $segments = explode('/', trim($script, '/'));
    $root = '/';
    if (count($segments) >= 1) {
        // assume the first segment is the project folder (e.g. 'ielts-web') when present
        $root = '/' . $segments[0] . '/';
    }
    ?>
    <base href="<?php echo htmlspecialchars($root, ENT_QUOTES, 'UTF-8'); ?>">

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg bg-white navbar-light shadow sticky-top p-0">

    <a href="index.php" class="navbar-brand d-flex align-items-center px-4 px-lg-5">
        <h2 class="m-0 text-danger">
            IELTS WEB
        </h2>
    </a>

    <!-- Add main navigation links -->
    <div class="collapse navbar-collapse" id="navbarCollapse">
        <div class="navbar-nav ms-auto p-4 p-lg-0">
            <a href="index.php" class="nav-item nav-link active">Trang chủ</a>
            <a href="courses.php" class="nav-item nav-link">Khóa học</a>
            <a href="study_plan.php" class="nav-item nav-link">Lộ trình học</a>
            <a href="pages/ielts_tips.php" class="nav-item nav-link">IELTS Tips</a>
            <a href="contact.php" class="nav-item nav-link">Liên hệ</a>
        </div>
    </div>

    <div class="d-none d-lg-flex align-items-center px-4 px-lg-5 flex-shrink-0">
        <?php if ($currentUser): ?>
            <div class="dropdown me-2">
                <a class="btn btn-sm btn-light position-relative rounded-circle d-flex align-items-center justify-content-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="width:38px;height:38px;">
                    <i class="fa fa-bell text-primary"></i>
                    <?php if ($replyCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?php echo (int) $replyCount; ?></span>
                    <?php endif; ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="min-width: 300px;">
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
<!-- Global Chat Button (appears on all pages) -->
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
            // Open internal chat page that uses OpenAI
            window.location.href = 'chat.php';
        });
    })();
</script>