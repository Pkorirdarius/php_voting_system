<?php
/**
 * layout helpers — call layout_head() / layout_foot()
 * $role = 'voter' | 'candidate' | 'admin' | 'public'
 */
function layout_head(string $title, string $role = 'public'): void { ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($title) ?> — VoteSecure</title>
<link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
<?php layout_nav($role); }

function layout_nav(string $role): void {
    $voter_id     = $_SESSION['voter_id']     ?? null;
    $candidate_id = $_SESSION['candidate_id'] ?? null;
    $admin_id     = $_SESSION['admin_id']     ?? null; ?>
<nav class="nav">
  <div class="container nav-inner">
    <a class="nav-brand" href="/">
      <div class="seal">VS</div> VoteSecure
    </a>
    <div class="nav-links">
    <?php if ($role === 'admin'): ?>
      <a href="/admin/dashboard.php">Dashboard</a>
      <a href="/admin/candidates.php">Candidates</a>
      <a href="/admin/live.php">Live Results</a>
      <a href="/admin/logout.php" class="btn-nav">Logout</a>
    <?php elseif ($role === 'candidate'): ?>
      <a href="/candidate/dashboard.php">My Profile</a>
      <a href="/admin/live.php">Live Results</a>
      <a href="/candidate/logout.php" class="btn-nav">Logout</a>
    <?php elseif ($role === 'voter'): ?>
      <a href="/voter/vote.php">Vote Now</a>
      <a href="/admin/live.php">Live Results</a>
      <a href="/voter/logout.php" class="btn-nav">Logout</a>
    <?php else: ?>
      <a href="/voter/register.php">Register to Vote</a>
      <a href="/voter/login.php">Voter Login</a>
      <a href="/candidate/register.php">I'm a Candidate</a>
      <a href="/admin/login.php" class="btn-nav">Admin</a>
    <?php endif; ?>
    </div>
  </div>
</nav>
<?php }

function layout_foot(): void { ?>
<footer>
  <strong>VoteSecure</strong> &nbsp;|&nbsp; Secure. Transparent. Democratic. &nbsp;&copy; <?= date('Y') ?>
</footer>
</body>
</html>
<?php }
