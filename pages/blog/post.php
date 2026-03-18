<?php
// pages/blog/post.php  — Single post view with comments, likes, and product ad sidebar
require_once '../../database/database.php';
require_once '../includes/auth.php';

$db   = getDB();
$slug = trim($_GET['slug'] ?? '');

if (!$slug) {
    header('Location: index.php');
    exit;
}

// Fetch post
$st = $db->prepare("
    SELECT p.*, u.name AS author_name
    FROM posts p
    JOIN users u ON u.id = p.user_id
    WHERE p.slug = ? AND p.status = 'published'
");
$st->execute([$slug]);
$post = $st->fetch();

if (!$post) {
    http_response_code(404);
    $pageTitle = 'Post Not Found';
    $depth = '../';
    require_once '../includes/header.php';
    require_once '../includes/navBar.php';
    echo '<div class="container py-5 text-center"><h2>Post not found.</h2><a href="index.php" class="btn btn-ghost mt-3">← Back to Blog</a></div>';
    require_once '../includes/footer.php';
    exit;
}

$postId = (int)$post['id'];

// Like count & whether current user has liked it
$likeCount   = (int)$db->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?")->execute([$postId]) ? 0 : 0;
$stLikes = $db->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
$stLikes->execute([$postId]);
$likeCount = (int)$stLikes->fetchColumn();

$userLiked = false;
if (isLoggedIn()) {
    $stUL = $db->prepare("SELECT 1 FROM likes WHERE user_id = ? AND post_id = ?");
    $stUL->execute([currentUserId(), $postId]);
    $userLiked = (bool)$stUL->fetchColumn();
}

// Top-level comments with replies
$stComments = $db->prepare("
    SELECT c.*, u.name AS author_name
    FROM comments c
    JOIN users u ON u.id = c.user_id
    WHERE c.post_id = ? AND c.parent_id IS NULL
    ORDER BY c.created_at DESC
");
$stComments->execute([$postId]);
$topComments = $stComments->fetchAll();

// Replies keyed by parent_id
$stReplies = $db->prepare("
    SELECT c.*, u.name AS author_name
    FROM comments c
    JOIN users u ON u.id = c.user_id
    WHERE c.post_id = ? AND c.parent_id IS NOT NULL
    ORDER BY c.created_at ASC
");
$stReplies->execute([$postId]);
$replies = [];
foreach ($stReplies->fetchAll() as $r) {
    $replies[(int)$r['parent_id']][] = $r;
}

// Attached product advertisements
$stAds = $db->prepare("
    SELECT p.id, p.name, p.price, p.image, p.description
    FROM post_products pp
    JOIN products p ON p.id = pp.product_id
    WHERE pp.post_id = ?
    ORDER BY pp.sort_order
    LIMIT 3
");
$stAds->execute([$postId]);
$adProducts = $stAds->fetchAll();

// Helper: time ago
function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return (int)($diff/60)   . ' min ago';
    if ($diff < 86400)  return (int)($diff/3600)  . ' hr ago';
    if ($diff < 604800) return (int)($diff/86400) . ' days ago';
    return date('M j, Y', strtotime($datetime));
}

$pageTitle = $post['title'];
$depth     = '../';
require_once '../includes/header.php';
require_once '../includes/navBar.php';
?>

<style>
/* ── Post Layout ─────────────────────────────────────────── */
.post-layout {
    display:grid;
    grid-template-columns: 1fr 340px;
    gap:2rem;
    align-items:start;
    max-width:1200px;
    margin:0 auto;
    padding:2.5rem 1rem 4rem;
}
@media(max-width:900px) {
    .post-layout { grid-template-columns:1fr; }
    .post-sidebar { order:-1; }
}

/* ── Featured Image Hero ─────────────────────────────────── */
.post-hero {
    width:100%; max-height:420px; object-fit:cover;
    border-radius:var(--radius); margin-bottom:1.75rem;
    display:block;
}

/* ── Post Meta ──────────────────────────────────────────── */
.post-meta { display:flex; align-items:center; gap:.8rem; margin-bottom:1rem; flex-wrap:wrap; }
.post-avatar {
    width:40px; height:40px; border-radius:50%;
    background:var(--accent); display:inline-flex;
    align-items:center; justify-content:center;
    font-weight:800; font-size:.9rem; color:#000; flex-shrink:0;
}
.post-author-name { font-weight:600; font-size:.9rem; color:var(--text); }
.post-date { font-size:.82rem; color:var(--text-muted); }
.post-title {
    font-size:clamp(1.6rem,4vw,2.4rem);
    font-weight:800; line-height:1.3;
    margin-bottom:1.5rem; color:var(--text);
}
.post-excerpt {
    font-size:1.05rem; color:var(--text-muted);
    border-left:4px solid var(--accent);
    padding-left:1rem; margin-bottom:1.75rem;
    font-style:italic; line-height:1.7;
}

/* ── Post Content ───────────────────────────────────────── */
.post-content {
    font-size:1.01rem; line-height:1.85; color:var(--text);
    border-bottom:1px solid var(--border); padding-bottom:2rem; margin-bottom:2rem;
}
.post-content h2,.post-content h3,.post-content h4 {
    color:var(--text); margin-top:1.75rem; margin-bottom:.6rem; font-weight:700;
}
.post-content p   { margin-bottom:1.1rem; }
.post-content a   { color:var(--accent); }
.post-content img { max-width:100%; border-radius:var(--radius); margin:.75rem 0; }
.post-content blockquote {
    border-left:4px solid var(--accent);
    padding:0.75rem 1rem;
    margin:1rem 0;
    background:rgba(var(--accent-rgb),.06);
    border-radius:0 var(--radius) var(--radius) 0;
    color:var(--text-muted);
}
.post-content pre {
    background:var(--bg2); padding:1rem; border-radius:var(--radius);
    overflow-x:auto; font-size:.875rem;
}
.post-content code { background:var(--bg2); padding:.15em .4em; border-radius:4px; font-size:.875em; }
.post-content ul,.post-content ol { padding-left:1.5rem; margin-bottom:1rem; }

/* ── Like Button ────────────────────────────────────────── */
.like-section {
    display:flex; align-items:center; gap:1rem;
    padding:1.25rem 0; border-bottom:1px solid var(--border); margin-bottom:2rem;
}
#likeBtn {
    display:inline-flex; align-items:center; gap:.5rem;
    background:none; border:2px solid var(--border);
    border-radius:50px; padding:.5rem 1.2rem;
    font-size:.95rem; font-weight:600; cursor:pointer;
    color:var(--text-muted); transition:all .2s ease;
}
#likeBtn:hover { border-color:var(--danger); color:var(--danger); }
#likeBtn.liked { border-color:var(--danger); color:var(--danger); background:rgba(231,76,60,.08); }
#likeBtn .heart { font-size:1.1rem; transition:transform .2s; }
#likeBtn.pop .heart { animation:heartPop .35s ease; }
@keyframes heartPop {
    0%   { transform:scale(1); }
    40%  { transform:scale(1.5); }
    70%  { transform:scale(.88); }
    100% { transform:scale(1); }
}
.like-label { font-size:.85rem; color:var(--text-muted); }

/* ── Comments ───────────────────────────────────────────── */
.comments-section h3 { font-size:1.2rem; font-weight:700; margin-bottom:1.25rem; }
.comment-form-wrap textarea {
    background:var(--bg); border-color:var(--border); color:var(--text);
    resize:vertical; min-height:90px;
}
.comment-list { margin-top:1.5rem; display:flex; flex-direction:column; gap:.75rem; }
.comment-item { display:flex; gap:.75rem; }
.comment-item.comment-reply { margin-left:3rem; }
.comment-avatar {
    width:36px; height:36px; border-radius:50%;
    background:var(--accent); display:inline-flex;
    align-items:center; justify-content:center;
    font-weight:700; font-size:.78rem; color:#000; flex-shrink:0;
}
.comment-avatar.reply-av { background:var(--bg2); border:2px solid var(--border); color:var(--text-muted); }
.comment-body { background:var(--bg2); border:1px solid var(--border); border-radius:var(--radius); padding:.8rem 1rem; flex:1; }
.comment-meta { display:flex; align-items:baseline; gap:.6rem; margin-bottom:.4rem; }
.comment-author { font-weight:600; font-size:.85rem; color:var(--text); }
.comment-time { font-size:.75rem; color:var(--text-muted); }
.comment-text { font-size:.9rem; color:var(--text); line-height:1.6; white-space:pre-wrap; word-break:break-word; }
.comment-actions { margin-top:.4rem; }
.btn-reply-toggle {
    background:none; border:none; color:var(--text-muted);
    font-size:.78rem; font-weight:600; cursor:pointer; padding:0;
    transition:color .18s;
}
.btn-reply-toggle:hover { color:var(--accent); }
.reply-box { display:none; margin-top:.75rem; }
.reply-box textarea {
    background:var(--bg); border-color:var(--border); color:var(--text);
    resize:vertical; min-height:70px; font-size:.88rem;
}
.login-prompt {
    background:var(--bg2); border:1px solid var(--border);
    border-radius:var(--radius); padding:1.2rem;
    text-align:center; color:var(--text-muted); font-size:.9rem;
}

/* ── Sidebar ────────────────────────────────────────────── */
.post-sidebar { position:sticky; top:80px; }
.sidebar-card {
    background:var(--bg2); border:1px solid var(--border);
    border-radius:var(--radius); padding:1.2rem;
    margin-bottom:1.25rem;
}
.sidebar-card h4 {
    font-size:.82rem; font-weight:700; text-transform:uppercase;
    letter-spacing:.06em; color:var(--text-muted);
    margin-bottom:1rem; display:flex; align-items:center; gap:.4rem;
}
/* Ad Product Cards */
.ad-product {
    border:1px solid var(--border); border-radius:var(--radius);
    overflow:hidden; margin-bottom:.875rem; transition:border-color .2s,box-shadow .2s;
}
.ad-product:hover { border-color:var(--accent); box-shadow:0 4px 16px rgba(0,0,0,.2); }
.ad-product:last-child { margin-bottom:0; }
.ad-product img {
    width:100%; height:120px; object-fit:cover;
    background:var(--bg); display:block;
}
.ad-product-body { padding:.75rem; }
.ad-sponsored {
    font-size:.68rem; font-weight:700; text-transform:uppercase;
    letter-spacing:.08em; color:var(--text-muted);
    background:rgba(255,255,255,.06); padding:.15rem .45rem;
    border-radius:20px; border:1px solid var(--border);
    display:inline-block; margin-bottom:.4rem;
}
.ad-name { font-size:.88rem; font-weight:700; color:var(--text); margin-bottom:.2rem; }
.ad-price { font-size:.92rem; color:var(--accent); font-weight:700; margin-bottom:.6rem; }
.btn-shop {
    display:block; text-align:center;
    background:var(--accent); color:#000;
    border-radius:calc(var(--radius)/2); padding:.4rem .8rem;
    font-size:.8rem; font-weight:700; text-decoration:none;
    transition:opacity .18s;
}
.btn-shop:hover { opacity:.85; color:#000; }

/* Author bio */
.author-bio { display:flex; gap:.75rem; align-items:center; }
.author-avatar-lg {
    width:48px; height:48px; border-radius:50%;
    background:var(--accent); display:inline-flex;
    align-items:center; justify-content:center;
    font-weight:800; font-size:1.1rem; color:#000; flex-shrink:0;
}

/* ── Share bar ──────────────────────────────────────────── */
.breadcrumb-bar {
    background:var(--bg2); border-bottom:1px solid var(--border);
    padding:.6rem 0; font-size:.82rem; color:var(--text-muted);
}
.breadcrumb-bar a { color:var(--text-muted); text-decoration:none; }
.breadcrumb-bar a:hover { color:var(--accent); }
</style>

<!-- Breadcrumb -->
<div class="breadcrumb-bar">
  <div class="container">
    <a href="/e-commerce/index.php"><i class="bi bi-house"></i> Home</a>
    &nbsp;/&nbsp;
    <a href="/e-commerce/pages/blog/index.php">Blog</a>
    &nbsp;/&nbsp;
    <span><?= htmlspecialchars(mb_substr($post['title'], 0, 55)) ?>...</span>
  </div>
</div>

<div class="container">
  <div class="post-layout">

    <!-- ────────────────── LEFT: Main Article ──────────────── -->
    <main>

      <?php if ($post['image']): ?>
        <img class="post-hero"
             src="/e-commerce/<?= htmlspecialchars($post['image']) ?>"
             alt="<?= htmlspecialchars($post['title']) ?>"
             onerror="this.style.display='none'">
      <?php endif; ?>

      <div class="post-meta">
        <span class="post-avatar">
          <?= strtoupper(mb_substr($post['author_name'], 0, 1)) ?>
        </span>
        <div>
          <div class="post-author-name"><?= htmlspecialchars($post['author_name']) ?></div>
          <div class="post-date">
            📅 <?= date('F j, Y', strtotime($post['created_at'])) ?>
            &nbsp;·&nbsp; ⏱ <?= max(1, (int)(str_word_count(strip_tags($post['content'])) / 200)) ?> min read
          </div>
        </div>
      </div>

      <h1 class="post-title"><?= htmlspecialchars($post['title']) ?></h1>

      <?php if ($post['excerpt']): ?>
        <p class="post-excerpt"><?= htmlspecialchars($post['excerpt']) ?></p>
      <?php endif; ?>

      <!-- Full article content (HTML from Quill — admin/premium only) -->
      <div class="post-content">
        <?= $post['content'] ?>
      </div>

      <!-- ── Like Button ──────────────────────────────────── -->
      <div class="like-section">
        <?php if (isLoggedIn()): ?>
          <button id="likeBtn" class="<?= $userLiked ? 'liked' : '' ?>"
                  data-post-id="<?= $postId ?>"
                  data-liked="<?= $userLiked ? '1' : '0' ?>">
            <span class="heart"><?= $userLiked ? '❤️' : '🤍' ?></span>
            <span id="likeCount"><?= $likeCount ?></span>
            <?= $userLiked ? 'Liked' : 'Like' ?>
          </button>
        <?php else: ?>
          <a href="/e-commerce/pages/auth/login.php" class="btn btn-ghost">
            🤍 <?= $likeCount ?> likes — Log in to like
          </a>
        <?php endif; ?>
        <span class="like-label"><?= $likeCount === 1 ? '1 like' : "$likeCount likes" ?></span>
      </div>

      <!-- ── Comment Section ─────────────────────────────── -->
      <section class="comments-section" id="comments">
        <h3>💬 <?= count($topComments) ?> Comment<?= count($topComments) !== 1 ? 's' : '' ?></h3>

        <?php if (isLoggedIn()): ?>
          <div class="comment-form-wrap">
            <div class="d-flex gap-2 align-items-start">
              <span class="comment-avatar" style="margin-top:.1rem">
                <?= strtoupper(mb_substr(currentUserName(), 0, 1)) ?>
              </span>
              <div style="flex:1">
                <textarea id="commentBody" class="form-control mb-2"
                          placeholder="Share your thoughts…" rows="3"></textarea>
                <button id="submitComment" class="btn btn-accent btn-sm"
                        data-post-id="<?= $postId ?>">
                  <i class="bi bi-send me-1"></i>Post Comment
                </button>
              </div>
            </div>
          </div>
        <?php else: ?>
          <div class="login-prompt">
            <a href="/e-commerce/pages/auth/login.php" style="color:var(--accent);font-weight:600">Log in</a>
            or
            <a href="/e-commerce/pages/auth/signup.php" style="color:var(--accent);font-weight:600">sign up</a>
            to join the conversation.
          </div>
        <?php endif; ?>

        <!-- Comment list -->
        <div class="comment-list" id="commentList">
          <?php foreach ($topComments as $c): ?>
            <?php
              $cId   = (int)$c['id'];
              $cInit = strtoupper(mb_substr($c['author_name'], 0, 1));
              $cName = htmlspecialchars($c['author_name']);
              $cTime = timeAgo($c['created_at']);
              $cBody = nl2br(htmlspecialchars($c['body'], ENT_QUOTES, 'UTF-8'));
            ?>
            <div class="comment-item" id="comment-<?= $cId ?>">
              <div class="comment-avatar"><?= $cInit ?></div>
              <div class="comment-body">
                <div class="comment-meta">
                  <span class="comment-author"><?= $cName ?></span>
                  <span class="comment-time"><?= $cTime ?></span>
                </div>
                <div class="comment-text"><?= $cBody ?></div>
                <div class="comment-actions">
                  <?php if (isLoggedIn()): ?>
                    <button class="btn-reply-toggle" data-comment-id="<?= $cId ?>">Reply</button>
                    <div class="reply-box" id="reply-<?= $cId ?>">
                      <textarea class="form-control mb-1 reply-textarea"
                                placeholder="Write a reply…" rows="2"
                                data-comment-id="<?= $cId ?>"></textarea>
                      <button class="btn btn-ghost btn-sm submit-reply"
                              data-post-id="<?= $postId ?>"
                              data-parent-id="<?= $cId ?>">
                        <i class="bi bi-reply me-1"></i>Send Reply
                      </button>
                    </div>
                  <?php endif; ?>
                </div>

                <!-- Nested replies -->
                <?php if (!empty($replies[$cId])): ?>
                  <div class="comment-list mt-2 ps-1" id="replies-<?= $cId ?>">
                    <?php foreach ($replies[$cId] as $r): ?>
                      <?php
                        $rInit = strtoupper(mb_substr($r['author_name'], 0, 1));
                        $rName = htmlspecialchars($r['author_name']);
                        $rTime = timeAgo($r['created_at']);
                        $rBody = nl2br(htmlspecialchars($r['body'], ENT_QUOTES, 'UTF-8'));
                      ?>
                      <div class="comment-item comment-reply" id="comment-<?= $r['id'] ?>">
                        <div class="comment-avatar reply-av"><?= $rInit ?></div>
                        <div class="comment-body">
                          <div class="comment-meta">
                            <span class="comment-author"><?= $rName ?></span>
                            <span class="comment-time"><?= $rTime ?></span>
                          </div>
                          <div class="comment-text"><?= $rBody ?></div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <div id="replies-<?= $cId ?>"></div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>

          <?php if (empty($topComments)): ?>
            <p id="noComments" style="color:var(--text-muted);font-size:.9rem;text-align:center;padding:1.5rem">
              No comments yet. <?= isLoggedIn() ? 'Be the first!' : '' ?>
            </p>
          <?php endif; ?>
        </div>
      </section>

    </main>

    <!-- ────────────────── RIGHT: Sidebar ─────────────────── -->
    <aside class="post-sidebar">

      <!-- Product Advertisement Widget -->
      <?php if (!empty($adProducts)): ?>
        <div class="sidebar-card">
          <h4><i class="bi bi-megaphone-fill" style="color:var(--accent)"></i> Featured Products</h4>
          <?php foreach ($adProducts as $ad): ?>
            <div class="ad-product">
              <?php if ($ad['image']): ?>
                <img src="/e-commerce/<?= htmlspecialchars($ad['image']) ?>"
                     alt="<?= htmlspecialchars($ad['name']) ?>"
                     onerror="this.style.display='none'">
              <?php endif; ?>
              <div class="ad-product-body">
                <span class="ad-sponsored">Sponsored</span>
                <div class="ad-name"><?= htmlspecialchars($ad['name']) ?></div>
                <div class="ad-price">$<?= number_format($ad['price'], 2) ?></div>
                <a class="btn-shop"
                   href="/e-commerce/pages/product/productList.php?id=<?= $ad['id'] ?>">
                  🛒 Shop Now
                </a>
              </div>
            </div>
          <?php endforeach; ?>
          <p style="font-size:.7rem;color:var(--text-muted);text-align:center;margin-top:.6rem">
            Sponsored content. Advertised by post author.
          </p>
        </div>
      <?php endif; ?>

      <!-- Author Bio -->
      <div class="sidebar-card">
        <h4><i class="bi bi-person-fill"></i> About the Author</h4>
        <div class="author-bio">
          <div class="author-avatar-lg">
            <?= strtoupper(mb_substr($post['author_name'], 0, 1)) ?>
          </div>
          <div>
            <div style="font-weight:700;font-size:.9rem"><?= htmlspecialchars($post['author_name']) ?></div>
            <div style="font-size:.8rem;color:var(--text-muted)">ShopZone Author</div>
          </div>
        </div>
      </div>

      <!-- More Posts from Blog -->
      <div class="sidebar-card">
        <h4><i class="bi bi-journal-bookmark"></i> More Articles</h4>
        <?php
          $stMore = $db->prepare("
            SELECT title, slug FROM posts
            WHERE status='published' AND id != ?
            ORDER BY created_at DESC LIMIT 4
          ");
          $stMore->execute([$postId]);
          $morePosts = $stMore->fetchAll();
        ?>
        <?php if ($morePosts): ?>
          <?php foreach ($morePosts as $mp): ?>
            <a href="/e-commerce/pages/blog/post.php?slug=<?= urlencode($mp['slug']) ?>"
               style="display:block;padding:.5rem 0;border-bottom:1px solid var(--border);
                      color:var(--text);text-decoration:none;font-size:.85rem;font-weight:500;
                      transition:color .18s"
               onmouseover="this.style.color='var(--accent)'"
               onmouseout="this.style.color='var(--text)'">
              <i class="bi bi-arrow-right me-1" style="color:var(--accent)"></i>
              <?= htmlspecialchars(mb_substr($mp['title'], 0, 58)) ?>
            </a>
          <?php endforeach; ?>
        <?php else: ?>
          <p style="color:var(--text-muted);font-size:.83rem">No other posts yet.</p>
        <?php endif; ?>
        <a href="/e-commerce/pages/blog/index.php"
           style="display:block;margin-top:.75rem;text-align:center;font-size:.82rem;font-weight:600;color:var(--accent);text-decoration:none">
          View all posts →
        </a>
      </div>

    </aside>

  </div><!-- /post-layout -->
</div>

<!-- ── JavaScript: Like Button + Comments (AJAX) ──────────── -->
<script>
const BLOG_API = '/e-commerce/pages/api/blog_interact.php';

// ── Like Button ──────────────────────────────────────────
const likeBtn = document.getElementById('likeBtn');
if (likeBtn) {
    likeBtn.addEventListener('click', async function () {
        const postId  = this.dataset.postId;
        let isLiked   = this.dataset.liked === '1';
        const countEl = document.getElementById('likeCount');

        // Optimistic UI update
        isLiked = !isLiked;
        this.dataset.liked = isLiked ? '1' : '0';
        this.classList.toggle('liked', isLiked);
        this.querySelector('.heart').textContent = isLiked ? '❤️' : '🤍';
        this.classList.add('pop');
        setTimeout(() => this.classList.remove('pop'), 400);

        const optimisticCount = parseInt(countEl.textContent) + (isLiked ? 1 : -1);
        countEl.textContent = optimisticCount;
        this.lastChild.textContent = ' ' + (isLiked ? 'Liked' : 'Like');

        // Server sync
        try {
            const fd = new FormData();
            fd.append('action',  'like');
            fd.append('post_id', postId);

            const res  = await fetch(BLOG_API, { method: 'POST', body: fd });
            const data = await res.json();

            if (data.count !== undefined) {
                countEl.textContent = data.count;
                document.querySelector('.like-label').textContent =
                    data.count === 1 ? '1 like' : data.count + ' likes';
            }
        } catch (err) {
            console.error('Like failed:', err);
        }
    });
}

// ── Reply box toggle ─────────────────────────────────────
document.addEventListener('click', function(e) {
    if (e.target.matches('.btn-reply-toggle')) {
        const cId  = e.target.dataset.commentId;
        const box  = document.getElementById('reply-' + cId);
        if (!box) return;
        const open = box.style.display === 'block';
        box.style.display = open ? 'none' : 'block';
        if (!open) box.querySelector('.reply-textarea')?.focus();
    }
});

// ── Submit top-level comment ─────────────────────────────
const submitBtn = document.getElementById('submitComment');
if (submitBtn) {
    submitBtn.addEventListener('click', async function() {
        await submitComment(
            this.dataset.postId,
            null,
            document.getElementById('commentBody'),
            'commentList',
            'noComments'
        );
    });
}

// ── Submit replies ────────────────────────────────────────
document.addEventListener('click', async function(e) {
    if (e.target.matches('.submit-reply')) {
        const postId   = e.target.dataset.postId;
        const parentId = e.target.dataset.parentId;
        const textarea = e.target.closest('.reply-box').querySelector('.reply-textarea');
        await submitComment(postId, parentId, textarea, 'replies-' + parentId, null);
        document.getElementById('reply-' + parentId).style.display = 'none';
    }
});

// ── Core submit function ──────────────────────────────────
async function submitComment(postId, parentId, textarea, listId, emptyMsgId) {
    const body = textarea.value.trim();
    if (body.length < 2) {
        textarea.classList.add('is-invalid');
        setTimeout(() => textarea.classList.remove('is-invalid'), 2000);
        return;
    }

    const fd = new FormData();
    fd.append('action',  'comment');
    fd.append('post_id', postId);
    fd.append('body',    body);
    if (parentId) fd.append('parent_id', parentId);

    try {
        const res  = await fetch(BLOG_API, { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success && data.html) {
            const list = document.getElementById(listId);
            if (list) {
                // Top-level: prepend; replies: append
                if (!parentId) {
                    list.insertAdjacentHTML('afterbegin', data.html);
                } else {
                    list.insertAdjacentHTML('beforeend', data.html);
                }
            }
            // Remove "no comments" placeholder
            if (emptyMsgId) {
                const el = document.getElementById(emptyMsgId);
                if (el) el.remove();
            }
            textarea.value = '';
            // Scroll to new comment
            document.getElementById('comment-' + data.comment_id)?.scrollIntoView({ behavior:'smooth', block:'nearest' });
        } else {
            alert(data.error || 'Could not post comment. Please try again.');
        }
    } catch (err) {
        console.error('Comment submit failed:', err);
        alert('Network error. Please try again.');
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
