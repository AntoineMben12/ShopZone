<?php
// products/product_details.php
require_once '../../database/database.php';
require_once '../includes/auth.php';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

if (!$id) { header('Location: productList.php'); exit; }

$st = $db->prepare("SELECT p.*, c.name AS cat_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.id=?");
$st->execute([$id]);
$p = $st->fetch();

if (!$p) { header('Location: productList.php'); exit; }

// Related products
$related = $db->prepare("SELECT * FROM products WHERE category_id=? AND id!=? AND stock>0 LIMIT 4");
$related->execute([$p['category_id'], $id]);
$related = $related->fetchAll();

$effectivePrice = $p['sale_price'] ?? $p['price'];
$onSale = !empty($p['sale_price']);

$pageTitle = $p['name'];
$depth = '../';
require_once '../includes/header.php';
require_once '../includes/navBar.php';
?>

<div class="container py-4">
  <?php renderFlash(); ?>

  <!-- Breadcrumb -->
  <nav style="margin-bottom:1.5rem">
    <ol class="breadcrumb" style="background:none;padding:0">
      <li class="breadcrumb-item"><a href="../index.php" style="color:var(--text-muted)">Home</a></li>
      <li class="breadcrumb-item"><a href="productList.php" style="color:var(--text-muted)">Products</a></li>
      <li class="breadcrumb-item" style="color:var(--text-muted)"><?= htmlspecialchars($p['name']) ?></li>
    </ol>
  </nav>

  <div class="row g-5">
    <!-- Image -->
    <div class="col-md-5">
      <div class="card-dark" style="aspect-ratio:1;display:flex;align-items:center;justify-content:center;font-size:6rem">
        🛒
      </div>
    </div>

    <!-- Info -->
    <div class="col-md-7">
      <?php if ($p['cat_name']): ?>
        <p style="color:var(--accent);font-size:.78rem;letter-spacing:.12em;text-transform:uppercase;font-weight:600;margin-bottom:.3rem">
          <?= htmlspecialchars($p['cat_name']) ?>
        </p>
      <?php endif; ?>

      <h1 style="font-family:var(--font-head);font-size:2rem;margin-bottom:.5rem"><?= htmlspecialchars($p['name']) ?></h1>

      <!-- Price -->
      <div class="d-flex align-items-baseline gap-3 mb-3">
        <span style="font-size:1.9rem;font-weight:700;color:var(--accent)"><?= CURRENCY . number_format($effectivePrice, 2) ?></span>
        <?php if ($onSale): ?>
          <span style="text-decoration:line-through;color:var(--text-muted);font-size:1.1rem"><?= CURRENCY . number_format($p['price'], 2) ?></span>
          <span class="badge-sale">
            -<?= round((1 - $effectivePrice/$p['price'])*100) ?>% OFF
          </span>
        <?php endif; ?>
      </div>

      <!-- Stock -->
      <p style="color:<?= $p['stock'] > 0 ? 'var(--success)' : 'var(--danger)' ?>;font-size:.9rem;margin-bottom:1rem">
        <?= $p['stock'] > 0 ? "✓ In Stock ({$p['stock']} available)" : "✗ Out of Stock" ?>
      </p>

      <!-- Description -->
      <p style="color:var(--text-muted);line-height:1.7;margin-bottom:1.5rem">
        <?= nl2br(htmlspecialchars($p['description'] ?? 'No description available.')) ?>
      </p>

      <div class="divider"></div>

      <?php if ($p['stock'] > 0): ?>
        <!-- Add to cart form -->
        <form id="add-to-cart-form" action="../cart/addToCart.php" method="POST" class="d-flex align-items-center gap-3">
          <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
          <input type="hidden" name="redirect" value="product">

          <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-ghost qty-btn" data-target="#qty" data-dir="down"
                    style="width:36px;height:36px;padding:0;display:flex;align-items:center;justify-content:center">−</button>
            <input type="number" id="qty" name="quantity" class="qty-input" value="1" min="1" max="<?= $p['stock'] ?>">
            <button type="button" class="btn btn-ghost qty-btn" data-target="#qty" data-dir="up"
                    style="width:36px;height:36px;padding:0;display:flex;align-items:center;justify-content:center">+</button>
          </div>

          <?php if (isLoggedIn()): ?>
            <button type="submit" class="btn btn-accent px-4 py-2">
              <i class="bi bi-cart-plus me-2"></i>Add to Cart
            </button>
          <?php else: ?>
            <a href="../auth/login.php" class="btn btn-accent px-4 py-2">
              <i class="bi bi-lock me-2"></i>Login to Buy
            </a>
          <?php endif; ?>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <!-- Related products -->
  <?php if ($related): ?>
    <div class="mt-5">
      <h3 class="section-title">Related Products</h3>
      <div class="row g-3">
        <?php foreach ($related as $r):
          $rp = $r['sale_price'] ?? $r['price'];
        ?>
          <div class="col-6 col-md-3">
            <div class="product-card">
              <div class="img-wrap"><div style="font-size:2.5rem;padding:1rem">🛒</div></div>
              <div class="card-body">
                <h5 class="card-title" style="font-size:.92rem"><?= htmlspecialchars($r['name']) ?></h5>
                <div class="price mb-2"><?= CURRENCY . number_format($rp, 2) ?></div>
                <a href="productDetail.php?id=<?= $r['id'] ?>" class="btn btn-ghost w-100" style="font-size:.82rem">View</a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('add-to-cart-form');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(form);
        formData.append('ajax', '1');

        const btn = form.querySelector('button[type="submit"]');
        if (!btn) {
            // Must be logged out (using 'a' tag)
            return;
        }

        try {
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Adding...';
            btn.disabled = true;

            const res = await fetch(form.action, {
                method: 'POST',
                body: formData
            });

            // Prevent JSON parsing error if redirected to login
            if (res.redirected && res.url.includes('login.php')) {
                window.location.href = res.url;
                return;
            }

            const data = await res.json();
            
            btn.innerHTML = originalContent;
            btn.disabled = false;

            if (data.success) {
                // Update badge
                const badge = document.getElementById('nav-cart-badge');
                if (badge) {
                    badge.textContent = data.cartCount;
                    badge.classList.remove('d-none');
                    
                    // Small pop animation
                    badge.style.transform = 'translate(-50%, -50%) scale(1.3)';
                    badge.style.transition = 'transform 0.2s';
                    setTimeout(() => { badge.style.transform = 'translate(-50%, -50%) scale(1)'; }, 200);
                }
                showToast(data.message, 'success');
            } else {
                showToast(data.message || 'Error adding to cart', 'error');
            }
        } catch (err) {
            console.error(err);
            btn.innerHTML = '<i class="bi bi-cart-plus me-2"></i>Add to Cart';
            btn.disabled = false;
            showToast('A network error occurred', 'error');
        }
    });

    function showToast(message, type) {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        const icon = type === 'success' ? '<i class="bi bi-check-circle-fill" style="color:var(--accent);font-size:1.25rem"></i>' : '<i class="bi bi-exclamation-circle-fill" style="color:red;font-size:1.25rem"></i>';
        
        toast.innerHTML = `${icon} <span>${message}</span>`;
        container.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('hiding');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>