<?php
// admin/dashboard.php
require_once '../../database/database.php';
require_once '../includes/auth.php';
requireAdmin('../index.php');

$db = getDB();

// Stats
$totalUsers    = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalProducts = (int)$db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalOrders   = (int)$db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalRevenue  = (float)$db->query("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE payment_status='paid'")->fetchColumn();

// Pending orders
$pendingOrders = (int)$db->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();

// Recent orders
$recentOrders = $db->query("
    SELECT o.*, u.name AS user_name
    FROM orders o JOIN users u ON u.id=o.user_id
    ORDER BY o.created_at DESC LIMIT 8
")->fetchAll();

// Low stock products
$lowStock = $db->query("SELECT * FROM products WHERE stock < 10 ORDER BY stock ASC LIMIT 5")->fetchAll();

// Monthly revenue (last 6 months)
$monthly = $db->query("
    SELECT DATE_FORMAT(created_at,'%b %Y') AS month,
           SUM(total_price) AS revenue
    FROM orders
    WHERE payment_status='paid' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at,'%Y-%m')
    ORDER BY MIN(created_at) ASC
")->fetchAll();

$pageTitle = 'Admin Dashboard';
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
          <a class="nav-link active" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
          <a class="nav-link" href="product.php"><i class="bi bi-box"></i> Products</a>
          <a class="nav-link" href="addProduct.php"><i class="bi bi-plus-circle"></i> Add Product</a>
          <a class="nav-link" href="users.php"><i class="bi bi-people"></i> Users</a>
          <a class="nav-link" href="orders.php"><i class="bi bi-bag-check"></i> Orders</a>
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
          <h1 class="section-title mb-0">Admin Dashboard</h1>
          <p style="color:var(--text-muted);font-size:.85rem;margin-top:.2rem">
            <?= date('l, F j, Y') ?> &nbsp;·&nbsp;
            <?php if ($pendingOrders > 0): ?>
              <span style="color:var(--accent)">⚠ <?= $pendingOrders ?> pending orders</span>
            <?php else: ?>
              <span style="color:var(--success)">✓ All orders up to date</span>
            <?php endif; ?>
          </p>
        </div>
        <a href="add_product.php" class="btn btn-accent">
          <i class="bi bi-plus me-1"></i>New Product
        </a>
      </div>

      <!-- Stats -->
      <div class="row g-3 mb-4">
        <?php $stats = [
          ['💰', 'Total Revenue', CURRENCY . number_format($totalRevenue, 2), 'var(--accent)'],
          ['📦', 'Total Orders',  $totalOrders,    'var(--info)'],
          ['👥', 'Total Users',   $totalUsers,     'var(--success)'],
          ['🛍️', 'Products',      $totalProducts,  '#9b59b6'],
        ];
        foreach ($stats as [$icon, $label, $value, $color]): ?>
          <div class="col-6 col-xl-3">
            <div class="stat-card">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="stat-label"><?= $label ?></div>
                  <div class="stat-value" style="color:<?= $color ?>"><?= $value ?></div>
                </div>
                <div style="font-size:1.8rem;opacity:.6"><?= $icon ?></div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="row g-4">
        <!-- Recent Orders -->
        <div class="col-lg-8">
          <div class="card-dark p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h5 class="mb-0">Recent Orders</h5>
              <a href="orders.php" class="btn btn-ghost btn-sm">View All</a>
            </div>
            <div class="table-responsive">
              <table class="table table-dark-custom mb-0" style="font-size:.85rem">
                <thead>
                  <tr><th>#</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th></th></tr>
                </thead>
                <tbody>
                  <?php foreach ($recentOrders as $o): ?>
                    <tr>
                      <td><strong>#<?= $o['id'] ?></strong></td>
                      <td><?= htmlspecialchars($o['user_name']) ?></td>
                      <td><?= CURRENCY . number_format($o['total_price'], 2) ?></td>
                      <td>
                        <span style="color:<?= $o['payment_status']==='paid'?'var(--success)':'var(--danger)' ?>;font-weight:600">
                          <?= ucfirst($o['payment_status']) ?>
                        </span>
                      </td>
                      <td><span class="badge-status badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                      <td style="color:var(--text-muted)"><?= date('M j', strtotime($o['created_at'])) ?></td>
                      <td><a href="orders.php?id=<?= $o['id'] ?>" class="btn btn-ghost btn-sm">Manage</a></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Low Stock -->
        <div class="col-lg-4">
          <div class="card-dark p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h5 class="mb-0">⚠ Low Stock</h5>
              <a href="product.php" class="btn btn-ghost btn-sm">Manage</a>
            </div>
            <?php if ($lowStock): ?>
              <?php foreach ($lowStock as $p): ?>
                <a href="addProduct.php?edit=<?= $p['id'] ?>" class="d-flex justify-content-between align-items-center py-2 text-decoration-none"
                     style="border-bottom:1px solid var(--border);color:inherit">
                  <div style="font-size:.85rem;font-weight:500"><?= htmlspecialchars($p['name']) ?></div>
                  <span class="badge-status" style="background:rgba(231,76,60,.2);color:var(--danger)">
                    <?= $p['stock'] ?> left
                  </span>
                </a>
              <?php endforeach; ?>
            <?php else: ?>
              <p style="color:var(--text-muted);font-size:.85rem">All products well stocked ✓</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>