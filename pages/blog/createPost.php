<?php
// pages/blog/createPost.php
// Create or edit a blog post. Accessible to ALL logged-in users.
require_once '../../database/database.php';
require_once '../includes/auth.php';
requireLogin('../auth/login.php');

$db       = getDB();
$uid      = currentUserId();
$editId   = (int)($_GET['edit'] ?? 0);
$isEdit   = $editId > 0;
$post     = null;
$errors   = [];

// Load existing post for edit mode
if ($isEdit) {
    $st = $db->prepare("SELECT * FROM posts WHERE id = ?");
    $st->execute([$editId]);
    $post = $st->fetch();

    if (!$post) {
        setFlashMessage('Post not found.', 'error');
        header('Location: index.php');
        exit;
    }
    // Only the post owner OR an admin can edit
    if ((int)$post['user_id'] !== $uid && !isAdmin()) {
        setFlashMessage('You do not have permission to edit this post.', 'error');
        header('Location: index.php');
        exit;
    }
}

// ─────────────────────────────────────────────────────────
// Handle form submission
// ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = trim($_POST['title']   ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = $_POST['content']      ?? '';
    $status  = in_array($_POST['status'] ?? '', ['draft', 'published']) ? $_POST['status'] : 'draft';

    // Validation
    if (strlen($title)   < 3)  $errors[] = 'Title must be at least 3 characters.';
    if (strlen($excerpt) < 10) $errors[] = 'Excerpt must be at least 10 characters.';
    if (strlen(strip_tags($content)) < 10) $errors[] = 'Post content is too short.';

    // Build unique slug
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
    $slug = trim($slug, '-');
    $stSlug = $db->prepare("SELECT id FROM posts WHERE slug = ? AND id != ?");
    $stSlug->execute([$slug, $editId ?: 0]);
    if ($stSlug->fetch()) {
        $slug .= '-' . time();
    }

    // Handle featured image upload
    $imagePath = $isEdit ? ($post['image'] ?? '') : '';
    if (!empty($_FILES['image']['name'])) {
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ];
        $mime = mime_content_type($_FILES['image']['tmp_name']);
        if (!array_key_exists($mime, $allowed)) {
            $errors[] = 'Only JPEG, PNG, WebP, or GIF images are allowed.';
        } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Image must be less than 5 MB.';
        } else {
            $ext      = $allowed[$mime];
            $filename = 'blog_' . uniqid() . '.' . $ext;
            $destDir  = __DIR__ . '/../../Assets/blog/';
            if (!is_dir($destDir)) mkdir($destDir, 0777, true);
            $dest = $destDir . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                // Remove old image when replacing
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
            $st->execute([$uid, $title, $slug, $excerpt, $content, $imagePath, $status]);
            $savedId = (int)$db->lastInsertId();
        }

        setFlashMessage($isEdit ? 'Post updated successfully.' : 'Post published successfully! 🎉');
        header('Location: /e-commerce/pages/blog/index.php');
        exit;
    }
}

$pageTitle = $isEdit ? 'Edit Post' : 'Write a Post';
$depth     = '../';
require_once '../includes/header.php';
require_once '../includes/navBar.php';
?>

<!-- Quill.js CSS -->
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">

<style>
/* ── Create Post Styles ─────────────────────────────────── */
.cp-hero {
    background: linear-gradient(135deg, var(--bg2) 0%, var(--bg) 100%);
    border-bottom: 1px solid var(--border);
    padding: 2.5rem 0 2rem;
}
.cp-hero h1 {
    font-size: clamp(1.6rem, 4vw, 2.4rem);
    font-weight: 800;
    background: linear-gradient(135deg, var(--text) 0%, var(--accent) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: .25rem;
}
.cp-hero p { color: var(--text-muted); font-size: .95rem; margin: 0; }

.form-card {
    background: var(--bg2);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.4rem 1.5rem;
    margin-bottom: 1.25rem;
}
.field-label {
    font-size: .78rem;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-bottom: .45rem;
    display: block;
}
.quill-wrap {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
}
.quill-wrap .ql-toolbar {
    background: var(--bg2);
    border-bottom: 1px solid var(--border);
    border-top: none; border-left: none; border-right: none;
}
.quill-wrap .ql-container { border: none; }
.ql-editor {
    min-height: 280px;
    color: var(--text);
    font-size: 1rem;
    line-height: 1.75;
}
.ql-editor.ql-blank::before { color: var(--text-muted); font-style: normal; }

/* Toolbar icon colour fix for dark mode */
.ql-toolbar .ql-stroke { stroke: var(--text-muted) !important; }
.ql-toolbar .ql-fill   { fill:   var(--text-muted) !important; }
.ql-toolbar .ql-picker-label { color: var(--text-muted) !important; }

.status-badge-row { display: flex; gap: .5rem; flex-wrap: wrap; margin-top: .5rem; }
.status-radio { display: none; }
.status-label {
    flex: 1;
    text-align: center;
    padding: .55rem .5rem;
    border: 2px solid var(--border);
    border-radius: var(--radius);
    cursor: pointer;
    font-size: .85rem;
    font-weight: 600;
    color: var(--text-muted);
    transition: border-color .2s, color .2s, background .2s;
    min-width: 100px;
}
.status-radio:checked + .status-label {
    border-color: var(--accent);
    color: var(--accent);
    background: rgba(var(--accent-rgb), .08);
}
.img-preview-wrap { margin-top: .75rem; display: none; }
.img-preview-wrap img {
    width: 100%; border-radius: var(--radius);
    max-height: 180px; object-fit: cover;
}
.tips-card {
    background: rgba(var(--accent-rgb), .05);
    border: 1px solid rgba(var(--accent-rgb), .18);
    border-radius: var(--radius);
    padding: 1rem 1.2rem;
    font-size: .82rem;
    color: var(--text-muted);
    line-height: 1.7;
}
.tips-card strong { color: var(--text); }
</style>

<!-- Hero -->
<div class="cp-hero">
  <div class="container">
    <h1><?= $isEdit ? '✏️ Edit Your Post' : '✏️ Write a Post' ?></h1>
    <p><?= $isEdit ? 'Update the details below and save.' : 'Share your thoughts, tips, or stories with the community.' ?></p>
  </div>
</div>

<div class="container py-5">
  <?php renderFlash(); ?>

  <?php if ($errors): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4">
      <strong>Please fix the following:</strong>
      <ul class="mb-0 mt-1">
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" id="cpForm">
    <div class="row g-4">

      <!-- ── Left: Content ─────────────────────────────── -->
      <div class="col-lg-8">

        <!-- Title -->
        <div class="form-card">
          <label class="field-label" for="titleInput">Post Title *</label>
          <input type="text" name="title" id="titleInput"
                 class="form-control"
                 style="background:var(--bg);border-color:var(--border);color:var(--text);font-size:1.1rem;font-weight:600"
                 placeholder="Your compelling headline…"
                 value="<?= htmlspecialchars($post['title'] ?? ($_POST['title'] ?? '')) ?>"
                 required>
          <small style="color:var(--text-muted);display:block;margin-top:.4rem">
            Slug preview: <code id="slugPreview">/<?= htmlspecialchars($post['slug'] ?? '') ?></code>
          </small>
        </div>

        <!-- Excerpt -->
        <div class="form-card">
          <label class="field-label" for="excerptInput">
            Short Excerpt *
            <span style="font-weight:400;color:var(--text-muted);text-transform:none">(shown in blog feed)</span>
          </label>
          <textarea name="excerpt" id="excerptInput" rows="2"
                    class="form-control"
                    style="background:var(--bg);border-color:var(--border);color:var(--text)"
                    placeholder="A short summary to hook readers (2–3 sentences)…"
                    maxlength="500"><?= htmlspecialchars($post['excerpt'] ?? ($_POST['excerpt'] ?? '')) ?></textarea>
        </div>

        <!-- Rich-text body -->
        <div class="form-card">
          <label class="field-label">Post Content *</label>
          <div class="quill-wrap">
            <div id="quillEditor"><?= $isEdit ? $post['content'] : '' ?></div>
          </div>
          <input type="hidden" name="content" id="contentHidden">
        </div>

      </div><!-- /col-lg-8 -->

      <!-- ── Right: Meta ──────────────────────────────── -->
      <div class="col-lg-4">

        <!-- Publish Status -->
        <div class="form-card">
          <label class="field-label">Publish Status</label>
          <div class="status-badge-row">
            <input type="radio" name="status" id="sDraft" value="draft"
                   class="status-radio"
                   <?= ($post['status'] ?? 'draft') === 'draft' ? 'checked' : '' ?>>
            <label class="status-label" for="sDraft">📝 Draft</label>

            <input type="radio" name="status" id="sPublished" value="published"
                   class="status-radio"
                   <?= ($post['status'] ?? '') === 'published' ? 'checked' : '' ?>>
            <label class="status-label" for="sPublished">✅ Published</label>
          </div>
          <div class="d-grid mt-3 gap-2">
            <button type="submit" class="btn btn-accent" id="submitBtn">
              <i class="bi bi-send me-1"></i>
              <?= $isEdit ? 'Update Post' : 'Submit Post' ?>
            </button>
            <a href="index.php" class="btn btn-ghost btn-sm">
              <i class="bi bi-arrow-left me-1"></i>Back to Blog
            </a>
          </div>
        </div>

        <!-- Featured Image -->
        <div class="form-card">
          <label class="field-label">Featured Image</label>
          <?php if (!empty($post['image'])): ?>
            <img src="/e-commerce/<?= htmlspecialchars($post['image']) ?>"
                 alt="Current featured image"
                 style="width:100%;border-radius:var(--radius);margin-bottom:.75rem;max-height:160px;object-fit:cover">
            <p style="font-size:.75rem;color:var(--text-muted);margin-bottom:.5rem">Upload a new image to replace.</p>
          <?php endif; ?>
          <input type="file" name="image" id="imageInput" accept="image/*"
                 class="form-control"
                 style="background:var(--bg);border-color:var(--border);color:var(--text)">
          <small style="color:var(--text-muted);margin-top:.3rem;display:block">JPEG/PNG/WebP/GIF · max 5 MB</small>
          <div class="img-preview-wrap" id="imgPreviewWrap">
            <img id="imgPreview" alt="Preview">
          </div>
        </div>

        <!-- Tips -->
        <div class="tips-card">
          <strong>✍️ Writing tips:</strong><br>
          • Keep your title under 70 characters.<br>
          • Add a featured image for better engagement.<br>
          • Use the excerpt to hook readers in the feed.<br>
          • Published posts appear immediately on the blog.<br>
          • You can always come back to edit or delete your post.
        </div>

      </div><!-- /col-lg-4 -->

    </div><!-- /row -->
  </form>
</div>

<!-- Quill.js -->
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
// ── Quill editor ──────────────────────────────────────────
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

document.getElementById('cpForm').addEventListener('submit', function(e) {
    const content = quill.root.innerHTML.trim();
    if (content === '<p><br></p>' || content.replace(/<[^>]+>/g,'').trim().length < 10) {
        e.preventDefault();
        alert('Please write some content for your post (at least 10 characters).');
        return;
    }
    document.getElementById('contentHidden').value = content;
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitBtn').textContent = 'Saving…';
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
        document.getElementById('imgPreview').src = e.target.result;
        document.getElementById('imgPreviewWrap').style.display = 'block';
    };
    reader.readAsDataURL(file);
});
</script>

<?php require_once '../includes/footer.php'; ?>
