<?php
// pages/admin/blog.php
// Admin: list and manage all blog posts.
require_once '../../database/database.php';
require_once '../includes/auth.php';
requireAdmin('../auth/login.php');

$db = getDB();

// Handle quick status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    $tid = (int)$_POST['toggle_id'];
    $db->prepare("UPDATE posts SET status = IF(status='published','draft','published') WHERE id = ?")->execute([$tid]);
    setFlashMessage('Post status updated.');
    header('Location: blog.php');
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    // Remove image file if exists
    $img = $db->prepare("SELECT image FROM posts WHERE id = ?");
    $img->execute([$delId]);
    $imgPath = $img->fetchColumn();
    if ($imgPath && file_exists(__DIR__ . '/../../' . $imgPath)) {
        unlink(__DIR__ . '/../../' . $imgPath);
    }
    $db->prepare("DELETE FROM posts WHERE id = ?")->execute([$delId]);
    setFlashMessage('Post deleted.');
    header('Location: blog.php');
    exit;
}

// Pagination
$perPage = 10;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;
$total   = (int)$db->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$pages   = (int)ceil($total / $perPage);

$posts = $db->prepare("
    SELECT p.*, u.name AS author_name,
           (SELECT COUNT(*) FROM likes    WHERE post_id = p.id) AS like_count,
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comment_count
    FROM posts p
    JOIN users u ON u.id = p.user_id
    ORDER BY p.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$posts->execute();
$posts = $posts->fetchAll();

$pageTitle = 'Blog Posts';
$depth = '../';
require_once '../includes/header.php';
require_once '../includes/navBar.php';
?>

<div class="container-fluid py-4 px-4">
  <?php renderFlash(); ?>

  <div class="row g-4">
    <!-- Sidebar -->
    <div class="col-xl-2 col-lg-3">
      <div class="dash-sidebar">
        <div class="user-info d-flex align-items-center gap-2">
          <div class="user-avatar" style="background:var(--danger)">A</div>
          <div>
            <div style="font-weight:600;font-size:.88rem"><?= currentUserName() ?></div>
            <span class="badge-role-admin">Admin</span>
          </div>
        </div>
        <nav class="nav flex-column gap-1" style="font-size:.85rem">
          <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
          <a class="nav-link" href="product.php"><i class="bi bi-box"></i> Products</a>
          <a class="nav-link" href="addProduct.php"><i class="bi bi-plus-circle"></i> Add Product</a>
          <a class="nav-link" href="users.php"><i class="bi bi-people"></i> Users</a>
          <a class="nav-link" href="orders.php"><i class="bi bi-bag-check"></i> Orders</a>
          <hr style="border-color:var(--border);margin:.4rem 0">
          <a class="nav-link active" href="blog.php"><i class="bi bi-journal-text"></i> Blog Posts</a>
          <a class="nav-link" href="addPost.php"><i class="bi bi-pencil-square"></i> Add Post</a>
          <hr style="border-color:var(--border);margin:.4rem 0">
          <a class="nav-link" href="../../index.php"><i class="bi bi-house"></i> View Site</a>
          <a class="nav-link" href="../auth/logout.php" style="color:var(--danger)!important">
            <i class="bi bi-box-arrow-right"></i> Logout
          </a>
        </nav>
      </div>
    </div>

    <!-- Main -->
    <div class="col-xl-10 col-lg-9">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h1 class="section-title mb-0">Blog Posts</h1>
          <p style="color:var(--text-muted);font-size:.85rem;margin-top:.2rem"><?= $total ?> posts total</p>
        </div>
        <a href="addPost.php" class="btn btn-accent">
          <i class="bi bi-pencil-square me-1"></i>New Post
        </a>
      </div>

      <div class="card-dark p-3">
        <div class="table-responsive">
          <table class="table table-dark-custom mb-0" style="font-size:.85rem">
            <thead>
              <tr>
                <th>#</th>
                <th>Title</th>
                <th>Author</th>
                <th>Status</th>
                <th style="text-align:center">❤️</th>
                <th style="text-align:center">💬</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($posts)): ?>
                <tr>
                  <td colspan="8" style="text-align:center;color:var(--text-muted);padding:2rem">No posts yet. <a href="addPost.php">Create the first one →</a></td>
                </tr>
              <?php endif; ?>
              <?php foreach ($posts as $p): ?>
                <tr>
                  <td><strong>#<?= $p['id'] ?></strong></td>
                  <td>
                    <div style="font-weight:500;max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                      <?= htmlspecialchars($p['title']) ?>
                    </div>
                    <small style="color:var(--text-muted)">/<?= htmlspecialchars($p['slug']) ?></small>
                  </td>
                  <td><?= htmlspecialchars($p['author_name']) ?></td>
                  <td>
                    <form method="POST" style="display:inline">
                      <input type="hidden" name="toggle_id" value="<?= $p['id'] ?>">
                      <button type="submit" class="badge-status badge-<?= $p['status'] === 'published' ? 'completed' : 'pending' ?>"
                              style="border:none;cursor:pointer;background:none;padding:0">
                        <?= $p['status'] === 'published'
                          ? '<span style="color:var(--success);font-weight:600">● Published</span>'
                          : '<span style="color:var(--text-muted);font-weight:600">○ Draft</span>' ?>
                      </button>
                    </form>
                  </td>
                  <td style="text-align:center;color:var(--danger)"><?= $p['like_count'] ?></td>
                  <td style="text-align:center;color:var(--info)"><?= $p['comment_count'] ?></td>
                  <td style="color:var(--text-muted);white-space:nowrap"><?= date('M j, Y', strtotime($p['created_at'])) ?></td>
                  <td style="white-space:nowrap">
                    <a href="addPost.php?edit=<?= $p['id'] ?>" class="btn btn-ghost btn-sm me-1">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <a href="/e-commerce/pages/blog/post.php?slug=<?= urlencode($p['slug']) ?>" class="btn btn-ghost btn-sm me-1" target="_blank">
                      <i class="bi bi-eye"></i>
                    </a>
                    <a href="blog.php?delete=<?= $p['id'] ?>"
                       class="btn btn-sm"
                       style="background:rgba(231,76,60,.15);color:var(--danger)"
                       onclick="return confirm('Delete this post and all its comments?')">
                      <i class="bi bi-trash"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <?php if ($pages > 1): ?>
        <div class="d-flex justify-content-center gap-1 mt-3">
          <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a href="?page=<?= $i ?>" class="btn btn-sm <?= $i === $page ? 'btn-accent' : 'btn-ghost' ?>"><?= $i ?></a>
          <?php endfor; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
