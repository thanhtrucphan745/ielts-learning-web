<?php
require_once __DIR__ . '/_layout.php';

admin_ensure_posts_table($conn);
admin_ensure_feedback_status_column($conn);
streak_ensure_tables($conn);

$counts = [
	'users' => 0,
	'admins' => 0,
	'students' => 0,
	'teachers' => 0,
	'posts' => 0,
	'feedback' => 0,
	'unread_feedback' => 0,
	'sessions' => 0,
];

$result = $conn->query('SELECT COUNT(*) AS total, SUM(CASE WHEN role = 1 THEN 1 ELSE 0 END) AS admins, SUM(CASE WHEN role = 2 THEN 1 ELSE 0 END) AS students, SUM(CASE WHEN role = 3 THEN 1 ELSE 0 END) AS teachers FROM users');
if ($result) {
	$row = $result->fetch_assoc() ?: [];
	$counts['users'] = (int) ($row['total'] ?? 0);
	$counts['admins'] = (int) ($row['admins'] ?? 0);
	$counts['students'] = (int) ($row['students'] ?? 0);
	$counts['teachers'] = (int) ($row['teachers'] ?? 0);
}

$result = $conn->query('SELECT COUNT(*) AS total FROM site_posts');
if ($result) {
	$row = $result->fetch_assoc() ?: [];
	$counts['posts'] = (int) ($row['total'] ?? 0);
}

$result = $conn->query('SELECT COUNT(*) AS total, SUM(CASE WHEN COALESCE(is_read,0) = 0 THEN 1 ELSE 0 END) AS unread FROM contact_messages');
if ($result) {
	$row = $result->fetch_assoc() ?: [];
	$counts['feedback'] = (int) ($row['total'] ?? 0);
	$counts['unread_feedback'] = (int) ($row['unread'] ?? 0);
}

$result = $conn->query('SELECT COUNT(*) AS total FROM study_sessions');
if ($result) {
	$row = $result->fetch_assoc() ?: [];
	$counts['sessions'] = (int) ($row['total'] ?? 0);
}

admin_render_header('Trang quản trị', 'dashboard', 'Truy cập nhanh các khu vực quản lý');
?>
<div class="row">
	<div class="col-xl-3 col-md-6 mb-4">
		<div class="card border-left-primary shadow h-100 py-2">
			<div class="card-body">
				<div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Người dùng</div>
				<div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $counts['users']; ?></div>
			</div>
		</div>
	</div>
	<div class="col-xl-3 col-md-6 mb-4">
		<div class="card border-left-success shadow h-100 py-2">
			<div class="card-body">
				<div class="text-xs font-weight-bold text-success text-uppercase mb-1">Bài viết</div>
				<div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $counts['posts']; ?></div>
			</div>
		</div>
	</div>
	<div class="col-xl-3 col-md-6 mb-4">
		<div class="card border-left-info shadow h-100 py-2">
			<div class="card-body">
				<div class="text-xs font-weight-bold text-info text-uppercase mb-1">Phản hồi</div>
				<div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $counts['feedback']; ?></div>
			</div>
		</div>
	</div>
	<div class="col-xl-3 col-md-6 mb-4">
		<div class="card border-left-warning shadow h-100 py-2">
			<div class="card-body">
				<div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Lượt học</div>
				<div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $counts['sessions']; ?></div>
			</div>
		</div>
	</div>
</div>

<div class="row mb-4">
	<div class="col-lg-6 mb-4">
		<div class="card content-card shadow h-100">
			<div class="card-header py-3">
				<h6 class="m-0 font-weight-bold text-primary">Quản lý người dùng và tài khoản</h6>
			</div>
			<div class="card-body">
				<p class="mb-2">Tổng người dùng: <strong><?php echo $counts['users']; ?></strong></p>
				<p class="mb-2">Quản trị viên: <strong><?php echo $counts['admins']; ?></strong></p>
				<p class="mb-2">Học viên: <strong><?php echo $counts['students']; ?></strong></p>
				<p class="mb-3">Giảng viên: <strong><?php echo $counts['teachers']; ?></strong></p>
				<a href="users.php" class="btn btn-primary btn-sm mr-2">Người dùng</a>
				<a href="accounts.php" class="btn btn-outline-primary btn-sm">Tài khoản</a>
			</div>
		</div>
	</div>
	<div class="col-lg-6 mb-4">
		<div class="card content-card shadow h-100">
			<div class="card-header py-3">
				<h6 class="m-0 font-weight-bold text-primary">Nội dung và phản hồi</h6>
			</div>
			<div class="card-body">
				<p class="mb-2">Bài viết: <strong><?php echo $counts['posts']; ?></strong></p>
				<p class="mb-2">Phản hồi: <strong><?php echo $counts['feedback']; ?></strong></p>
				<p class="mb-3">Chưa đọc: <strong><?php echo $counts['unread_feedback']; ?></strong></p>
				<a href="posts.php" class="btn btn-success btn-sm mr-2">Bài viết</a>
				<a href="feedback.php" class="btn btn-outline-success btn-sm">Phản hồi</a>
			</div>
		</div>
	</div>
</div>

<div class="card content-card shadow mb-4">
	<div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
		<h6 class="m-0 font-weight-bold text-primary">Điều hướng nhanh</h6>
		<a href="statistics.php" class="btn btn-sm btn-info">Xem thống kê</a>
	</div>
	<div class="card-body">
		<div class="row">
			<div class="col-md-4 mb-3">
				<a href="users.php" class="card border-left-primary h-100 text-decoration-none">
					<div class="card-body">
						<div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Người dùng</div>
						<div class="text-gray-800">Danh sách, tìm kiếm, sửa, xóa</div>
					</div>
				</a>
			</div>
			<div class="col-md-4 mb-3">
				<a href="posts.php" class="card border-left-success h-100 text-decoration-none">
					<div class="card-body">
						<div class="text-xs font-weight-bold text-success text-uppercase mb-1">Bài viết</div>
						<div class="text-gray-800">Tạo, sửa, xuất bản, xóa</div>
					</div>
				</a>
			</div>
			<div class="col-md-4 mb-3">
				<a href="feedback.php" class="card border-left-warning h-100 text-decoration-none">
					<div class="card-body">
						<div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Phản hồi</div>
						<div class="text-gray-800">Đọc, đánh dấu, xóa tin nhắn</div>
					</div>
				</a>
			</div>
		</div>
	</div>
</div>
<?php admin_render_footer(); ?>
