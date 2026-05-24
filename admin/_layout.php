<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../streak.php';

function admin_slugify(string $text): string
{
    $slug = strtolower(trim($text));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim((string) $slug, '-');

    return $slug !== '' ? $slug : 'post';
}

function admin_ensure_posts_table(mysqli $conn): void
{
    $sql = "
        CREATE TABLE IF NOT EXISTS site_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            excerpt TEXT NULL,
            content LONGTEXT NULL,
            status ENUM('draft','published') NOT NULL DEFAULT 'draft',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    $conn->query($sql);
}

function admin_ensure_feedback_status_column(mysqli $conn): void
{
    $result = $conn->query("SHOW COLUMNS FROM contact_messages LIKE 'is_read'");
    if ($result && $result->num_rows === 0) {
        $conn->query("ALTER TABLE contact_messages ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0 AFTER message");
    }
}

function admin_ensure_feedback_reply_columns(mysqli $conn): void
{
    $result = $conn->query("SHOW COLUMNS FROM contact_messages LIKE 'reply_message'");
    if ($result && $result->num_rows === 0) {
        $conn->query("ALTER TABLE contact_messages ADD COLUMN reply_message LONGTEXT NULL AFTER is_read");
    }

    $result = $conn->query("SHOW COLUMNS FROM contact_messages LIKE 'replied_at'");
    if ($result && $result->num_rows === 0) {
        $conn->query("ALTER TABLE contact_messages ADD COLUMN replied_at DATETIME NULL AFTER reply_message");
    }
}

function admin_render_header(string $title, string $activeSection, string $subtitle = ''): void
{
    auth_require_role(1);
    auth_start_session();

    $currentUser = auth_user() ?? [];
    $userName = $currentUser['name'] ?? ($currentUser['username'] ?? 'Admin');
    $userEmail = $currentUser['email'] ?? '';
    $active = static function (string $section) use ($activeSection): string {
        return $section === $activeSection ? 'active' : '';
    };

    echo '<!DOCTYPE html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"><title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title><link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css"><link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,800,900" rel="stylesheet"><link href="css/sb-admin-2.min.css" rel="stylesheet"><style>.sidebar .nav-item .nav-link.active{font-weight:700}.content-card{border:0;box-shadow:0 .15rem 1.75rem 0 rgba(58,59,69,.15)}</style></head><body id="page-top"><div id="wrapper">';

    echo '<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">';
    echo '<a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php"><div class="sidebar-brand-icon rotate-n-15"><i class="fas fa-laugh-wink"></i></div><div class="sidebar-brand-text mx-3">Admin <sup>2</sup></div></a>';
    echo '<hr class="sidebar-divider my-0">';
    echo '<li class="nav-item ' . $active('dashboard') . '"><a class="nav-link" href="index.php"><i class="fas fa-fw fa-tachometer-alt"></i><span>Trang chính</span></a></li>';
    echo '<hr class="sidebar-divider">';
    echo '<div class="sidebar-heading">Quản lý</div>';
    echo '<li class="nav-item ' . $active('users') . '"><a class="nav-link" href="users.php"><i class="fas fa-fw fa-users"></i><span>Người dùng</span></a></li>';
    echo '<li class="nav-item ' . $active('accounts') . '"><a class="nav-link" href="accounts.php"><i class="fas fa-fw fa-id-card"></i><span>Tài khoản</span></a></li>';
    echo '<li class="nav-item ' . $active('statistics') . '"><a class="nav-link" href="statistics.php"><i class="fas fa-fw fa-chart-line"></i><span>Thống kê</span></a></li>';
    echo '<li class="nav-item ' . $active('posts') . '"><a class="nav-link" href="posts.php"><i class="fas fa-fw fa-pen-nib"></i><span>Bài viết</span></a></li>';
    echo '<li class="nav-item ' . $active('feedback') . '"><a class="nav-link" href="feedback.php"><i class="fas fa-fw fa-inbox"></i><span>Phản hồi</span></a></li>';
    echo '<hr class="sidebar-divider">';
    echo '<div class="sidebar-heading">Kỹ năng</div>';
    echo '<li class="nav-item"><a class="nav-link" href="add_skill.php?skill=reading"><i class="fas fa-fw fa-book-open"></i><span>Đọc</span></a></li>';
    echo '<li class="nav-item"><a class="nav-link" href="add_skill.php?skill=listening"><i class="fas fa-fw fa-headphones"></i><span>Nghe</span></a></li>';
    echo '<li class="nav-item"><a class="nav-link" href="add_skill.php?skill=writing"><i class="fas fa-fw fa-pencil-alt"></i><span>Viết</span></a></li>';
    echo '<li class="nav-item"><a class="nav-link" href="add_skill.php?skill=speaking"><i class="fas fa-fw fa-microphone"></i><span>Nói</span></a></li>';
    echo '<hr class="sidebar-divider d-none d-md-block">';
    echo '<div class="text-center d-none d-md-inline"><button class="rounded-circle border-0" id="sidebarToggle"></button></div>';
    echo '</ul>';

    echo '<div id="content-wrapper" class="d-flex flex-column"><div id="content">';
    echo '<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow"><button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3"><i class="fa fa-bars"></i></button><div class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search"><span class="text-gray-600 small">' . htmlspecialchars($subtitle !== '' ? $subtitle : 'Admin Management', ENT_QUOTES, 'UTF-8') . '</span></div><ul class="navbar-nav ml-auto"><li class="nav-item dropdown no-arrow"><a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="mr-2 d-none d-lg-inline text-gray-600 small">' . htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') . '</span><img class="img-profile rounded-circle" src="img/undraw_profile.svg" alt="profile"></a><div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown"><a class="dropdown-item" href="../profile.php"><i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>Profile</a><a class="dropdown-item" href="../dashboard.php"><i class="fas fa-tachometer-alt fa-sm fa-fw mr-2 text-gray-400"></i>Dashboard</a><div class="dropdown-divider"></div><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>Logout</a></div></li></ul></nav><div class="container-fluid">';

    echo '<div class="d-sm-flex align-items-center justify-content-between mb-4"><div><h1 class="h3 mb-0 text-gray-800">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>';
    if ($userEmail !== '') {
        echo '<p class="mb-0 text-muted small">' . htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8') . '</p>';
    }
    echo '</div><a href="index.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm"><i class="fas fa-home fa-sm text-white-50 mr-1"></i>Trang chính</a></div>';
}

function admin_render_footer(): void
{
    echo '</div></div></div></div><a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a><script src="vendor/jquery/jquery.min.js"></script><script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script><script src="vendor/jquery-easing/jquery.easing.min.js"></script><script src="js/sb-admin-2.min.js"></script></body></html>';
}
