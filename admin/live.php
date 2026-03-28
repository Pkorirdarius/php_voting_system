<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';

$db = get_db();

// All seats with candidates and vote counts
$seats_stmt = $db->query("SELECT DISTINCT seat FROM candidates WHERE status='approved' ORDER BY seat");
$seats = $seats_stmt->fetchAll(PDO::FETCH_COLUMN);

$results = [];
foreach ($seats as $seat) {
    $stmt = $db->prepare("
        SELECT c.id, c.full_name, c.party,
               COUNT(v.id) AS vote_count
        FROM candidates c
        LEFT JOIN votes v ON v.candidate_id = c.id
        WHERE c.seat = ? AND c.status = 'approved'
        GROUP BY c.id
        ORDER BY vote_count DESC
    ");
    $stmt->execute([$seat]);
    $results[$seat] = $stmt->fetchAll();
}

$total_votes  = $db->query("SELECT COUNT(*) FROM votes")->fetchColumn();
$total_voters = $db->query("SELECT COUNT(*) FROM voters")->fetchColumn();
$voted_unique = $db->query("SELECT COUNT(DISTINCT voter_id) FROM votes")->fetchColumn();
$turnout      = $total_voters > 0 ? round($voted_unique / $total_voters * 100, 1) : 0;

// Recent 20 votes for ticker
$ticker = $db->query("
    SELECT v.voted_at, c.full_name AS candidate, c.seat, vt.full_name AS voter
    FROM votes v
    JOIN candidates c ON c.id = v.candidate_id
    JOIN voters vt    ON vt.id = v.voter_id
    ORDER BY v.voted_at DESC LIMIT 20
")->fetchAll();

// Determine role for nav
$role = 'public';
if (!empty($_SESSION['admin_id']))     $role = 'admin';
elseif (!empty($_SESSION['voter_id'])) $role = 'voter';
elseif (!empty($_SESSION['candidate_id'])) $role = 'candidate';

layout_head('Live Election Results', $role);
?>
<div class="page-wrap">

  <!-- Hero -->
  <div class="hero" style="padding:2.2rem 0">
    <div class="container hero-content">
      <div class="hero-eyebrow" id="live-badge">🔴 LIVE — Updates every 10 seconds</div>
      <h1 style="font-size:2rem">Live Election Results</h1>
      <p>Real-time vote tallies as ballots are being cast.</p>
    </div>
  </div>

  <div class="container section">

    <!-- Top stats bar -->
    <div class="grid-3 mb-3" style="grid-template-columns:repeat(auto-fill,minmax(180px,1fr))">
      <div class="stat-box">
        <div class="stat-number" id="stat-total"><?= number_format($total_votes) ?></div>
        <div class="stat-label">Total Ballots Cast</div>
      </div>
      <div class="stat-box">
        <div class="stat-number" id="stat-voters"><?= number_format($total_voters) ?></div>
        <div class="stat-label">Registered Voters</div>
      </div>
      <div class="stat-box">
        <div class="stat-number" id="stat-turnout"><?= $turnout ?>%</div>
        <div class="stat-label">Voter Turnout</div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 340px;gap:1.5rem;align-items:start" id="main-grid">

      <!-- Left: Results per seat -->
      <div id="results-container">
        <?php if (empty($results)): ?>
          <div class="alert alert-info">No approved candidates yet. Results will appear here once candidates are approved.</div>
        <?php else: ?>
          <?php foreach ($results as $seat => $candidates):
              $seat_total = array_sum(array_column($candidates, 'vote_count'));
          ?>
          <div class="card mb-3 seat-card" data-seat="<?= htmlspecialchars($seat) ?>">
            <div class="card-header">
              <h3 style="margin:0"><?= htmlspecialchars($seat) ?></h3>
              <span class="text-sm text-muted"><?= $seat_total ?> vote<?= $seat_total !== 1 ? 's' : '' ?></span>
            </div>
            <div class="card-body">
              <?php foreach ($candidates as $i => $c):
                  $pct = $seat_total > 0 ? round($c['vote_count'] / $seat_total * 100, 1) : 0;
                  $rankClass = $i === 0 ? 'rank-1' : ($i === 1 ? 'rank-2' : ($i === 2 ? 'rank-3' : ''));
              ?>
              <div class="<?= $rankClass ?>" style="margin-bottom:1.1rem">
                <div class="flex-center justify-between mb-1">
                  <div>
                    <span class="candidate-name" style="font-family:'Playfair Display',serif;font-weight:700">
                      <?= htmlspecialchars($c['full_name']) ?>
                    </span>
                    <span class="text-sm text-muted" style="margin-left:.5rem"><?= htmlspecialchars($c['party']) ?></span>
                  </div>
                  <div class="flex-center gap-2">
                    <strong><?= $c['vote_count'] ?></strong>
                    <span class="text-sm text-muted">(<?= $pct ?>%)</span>
                    <?php if ($i === 0 && $seat_total > 0): ?><span class="badge badge-gold">Leading</span><?php endif; ?>
                  </div>
                </div>
                <div class="progress">
                  <div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $i===0 ? 'linear-gradient(90deg,var(--gold),var(--gold-light))' : 'linear-gradient(90deg,var(--emerald),#2e8b57)' ?>"></div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Right: Live ticker -->
      <div style="position:sticky;top:80px">
        <div class="card">
          <div class="card-header">
            <div class="flex-center gap-2">
              <div class="vote-dot"></div>
              <h3 style="margin:0;font-size:1rem">Live Vote Feed</h3>
            </div>
          </div>
          <div id="ticker" style="padding:.75rem;max-height:520px;overflow-y:auto;display:flex;flex-direction:column;gap:.5rem">
            <?php if (empty($ticker)): ?>
              <p class="text-muted text-sm text-center" style="padding:1rem">No votes cast yet.</p>
            <?php else: ?>
              <?php foreach ($ticker as $t): ?>
              <div class="vote-ticker">
                <div class="vote-dot"></div>
                <div>
                  <div style="font-size:.82rem;font-weight:600"><?= htmlspecialchars($t['voter']) ?></div>
                  <div style="font-size:.78rem;color:var(--ink-soft)">
                    voted for <strong><?= htmlspecialchars($t['candidate']) ?></strong> · <?= htmlspecialchars($t['seat']) ?>
                  </div>
                  <div style="font-size:.72rem;color:var(--ink-soft)"><?= date('H:i:s', strtotime($t['voted_at'])) ?></div>
                </div>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- Leading candidates summary -->
        <div class="card mt-3">
          <div class="card-header"><h3 style="margin:0;font-size:1rem">🏆 Current Leaders</h3></div>
          <div id="leaders" style="padding:.75rem;display:flex;flex-direction:column;gap:.6rem">
            <?php foreach ($results as $seat => $candidates):
                if (empty($candidates)) continue;
                $leader = $candidates[0];
            ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:.5rem .75rem;background:var(--paper);border-radius:6px">
              <div>
                <div style="font-size:.82rem;font-weight:700"><?= htmlspecialchars($leader['full_name']) ?></div>
                <div style="font-size:.75rem;color:var(--ink-soft)"><?= htmlspecialchars($seat) ?></div>
              </div>
              <span class="badge badge-gold"><?= $leader['vote_count'] ?> votes</span>
            </div>
            <?php endforeach; ?>
            <?php if (empty($results)): ?>
              <p class="text-muted text-sm text-center" style="padding:.5rem">No data yet.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div><!-- /main-grid -->

    <div class="text-center mt-4">
      <p class="text-muted text-sm">Page auto-refreshes every 10 seconds. Last updated: <span id="last-updated"><?= date('H:i:s') ?></span></p>
    </div>
  </div>
</div>

<script>
// Auto-refresh results via AJAX every 10 seconds
function fetchResults() {
  fetch('/admin/api_results.php')
    .then(r => r.json())
    .then(data => {
      // Update stats
      document.getElementById('stat-total').textContent   = data.total_votes.toLocaleString();
      document.getElementById('stat-voters').textContent  = data.total_voters.toLocaleString();
      document.getElementById('stat-turnout').textContent = data.turnout + '%';
      document.getElementById('last-updated').textContent = new Date().toLocaleTimeString();

      // Rebuild results
      const container = document.getElementById('results-container');
      if (!data.results || Object.keys(data.results).length === 0) return;
      container.innerHTML = '';
      for (const [seat, candidates] of Object.entries(data.results)) {
        const seatTotal = candidates.reduce((s, c) => s + c.vote_count, 0);
        let html = `<div class="card mb-3">
          <div class="card-header">
            <h3 style="margin:0">${escHtml(seat)}</h3>
            <span class="text-sm text-muted">${seatTotal} vote${seatTotal !== 1 ? 's' : ''}</span>
          </div>
          <div class="card-body">`;
        candidates.forEach((c, i) => {
          const pct = seatTotal > 0 ? (c.vote_count / seatTotal * 100).toFixed(1) : 0;
          const rankClass = i === 0 ? 'rank-1' : i === 1 ? 'rank-2' : i === 2 ? 'rank-3' : '';
          const barColor = i === 0
            ? 'linear-gradient(90deg,var(--gold),var(--gold-light))'
            : 'linear-gradient(90deg,var(--emerald),#2e8b57)';
          const leading = (i === 0 && seatTotal > 0) ? '<span class="badge badge-gold">Leading</span>' : '';
          html += `<div class="${rankClass}" style="margin-bottom:1.1rem">
            <div class="flex-center justify-between mb-1">
              <div>
                <span class="candidate-name" style="font-family:'Playfair Display',serif;font-weight:700">${escHtml(c.full_name)}</span>
                <span class="text-sm text-muted" style="margin-left:.5rem">${escHtml(c.party)}</span>
              </div>
              <div class="flex-center gap-2">
                <strong>${c.vote_count}</strong>
                <span class="text-sm text-muted">(${pct}%)</span>
                ${leading}
              </div>
            </div>
            <div class="progress">
              <div class="progress-bar" style="width:${pct}%;background:${barColor}"></div>
            </div>
          </div>`;
        });
        html += '</div></div>';
        container.innerHTML += html;
      }

      // Update leaders panel
      const leaders = document.getElementById('leaders');
      leaders.innerHTML = '';
      let hasLeaders = false;
      for (const [seat, candidates] of Object.entries(data.results)) {
        if (!candidates.length) continue;
        hasLeaders = true;
        const leader = candidates[0];
        leaders.innerHTML += `<div style="display:flex;align-items:center;justify-content:space-between;padding:.5rem .75rem;background:var(--paper);border-radius:6px">
          <div>
            <div style="font-size:.82rem;font-weight:700">${escHtml(leader.full_name)}</div>
            <div style="font-size:.75rem;color:var(--ink-soft)">${escHtml(seat)}</div>
          </div>
          <span class="badge badge-gold">${leader.vote_count} votes</span>
        </div>`;
      }
      if (!hasLeaders) leaders.innerHTML = '<p class="text-muted text-sm text-center" style="padding:.5rem">No data yet.</p>';

      // Update ticker
      if (data.ticker && data.ticker.length) {
        const ticker = document.getElementById('ticker');
        ticker.innerHTML = '';
        data.ticker.forEach(t => {
          ticker.innerHTML += `<div class="vote-ticker">
            <div class="vote-dot"></div>
            <div>
              <div style="font-size:.82rem;font-weight:600">${escHtml(t.voter)}</div>
              <div style="font-size:.78rem;color:var(--ink-soft)">voted for <strong>${escHtml(t.candidate)}</strong> · ${escHtml(t.seat)}</div>
              <div style="font-size:.72rem;color:var(--ink-soft)">${t.voted_at}</div>
            </div>
          </div>`;
        });
      }
    })
    .catch(err => console.error('Live update error:', err));
}

function escHtml(str) {
  const d = document.createElement('div');
  d.appendChild(document.createTextNode(str));
  return d.innerHTML;
}

setInterval(fetchResults, 10000);
</script>

<?php layout_foot(); ?>
