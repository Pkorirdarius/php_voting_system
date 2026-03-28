<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';

if (is_logged_in_candidate()) { header('Location: /candidate/dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $db    = get_db();
    $c     = $db->prepare("SELECT * FROM candidates WHERE email=?");
    $c->execute([$email]);
    $c = $c->fetch();
    if ($c && password_verify($pass, $c['password_hash'])) {
        $_SESSION['candidate_id']   = $c['id'];
        $_SESSION['candidate_name'] = $c['full_name'];
        header('Location: /candidate/dashboard.php');
        exit;
    }
    $error = 'Invalid email or password.';
}

layout_head('Candidate Login', 'public');
?>
<div class="auth-wrap">
  <div class="auth-left">
    <div class="icon-large">🏛️</div>
    <h2>Candidate Portal</h2>
    <p>Access your campaign dashboard. View your approval status, vote tally, and election standing.</p>
    <div style="margin-top:2rem;font-size:.85rem;color:rgba(255,255,255,.6)">
      New candidate? <a href="/candidate/register.php" style="color:var(--gold-light);font-weight:600">Apply here →</a>
    </div>
  </div>
  <div class="auth-right">
    <h3>Candidate Login</h3>
    <?php if ($error): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div class="form-group">
        <label>Email Address</label>
        <input class="form-control" type="email" name="email" required autofocus>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input class="form-control" type="password" name="password" required>
      </div>
      <button class="btn btn-primary btn-block btn-lg" type="submit">Login</button>
    </form>
  </div>
</div>
<?php layout_foot(); ?>
