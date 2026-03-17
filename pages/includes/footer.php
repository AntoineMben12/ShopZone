<?php
// includes/footer.php
$depth = $depth ?? '';
?>
<footer>
  <div class="container">
    <div class="row g-4">
      <div class="col-md-4">
        <div class="brand mb-2">Shop<span style="color:var(--text)">Zone</span></div>
        <p style="color:var(--text-muted);font-size:.88rem;max-width:260px">
          Premium products delivered fast. Quality you can trust, prices you'll love.
        </p>
      </div>
      <div class="col-md-2">
        <p style="font-weight:600;font-size:.85rem;letter-spacing:.06em;text-transform:uppercase;color:var(--text-muted);margin-bottom:.8rem">Shop</p>
        <div class="foot-links">
          <a href="/e-commerce/pages/product/productList.php">All Products</a>
          <a href="/e-commerce/pages/cart/viewCart.php">Cart</a>
          <a href="/e-commerce/pages/user/orders.php">My Orders</a>
        </div>
      </div>
      <div class="col-md-2">
        <p style="font-weight:600;font-size:.85rem;letter-spacing:.06em;text-transform:uppercase;color:var(--text-muted);margin-bottom:.8rem">Account</p>
        <div class="foot-links">
          <a href="/e-commerce/pages/auth/login.php">Login</a>
          <a href="/e-commerce/pages/auth/signup.php">Sign Up</a>
          <a href="/e-commerce/pages/user/profile.php">Profile</a>
        </div>
      </div>
      <div class="col-md-4">
        <p style="font-weight:600;font-size:.85rem;letter-spacing:.06em;text-transform:uppercase;color:var(--text-muted);margin-bottom:.8rem">Stay in the loop</p>
        <form class="d-flex gap-2" onsubmit="return false">
          <input type="email" class="form-control form-control-dark" placeholder="your@email.com" style="font-size:.85rem">
          <button class="btn btn-accent" style="white-space:nowrap;font-size:.85rem">Subscribe</button>
        </form>
      </div>
    </div>
    <p class="copy text-center">
      &copy; <?= date('Y') ?> ShopZone. Built with PHP &amp; <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16" style="vertical-align: text-bottom;"><path fill-rule="evenodd" d="M8 1.314C12.438-3.248 23.534 4.735 8 15-7.534 4.736 3.562-3.248 8 1.314z"/></svg>
      &nbsp;|&nbsp;
      <a href="/e-commerce/pages/auth/login.php" style="font-size:.8rem">Admin Login</a>
    </p>
  </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="/e-commerce/Assets/js/Main.js"></script>
</body>

</html>