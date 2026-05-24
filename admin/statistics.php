<?php
require_once __DIR__ . '/_layout.php';

$stats = [
    'users' => 0,
    'admins' => 0,
    'students' => 0,
    'contacts' => 0,
    'unread_contacts' => 0,
    'sessions' => 0,
    'posts' => 0,
];

$result = $conn->query('SELECT COUNT(*) AS total, SUM(CASE WHEN role = 1 THEN 1 ELSE 0 END) AS admins, SUM(CASE WHEN role = 2 THEN 1 ELSE 0 END) AS students FROM users');
if ($result) {
    $row = $result->fetch_assoc() ?: [];
    $stats['users'] = (int) ($row['total'] ?? 0);
    $stats['admins'] = (int) ($row['admins'] ?? 0);
    $stats['students'] = (int) ($row['students'] ?? 0);
}

$result = $conn->query('SELECT COUNT(*) AS total, SUM(CASE WHEN COALESCE(is_read,0) = 0 THEN 1 ELSE 0 END) AS unread FROM contact_messages');
if ($result) {
    $row = $result->fetch_assoc() ?: [];
    $stats['contacts'] = (int) ($row['total'] ?? 0);
    $stats['unread_contacts'] = (int) ($row['unread'] ?? 0);
}

streak_ensure_tables($conn);
$result = $conn->query('SELECT COUNT(*) AS total FROM study_sessions');
if ($result) {
    $row = $result->fetch_assoc() ?: [];
    $stats['sessions'] = (int) ($row['total'] ?? 0);
}

admin_ensure_posts_table($conn);
$result = $conn->query('SELECT COUNT(*) AS total FROM site_posts');
if ($result) {
    $row = $result->fetch_assoc() ?: [];
    $stats['posts'] = (int) ($row['total'] ?? 0);
}

$recentSessions = [];
admin_ensure_feedback_status_column($conn);
$result = $conn->query('SELECT skill, activity_type, score, max_score, band_score, duration_minutes, created_at FROM study_sessions ORDER BY id DESC LIMIT 8');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentSessions[] = $row;
    }
}

admin_render_header('Statistics', 'statistics', 'Overview of platform activity');
?>
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4"><div class="card border-left-primary shadow h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Users</div><div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['users']; ?></div></div></div></div>
    <div class="col-xl-3 col-md-6 mb-4"><div class="card border-left-success shadow h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-success text-uppercase mb-1">Contacts</div><div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['contacts']; ?></div></div></div></div>
    <div class="col-xl-3 col-md-6 mb-4"><div class="card border-left-info shadow h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-info text-uppercase mb-1">Study sessions</div><div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['sessions']; ?></div></div></div></div>
    <div class="col-xl-3 col-md-6 mb-4"><div class="card border-left-warning shadow h-100 py-2"><div class="card-body"><div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Posts</div><div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['posts']; ?></div></div></div></div>
</div>

<div class="row mb-4">
    <div class="col-lg-6 mb-4"><div class="card content-card shadow h-100"><div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">User breakdown</h6></div><div class="card-body"><p class="mb-2">Admins: <strong><?php echo $stats['admins']; ?></strong></p><p class="mb-0">Students: <strong><?php echo $stats['students']; ?></strong></p></div></div></div>
    <div class="col-lg-6 mb-4"><div class="card content-card shadow h-100"><div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Feedback status</h6></div><div class="card-body"><p class="mb-2">Unread feedback: <strong><?php echo $stats['unread_contacts']; ?></strong></p><p class="mb-0 text-muted">Mark messages as read from the Feedback page.</p></div></div></div>
</div>

<div class="card content-card shadow mb-4">
    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Recent study sessions</h6></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead><tr><th>Skill</th><th>Activity</th><th>Score</th><th>Band</th><th>Duration</th><th>Date</th></tr></thead>
                <tbody>
                    <?php if (!$recentSessions): ?>
                        <tr><td colspan="6" class="text-center text-muted">No sessions yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentSessions as $session): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($session['skill'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($session['activity_type'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string) ($session['score'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>/<?php echo htmlspecialchars((string) ($session['max_score'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string) ($session['band_score'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string) ($session['duration_minutes'] ?? ''), ENT_QUOTES, 'UTF-8'); ?> min</td>
                                <td><?php echo htmlspecialchars($session['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php admin_render_footer(); ?>
