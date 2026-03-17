<?php
// products/product_list.php
require_once '../../database/database.php';
require_once '../includes/auth.php';

$db = getDB();

// Filters
$catId   = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search  = trim($_GET['search'] ?? '');
$sort    = $_GET['sort'] ?? 'newest';

$where  = ['1=1'];
$params = [];

if ($catId) {
  $where[]  = 'p.category_id = ?';
  $params[] = $catId;
}
if ($search) {
  $where[]  = '(p.name LIKE ? OR p.description LIKE ?)';
  $params[] = "%$search%";
  $params[] = "%$search%";
}

// Pagination logic
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 24;
$offset  = ($page - 1) * $perPage;

// Get total count for pagination
$countSql = "SELECT COUNT(*) FROM products p 
             LEFT JOIN categories c ON c.id = p.category_id 
             WHERE " . implode(' AND ', $where) . " AND p.stock > 0";
$countQuery = $db->prepare($countSql);
$countQuery->execute($params);
$totalProducts = (int)$countQuery->fetchColumn();
$totalPages    = ceil($totalProducts / $perPage);

$order = match ($sort) {
  'price_asc'  => 'p.price ASC',
  'price_desc' => 'p.price DESC',
  'name'       => 'p.name ASC',
  default      => 'p.created_at DESC',
};

$sql = "SELECT p.*, c.name AS cat_name FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE " . implode(' AND ', $where) . " AND p.stock > 0
        ORDER BY $order
        LIMIT $perPage OFFSET $offset";

$st = $db->prepare($sql);
$st->execute($params);
$products = $st->fetchAll();

$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$pageTitle = 'All Products';
$depth = '../';
require_once '../includes/header.php';
require_once '../includes/navBar.php';
?>

<div class="container py-4">
  <!-- Filters bar -->
  <form method="GET" class="card-dark p-3 mb-4 d-flex flex-wrap gap-3 align-items-end">
    <div class="flex-grow-1" style="min-width:200px">
      <label class="form-label-dark">Search</label>
      <input type="text" name="search" class="form-control form-control-dark"
        placeholder="Search products…" value="<?= htmlspecialchars($search) ?>">
    </div>
    <div style="min-width:160px">
      <label class="form-label-dark">Category</label>
      <select name="category" class="form-control form-control-dark">
        <option value="">All Categories</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $catId == $c['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="min-width:140px">
      <label class="form-label-dark">Sort By</label>
      <select name="sort" class="form-control form-control-dark">
        <option value="newest" <?= $sort === 'newest'     ? 'selected' : '' ?>>Newest</option>
        <option value="price_asc" <?= $sort === 'price_asc'  ? 'selected' : '' ?>>Price ↑</option>
        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price ↓</option>
        <option value="name" <?= $sort === 'name'       ? 'selected' : '' ?>>Name A-Z</option>
      </select>
    </div>
    <button type="submit" class="btn btn-accent">Filter</button>
    <a href="productList.php" class="btn btn-ghost">Reset</a>
  </form>

  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="section-title mb-0">Products</h1>
      <p style="color:var(--text-muted);font-size:.85rem"><?= $totalProducts ?> items found</p>
    </div>
  </div>

  <?php if ($products): ?>
    <div class="row g-3">
      <?php foreach ($products as $p):
        $effectivePrice = $p['sale_price'] ?? $p['price'];
        $onSale = !empty($p['sale_price']);
      ?>
        <div class="col-6 col-md-4 col-lg-3">
          <div class="product-card">
            <div class="img-wrap">
              <div style="font-size:3rem;padding:1.5rem;display:flex;align-items:center;justify-content:center;height:100%">🛒</div>
            </div>
            <div class="card-body">
              <?php if ($onSale): ?>
                <span class="badge-sale mb-1 d-inline-block">SALE</span>
              <?php endif; ?>
              <?php if ($p['cat_name']): ?>
                <div style="color:var(--text-muted);font-size:.72rem;letter-spacing:.06em;text-transform:uppercase;margin-bottom:.2rem">
                  <?= htmlspecialchars($p['cat_name']) ?>
                </div>
              <?php endif; ?>
              <h5 class="card-title"><?= htmlspecialchars($p['name']) ?></h5>
              <p style="color:var(--text-muted);font-size:.82rem;flex:1">
                <?= htmlspecialchars(substr($p['description'] ?? '', 0, 65)) ?>…
              </p>
              <div class="d-flex align-items-center gap-2 mb-2">
                <span class="price"><?= CURRENCY . number_format($effectivePrice, 2) ?></span>
                <?php if ($onSale): ?>
                  <span class="price-old"><?= CURRENCY . number_format($p['price'], 2) ?></span>
                <?php endif; ?>
              </div>
              <a href="productDetail.php?id=<?= $p['id'] ?>" class="btn btn-accent w-100" style="font-size:.85rem">
                View Details
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="text-center py-5">
      <div style="font-size:4rem;margin-bottom:1rem">🔍</div>
      <h4>No products found</h4>
      <p style="color:var(--text-muted)">Try adjusting your search or filters.</p>
      <a href="productList.php" class="btn btn-accent mt-2">Clear Filters</a>
    </div>
  <?php endif; ?>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
    <nav aria-label="Page navigation" class="mt-5">
      <ul class="pagination pagination-dark justify-content-center">
        <?php
          $qsParts = [];
          if ($search) $qsParts[] = 'search=' . urlencode($search);
          if ($catId)  $qsParts[] = 'category=' . urlencode((string)$catId);
          if ($sort !== 'newest') $qsParts[] = 'sort=' . urlencode($sort);
          
          $qs = implode('&', $qsParts);
          $qs = $qs ? '&' . $qs : '';
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

<?php require_once '../includes/footer.php'; ?>