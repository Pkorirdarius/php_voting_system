<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';
require_admin();

$db = get_db();
$voters = $db->query("
    SELECT v.*, (SELECT COUNT(*) FROM votes vt WHERE vt.voter_id=v.id) as num_votes
    FROM voters v ORDER BY v.created_at DESC
")->fetchAll();

layout_head('Voter Registry', 'admin');
?>
<div class="page-wrap">
  <div class="hero" style="padding:2rem 0;background:linear-gradient(135deg,#1a1a2e,#16213e)">
    <div class="container hero-content">
      <h1 style="font-size:1.8rem">Voter Registry</h1>
      <p><?= count($voters) ?> registered voter<?= count($voters) !== 1 ? 's' : '' ?></p>
    </div>
  </div>
  <div class="container section">
    <div class="card">
      <div class="table-wrap">
        <table>
          <thead><tr><th>#</th><th>Full Name</th><th>National ID</th><th>Email</th><th>Ballots Cast</th><th>Registered</th></tr></thead>
          <tbody>
          <?php foreach ($voters as $i => $v): ?>
          <tr>
            <td class="text-muted"><?= $i+1 ?></td>
            <td><strong><?= htmlspecialchars($v['full_name']) ?></strong></td>
            <td class="text-sm"><?= htmlspecialchars($v['national_id']) ?></td>
            <td class="text-sm"><?= htmlspecialchars($v['email']) ?></td>
            <td><?= $v['num_votes'] > 0 ? '<span class="badge badge-approved">'.$v['num_votes'].' vote(s)</span>' : '<span class="badge badge-pending">Not voted</span>' ?></td>
            <td class="text-sm text-muted"><?= date('d M Y', strtotime($v['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php layout_foot(); ?>
