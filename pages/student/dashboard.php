<?php
// student/dashboard.php
require_once '../../database/database.php';
require_once '../includes/auth.php';
requireLogin('../auth/login.php');
if (!isStudent()) { header('Location: /e-commerce/index.php'); exit; }

$db  = getDB();
$uid = currentUserId();

$orderCount = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id=?");
$orderCount->execute([$uid]); $orderCount = (int)$orderCount->fetchColumn();

$totalSaved = $db->prepare("SELECT COALESCE(SUM(p.price - p.sale_price),0)
    FROM order_items oi
    JOIN orders o ON o.id=oi.order_id
    JOIN products p ON p.id=oi.product_id
    WHERE o.user_id=? AND p.sale_price IS NOT NULL AND o.payment_status='paid'");
$totalSaved->execute([$uid]); $totalSaved = (float)$totalSaved->fetchColumn();

// Featured books/discounted items
$deals = $db->query("SELECT * FROM products WHERE sale_price IS NOT NULL AND stock > 0 ORDER BY (price - sale_price) DESC LIMIT 4")->fetchAll();

$pageTitle = 'Student Dashboard';
$depth = '../';
require_once '../includes/header.php';
require_once '../includes/navBar.php';
?>

<div class="container py-4">
  <?php renderFlash(); ?>
  <div class="row g-4">
    <div class="col-lg-3">
      <div class="dash-sidebar">
        <div class="user-info d-flex align-items-center gap-3">
          <div class="user-avatar" style="background:#3498db"><?= strtoupper(substr(currentUserName(),0,1)) ?></div>
          <div>
            <div style="font-weight:600;font-size:.95rem"><?= currentUserName() ?></div>
            <span class="badge-role-student">Student</span>
          </div>
        </div>
        <nav class="nav flex-column gap-1">
          <a class="nav-link active" href="dashboard.php"><i class="bi bi-mortarboard"></i> Dashboard</a>
          <a class="nav-link" href="../user/orders.php"><i class="bi bi-bag"></i> My Orders</a>
          <a class="nav-link" href="../cart/viewCart.php"><i class="bi bi-cart3"></i> Cart</a>
          <a class="nav-link" href="../user/profile.php"><i class="bi bi-person"></i> Profile</a>
          <a class="nav-link" href="../product/productList.php"><i class="bi bi-grid"></i> Browse</a>
          <hr style="border-color:var(--border);margin:.5rem 0">
          <a class="nav-link" href="../auth/logout.php" style="color:var(--danger)!important">
            <i class="bi bi-box-arrow-right"></i> Logout
          </a>
        </nav>
      </div>
    </div>

    <div class="col-lg-9">
      <!-- Banner -->
      <div class="p-4 mb-4 rounded-3" style="background:linear-gradient(135deg,rgba(52,152,219,.15),rgba(245,166,35,.1));border:1px solid rgba(52,152,219,.3)">
        <div class="d-flex align-items-center gap-3">
          <div style="font-size:2.5rem">🎓</div>
          <div>
            <h4 class="mb-1">Student Perks Active!</h4>
            <p class="mb-0" style="color:var(--text-muted);font-size:.9rem">
              You get exclusive discounts on books and selected products. Keep learning!
            </p>
          </div>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-sm-4">
          <div class="stat-card">
            <div class="stat-icon">📚</div>
            <div class="stat-value"><?= $orderCount ?></div>
            <div class="stat-label">Orders Placed</div>
          </div>
        </div>
        <div class="col-sm-4">
          <div class="stat-card">
            <div class="stat-icon">💸</div>
            <div class="stat-value"><?= CURRENCY . number_format($totalSaved, 2) ?></div>
            <div class="stat-label">Total Saved</div>
          </div>
        </div>
        <div class="col-sm-4">
          <div class="stat-card">
            <div class="stat-icon">⭐</div>
            <div class="stat-value">Active</div>
            <div class="stat-label">Student Status</div>
          </div>
        </div>
      </div>

      <!-- Deals for students -->
      <h5 class="mb-3">🔥 Best Deals For You</h5>
      <div class="row g-3">
        <?php foreach ($deals as $p): ?>
          <div class="col-sm-6">
            <div class="card-dark p-3 d-flex gap-3 align-items-center">
              <div style="font-size:2rem;width:50px;text-align:center">🛒</div>
              <div class="flex-grow-1">
                <div style="font-weight:500;font-size:.92rem"><?= htmlspecialchars($p['name']) ?></div>
                <div>
                  <span style="color:var(--accent);font-weight:700"><?= CURRENCY . number_format($p['sale_price'],2) ?></span>
                  <span style="text-decoration:line-through;color:var(--text-muted);font-size:.82rem;margin-left:.4rem"><?= CURRENCY . number_format($p['price'],2) ?></span>
                </div>
              </div>
              <a href="../product/productDetail.php?id=<?= $p['id'] ?>" class="btn btn-accent btn-sm">Buy</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>