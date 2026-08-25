<?php
/**
 * Sermon View Tracking API
 * POST ?sermon_id=N
 * Returns JSON {ok: true, views: N}
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

header('Content-Type: application/json; charset=utf-8');

$sermonId = (int)($_POST['sermon_id'] ?? $_GET['sermon_id'] ?? 0);
if ($sermonId <= 0) {
    echo json_encode(['ok' => false, 'error' => 'sermon_id required']);
    exit;
}

ensure_sermon_views_table();
record_sermon_view($sermonId);
$views = get_sermon_view_count($sermonId);

echo json_encode(['ok' => true, 'views' => $views]);
exit;
