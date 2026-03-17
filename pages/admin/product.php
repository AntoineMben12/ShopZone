<?php
// admin/products.php
require_once '../../database/database.php';
require_once '../includes/auth.php';
requireAdmin('../index.php');

$db = getDB();

// Delete action
if (isset($_GET['delete'])) {
    $db->prepare("DELETE FROM products WHERE id=?")->execute([(int)$_GET['delete']]);
    setFlashMessage('success', 'Product deleted.');
    header('Location: product.php'); exit;
}

// Toggle stock
if (isset($_GET['toggle_featured'])) {
    $db->prepare("UPDATE products SET featured = 1 - featured WHERE id=?")
       ->execute([(int)$_GET['toggle_featured']]);
    header('Location: product.php'); exit;
}

$search = trim($_GET['search'] ?? '');
$where  = $search ? "WHERE p.name LIKE ?" : '';
$params = $search ? ["%$search%"] : [];

// Pagination logic
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

// Get total count for pagination
$countQuery = $db->prepare("SELECT COUNT(*) FROM products p " . $where);
$countQuery->execute($params);
$totalProducts = (int)$countQuery->fetchColumn();
$totalPages    = ceil($totalProducts / $perPage);

$products = $db->prepare("
    SELECT p.*, c.name AS cat_name
    FROM products p LEFT JOIN categories c ON c.id=p.category_id
    $where ORDER BY p.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$products->execute($params);
$products = $products->fetchAll();

$pageTitle = 'Manage Products';
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
          <a class="nav-link active" href="product.php"><i class="bi bi-box"></i> Products</a>
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

    <div class="col-xl-10 col-lg-9">
      <?php renderFlash(); ?>

      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h1 class="section-title mb-0">Products</h1>
          <p style="color:var(--text-muted);font-size:.85rem"><?= $totalProducts ?> products total</p>
        </div>
        <a href="addProduct.php" class="btn btn-accent"><i class="bi bi-plus me-1"></i>Add Product</a>
      </div>

      <!-- Search -->
      <form method="GET" class="mb-3 d-flex gap-2">
        <input type="text" name="search" class="form-control form-control-dark" style="max-width:300px"
               placeholder="Search products…" value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-ghost">Search</button>
        <?php if ($search): ?>
          <a href="product.php" class="btn btn-ghost">Clear</a>
        <?php endif; ?>
      </form>

      <div class="card-dark">
        <div class="table-responsive">
          <table class="table table-dark-custom mb-0">
            <thead>
              <tr>
                <th>ID</th><th>Product</th><th>Category</th>
                <th>Price</th><th>Sale</th><th>Stock</th>
                <th>Featured</th><th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($products as $p): ?>
                <tr>
                  <td style="color:var(--text-muted)">#<?= $p['id'] ?></td>
                  <td>
                    <div style="font-weight:500"><?= htmlspecialchars($p['name']) ?></div>
                    <div style="font-size:.75rem;color:var(--text-muted)"><?= htmlspecialchars(substr($p['description']??'',0,45)) ?>…</div>
                  </td>
                  <td style="color:var(--text-muted);font-size:.85rem"><?= htmlspecialchars($p['cat_name'] ?? '—') ?></td>
                  <td><?= CURRENCY . number_format($p['price'], 2) ?></td>
                  <td>
                    <?php if ($p['sale_price']): ?>
                      <span style="color:var(--success)"><?= CURRENCY . number_format($p['sale_price'], 2) ?></span>
                    <?php else: ?>
                      <span style="color:var(--text-muted)">—</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span style="color:<?= $p['stock'] < 5 ? 'var(--danger)' : ($p['stock'] < 20 ? 'var(--accent)' : 'var(--success)') ?>;font-weight:600">
                      <?= $p['stock'] ?>
                    </span>
                  </td>
                  <td>
                    <a href="?toggle_featured=<?= $p['id'] ?>" title="Toggle featured">
                      <?= $p['featured'] ? '⭐' : '☆' ?>
                    </a>
                  </td>
                  <td>
                    <div class="d-flex gap-1">
                      <a href="addProduct.php?edit=<?= $p['id'] ?>" class="btn btn-ghost btn-sm" title="Edit">
                        <i class="bi bi-pencil"></i>
                      </a>
                      <a href="?delete=<?= $p['id'] ?>" class="btn btn-ghost btn-sm"
                         style="color:var(--danger)"
                         data-confirm="Delete '<?= htmlspecialchars($p['name']) ?>'?">
                        <i class="bi bi-trash"></i>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
          <ul class="pagination pagination-dark justify-content-center">
            <?php
              $qs = $search ? '&search=' . urlencode($search) : '';
            ?>
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
              <a class="page-link" href="?page=<?= $page - 1 ?><?= $qs ?>">Previous</a>
            </li>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?><?= $qs ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>

            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
              <a class="page-link" href="?page=<?= $page + 1 ?><?= $qs ?>">Next</a>
            </li>
          </ul>
        </nav>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>