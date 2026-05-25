<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../streak.php';

require_teacher();
auth_start_session();

$teacherCurrentUser = auth_user() ?? [];
$teacherCurrentUserName = (string) ($teacherCurrentUser['name'] ?? $teacherCurrentUser['username'] ?? 'Giảng viên');
$teacherCurrentUserEmail = (string) ($teacherCurrentUser['email'] ?? '');
$teacherCurrentUserId = (int) ($teacherCurrentUser['id'] ?? 0);
$teacherCurrentUserRole = (int) ($teacherCurrentUser['role'] ?? 0);

function teacher_table_exists(mysqli $conn, string $tableName): bool
{
    $stmt = $conn->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('s', $tableName);
    $stmt->execute();
    $exists = (bool) $stmt->get_result()?->fetch_row();
    $stmt->close();

    return $exists;
}

function teacher_column_exists(mysqli $conn, string $tableName, string $columnName): bool
{
    $stmt = $conn->prepare('SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('ss', $tableName, $columnName);
    $stmt->execute();
    $exists = (bool) $stmt->get_result()?->fetch_row();
    $stmt->close();

    return $exists;
}

function teacher_skill_label(string $skill): string
{
    $skill = strtolower(trim($skill));

    return match ($skill) {
        'reading' => 'Reading',
        'listening' => 'Listening',
        'writing' => 'Writing',
        'speaking' => 'Speaking',
        default => $skill !== '' ? ucfirst($skill) : 'Chưa xác định',
    };
}

function teacher_status_meta(string $status): array
{
    $status = strtolower(trim($status));

    if ($status === 'answered') {
        return ['Trả lời', 'success'];
    }

    return ['Chờ trả lời', 'warning'];
}

function teacher_render_header(string $title, string $subtitle, string $activeSection): void
{
    global $teacherCurrentUserName, $teacherCurrentUserEmail, $teacherCurrentUserRole;

    $navClass = static function (string $section) use ($activeSection): string {
        return $section === $activeSection ? 'active' : '';
    };

    echo '<!doctype html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title><link href="../css/bootstrap.min.css" rel="stylesheet"><style>';
    echo ':root{--teacher-bg:#f5f7fb;--teacher-card:#ffffff;--teacher-primary:#5b6cff;--teacher-primary-2:#8b5cf6;--teacher-text:#1f2937;--teacher-muted:#6b7280;--teacher-border:#e5e7eb;}';
    echo 'body{background:var(--teacher-bg);color:var(--teacher-text);margin:0;}';
    echo 'header, .navbar, .site-header, .main-header, .main-nav, .topbar {display:none !important;}';
    echo '.teacher-shell{min-height:100vh;display:flex;}';
    echo '.teacher-sidebar{width:290px;flex:0 0 290px;background:linear-gradient(180deg,#111827 0%,#1f2937 100%);color:#fff;position:sticky;top:0;height:100vh;display:flex;flex-direction:column;box-shadow:0 20px 45px rgba(15,23,42,.18);}';
    echo '.teacher-brand{padding:28px 24px 18px;border-bottom:1px solid rgba(255,255,255,.08);}';
    echo '.teacher-brand h1{font-size:1.3rem;font-weight:800;margin:0;letter-spacing:.2px;}';
    echo '.teacher-brand p{margin:6px 0 0;color:rgba(255,255,255,.65);font-size:.92rem;}';
    echo '.teacher-nav{padding:18px 14px;display:flex;flex-direction:column;gap:8px;flex:1;}';
    echo '.teacher-nav a{color:rgba(255,255,255,.82);text-decoration:none;padding:13px 16px;border-radius:14px;display:flex;align-items:center;justify-content:space-between;font-weight:600;transition:all .2s ease;background:transparent;}';
    echo '.teacher-nav a:hover,.teacher-nav a.active{background:rgba(255,255,255,.1);color:#fff;transform:translateX(2px);}';
    echo '.teacher-nav .nav-pill{font-size:.72rem;padding:.25rem .55rem;border-radius:999px;background:rgba(255,255,255,.12);color:inherit;}';
    echo '.teacher-sidebar-footer{padding:18px 24px 24px;border-top:1px solid rgba(255,255,255,.08);}';
    echo '.teacher-sidebar-footer .mini{color:rgba(255,255,255,.72);font-size:.88rem;line-height:1.5;}';
    echo '.teacher-main{flex:1;min-width:0;}';
    echo '.teacher-topbar{position:sticky;top:0;z-index:10;background:rgba(245,247,251,.92);backdrop-filter:blur(12px);border-bottom:1px solid rgba(229,231,235,.9);}';
    echo '.teacher-topbar-inner{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:18px 28px;}';
    echo '.teacher-page-title{margin:0;font-size:1.45rem;font-weight:800;color:var(--teacher-text);}';
    echo '.teacher-subtitle{margin-top:4px;color:var(--teacher-muted);font-size:.95rem;}';
    echo '.teacher-content{padding:28px;}';
    echo '.teacher-card{background:var(--teacher-card);border:1px solid rgba(229,231,235,.9);border-radius:22px;box-shadow:0 18px 38px rgba(15,23,42,.06);}';
    echo '.teacher-stat{padding:22px;}';
    echo '.teacher-stat .label{font-size:.82rem;color:var(--teacher-muted);text-transform:uppercase;letter-spacing:.06em;font-weight:700;}';
    echo '.teacher-stat .value{font-size:2rem;font-weight:800;margin-top:12px;color:var(--teacher-text);}';
    echo '.teacher-table{margin-bottom:0;}';
    echo '.teacher-table th{white-space:nowrap;font-size:.85rem;color:#4b5563;background:#f9fafb;}';
    echo '.teacher-table td,.teacher-table th{padding:14px 16px;vertical-align:middle;}';
    echo '.teacher-table tbody tr:hover{background:#f8fbff;}';
    echo '.teacher-chip{display:inline-flex;align-items:center;gap:.4rem;padding:.38rem .7rem;border-radius:999px;font-size:.8rem;font-weight:700;}';
    echo '.teacher-chip.pending{background:rgba(249,115,22,.12);color:#c2410c;}';
    echo '.teacher-chip.answered{background:rgba(22,163,74,.12);color:#15803d;}';
    echo '.teacher-empty{padding:26px;text-align:center;color:var(--teacher-muted);}';
    echo '.teacher-grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:20px;}';
    echo '.teacher-col-8{grid-column:span 8;} .teacher-col-6{grid-column:span 6;} .teacher-col-4{grid-column:span 4;} .teacher-col-3{grid-column:span 3;}';
    echo '.teacher-section-title{font-size:1.05rem;font-weight:800;margin:0 0 14px;color:var(--teacher-text);}';
    echo '.teacher-muted{color:var(--teacher-muted);}';
    echo '.teacher-avatar{width:48px;height:48px;border-radius:16px;background:linear-gradient(135deg,var(--teacher-primary),var(--teacher-primary-2));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;}';
    echo '.teacher-panel{display:none;}';
    echo '@media (max-width: 991.98px){.teacher-shell{flex-direction:column;}.teacher-sidebar{position:relative;top:auto;width:100%;flex-basis:auto;height:auto;}.teacher-topbar-inner{padding:16px 18px;flex-direction:column;align-items:flex-start;}.teacher-content{padding:18px;}.teacher-grid{grid-template-columns:repeat(1,minmax(0,1fr));}.teacher-col-8,.teacher-col-6,.teacher-col-4{grid-column:span 1;}.teacher-nav{padding:12px;}}';
    echo '</style></head><body><div class="teacher-shell">';
    echo '<aside class="teacher-sidebar">';
    echo '<div class="teacher-brand"><h1>Tổng quan giảng viên</h1><p>' . htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') . '</p></div>';
    echo '<nav class="teacher-nav">';
    echo '<a class="' . $navClass('home') . '" href="../index.php"><span>Trang chủ</span><span class="nav-pill">01</span></a>';
    echo '<a class="' . $navClass('dashboard') . '" href="dashboard.php"><span>Tổng quan</span><span class="nav-pill">02</span></a>';
    echo '<a class="' . $navClass('students') . '" href="students.php"><span>Học sinh</span><span class="nav-pill">03</span></a>';
    echo '<a class="' . $navClass('lessons') . '" href="lessons.php"><span>Bài học/Bài thi</span><span class="nav-pill">04</span></a>';
    echo '<a class="' . $navClass('questions') . '" href="questions.php"><span>Thắc mắc</span><span class="nav-pill">05</span></a>';
    echo '<a class="' . $navClass('progress') . '" href="student_progress.php"><span>Tiến độ học sinh</span><span class="nav-pill">06</span></a>';
    echo '<a href="../logout.php"><span>Đăng xuất</span><span class="nav-pill">⎋</span></a>';
    echo '</nav>';
    echo '<div class="teacher-sidebar-footer"><div class="mini">Đăng nhập với vai trò giảng viên để quản lý học sinh, bài học và thắc mắc.</div></div>';
    echo '</aside>';
    echo '<div class="teacher-main">';
    echo '<div class="teacher-topbar"><div class="teacher-topbar-inner"><div><h2 class="teacher-page-title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2><div class="teacher-subtitle">' . htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') . '</div></div><div class="teacher-muted fw-semibold">' . htmlspecialchars($teacherCurrentUserName, ENT_QUOTES, 'UTF-8') . '</div></div></div>';
    echo '<main class="teacher-content">';
    echo '<div class="d-flex align-items-center gap-3 mb-4"><div class="teacher-avatar">' . htmlspecialchars(mb_substr($teacherCurrentUserName, 0, 1, 'UTF-8'), ENT_QUOTES, 'UTF-8') . '</div><div><div class="fw-bold">Xin chào ' . htmlspecialchars($teacherCurrentUserName, ENT_QUOTES, 'UTF-8') . '</div><div class="teacher-muted">' . htmlspecialchars($teacherCurrentUserEmail, ENT_QUOTES, 'UTF-8') . ' · Vai trò: Giảng viên</div></div></div>';
}

function teacher_render_footer(): void
{
    echo '</main></div></div></body></html>';
}