<?php
// cart/checkout.php
require_once '../../database/database.php';
require_once '../includes/auth.php';
require_once '../includes/mailer.php';
requireLogin('../auth/login.php');

$db  = getDB();
$uid = currentUserId();

// Load cart
$items = $db->prepare("
    SELECT c.quantity, p.id AS product_id, p.name, p.price, p.sale_price, p.stock, p.image
    FROM cart c JOIN products p ON p.id=c.product_id
    WHERE c.user_id=?
");
$items->execute([$uid]);
$items = $items->fetchAll();

if (!$items) {
    setFlashMessage('error', 'Your cart is empty.');
    header('Location: viewCart.php');
    exit;
}

// Totals
$subtotal = 0;
foreach ($items as $item) {
    $ep = $item['sale_price'] ?? $item['price'];
    $subtotal += $ep * $item['quantity'];
}
$shipping = $subtotal >= 100 ? 0 : 9.99;
$total    = $subtotal + $shipping;

// Load user info for prefill
$userInfo = $db->prepare("SELECT * FROM users WHERE id=?");
$userInfo->execute([$uid]);
$userInfo = $userInfo->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['address'] ?? '');
    $method  = $_POST['payment_method'] ?? 'cod';

    if (!$address) $errors[] = 'Shipping address is required.';

    if (!$errors) {
        $db->beginTransaction();
        try {
            // Create order
            $db->prepare("
                INSERT INTO orders (user_id, total_price, status, payment_method, payment_status, shipping_address)
                VALUES (?, ?, 'pending', ?, ?, ?)
            ")->execute([$uid, $total, $method, $method === 'cod' ? 'unpaid' : 'paid', $address]);

            $orderId = (int)$db->lastInsertId();

            // Insert order items & decrement stock
            foreach ($items as $item) {
                $ep = $item['sale_price'] ?? $item['price'];
                $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?,?,?,?)")
                   ->execute([$orderId, $item['product_id'], $item['quantity'], $ep]);
                $db->prepare("UPDATE products SET stock = stock - ? WHERE id=?")
                   ->execute([$item['quantity'], $item['product_id']]);
            }

            // Clear cart
            $db->prepare("DELETE FROM cart WHERE user_id=?")->execute([$uid]);

            $db->commit();

            // Send Email Receipt
            try {
                sendOrderReceiptEmail(
                    $userInfo['email'],
                    $userInfo['name'],
                    $orderId,
                    $items,
                    $subtotal,
                    $shipping,
                    $total,
                    $address
                );
            } catch (Exception $e) {
                // Email failed, but the order is still valid in the DB! We just log it.
                error_log("Failed to send order email: " . $e->getMessage());
            }

            setFlashMessage('success', "Order #$orderId placed successfully! Thank you for shopping with us. 🎉");
            header('Location: ../user/orders.php?id=' . $orderId);
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Something went wrong. Please try again.';
        }
    }
}

$pageTitle = 'Checkout';
$depth = '../';
require_once '../includes/header.php';
require_once '../includes/navBar.php';
?>

<div class="container py-4" style="max-width:960px">
  <?php renderFlash(); ?>

  <!-- Steps indicator -->
  <div class="d-flex align-items-center gap-3 mb-4" style="font-size:.85rem">
    <span style="color:var(--text-muted)"><a href="viewCart.php" style="color:var(--text-muted)"><i class="bi bi-cart3 me-1"></i>Cart</a></span>
    <span style="color:var(--border)">›</span>
    <span style="color:var(--accent);font-weight:600"><i class="bi bi-credit-card me-1"></i>Checkout</span>
    <span style="color:var(--border)">›</span>
    <span style="color:var(--text-muted)">Confirmation</span>
  </div>

  <div class="row g-4">
    <!-- Left: Form -->
    <div class="col-lg-7">
      <?php foreach ($errors as $e): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($e) ?></div>
      <?php endforeach; ?>

      <form method="POST">
        <!-- Shipping -->
        <div class="card-dark p-4 mb-3">
          <h5 class="mb-3"><i class="bi bi-geo-alt me-2"></i>Shipping Address</h5>
          <div class="mb-3">
            <label class="form-label-dark">Full Name</label>
            <input type="text" class="form-control form-control-dark" value="<?= htmlspecialchars($userInfo['name']) ?>" readonly>
          </div>
          <div class="mb-0">
            <label class="form-label-dark">Delivery Address *</label>
            <textarea name="address" class="form-control form-control-dark" rows="3"
                      placeholder="Street, City, Country, Postal Code" required><?= htmlspecialchars($userInfo['address'] ?? '') ?></textarea>
          </div>
        </div>

        <!-- Payment Method -->
        <div class="card-dark p-4 mb-3">
          <h5 class="mb-3"><i class="bi bi-credit-card me-2"></i>Payment Method</h5>

          <?php $methods = [
            ['cod',    'bi-truck',         'Cash on Delivery',    'Pay when your order arrives'],
            ['stripe', 'bi-credit-card-2-front', 'Credit / Debit Card', 'Powered by Stripe (demo)'],
            ['paypal', 'bi-paypal',        'PayPal',              'Fast & secure PayPal checkout'],
            ['mobile', 'bi-phone',         'Mobile Money',        'MTN, Orange, Wave etc.'],
          ]; ?>

          <div class="d-flex flex-column gap-2">
            <?php foreach ($methods as [$val, $icon, $label, $sub]): ?>
              <label class="d-flex align-items-center gap-3 p-3 rounded" style="cursor:pointer;border:1px solid var(--border);transition:border-color .2s"
                     onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='var(--border)'">
                <input type="radio" name="payment_method" value="<?= $val ?>"
                       <?= ($val === 'cod') ? 'checked' : '' ?>
                       style="accent-color:var(--accent);width:16px;height:16px">
                <span style="font-size:1.5rem;color:var(--accent);width:28px;text-align:center">
                  <i class="bi <?= $icon ?>"></i>
                </span>
                <div>
                  <div style="font-weight:600;font-size:.9rem"><?= $label ?></div>
                  <div style="color:var(--text-muted);font-size:.78rem"><?= $sub ?></div>
                </div>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <button type="submit" class="btn btn-accent w-100 py-3" style="font-size:1.05rem">
          <i class="bi bi-shield-check me-2"></i>Place Order — <?= CURRENCY . number_format($total, 2) ?>
        </button>
      </form>
    </div>

    <!-- Right: Summary -->
    <div class="col-lg-5">
      <div class="card-dark p-4 position-sticky" style="top:80px">
        <h5 class="mb-3">Order Summary</h5>

        <div style="max-height:280px;overflow-y:auto;margin-bottom:1rem">
          <?php foreach ($items as $item):
            $ep = $item['sale_price'] ?? $item['price'];
          ?>
            <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid var(--border);gap:.75rem">
              <div class="d-flex align-items-center gap-2">
                <div style="width:44px;height:44px;border-radius:6px;background:var(--bg3);overflow:hidden;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:1.1rem">
                  <?php if (!empty($item['image']) && $item['image'] !== 'placeholder.jpg'): ?>
                    <img src="../../Assets/images/<?= htmlspecialchars($item['image']) ?>"
                         alt="<?= htmlspecialchars($item['name']) ?>"
                         style="width:100%;height:100%;object-fit:cover">
                  <?php else: ?>
                    🛒
                  <?php endif; ?>
                </div>
                <div>
                  <div style="font-size:.88rem;font-weight:500"><?= htmlspecialchars($item['name']) ?></div>
                  <div style="color:var(--text-muted);font-size:.78rem">Qty: <?= $item['quantity'] ?></div>
                </div>
              </div>
              <div style="font-weight:600;font-size:.9rem;white-space:nowrap"><?= CURRENCY . number_format($ep * $item['quantity'], 2) ?></div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="d-flex justify-content-between mb-1" style="font-size:.9rem">
          <span style="color:var(--text-muted)">Subtotal</span>
          <span><?= CURRENCY . number_format($subtotal, 2) ?></span>
        </div>
        <div class="d-flex justify-content-between mb-2" style="font-size:.9rem">
          <span style="color:var(--text-muted)">Shipping</span>
          <span style="color:<?= $shipping===0 ? 'var(--success)' : 'inherit' ?>">
            <?= $shipping === 0 ? 'FREE' : CURRENCY . number_format($shipping, 2) ?>
          </span>
        </div>
        <div class="divider"></div>
        <div class="d-flex justify-content-between">
          <strong>Total</strong>
          <strong style="color:var(--accent);font-size:1.15rem"><?= CURRENCY . number_format($total, 2) ?></strong>
        </div>

        <div class="mt-3 p-2 rounded text-center" style="background:rgba(46,204,113,.08);font-size:.78rem;color:var(--success)">
          <i class="bi bi-shield-fill-check me-1"></i>Protected by ShopZone Buyer Guarantee
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>