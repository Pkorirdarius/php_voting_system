<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';

if (is_logged_in_voter()) { header('Location: /voter/vote.php'); exit; }

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name   = trim($_POST['full_name']   ?? '');
    $nid    = trim($_POST['national_id'] ?? '');
    $email  = trim($_POST['email']       ?? '');
    $pass   = $_POST['password']         ?? '';
    $pass2  = $_POST['password2']        ?? '';

    if (!$name || !$nid || !$email || !$pass) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($pass) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($pass !== $pass2) {
        $error = 'Passwords do not match.';
    } else {
        $db = get_db();
        $exists = $db->prepare("SELECT id FROM voters WHERE national_id=? OR email=?");
        $exists->execute([$nid, $email]);
        if ($exists->fetch()) {
            $error = 'A voter with this National ID or email already exists.';
        } else {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO voters (full_name,national_id,email,password_hash) VALUES (?,?,?,?)");
            $stmt->execute([$name, $nid, $email, $hash]);
            $success = 'Registration successful! You can now login and cast your vote.';
        }
    }
}

layout_head('Voter Registration', 'public');
?>
<div class="auth-wrap">
  <div class="auth-left">
    <div class="icon-large">🗳️</div>
    <h2>Register to Vote</h2>
    <p>Create your secure voter account using your National ID. You'll be able to cast one vote per electoral seat.</p>
    <div style="margin-top:2rem;font-size:.85rem;color:rgba(255,255,255,.6)">
      Already registered? <a href="/voter/login.php" style="color:var(--gold-light);font-weight:600">Login here →</a>
    </div>
  </div>
  <div class="auth-right">
    <h3>Create Voter Account</h3>
    <?php if ($error):   ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?> <a href="/voter/login.php">Login now</a></div><?php endif; ?>
    <?php if (!$success): ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div class="form-group">
        <label>Full Name</label>
        <input class="form-control" type="text" name="full_name" placeholder="e.g. Jane Doe" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>National ID</label>
        <input class="form-control" type="text" name="national_id" placeholder="Your national ID number" required value="<?= htmlspecialchars($_POST['national_id'] ?? '') ?>">
        <div class="form-hint">This uniquely identifies you and prevents duplicate registrations.</div>
      </div>
      <div class="form-group">
        <label>Email Address</label>
        <input class="form-control" type="email" name="email" placeholder="you@example.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input class="form-control" type="password" name="password" placeholder="Min. 8 characters" required>
      </div>
      <div class="form-group">
        <label>Confirm Password</label>
        <input class="form-control" type="password" name="password2" placeholder="Repeat password" required>
      </div>
      <button class="btn btn-primary btn-block btn-lg" type="submit">Register Now</button>
    </form>
    <?php endif; ?>
  </div>
</div>
<?php layout_foot(); ?>
