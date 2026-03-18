<?php
// index.php — Homepage
require_once 'database/database.php';
require_once 'pages/includes/auth.php';

$db = getDB();

// Featured products
$featured = $db->query("SELECT * FROM products WHERE featured = 1 AND stock > 0 LIMIT 8")->fetchAll();

// All categories
$categories = $db->query("SELECT c.*, COUNT(p.id) AS product_count FROM categories c LEFT JOIN products p ON p.category_id = c.id GROUP BY c.id")->fetchAll();

$pageTitle = 'Home';
$depth = '';
require_once 'pages/includes/header.php';
require_once 'pages/includes/navBar.php';
?>

<!-- ── Hero ──────────────────────────────────────────────── -->
<section class="hero">
  <div class="container position-relative" style="z-index:1">
    <div class="row align-items-center">
      <div class="col-lg-6">
        <p class="fade-up" style="color:var(--accent);font-size:.85rem;letter-spacing:.12em;text-transform:uppercase;font-weight:600;margin-bottom:.5rem">
          ✦ New Arrivals 2025
        </p>
        <h1 class="hero-title fade-up-2">
          Shop Smarter,<br>Live <span class="highlight">Better</span>
        </h1>
        <p class="hero-sub fade-up-3">
          Discover premium products across electronics, fashion, books and more — curated for every lifestyle.
        </p>
        <div class="d-flex gap-3 flex-wrap fade-up-3">
          <a href="/e-commerce/pages/product/productList.php" class="btn btn-accent px-4 py-2">
            <i class="bi bi-grid me-2"></i>Browse Products
          </a>
          <?php if (!isLoggedIn()): ?>
            <a href="/e-commerce/pages/auth/signup.php" class="btn btn-outline-accent px-4 py-2">
              <i class="bi bi-person-plus me-2"></i>Join Free
            </a>
          <?php endif; ?>
        </div>

        <!-- Stats row -->
        <div class="d-flex gap-4 mt-4 pt-2 fade-up-3">
          <div>
            <div style="font-family:var(--font-head);font-size:1.5rem;color:var(--accent);font-weight:700">500+</div>
            <div style="color:var(--text-muted);font-size:.78rem;text-transform:uppercase;letter-spacing:.08em">Products</div>
          </div>
          <div style="width:1px;background:var(--border)"></div>
          <div>
            <div style="font-family:var(--font-head);font-size:1.5rem;color:var(--accent);font-weight:700">10K+</div>
            <div style="color:var(--text-muted);font-size:.78rem;text-transform:uppercase;letter-spacing:.08em">Customers</div>
          </div>
          <div style="width:1px;background:var(--border)"></div>
          <div>
            <div style="font-family:var(--font-head);font-size:1.5rem;color:var(--accent);font-weight:700">4.9★</div>
            <div style="color:var(--text-muted);font-size:.78rem;text-transform:uppercase;letter-spacing:.08em">Rating</div>
          </div>
        </div>
      </div>

      <div class="col-lg-6 d-none d-lg-flex justify-content-end">
        <!-- Decorative placeholder image area -->
        <div style="width:420px;height:360px;
                    background:#ffffff;
                    border:2px solid var(--accent);display:flex;flex-direction:column;
                    align-items:center;justify-content:center;gap:1.5rem;position:relative;overflow:hidden;
                    box-shadow: 12px 12px 0px rgba(0,0,0,1);">
          <div style="font-size:5rem; color:var(--accent);">
            <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="currentColor" viewBox="0 0 16 16">
              <path d="M8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1m3.5 3v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4zM2 5h12v9a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1z"/>
            </svg>
          </div>
          <p style="color:var(--accent);font-weight:600;letter-spacing:1px;font-size:.9rem;text-transform:uppercase;">Your products, delivered</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ── Categories ─────────────────────────────────────────── -->
<section class="py-5" style="background:var(--bg2)">
  <div class="container">
    <h2 class="section-title">Shop by Category</h2>
    <p class="section-sub">Browse our curated collections</p>
    <div class="row g-3">
      <?php
      $icons = [
        'electronics' => '<svg width="28" height="28" fill="currentColor" viewBox="0 0 16 16"><path d="M13.5 3a.5.5 0 0 1 .5.5V11H2V3.5a.5.5 0 0 1 .5-.5zm-11-1A1.5 1.5 0 0 0 1 3.5V12h14V3.5A1.5 1.5 0 0 0 13.5 2zM0 12.5h16a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 12.5"/></svg>',
        'clothing' => '<svg width="28" height="28" fill="currentColor" viewBox="0 0 16 16"><path d="M6 4.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m-1 0a.5.5 0 1 0-1 0 .5.5 0 0 0 1 0"/><path d="M2 1h4.586a1 1 0 0 1 .707.293l7 7a1 1 0 0 1 0 1.414l-4.586 4.586a1 1 0 0 1-1.414 0l-7-7A1 1 0 0 1 1 6.586V2a1 1 0 0 1 1-1m0 5.586 7 7L13.586 9l-7-7H2z"/></svg>',
        'books' => '<svg width="28" height="28" fill="currentColor" viewBox="0 0 16 16"><path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783"/></svg>',
        'sports' => '<svg width="28" height="28" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M6 2a.5.5 0 0 1 .47.33L10 12.036l1.53-4.208A.5.5 0 0 1 12 7.5h3.5a.5.5 0 0 1 0 1h-3.15l-1.88 5.17a.5.5 0 0 1-.94 0L6 3.964 4.47 8.171A.5.5 0 0 1 4 8.5H.5a.5.5 0 0 1 0-1h3.15l1.88-5.17A.5.5 0 0 1 6 2"/></svg>'
      ];
      foreach ($categories as $cat): ?>
        <div class="col-6 col-md-3">
          <a href="/e-commerce/pages/product/productList.php?category=<?= $cat['id'] ?>"
            class="card-dark d-block text-center p-3 text-decoration-none">
            <div style="font-size:1.8rem;margin-bottom:.5rem;color:var(--accent);">
              <?= $icons[$cat['slug']] ?? '<svg width="28" height="28" fill="currentColor" viewBox="0 0 16 16"><path d="M6 4.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m-1 0a.5.5 0 1 0-1 0 .5.5 0 0 0 1 0"/><path d="M2 1h4.586a1 1 0 0 1 .707.293l7 7a1 1 0 0 1 0 1.414l-4.586 4.586a1 1 0 0 1-1.414 0l-7-7A1 1 0 0 1 1 6.586V2a1 1 0 0 1 1-1m0 5.586 7 7L13.586 9l-7-7H2z"/></svg>' ?>
            </div>
            <div style="font-weight:600;font-size:.95rem;color:var(--text)"><?= htmlspecialchars($cat['name']) ?></div>
            <div style="color:var(--text-muted);font-size:.78rem"><?= $cat['product_count'] ?> items</div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── Featured Products ──────────────────────────────────── -->
<section class="py-5">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end mb-4">
      <div>
        <h2 class="section-title">Featured Products</h2>
        <p class="mb-0" style="color:var(--text-muted)">Hand-picked just for you</p>
      </div>
      <a href="/e-commerce/pages/product/productList.php" class="btn btn-ghost btn-sm">
        View All <i class="bi bi-arrow-right ms-1"></i>
      </a>
    </div>

    <div class="row g-3">
      <?php foreach ($featured as $p):
        $effectivePrice = $p['sale_price'] ?? $p['price'];
        $onSale = !empty($p['sale_price']);
      ?>
        <div class="col-6 col-md-4 col-lg-3">
          <div class="product-card">
            <div class="img-wrap">
              <?php if ($p['image'] && $p['image'] !== 'placeholder.jpg'): ?>
                <img src="/e-commerce/Assets/images/<?= htmlspecialchars($p['image']) ?>"
                  alt="<?= htmlspecialchars($p['name']) ?>">
              <?php else: ?>
                <div style="font-size:3.5rem;padding:1.5rem;color:var(--text-muted);">
                  <svg width="48" height="48" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 1a2.5 2.5 0 0 1 2.5 2.5V4h-5v-.5A2.5 2.5 0 0 1 8 1m3.5 3v-.5a3.5 3.5 0 1 0-7 0V4H1v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V4zM2 5h12v9a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1z"/>
                  </svg>
                </div>
              <?php endif; ?>
            </div>
            <div class="card-body">
              <?php if ($onSale): ?>
                <span class="badge-sale mb-1 d-inline-block">SALE</span>
              <?php endif; ?>
              <h5 class="card-title"><?= htmlspecialchars($p['name']) ?></h5>
              <p style="color:var(--text-muted);font-size:.82rem;flex:1">
                <?= htmlspecialchars(substr($p['description'] ?? '', 0, 60)) ?>…
              </p>
              <div class="d-flex align-items-center gap-2 mb-2">
                <span class="price"><?= CURRENCY . number_format($effectivePrice, 2) ?></span>
                <?php if ($onSale): ?>
                  <span class="price-old"><?= CURRENCY . number_format($p['price'], 2) ?></span>
                <?php endif; ?>
              </div>
              <a href="/e-commerce/pages/product/productDetail.php?id=<?= $p['id'] ?>"
                class="btn btn-accent w-100" style="font-size:.85rem">
                <i class="bi bi-eye me-1"></i> View Details
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── Value Props ────────────────────────────────────────── -->
<section class="py-5" style="background:var(--bg2)">
  <div class="container">
    <div class="row g-3 text-center">
      <?php 
      $truck = '<svg width="32" height="32" fill="currentColor" viewBox="0 0 16 16"><path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5V5h1.02a1.5 1.5 0 0 1 1.17.563l1.481 1.85a1.5 1.5 0 0 1 .329.938V10.5a1.5 1.5 0 0 1-1.5 1.5H14a2 2 0 1 1-4 0H5a2 2 0 1 1-3.998-.085A1.5 1.5 0 0 1 0 10.5zm1.294 7.456A2 2 0 0 1 4.732 11h5.536a2 2 0 0 1 .732-.732V3.5a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5v7a.5.5 0 0 0 .294.456M12 10a2 2 0 0 1 1.732 1h.768a.5.5 0 0 0 .5-.5V8.35a.5.5 0 0 0-.11-.312l-1.48-1.85A.5.5 0 0 0 13.02 6H12zm-9 1a1 1 0 1 0 0 2 1 1 0 0 0 0-2m9 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2"/></svg>';
      $lock = '<svg width="32" height="32" fill="currentColor" viewBox="0 0 16 16"><path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2m3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2M5 8h6a1 1 0 0 1 1 1v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1"/></svg>';
      $arrow = '<svg width="32" height="32" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 3a5 5 0 1 1-4.546 2.914.5.5 0 0 0-.908-.417A6 6 0 1 0 8 2v1z"/><path d="M8 4.466V.534a.25.25 0 0 0-.41-.192L5.23 2.308a.25.25 0 0 0 0 .384l2.36 1.966A.25.25 0 0 0 8 4.466z"/></svg>';
      $headset = '<svg width="32" height="32" fill="currentColor" viewBox="0 0 16 16"><path d="M8 1a5 5 0 0 0-5 5v1h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a6 6 0 1 1 12 0v6a2.5 2.5 0 0 1-2.5 2.5H9.366a1 1 0 0 1-.866.5h-1a1 1 0 1 1 0-2h1a1 1 0 0 1 .866.5H11.5A1.5 1.5 0 0 0 13 12h-1a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1h1V6a5 5 0 0 0-5-5"/></svg>';
      $props = [
        [$truck, 'Fast Delivery', 'Orders shipped within 24h'],
        [$lock, 'Secure Payment', '256-bit SSL encryption'],
        [$arrow, 'Easy Returns', '30-day return policy'],
        [$headset, '24/7 Support', 'Always here to help'],
      ];
      foreach ($props as [$icon, $title, $sub]): ?>
        <div class="col-6 col-md-3">
          <div class="stat-card text-center">
            <div class="stat-icon"><?= $icon ?></div>
            <div style="font-weight:600;margin-bottom:.2rem"><?= $title ?></div>
            <div style="color:var(--text-muted);font-size:.82rem"><?= $sub ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require_once 'pages/includes/footer.php'; ?>