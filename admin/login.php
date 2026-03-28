<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';

if (is_logged_in_admin()) { header('Location: /admin/dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    $db   = get_db();
    $a    = $db->prepare("SELECT * FROM admins WHERE username=?");
    $a->execute([$user]);
    $a = $a->fetch();
    if ($a && password_verify($pass, $a['password_hash'])) {
        $_SESSION['admin_id']   = $a['id'];
        $_SESSION['admin_name'] = $a['username'];
        header('Location: /admin/dashboard.php');
        exit;
    }
    $error = 'Invalid credentials.';
}

layout_head('Admin Login', 'public');
?>
<div class="auth-wrap">
  <div class="auth-left" style="background:linear-gradient(160deg,#1a1a2e,#16213e)">
    <div class="icon-large">🔑</div>
    <h2 style="color:var(--gold-light)">Admin Panel</h2>
    <p>Restricted access. Authorised election administrators only.</p>
  </div>
  <div class="auth-right">
    <h3>Administrator Login</h3>
    <?php if ($error): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div class="form-group">
        <label>Username</label>
        <input class="form-control" type="text" name="username" required autofocus>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input class="form-control" type="password" name="password" required>
      </div>
      <button class="btn btn-primary btn-block btn-lg" type="submit">Login</button>
    </form>
    <p class="text-sm text-muted mt-2">Default: admin / Admin@1234 — <em>change after first login.</em></p>
  </div>
</div>
<?php layout_foot(); ?>
