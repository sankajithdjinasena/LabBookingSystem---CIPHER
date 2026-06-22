<?php
/**
 * register.php — SURAS account creation
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header('Location: ' . dashboard_for_role(current_user()['role']));
    exit;
}

$pdo = get_db_connection();
$departments = $pdo->query('SELECT name FROM departments ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);

$error = '';
$old = ['full_name' => '', 'email' => '', 'role' => 'student', 'department' => '', 'university_id' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['full_name']     = trim($_POST['full_name'] ?? '');
    $old['email']         = trim($_POST['email'] ?? '');
    $old['role']          = $_POST['role'] ?? 'student';
    $old['department']    = trim($_POST['department'] ?? '');
    $old['university_id'] = trim($_POST['university_id'] ?? '');
    $password             = (string) ($_POST['password'] ?? '');
    $confirm              = (string) ($_POST['confirm_password'] ?? '');

    $allowedRoles = ['student', 'project_lead', 'faculty']; // admin accounts are provisioned separately

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = 'Your session expired. Please try again.';
    } elseif ($old['full_name'] === '' || $old['email'] === '' || $password === '') {
        $error = 'Please fill in your name, email and password.';
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $error = "That email address doesn't look quite right.";
    } elseif (strlen($password) < 8) {
        $error = 'Your password needs to be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = "Those passwords don't match.";
    } elseif (!in_array($old['role'], $allowedRoles, true)) {
        $error = 'Please choose a valid account type.';
    } elseif ($old['role'] === 'student' && $old['university_id'] === '') {
        $error = 'Students must provide their University Registration ID.';
    } elseif ($old['department'] === '') {
        $error = 'Please select a department / faculty.';
    } elseif (!isset($_POST['agree_terms'])) {
        $error = 'Please agree to the Terms of Service and Privacy Policy to continue.';
    } else {
        $pdo = get_db_connection();
        $check = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $check->execute(['email' => $old['email']]);

        if ($check->fetch()) {
            $error = 'An account with that email already exists. Try signing in instead.';
        } else {
            $insert = $pdo->prepare(
                'INSERT INTO users (full_name, email, password_hash, role, university_id, department, status)
                 VALUES (:full_name, :email, :password_hash, :role, :university_id, :department, :status)'
            );
            $insert->execute([
                'full_name'     => $old['full_name'],
                'email'         => $old['email'],
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role'          => $old['role'],
                'university_id' => $old['university_id'] !== '' ? $old['university_id'] : null,
                'department'    => $old['department'] !== '' ? $old['department'] : null,
                // Faculty accounts are flagged for admin review before they gain reviewer access.
                'status'        => 'active',
            ]);

            $userId = (int) $pdo->lastInsertId();
            login_user([
                'id'            => $userId,
                'full_name'     => $old['full_name'],
                'email'         => $old['email'],
                'role'          => $old['role'],
                'university_id' => $old['university_id'],
                'department'    => $old['department'],
                'status'        => 'active',
            ]);

            header('Location: ' . dashboard_for_role($old['role']) . '?welcome=1');
            exit;
        }
    }
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create an account — SURAS</title>
<meta name="description" content="Create a SURAS account to start booking university labs, rooms and equipment.">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="auth-shell">

  <aside class="auth-aside">
    <div class="auth-aside-top">
      <a href="index.php" class="brand">
        <span class="brand-mark">S</span>
        <span>
          <span class="brand-name">SURAS</span>
          <span class="brand-sub">RESOURCE LEDGER</span>
        </span>
      </a>

      <p class="auth-quote">
        "One account, every lab, room and device on campus —
        no more chasing a sign-up sheet."
      </p>
      <span class="auth-quote-by">— what your first booking feels like</span>
    </div>

    <div class="auth-aside-bottom">
      <div class="auth-ledger">
        <div class="row"><span>Setup time</span><span>&lt; 1 minute</span></div>
        <div class="row"><span>Resource categories</span><span>4</span></div>
        <div class="row"><span>Approval</span><span>Automatic*</span></div>
      </div>
    </div>
  </aside>

  <main class="auth-main">
    <div class="auth-card is-wide">
      <span class="eyebrow">New here</span>
      <h1>Create your account</h1>
      <p class="auth-sub">Use your university email — your dashboard is scoped to your role automatically.</p>

      <?php if ($error): ?>
        <div class="alert alert-error" role="alert">
          <span>⚠️</span>
          <span><?php echo e($error); ?></span>
        </div>
      <?php endif; ?>

      <form id="register-form" action="register.php" method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo e($csrf_token); ?>">

        <div class="form-grid">
          <div class="field field-full">
            <label for="full_name">Full name</label>
            <div class="field-control">
              <input type="text" id="full_name" name="full_name" placeholder="Jane Doe"
                     value="<?php echo e($old['full_name']); ?>" autocomplete="name" required>
            </div>
          </div>

          <div class="field field-full">
            <label for="email">University email</label>
            <div class="field-control">
              <input type="email" id="email" name="email" placeholder="you@university.edu"
                     value="<?php echo e($old['email']); ?>" autocomplete="username" required>
            </div>
          </div>

          <div class="field">
            <label for="role">I am a</label>
            <select id="role" name="role">
              <option value="student" <?php echo $old['role'] === 'student' ? 'selected' : ''; ?>>Student</option>
              <option value="project_lead" <?php echo $old['role'] === 'project_lead' ? 'selected' : ''; ?>>Project Team Leader</option>
              <option value="faculty" <?php echo $old['role'] === 'faculty' ? 'selected' : ''; ?>>Faculty Member</option>
            </select>
          </div>

          <div class="field field-full">
            <label for="university_id">University ID / Registration No. <span style="color:var(--ink-soft); font-weight:400;">(Required for students)</span></label>
            <div class="field-control">
              <input type="text" id="university_id" name="university_id" placeholder="e.g., 22CDS0439"
                     value="<?php echo e($old['university_id']); ?>">
            </div>
          </div>

          <div class="field">
            <label for="department">Department / Faculty</label>
            <select id="department" name="department" required>
              <option value="">-- Select Department --</option>
              <?php foreach ($departments as $dept): ?>
                <option value="<?php echo e($dept); ?>" <?php echo $old['department'] === $dept ? 'selected' : ''; ?>>
                  <?php echo e($dept); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="field">
            <label for="password">Password</label>
            <div class="field-control">
              <input type="password" id="password" name="password" placeholder="At least 8 characters"
                     autocomplete="new-password" required>
              <button type="button" class="field-toggle" data-toggle-password="password" aria-pressed="false">Show</button>
            </div>
          </div>

          <div class="field">
            <label for="confirm_password">Confirm password</label>
            <div class="field-control">
              <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat password"
                     autocomplete="new-password" required>
              <button type="button" class="field-toggle" data-toggle-password="confirm_password" aria-pressed="false">Show</button>
            </div>
          </div>
        </div>

        <p class="field-hint" style="margin: 4px 0 22px;">*Faculty accounts gain reviewer permissions after a quick verification by an administrator.</p>

        <div class="field-row" style="align-items:flex-start; margin-bottom: 22px;">
          <label class="checkbox" style="align-items:flex-start;">
            <input type="checkbox" name="agree_terms" required>
            <span>
              I agree to the
              <a href="terms.php" class="link-amber" target="_blank" rel="noopener">Terms of Service</a>
              and
              <a href="privacy-policy.php" class="link-amber" target="_blank" rel="noopener">Privacy Policy</a>
            </span>
          </label>
        </div>

        <button type="submit" class="btn btn-amber btn-block">Create account</button>
      </form>

      <div class="auth-divider">Already registered</div>

      <p class="auth-footer-note">
        <a href="login.php" class="link-amber">Sign in instead</a>
      </p>
    </div>
  </main>

</div>

<script src="assets/js/main.js"></script>
</body>
</html>
