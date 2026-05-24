<?php
require_once __DIR__ . '/_layout.php';

admin_ensure_posts_table($conn);

$message = '';
$editingPost = null;
$statusFilter = trim((string) ($_GET['status'] ?? 'all'));
$searchText = trim((string) ($_GET['q'] ?? ''));

if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    if ($deleteId > 0) {
        $stmt = $conn->prepare('DELETE FROM site_posts WHERE id = ?');
        if ($stmt) {
            $stmt->bind_param('i', $deleteId);
            $stmt->execute();
            $stmt->close();
            $message = 'Đã xóa bài viết.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId = (int) ($_POST['post_id'] ?? 0);
    $title = trim((string) ($_POST['title'] ?? ''));
    $slug = trim((string) ($_POST['slug'] ?? ''));
    $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
    $content = trim((string) ($_POST['content'] ?? ''));
    $status = (string) ($_POST['status'] ?? 'draft');

    if ($title === '') {
        $message = 'Vui lòng nhập tiêu đề.';
    } else {
        $slug = $slug !== '' ? admin_slugify($slug) : admin_slugify($title);
        $status = in_array($status, ['draft', 'published'], true) ? $status : 'draft';

        if ($postId > 0) {
            $stmt = $conn->prepare('UPDATE site_posts SET title = ?, slug = ?, excerpt = ?, content = ?, status = ? WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('sssssi', $title, $slug, $excerpt, $content, $status, $postId);
                $stmt->execute();
                $stmt->close();
                $message = 'Đã cập nhật bài viết.';
            }
        } else {
            $stmt = $conn->prepare('INSERT INTO site_posts (title, slug, excerpt, content, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
            if ($stmt) {
                $stmt->bind_param('sssss', $title, $slug, $excerpt, $content, $status);
                $stmt->execute();
                $stmt->close();
                $message = 'Đã tạo bài viết.';
            }
        }
    }
}

if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = $conn->prepare('SELECT id, title, slug, excerpt, content, status FROM site_posts WHERE id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $editId);
        $stmt->execute();
        $editingPost = $stmt->get_result()?->fetch_assoc();
        $stmt->close();
    }
}

$posts = [];
$result = $conn->query('SELECT id, title, slug, status, created_at FROM site_posts ORDER BY id DESC');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
}

admin_render_header('Bài viết', 'posts', 'Tạo và quản lý bài viết nội dung');
if ($message):
?>
<div class="alert alert-info"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-5 mb-4">
        <div class="card content-card shadow h-100">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><?php echo $editingPost ? 'Sửa bài viết' : 'Bài viết mới'; ?></h6></div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="post_id" value="<?php echo (int) ($editingPost['id'] ?? 0); ?>">
                    <div class="form-group"><label>Tiêu đề</label><input class="form-control" name="title" value="<?php echo htmlspecialchars($editingPost['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required></div>
                    <div class="form-group"><label>Đường dẫn</label><input class="form-control" name="slug" value="<?php echo htmlspecialchars($editingPost['slug'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="tự tạo từ tiêu đề"></div>
                    <div class="form-group"><label>Tóm tắt</label><textarea class="form-control" name="excerpt" rows="3"><?php echo htmlspecialchars($editingPost['excerpt'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea></div>
                    <div class="form-group"><label>Nội dung</label><textarea class="form-control" name="content" rows="8"><?php echo htmlspecialchars($editingPost['content'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea></div>
                    <div class="form-group"><label>Trạng thái</label><select class="form-control" name="status"><option value="draft"<?php echo (($editingPost['status'] ?? 'draft') === 'draft') ? ' selected' : ''; ?>>Bản nháp</option><option value="published"<?php echo (($editingPost['status'] ?? '') === 'published') ? ' selected' : ''; ?>>Đã xuất bản</option></select></div>
                    <button class="btn btn-primary"><?php echo $editingPost ? 'Cập nhật' : 'Tạo mới'; ?></button>
                    <?php if ($editingPost): ?><a class="btn btn-link" href="posts.php">Hủy</a><?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7 mb-4">
        <div class="card content-card shadow h-100">
            <div class="card-header py-3 d-flex justify-content-between align-items-center"><h6 class="m-0 font-weight-bold text-primary">Danh sách bài viết</h6><form class="form-inline" method="get"><input type="text" name="q" value="<?php echo htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8'); ?>" class="form-control form-control-sm mr-2" placeholder="Tìm theo tiêu đề"><select name="status" class="form-control form-control-sm mr-2"><option value="all"<?php echo $statusFilter === 'all' ? ' selected' : ''; ?>>Tất cả</option><option value="draft"<?php echo $statusFilter === 'draft' ? ' selected' : ''; ?>>Bản nháp</option><option value="published"<?php echo $statusFilter === 'published' ? ' selected' : ''; ?>>Đã xuất bản</option></select><button class="btn btn-sm btn-primary">Lọc</button></form></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead><tr><th>Tiêu đề</th><th>Trạng thái</th><th>Ngày tạo</th><th>Thao tác</th></tr></thead>
                        <tbody>
                            <?php if (!$posts): ?>
                                <tr><td colspan="4" class="text-center text-muted">Chưa có bài viết nào.</td></tr>
                            <?php else: ?>
                                <?php foreach ($posts as $post): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($post['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <div class="small text-muted"><?php echo htmlspecialchars($post['slug'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                                        </td>
                                        <td><span class="badge badge-<?php echo (($post['status'] ?? 'draft') === 'published') ? 'success' : 'secondary'; ?>"><?php echo (($post['status'] ?? 'draft') === 'published') ? 'Đã xuất bản' : 'Bản nháp'; ?></span></td>
                                        <td><?php echo htmlspecialchars($post['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <a class="btn btn-sm btn-info" href="posts.php?edit=<?php echo (int) $post['id']; ?>">Sửa</a>
                                            <a class="btn btn-sm btn-danger" href="posts.php?delete=<?php echo (int) $post['id']; ?>" onclick="return confirm('Xóa bài viết này?');">Xóa</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php admin_render_footer(); ?>
