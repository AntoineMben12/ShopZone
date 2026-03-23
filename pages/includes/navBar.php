<?php

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/auth.php';


$depth = $depth ?? '';

$cartCount = 0;
if (isLoggedIn()) {
  require_once __DIR__ . '/../../database/database.php';
  $db = getDB();
  $st = $db->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart WHERE user_id = ?");
  $st->execute([currentUserId()]);
  $cartCount = (int)$st->fetchColumn();
}
?>

<nav class="navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand" href="/e-commerce/index.php">
      Shop<span>Zone</span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
      <!-- Left links -->
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link" href="/e-commerce/index.php"><i class="bi bi-house me-1"></i><?= __('nav_home') ?></a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/e-commerce/pages/product/productList.php"><i class="bi bi-grid me-1"></i><?= __('nav_products') ?></a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/e-commerce/pages/blog/index.php"><i class="bi bi-pencil-square me-1"></i><?= __('nav_blog') ?></a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="/e-commerce/pages/documentation.php"><i class="bi bi-book me-1"></i><?= __('nav_docs') ?></a>
        </li>
      </ul>

      <!-- Right links -->
      <ul class="navbar-nav align-items-center gap-1">
        <!-- Language Dropdown -->
        <li class="nav-item dropdown me-2">
          <a class="nav-link dropdown-toggle d-flex align-items-center gap-1" href="#" data-bs-toggle="dropdown">
            <?php
              $currLang = $_SESSION['lang'] ?? 'en';
              $flag = ['en'=>'🇬🇧', 'fr'=>'🇫🇷', 'es'=>'🇪🇸'][$currLang] ?? '🇬🇧';
              echo "<span>$flag</span> " . strtoupper($currLang);
            ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end" style="min-width:120px; background:var(--bg2); border:1px solid var(--border); border-radius:var(--radius);">
            <li><a class="dropdown-item" href="/e-commerce/pages/includes/lang/setLanguage.php?lang=en">🇬🇧 EN</a></li>
            <li><a class="dropdown-item" href="/e-commerce/pages/includes/lang/setLanguage.php?lang=fr">🇫🇷 FR</a></li>
            <li><a class="dropdown-item" href="/e-commerce/pages/includes/lang/setLanguage.php?lang=es">🇪🇸 ES</a></li>
          </ul>
        </li>

        <?php if (isLoggedIn()): ?>

          <!-- Cart icon with badge -->
          <li class="nav-item">
            <a class="nav-link position-relative" href="/e-commerce/pages/cart/viewCart.php">
              <i class="bi bi-cart3" style="font-size:1.1rem"></i>
              <span id="nav-cart-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill <?= $cartCount > 0 ? '' : 'd-none' ?>"
                style="background:var(--accent);color:#000;font-size:.65rem">
                <?= $cartCount ?>
              </span>
            </a>
          </li>

          <!-- User dropdown -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#"
              data-bs-toggle="dropdown" id="userMenu">
              <span style="width:32px;height:32px;border-radius:50%;background:var(--accent);
                    display:inline-flex;align-items:center;justify-content:center;
                    color:#000;font-weight:700;font-size:.8rem">
                <?= strtoupper(substr(currentUserName(), 0, 1)) ?>
              </span>
              <span class="d-none d-lg-inline" style="color:var(--text)"><?= currentUserName() ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end"
              style="background:var(--bg2);border:1px solid var(--border);border-radius:var(--radius);">
              <?php
              $dashLink = match (getRole()) {
                'admin'   => '/e-commerce/pages/admin/dashboard.php',
                'student' => '/e-commerce/pages/user/dashboard.php',
                default   => '/e-commerce/pages/user/dashboard.php',
              };
              ?>
              <li>
                <a class="dropdown-item" href="<?= $dashLink ?>"
                  style="color:var(--text);font-size:.9rem">
                  <i class="bi bi-speedometer2 me-2"></i><?= __('nav_dashboard') ?>
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="/e-commerce/pages/user/profile.php"
                  style="color:var(--text);font-size:.9rem">
                  <i class="bi bi-person me-2"></i><?= __('nav_profile') ?>
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="/e-commerce/pages/user/orders.php"
                  style="color:var(--text);font-size:.9rem">
                  <i class="bi bi-bag me-2"></i><?= __('nav_orders') ?>
                </a>
              </li>
              <li>
                <a class="dropdown-item" href="/e-commerce/pages/blog/createPost.php"
                  style="color:var(--text);font-size:.9rem">
                  <i class="bi bi-pencil-square me-2"></i><?= __('nav_write_post') ?>
                </a>
              </li>
              <li>
                <hr class="dropdown-divider" style="border-color:var(--border)">
              </li>
              <li>
                <a class="dropdown-item" href="/e-commerce/pages/auth/logout.php"
                  style="color:var(--danger);font-size:.9rem">
                  <i class="bi bi-box-arrow-right me-2"></i><?= __('nav_logout') ?>
                </a>
              </li>
            </ul>
          </li>

        <?php else: ?>

          <li class="nav-item">
            <a class="btn btn-ghost btn-sm" href="/e-commerce/pages/auth/login.php"><?= __('nav_login') ?></a>
          </li>
          <li class="nav-item">
            <a class="btn btn-accent btn-sm ms-1" href="/e-commerce/pages/auth/signup.php"><?= __('nav_signup') ?></a>
          </li>

        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>