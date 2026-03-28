<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';
require_admin();

$db = get_db();
$total_voters     = $db->query("SELECT COUNT(*) FROM voters")->fetchColumn();
$total_candidates = $db->query("SELECT COUNT(*) FROM candidates")->fetchColumn();
$pending          = $db->query("SELECT COUNT(*) FROM candidates WHERE status='pending'")->fetchColumn();
$total_votes      = $db->query("SELECT COUNT(*) FROM votes")->fetchColumn();
$voters_voted     = $db->query("SELECT COUNT(DISTINCT voter_id) FROM votes")->fetchColumn();

// Recent votes
$recent = $db->query("
    SELECT v.voted_at, c.full_name AS candidate, c.seat, vt.full_name AS voter
    FROM votes v
    JOIN candidates c ON c.id = v.candidate_id
    JOIN voters vt ON vt.id = v.voter_id
    ORDER BY v.voted_at DESC LIMIT 10
")->fetchAll();

layout_head('Admin Dashboard', 'admin');
?>
<div class="page-wrap">
  <div class="hero" style="padding:2rem 0;background:linear-gradient(135deg,#1a1a2e,#16213e)">
    <div class="container hero-content">
      <div class="hero-eyebrow">Administration</div>
      <h1 style="font-size:1.8rem">Election Control Centre</h1>
      <p>Welcome, <?= htmlspecialchars($_SESSION['admin_name']) ?>. Manage candidates, monitor live results.</p>
    </div>
  </div>

  <div class="container section">
    <?php if ($pending > 0): ?>
      <div class="alert alert-warning">⏳ <strong><?= $pending ?></strong> candidate application<?= $pending > 1 ? 's' : '' ?> pending approval. <a href="/admin/candidates.php" style="font-weight:700">Review now →</a></div>
    <?php endif; ?>

    <div class="grid-3 mb-3" style="grid-template-columns:repeat(auto-fill,minmax(180px,1fr))">
      <div class="stat-box"><div class="stat-number"><?= number_format($total_voters) ?></div><div class="stat-label">Registered Voters</div></div>
      <div class="stat-box"><div class="stat-number"><?= number_format($voters_voted) ?></div><div class="stat-label">Voters Who Voted</div></div>
      <div class="stat-box"><div class="stat-number"><?= number_format($total_votes) ?></div><div class="stat-label">Total Ballots Cast</div></div>
      <div class="stat-box"><div class="stat-number"><?= number_format($total_candidates) ?></div><div class="stat-label">Candidate Applications</div></div>
      <div class="stat-box"><div class="stat-number" style="color:<?= $pending > 0 ? 'var(--gold)' : 'var(--emerald)' ?>"><?= $pending ?></div><div class="stat-label">Pending Approval</div></div>
      <div class="stat-box">
        <div class="stat-number"><?= $total_voters > 0 ? round($voters_voted/$total_voters*100) : 0 ?>%</div>
        <div class="stat-label">Voter Turnout</div>
      </div>
    </div>

    <!-- Quick links -->
    <div class="grid-3 mb-3">
      <a href="/admin/candidates.php" class="card" style="text-decoration:none">
        <div class="card-body text-center" style="padding:1.5rem">
          <div style="font-size:2rem;margin-bottom:.5rem">👤</div>
          <h3>Manage Candidates</h3>
          <p class="text-muted text-sm mt-1">Approve or reject candidacy applications.</p>
        </div>
      </a>
      <a href="/admin/live.php" class="card" style="text-decoration:none">
        <div class="card-body text-center" style="padding:1.5rem">
          <div style="font-size:2rem;margin-bottom:.5rem">📊</div>
          <h3>Live Results</h3>
          <p class="text-muted text-sm mt-1">Real-time voting tallies and leaderboards.</p>
        </div>
      </a>
      <a href="/admin/voters.php" class="card" style="text-decoration:none">
        <div class="card-body text-center" style="padding:1.5rem">
          <div style="font-size:2rem;margin-bottom:.5rem">🗂️</div>
          <h3>Voter Registry</h3>
          <p class="text-muted text-sm mt-1">Browse all registered voters.</p>
        </div>
      </a>
    </div>

    <!-- Recent activity -->
    <?php if (!empty($recent)): ?>
    <div class="card">
      <div class="card-header"><h3 style="margin:0">Recent Votes</h3><span class="badge badge-approved">Live</span></div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Time</th><th>Voter</th><th>Candidate</th><th>Seat</th></tr></thead>
          <tbody>
          <?php foreach ($recent as $r): ?>
          <tr>
            <td class="text-sm text-muted"><?= date('H:i:s', strtotime($r['voted_at'])) ?></td>
            <td><?= htmlspecialchars($r['voter']) ?></td>
            <td><?= htmlspecialchars($r['candidate']) ?></td>
            <td><span class="badge badge-approved"><?= htmlspecialchars($r['seat']) ?></span></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php layout_foot(); ?>
