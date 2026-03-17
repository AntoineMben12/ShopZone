<?php
// cart/addToCart.php
require_once '../../database/database.php';
require_once '../includes/auth.php';

$isAjax = !empty($_POST['ajax']);

if (!isLoggedIn()) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Please log in to add items to your cart.']);
        exit;
    }
    setFlashMessage('Please log in to add items to your cart.', 'error');
    header('Location: /e-commerce/pages/auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /e-commerce/pages/product/productList.php');
    exit;
}

$productId = (int)($_POST['product_id'] ?? 0);
$quantity  = max(1, (int)($_POST['quantity'] ?? 1));
$redirect  = $_POST['redirect'] ?? 'list';

if (!$productId) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid product.']);
        exit;
    }
    setFlashMessage('Invalid product.', 'error');
    header('Location: /e-commerce/pages/product/productList.php');
    exit;
}

$db  = getDB();
$uid = currentUserId();

// Validate product exists and has stock
$st = $db->prepare("SELECT id, name, stock FROM products WHERE id = ? AND stock > 0");
$st->execute([$productId]);
$product = $st->fetch();

if (!$product) {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Product not available.']);
        exit;
    }
    setFlashMessage('Product not available.', 'error');
    header('Location: /e-commerce/pages/product/productList.php');
    exit;
}

// Check requested quantity vs stock
if ($quantity > $product['stock']) {
    $quantity = $product['stock'];
}

// Upsert into cart
$st = $db->prepare("
    INSERT INTO cart (user_id, product_id, quantity)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE quantity = LEAST(quantity + VALUES(quantity), ?)
");
$st->execute([$uid, $productId, $quantity, $product['stock']]);

$message = "\"{$product['name']}\" added to your cart!";

if ($isAjax) {
    // Get updated cart count
    $stCount = $db->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart WHERE user_id = ?");
    $stCount->execute([$uid]);
    $cartCount = (int)$stCount->fetchColumn();

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => $message, 'cartCount' => $cartCount]);
    exit;
}

setFlashMessage($message, 'success');

// Redirect
if ($redirect === 'cart') {
    header('Location: /e-commerce/pages/cart/viewCart.php');
} elseif ($redirect === 'product') {
    header("Location: /e-commerce/pages/product/productDetail.php?id=$productId");
} else {
    header('Location: /e-commerce/pages/product/productList.php');
}
exit;