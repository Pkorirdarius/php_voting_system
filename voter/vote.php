<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';
require_voter();

$db       = get_db();
$voter_id = $_SESSION['voter_id'];

// Fetch voter record
$voter = $db->prepare("SELECT * FROM voters WHERE id=?");
$voter->execute([$voter_id]);
$voter = $voter->fetch();

// Seats already voted for
$voted_seats_stmt = $db->prepare("SELECT seat FROM votes WHERE voter_id=?");
$voted_seats_stmt->execute([$voter_id]);
$voted_seats = $voted_seats_stmt->fetchAll(PDO::FETCH_COLUMN);

// All approved seats and their candidates
$seats_stmt = $db->query("SELECT DISTINCT seat FROM candidates WHERE status='approved' ORDER BY seat");
$seats = $seats_stmt->fetchAll(PDO::FETCH_COLUMN);

$message = '';
$msg_type = 'success';

// Handle vote submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['candidate_id'], $_POST['seat'])) {
    verify_csrf();
    $cid  = (int)$_POST['candidate_id'];
    $seat = trim($_POST['seat']);

    // Already voted for this seat?
    if (in_array($seat, $voted_seats)) {
        $message = 'You have already voted for the ' . htmlspecialchars($seat) . ' seat.';
        $msg_type = 'error';
    } else {
        // Verify candidate is approved & belongs to seat
        $chk = $db->prepare("SELECT id FROM candidates WHERE id=? AND seat=? AND status='approved'");
        $chk->execute([$cid, $seat]);
        if (!$chk->fetch()) {
            $message = 'Invalid candidate selection.';
            $msg_type = 'error';
        } else {
            $ins = $db->prepare("INSERT INTO votes (voter_id,candidate_id,seat) VALUES (?,?,?)");
            $ins->execute([$voter_id, $cid, $seat]);
            // Refresh voted seats
            $voted_seats[] = $seat;
            $message = '✅ Your vote for the ' . htmlspecialchars($seat) . ' seat has been recorded!';
        }
    }
}

layout_head('Cast Your Vote', 'voter');
?>
<div class="page-wrap">
  <div class="hero" style="padding:2.5rem 0">
    <div class="container hero-content">
      <h1 style="font-size:1.8rem">Welcome, <?= htmlspecialchars($voter['full_name']) ?></h1>
      <p>Select one candidate per electoral seat. Your choices are private and final.</p>
    </div>
  </div>

  <div class="container section">
    <?php if ($message): ?>
      <div class="alert alert-<?= $msg_type === 'error' ? 'error' : 'success' ?>"><?= $message ?></div>
    <?php endif; ?>

    <!-- Progress indicator -->
    <?php if (!empty($seats)): ?>
    <div class="card mb-3">
      <div class="card-body">
        <div class="flex-center justify-between gap-2 mb-2">
          <span style="font-weight:600">Your Voting Progress</span>
          <span class="badge badge-approved"><?= count($voted_seats) ?> / <?= count($seats) ?> Seats</span>
        </div>
        <div class="progress">
          <div class="progress-bar" style="width:<?= count($seats) > 0 ? round(count($voted_seats)/count($seats)*100) : 0 ?>%"></div>
        </div>
        <?php if (count($voted_seats) === count($seats) && count($seats) > 0): ?>
          <p class="text-center mt-2" style="color:var(--emerald);font-weight:600">🎉 You have voted in all seats. Thank you!</p>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if (empty($seats)): ?>
      <div class="alert alert-info">ℹ️ No approved candidates are available yet. Please check back later.</div>
    <?php else: ?>

    <?php foreach ($seats as $seat):
        $already_voted = in_array($seat, $voted_seats);
        $candidates_stmt = $db->prepare("SELECT * FROM candidates WHERE seat=? AND status='approved' ORDER BY full_name");
        $candidates_stmt->execute([$seat]);
        $candidates = $candidates_stmt->fetchAll();
    ?>
    <div class="card mb-3" style="<?= $already_voted ? 'opacity:.7' : '' ?>">
      <div class="card-header">
        <div>
          <h3 style="margin:0"><?= htmlspecialchars($seat) ?></h3>
          <span class="text-sm text-muted"><?= count($candidates) ?> candidate<?= count($candidates) !== 1 ? 's' : '' ?></span>
        </div>
        <?php if ($already_voted): ?>
          <span class="badge badge-approved">✓ Voted</span>
        <?php else: ?>
          <span class="badge badge-pending">Pending</span>
        <?php endif; ?>
      </div>

      <?php if ($already_voted): ?>
        <div class="card-body"><p class="text-muted text-sm">You have already cast your vote for this seat.</p></div>
      <?php elseif (empty($candidates)): ?>
        <div class="card-body"><p class="text-muted text-sm">No candidates for this seat yet.</p></div>
      <?php else: ?>
        <form method="post">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
          <input type="hidden" name="seat" value="<?= htmlspecialchars($seat) ?>">
          <div class="card-body">
            <div class="grid-2">
              <?php foreach ($candidates as $c): ?>
              <label class="candidate-card" style="cursor:pointer" id="card-<?= $c['id'] ?>">
                <input type="radio" name="candidate_id" value="<?= $c['id'] ?>" required
                       style="position:absolute;opacity:0;pointer-events:none"
                       onchange="highlightCard(this)">
                <div class="flex-center gap-2">
                  <div class="candidate-avatar"><?= strtoupper(substr($c['full_name'],0,1)) ?></div>
                  <div>
                    <div class="candidate-name"><?= htmlspecialchars($c['full_name']) ?></div>
                    <div class="candidate-meta">🏛️ <?= htmlspecialchars($c['party']) ?></div>
                  </div>
                </div>
                <?php if ($c['manifesto']): ?>
                  <p style="font-size:.85rem;color:var(--ink-soft);margin-top:.75rem;line-height:1.5">
                    <?= nl2br(htmlspecialchars(mb_substr($c['manifesto'], 0, 180))) ?><?= mb_strlen($c['manifesto']) > 180 ? '…' : '' ?>
                  </p>
                <?php endif; ?>
              </label>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="card-footer">
            <button class="btn btn-primary" type="submit">
              🗳️ Cast Vote for <?= htmlspecialchars($seat) ?>
            </button>
          </div>
        </form>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <div class="text-center mt-4">
      <a href="/admin/live.php" class="btn btn-outline">📊 View Live Results</a>
      &nbsp;
      <a href="/voter/logout.php" class="btn btn-outline btn-sm" style="color:var(--crimson);border-color:var(--crimson)">Logout</a>
    </div>
  </div>
</div>

<script>
function highlightCard(radio) {
  // Remove selected state from all cards in same form
  const form = radio.closest('form');
  form.querySelectorAll('.candidate-card').forEach(c => c.classList.remove('selected'));
  radio.closest('.candidate-card').classList.add('selected');
}
</script>
<?php layout_foot(); ?>
