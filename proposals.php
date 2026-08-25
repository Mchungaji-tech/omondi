<?php
/**
 * Public Proposals & Capital Projects Page
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

ensure_project_images_table();

$projects = db_fetch_all("SELECT * FROM projects ORDER BY sort_order ASC, id ASC");
$selectedId = isset($_GET['id']) ? (int)$_GET['id'] : (!empty($projects) ? (int)$projects[0]['id'] : 0);

$currentProject = null;
foreach ($projects as $p) {
    if ((int)$p['id'] === $selectedId) {
        $currentProject = $p;
        break;
    }
}
if (!$currentProject && !empty($projects)) {
    $currentProject = $projects[0];
}

// Parse budget rows
$budgetRows = [];
$totalBudget = 0;
$projectImages = [];
if ($currentProject && !empty($currentProject['budget_text'])) {
    $lines = explode("\n", trim($currentProject['budget_text']));
    foreach ($lines as $line) {
        $parts = explode("|", trim($line));
        if (!empty($parts[0])) {
            $cost = isset($parts[1]) ? (float)$parts[1] : 0;
            $budgetRows[] = ['item' => trim($parts[0]), 'cost' => $cost];
            $totalBudget += $cost;
        }
    }
}
if ($currentProject) {
  $projectImages = db_fetch_all("SELECT image_url FROM project_images WHERE project_id = ? ORDER BY sort_order ASC, id ASC", "i", [$currentProject['id']]);
}

// Member prefill
$member = user_auth_data();

// Handle partner commitment POST
$commitmentSuccess = false;
$commitmentRef = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['partner_name'])) {
    csrf_verify();

    $pName = sanitize_input($_POST['partner_name'] ?? '');
    $pContact = sanitize_input($_POST['partner_contact'] ?? '');
    $pAmount = (float)($_POST['amount_pledged'] ?? 0);
    $pNotes = sanitize_input($_POST['notes'] ?? '');
    $projId = (int)($_POST['project_id'] ?? ($currentProject['id'] ?? 0));
    $userId = $member ? (int)$member['id'] : null;

    if (!empty($pName) && !empty($pContact)) {
        $refNo = generate_ref_no('PROP');
        $sql = "INSERT INTO project_commitments (user_id, project_id, ref_no, partner_name, partner_contact, amount_pledged, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
        if (db_query($sql, "iisssds", [$userId, $projId, $refNo, $pName, $pContact, $pAmount, $pNotes])) {
            $commitmentSuccess = true;
            $commitmentRef = $refNo;
            log_audit($userId, $pName, "New project commitment pledged ($refNo)");
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="wrap" style="padding-top:100px;padding-bottom:80px;">
  <div class="shead" style="position:relative;">
    <p class="idx">Kingdom Partnerships</p>
    <h1 style="font-size:clamp(2.4rem, 5.5vw, 4rem);">Capital Projects &amp; <span class="ser">Proposals</span></h1>
    <p style="margin-top:10px;color:var(--ink2);max-width:650px;">Line-item budgets, milestones, and quarterly audited accountability framework for Redeemed Gospel Church Eldoret projects.</p>
    <button type="button" class="currency-toggle proposal-currency-toggle" data-currency-toggle aria-label="Switch between Kenyan shillings and US dollars">KSh / USD</button>
  </div>

  <div class="propgrid">
    <!-- Sidebar project list -->
    <div class="propside">
      <h4>Active Capital Projects</h4>
      <?php foreach ($projects as $proj): ?>
        <a href="proposals.php?id=<?= $proj['id'] ?>" class="<?= (int)$proj['id'] === (int)$currentProject['id'] ? 'on' : '' ?>">
          <?= esc($proj['name']) ?>
          <span class="chip <?= $proj['status'] === 'ongoing' ? 'wine' : ($proj['status'] === 'completed' ? 'pine' : 'gold') ?>" style="float:right;font-size:11px;padding:2px 6px;">
            <?= esc($proj['status']) ?>
          </span>
        </a>
      <?php endforeach; ?>

      <div style="margin-top:24px;padding-top:18px;border-top:1px solid var(--line);font-family:var(--mono);font-size:12px;color:var(--ink2);line-height:1.8;">
        <b>Financial Integrity:</b><br>
        All funds received via audited accounts. Quarterly photographic and financial reports sent to every co-labourer.
      </div>
    </div>

    <!-- Main Proposal Document -->
    <div>
      <?php if ($currentProject): 
        $pct = $currentProject['goal_amount'] > 0 ? min(100, round(($currentProject['raised_amount'] / $currentProject['goal_amount']) * 100)) : 0;
      ?>
        <div class="pdoc">
          <div class="dh">
            <span>Redeemed Gospel Church Eldoret</span>
            <span>Ref BGC/PROP/<?= date('Y') ?>/<?= str_pad($currentProject['id'], 2, '0', STR_PAD_LEFT) ?></span>
            <span><?= date('d M Y') ?></span>
          </div>

          <h2><?= esc($currentProject['name']) ?></h2>
          <div class="meta">
            PARTNERSHIP PROPOSAL · STATUS: <?= strtoupper($currentProject['status']) ?> · RAISED <?= format_ksh($currentProject['raised_amount']) ?> OF <?= format_ksh($currentProject['goal_amount']) ?> (<?= $pct ?>%)
          </div>

          <div style="margin:20px 0 30px;border-radius:6px;overflow:hidden;border:1px solid var(--line);max-height:360px;">
            <img src="<?= esc(img_src($currentProject['image_url'])) ?>" alt="<?= esc($currentProject['name']) ?>" style="width:100%;height:100%;object-fit:cover;">
          </div>
          <?php if (!empty($projectImages)): ?><div class="project-gallery" aria-label="Project photos">
            <?php foreach ($projectImages as $image): ?><img src="<?= esc(img_src($image['image_url'])) ?>" alt="<?= esc($currentProject['name']) ?> project photo"><?php endforeach; ?>
          </div><?php endif; ?>

          <h4>1 · Executive Summary</h4>
          <p><?= nl2br(esc($currentProject['summary'])) ?> This proposal invites partners — individuals, churches, businesses, and the diaspora — to co-labour with Redeemed Gospel Church Eldoret in completing this work, with full quarterly reporting and an annual independent audit.</p>

          <h4>2 · Objectives</h4>
          <ul>
            <li>Complete the defined scope within 12 months of full funding.</li>
            <li>Serve the most vulnerable first, vetted by the elders' committee.</li>
            <li>Report to every partner quarterly with site photos, receipts, and audited figures.</li>
            <li>Glory to God alone: every commemorative plaque reads “Soli Deo Gloria”.</li>
          </ul>

          <h4>3 · Budget Breakdown</h4>
          <table class="bud">
            <thead>
              <tr>
                <th>Line Item</th>
                <th style="text-align:right">Estimated Cost (KSh)</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($budgetRows as $row): ?>
                <tr>
                  <td><?= esc($row['item']) ?></td>
                  <td style="text-align:right"><?= format_ksh($row['cost']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr style="font-weight:bold;background:var(--paper2);">
                <td>TOTAL ESTIMATE</td>
                <td style="text-align:right"><?= format_ksh($totalBudget) ?></td>
              </tr>
            </tfoot>
          </table>

          <h4>4 · Timeline &amp; Milestones</h4>
          <ul>
            <li><b>Month 1–2</b> · Partner mobilisation, procurement &amp; site onboarding</li>
            <li><b>Month 3–9</b> · Construction &amp; field execution with monthly site reports</li>
            <li><b>Month 10–11</b> · Inspection, quality audit &amp; commissioning</li>
            <li><b>Month 12</b> · Dedication service &amp; final audited distribution report</li>
          </ul>

          <h4>5 · Financial Accountability &amp; Banking</h4>
          <p>Contributions are received strictly through the church's official accounts. Queries: <b><?= esc(get_setting('email')) ?></b>.</p>
          <div style="background:var(--paper);padding:14px 18px;border-radius:4px;font-family:var(--mono);font-size:11px;margin:12px 0 20px;border-left:3px solid var(--wine);">
            • <b>M-Pesa Paybill:</b> <?= esc(get_setting('mpesa_paybill')) ?> · Account: <?= esc($currentProject['name']) ?><br>
            • <b>Bank:</b> <?= esc(get_setting('bank_name')) ?> · Account: <?= esc(get_setting('bank_account')) ?><br>
            • <b>SWIFT Code:</b> <?= esc(get_setting('swift_code')) ?>
          </div>

          <div class="sigblock">
            <div>
              <b><?= esc(get_setting('pastor_name')) ?></b><br>
              Bishop Morris Omondi · Redeemed Gospel Church Eldoret
            </div>
            <div>
              <b>Elder Board &amp; Projects Committee</b><br>
              Redeemed Gospel Church Eldoret
            </div>
          </div>
        </div>

        <!-- Partner Commitment Form -->
        <div class="card" style="margin-top:30px;">
          <h3>Co-Labour with Us on this Project</h3>
          <p style="font-size:.95rem;color:var(--ink2);margin:6px 0 20px;">Register your funding commitment or pledge. Our finance secretariat will connect with you directly.</p>

          <?php if ($commitmentSuccess): ?>
            <div class="card success show" style="display:block;">
              <svg viewBox="0 0 80 80"><circle cx="40" cy="40" r="36"/><path d="M24 41 L36 53 L58 29"/></svg>
              <h3>Thank You for Standing with Beacon!</h3>
              <p class="ref">PLEDGE REFERENCE: <?= esc($commitmentRef) ?></p>
              <p>Your partnership commitment has been recorded in the registry. A confirmation email and bank wire instruction packet will be dispatched to you.</p>
              <?php if ($member): ?>
                <a href="<?= BASE_URL ?>my_requests.php" class="btn sm gold" style="margin-top:14px;">View In My Pledges &rarr;</a>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <form method="post" action="proposals.php?id=<?= $currentProject['id'] ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="project_id" value="<?= $currentProject['id'] ?>">
              
              <div class="f2">
                <div class="field">
                  <label>Your Name / Organization *</label>
                  <input type="text" name="partner_name" value="<?= esc($member['name'] ?? '') ?>" placeholder="e.g. Deacon David Koech" required>
                </div>
                <div class="field">
                  <label>Email or Phone Number *</label>
                  <input type="text" name="partner_contact" value="<?= esc($member['email'] ?? '') ?>" placeholder="+254 7XX XXX XXX" required>
                </div>
              </div>

              <div class="f2">
                <div class="field">
                  <label>Pledged Amount (KSh or Approx)</label>
                  <input type="number" name="amount_pledged" placeholder="50000" step="500">
                </div>
                <div class="field">
                  <label>Project</label>
                  <input type="text" value="<?= esc($currentProject['name']) ?>" disabled>
                </div>
              </div>

              <div class="field">
                <label>Partner Notes / Preferred Follow-up Time</label>
                <textarea name="notes" placeholder="Notes, installments timeline, or prayer dedication..."></textarea>
              </div>

              <button type="submit" class="btn" style="width:100%">Submit Partnership Commitment ✦</button>
            </form>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
