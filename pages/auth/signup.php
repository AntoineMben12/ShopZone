<?php
// auth/signup.php
require_once '../../database/database.php';
require_once '../includes/auth.php';

if (isLoggedIn()) {
    header('Location: /e-commerce/index.php');
    exit;
}

$errors = [];
$data   = ['name'=>'','email'=>'','role'=>'user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['name']  = trim($_POST['name']  ?? '');
    $data['email'] = trim($_POST['email'] ?? '');
    $data['role']  = in_array($_POST['role'] ?? '', ['user','student']) ? $_POST['role'] : 'user';
    $password      = $_POST['password']  ?? '';
    $confirm       = $_POST['confirm']   ?? '';

    if (!$data['name'])                                   $errors[] = 'Name is required.';
    if (strlen($data['name']) < 2)                        $errors[] = 'Name must be at least 2 characters.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (strlen($password) < 6)                            $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm)                           $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $db = getDB();
        // Check duplicate email
        $st = $db->prepare("SELECT id FROM users WHERE email = ?");
        $st->execute([$data['email']]);
        if ($st->fetch()) {
            $errors[] = 'Email already registered. <a href="login.php">Sign in?</a>';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $db->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)")
               ->execute([$data['name'], $data['email'], $hash, $data['role']]);

            setFlashMessage('success', 'Account created! Please sign in.');
            header('Location: login.php');
            exit;
        }
    }
}

$pageTitle = 'Sign Up';
$depth = '../';
require_once '../includes/header.php';
require_once '../includes/navBar.php';
?>

<div class="auth-wrap">
  <div class="auth-card fade-up" style="max-width:480px">
    <div class="text-center mb-4">
      <a href="/e-commerce/index.php" style="font-family:var(--font-head);font-size:1.8rem;color:var(--accent);text-decoration:none">
        Shop<span style="color:var(--text)">Zone</span>
      </a>
    </div>

    <h2>Create an account</h2>
    <p class="subtitle">Join thousands of happy shoppers</p>

    <?php foreach ($errors as $e): ?>
      <div class="alert alert-danger"><?= $e ?></div>
    <?php endforeach; ?>

    <form method="POST" novalidate>
      <div class="mb-3">
        <label class="form-label-dark">Full Name</label>
        <input type="text" name="name" class="form-control form-control-dark"
               placeholder="Jane Doe" value="<?= htmlspecialchars($data['name']) ?>" required>
      </div>

      <div class="mb-3">
        <label class="form-label-dark">Email Address</label>
        <input type="email" name="email" class="form-control form-control-dark"
               placeholder="you@example.com" value="<?= htmlspecialchars($data['email']) ?>" required>
      </div>

      <div class="mb-3">
        <label class="form-label-dark">Account Type</label>
        <select name="role" class="form-control form-control-dark">
          <option value="user"    <?= $data['role']==='user'    ? 'selected' : '' ?>>Regular User</option>
          <option value="student" <?= $data['role']==='student' ? 'selected' : '' ?>>Student (get discounts)</option>
        </select>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-6">
          <label class="form-label-dark">Password</label>
          <input type="password" name="password" class="form-control form-control-dark"
                 placeholder="Min 6 chars" required>
        </div>
        <div class="col-6">
          <label class="form-label-dark">Confirm</label>
          <input type="password" name="confirm" class="form-control form-control-dark"
                 placeholder="Repeat" required>
        </div>
      </div>

      <button type="submit" class="btn btn-accent w-100 py-2">
        <i class="bi bi-person-plus me-2"></i>Create Account
      </button>
    </form>

    <p class="text-center mt-3" style="color:var(--text-muted);font-size:.88rem">
      Already have an account? <a href="login.php">Sign in</a>
    </p>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>