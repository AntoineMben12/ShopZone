<?php
// pages/admin/addPost.php
// Create or Edit a blog post. Supports rich-text body (Quill.js),
// featured image upload, and product advertisement attachment.
require_once '../../database/database.php';
require_once '../includes/auth.php';
requireAdmin('../auth/login.php');

$db        = getDB();
$editId    = (int)($_GET['edit'] ?? 0);
$isEdit    = $editId > 0;
$post      = null;
$attached  = [];
$errors    = [];

// Load existing post for edit mode
if ($isEdit) {
    $st = $db->prepare("SELECT * FROM posts WHERE id = ?");
    $st->execute([$editId]);
    $post = $st->fetch();
    if (!$post) {
        setFlashMessage('Post not found.', 'error');
        header('Location: blog.php');
        exit;
    }
    // Load attached product IDs
    $stAtt = $db->prepare("SELECT product_id FROM post_products WHERE post_id = ? ORDER BY sort_order");
    $stAtt->execute([$editId]);
    $attached = $stAtt->fetchAll(PDO::FETCH_COLUMN);
}

// Check if current admin user is premium (can attach product ads)
$stPrem = $db->prepare("SELECT is_premium FROM users WHERE id = ?");
$stPrem->execute([currentUserId()]);
$isPremium = (bool)$stPrem->fetchColumn();

// Load all products (for the ad picker)
$allProducts = $db->query("SELECT id, name, price, image FROM products ORDER BY name")->fetchAll();

// -----------------------------------------------------------
// Handle form submission
// -----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = trim($_POST['title']   ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = $_POST['content']      ?? ''; // raw HTML from Quill — admin-only input
    $status  = in_array($_POST['status'] ?? '', ['draft','published']) ? $_POST['status'] : 'draft';
    $adIds   = array_map('intval', $_POST['ad_products'] ?? []);

    // Validation
    if (strlen($title) < 3)     $errors[] = 'Title must be at least 3 characters.';
    if (strlen($excerpt) < 10)  $errors[] = 'Excerpt must be at least 10 characters.';
    if (strlen($content) < 20)  $errors[] = 'Post content is too short.';

    // Generate unique slug from title
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
    $slug = trim($slug, '-');
    // Check uniqueness (exclude self when editing)
    $stSlug = $db->prepare("SELECT id FROM posts WHERE slug = ? AND id != ?");
    $stSlug->execute([$slug, $editId ?: 0]);
    if ($stSlug->fetch()) {
        $slug .= '-' . time();
    }

    // Handle featured image upload
    $imagePath = $isEdit ? ($post['image'] ?? '') : '';
    if (!empty($_FILES['image']['name'])) {
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        $mime = mime_content_type($_FILES['image']['tmp_name']);
        if (!array_key_exists($mime, $allowed)) {
            $errors[] = 'Only JPEG, PNG, WebP, or GIF images are allowed.';
        } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Image must be less than 5 MB.';
        } else {
            $ext      = $allowed[$mime];
            $filename = 'blog_' . uniqid() . '.' . $ext;
            $dest     = __DIR__ . '/../../Assets/blog/' . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                // Delete old image if editing
                if ($isEdit && $imagePath && file_exists(__DIR__ . '/../../' . $imagePath)) {
                    unlink(__DIR__ . '/../../' . $imagePath);
                }
                $imagePath = 'Assets/blog/' . $filename;
            } else {
                $errors[] = 'Failed to save image. Check directory permissions.';
            }
        }
    }

    if (empty($errors)) {
        if ($isEdit) {
            $st = $db->prepare("
                UPDATE posts SET title=?, slug=?, excerpt=?, content=?, image=?, status=?
                WHERE id = ?
            ");
            $st->execute([$title, $slug, $excerpt, $content, $imagePath, $status, $editId]);
            $savedId = $editId;
        } else {
            $st = $db->prepare("
                INSERT INTO posts (user_id, title, slug, excerpt, content, image, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $st->execute([currentUserId(), $title, $slug, $excerpt, $content, $imagePath, $status]);
            $savedId = (int)$db->lastInsertId();
        }

        // Sync attached product ads (only for premium users)
        if ($isPremium) {
            $db->prepare("DELETE FROM post_products WHERE post_id = ?")->execute([$savedId]);
            if ($adIds) {
                $stIns = $db->prepare("INSERT IGNORE INTO post_products (post_id, product_id, sort_order) VALUES (?, ?, ?)");
                foreach ($adIds as $ord => $pid) {
                    $stIns->execute([$savedId, $pid, $ord]);
                }
            }
        }

        setFlashMessage($isEdit ? 'Post updated successfully.' : 'Post published successfully!');
        header('Location: blog.php');
        exit;
    }
}

$pageTitle = $isEdit ? 'Edit Post' : 'Add New Post';
$depth     = '../';
require_once '../includes/header.php';
require_once '../includes/navBar.php';
?>

<!-- Quill.js CSS -->
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">

<style>
.post-form-card { background:var(--bg2); border:1px solid var(--border); border-radius:var(--radius); padding:1.5rem; }
.form-label-blog { font-size:.85rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:.04em; margin-bottom:.4rem; display:block; }
.quill-wrapper { background:var(--bg); border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; }
.quill-wrapper .ql-toolbar { background:var(--bg2); border-bottom:1px solid var(--border); border-top:none; border-left:none; border-right:none; }
.quill-wrapper .ql-container { border:none; }
.ql-editor { min-height:320px; color:var(--text); font-size:1rem; line-height:1.7; }
.ql-editor.ql-blank::before { color:var(--text-muted); font-style:normal; }
.product-picker-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:.75rem; }
.product-pick-card { border:2px solid var(--border); border-radius:var(--radius); padding:.6rem; cursor:pointer; transition:border-color .2s,background .2s; }
.product-pick-card:hover { border-color:var(--accent); }
.product-pick-card.selected { border-color:var(--accent); background:rgba(var(--accent-rgb),.1); }
.product-pick-card img { width:100%; height:80px; object-fit:cover; border-radius:calc(var(--radius) - 4px); margin-bottom:.5rem; }
.product-pick-card .pick-name { font-size:.8rem; font-weight:600; color:var(--text); margin-bottom:.2rem; }
.product-pick-card .pick-price { font-size:.75rem; color:var(--accent); }
.premium-gate { background:rgba(255,193,7,.08); border:1px dashed rgba(255,193,7,.4); border-radius:var(--radius); padding:1rem; text-align:center; }
</style>

<div class="container-fluid py-4 px-4">
  <?php if ($errors): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-3">
      <strong>Please fix the following:</strong>
      <ul class="mb-0 mt-1"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

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
          <a class="nav-link" href="blog.php"><i class="bi bi-journal-text"></i> Blog Posts</a>
          <a class="nav-link active" href="addPost.php"><i class="bi bi-pencil-square"></i> Add Post</a>
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
          <h1 class="section-title mb-0"><?= $isEdit ? 'Edit Post' : 'New Blog Post' ?></h1>
          <p style="color:var(--text-muted);font-size:.85rem;margin-top:.2rem">
            <?= $isEdit ? 'Update the post details below.' : 'Create a great article and optionally feature your products.' ?>
          </p>
        </div>
        <a href="blog.php" class="btn btn-ghost">
          <i class="bi bi-arrow-left me-1"></i>Back to Posts
        </a>
      </div>

      <form method="POST" enctype="multipart/form-data" id="postForm">

        <div class="row g-4">
          <!-- Left: Main content -->
          <div class="col-lg-8">

            <!-- Title -->
            <div class="post-form-card mb-3">
              <label class="form-label-blog">Post Title</label>
              <input type="text" name="title" id="titleInput"
                     class="form-control"
                     style="background:var(--bg);border-color:var(--border);color:var(--text);font-size:1.1rem;font-weight:600"
                     placeholder="Your compelling headline…"
                     value="<?= htmlspecialchars($post['title'] ?? ($_POST['title'] ?? '')) ?>"
                     required>
              <small style="color:var(--text-muted);display:block;margin-top:.4rem">Slug preview: <code id="slugPreview">/<?= htmlspecialchars($post['slug'] ?? '') ?></code></small>
            </div>

            <!-- Excerpt -->
            <div class="post-form-card mb-3">
              <label class="form-label-blog">Excerpt <small style="color:var(--text-muted)">(shown in blog feed)</small></label>
              <textarea name="excerpt" rows="2"
                        class="form-control"
                        style="background:var(--bg);border-color:var(--border);color:var(--text)"
                        placeholder="A short summary (2–3 sentences)…"
                        maxlength="500"><?= htmlspecialchars($post['excerpt'] ?? ($_POST['excerpt'] ?? '')) ?></textarea>
            </div>

            <!-- Rich-text body -->
            <div class="post-form-card mb-3">
              <label class="form-label-blog">Post Content</label>
              <div class="quill-wrapper">
                <div id="quillEditor"><?= $isEdit ? $post['content'] : '' ?></div>
              </div>
              <input type="hidden" name="content" id="contentHidden">
            </div>

            <!-- Product Advertisement Picker -->
            <div class="post-form-card">
              <label class="form-label-blog">
                <i class="bi bi-megaphone me-1" style="color:var(--accent)"></i>
                Featured Product Advertisements
                <?php if ($isPremium): ?>
                  <span class="ms-2" style="background:rgba(255,193,7,.15);color:#f0b429;font-size:.7rem;padding:.15rem .5rem;border-radius:20px;font-weight:700;text-transform:uppercase">Premium</span>
                <?php endif; ?>
              </label>

              <?php if (!$isPremium): ?>
                <div class="premium-gate">
                  <i class="bi bi-lock-fill" style="color:#f0b429;font-size:1.5rem;display:block;margin-bottom:.5rem"></i>
                  <p style="color:var(--text-muted);font-size:.9rem;margin:0">
                    <strong style="color:var(--text)">Premium feature locked.</strong><br>
                    Only Premium Advertisers can attach product promotions to posts.<br>
                    Contact the site administrator to upgrade your account.
                  </p>
                </div>
              <?php else: ?>
                <p style="color:var(--text-muted);font-size:.8rem;margin-bottom:.75rem">
                  Select up to 3 products to feature as sponsored ads alongside this post. Tick to select, tick again to deselect.
                </p>
                <div class="product-picker-grid">
                  <?php foreach ($allProducts as $prd): ?>
                    <?php $checked = in_array($prd['id'], $attached) ? 'checked' : ''; ?>
                    <label class="product-pick-card <?= $checked ? 'selected' : '' ?>">
                      <input type="checkbox" name="ad_products[]" value="<?= $prd['id'] ?>"
                             <?= $checked ?> style="display:none" class="pick-check">
                      <?php if ($prd['image']): ?>
                        <img src="/e-commerce/<?= htmlspecialchars($prd['image']) ?>"
                             alt="<?= htmlspecialchars($prd['name']) ?>"
                             onerror="this.style.display='none'">
                      <?php else: ?>
                        <div style="height:80px;background:var(--bg);display:flex;align-items:center;justify-content:center;border-radius:calc(var(--radius)-4px);margin-bottom:.5rem">
                          <i class="bi bi-image" style="color:var(--text-muted);font-size:1.5rem"></i>
                        </div>
                      <?php endif; ?>
                      <div class="pick-name"><?= htmlspecialchars($prd['name']) ?></div>
                      <div class="pick-price">$<?= number_format($prd['price'], 2) ?></div>
                    </label>
                  <?php endforeach; ?>
                  <?php if (empty($allProducts)): ?>
                    <p style="color:var(--text-muted);font-size:.85rem">No products in the store yet. <a href="addProduct.php">Add products first →</a></p>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>

          </div><!-- /col-lg-8 -->

          <!-- Right: Meta -->
          <div class="col-lg-4">

            <!-- Publish box -->
            <div class="post-form-card mb-3">
              <label class="form-label-blog">Publish Status</label>
              <select name="status" class="form-select"
                      style="background:var(--bg);border-color:var(--border);color:var(--text)">
                <option value="draft" <?= ($post['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>📝 Draft</option>
                <option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>>✅ Published</option>
              </select>
              <div class="d-grid mt-3 gap-2">
                <button type="submit" class="btn btn-accent" id="submitBtn">
                  <i class="bi bi-send me-1"></i><?= $isEdit ? 'Update Post' : 'Publish Post' ?>
                </button>
                <?php if ($isEdit): ?>
                  <a href="/e-commerce/pages/blog/post.php?slug=<?= urlencode($post['slug']) ?>"
                     class="btn btn-ghost btn-sm" target="_blank">
                    <i class="bi bi-eye me-1"></i>Preview Live
                  </a>
                <?php endif; ?>
              </div>
            </div>

            <!-- Featured Image -->
            <div class="post-form-card mb-3">
              <label class="form-label-blog">Featured Image</label>
              <?php if (!empty($post['image'])): ?>
                <img src="/e-commerce/<?= htmlspecialchars($post['image']) ?>"
                     alt="Current featured image"
                     style="width:100%;border-radius:var(--radius);margin-bottom:.75rem;max-height:160px;object-fit:cover">
                <p style="font-size:.75rem;color:var(--text-muted);margin-bottom:.5rem">Upload a new image to replace the current one.</p>
              <?php endif; ?>
              <input type="file" name="image" id="imageInput" accept="image/*"
                     class="form-control"
                     style="background:var(--bg);border-color:var(--border);color:var(--text)">
              <small style="color:var(--text-muted);margin-top:.35rem;display:block">JPEG/PNG/WebP/GIF · max 5 MB</small>
              <div id="imagePreviewWrap" style="margin-top:.75rem;display:none">
                <img id="imagePreview" style="width:100%;border-radius:var(--radius);max-height:160px;object-fit:cover" alt="Preview">
              </div>
            </div>

            <!-- Tips -->
            <div class="post-form-card" style="background:rgba(var(--accent-rgb),.05);border-color:rgba(var(--accent-rgb),.2)">
              <p style="font-size:.8rem;color:var(--text-muted);margin:0;line-height:1.7">
                <strong style="color:var(--text)">✍️ Writing tips:</strong><br>
                • Keep your title under 70 characters.<br>
                • Include a featured image for better engagement.<br>
                • Use the excerpt to hook readers in the feed.<br>
                • Published posts appear immediately on the blog.
              </p>
            </div>

          </div><!-- /col-lg-4 -->
        </div><!-- /row -->

      </form>
    </div><!-- /main -->
  </div><!-- /row -->
</div>

<!-- Quill.js -->
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
// ── Quill rich-text editor ────────────────────────────────
const quill = new Quill('#quillEditor', {
    theme: 'snow',
    placeholder: 'Write your post here…',
    modules: {
        toolbar: [
            [{ header: [2, 3, 4, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            ['blockquote', 'code-block'],
            ['link', 'image'],
            [{ color: [] }, { background: [] }],
            ['clean']
        ]
    }
});

// Sync Quill content to hidden input before form submit
document.getElementById('postForm').addEventListener('submit', function(e) {
    const content = quill.root.innerHTML.trim();
    if (content === '<p><br></p>' || content.length < 20) {
        e.preventDefault();
        alert('Please write some content for the post.');
        return;
    }
    document.getElementById('contentHidden').value = content;
});

// ── Slug preview ──────────────────────────────────────────
document.getElementById('titleInput').addEventListener('input', function() {
    const slug = this.value.toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .trim();
    document.getElementById('slugPreview').textContent = '/' + slug;
});

// ── Image preview ─────────────────────────────────────────
document.getElementById('imageInput').addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('imagePreview').src = e.target.result;
        document.getElementById('imagePreviewWrap').style.display = 'block';
    };
    reader.readAsDataURL(file);
});

// ── Product picker card toggle ────────────────────────────
document.querySelectorAll('.product-pick-card').forEach(card => {
    card.addEventListener('click', function() {
        const chk = this.querySelector('.pick-check');
        const max = 3;
        const allChecked = document.querySelectorAll('.pick-check:checked').length;

        if (!chk.checked && allChecked >= max) {
            alert('You can feature up to 3 products per post.');
            return;
        }
        chk.checked = !chk.checked;
        this.classList.toggle('selected', chk.checked);
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
