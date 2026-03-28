<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';
require_candidate();

$db  = get_db();
$cid = $_SESSION['candidate_id'];

$me = $db->prepare("SELECT * FROM candidates WHERE id=?");
$me->execute([$cid]);
$me = $me->fetch();

// My votes
$my_votes = $db->prepare("SELECT COUNT(*) FROM votes WHERE candidate_id=?");
$my_votes->execute([$cid]);
$my_votes = (int)$my_votes->fetchColumn();

// Total votes in my seat
$seat_total = $db->prepare("SELECT COUNT(*) FROM votes WHERE seat=?");
$seat_total->execute([$me['seat']]);
$seat_total = (int)$seat_total->fetchColumn();

// My rank in seat
$rank_stmt = $db->prepare("
    SELECT c.id, c.full_name, COUNT(v.id) as vote_count
    FROM candidates c
    LEFT JOIN votes v ON v.candidate_id = c.id
    WHERE c.seat = ? AND c.status='approved'
    GROUP BY c.id ORDER BY vote_count DESC
");
$rank_stmt->execute([$me['seat']]);
$ranking = $rank_stmt->fetchAll();
$my_rank = 1;
foreach ($ranking as $i => $r) {
    if ($r['id'] == $cid) { $my_rank = $i + 1; break; }
}

// Update manifesto
$upd_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manifesto'])) {
    verify_csrf();
    $upd = $db->prepare("UPDATE candidates SET manifesto=? WHERE id=?");
    $upd->execute([trim($_POST['manifesto']), $cid]);
    $me['manifesto'] = trim($_POST['manifesto']);
    $upd_msg = 'Manifesto updated successfully.';
}

layout_head('My Dashboard', 'candidate');
$status_labels = ['pending'=>'Pending Review','approved'=>'Approved','rejected'=>'Rejected'];
$status_badge  = ['pending'=>'badge-pending','approved'=>'badge-approved','rejected'=>'badge-rejected'];
?>
<div class="page-wrap">
  <div class="hero" style="padding:2rem 0">
    <div class="container hero-content">
      <div class="hero-eyebrow">Candidate Dashboard</div>
      <h1 style="font-size:1.8rem"><?= htmlspecialchars($me['full_name']) ?></h1>
      <p><?= htmlspecialchars($me['party']) ?> · Running for <strong><?= htmlspecialchars($me['seat']) ?></strong></p>
    </div>
  </div>

  <div class="container section">
    <?php if ($upd_msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($upd_msg) ?></div><?php endif; ?>

    <!-- Status banner -->
    <?php if ($me['status'] === 'pending'): ?>
      <div class="alert alert-warning">⏳ Your candidacy is awaiting admin approval. You'll be visible to voters once approved.</div>
    <?php elseif ($me['status'] === 'rejected'): ?>
      <div class="alert alert-error">❌ Your candidacy was not approved. Contact the election office for details.</div>
    <?php endif; ?>

    <!-- Stats row -->
    <div class="grid-3 mb-3">
      <div class="stat-box">
        <div class="stat-number"><?= $my_votes ?></div>
        <div class="stat-label">Votes Received</div>
      </div>
      <div class="stat-box">
        <div class="stat-number">#<?= $my_rank ?></div>
        <div class="stat-label">Current Rank</div>
      </div>
      <div class="stat-box">
        <div class="stat-number">
          <span class="badge <?= $status_badge[$me['status']] ?>" style="font-size:1rem;padding:.4rem 1rem">
            <?= $status_labels[$me['status']] ?>
          </span>
        </div>
        <div class="stat-label">Candidacy Status</div>
      </div>
    </div>

    <!-- Seat leaderboard -->
    <?php if (!empty($ranking)): ?>
    <div class="card mb-3">
      <div class="card-header"><h3 style="margin:0">Standings — <?= htmlspecialchars($me['seat']) ?></h3></div>
      <div class="card-body" style="padding:0">
        <div class="table-wrap">
          <table>
            <thead><tr><th>#</th><th>Candidate</th><th>Votes</th><th>Share</th></tr></thead>
            <tbody>
            <?php foreach ($ranking as $i => $r):
                $pct = $seat_total > 0 ? round($r['vote_count'] / $seat_total * 100, 1) : 0;
                $is_me = $r['id'] == $cid;
            ?>
            <tr style="<?= $is_me ? 'background:var(--mist);font-weight:600' : '' ?>">
              <td><?= $i+1 ?></td>
              <td><?= htmlspecialchars($r['full_name']) ?> <?= $is_me ? '<span class="badge badge-gold">You</span>' : '' ?></td>
              <td><?= $r['vote_count'] ?></td>
              <td>
                <div class="flex-center gap-2">
                  <div class="progress" style="flex:1;min-width:80px"><div class="progress-bar" style="width:<?= $pct ?>%"></div></div>
                  <span class="text-sm"><?= $pct ?>%</span>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Edit manifesto -->
    <div class="card">
      <div class="card-header"><h3 style="margin:0">My Manifesto</h3></div>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <div class="card-body">
          <textarea class="form-control" name="manifesto" rows="6"><?= htmlspecialchars($me['manifesto'] ?? '') ?></textarea>
        </div>
        <div class="card-footer">
          <button class="btn btn-primary" type="submit">Save Manifesto</button>
        </div>
      </form>
    </div>

    <div class="text-center mt-4">
      <a href="/admin/live.php" class="btn btn-outline">📊 Live Results</a>
    </div>
  </div>
</div>
<?php layout_foot(); ?>
