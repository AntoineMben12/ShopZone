<?php
// user/profile.php
require_once '../../database/database.php';
require_once '../includes/auth.php';
requireLogin('../auth/login.php');

$db  = getDB();
$uid = currentUserId();

$user = $db->prepare("SELECT * FROM users WHERE id = ?");
$user->execute([$uid]);
$user = $user->fetch();

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $phone   = trim($_POST['phone']   ?? '');
    $address = trim($_POST['address'] ?? '');
    $newPass = $_POST['new_password'] ?? '';
    $curPass = $_POST['current_password'] ?? '';

    if (!$name) {
        $error = 'Name is required.';
    } else {
        // Update basic info
        $db->prepare("UPDATE users SET name=?, phone=?, address=? WHERE id=?")
           ->execute([$name, $phone, $address, $uid]);

        // Update session name
        $_SESSION['user_name'] = $name;

        // Change password if requested
        if ($newPass) {
            if (!password_verify($curPass, $user['password'])) {
                $error = 'Current password is incorrect.';
            } elseif (strlen($newPass) < 6) {
                $error = 'New password must be at least 6 characters.';
            } else {
                $hash = password_hash($newPass, PASSWORD_DEFAULT);
                $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hash, $uid]);
            }
        }

        if (!$error) {
            $success = 'Profile updated successfully!';
            // Refresh user data
            $st = $db->prepare("SELECT * FROM users WHERE id = ?");
            $st->execute([$uid]);
            $user = $st->fetch();
        }
    }
}

$pageTitle = 'My Profile';
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
          <a class="nav-link" href="orders.php"><i class="bi bi-bag"></i> My Orders</a>
          <a class="nav-link" href="../cart/viewCart.php"><i class="bi bi-cart3"></i> Cart</a>
          <a class="nav-link active" href="profile.php"><i class="bi bi-person"></i> Profile</a>
          <a class="nav-link" href="../product/productList.php"><i class="bi bi-grid"></i> Browse</a>
          <hr style="border-color:var(--border);margin:.5rem 0">
          <a class="nav-link" href="../auth/logout.php" style="color:var(--danger)!important">
            <i class="bi bi-box-arrow-right"></i> Logout
          </a>
        </nav>
      </div>
    </div>

    <div class="col-lg-9">
      <h1 class="section-title">My Profile</h1>
      <p class="section-sub">Manage your personal information</p>

      <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
      <?php if ($error):   ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

      <form method="POST">
        <div class="card-dark p-4 mb-4">
          <h5 class="mb-3">Personal Information</h5>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label-dark">Full Name</label>
              <input type="text" name="name" class="form-control form-control-dark"
                     value="<?= htmlspecialchars($user['name']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label-dark">Email (read-only)</label>
              <input type="email" class="form-control form-control-dark"
                     value="<?= htmlspecialchars($user['email']) ?>" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label-dark">Phone</label>
              <input type="text" name="phone" class="form-control form-control-dark"
                     value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+1 555 000 0000">
            </div>
            <div class="col-md-6">
              <label class="form-label-dark">Account Role</label>
              <input type="text" class="form-control form-control-dark"
                     value="<?= ucfirst($user['role']) ?>" readonly>
            </div>
            <div class="col-12">
              <label class="form-label-dark">Shipping Address</label>
              <textarea name="address" class="form-control form-control-dark" rows="2"
                        placeholder="123 Main St, City, Country"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
            </div>
          </div>
        </div>

        <div class="card-dark p-4 mb-4">
          <h5 class="mb-3">Change Password <small style="color:var(--text-muted);font-size:.8rem">(leave blank to keep current)</small></h5>
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label-dark">Current Password</label>
              <input type="password" name="current_password" class="form-control form-control-dark" placeholder="••••••••">
            </div>
            <div class="col-md-4">
              <label class="form-label-dark">New Password</label>
              <input type="password" name="new_password" class="form-control form-control-dark" placeholder="Min 6 chars">
            </div>
          </div>
        </div>

        <button type="submit" class="btn btn-accent px-4 py-2">
          <i class="bi bi-check2 me-2"></i>Save Changes
        </button>
      </form>
    </div>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>