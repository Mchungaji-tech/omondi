<?php
/**
 * Admin Proposal Document Viewer & Commitment Logger
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/includes/admin_header.php';

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

// Handle Admin Commitment Log POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $pName = sanitize_input($_POST['partner_name'] ?? '');
    $pContact = sanitize_input($_POST['partner_contact'] ?? '');
    $pAmount = (float)($_POST['amount_pledged'] ?? 0);
    $pNotes = sanitize_input($_POST['notes'] ?? '');
    $projId = (int)$currentProject['id'];

    if (!empty($pName)) {
        $refNo = generate_ref_no('PROP');
        db_query("INSERT INTO project_commitments (project_id, ref_no, partner_name, partner_contact, amount_pledged, notes, status) VALUES (?, ?, ?, ?, ?, ?, 'confirmed')", "isssds", [$projId, $refNo, $pName, $pContact, $pAmount, $pNotes]);
        log_audit($adminUser['id'], $adminUser['username'], "Logged partner pledge for '$pName' ($refNo)");
        set_flash('success', "Partner commitment $refNo recorded successfully.");
        header('Location: ' . BASE_URL . 'admin/proposal_view.php?id=' . $projId);
        exit;
    }
}
?>

<div class="phead">
  <div>
    <h2>Proposal <span class="ser">Document &amp; Printer</span></h2>
    <p>Official partnership proposals for churches, foundations, and diaspora partners</p>
  </div>
  <div>
    <button onclick="window.print()" class="btn gold">🖨 Print Proposal</button>
  </div>
</div>

<div class="propgrid">
  <!-- Sidebar project list -->
  <div class="propside">
    <h4>Select Proposal</h4>
    <?php foreach ($projects as $proj): ?>
      <a href="proposal_view.php?id=<?= $proj['id'] ?>" class="<?= (int)$proj['id'] === (int)$currentProject['id'] ? 'on' : '' ?>">
        <?= esc($proj['name']) ?>
      </a>
    <?php endforeach; ?>

    <div style="margin-top:24px;padding-top:18px;border-top:1px solid var(--line);">
      <a href="commitments.php" class="btn sm ghost" style="width:100%;text-align:center;">View All Pledges &rarr;</a>
    </div>
  </div>

  <!-- Proposal View -->
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

        <h4>4 · Timeline</h4>
        <ul>
          <li><b>Month 1–2</b> · Partner mobilisation &amp; procurement</li>
          <li><b>Month 3–9</b> · Construction &amp; field execution</li>
          <li><b>Month 10–11</b> · Inspection &amp; commissioning</li>
          <li><b>Month 12</b> · Dedication service &amp; final audited report</li>
        </ul>

        <h4>5 · Accountability &amp; Banking</h4>
        <p>Funds are received only through the church's audited accounts (M-Pesa Paybill <b><?= esc(get_setting('mpesa_paybill')) ?></b> · <b><?= esc(get_setting('bank_name')) ?></b> · SWIFT <b><?= esc(get_setting('swift_code')) ?></b>). No cash to individuals.</p>

        <div class="sigblock">
          <div>
            <b><?= esc(get_setting('pastor_name')) ?></b><br>
            Bishop Morris Omondi · Redeemed Gospel Church Eldoret
          </div>
          <div>
            <b>Elder Board &amp; Finance Secretariat</b><br>
            Redeemed Gospel Church Eldoret
          </div>
        </div>
      </div>

      <!-- Quick Commitment Logger inside Admin -->
      <div class="tblwrap" style="margin-top:26px;padding:26px;">
        <h4 style="margin-bottom:12px;">Log Partner Commitment for this Project</h4>
        <form method="post">
          <?= csrf_field() ?>

          <div class="mrow2">
            <div class="field">
              <label>Partner / Donor Name *</label>
              <input type="text" name="partner_name" placeholder="e.g. Eldoret Business Fellowship" required>
            </div>
            <div class="field">
              <label>Contact Phone / Email</label>
              <input type="text" name="partner_contact" placeholder="+254 7XX XXX XXX">
            </div>
          </div>

          <div class="mrow2">
            <div class="field">
              <label>Pledged Amount (KSh)</label>
              <input type="number" name="amount_pledged" placeholder="100000" step="1000" required>
            </div>
            <div class="field">
              <label>Notes</label>
              <input type="text" name="notes" placeholder="e.g. 4 quarterly installments">
            </div>
          </div>

          <button type="submit" class="btn sm">Record Partner Pledge ✦</button>
        </form>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
