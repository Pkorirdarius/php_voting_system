<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';

if (is_logged_in_voter()) { header('Location: /voter/vote.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $db    = get_db();
    $voter = $db->prepare("SELECT * FROM voters WHERE email=?");
    $voter->execute([$email]);
    $voter = $voter->fetch();
    if ($voter && password_verify($pass, $voter['password_hash'])) {
        $_SESSION['voter_id']   = $voter['id'];
        $_SESSION['voter_name'] = $voter['full_name'];
        header('Location: /voter/vote.php');
        exit;
    }
    $error = 'Invalid email or password.';
}

layout_head('Voter Login', 'public');
?>
<div class="auth-wrap">
  <div class="auth-left">
    <div class="icon-large">🔐</div>
    <h2>Welcome Back</h2>
    <p>Log in to access your ballot. Your vote is secret, secure, and counts only once.</p>
    <div style="margin-top:2rem;font-size:.85rem;color:rgba(255,255,255,.6)">
      New voter? <a href="/voter/register.php" style="color:var(--gold-light);font-weight:600">Register here →</a>
    </div>
  </div>
  <div class="auth-right">
    <h3>Voter Login</h3>
    <?php if ($error): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div class="form-group">
        <label>Email Address</label>
        <input class="form-control" type="email" name="email" placeholder="you@example.com" required autofocus>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input class="form-control" type="password" name="password" placeholder="Your password" required>
      </div>
      <button class="btn btn-primary btn-block btn-lg" type="submit">Login &amp; Vote</button>
    </form>
  </div>
</div>
<?php layout_foot(); ?>
