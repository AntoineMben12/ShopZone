<?php
// pages/blog/index.php  — Public blog feed
require_once '../../database/database.php';
require_once '../includes/auth.php';

$db  = getDB();
$tab = in_array($_GET['tab'] ?? '', ['popular']) ? 'popular' : 'latest';

// Pagination
$perPage = 8;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$orderBy = $tab === 'popular'
    ? "ORDER BY like_count DESC, p.created_at DESC"
    : "ORDER BY p.created_at DESC";

$total = (int)$db->query("SELECT COUNT(*) FROM posts WHERE status='published'")->fetchColumn();
$pages = (int)ceil($total / $perPage);

$st = $db->prepare("
    SELECT p.*,
           u.name AS author_name,
           (SELECT COUNT(*) FROM likes    WHERE post_id = p.id) AS like_count,
           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comment_count
    FROM posts p
    JOIN users u ON u.id = p.user_id
    WHERE p.status = 'published'
    $orderBy
    LIMIT $perPage OFFSET $offset
");
$st->execute();
$posts = $st->fetchAll();

$pageTitle = 'Blog';
$depth     = '../';
require_once '../includes/header.php';
require_once '../includes/navBar.php';
?>

<style>
/* ── Blog Feed Styles ──────────────────────────────────── */
.blog-hero {
    background: linear-gradient(135deg, var(--bg2) 0%, var(--bg) 100%);
    border-bottom: 1px solid var(--border);
    padding: 3.5rem 0 2.5rem;
    text-align: center;
}
.blog-hero h1 {
    font-size: clamp(2rem, 5vw, 3rem);
    font-weight: 800;
    background: linear-gradient(135deg, var(--text) 0%, var(--accent) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: .5rem;
}
.blog-hero p { color:var(--text-muted); font-size:1.05rem; max-width:520px; margin:0 auto; }
.tab-bar {
    display:flex; gap:.5rem;
    border-bottom:1px solid var(--border);
    margin-bottom: 2rem;
}
.tab-btn {
    background:none; border:none; padding:.6rem 1.2rem;
    font-size:.9rem; font-weight:600; color:var(--text-muted);
    border-bottom:3px solid transparent; cursor:pointer;
    transition:color .2s, border-color .2s;
}
.tab-btn:hover { color:var(--text); }
.tab-btn.active { color:var(--accent); border-bottom-color:var(--accent); }
.blog-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
}
.blog-card {
    background:var(--bg2);
    border:1px solid var(--border);
    border-radius:var(--radius);
    overflow:hidden;
    transition:transform .22s ease, box-shadow .22s ease, border-color .22s ease;
    display:flex; flex-direction:column;
}
.blog-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(0,0,0,.25);
    border-color:var(--accent);
}
.blog-card-img {
    width:100%; height:200px; object-fit:cover;
    background:var(--bg);
}
.blog-card-img-placeholder {
    width:100%; height:200px;
    background:linear-gradient(135deg,var(--bg),var(--bg2));
    display:flex; align-items:center; justify-content:center;
    font-size:3rem; opacity:.4;
}
.blog-card-body { padding:1.2rem; flex:1; display:flex; flex-direction:column; }
.blog-card-meta { display:flex; align-items:center; gap:.6rem; margin-bottom:.75rem; }
.blog-avatar {
    width:30px; height:30px; border-radius:50%;
    background:var(--accent); display:inline-flex;
    align-items:center; justify-content:center;
    font-weight:700; font-size:.75rem; color:#000; flex-shrink:0;
}
.blog-card-author { font-size:.78rem; color:var(--text-muted); }
.blog-card-title {
    font-size:1.05rem; font-weight:700; color:var(--text);
    margin-bottom:.5rem; line-height:1.4;
    text-decoration:none; display:block;
}
.blog-card-title:hover { color:var(--accent); }
.blog-card-excerpt { font-size:.85rem; color:var(--text-muted); line-height:1.6; flex:1; }
.blog-card-footer {
    display:flex; align-items:center; justify-content:space-between;
    margin-top:1rem; padding-top:.75rem;
    border-top:1px solid var(--border);
    font-size:.78rem; color:var(--text-muted);
}
.blog-stat { display:flex; align-items:center; gap:.3rem; }
.blog-cta { color:var(--accent); font-weight:600; text-decoration:none; font-size:.83rem; }
.blog-cta:hover { text-decoration:underline; }
.empty-state { text-align:center; padding:4rem 1rem; color:var(--text-muted); }
.empty-state i { font-size:3rem; display:block; margin-bottom:1rem; opacity:.4; }
</style>

<!-- Hero -->
<div class="blog-hero">
  <div class="container">
    <h1>✏️ ShopZone Blog</h1>
    <p>Discover stories, product highlights, and expert guides from our community.</p>
  </div>
</div>

<div class="container py-5">

  <!-- Tab bar -->
  <div class="tab-bar">
    <a href="?tab=latest" class="tab-btn <?= $tab === 'latest' ? 'active' : '' ?>">🕐 Latest</a>
    <a href="?tab=popular" class="tab-btn <?= $tab === 'popular' ? 'active' : '' ?>">🔥 Most Popular</a>
  </div>

  <?php if (empty($posts)): ?>
    <div class="empty-state">
      <i class="bi bi-journal-x"></i>
      <h4>No posts yet.</h4>
      <p>Check back soon — great content is on the way!</p>
      <?php if (isAdmin()): ?>
        <a href="/e-commerce/pages/admin/addPost.php" class="btn btn-accent mt-2">
          <i class="bi bi-pencil-square me-1"></i>Write the First Post
        </a>
      <?php endif; ?>
    </div>
  <?php else: ?>

    <div class="blog-grid">
      <?php foreach ($posts as $p): ?>
        <article class="blog-card">

          <?php if ($p['image']): ?>
            <img class="blog-card-img"
                 src="/e-commerce/<?= htmlspecialchars($p['image']) ?>"
                 alt="<?= htmlspecialchars($p['title']) ?>"
                 onerror="this.style.display='none'">
          <?php else: ?>
            <div class="blog-card-img-placeholder">📝</div>
          <?php endif; ?>

          <div class="blog-card-body">
            <div class="blog-card-meta">
              <span class="blog-avatar"><?= strtoupper(mb_substr($p['author_name'], 0, 1)) ?></span>
              <span class="blog-card-author">
                <?= htmlspecialchars($p['author_name']) ?>
                &nbsp;·&nbsp;
                <?= date('M j, Y', strtotime($p['created_at'])) ?>
              </span>
            </div>

            <a class="blog-card-title" href="/e-commerce/pages/blog/post.php?slug=<?= urlencode($p['slug']) ?>">
              <?= htmlspecialchars($p['title']) ?>
            </a>

            <p class="blog-card-excerpt">
              <?= htmlspecialchars(mb_substr($p['excerpt'], 0, 130)) ?><?= strlen($p['excerpt']) > 130 ? '…' : '' ?>
            </p>

            <div class="blog-card-footer">
              <div class="d-flex gap-3">
                <span class="blog-stat">
                  <i class="bi bi-heart-fill" style="color:var(--danger)"></i>
                  <?= $p['like_count'] ?>
                </span>
                <span class="blog-stat">
                  <i class="bi bi-chat-fill" style="color:var(--info)"></i>
                  <?= $p['comment_count'] ?>
                </span>
              </div>
              <a class="blog-cta" href="/e-commerce/pages/blog/post.php?slug=<?= urlencode($p['slug']) ?>">
                Read more <i class="bi bi-arrow-right"></i>
              </a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
      <div class="d-flex justify-content-center gap-1 mt-5">
        <?php if ($page > 1): ?>
          <a href="?tab=<?= $tab ?>&page=<?= $page-1 ?>" class="btn btn-ghost">
            <i class="bi bi-chevron-left"></i>
          </a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $pages; $i++): ?>
          <a href="?tab=<?= $tab ?>&page=<?= $i ?>"
             class="btn btn-sm <?= $i === $page ? 'btn-accent' : 'btn-ghost' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $pages): ?>
          <a href="?tab=<?= $tab ?>&page=<?= $page+1 ?>" class="btn btn-ghost">
            <i class="bi bi-chevron-right"></i>
          </a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

  <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
