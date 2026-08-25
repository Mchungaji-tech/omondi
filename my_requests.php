<?php
/**
 * Public Member Activity & Requests Portal
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

// Require member login
if (!user_auth_check()) {
    set_flash('info', 'Please sign in to view your activity and requests.');
    header('Location: ' . BASE_URL . 'login.php?redirect=my_requests.php');
    exit;
}

$currentUser = user_auth_data();
$userId = (int)$currentUser['id'];

// Fetch member's prayer requests
$myPrayers = db_fetch_all("SELECT * FROM prayer_requests WHERE user_id = ? OR (sender_name = ? AND is_anonymous = 0) ORDER BY id DESC", "is", [$userId, $currentUser['name']]);

// Fetch member's preaching invitations
$myInvites = db_fetch_all("SELECT * FROM invitations WHERE user_id = ? OR contact_name = ? ORDER BY id DESC", "is", [$userId, $currentUser['name']]);

// Fetch member's pledges
$myPledges = db_fetch_all("
    SELECT c.*, p.name as project_name 
    FROM project_commitments c 
    LEFT JOIN projects p ON c.project_id = p.id 
    WHERE c.user_id = ? OR c.partner_name = ?
    ORDER BY c.id DESC
", "is", [$userId, $currentUser['name']]);

require_once __DIR__ . '/includes/header.php';
?>

<div class="wrap" style="padding-top:100px;padding-bottom:80px;">
  <!-- USER BANNER -->
  <div class="card" style="margin-bottom:30px;display:flex;align-items:center;gap:24px;flex-wrap:wrap;border-left:5px solid var(--wine);">
    <div style="width:68px;height:68px;border-radius:50%;background:var(--wine);color:var(--paper);display:flex;align-items:center;justify-content:center;font-family:var(--disp);font-size:1.8rem;letter-spacing:.05em;border:2px solid var(--gold);">
      <?= esc($currentUser['initials']) ?>
    </div>
    <div style="flex:1;">
      <h2 style="font-size:1.6rem;"><?= esc($currentUser['name']) ?></h2>
      <p style="font-family:var(--mono);font-size:11px;color:var(--ink2);margin-top:2px;">
        <?= esc($currentUser['email']) ?> · Location: <?= esc($currentUser['location'] ?: 'Kenya') ?>
      </p>
    </div>
    <div style="display:flex;gap:10px;">
      <a href="<?= BASE_URL ?>prayer.php" class="btn sm">+ Request Prayer</a>
      <a href="<?= BASE_URL ?>invite.php" class="btn sm gold">Invite Pastor</a>
      <a href="<?= BASE_URL ?>logout.php" class="btn sm ghost">Sign Out</a>
    </div>
  </div>

  <!-- 3 COLUMN STATS / TABS -->
  <div class="cards4" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 30px;">
    <div class="statcard" style="border-top-color:var(--wine);">
      <b><?= count($myPrayers) ?></b>
      <small>Prayer Requests Submitted</small>
    </div>
    <div class="statcard" style="border-top-color:var(--gold);">
      <b><?= count($myInvites) ?></b>
      <small>Preaching Invitations</small>
    </div>
    <div class="statcard" style="border-top-color:var(--pine);">
      <b><?= count($myPledges) ?></b>
      <small>Project Pledges / Pledges</small>
    </div>
  </div>

  <!-- SECTION 1: MY PRAYER REQUESTS -->
  <div class="tblwrap" style="margin-bottom:34px;">
    <div style="padding:18px 22px;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;align-items:center;">
      <h3 style="font-size:1.2rem;">My Prayer Requests</h3>
      <a href="<?= BASE_URL ?>prayer.php" class="btn sm">+ New Prayer Request</a>
    </div>
    <table>
      <thead>
        <tr>
          <th>Ref No.</th>
          <th>Category</th>
          <th>Request Details</th>
          <th>Status</th>
          <th>Submitted On</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($myPrayers)): ?>
          <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--ink2);">You haven't submitted any prayer requests yet.</td></tr>
        <?php else: ?>
          <?php foreach ($myPrayers as $p): ?>
            <tr>
              <td><span class="chip wine"><?= esc($p['ref_no']) ?></span></td>
              <td><span class="chip"><?= esc($p['category']) ?></span></td>
              <td style="max-width:360px;"><?= nl2br(esc($p['request_text'])) ?></td>
              <td>
                <span class="chip <?= $p['status'] === 'prayed' ? 'pine' : ($p['status'] === 'new' ? 'gold' : '') ?>">
                  <?= $p['status'] === 'prayed' ? 'Prayed 🙏' : esc($p['status']) ?>
                </span>
              </td>
              <td class="mono" style="font-size:11px;"><?= date('M d, Y', strtotime($p['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- SECTION 2: MY PREACHING INVITATIONS -->
  <div class="tblwrap" style="margin-bottom:34px;">
    <div style="padding:18px 22px;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;align-items:center;">
      <h3 style="font-size:1.2rem;">My Preaching Invitations</h3>
      <a href="<?= BASE_URL ?>invite.php" class="btn sm gold">+ Invite the Pastor</a>
    </div>
    <table>
      <thead>
        <tr>
          <th>Ref No.</th>
          <th>Church / Org</th>
          <th>Service Type</th>
          <th>Date</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($myInvites)): ?>
          <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--ink2);">No preaching invitations submitted yet.</td></tr>
        <?php else: ?>
          <?php foreach ($myInvites as $inv): ?>
            <tr>
              <td><span class="chip wine"><?= esc($inv['ref_no']) ?></span></td>
              <td><b><?= esc($inv['church_org']) ?></b> (<?= esc($inv['town_county']) ?>)</td>
              <td><span class="chip"><?= esc($inv['service_type']) ?></span></td>
              <td class="mono" style="font-size:11px;"><?= esc($inv['preferred_date']) ?></td>
              <td>
                <span class="chip <?= $inv['status'] === 'confirmed' ? 'pine' : ($inv['status'] === 'new' ? 'gold' : '') ?>">
                  <?= esc($inv['status']) ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- SECTION 3: MY PROJECT PLEDGES -->
  <div class="tblwrap">
    <div style="padding:18px 22px;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;align-items:center;">
      <h3 style="font-size:1.2rem;">My Partnership Pledges</h3>
      <a href="<?= BASE_URL ?>proposals.php" class="btn sm ghost">Explore Proposals &rarr;</a>
    </div>
    <table>
      <thead>
        <tr>
          <th>Pledge Ref</th>
          <th>Project</th>
          <th>Amount Pledged</th>
          <th>Notes</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($myPledges)): ?>
          <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--ink2);">No partnership pledges submitted yet.</td></tr>
        <?php else: ?>
          <?php foreach ($myPledges as $pl): ?>
            <tr>
              <td><span class="chip wine"><?= esc($pl['ref_no']) ?></span></td>
              <td><b><?= esc($pl['project_name'] ?: 'Capital Fund') ?></b></td>
              <td class="mono" style="font-weight:600;color:var(--wine);"><?= format_ksh($pl['amount_pledged']) ?></td>
              <td style="font-size:12px;"><?= esc($pl['notes']) ?></td>
              <td>
                <span class="chip <?= $pl['status'] === 'received' ? 'pine' : 'gold' ?>">
                  <?= esc($pl['status']) ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
