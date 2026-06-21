<?php
/**
 * admin/users.php — manage user accounts.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin-functions.php';

require_role(['admin'], 1);
$user = current_user();
$active = 'admin_users';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = 'Your session expired. Please try again.';
    } else {
        $targetId = (int) ($_POST['user_id'] ?? 0);
        $actionType = $_POST['action'] ?? '';

        if ($targetId === (int) $user['id'] && $actionType !== 'noop') {
            $error = "You can't modify your own account from here.";
        } elseif ($actionType === 'delete') {
            delete_user($targetId);
            $success = 'User removed.';
        } elseif ($actionType === 'update') {
            $role = $_POST['role'] ?? 'student';
            $status = $_POST['status'] ?? 'active';
            if (in_array($role, ['student', 'faculty', 'project_lead', 'admin'], true)
                && in_array($status, ['active', 'suspended'], true)) {
                update_user($targetId, $role, $status);
                $success = 'User updated.';
            } else {
                $error = 'Invalid role or status.';
            }
        }
    }
}

$search = trim($_GET['q'] ?? '');
$roleFilter = $_GET['role'] ?? 'all';
$users = get_all_users($search, $roleFilter);

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf_token = $_SESSION['csrf_token'];

$roles = ['all' => 'All roles', 'student' => 'Students', 'project_lead' => 'Project Leads', 'faculty' => 'Faculty', 'admin' => 'Admins'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Users — SURAS Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/../includes/ops-navbar.php'; ?>

<main class="app-main">
  <div class="container">

    <div class="page-head">
      <div>
        <h1>Users</h1>
        <p>Adjust roles, suspend accounts, or remove users entirely.</p>
      </div>
    </div>

    <?php if ($success): ?><div class="banner banner-success"><span>✓</span><span><?php echo e($success); ?></span></div><?php endif; ?>
    <?php if ($error): ?><div class="banner banner-error"><span>⚠️</span><span><?php echo e($error); ?></span></div><?php endif; ?>

    <form class="filter-bar" method="GET" action="users.php">
      <input type="search" name="q" placeholder="Search by name or email…" value="<?php echo e($search); ?>">
      <?php if ($roleFilter !== 'all'): ?><input type="hidden" name="role" value="<?php echo e($roleFilter); ?>"><?php endif; ?>
      <button type="submit" class="btn btn-ghost">Search</button>
    </form>

    <div class="chip-group" style="margin-bottom: 24px;">
      <?php foreach ($roles as $key => $label): ?>
        <a class="chip <?php echo $roleFilter === $key ? 'is-active' : ''; ?>"
           href="users.php?role=<?php echo e($key); ?><?php echo $search !== '' ? '&q=' . urlencode($search) : ''; ?>">
          <?php echo e($label); ?>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if (empty($users)): ?>
      <div class="empty-state">
        <span class="empty-icon">🔍</span>
        <p>No users match that search.</p>
      </div>
    <?php else: ?>
      <table class="data-table">
        <thead>
          <tr><th>Name</th><th>Email</th><th>Department</th><th>Role</th><th>Status</th><th>Joined</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td class="resource-name"><?php echo e($u['full_name']); ?></td>
              <td><?php echo e($u['email']); ?></td>
              <td><?php echo e($u['department'] ?? '—'); ?></td>
              <td>
                <?php if ((int) $u['id'] === (int) $user['id']): ?>
                  <span class="badge is-approved"><?php echo e(role_label($u['role'])); ?> (you)</span>
                <?php else: ?>
                  <form method="POST" action="users.php" style="display:flex; gap:6px; align-items:center;">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="user_id" value="<?php echo (int) $u['id']; ?>">
                    <select name="role" style="font-size:12.5px; padding:6px 8px; border:1px solid var(--line); border-radius:4px;" onchange="this.form.requestSubmit();">
                      <?php foreach (['student','faculty','project_lead','admin'] as $r): ?>
                        <option value="<?php echo $r; ?>" <?php echo $u['role'] === $r ? 'selected' : ''; ?>><?php echo e(role_label($r)); ?></option>
                      <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="status" value="<?php echo e($u['status']); ?>">
                  </form>
                <?php endif; ?>
              </td>
              <td>
                <?php if ((int) $u['id'] !== (int) $user['id']): ?>
                  <form method="POST" action="users.php">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="user_id" value="<?php echo (int) $u['id']; ?>">
                    <input type="hidden" name="role" value="<?php echo e($u['role']); ?>">
                    <select name="status" style="font-size:12.5px; padding:6px 8px; border:1px solid var(--line); border-radius:4px;" onchange="this.form.requestSubmit();">
                      <option value="active" <?php echo $u['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                      <option value="suspended" <?php echo $u['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                  </form>
                <?php else: ?>
                  <span class="badge is-approved">active</span>
                <?php endif; ?>
              </td>
              <td class="resource-sub"><?php echo date('M j, Y', strtotime($u['created_at'])); ?></td>
              <td>
                <?php if ((int) $u['id'] !== (int) $user['id']): ?>
                  <form method="POST" action="users.php" onsubmit="return confirm('Remove this user permanently?');">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" value="<?php echo (int) $u['id']; ?>">
                    <button type="submit" class="icon-btn">Remove</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

  </div>
</main>

<script src="../assets/js/main.js"></script>
</body>
</html>
