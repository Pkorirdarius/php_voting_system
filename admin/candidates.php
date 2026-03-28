<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';
require_admin();

$db  = get_db();
$msg = '';

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id     = (int)($_POST['candidate_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($id && in_array($action, ['approved', 'rejected'])) {
        $upd = $db->prepare("UPDATE candidates SET status=? WHERE id=?");
        $upd->execute([$action, $id]);
        $msg = 'Candidate status updated to ' . $action . '.';
    }
}

$filter = $_GET['status'] ?? 'all';
$where  = $filter !== 'all' ? "WHERE status=?" : "";
$args   = $filter !== 'all' ? [$filter] : [];
$stmt   = $db->prepare("SELECT c.*, (SELECT COUNT(*) FROM votes v WHERE v.candidate_id=c.id) as vote_count FROM candidates c $where ORDER BY created_at DESC");
$stmt->execute($args);
$candidates = $stmt->fetchAll();

layout_head('Manage Candidates', 'admin');
?>
<div class="page-wrap">
  <div class="hero" style="padding:2rem 0;background:linear-gradient(135deg,#1a1a2e,#16213e)">
    <div class="container hero-content">
      <h1 style="font-size:1.8rem">Candidate Applications</h1>
      <p>Review, approve, or reject candidacy submissions.</p>
    </div>
  </div>
  <div class="container section">
    <?php if ($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <!-- Filter tabs -->
    <div class="flex gap-1 mb-3">
      <?php foreach (['all','pending','approved','rejected'] as $f): ?>
        <a href="?status=<?= $f ?>" class="btn btn-sm <?= $filter===$f ? 'btn-primary' : 'btn-outline' ?>">
          <?= ucfirst($f) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if (empty($candidates)): ?>
      <div class="alert alert-info">No candidates found for this filter.</div>
    <?php else: ?>
    <div class="card">
      <div class="table-wrap">
        <table>
          <thead><tr><th>Candidate</th><th>Seat</th><th>Party</th><th>Votes</th><th>Status</th><th>Applied</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach ($candidates as $c): ?>
          <tr>
            <td>
              <strong><?= htmlspecialchars($c['full_name']) ?></strong><br>
              <span class="text-sm text-muted"><?= htmlspecialchars($c['email']) ?></span>
            </td>
            <td><?= htmlspecialchars($c['seat']) ?></td>
            <td><?= htmlspecialchars($c['party']) ?></td>
            <td><strong><?= $c['vote_count'] ?></strong></td>
            <td><span class="badge badge-<?= $c['status'] ?>"><?= ucfirst($c['status']) ?></span></td>
            <td class="text-sm text-muted"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
            <td>
              <div class="flex gap-1">
                <button onclick="toggleManifesto(<?= $c['id'] ?>)" class="btn btn-sm btn-outline">View</button>
                <?php if ($c['status'] !== 'approved'): ?>
                <form method="post" style="display:inline">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="candidate_id" value="<?= $c['id'] ?>">
                  <input type="hidden" name="action" value="approved">
                  <button class="btn btn-sm btn-primary" type="submit">Approve</button>
                </form>
                <?php endif; ?>
                <?php if ($c['status'] !== 'rejected'): ?>
                <form method="post" style="display:inline">
                  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                  <input type="hidden" name="candidate_id" value="<?= $c['id'] ?>">
                  <input type="hidden" name="action" value="rejected">
                  <button class="btn btn-sm btn-danger" type="submit">Reject</button>
                </form>
                <?php endif; ?>
              </div>
              <!-- Manifesto expand -->
              <div id="m<?= $c['id'] ?>" style="display:none;margin-top:.75rem;background:var(--paper);border-radius:6px;padding:.75rem;font-size:.85rem;max-width:400px">
                <?= nl2br(htmlspecialchars($c['manifesto'] ?: 'No manifesto provided.')) ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
<script>
function toggleManifesto(id) {
  const el = document.getElementById('m' + id);
  el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>
<?php layout_foot(); ?>
