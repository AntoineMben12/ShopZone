<?php
// user/dashboard.php
require_once '../../database/database.php';
require_once '../includes/auth.php';
requireLogin('../auth/login.php');

$db  = getDB();
$uid = currentUserId();

// Stats
$orderCount  = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id=?");
$orderCount->execute([$uid]); $orderCount = (int)$orderCount->fetchColumn();

$totalSpent  = $db->prepare("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE user_id=? AND payment_status='paid'");
$totalSpent->execute([$uid]); $totalSpent = (float)$totalSpent->fetchColumn();

$cartItems   = $db->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart WHERE user_id=?");
$cartItems->execute([$uid]); $cartItems = (int)$cartItems->fetchColumn();

$postCount   = $db->prepare("SELECT COUNT(*) FROM posts WHERE user_id=?");
$postCount->execute([$uid]); $postCount = (int)$postCount->fetchColumn();

// Recent orders
$orders = $db->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC LIMIT 5");
$orders->execute([$uid]); $orders = $orders->fetchAll();

$pageTitle = 'My Dashboard';
$depth = '../';
require_once '../includes/header.php';
require_once '../includes/navBar.php';
?>

<div class="container py-4">
  <?php renderFlash(); ?>

  <div class="row g-4">
    <!-- Sidebar -->
    <div class="col-lg-3">
      <div class="dash-sidebar">
        <div class="user-info d-flex align-items-center gap-3">
          <div class="user-avatar"><?= strtoupper(substr(currentUserName(),0,1)) ?></div>
          <div>
            <div style="font-weight:600;font-size:.95rem"><?= currentUserName() ?></div>
            <span class="badge-role-<?= getRole() ?>"><?= ucfirst(getRole()) ?></span>
          </div>
        </div>
        <nav class="nav flex-column gap-1">
          <a class="nav-link active" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
          <a class="nav-link" href="orders.php"><i class="bi bi-bag"></i> My Orders</a>
          <a class="nav-link" href="../cart/viewCart.php"><i class="bi bi-cart3"></i> Cart</a>
          <a class="nav-link" href="profile.php"><i class="bi bi-person"></i> Profile</a>
          <a class="nav-link" href="../product/productList.php"><i class="bi bi-grid"></i> Browse Products</a>
          <hr style="border-color:var(--border);margin:.5rem 0">
          <a class="nav-link" href="/e-commerce/pages/blog/index.php"><i class="bi bi-journal-text"></i> Blog</a>
          <a class="nav-link" href="/e-commerce/pages/blog/createPost.php"><i class="bi bi-pencil-square"></i> Write a Post</a>
          <hr style="border-color:var(--border);margin:.5rem 0">
          <a class="nav-link" href="../auth/logout.php" style="color:var(--danger)!important">
            <i class="bi bi-box-arrow-right"></i> Logout
          </a>
        </nav>
      </div>
    </div>

    <!-- Main -->
    <div class="col-lg-9">
      <h1 class="section-title fade-up">Dashboard</h1>
      <p class="section-sub fade-up-2">Welcome back, <?= currentUserName() ?>! Here's your overview.</p>

      <!-- Stats -->
      <div class="row g-3 mb-4">
        <div class="col-sm-4">
          <div class="stat-card fade-up">
            <div class="stat-icon">📦</div>
            <div class="stat-value"><?= $orderCount ?></div>
            <div class="stat-label">Total Orders</div>
          </div>
        </div>
        <div class="col-sm-4">
          <div class="stat-card fade-up-2">
            <div class="stat-icon">💰</div>
            <div class="stat-value"><?= CURRENCY . number_format($totalSpent, 2) ?></div>
            <div class="stat-label">Amount Spent</div>
          </div>
        </div>
        <div class="col-sm-4">
          <div class="stat-card fade-up-3">
            <div class="stat-icon">🛒</div>
            <div class="stat-value"><?= $cartItems ?></div>
            <div class="stat-label">Items in Cart</div>
          </div>
        </div>
        <div class="col-sm-4">
          <div class="stat-card fade-up">
            <div class="stat-icon">✍️</div>
            <div class="stat-value"><?= $postCount ?></div>
            <div class="stat-label">My Posts</div>
          </div>
        </div>
      </div>

      <!-- Recent Orders -->
      <div class="card-dark p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0">Recent Orders</h5>
          <a href="orders.php" class="btn btn-ghost btn-sm">View All</a>
        </div>
        <?php if ($orders): ?>
          <div class="table-responsive">
            <table class="table table-dark-custom mb-0">
              <thead>
                <tr><th>Order #</th><th>Date</th><th>Total</th><th>Status</th><th></th></tr>
              </thead>
              <tbody>
                <?php foreach ($orders as $o): ?>
                  <tr>
                    <td><strong>#<?= $o['id'] ?></strong></td>
                    <td style="color:var(--text-muted);font-size:.85rem"><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
                    <td><?= CURRENCY . number_format($o['total_price'], 2) ?></td>
                    <td><span class="badge-status badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                    <td><a href="orders.php?id=<?= $o['id'] ?>" class="btn btn-ghost btn-sm">Details</a></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="text-center py-4">
            <div style="font-size:3rem;margin-bottom:.5rem">📭</div>
            <p style="color:var(--text-muted)">No orders yet.</p>
            <a href="../product/productList.php" class="btn btn-accent">Start Shopping</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>