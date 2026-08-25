<?php
/**
 * Sermon Comments API (AJAX)
 *   GET  ?action=list&sermon_id=N        → return JSON {count, html, pending}
 *   POST ?action=add   (sermon_id, parent_id, name, email, location, comment_text, csrf_token)
 *   POST ?action=like  (comment_id)
 * Pure procedural — returns JSON always.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

header('Content-Type: application/json; charset=utf-8');
ensure_sermon_comments_table();

$action = $_REQUEST['action'] ?? 'list';
$member = user_auth_data();

function render_comment_card($c, $member) {
    $id = (int)$c['id'];
    $isMember = !empty($c['user_id']);
    $name = esc($c['author_name']);
    $initials = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 2));
    $loc = $c['author_location'] ? ' · <span>' . esc($c['author_location']) . '</span>' : '';
    $avatar = $isMember && !empty($member['id']) && (int)$member['id'] === (int)$c['user_id']
        ? ($member['avatar_color'] ?? '#8C1D2F')
        : (dechex(crc32($name) % 0xFFFFFF + 0x222222));
    $time = date('M j, Y · g:i A', strtotime($c['created_at']));
    $approved = (int)$c['is_approved'] === 1;
    $likes = (int)$c['likes'];
    $text = nl2br(esc($c['comment_text']));
    $pending = $approved ? '' : '<span class="pending" style="display:inline-block;margin-left:8px;padding:2px 8px;border-radius:99px;background:var(--gold);color:var(--warmdark);font-family:var(--mono);font-size:11px;letter-spacing:.1em;">PENDING REVIEW</span>';
    $out = '
<article class="cmt" data-cid="' . $id . '">
  <div class="cmt-av" style="background:#' . $avatar . ';" aria-hidden="true">' . $initials . '</div>
  <div class="cmt-body">
    <header class="cmt-head">
      <b>' . $name . '</b>' . $loc . '<span class="cmt-time">' . $time . '</span>' . $pending . '
    </header>
    <div class="cmt-text">' . $text . '</div>
    <footer class="cmt-foot">
      <button type="button" class="cmt-like" data-cid="' . $id . '" aria-label="Like comment">
        <span class="h">♡</span><span class="full" hidden>❤️</span><span class="ct">' . ($likes > 0 ? number_format($likes) : '') . '</span>
      </button>
      <button type="button" class="cmt-reply" data-parent="' . $id . '" aria-label="Reply"><i>↩</i> Reply</button>
    </footer>';
    // Recursively render replies if any
    if (!empty($c['replies']) && is_array($c['replies'])) {
        $out .= '<div class="cmt-replies">';
        foreach ($c['replies'] as $r) {
            $out .= render_comment_card($r, $member);
        }
        $out .= '</div>';
    }
    // Reply form hidden, shown when cmt-reply clicked
    $out .= '
    <form class="cmt-reply-form" hidden data-parent="' . $id . '">
      <input type="hidden" name="parent_id" value="' . $id . '">
      <textarea name="comment_text" rows="2" placeholder="Write a reply..." required maxlength="800"></textarea>
      <div class="cmt-rf-actions">
        <button type="button" class="cmt-rf-cancel ghost sm">Cancel</button>
        <button type="submit" class="sm">Post Reply</button>
      </div>
    </form>';
    $out .= '
  </div>
</article>';
    return $out;
}

// ---------- ACTION: LIST ----------
if ($action === 'list') {
    $sermonId = (int)($_GET['sermon_id'] ?? 0);
    if ($sermonId <= 0) {
        echo json_encode(['ok' => false, 'error' => 'sermon_id required']);
        exit;
    }
    $tree = get_sermon_comments_tree($sermonId, false);
    $html = '';
    if ($tree['count'] <= 0) {
        $html = '<div class="comments-empty"><div class="ce-ico">💬</div><h4>No conversations yet</h4><p>Be the first to share what this sermon spoke to you.</p></div>';
    } else {
        foreach ($tree['tree'] as $c) {
            $html .= render_comment_card($c, $member);
        }
    }
    echo json_encode(['ok' => true, 'count' => (int)$tree['count'], 'html' => $html]);
    exit;
}

// ---------- ACTION: ADD ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add') {
    $sermonId = (int)($_POST['sermon_id'] ?? 0);
    $parentId = max(0, (int)($_POST['parent_id'] ?? 0));
    $text = trim($_POST['comment_text'] ?? '');
    if ($sermonId <= 0 || $text === '') {
        echo json_encode(['ok' => false, 'error' => 'Missing sermon_id or comment_text']);
        exit;
    }
    if (strlen($text) > 4000) {
        echo json_encode(['ok' => false, 'error' => 'Comment too long']);
        exit;
    }

    // Name: prefer logged-in user
    $userId = $member ? (int)$member['id'] : null;
    $name = trim($_POST['name'] ?? '');
    if ($name === '' && $member) $name = $member['name'];
    $email = trim($_POST['email'] ?? '');
    if ($email === '' && $member) $email = $member['email'];
    $location = trim($_POST['location'] ?? '');

    $row = add_sermon_comment($sermonId, $parentId, $userId, $name, $email, $location, $text);
    if (!$row) {
        echo json_encode(['ok' => false, 'error' => 'Could not save comment']);
        exit;
    }
    $tree = get_sermon_comments_tree($sermonId, false);
    $html = '';
    if ($tree['count'] <= 0) {
        $html = '<div class="comments-empty"><div class="ce-ico">💬</div><h4>No conversations yet</h4><p>Be the first to share what this sermon spoke to you.</p></div>';
    } else {
        foreach ($tree['tree'] as $c) {
            $html .= render_comment_card($c, $member);
        }
    }
    echo json_encode([
        'ok' => true,
        'count' => (int)$tree['count'],
        'html' => $html,
        'new_approved' => (int)$row['is_approved'] === 1,
    ]);
    exit;
}

// ---------- ACTION: LIKE ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'like') {
    $cid = (int)($_POST['comment_id'] ?? 0);
    if ($cid <= 0) {
        echo json_encode(['ok' => false, 'error' => 'comment_id required']);
        exit;
    }
    $n = like_sermon_comment($cid);
    echo json_encode(['ok' => true, 'likes' => $n]);
    exit;
}

// ---------- FALLBACK ----------
echo json_encode(['ok' => false, 'error' => 'Unknown action']);
