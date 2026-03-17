<?php

require_once '../../database/database.php';
require_once '../includes/auth.php';
if (isLoggedIn()) {
    header('Location: /e-commerce/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        $db = getDB();
        $st = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $st->execute([$email]);
        $user = $st->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);

            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role']      = $user['role'];

            // Role-based redirect
            $redirect = match($user['role']) {
                'admin'   => '../admin/dashboard.php',
                'student' => '../student/dashboard.php',
                default   => '../user/dashboard.php',
            };
            setFlashMessage('success', 'Welcome back, ' . $user['name'] . '!');
            header("Location: $redirect");
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

$pageTitle = 'Login';
$depth = '/pages/';
require_once '../includes/header.php';
require_once '../includes/navBar.php';
?>

<div class="auth-wrap">
  <div class="auth-card fade-up">
    <!-- Logo -->
    <div class="text-center mb-4">
      <a href="/e-commerce/index.php" style="font-family:var(--font-head);font-size:1.8rem;color:var(--accent);text-decoration:none">
        Shop<span style="color:var(--text)">Zone</span>
      </a>
    </div>

    <h2>Welcome back</h2>
    <p class="subtitle">Sign in to your account to continue</p>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <div class="mb-3">
        <label class="form-label-dark">Email Address</label>
        <input type="email" name="email" class="form-control form-control-dark"
               placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               required autocomplete="email">
      </div>

      <div class="mb-4">
        <div class="d-flex justify-content-between">
          <label class="form-label-dark">Password</label>
        </div>
        <input type="password" name="password" class="form-control form-control-dark"
               placeholder="••••••••" required autocomplete="current-password">
      </div>

      <button type="submit" class="btn btn-accent w-100 py-2">
        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
      </button>
    </form>

    <!-- Demo credentials -->
    <div class="mt-4 p-3 rounded" style="background:var(--bg3);border:1px solid var(--border)">
      <p style="font-size:.78rem;color:var(--text-muted);margin-bottom:.5rem;letter-spacing:.06em;text-transform:uppercase;font-weight:600">
        Demo Credentials
      </p>
      <div style="font-size:.82rem;color:var(--text-muted);line-height:2">
        <div>Admin &nbsp;&nbsp;&nbsp;<code style="color:var(--accent)">admin@shop.com</code></div>
        <div>Student <code style="color:var(--accent)">student@shop.com</code></div>
        <div>User &nbsp;&nbsp;&nbsp;&nbsp;<code style="color:var(--accent)">user@shop.com</code></div>
        <div class="mt-1">Password: <code style="color:var(--accent)">password</code></div>
      </div>
    </div>

    <p class="text-center mt-3" style="color:var(--text-muted);font-size:.88rem">
      Don't have an account?
      <a href="/e-commerce/pages/auth/signup.php">Sign up free</a>
    </p>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>