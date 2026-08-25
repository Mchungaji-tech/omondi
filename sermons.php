<?php
/**
 * Dedicated Sermon Library Page — Beacon Gospel Centre
 * Pure Procedural PHP + MySQL — YouTube-style layout
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

ensure_sermon_views_table();

function __thumb_src($sermon) {
    $url = (string)($sermon['audio_url'] ?? '');
    $id = (int)($sermon['id'] ?? 0);
    $title = (string)($sermon['title'] ?? 'sermon-' . $id);
    $seed = preg_replace('/[^a-z0-9]+/i', '-', strtolower($title)) ?: 'sermon-' . $id;

    if (preg_match('#(?:youtube\.com/watch\?(?:[^&]*&)?v=|youtu\.be/|youtube\.com/embed/|m\.youtube\.com/watch\?(?:[^&]*&)?v=|youtube\.com/shorts/)([A-Za-z0-9_-]{6,})#i', $url, $m)) {
        $ytId = $m[1];
        return 'https://i.ytimg.com/vi/' . $ytId . '/maxresdefault.jpg';
    }

    return 'https://picsum.photos/seed/' . rawurlencode($seed) . '/640/360';
}

$search = sanitize_input($_GET['q'] ?? '');
$category = sanitize_input($_GET['cat'] ?? 'all');

$where = "WHERE 1=1";
$params = [];
$types = "";

if ($category !== 'all' && !empty($category)) {
    $where .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}

if (!empty($search)) {
    $where .= " AND (title LIKE ? OR series LIKE ? OR scripture_ref LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

$sermons = db_fetch_all("SELECT * FROM sermons $where ORDER BY sort_order ASC, id DESC", $types, $params);
$radioStation = get_setting('radio_station', 'Voice of the Gospel 94.6 FM');
$sermonCount = count($sermons);

$sermonIds = array_column($sermons, 'id');
$viewCounts = [];
if (!empty($sermonIds)) {
    $ids = implode(',', array_map('intval', $sermonIds));
    $viewRows = db_fetch_all("SELECT sermon_id, COUNT(*) as cnt FROM sermon_views WHERE sermon_id IN ($ids) GROUP BY sermon_id");
    foreach ($viewRows as $vr) {
        $viewCounts[(int)$vr['sermon_id']] = (int)$vr['cnt'];
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
.sermon-grid {
  display: grid;
  grid-template-columns: 1.7fr 380px;
  gap: 28px;
  align-items: start;
}

.big-vid-wrap {
  aspect-ratio: 16/9;
  border-radius: 14px;
  overflow: hidden;
  background: #000;
  box-shadow: 0 20px 50px rgba(23,18,14,0.18);
  position: relative;
}

.big-vid-wrap iframe {
  width: 100%;
  height: 100%;
  border: 0;
  display: block;
}

.big-vid-empty {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 40px;
  background:
    radial-gradient(circle at 20% 20%, rgba(201,155,63,0.18), transparent 50%),
    radial-gradient(circle at 80% 80%, rgba(140,29,47,0.22), transparent 55%),
    linear-gradient(135deg, #0f0c09 0%, #1a1410 50%, #241c16 100%);
  color: var(--paper);
}

.big-vid-empty .bve-big {
  font-family: var(--disp);
  font-size: clamp(2.4rem, 5vw, 4.2rem);
  line-height: 0.95;
  text-transform: uppercase;
  letter-spacing: 0.02em;
  background: linear-gradient(135deg, var(--gold2) 0%, var(--gold) 40%, #d4a74d 60%, var(--wine) 100%);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
  margin-bottom: 14px;
}

.big-vid-empty .bve-sub {
  font-family: var(--ser);
  font-style: italic;
  font-size: 1.05rem;
  color: rgba(247,242,233,0.72);
  max-width: 420px;
  line-height: 1.6;
}

.vmeta-top {
  margin-top: 22px;
}

.vseries-top {
  font-family: var(--mono);
  font-size: 12px;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: var(--wine);
  font-weight: 600;
  display: inline-block;
  margin-bottom: 8px;
}

h2.vtitle {
  font-size: clamp(1.6rem, 3vw, 2.4rem);
  margin-bottom: 14px;
}

.vstat-row {
  display: inline-flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 18px;
}

.vstat-row > span {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-family: var(--mono);
  font-size: 12px;
  padding: 5px 12px;
  border-radius: 99px;
  background: var(--paper2);
  border: 1px solid var(--line);
  color: var(--ink2);
}

.vstat-row .sd b,
.vstat-row .sdur b {
  color: var(--ink);
  font-weight: 600;
}

.chip.vchip {
  font-weight: 600;
}

.chip.vchip.yt { background: rgba(255,0,0,.08); color: #c00; border-color: rgba(255,0,0,.28); }
.chip.vchip.fb { background: rgba(24,119,242,.08); color: #1877f2; border-color: rgba(24,119,242,.28); }
.chip.vchip.vm { background: rgba(26,183,234,.08); color: #1ab7ea; border-color: rgba(26,183,234,.28); }

.vscripture {
  font-style: italic;
  font-size: 1.08rem;
  color: var(--ink2);
  padding: 14px 18px;
  border-left: 3px solid var(--gold);
  background: rgba(201,155,63,0.06);
  border-radius: 0 6px 6px 0;
  margin-bottom: 20px;
}

.vactions {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
}

.vactions button,
.vactions a {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-family: var(--mono);
  font-size: 12px;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  padding: 9px 16px;
  border-radius: 6px;
  border: 1px solid var(--line);
  background: var(--card);
  color: var(--ink);
  cursor: pointer;
  font-weight: 600;
  transition: 0.2s;
  text-decoration: none;
}

.vactions button:hover,
.vactions a:hover {
  border-color: var(--wine);
  color: var(--wine);
  transform: translateY(-1px);
}

.vactions button[data-act="save"]:hover { background: rgba(39,77,61,0.08); border-color: var(--pine); color: var(--pine); }
.vactions a[data-act="copylink"]:hover { background: rgba(201,155,63,0.1); border-color: var(--gold); color: #96701e; }
.vactions button[data-act="amen"]:hover { background: rgba(140,29,47,0.08); border-color: var(--wine); color: var(--wine); }

/* ====== RIGHT COLUMN: QUEUE ====== */
.sermon-queue {
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: 12px;
  padding: 22px 18px 18px;
  position: sticky;
  top: 90px;
  max-height: calc(100vh - 110px);
  overflow-y: auto;
}

.sermon-queue h3 {
  font-size: 1.15rem;
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  margin-bottom: 16px;
  padding-bottom: 14px;
  border-bottom: 1px solid var(--line);
}

.sermon-queue h3 span {
  font-family: var(--mono);
  font-size: 11.5px;
  letter-spacing: 0.12em;
  color: var(--ink2);
  text-transform: uppercase;
  font-weight: normal;
}

.qlist {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.qcard {
  display: grid;
  grid-template-columns: 110px 1fr;
  gap: 12px;
  padding: 8px;
  border-radius: 10px;
  cursor: pointer;
  transition: 0.2s;
  border: 1px solid transparent;
}

.qcard:hover {
  background: var(--paper2);
  border-color: var(--line);
}

.qcard.active {
  background: rgba(140,29,47,0.08);
  border-color: rgba(140,29,47,0.3);
  box-shadow: 0 4px 12px rgba(140,29,47,0.08);
}

.qthumb {
  position: relative;
  aspect-ratio: 16/10;
  border-radius: 6px;
  background-size: cover;
  background-position: center;
  background-color: var(--warmdark);
  overflow: hidden;
  flex-shrink: 0;
}

.qthumb::before {
  content: "";
  position: absolute;
  inset: 0;
  background: linear-gradient(to top, rgba(0,0,0,0.4), transparent 50%);
}

.qthumb .qd {
  position: absolute;
  bottom: 5px;
  right: 6px;
  background: rgba(0,0,0,0.82);
  color: #fff;
  font-family: var(--mono);
  font-size: 11px;
  padding: 2px 6px;
  border-radius: 3px;
  font-weight: 600;
  letter-spacing: 0.05em;
}

.qinfo {
  padding: 12px;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.qser {
  font-family: var(--mono);
  font-size: 11px;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--wine);
  font-weight: 600;
}

.qt {
  font-weight: 600;
  font-size: 0.95rem;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  color: var(--ink);
}

.qref {
  font-style: italic;
  font-size: .95rem;
  color: var(--ink2);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.qm {
  margin-top: 4px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-family: var(--mono);
  font-size: 11.5px;
  color: var(--ink2);
  letter-spacing: 0.04em;
}

.qm .date {
  color: var(--ink2);
}

.qnext-btn {
  margin-top: 18px;
  padding-top: 14px;
  border-top: 1px solid var(--line);
  font-family: var(--mono);
  font-size: 12px;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--ink2);
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  padding-left: 4px;
  transition: 0.2s;
}

.qnext-btn i {
  font-style: normal;
  width: 24px;
  height: 24px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: var(--wine);
  color: var(--paper);
  border-radius: 50%;
  font-size: 11px;
  padding-left: 1px;
}

.qnext-btn:hover {
  color: var(--wine);
}

.qnext-btn:hover i {
  background: var(--gold);
  color: var(--warmdark);
}

/* ====== COMMENTS SECTION ====== */
.section-comments {
  margin-top: 50px;
}

.cm-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 14px;
  margin-bottom: 24px;
}

.cm-head h3 {
  font-size: 1.5rem;
}

.cm-head h3 .cm-count {
  font-family: var(--mono);
  font-size: 11px;
  letter-spacing: 0.14em;
  color: var(--ink2);
  text-transform: uppercase;
  font-weight: normal;
  margin-left: 8px;
}

.cm-sort {
  display: inline-flex;
  gap: 4px;
  background: var(--paper2);
  border: 1px solid var(--line);
  border-radius: 8px;
  padding: 4px;
}

.cm-sort button {
  font-family: var(--mono);
  font-size: 12px;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  padding: 6px 14px;
  background: transparent;
  border: 0;
  border-radius: 5px;
  cursor: pointer;
  color: var(--ink2);
  font-weight: 600;
  transition: 0.2s;
}

.cm-sort button.on {
  background: var(--ink);
  color: var(--gold2);
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.cm-add {
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 28px;
}

.cm-add-head {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  font-size: 0.98rem;
}

.cm-add-head .mini {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--wine);
  color: var(--paper);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-family: var(--disp);
  font-size: 15px;
  letter-spacing: 0.03em;
  border: 1.5px solid var(--gold);
}

.cm-add form .cm-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 12px;
  margin-bottom: 12px;
}

.cm-add form input,
.cm-add form textarea {
  width: 100%;
  background: #fff;
  border: 1px solid var(--line);
  border-radius: 5px;
  padding: 10px 12px;
  font-family: var(--ser);
  font-size: 0.95rem;
  color: var(--ink);
  transition: 0.15s;
}

.cm-add form input:focus,
.cm-add form textarea:focus {
  outline: none;
  border-color: var(--wine);
  box-shadow: 0 0 0 3px rgba(140,29,47,0.1);
}

.cm-add form textarea.full {
  width: 100%;
  min-height: 84px;
  resize: vertical;
  margin-bottom: 12px;
}

.cm-add form .actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
}

.cm-add form .actions .hint {
  font-family: var(--mono);
  font-size: 12px;
  letter-spacing: 0.04em;
  color: var(--ink2);
}

.cm-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.cm-empty {
  background: var(--card);
  border: 1px dashed var(--line);
  border-radius: 10px;
  padding: 40px 20px;
  text-align: center;
  color: var(--ink2);
}

.cm-empty b {
  display: block;
  color: var(--ink);
  font-size: 1.05rem;
  margin-bottom: 6px;
}

@media (max-width: 1024px) {
  .sermon-grid {
    grid-template-columns: 1fr;
  }
  .sermon-queue {
    position: static;
    max-height: none;
  }
}

@media (max-width: 640px) {
  .cm-add form .cm-grid {
    grid-template-columns: 1fr;
  }
  .qcard {
    grid-template-columns: 1fr;
  }
}
</style>

<div class="wrap" style="padding-top:100px;padding-bottom:80px;">
  <div class="shead">
    <p class="idx">The Word of God</p>
    <h1 style="font-size:clamp(2.4rem, 5.5vw, 4rem);">Sermon <span class="ser">Library</span></h1>
    <p style="margin-top:10px;color:var(--ink2);max-width:640px;">Expository preaching from Redeemed Gospel Church Eldoret. Tap a sermon card on the right to watch. Filter by series or search scripture.</p>
  </div>

  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;margin-bottom:28px;">
    <div class="filters">
      <a href="sermons.php?cat=all" class="<?= $category === 'all' ? 'on' : '' ?>">All Sermons</a>
      <a href="sermons.php?cat=grace" class="<?= $category === 'grace' ? 'on' : '' ?>">Grace</a>
      <a href="sermons.php?cat=john" class="<?= $category === 'john' ? 'on' : '' ?>">Gospel of John</a>
      <a href="sermons.php?cat=psalms" class="<?= $category === 'psalms' ? 'on' : '' ?>">Psalms</a>
      <a href="sermons.php?cat=family" class="<?= $category === 'family' ? 'on' : '' ?>">Family Altar</a>
      <a href="sermons.php?cat=prayer" class="<?= $category === 'prayer' ? 'on' : '' ?>">Prayer</a>
    </div>

    <form method="get" action="sermons.php" style="display:flex;gap:8px;">
      <?php if ($category !== 'all'): ?>
        <input type="hidden" name="cat" value="<?= esc($category) ?>">
      <?php endif; ?>
      <input type="text" name="q" value="<?= esc($search) ?>" placeholder="Search scripture, title..." style="padding:8px 12px;border:1px solid var(--line);border-radius:4px;font-family:var(--ser);font-size:1rem;">
      <button type="submit" class="btn sm">Search</button>
    </form>
  </div>

  <?php if (empty($sermons)): ?>
    <div style="text-align:center;padding:60px 20px;background:var(--card);border:1px solid var(--line);border-radius:12px;">
      <p style="font-family:var(--disp);font-size:1.8rem;color:var(--wine);margin-bottom:8px;">No Sermons Found</p>
      <p style="color:var(--ink2);margin-bottom:18px;">No sermons matched your filter criteria. Try another series or clear search.</p>
      <a href="sermons.php" class="btn sm ghost">Reset Filters</a>
    </div>
  <?php else: ?>
    <div class="sermon-grid">
      <!-- ====== LEFT COLUMN: NOW PLAYING ====== -->
      <div class="sermon-now">
        <div class="big-vid-wrap" id="bigVidWrap">
          <div class="big-vid-empty" id="bigVidEmpty">
            <div class="bve-big">Choose a Sermon<br>to Begin</div>
            <div class="bve-sub">Tap any card in the queue to the right and the pulpit will come to you. Video, notes, and community notes load below.</div>
          </div>
        </div>

        <div class="vmeta-top">
          <span class="vseries-top" id="vseriesTop">— Select a sermon from the queue —</span>
          <h2 class="vtitle" id="vtitle">The Word of God Awaits</h2>
          <div class="vstat-row">
            <span class="sd">📅 <b>—</b></span>
            <span class="sdur">⏱ <b>—</b></span>
            <span class="chip vchip" id="curChip">—</span>
            <span>👁 <b id="mainViewCount"><?= number_format($decorViews) ?></b> views</span>
            <span>💬 <b class="cm-count">0</b> conversations</span>
          </div>
          <div class="vscripture" id="vscripture">
            &ldquo;Your word is a lamp to my feet and a light to my path.&rdquo; — Psalm 119:105
          </div>
          <div class="vactions">
            <button data-act="save">💾 Save</button>
            <button data-act="share">🔁 Share</button>
            <a data-act="copylink" href="javascript:;">🔗 Copy link</a>
            <button data-act="amen">🙏 Say Amen</button>
          </div>
        </div>
      </div>

      <aside class="sermon-queue">
        <h3>Up next in queue <span><?= $sermonCount ?> sermons</span></h3>
        <div class="qlist" id="qlist">
          <?php foreach ($sermons as $s): ?>
            <?php
              $url = (string)($s['audio_url'] ?? '');
              $hasYt = !empty($url) && (stripos($url, 'youtube') !== false || stripos($url, 'youtu.be') !== false);
              $hasFb = !empty($url) && (stripos($url, 'facebook') !== false || stripos($url, 'fb.') !== false || stripos($url, 'fb.watch') !== false);
              $hasVm = !empty($url) && stripos($url, 'vimeo') !== false;
              $decorViews = $viewCounts[(int)$s['id']] ?? 0;
            ?>
            <div class="qcard"
                 data-sid="<?= (int)$s['id'] ?>"
                 data-vurl="<?= esc($url) ?>"
                 data-title="<?= esc($s['title']) ?>"
                 data-series="<?= esc($s['series']) ?>"
                 data-date="<?= esc($s['sermon_date']) ?>"
                 data-duration="<?= esc($s['duration']) ?>"
                 data-scripture="<?= esc($s['scripture_ref']) ?>"
                 data-platform="<?= $hasYt ? 'yt' : ($hasFb ? 'fb' : ($hasVm ? 'vm' : 'none')) ?>"
            >
              <div class="qthumb" style="background-image:url('<?= esc(__thumb_src($s)) ?>');">
                <span class="qd"><?= esc($s['duration']) ?></span>
              </div>
              <div class="qinfo">
                <span class="qser"><?= esc($s['series']) ?></span>
                <div class="qt"><?= esc($s['title']) ?></div>
                <div class="qref"><?= esc($s['scripture_ref']) ?></div>
                <div class="qm">
                  <span class="date"><?= esc($s['sermon_date']) ?></span>
                  <span class="views-badge" data-views="<?= (int)$s['id'] ?>">👁 <?= number_format($decorViews) ?></span>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="qnext-btn" id="qnextBtn"><i>▶</i> Play next sermon automatically</div>
      </aside>
    </div>

    <!-- ====== COMMENTS SECTION (below grid) ====== -->
    <section class="section-comments">
      <div class="cm-head">
        <h3>Conversations from the pews <span class="cm-count">…</span></h3>
        <div class="cm-sort">
          <button class="on" data-sort="newest">Newest</button>
          <button data-sort="oldest">Oldest</button>
          <button data-sort="top">Top rated</button>
        </div>
      </div>

      <div class="cm-add">
        <div class="cm-add-head">
          <span class="mini" id="cmMini">✝</span>
          <div>Leave your takeaway · <span style="font-weight:400;color:var(--ink2);">What did this sermon speak to you?</span></div>
        </div>
        <form id="cmForm" method="post" action="api/sermon_comments.php">
          <input type="hidden" name="sermon_id" id="cmSermonId" value="">
          <input type="hidden" name="csrf_token" value="<?= esc(csrf_token()) ?>">
          <div class="cm-grid">
            <div>
              <input type="text" name="author_name" placeholder="Your name" required maxlength="120">
            </div>
            <div>
              <input type="email" name="author_email" placeholder="Email (private, not shown)" maxlength="180">
            </div>
            <div>
              <input type="text" name="author_location" placeholder="Eldoret, Kenya" maxlength="120">
            </div>
          </div>
          <textarea class="full" name="comment_text" rows="3" placeholder="Share your notes, prayer points, or a word of encouragement..." required maxlength="3000"></textarea>
          <div class="actions">
            <span class="hint">Anonymous comments are moderated · logged-in members publish instantly</span>
            <button type="submit" class="btn sm gold">Post Comment</button>
          </div>
        </form>
      </div>

      <div class="cm-list" id="cmList">
        <div class="cm-empty">
          <b>Select a sermon above</b>
          Conversations and community notes for the active sermon will appear here.
        </div>
      </div>
    </section>
  <?php endif; ?>

  <div class="radio">
    <span class="dot"></span>
    <p><b>ON AIR BROADCAST:</b> "Morning with God" — Wednesdays &amp; Sundays, 6:00 AM on <b><?= esc($radioStation) ?></b> &amp; streaming live.</p>
  </div>
</div>

<script>
(function(){
  var qcards = document.querySelectorAll('.qcard');
  var bigWrap = document.getElementById('bigVidWrap');
  var bigEmpty = document.getElementById('bigVidEmpty');
  var vseries = document.getElementById('vseriesTop');
  var vtitle = document.getElementById('vtitle');
  var vscripture = document.getElementById('vscripture');
  var curChip = document.getElementById('curChip');
  var cmSermonId = document.getElementById('cmSermonId');
  var cmList = document.getElementById('cmList');
  var cmCountEls = document.querySelectorAll('.cm-count');
  var sd = document.querySelector('.vstat-row .sd b');
  var sdur = document.querySelector('.vstat-row .sdur b');
  var qnextBtn = document.getElementById('qnextBtn');
  var autoPlayNext = false;
  var viewCache = {};

  function loadViewCount(sid, callback){
    if (viewCache[sid] && callback) { callback(viewCache[sid]); return; }
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/sermon_view.php', true);
    xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
    xhr.onload = function(){
      try {
        var r = JSON.parse(xhr.responseText);
        if (r && r.ok) {
          viewCache[sid] = r.views || 0;
          if (callback) callback(viewCache[sid]);
        }
      } catch(e){}
    };
    xhr.send('sermon_id=' + encodeURIComponent(sid));
  }

  function formatViews(n){
    if (n >= 1000000) return (n/1000000).toFixed(1).replace(/\.0$/,'') + 'M';
    if (n >= 1000) return (n/1000).toFixed(1).replace(/\.0$/,'') + 'K';
    return '' + n;
  }

  function platformLabel(p){
    return {yt:'YouTube', fb:'Facebook', vm:'Vimeo', none:'Unpublished'}[p] || 'Video';
  }

  function embedSrc(raw, autoplay){
    var ap = autoplay ? '1' : '0';
    if (!raw) return '';
    var m = raw.match(/(?:youtube\.com\/watch\?(?:[^&]*&)?v=|youtu\.be\/|youtube\.com\/embed\/|m\.youtube\.com\/watch\?(?:[^&]*&)?v=)([A-Za-z0-9_-]{6,})/i);
    if (m) return 'https://www.youtube.com/embed/' + m[1] + '?autoplay=' + ap + '&mute=' + ap + '&rel=0&modestbranding=1&playsinline=1';
    m = raw.match(/youtube\.com\/shorts\/([A-Za-z0-9_-]{6,})/i);
    if (m) return 'https://www.youtube.com/embed/' + m[1] + '?autoplay=' + ap + '&mute=' + ap + '&rel=0&playsinline=1';
    m = raw.match(/facebook\.com\/watch\/?\?v=(\d+)/i);
    if (m) return 'https://www.facebook.com/plugins/video.php?href=' + encodeURIComponent('https://www.facebook.com/watch/?v=' + m[1]) + '&show_text=0&width=1200&autoplay=' + ap;
    m = raw.match(/facebook\.com\/[^/\s]+\/videos\/(\d+)/i);
    if (m) return 'https://www.facebook.com/plugins/video.php?href=' + encodeURIComponent(raw) + '&show_text=0&width=1200&autoplay=' + ap;
    m = raw.match(/vimeo\.com\/(?:video\/)?(\d{5,})/i);
    if (m) return 'https://player.vimeo.com/video/' + m[1] + '?autoplay=' + ap + '&title=0&byline=0';
    if (raw.indexOf('fb.watch') !== -1) return raw;
    return raw;
  }

  function activateCard(card, autoplay){
    if (!card) return;
    qcards.forEach(function(c){ c.classList.remove('active'); });
    card.classList.add('active');
    var sid = card.getAttribute('data-sid');
    var vurl = card.getAttribute('data-vurl');
    var title = card.getAttribute('data-title');
    var series = card.getAttribute('data-series');
    var date = card.getAttribute('data-date');
    var dur = card.getAttribute('data-duration');
    var scripture = card.getAttribute('data-scripture');
    var plat = card.getAttribute('data-platform');

    var src = embedSrc(vurl, !!autoplay);
    if (bigEmpty) bigEmpty.remove();
    bigWrap.querySelectorAll('iframe').forEach(function(f){ f.remove(); });
    if (src) {
      var ifr = document.createElement('iframe');
      ifr.src = src;
      ifr.title = 'Sermon video player';
      ifr.style.cssText = 'width:100%;height:100%;border:0;display:block;';
      ifr.setAttribute('allow','accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share');
      ifr.setAttribute('allowfullscreen','');
      ifr.setAttribute('referrerpolicy','strict-origin-when-cross-origin');
      bigWrap.appendChild(ifr);
    } else {
      var ph = document.createElement('div');
      ph.className = 'big-vid-empty';
      ph.innerHTML = '<div class="bve-big">Video Coming<br>Soon</div><div class="bve-sub">This sermon was preached recently. Video upload and publishing is in progress. Check back soon or tune in on FM radio.</div>';
      bigWrap.appendChild(ph);
    }

    if (vseries) vseries.textContent = series || '—';
    if (vtitle) vtitle.textContent = title || 'Untitled Sermon';
    if (sd) sd.textContent = date || '—';
    if (sdur) sdur.textContent = dur || '—';
    if (vscripture) vscripture.textContent = scripture || '—';
    if (curChip) {
      curChip.textContent = platformLabel(plat);
      curChip.className = 'chip vchip ' + (plat === 'yt' || plat === 'fb' || plat === 'vm' ? plat : '');
    }
    if (cmSermonId) cmSermonId.value = sid;
    loadComments(sid);
    loadViewCount(sid, function(v){
      var mainViews = document.getElementById('mainViewCount');
      if (mainViews) mainViews.textContent = formatViews(v);
    });
    var qv = document.querySelector('.qcard[data-sid="' + sid + '"] .qm .views-badge');
    if (qv && viewCache[sid]) qv.textContent = formatViews(viewCache[sid]);
  }

  qcards.forEach(function(card){
    card.addEventListener('click', function(){ activateCard(card, true); });
  });

  if (qnextBtn) {
    qnextBtn.addEventListener('click', function(){
      autoPlayNext = !autoPlayNext;
      qnextBtn.querySelector('i').style.background = autoPlayNext ? 'var(--pine)' : 'var(--wine)';
      qnextBtn.innerHTML = (autoPlayNext ? '<i>✓</i> Auto-play next sermon ON' : '<i>▶</i> Play next sermon automatically');
    });
  }

  function loadComments(sid){
    if (!cmList) return;
    cmList.innerHTML = '<div class="cm-empty"><b>Loading conversations…</b></div>';
    cmCountEls.forEach(function(el){ el.textContent = '…'; });
    var req = new XMLHttpRequest();
    req.open('GET', 'api/sermon_comments.php?sermon_id=' + encodeURIComponent(sid), true);
    req.onload = function(){
      try {
        var res = JSON.parse(req.responseText);
        renderComments(res || {});
      } catch(e){
        cmList.innerHTML = '<div class="cm-empty"><b>Could not load notes</b>Please try again in a moment.</div>';
      }
    };
    req.onerror = function(){
      cmList.innerHTML = '<div class="cm-empty"><b>Offline</b>Conversations will load when you have a connection.</div>';
    };
    req.send();
  }

  function renderComments(res){
    var count = res.count || 0;
    var tree = res.tree || [];
    cmCountEls.forEach(function(el){ el.textContent = count ? count + ' notes' : 'Be the first'; });
    if (!tree.length) {
      cmList.innerHTML = '<div class="cm-empty"><b>No notes yet</b>Be the first to share a takeaway from this sermon. Your note encourages the whole church.</div>';
      return;
    }
    var html = '';
    (function walk(nodes){
      nodes.forEach(function(c){
        var initials = ((c.author_name || '✝').match(/\S+/g) || []).slice(0,2).map(function(w){ return w[0] || ''; }).join('').toUpperCase() || '✝';
        html += '<div style="background:var(--card);border:1px solid var(--line);border-radius:10px;padding:16px 18px;">' +
          '<div style="display:flex;gap:12px;align-items:flex-start;">' +
            '<div class="avatar-circle" style="width:36px;height:36px;font-size:12px;flex-shrink:0;">' + (initials.length > 2 ? '✝' : initials) + '</div>' +
            '<div style="flex:1;min-width:0;">' +
              '<div style="display:flex;align-items:baseline;gap:8px;flex-wrap:wrap;margin-bottom:4px;">' +
                '<b style="font-family:var(--disp);font-size:1rem;letter-spacing:0.02em;text-transform:uppercase;">' + escapeHtml(c.author_name || 'Anonymous') + '</b>' +
                 '<span style="font-family:var(--mono);font-size:11.5px;color:var(--ink2);letter-spacing:0.06em;">' + escapeHtml(c.author_location || '') + '</span>' +
                 '<span style="font-family:var(--mono);font-size:11px;color:var(--ink2);">' + formatDate(c.created_at) + '</span>' +
              '</div>' +
               '<div style="font-size:1rem;line-height:1.65;color:var(--ink);white-space:pre-wrap;">' + escapeHtml(c.comment_text || '') + '</div>' +
               '<div style="margin-top:8px;display:flex;gap:14px;font-family:var(--mono);font-size:12px;color:var(--ink2);">' +
                '<button style="background:none;border:0;cursor:pointer;color:var(--wine);font-weight:600;" data-like="' + c.id + '">🙏 Amen (<span>' + (c.likes || 0) + '</span>)</button>' +
                '<button style="background:none;border:0;cursor:pointer;" data-reply="' + c.id + '">↩ Reply</button>' +
              '</div>' +
              ((c.replies && c.replies.length) ? '<div style="margin-top:14px;padding-left:18px;border-left:2px solid var(--paper2);display:flex;flex-direction:column;gap:12px;">' + walkReplies(c.replies) + '</div>' : '') +
            '</div>' +
          '</div>' +
        '</div>';
      });
    })(tree);
    cmList.innerHTML = html;
    bindClicks();
  }

  function walkReplies(nodes){
    var out = '';
    nodes.forEach(function(c){
      var initials = ((c.author_name || '✝').match(/\S+/g) || []).slice(0,2).map(function(w){ return w[0] || ''; }).join('').toUpperCase() || '✝';
      out += '<div style="background:var(--paper);border:1px solid var(--line);border-radius:8px;padding:12px 14px;">' +
        '<div style="display:flex;gap:10px;align-items:flex-start;">' +
          '<div class="avatar-circle" style="width:28px;height:28px;font-size:12px;flex-shrink:0;">' + (initials.length > 2 ? '✝' : initials) + '</div>' +
          '<div style="flex:1;min-width:0;">' +
            '<div style="display:flex;align-items:baseline;gap:8px;flex-wrap:wrap;margin-bottom:3px;">' +
              '<b style="font-family:var(--disp);font-size:1rem;letter-spacing:0.02em;text-transform:uppercase;">' + escapeHtml(c.author_name || 'Anonymous') + '</b>' +
              '<span style="font-family:var(--mono);font-size:11px;color:var(--ink2);">' + formatDate(c.created_at) + '</span>' +
            '</div>' +
            '<div style="font-size:1rem;line-height:1.6;white-space:pre-wrap;">' + escapeHtml(c.comment_text || '') + '</div>' +
          '</div>' +
        '</div>' +
      '</div>';
      if (c.replies && c.replies.length) out += walkReplies(c.replies);
    });
    return out;
  }

  function escapeHtml(s){
    return String(s||'').replace(/[&<>"']/g, function(m){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]; });
  }

  function formatDate(s){
    if (!s) return '';
    var d = new Date(s);
    if (isNaN(d.getTime())) return s;
    var diff = (Date.now() - d.getTime()) / 1000;
    if (diff < 60) return 'just now';
    if (diff < 3600) return Math.floor(diff/60) + 'm ago';
    if (diff < 86400) return Math.floor(diff/3600) + 'h ago';
    if (diff < 7*86400) return Math.floor(diff/86400) + 'd ago';
    return d.toLocaleDateString();
  }

  function bindClicks(){
    cmList.querySelectorAll('[data-like]').forEach(function(btn){
      btn.addEventListener('click', function(){
        var id = btn.getAttribute('data-like');
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'api/sermon_comments.php', true);
        xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
        xhr.onload = function(){
          try { var r = JSON.parse(xhr.responseText); btn.querySelector('span').textContent = r.likes || 0; } catch(e){}
        };
        xhr.send('action=like&comment_id=' + encodeURIComponent(id) + '&csrf_token=' + encodeURIComponent(document.querySelector('input[name="csrf_token"]').value));
      });
    });
  }

  var cmForm = document.getElementById('cmForm');
  if (cmForm) {
    cmForm.addEventListener('submit', function(e){
      e.preventDefault();
      var sid = cmSermonId && cmSermonId.value;
      if (!sid) { alert('Please select a sermon first.'); return; }
      var fd = new FormData(cmForm);
      var xhr = new XMLHttpRequest();
      xhr.open('POST', 'api/sermon_comments.php', true);
      xhr.onload = function(){
        try {
          var r = JSON.parse(xhr.responseText);
          if (r && r.ok) {
            cmForm.querySelector('textarea').value = '';
            loadComments(sid);
          } else {
            alert((r && r.error) || 'Could not post comment.');
          }
        } catch(e){ alert('Could not post. Please try again.'); }
      };
      xhr.send(fd);
    });
  }

  var vactions = document.querySelector('.vactions');
  if (vactions) {
    vactions.addEventListener('click', function(e){
      var btn = e.target.closest('[data-act]');
      if (!btn) return;
      var act = btn.getAttribute('data-act');
      var active = document.querySelector('.qcard.active');
      var title = active ? active.getAttribute('data-title') : '';
      var link = active ? (location.origin + location.pathname + '?s=' + encodeURIComponent(active.getAttribute('data-sid'))) : location.href;
      if (act === 'save') { btn.textContent = '✅ Saved'; setTimeout(function(){ btn.textContent = '💾 Save'; }, 1800); }
      else if (act === 'amen') { btn.textContent = '🙏 Amen! +1'; setTimeout(function(){ btn.textContent = '🙏 Say Amen'; }, 1800); }
      else if (act === 'share' || act === 'copylink') {
        if (navigator.share && act === 'share') {
          navigator.share({ title: title + ' — Redeemed Gospel Church Eldoret', text: title, url: link }).catch(function(){});
        } else {
          try {
            navigator.clipboard && navigator.clipboard.writeText(link);
            btn.textContent = '✅ Link copied';
            setTimeout(function(){ btn.textContent = act === 'share' ? '🔁 Share' : '🔗 Copy link'; }, 1800);
          } catch(err){}
        }
      }
    });
  }

  var cmMini = document.getElementById('cmMini');
  var memberInitials = '<?= $member ? esc($member['initials']) : '' ?>';
  if (cmMini && memberInitials) cmMini.textContent = memberInitials;

  if (qcards.length) {
    var firstWithVid = Array.prototype.find.call(qcards, function(c){ return c.getAttribute('data-platform') !== 'none'; }) || qcards[0];
    activateCard(firstWithVid, false);
  }
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
