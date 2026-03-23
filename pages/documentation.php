<?php
// pages/documentation.php
// Public-facing documentation for ShopZone users.

if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../database/database.php';
require_once 'includes/auth.php';

$pageTitle = 'Documentation | ShopZone';
$depth = '';
require_once 'includes/header.php';
require_once 'includes/navBar.php';
?>

<style>
.docs-hero {
    background: linear-gradient(135deg, var(--bg2) 0%, var(--bg) 100%);
    border-bottom: 1px solid var(--border);
    padding: 3rem 0;
    text-align: center;
}
.docs-hero h1 {
    font-size: clamp(2rem, 5vw, 3rem);
    font-weight: 800;
    background: linear-gradient(135deg, var(--text) 0%, var(--accent) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: .5rem;
}
.docs-hero p { color:var(--text-muted); font-size:1.05rem; max-width:600px; margin:0 auto; }

.docs-content {
    max-width: 800px;
    margin: 0 auto;
    font-size: 1rem;
    line-height: 1.8;
}
.docs-section {
    background: var(--bg2);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 2rem;
    margin-bottom: 2rem;
}
.docs-section h2 {
    font-size: 1.5rem;
    margin-top: 0;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--border);
    color: var(--text);
}
.docs-section h3 {
    font-size: 1.2rem;
    margin-top: 1.5rem;
    margin-bottom: 0.5rem;
    color: var(--accent);
}
.docs-section p { color: var(--text-muted); margin-bottom: 1rem; }
.docs-section ul {
    color: var(--text-muted);
    padding-left: 1.5rem;
    margin-bottom: 1rem;
}
.docs-section li { margin-bottom: 0.4rem; }
.docs-kbd {
    background: var(--border);
    padding: 0.15rem 0.4rem;
    border-radius: 4px;
    font-family: monospace;
    font-size: 0.85rem;
    color: var(--text);
}
.badge-doc {
    background: rgba(var(--accent-rgb), .1);
    color: var(--accent);
    padding: 0.2rem 0.6rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 700;
    margin-right: 0.5rem;
}
</style>

<!-- Hero -->
<div class="docs-hero">
  <div class="container">
    <h1>📘 ShopZone Documentation</h1>
    <p>Your complete guide to navigating and using the ShopZone e-commerce and blogging platform.</p>
  </div>
</div>

<div class="container py-5">
  <div class="docs-content">

    <!-- Section 1: Intro -->
    <div class="docs-section">
      <h2>1. Welcome to ShopZone</h2>
      <p>
        ShopZone is a custom-built, highly optimized e-commerce and community blogging platform. 
        Designed for speed, security, and a premium user experience, it acts as a central hub 
        for both shopping and connecting with the community.
      </p>
    </div>

    <!-- Section 2: Using the App (Roles & Auth) -->
    <div class="docs-section">
      <h2>2. Roles & Authentication</h2>
      <p>Depending on your account type, you have different features available to you within the platform:</p>
      <ul>
        <li><span class="badge-role-admin">Admin</span> <strong>Administrators</strong> have full control over the store. They can add or remove products, manage user orders, and moderate community blog posts. Admins can also embed specific "Featured Products" directly into articles.</li>
        <li><span class="badge-role-user">User</span> <strong>Standard Users</strong> can browse products, add items to their cart, securely check out, track order history from their dashboard, and write their own rich-text blog posts.</li>
        <li><span class="badge-role-student">Student</span> <strong>Students</strong> have the exact same capabilities as Standard Users, but are automatically entitled to exclusive educational discounts on qualifying products store-wide!</li>
      </ul>
      <p>To access your specific features, simply use the <a href="auth/login.php">Login</a> page. If you are new, <a href="auth/signup.php">Create an Account</a>.</p>
    </div>

    <!-- Section 3: E-Commerce -->
    <div class="docs-section">
      <h2>3. Shopping & Orders</h2>
      <h3>Browsing & Cart</h3>
      <p>Navigate to the <strong>Products</strong> page to view our catalog. Products feature live pricing and dynamic discount badges. When you find what you like, add it to your <strong>Cart</strong>. The cart securely stores your session data and allows for instant quantity adjustments.</p>
      
      <h3>Checkout & Tracking</h3>
      <p>The checkout process is streamlined for speed. Once an order is placed, you are issued a unique tracking ID. You can monitor the live status of your shipment (e.g., Pending, Shipped, Delivered) directly from the <strong>My Orders</strong> section of your Dashboard.</p>
    </div>

    <!-- Section 4: Blog -->
    <div class="docs-section">
      <h2>4. The Community Blog</h2>
      <p>ShopZone integrates a powerful community blog where users share stories, and admins highlight products.</p>
      <ul>
        <li><strong>Rich-Text Editor:</strong> Simply click "Write a Post" from your dashboard or the blog feed to open the Quill.js editor. You can format text, create lists, and upload featured images seamlessly.</li>
        <li><strong>Interaction:</strong> Found a post you love? Hit the ❤️ button to 'Like' it, or jump into the conversation by leaving a 💬 Comment.</li>
        <li><strong>Product Embeds:</strong> Selected posts will contain embedded "Featured Products" recommended directly by our staff.</li>
      </ul>
    </div>

    <!-- Section 5: Command Palette -->
    <div class="docs-section">
      <h2>5. The Global Command Palette</h2>
      <p>We've implemented a developer-friendly navigation tool for power users.</p>
      <p>
        Press <span class="docs-kbd">Ctrl</span> + <span class="docs-kbd">K</span> (or <span class="docs-kbd">Cmd</span> + <span class="docs-kbd">K</span> on Mac) 
        <strong>anywhere on the site</strong> to open the Command Palette.
      </p>
      <ul>
        <li><strong>Fuzzy Search:</strong> Instantly type to filter commands (e.g., type "Cart" to find your cart).</li>
        <li><strong>Keyboard Navigation:</strong> Use your Arrow keys to select an option and press Enter to navigate.</li>
        <li><strong>Dark Mode Toggle:</strong> Search for "Theme" or "Dark" in the palette to toggle the entire site's CSS-variable-powered Dark Mode on or off!</li>
      </ul>
    </div>

  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
