<?php
// user/orders.php
require_once '../../database/database.php';
require_once '../includes/auth.php';
requireLogin('../auth/login.php');

$db  = getDB();
$uid = currentUserId();

// Fetch all orders
$orders = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$orders->execute([$uid]);
$orders = $orders->fetchAll();

// Single order detail
$detail = null;
if (isset($_GET['id'])) {
    $st = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $st->execute([(int)$_GET['id'], $uid]);
    $detail = $st->fetch();
    if ($detail) {
        $items = $db->prepare("
            SELECT oi.*, p.name, p.image FROM order_items oi
            JOIN products p ON p.id = oi.product_id
            WHERE oi.order_id = ?");
        $items->execute([$detail['id']]);
        $detail['items'] = $items->fetchAll();
    }
}

$pageTitle = 'My Orders';
$depth = '../';
require_once '../includes/header.php';
require_once '../includes/navBar.php';
?>

<div class="container py-4">
  <div class="row g-4">
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
          <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
          <a class="nav-link active" href="orders.php"><i class="bi bi-bag"></i> My Orders</a>
          <a class="nav-link" href="../cart/viewCart.php"><i class="bi bi-cart3"></i> Cart</a>
          <a class="nav-link" href="profile.php"><i class="bi bi-person"></i> Profile</a>
          <a class="nav-link" href="../product/productList.php"><i class="bi bi-grid"></i> Browse</a>
          <hr style="border-color:var(--border);margin:.5rem 0">
          <a class="nav-link" href="../auth/logout.php" style="color:var(--danger)!important">
            <i class="bi bi-box-arrow-right"></i> Logout
          </a>
        </nav>
      </div>
    </div>

    <div class="col-lg-9">
      <?php if ($detail): ?>
        <!-- Order Detail -->
        <div class="d-flex align-items-center gap-3 mb-3">
          <a href="orders.php" class="btn btn-ghost btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
          <h1 class="section-title mb-0">Order #<?= $detail['id'] ?></h1>
        </div>

        <div class="row g-3 mb-4">
          <div class="col-sm-3">
            <div class="stat-card">
              <div class="stat-label">Status</div>
              <span class="badge-status badge-<?= $detail['status'] ?>"><?= ucfirst($detail['status']) ?></span>
            </div>
          </div>
          <div class="col-sm-3">
            <div class="stat-card">
              <div class="stat-label">Payment</div>
              <span style="color:<?= $detail['payment_status']==='paid' ? 'var(--success)' : 'var(--danger)' ?>;font-weight:600">
                <?= ucfirst($detail['payment_status']) ?>
              </span>
            </div>
          </div>
          <div class="col-sm-3">
            <div class="stat-card">
              <div class="stat-label">Total</div>
              <div style="font-weight:700;color:var(--accent)"><?= CURRENCY . number_format($detail['total_price'],2) ?></div>
            </div>
          </div>
          <div class="col-sm-3">
            <div class="stat-card">
              <div class="stat-label">Date</div>
              <div style="font-size:.9rem"><?= date('M j, Y', strtotime($detail['created_at'])) ?></div>
            </div>
          </div>
        </div>

        <div class="row g-4 mb-4">
          <div class="col-lg-8">
            <div class="card-dark p-3 h-100">
              <h5 class="mb-3">Order Items</h5>
              <?php foreach ($detail['items'] as $item): ?>
                <div class="d-flex align-items-center gap-3 py-2" style="border-bottom:1px solid var(--border)">
                  <div style="width:52px;height:52px;background:var(--bg3);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.5rem">
                    🛒
                  </div>
                  <div class="flex-grow-1">
                    <div style="font-weight:500"><?= htmlspecialchars($item['name']) ?></div>
                    <div style="color:var(--text-muted);font-size:.82rem">Qty: <?= $item['quantity'] ?></div>
                  </div>
                  <div style="font-weight:600;color:var(--accent)"><?= CURRENCY . number_format($item['price'] * $item['quantity'], 2) ?></div>
                </div>
              <?php endforeach; ?>
              <div class="text-end pt-2 pe-2">
                <strong>Total: <?= CURRENCY . number_format($detail['total_price'], 2) ?></strong>
              </div>
            </div>
          </div>

          <!-- Digital Receipt / QR Code Panel -->
          <div class="col-lg-4">
            <div class="card-dark p-4 text-center h-100 d-flex flex-column justify-content-center">
              <?php 
                $otp = strtoupper(substr(hash('sha256', "SHOPZONE_OTP_" . $detail['id']), 0, 6)); 
                $orderUrl = "http://localhost/e-commerce/pages/user/orders.php?id=" . $detail['id'];
                $qrData = "Order: {$orderUrl}\nOTP: {$otp}";
                $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qrData);
              ?>
              <h6 class="mb-3" style="color:var(--text-muted);text-transform:uppercase;letter-spacing:1px">Digital Receipt</h6>
              
              <div class="bg-white p-2 rounded mx-auto mb-3 shadow-sm" style="display:inline-block;">
                <img src="<?= $qrUrl ?>" alt="QR Code" style="width:140px;height:140px;display:block;">
              </div>

              <div style="background:rgba(255,255,255,0.05); padding:10px; border-radius:8px; border:1px dashed var(--border);">
                <div style="font-size:.8rem; color:var(--text-muted); margin-bottom:5px">Payment OTP</div>
                <div style="font-size:1.8rem; font-weight:700; letter-spacing:4px; color:var(--accent)"><?= $otp ?></div>
              </div>
              
              <p style="font-size:.8rem; color:var(--text-muted); margin-top:15px; margin-bottom:0">Scan to view receipt details or present code.</p>
            </div>
          </div>
        </div>

      <?php else: ?>
        <!-- Orders List -->
        <h1 class="section-title">My Orders</h1>
        <p class="section-sub">Track and manage your orders</p>

        <?php if ($orders): ?>
          <div class="card-dark">
            <div class="table-responsive">
              <table class="table table-dark-custom mb-0">
                <thead>
                  <tr><th>Order #</th><th>Date</th><th>Total</th><th>Payment</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                  <?php foreach ($orders as $o): ?>
                    <tr>
                      <td><strong>#<?= $o['id'] ?></strong></td>
                      <td style="color:var(--text-muted);font-size:.85rem"><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
                      <td><?= CURRENCY . number_format($o['total_price'], 2) ?></td>
                      <td>
                        <span style="color:<?= $o['payment_status']==='paid'?'var(--success)':'var(--danger)' ?>;font-size:.85rem;font-weight:600">
                          <?= ucfirst($o['payment_status']) ?>
                        </span>
                      </td>
                      <td><span class="badge-status badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                      <td><a href="?id=<?= $o['id'] ?>" class="btn btn-ghost btn-sm">View</a></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php else: ?>
          <div class="card-dark p-5 text-center">
            <div style="font-size:3.5rem;margin-bottom:1rem">📦</div>
            <h4>No orders yet</h4>
            <p style="color:var(--text-muted)">Start shopping to see your orders here.</p>
            <a href="../product/productList.php" class="btn btn-accent mt-2">Browse Products</a>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>