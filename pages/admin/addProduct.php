<?php
// admin/add_product.php — create or edit a product
require_once '../../database/database.php';
require_once '../includes/auth.php';
requireAdmin('../index.php');

$db = getDB();

// Load for edit
$editId  = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$product = ['id'=>0,'name'=>'','price'=>'','sale_price'=>'','description'=>'','stock'=>'','category_id'=>'','featured'=>0,'image'=>''];

if ($editId) {
    $st = $db->prepare("SELECT * FROM products WHERE id=?");
    $st->execute([$editId]);
    $product = $st->fetch() ?: $product;
}

$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']        ?? '');
    $price       = (float)($_POST['price']    ?? 0);
    $salePrice   = trim($_POST['sale_price']  ?? '');
    $description = trim($_POST['description'] ?? '');
    $stock       = (int)($_POST['stock']      ?? 0);
    $catId       = (int)($_POST['category_id']?? 0);
    $featured    = isset($_POST['featured']) ? 1 : 0;
    $postId      = (int)($_POST['product_id'] ?? 0);

    // Slug
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));

    if (!$name)       $errors[] = 'Product name is required.';
    if ($price <= 0)  $errors[] = 'Price must be greater than 0.';
    if ($stock < 0)   $errors[] = 'Stock cannot be negative.';

    $salePriceFinal = ($salePrice !== '' && is_numeric($salePrice)) ? (float)$salePrice : null;

    // Handle image upload
    $imageName = $product['image'] ?: 'placeholder.jpg';
    if (!empty($_FILES['image']['name'])) {
        $ext       = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed   = ['jpg','jpeg','png','webp'];
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Image must be JPG, PNG or WEBP.';
        } else {
            $imageName = uniqid() . '.' . $ext;
            $dest      = '../../Assets/images/' . $imageName;
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                $errors[] = 'Image upload failed.';
                $imageName = $product['image'] ?: 'placeholder.jpg';
            }
        }
    }

    if (!$errors) {
        if ($postId) {
            // Update
            // Ensure slug unique (excluding self)
            $check = $db->prepare("SELECT id FROM products WHERE slug=? AND id!=?");
            $check->execute([$slug, $postId]);
            if ($check->fetch()) $slug .= '-' . $postId;

            $db->prepare("
                UPDATE products SET name=?,slug=?,price=?,sale_price=?,description=?,
                stock=?,category_id=?,featured=?,image=?,updated_at=NOW()
                WHERE id=?
            ")->execute([$name,$slug,$price,$salePriceFinal,$description,$stock,$catId,$featured,$imageName,$postId]);
            setFlashMessage('success','Product updated.');
        } else {
            // Check slug unique
            $check = $db->prepare("SELECT id FROM products WHERE slug=?");
            $check->execute([$slug]);
            if ($check->fetch()) $slug .= '-' . time();

            $db->prepare("
                INSERT INTO products (name,slug,price,sale_price,description,stock,category_id,featured,image)
                VALUES (?,?,?,?,?,?,?,?,?)
            ")->execute([$name,$slug,$price,$salePriceFinal,$description,$stock,$catId,$featured,$imageName]);
            setFlashMessage('success','Product added successfully!');
        }
        header('Location: product.php'); exit;
    }

    // Repopulate
    $product = array_merge($product, compact('name','price','description','stock','featured'));
    $product['sale_price']   = $salePrice;
    $product['category_id']  = $catId;
}

$isEdit = (bool)($editId ?: (int)($_POST['product_id'] ?? 0));
$pageTitle = $isEdit ? 'Edit Product' : 'Add Product';
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
          <a class="nav-link active" href="addProduct.php"><i class="bi bi-plus-circle"></i> Add Product</a>
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
      <div class="d-flex align-items-center gap-3 mb-4">
        <a href="products.php" class="btn btn-ghost btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
        <h1 class="section-title mb-0"><?= $editId ? 'Edit Product' : 'Add New Product' ?></h1>
      </div>

      <?php foreach ($errors as $e): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($e) ?></div>
      <?php endforeach; ?>

      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

        <div class="row g-4">
          <!-- Left: Core fields -->
          <div class="col-lg-8">
            <div class="card-dark p-4 mb-3">
              <h5 class="mb-3">Product Information</h5>

              <div class="mb-3">
                <label class="form-label-dark">Product Name *</label>
                <input type="text" name="name" class="form-control form-control-dark"
                       value="<?= htmlspecialchars($product['name']) ?>" required
                       placeholder="e.g. Wireless Headphones Pro">
              </div>

              <div class="mb-3">
                <label class="form-label-dark">Description</label>
                <textarea name="description" class="form-control form-control-dark" rows="4"
                          placeholder="Describe the product…"><?= htmlspecialchars($product['description']) ?></textarea>
              </div>

              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label-dark">Price (<?= CURRENCY ?>) *</label>
                  <input type="number" name="price" class="form-control form-control-dark"
                         value="<?= htmlspecialchars($product['price']) ?>"
                         step="0.01" min="0.01" required placeholder="0.00">
                </div>
                <div class="col-md-4">
                  <label class="form-label-dark">Sale Price (optional)</label>
                  <input type="number" name="sale_price" class="form-control form-control-dark"
                         value="<?= htmlspecialchars($product['sale_price'] ?? '') ?>"
                         step="0.01" min="0" placeholder="Leave blank = no sale">
                </div>
                <div class="col-md-4">
                  <label class="form-label-dark">Stock Quantity *</label>
                  <input type="number" name="stock" class="form-control form-control-dark"
                         value="<?= htmlspecialchars($product['stock']) ?>"
                         min="0" required placeholder="0">
                </div>
              </div>
            </div>

            <!-- Image -->
            <div class="card-dark p-4">
              <h5 class="mb-3">Product Image</h5>
              <?php if ($product['image'] && $product['image'] !== 'placeholder.jpg'): ?>
                <div class="mb-3">
                  <img src="../assets/images/<?= htmlspecialchars($product['image']) ?>"
                       style="height:120px;border-radius:8px;border:1px solid var(--border)">
                  <p style="color:var(--text-muted);font-size:.78rem;margin-top:.4rem">Current image — upload new to replace.</p>
                </div>
              <?php endif; ?>
              <input type="file" name="image" class="form-control form-control-dark" accept=".jpg,.jpeg,.png,.webp">
              <p style="color:var(--text-muted);font-size:.75rem;margin-top:.4rem">Accepted: JPG, PNG, WEBP — max 2 MB</p>
            </div>
          </div>

          <!-- Right: Meta -->
          <div class="col-lg-4">
            <div class="card-dark p-4 mb-3">
              <h5 class="mb-3">Categorization</h5>
              <div class="mb-3">
                <label class="form-label-dark">Category</label>
                <select name="category_id" class="form-control form-control-dark">
                  <option value="">Uncategorized</option>
                  <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>"
                      <?= $product['category_id'] == $c['id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($c['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-check d-flex align-items-center gap-2 mt-3">
                <input type="checkbox" name="featured" id="featured" class="form-check-input"
                       style="accent-color:var(--accent);width:18px;height:18px"
                       <?= $product['featured'] ? 'checked' : '' ?>>
                <label for="featured" class="form-check-label" style="cursor:pointer">
                  ⭐ Featured on homepage
                </label>
              </div>
            </div>

            <button type="submit" class="btn btn-accent w-100 py-2">
              <i class="bi bi-check2 me-2"></i><?= $editId ? 'Update Product' : 'Create Product' ?>
            </button>
            <a href="product.php" class="btn btn-ghost w-100 mt-2">Cancel</a>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>