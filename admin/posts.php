<?php
require_once __DIR__ . '/_layout.php';

admin_ensure_posts_table($conn);

$message = '';
$editingPost = null;

if (isset($_GET['delete'])) {
    $deleteId = (int) $_GET['delete'];
    if ($deleteId > 0) {
        $stmt = $conn->prepare('DELETE FROM site_posts WHERE id = ?');
        if ($stmt) {
            $stmt->bind_param('i', $deleteId);
            $stmt->execute();
            $stmt->close();
            $message = 'Post deleted.';
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
        $message = 'Title is required.';
    } else {
        $slug = $slug !== '' ? admin_slugify($slug) : admin_slugify($title);
        $status = in_array($status, ['draft', 'published'], true) ? $status : 'draft';

        if ($postId > 0) {
            $stmt = $conn->prepare('UPDATE site_posts SET title = ?, slug = ?, excerpt = ?, content = ?, status = ? WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('sssssi', $title, $slug, $excerpt, $content, $status, $postId);
                $stmt->execute();
                $stmt->close();
                $message = 'Post updated.';
            }
        } else {
            $stmt = $conn->prepare('INSERT INTO site_posts (title, slug, excerpt, content, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
            if ($stmt) {
                $stmt->bind_param('sssss', $title, $slug, $excerpt, $content, $status);
                $stmt->execute();
                $stmt->close();
                $message = 'Post created.';
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

admin_render_header('Posts Management', 'posts', 'Create and manage content articles');
if ($message):
?>
<div class="alert alert-info"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-5 mb-4">
        <div class="card content-card shadow h-100">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><?php echo $editingPost ? 'Edit post' : 'New post'; ?></h6></div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="post_id" value="<?php echo (int) ($editingPost['id'] ?? 0); ?>">
                    <div class="form-group"><label>Title</label><input class="form-control" name="title" value="<?php echo htmlspecialchars($editingPost['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required></div>
                    <div class="form-group"><label>Slug</label><input class="form-control" name="slug" value="<?php echo htmlspecialchars($editingPost['slug'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="auto-from-title"></div>
                    <div class="form-group"><label>Excerpt</label><textarea class="form-control" name="excerpt" rows="3"><?php echo htmlspecialchars($editingPost['excerpt'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea></div>
                    <div class="form-group"><label>Content</label><textarea class="form-control" name="content" rows="8"><?php echo htmlspecialchars($editingPost['content'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea></div>
                    <div class="form-group"><label>Status</label><select class="form-control" name="status"><option value="draft"<?php echo (($editingPost['status'] ?? 'draft') === 'draft') ? ' selected' : ''; ?>>Draft</option><option value="published"<?php echo (($editingPost['status'] ?? '') === 'published') ? ' selected' : ''; ?>>Published</option></select></div>
                    <button class="btn btn-primary"><?php echo $editingPost ? 'Update post' : 'Create post'; ?></button>
                    <?php if ($editingPost): ?><a class="btn btn-link" href="posts.php">Cancel</a><?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7 mb-4">
        <div class="card content-card shadow h-100">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">All posts</h6></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead><tr><th>Title</th><th>Status</th><th>Created</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php if (!$posts): ?>
                                <tr><td colspan="4" class="text-center text-muted">No posts yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($posts as $post): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($post['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <div class="small text-muted"><?php echo htmlspecialchars($post['slug'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                                        </td>
                                        <td><span class="badge badge-<?php echo (($post['status'] ?? 'draft') === 'published') ? 'success' : 'secondary'; ?>"><?php echo htmlspecialchars($post['status'] ?? 'draft', ENT_QUOTES, 'UTF-8'); ?></span></td>
                                        <td><?php echo htmlspecialchars($post['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <a class="btn btn-sm btn-info" href="posts.php?edit=<?php echo (int) $post['id']; ?>">Edit</a>
                                            <a class="btn btn-sm btn-danger" href="posts.php?delete=<?php echo (int) $post['id']; ?>" onclick="return confirm('Delete this post?');">Delete</a>
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
