<?php
/**
 * admin/resources.php — manage resources (add / edit / delete).
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin-functions.php';

require_role(['admin'], 1);
$user = current_user();
$active = 'admin_resources';

$error = '';
$success = '';

// ---- Handle form submissions -------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = 'Your session expired. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'delete') {
            delete_resource((int) $_POST['resource_id']);
            $success = 'Resource deleted.';
        } else {
            $name        = trim($_POST['name'] ?? '');
            $category    = $_POST['category'] ?? 'lab';
            $location    = trim($_POST['location'] ?? '');
            $capacity    = $_POST['capacity'] !== '' ? (int) $_POST['capacity'] : null;
            $description = trim($_POST['description'] ?? '');
            $status      = $_POST['status'] ?? 'available';

            if ($name === '') {
                $error = 'Please give the resource a name.';
            } elseif (!in_array($category, ['lab', 'room', 'multimedia', 'device'], true)) {
                $error = 'Please choose a valid category.';
            } elseif (!in_array($status, ['available', 'maintenance', 'retired'], true)) {
                $error = 'Please choose a valid status.';
            } else {
                if ($action === 'update') {
                    update_resource((int) $_POST['resource_id'], $name, $category, $location ?: null, $capacity, $description ?: null, $status);
                    $success = 'Resource updated.';
                } else {
                    create_resource($name, $category, $location ?: null, $capacity, $description ?: null, $status);
                    $success = 'Resource added.';
                }
            }
        }
    }
}

$editId = (int) ($_GET['edit'] ?? 0);
$editResource = $editId ? get_resource($editId) : null;

$resources = fetch_resources();
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Resources — SURAS Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/../includes/ops-navbar.php'; ?>

<main class="app-main">
  <div class="container">

    <div class="page-head">
      <div>
        <h1>Resources</h1>
        <p>Add new resources, update details, or retire what's no longer in service.</p>
      </div>
    </div>

    <?php if ($success): ?><div class="banner banner-success"><span>✓</span><span><?php echo e($success); ?></span></div><?php endif; ?>
    <?php if ($error): ?><div class="banner banner-error"><span>⚠️</span><span><?php echo e($error); ?></span></div><?php endif; ?>

    <div class="booking-layout">

      <div>
        <div class="panel">
          <div class="panel-head"><h2>All resources</h2></div>
          <table class="data-table">
            <thead>
              <tr><th>Name</th><th>Category</th><th>Location</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
              <?php foreach ($resources as $r): ?>
                <tr>
                  <td>
                    <div class="resource-name"><?php echo e($r['name']); ?></div>
                    <?php if ($r['capacity']): ?><div class="resource-sub">Capacity <?php echo (int) $r['capacity']; ?></div><?php endif; ?>
                  </td>
                  <td><?php echo e(category_label($r['category'])); ?></td>
                  <td><?php echo e($r['location'] ?? '—'); ?></td>
                  <td>
                    <span class="badge <?php echo $r['status'] === 'available' ? 'is-approved' : ($r['status'] === 'maintenance' ? 'is-pending' : 'is-rejected'); ?>">
                      <?php echo e($r['status']); ?>
                    </span>
                  </td>
                  <td style="white-space:nowrap;">
                    <a href="resources.php?edit=<?php echo (int) $r['id']; ?>" class="icon-btn" style="color:var(--ink); border-color:var(--line);">Edit</a>
                    <form method="POST" action="resources.php" style="display:inline;" onsubmit="return confirm('Delete this resource? This cannot be undone.');">
                      <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="resource_id" value="<?php echo (int) $r['id']; ?>">
                      <button type="submit" class="icon-btn">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div>
        <div class="panel summary-card">
          <div class="panel-head">
            <h2><?php echo $editResource ? 'Edit resource' : 'Add a resource'; ?></h2>
            <?php if ($editResource): ?><a href="resources.php">Cancel</a><?php endif; ?>
          </div>

          <form method="POST" action="resources.php">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">
            <input type="hidden" name="action" value="<?php echo $editResource ? 'update' : 'create'; ?>">
            <?php if ($editResource): ?>
              <input type="hidden" name="resource_id" value="<?php echo (int) $editResource['id']; ?>">
            <?php endif; ?>

            <div class="field">
              <label for="name">Name</label>
              <div class="field-control">
                <input type="text" id="name" name="name" required
                       value="<?php echo e($editResource['name'] ?? ''); ?>" placeholder="Computer Lab 305">
              </div>
            </div>

            <div class="field">
              <label for="category">Category</label>
              <select id="category" name="category">
                <?php foreach (['lab' => 'Computer Lab', 'room' => 'Meeting Room', 'multimedia' => 'Multimedia Equipment', 'device' => 'Testing Device'] as $val => $label): ?>
                  <option value="<?php echo $val; ?>" <?php echo ($editResource['category'] ?? '') === $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="field">
              <label for="location">Location</label>
              <div class="field-control">
                <input type="text" id="location" name="location" value="<?php echo e($editResource['location'] ?? ''); ?>" placeholder="Tech Building, Floor 3">
              </div>
            </div>

            <div class="field">
              <label for="capacity">Capacity <span style="color:var(--ink-soft); font-weight:400;">(optional)</span></label>
              <div class="field-control">
                <input type="number" id="capacity" name="capacity" min="0" value="<?php echo e((string) ($editResource['capacity'] ?? '')); ?>">
              </div>
            </div>

            <div class="field">
              <label for="description">Description</label>
              <textarea id="description" name="description" placeholder="Short description shown to users"><?php echo e($editResource['description'] ?? ''); ?></textarea>
            </div>

            <div class="field">
              <label for="status">Status</label>
              <select id="status" name="status">
                <?php foreach (['available' => 'Available', 'maintenance' => 'Maintenance', 'retired' => 'Retired'] as $val => $label): ?>
                  <option value="<?php echo $val; ?>" <?php echo ($editResource['status'] ?? 'available') === $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <button type="submit" class="btn btn-amber btn-block"><?php echo $editResource ? 'Save changes' : 'Add resource'; ?></button>
          </form>
        </div>
      </div>

    </div>

  </div>
</main>

<script src="../assets/js/main.js"></script>
</body>
</html>
