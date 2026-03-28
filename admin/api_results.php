<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

$db = get_db();

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
    $rows = $stmt->fetchAll();
    // cast vote_count to int
    foreach ($rows as &$r) $r['vote_count'] = (int)$r['vote_count'];
    $results[$seat] = $rows;
}

$total_votes  = (int)$db->query("SELECT COUNT(*) FROM votes")->fetchColumn();
$total_voters = (int)$db->query("SELECT COUNT(*) FROM voters")->fetchColumn();
$voted_count  = (int)$db->query("SELECT COUNT(DISTINCT voter_id) FROM votes")->fetchColumn();
$turnout      = $total_voters > 0 ? round($voted_count / $total_voters * 100, 1) : 0;

$ticker = $db->query("
    SELECT DATE_FORMAT(v.voted_at,'%H:%i:%s') AS voted_at,
           c.full_name AS candidate, c.seat,
           vt.full_name AS voter
    FROM votes v
    JOIN candidates c ON c.id = v.candidate_id
    JOIN voters vt    ON vt.id = v.voter_id
    ORDER BY v.voted_at DESC LIMIT 20
")->fetchAll();

echo json_encode([
    'results'      => $results,
    'total_votes'  => $total_votes,
    'total_voters' => $total_voters,
    'turnout'      => $turnout,
    'ticker'       => $ticker,
]);
