<?php
// admin/users.php
require_once '../../database/database.php';
require_once '../includes/auth.php';
requireAdmin('../index.php');

$db = getDB();

// Change role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $userId  = (int)$_POST['user_id'];
    $newRole = in_array($_POST['role'], ['admin','user','student']) ? $_POST['role'] : 'user';
    if ($userId !== currentUserId()) {   // prevent self-role change
        $db->prepare("UPDATE users SET role=? WHERE id=?")->execute([$newRole, $userId]);
        setFlashMessage('User role updated.', 'success');
    } else {
        setFlashMessage('You cannot change your own role.', 'error');
    }
    header('Location: users.php'); exit;
}

// Delete user
if (isset($_GET['delete'])) {
    $userId = (int)$_GET['delete'];
    if ($userId !== currentUserId()) {
        $db->prepare("DELETE FROM users WHERE id=?")->execute([$userId]);
        setFlashMessage('User deleted.', 'success');
    } else {
        setFlashMessage('You cannot delete your own account.', 'error');
    }
    header('Location: users.php'); exit;
}

$search = trim($_GET['search'] ?? '');
$role   = $_GET['role'] ?? '';

$where  = ['1=1'];
$params = [];
if ($search) { $where[] = '(name LIKE ? OR email LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($role)   { $where[] = 'role = ?'; $params[] = $role; }

$users = $db->prepare("SELECT u.*, (SELECT COUNT(*) FROM orders WHERE user_id=u.id) AS order_count FROM users u WHERE " . implode(' AND ', $where) . " ORDER BY u.created_at DESC");
$users->execute($params);
$users = $users->fetchAll();

$pageTitle = 'Manage Users';
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
          <a class="nav-link" href="addProduct.php"><i class="bi bi-plus-circle"></i> Add Product</a>
          <a class="nav-link active" href="users.php"><i class="bi bi-people"></i> Users</a>
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
          <h1 class="section-title mb-0">Users</h1>
          <p style="color:var(--text-muted);font-size:.85rem"><?= count($users) ?> users found</p>
        </div>
      </div>

      <!-- Filters -->
      <form method="GET" class="d-flex flex-wrap gap-2 mb-4">
        <input type="text" name="search" class="form-control form-control-dark" style="max-width:260px"
               placeholder="Name or email…" value="<?= htmlspecialchars($search) ?>">
        <select name="role" class="form-control form-control-dark" style="max-width:160px">
          <option value="">All Roles</option>
          <option value="admin"   <?= $role==='admin'   ? 'selected':'' ?>>Admin</option>
          <option value="user"    <?= $role==='user'    ? 'selected':'' ?>>User</option>
          <option value="student" <?= $role==='student' ? 'selected':'' ?>>Student</option>
        </select>
        <button class="btn btn-ghost">Filter</button>
        <a href="users.php" class="btn btn-ghost">Reset</a>
      </form>

      <div class="card-dark">
        <div class="table-responsive">
          <table class="table table-dark-custom mb-0">
            <thead>
              <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Orders</th><th>Joined</th><th>Change Role</th><th></th></tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u): ?>
                <tr>
                  <td style="color:var(--text-muted)">#<?= $u['id'] ?></td>
                  <td>
                    <div style="font-weight:500"><?= htmlspecialchars($u['name']) ?></div>
                    <?php if ($u['id'] === currentUserId()): ?>
                      <span style="font-size:.7rem;color:var(--accent)">(you)</span>
                    <?php endif; ?>
                  </td>
                  <td style="font-size:.85rem;color:var(--text-muted)"><?= htmlspecialchars($u['email']) ?></td>
                  <td><span class="badge-role-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                  <td><?= $u['order_count'] ?></td>
                  <td style="color:var(--text-muted);font-size:.82rem"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                  <td>
                    <?php if ($u['id'] !== currentUserId()): ?>
                      <form method="POST" class="d-flex gap-1 align-items-center">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <select name="role" class="form-control form-control-dark" style="padding:.3rem .6rem;font-size:.8rem;max-width:110px">
                          <option value="user"    <?= $u['role']==='user'    ? 'selected':'' ?>>User</option>
                          <option value="student" <?= $u['role']==='student' ? 'selected':'' ?>>Student</option>
                          <option value="admin"   <?= $u['role']==='admin'   ? 'selected':'' ?>>Admin</option>
                        </select>
                        <button name="change_role" class="btn btn-ghost btn-sm" title="Apply">
                          <i class="bi bi-check2"></i>
                        </button>
                      </form>
                    <?php else: ?>
                      <span style="color:var(--text-muted);font-size:.8rem">N/A</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($u['id'] !== currentUserId()): ?>
                      <a href="?delete=<?= $u['id'] ?>" class="btn btn-ghost btn-sm" style="color:var(--danger)"
                         data-confirm="Delete user '<?= htmlspecialchars($u['name']) ?>'? This also removes their orders.">
                        <i class="bi bi-trash"></i>
                      </a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>