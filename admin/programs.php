<?php
/**
 * Admin Programs & Funds Manager
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/includes/admin_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    // Programs CRUD
    if ($action === 'create_program' || $action === 'update_program') {
        $name = sanitize_input($_POST['name'] ?? '');
        $code = sanitize_input($_POST['account_code'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $goal = sanitize_input($_POST['goal_text'] ?? '');
        if (!empty($name) && !empty($code)) {
            if ($action === 'create_program') {
              db_query("INSERT INTO support_programs (name, account_code, description, goal_text) VALUES (?, ?, ?, ?)", "ssss", [$name, $code, $desc, $goal]);
              log_audit($adminUser['id'], $adminUser['username'], "Added support programme: '$name'");
              set_flash('success', "Programme added.");
            } else {
              $id = (int)$_POST['id'];
              db_query("UPDATE support_programs SET name = ?, account_code = ?, description = ?, goal_text = ? WHERE id = ?", "ssssi", [$name, $code, $desc, $goal, $id]);
              log_audit($adminUser['id'], $adminUser['username'], "Updated support programme: '$name'");
              set_flash('success', "Programme updated.");
            }
        }
    } elseif ($action === 'delete_program') {
        $id = (int)$_POST['id'];
        db_query("DELETE FROM support_programs WHERE id = ?", "i", [$id]);
        set_flash('success', "Programme removed.");
    }

    // Funds CRUD
    elseif ($action === 'create_fund' || $action === 'update_fund') {
        $name = sanitize_input($_POST['name'] ?? '');
        $raised = (float)($_POST['raised_amount'] ?? 0);
        $goal = (float)($_POST['goal_amount'] ?? 0);
        $note = sanitize_input($_POST['note'] ?? '');

        if (!empty($name)) {
            if ($action === 'create_fund') {
                db_query("INSERT INTO fund_goals (name, raised_amount, goal_amount, note) VALUES (?, ?, ?, ?)", "sdds", [$name, $raised, $goal, $note]);
                log_audit($adminUser['id'], $adminUser['username'], "Added fund goal: '$name'");
                set_flash('success', "Fund goal added.");
            } else {
                $id = (int)$_POST['id'];
                db_query("UPDATE fund_goals SET name = ?, raised_amount = ?, goal_amount = ?, note = ? WHERE id = ?", "sddsi", [$name, $raised, $goal, $note, $id]);
                log_audit($adminUser['id'], $adminUser['username'], "Updated fund goal: '$name'");
                set_flash('success', "Fund goal updated.");
            }
        }
    } elseif ($action === 'delete_fund') {
        $id = (int)$_POST['id'];
        db_query("DELETE FROM fund_goals WHERE id = ?", "i", [$id]);
        set_flash('success', "Fund goal deleted.");
    }

    header('Location: ' . BASE_URL . 'admin/programs.php');
    exit;
}

$programs = db_fetch_all("SELECT * FROM support_programs ORDER BY sort_order ASC, id ASC");
$funds = db_fetch_all("SELECT * FROM fund_goals ORDER BY sort_order ASC, id ASC");
$editFund = isset($_GET['edit_fund']) ? db_fetch_one("SELECT * FROM fund_goals WHERE id = ?", "i", [(int)$_GET['edit_fund']]) : null;
$editProgram = isset($_GET['edit_program']) ? db_fetch_one("SELECT * FROM support_programs WHERE id = ?", "i", [(int)$_GET['edit_program']]) : null;
?>

<div class="phead">
  <div>
    <h2>Programs <span class="ser">&amp; Fund Progress</span></h2>
    <p>Manage church giving codes, designated support funds, and public goal progress bars</p>
  </div>
  <div>
    <button type="button" class="btn" onclick="openAdminModal('fundModal')">+ Add Fund Goal</button>
    <button type="button" class="btn gold" onclick="openAdminModal('progModal')">+ Add Support Programme</button>
  </div>
</div>

<!-- SECTION 1: FUND PROGRESS BARS -->
<div class="tblwrap" style="margin-bottom:34px;">
  <div style="padding:18px 22px;border-bottom:1px solid var(--line);">
    <h4 style="font-size:1.15rem;">Capital &amp; Annual Fund Goals</h4>
  </div>
  <table>
    <thead>
      <tr>
        <th>Fund Campaign</th>
        <th>Raised (KSh)</th>
        <th>Goal (KSh)</th>
        <th>Progress</th>
        <th>Note</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($funds as $f): 
        $pct = $f['goal_amount'] > 0 ? min(100, round(($f['raised_amount'] / $f['goal_amount']) * 100)) : 0;
      ?>
        <tr>
          <td><b><?= esc($f['name']) ?></b></td>
          <td class="mono"><?= format_ksh($f['raised_amount']) ?></td>
          <td class="mono"><?= format_ksh($f['goal_amount']) ?></td>
          <td>
            <div style="width:120px;">
              <div style="font-size:12px;font-family:var(--mono);margin-bottom:2px;"><b><?= $pct ?>%</b></div>
              <div class="bar"><i style="width:<?= $pct ?>%"></i></div>
            </div>
          </td>
          <td><span style="font-size:11px;color:var(--ink2);"><?= esc($f['note']) ?></span></td>
          <td>
            <form method="post" style="display:inline;" onsubmit="return confirm('Delete this fund?');">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete_fund">
              <input type="hidden" name="id" value="<?= $f['id'] ?>">
              <button type="submit" class="btn sm danger">Delete</button>
            </form>
            <a href="programs.php?edit_fund=<?= $f['id'] ?>" class="btn sm ghost">Edit</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- SECTION 2: SUPPORT PROGRAMMES -->
<div class="tblwrap">
  <div style="padding:18px 22px;border-bottom:1px solid var(--line);">
    <h4 style="font-size:1.15rem;">Support Programmes &amp; M-Pesa Account Codes</h4>
  </div>
  <table>
    <thead>
      <tr>
        <th>Programme Name</th>
        <th>Account Code</th>
        <th>Goal / Target Label</th>
        <th>Description</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($programs as $prog): ?>
        <tr>
          <td><b><?= esc($prog['name']) ?></b></td>
          <td><span class="chip wine"><?= esc($prog['account_code']) ?></span></td>
          <td><span class="chip gold"><?= esc($prog['goal_text']) ?></span></td>
          <td style="font-size:12px;max-width:320px;"><?= esc($prog['description']) ?></td>
          <td>
            <form method="post" style="display:inline;" onsubmit="return confirm('Delete this programme?');">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete_program">
              <input type="hidden" name="id" value="<?= $prog['id'] ?>">
              <button type="submit" class="btn sm danger">Delete</button>
            </form>
            <a href="programs.php?edit_program=<?= $prog['id'] ?>" class="btn sm ghost">Edit</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php if ($editFund): ?>
<div class="modal" id="editFundModal">
  <div class="mcard-modal">
    <h3>Edit Fund Goal</h3>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update_fund">
      <input type="hidden" name="id" value="<?= $editFund['id'] ?>">
      <div class="field"><label>Fund Name *</label><input type="text" name="name" value="<?= esc($editFund['name']) ?>" required></div>
      <div class="mrow2">
        <div class="field"><label>Raised Amount (KSh)</label><input type="number" name="raised_amount" value="<?= esc($editFund['raised_amount']) ?>" step="1000" required></div>
        <div class="field"><label>Goal Amount (KSh)</label><input type="number" name="goal_amount" value="<?= esc($editFund['goal_amount']) ?>" step="1000" required></div>
      </div>
      <div class="field"><label>Phase / Milestone Note</label><input type="text" name="note" value="<?= esc($editFund['note']) ?>"></div>
      <div style="display:flex;gap:12px;margin-top:20px;"><button type="submit" class="btn" style="flex:1;">Update Fund Goal</button><a href="programs.php" class="btn ghost">Cancel</a></div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php if ($editProgram): ?>
<div class="modal" id="editProgramModal">
  <div class="mcard-modal">
    <h3>Edit Support Programme</h3>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update_program">
      <input type="hidden" name="id" value="<?= $editProgram['id'] ?>">
      <div class="mrow2">
        <div class="field"><label>Programme Name *</label><input type="text" name="name" value="<?= esc($editProgram['name']) ?>" required></div>
        <div class="field"><label>Account Code *</label><input type="text" name="account_code" value="<?= esc($editProgram['account_code']) ?>" required></div>
      </div>
      <div class="field"><label>Goal / Contribution Benchmark</label><input type="text" name="goal_text" value="<?= esc($editProgram['goal_text']) ?>"></div>
      <div class="field"><label>Programme Purpose &amp; Details</label><textarea name="description"><?= esc($editProgram['description']) ?></textarea></div>
      <div style="display:flex;gap:12px;margin-top:20px;"><button type="submit" class="btn" style="flex:1;">Update Programme</button><a href="programs.php" class="btn ghost">Cancel</a></div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- MODAL: ADD FUND -->
<div class="modal" id="fundModal" hidden>
  <div class="mcard-modal">
    <h3>Add Fund Goal</h3>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create_fund">

      <div class="field">
        <label>Fund Name *</label>
        <input type="text" name="name" placeholder="e.g. New 2,000-Seat Auditorium" required>
      </div>

      <div class="mrow2">
        <div class="field">
          <label>Raised Amount (KSh)</label>
          <input type="number" name="raised_amount" placeholder="8200000" step="1000" required>
        </div>
        <div class="field">
          <label>Goal Amount (KSh)</label>
          <input type="number" name="goal_amount" placeholder="12000000" step="1000" required>
        </div>
      </div>

      <div class="field">
        <label>Phase / Milestone Note</label>
        <input type="text" name="note" placeholder="e.g. Phase 2: roofing & pews">
      </div>

      <div style="display:flex;gap:12px;margin-top:20px;">
        <button type="submit" class="btn" style="flex:1;">Save Fund Goal ✦</button>
        <button type="button" class="btn ghost" onclick="closeAdminModal('fundModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL: ADD PROGRAMME -->
<div class="modal" id="progModal" hidden>
  <div class="mcard-modal">
    <h3>Add Support Programme</h3>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create_program">

      <div class="mrow2">
        <div class="field">
          <label>Programme Name *</label>
          <input type="text" name="name" placeholder="e.g. School Fees Bursary" required>
        </div>
        <div class="field">
          <label>Account Code (M-Pesa ACC) *</label>
          <input type="text" name="account_code" placeholder="e.g. SCHOOL" required>
        </div>
      </div>

      <div class="field">
        <label>Goal / Contribution Benchmark</label>
        <input type="text" name="goal_text" placeholder="e.g. KSh 8,000 / TERM">
      </div>

      <div class="field">
        <label>Programme Purpose &amp; Details</label>
        <textarea name="description" placeholder="KSh 8,000 covers one term for one vulnerable student..."></textarea>
      </div>

      <div style="display:flex;gap:12px;margin-top:20px;">
        <button type="submit" class="btn" style="flex:1;">Save Programme ✦</button>
        <button type="button" class="btn ghost" onclick="closeAdminModal('progModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
