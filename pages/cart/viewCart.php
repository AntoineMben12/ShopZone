<?php
// cart/view_cart.php
require_once '../../database/database.php';
require_once '../includes/auth.php';
requireLogin('../auth/login.php');

$db  = getDB();
$uid = currentUserId();

// Handle update/remove actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action'] ?? '';
    $productId = (int)($_POST['product_id'] ?? 0);

    if ($action === 'remove' && $productId) {
        $db->prepare("DELETE FROM cart WHERE user_id=? AND product_id=?")
           ->execute([$uid, $productId]);
        setFlashMessage('success', 'Item removed from cart.');
    }

    if ($action === 'update' && $productId) {
        $qty = max(1, (int)($_POST['quantity'] ?? 1));
        // Clamp to stock
        $stock = $db->prepare("SELECT stock FROM products WHERE id=?");
        $stock->execute([$productId]);
        $stock = (int)($stock->fetchColumn() ?: 1);
        $qty   = min($qty, $stock);
        $db->prepare("UPDATE cart SET quantity=? WHERE user_id=? AND product_id=?")
           ->execute([$qty, $uid, $productId]);
    }

    header('Location: viewCart.php');
    exit;
}

// Fetch cart items with product info
$items = $db->prepare("
    SELECT c.quantity, p.id AS product_id, p.name, p.price, p.sale_price, p.image, p.stock
    FROM cart c
    JOIN products p ON p.id = c.product_id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
");
$items->execute([$uid]);
$items = $items->fetchAll();

// Calculate totals
$subtotal = 0;
foreach ($items as $item) {
    $effectivePrice = $item['sale_price'] ?? $item['price'];
    $subtotal += $effectivePrice * $item['quantity'];
}
$shipping = $subtotal > 0 ? ($subtotal >= 100 ? 0 : 9.99) : 0;
$total    = $subtotal + $shipping;

$pageTitle = 'My Cart';
$depth = '../';
require_once '../includes/header.php';
require_once '../includes/navBar.php';
?>

<div class="container py-4">
  <?php renderFlash(); ?>

  <h1 class="section-title">Shopping Cart</h1>
  <p class="section-sub"><?= count($items) ?> item(s) in your cart</p>

  <?php if ($items): ?>
    <div class="row g-4">
      <!-- Cart Items -->
      <div class="col-lg-8">

        <?php foreach ($items as $item):
          $effectivePrice = $item['sale_price'] ?? $item['price'];
          $onSale = !empty($item['sale_price']);
          $lineTotal = $effectivePrice * $item['quantity'];
        ?>
          <div class="cart-item">
            <div class="d-flex align-items-center gap-3">
              <!-- Thumb -->
              <div style="width:72px;height:72px;background:var(--bg3);border-radius:8px;
                          overflow:hidden;flex-shrink:0;display:flex;align-items:center;
                          justify-content:center;font-size:1.8rem">
                <?php if (!empty($item['image']) && $item['image'] !== 'placeholder.jpg'): ?>
                  <img src="../../Assets/images/<?= htmlspecialchars($item['image']) ?>"
                       alt="<?= htmlspecialchars($item['name']) ?>"
                       style="width:100%;height:100%;object-fit:cover;border-radius:8px">
                <?php else: ?>
                  🛒
                <?php endif; ?>
              </div>

              <!-- Name & price -->
              <div class="flex-grow-1">
                <a href="../product/productDetail.php?id=<?= $item['product_id'] ?>"
                   style="font-weight:600;color:var(--text);font-size:.95rem">
                  <?= htmlspecialchars($item['name']) ?>
                </a>
                <div class="d-flex align-items-center gap-2 mt-1">
                  <span style="color:var(--accent);font-weight:700"><?= CURRENCY . number_format($effectivePrice, 2) ?></span>
                  <?php if ($onSale): ?>
                    <span style="text-decoration:line-through;color:var(--text-muted);font-size:.8rem">
                      <?= CURRENCY . number_format($item['price'], 2) ?>
                    </span>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Qty update form -->
              <form method="POST" class="d-flex align-items-center gap-2">
                <input type="hidden" name="action"     value="update">
                <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                <button type="button" class="btn btn-ghost qty-btn"
                        data-target="#qty-<?= $item['product_id'] ?>" data-dir="down"
                        style="width:30px;height:30px;padding:0;font-size:.9rem">−</button>
                <input id="qty-<?= $item['product_id'] ?>" type="number" name="quantity"
                       class="qty-input" value="<?= $item['quantity'] ?>"
                       min="1" max="<?= $item['stock'] ?>">
                <button type="button" class="btn btn-ghost qty-btn"
                        data-target="#qty-<?= $item['product_id'] ?>" data-dir="up"
                        style="width:30px;height:30px;padding:0;font-size:.9rem">+</button>
                <button type="submit" class="btn btn-ghost btn-sm" title="Update">
                  <i class="bi bi-check2"></i>
                </button>
              </form>

              <!-- Line total -->
              <div style="min-width:70px;text-align:right;font-weight:700">
                <?= CURRENCY . number_format($lineTotal, 2) ?>
              </div>

              <!-- Remove -->
              <form method="POST">
                <input type="hidden" name="action"     value="remove">
                <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--danger)"
                        data-confirm="Remove this item from cart?">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>

        <div class="d-flex justify-content-between mt-3">
                <a href="../product/productList.php" class="btn btn-ghost">
            <i class="bi bi-arrow-left me-1"></i>Continue Shopping
          </a>
        </div>
      </div>

      <!-- Summary -->
      <div class="col-lg-4">
        <div class="card-dark p-4 position-sticky" style="top:80px">
          <h5 class="mb-3">Order Summary</h5>

          <div class="d-flex justify-content-between mb-2" style="font-size:.9rem">
            <span style="color:var(--text-muted)">Subtotal</span>
            <span><?= CURRENCY . number_format($subtotal, 2) ?></span>
          </div>
          <div class="d-flex justify-content-between mb-2" style="font-size:.9rem">
            <span style="color:var(--text-muted)">Shipping</span>
            <?php if ($shipping == 0): ?>
              <span style="color:var(--success)">FREE</span>
            <?php else: ?>
              <span><?= CURRENCY . number_format($shipping, 2) ?></span>
            <?php endif; ?>
          </div>

          <?php if ($subtotal < 100 && $subtotal > 0): ?>
            <div class="mb-2 p-2 rounded" style="background:rgba(245,166,35,.08);font-size:.78rem;color:var(--text-muted)">
              💡 Add <?= CURRENCY . number_format(100 - $subtotal, 2) ?> more for FREE shipping!
            </div>
          <?php endif; ?>

          <div class="divider"></div>

          <div class="d-flex justify-content-between mb-4">
            <strong style="font-size:1.05rem">Total</strong>
            <strong style="font-size:1.2rem;color:var(--accent)"><?= CURRENCY . number_format($total, 2) ?></strong>
          </div>

          <a href="checkout.php" class="btn btn-accent w-100 py-2">
            <i class="bi bi-shield-check me-2"></i>Proceed to Checkout
          </a>

          <div class="text-center mt-3" style="color:var(--text-muted);font-size:.75rem">
            <i class="bi bi-lock-fill me-1"></i>Secure checkout powered by ShopZone
          </div>
        </div>
      </div>
    </div>

  <?php else: ?>
    <div class="text-center py-5">
      <div style="font-size:5rem;margin-bottom:1rem">🛒</div>
      <h3>Your cart is empty</h3>
      <p style="color:var(--text-muted)">Start adding products to see them here.</p>
      <a href="../product/productList.php" class="btn btn-accent mt-2 px-4">Browse Products</a>
    </div>
  <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>