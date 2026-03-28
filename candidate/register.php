<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';

if (is_logged_in_candidate()) { header('Location: /candidate/dashboard.php'); exit; }

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name      = trim($_POST['full_name']  ?? '');
    $email     = trim($_POST['email']      ?? '');
    $pass      = $_POST['password']        ?? '';
    $pass2     = $_POST['password2']       ?? '';
    $seat      = trim($_POST['seat']       ?? '');
    $party     = trim($_POST['party']      ?? '');
    $manifesto = trim($_POST['manifesto']  ?? '');

    if (!$name || !$email || !$pass || !$seat || !$party) {
        $error = 'All required fields must be filled.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($pass) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($pass !== $pass2) {
        $error = 'Passwords do not match.';
    } else {
        $db  = get_db();
        $chk = $db->prepare("SELECT id FROM candidates WHERE email=?");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $error = 'A candidate with this email already exists.';
        } else {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO candidates (full_name,email,password_hash,seat,party,manifesto) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$name, $email, $hash, $seat, $party, $manifesto]);
            $success = 'Application submitted! An admin will review your candidacy. Please login to check your status.';
        }
    }
}

layout_head('Candidate Registration', 'public');
?>
<div class="auth-wrap">
  <div class="auth-left">
    <div class="icon-large">🏛️</div>
    <h2>Run for Office</h2>
    <p>Submit your candidacy application. Once approved by an administrator, voters can cast ballots for you.</p>
    <div style="margin-top:2rem;font-size:.85rem;color:rgba(255,255,255,.6)">
      Already registered? <a href="/candidate/login.php" style="color:var(--gold-light);font-weight:600">Login here →</a>
    </div>
  </div>
  <div class="auth-right" style="overflow-y:auto;padding:2rem 2.5rem">
    <h3>Candidate Application</h3>
    <?php if ($error):   ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?> <a href="/candidate/login.php">Login</a></div><?php endif; ?>
    <?php if (!$success): ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div class="form-group">
        <label>Full Name *</label>
        <input class="form-control" type="text" name="full_name" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Email Address *</label>
        <input class="form-control" type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Electoral Seat *</label>
        <input class="form-control" type="text" name="seat" placeholder="e.g. Governor, Senator, Mayor" required value="<?= htmlspecialchars($_POST['seat'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Political Party *</label>
        <input class="form-control" type="text" name="party" placeholder="e.g. Progressive Alliance" required value="<?= htmlspecialchars($_POST['party'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Manifesto / Campaign Statement</label>
        <textarea class="form-control" name="manifesto" placeholder="Tell voters what you stand for..."><?= htmlspecialchars($_POST['manifesto'] ?? '') ?></textarea>
      </div>
      <div class="form-group">
        <label>Password *</label>
        <input class="form-control" type="password" name="password" placeholder="Min. 8 characters" required>
      </div>
      <div class="form-group">
        <label>Confirm Password *</label>
        <input class="form-control" type="password" name="password2" required>
      </div>
      <button class="btn btn-primary btn-block btn-lg" type="submit">Submit Application</button>
    </form>
    <?php endif; ?>
  </div>
</div>
<?php layout_foot(); ?>
