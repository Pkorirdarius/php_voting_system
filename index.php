<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/layout.php';

$db = get_db();
$total_voters     = $db->query("SELECT COUNT(*) FROM voters")->fetchColumn();
$total_candidates = $db->query("SELECT COUNT(*) FROM candidates WHERE status='approved'")->fetchColumn();
$total_votes      = $db->query("SELECT COUNT(*) FROM votes")->fetchColumn();
$seats            = $db->query("SELECT DISTINCT seat FROM candidates WHERE status='approved' ORDER BY seat")->fetchAll(PDO::FETCH_COLUMN);

layout_head('Welcome', 'public');
?>

<div class="page-wrap">
  <div class="hero">
    <div class="container hero-content">
      <div class="hero-eyebrow">🗳️ General Elections 2026</div>
      <h1>Your Vote.<br>Your Voice.<br>Your Future.</h1>
      <p>Cast your ballot securely. Every vote counts in shaping tomorrow's leadership.</p>
      <div class="flex gap-2 mt-4" style="flex-wrap:wrap">
        <a href="/voter/register.php" class="btn btn-gold btn-lg">Register to Vote</a>
        <a href="/voter/login.php"    class="btn btn-outline btn-lg" style="color:#fff;border-color:#fff">Voter Login</a>
      </div>
    </div>
  </div>

  <div class="container section">
    <!-- Stats -->
    <div class="grid-3 mb-3">
      <div class="stat-box">
        <div class="stat-number"><?= number_format($total_voters) ?></div>
        <div class="stat-label">Registered Voters</div>
      </div>
      <div class="stat-box">
        <div class="stat-number"><?= number_format($total_candidates) ?></div>
        <div class="stat-label">Approved Candidates</div>
      </div>
      <div class="stat-box">
        <div class="stat-number"><?= number_format($total_votes) ?></div>
        <div class="stat-label">Votes Cast</div>
      </div>
    </div>

    <!-- Portal cards -->
    <div class="section-title"><h2>Portals</h2></div>
    <div class="grid-3">
      <div class="card">
        <div class="card-body text-center" style="padding:2rem">
          <div style="font-size:2.8rem;margin-bottom:1rem">🗳️</div>
          <h3>Voter Portal</h3>
          <p class="text-muted text-sm mt-1 mb-3">Register with your national ID and cast your vote securely. One person, one vote.</p>
          <a href="/voter/register.php" class="btn btn-primary btn-block">Get Started</a>
        </div>
      </div>
      <div class="card">
        <div class="card-body text-center" style="padding:2rem">
          <div style="font-size:2.8rem;margin-bottom:1rem">🏛️</div>
          <h3>Candidate Portal</h3>
          <p class="text-muted text-sm mt-1 mb-3">Register your candidacy, submit your manifesto, and track your campaign progress.</p>
          <a href="/candidate/register.php" class="btn btn-primary btn-block">Register Candidacy</a>
        </div>
      </div>
      <div class="card">
        <div class="card-body text-center" style="padding:2rem">
          <div style="font-size:2.8rem;margin-bottom:1rem">📊</div>
          <h3>Live Results</h3>
          <p class="text-muted text-sm mt-1 mb-3">Watch real-time voting results as ballots are cast. Updated every 10 seconds.</p>
          <a href="/admin/live.php" class="btn btn-primary btn-block">View Live</a>
        </div>
      </div>
    </div>

    <?php if (!empty($seats)): ?>
    <div class="section-title mt-4"><h2>Electoral Seats</h2></div>
    <div class="flex gap-1" style="flex-wrap:wrap">
      <?php foreach ($seats as $s): ?>
        <span class="badge badge-approved" style="font-size:.9rem;padding:.35rem .85rem"><?= htmlspecialchars($s) ?></span>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php layout_foot(); ?>
