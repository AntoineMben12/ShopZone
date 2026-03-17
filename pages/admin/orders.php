<?php
// admin/orders.php
require_once '../../database/database.php';
require_once '../includes/auth.php';
requireAdmin('../index.php');

$db = getDB();

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId   = (int)$_POST['order_id'];
    $status    = in_array($_POST['status'], ['pending','processing','shipped','delivered','cancelled'])
                 ? $_POST['status'] : 'pending';
    $payStatus = in_array($_POST['payment_status'], ['unpaid','paid','refunded'])
                 ? $_POST['payment_status'] : 'unpaid';
    $db->prepare("UPDATE orders SET status=?, payment_status=? WHERE id=?")
       ->execute([$status, $payStatus, $orderId]);
    setFlashMessage('success', "Order #$orderId updated.");
    header('Location: orders.php' . ($orderId ? "?id=$orderId" : '')); exit;
}

// Single order detail
$detail = null;
if (isset($_GET['id'])) {
    $st = $db->prepare("SELECT o.*, u.name AS user_name, u.email AS user_email FROM orders o JOIN users u ON u.id=o.user_id WHERE o.id=?");
    $st->execute([(int)$_GET['id']]);
    $detail = $st->fetch();
    if ($detail) {
        $items = $db->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.order_id=?");
        $items->execute([$detail['id']]);
        $detail['items'] = $items->fetchAll();
    }
}

// Filters
$statusFilter = $_GET['status'] ?? '';
$where  = ['1=1'];
$params = [];
if ($statusFilter) { $where[] = 'o.status=?'; $params[] = $statusFilter; }

$orders = $db->prepare("
    SELECT o.*, u.name AS user_name
    FROM orders o JOIN users u ON u.id=o.user_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY o.created_at DESC
");
$orders->execute($params);
$orders = $orders->fetchAll();

$pageTitle = 'Manage Orders';
$depth = '../';
require_once '../includes/header.php';
require_once '../includes/navBar.php';
?>

<div class="container-fluid py-4 px-4">
  <div class="row g-4">
    <div class="col-xl-2 col-lg-3">
      <div class="dash-sidebar">
        <div class="user-info d-flex align-items-center gap-2">
          <div class="user-avatar" style="background:var(--danger)">A</div>
          <div><div style="font-weight:600;font-size:.88rem"><?= currentUserName() ?></div>
            <span class="badge-role-admin">Admin</span></div>
        </div>
        <nav class="nav flex-column gap-1" style="font-size:.85rem">
          <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
          <a class="nav-link" href="product.php"><i class="bi bi-box"></i> Products</a>
          <a class="nav-link" href="addProduct.php"><i class="bi bi-plus-circle"></i> Add Product</a>
          <a class="nav-link" href="users.php"><i class="bi bi-people"></i> Users</a>
          <a class="nav-link active" href="orders.php"><i class="bi bi-bag-check"></i> Orders</a>
          <hr style="border-color:var(--border);margin:.4rem 0">
          <a class="nav-link" href="../../index.php"><i class="bi bi-house"></i> View Site</a>
          <a class="nav-link" href="../auth/logout.php" style="color:var(--danger)!important">
            <i class="bi bi-box-arrow-right"></i> Logout
          </a>
        </nav>
      </div>
    </div>

    <div class="col-xl-10 col-lg-9">
      <?php renderFlash(); ?>

      <?php if ($detail): ?>
        <!-- ── Order Detail ─────────────────────────── -->
        <div class="d-flex align-items-center gap-3 mb-4">
          <a href="orders.php" class="btn btn-ghost btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
          <h1 class="section-title mb-0">Order #<?= $detail['id'] ?></h1>
          <span class="badge-status badge-<?= $detail['status'] ?>"><?= ucfirst($detail['status']) ?></span>
        </div>

        <div class="row g-3 mb-4">
          <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
              <div class="stat-label">Customer</div>
              <div style="font-weight:600"><?= htmlspecialchars($detail['user_name']) ?></div>
              <div style="color:var(--text-muted);font-size:.78rem"><?= htmlspecialchars($detail['user_email']) ?></div>
            </div>
          </div>
          <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
              <div class="stat-label">Order Total</div>
              <div class="stat-value"><?= CURRENCY . number_format($detail['total_price'], 2) ?></div>
            </div>
          </div>
          <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
              <div class="stat-label">Payment</div>
              <div style="color:<?= $detail['payment_status']==='paid'?'var(--success)':'var(--danger)' ?>;font-weight:600">
                <?= ucfirst($detail['payment_status']) ?> (<?= $detail['payment_method'] ?>)
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
              <div class="stat-label">Order Date</div>
              <div><?= date('M j, Y H:i', strtotime($detail['created_at'])) ?></div>
            </div>
          </div>
        </div>

        <div class="row g-4">
          <!-- Items -->
          <div class="col-lg-7">
            <div class="card-dark p-3">
              <h5 class="mb-3">Order Items</h5>
              <?php foreach ($detail['items'] as $item): ?>
                <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--border)">
                  <div>
                    <div style="font-weight:500"><?= htmlspecialchars($item['name']) ?></div>
                    <div style="color:var(--text-muted);font-size:.8rem">
                      <?= $item['quantity'] ?> × <?= CURRENCY . number_format($item['price'], 2) ?>
                    </div>
                  </div>
                  <strong><?= CURRENCY . number_format($item['price'] * $item['quantity'], 2) ?></strong>
                </div>
              <?php endforeach; ?>
              <div class="text-end pt-2 pe-1">
                <strong>Total: <?= CURRENCY . number_format($detail['total_price'], 2) ?></strong>
              </div>
            </div>

            <?php if ($detail['shipping_address']): ?>
              <div class="card-dark p-3 mt-3">
                <h5 class="mb-2">Shipping Address</h5>
                <p style="color:var(--text-muted);font-size:.9rem;margin:0"><?= nl2br(htmlspecialchars($detail['shipping_address'])) ?></p>
              </div>
            <?php endif; ?>
          </div>

          <!-- Status update -->
          <div class="col-lg-5">
            <div class="card-dark p-4">
              <h5 class="mb-3">Update Order</h5>
              <form method="POST">
                <input type="hidden" name="order_id" value="<?= $detail['id'] ?>">
                <div class="mb-3">
                  <label class="form-label-dark">Order Status</label>
                  <select name="status" class="form-control form-control-dark">
                    <?php foreach (['pending','processing','shipped','delivered','cancelled'] as $s): ?>
                      <option value="<?= $s ?>" <?= $detail['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="mb-4">
                  <label class="form-label-dark">Payment Status</label>
                  <select name="payment_status" class="form-control form-control-dark">
                    <?php foreach (['unpaid','paid','refunded'] as $s): ?>
                      <option value="<?= $s ?>" <?= $detail['payment_status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <button name="update_status" class="btn btn-accent w-100">
                  <i class="bi bi-check2 me-2"></i>Save Changes
                </button>
              </form>
            </div>
          </div>
        </div>

      <?php else: ?>
        <!-- ── Orders List ─────────────────────────── -->
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h1 class="section-title mb-0">Orders</h1>
            <p style="color:var(--text-muted);font-size:.85rem"><?= count($orders) ?> orders</p>
          </div>
        </div>

        <!-- Status filter pills -->
        <div class="d-flex flex-wrap gap-2 mb-3">
          <?php $statuses = [''=> 'All', 'pending'=>'Pending', 'processing'=>'Processing',
                             'shipped'=>'Shipped', 'delivered'=>'Delivered', 'cancelled'=>'Cancelled'];
          foreach ($statuses as $val => $label): ?>
            <a href="?status=<?= $val ?>"
               class="btn btn-sm <?= $statusFilter===$val ? 'btn-accent' : 'btn-ghost' ?>">
              <?= $label ?>
            </a>
          <?php endforeach; ?>
        </div>

        <div class="card-dark">
          <div class="table-responsive">
            <table class="table table-dark-custom mb-0" style="font-size:.85rem">
              <thead>
                <tr><th>#</th><th>Customer</th><th>Total</th><th>Method</th><th>Payment</th><th>Status</th><th>Date</th><th></th></tr>
              </thead>
              <tbody>
                <?php foreach ($orders as $o): ?>
                  <tr>
                    <td><strong>#<?= $o['id'] ?></strong></td>
                    <td><?= htmlspecialchars($o['user_name']) ?></td>
                    <td><?= CURRENCY . number_format($o['total_price'], 2) ?></td>
                    <td style="color:var(--text-muted)"><?= ucfirst($o['payment_method'] ?? 'N/A') ?></td>
                    <td><span style="color:<?= $o['payment_status']==='paid'?'var(--success)':'var(--danger)' ?>;font-weight:600">
                        <?= ucfirst($o['payment_status']) ?></span></td>
                    <td><span class="badge-status badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                    <td style="color:var(--text-muted)"><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
                    <td><a href="?id=<?= $o['id'] ?>" class="btn btn-ghost btn-sm">Manage</a></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>