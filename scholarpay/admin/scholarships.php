<?php
define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/layout.php';
requireAdmin();

$db = getDB();
$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';
$success = $error = '';

// Handle Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'create') {
    $name      = trim($_POST['name'] ?? '');
    $desc      = trim($_POST['description'] ?? '');
    $amount    = floatval($_POST['total_amount'] ?? 0);
    $tokenAddr = trim($_POST['token_address'] ?? '');

    if (empty($name))    { $error = 'Scholarship name is required.'; }
    elseif ($amount <= 0){ $error = 'Total amount must be greater than 0.'; }
    else {
        $rawAmount = intval($amount * 10000000);
        $stmt = $db->prepare("
            INSERT INTO scholarships (name, description, total_amount, remaining_amount, token_address, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $desc, $rawAmount, $rawAmount, $tokenAddr ?: null, $userId]);
        $newId = $db->lastInsertId();
        logActivity($userId, 'CREATE_SCHOLARSHIP', "Created scholarship: {$name} (ID {$newId})");
        $success = "Scholarship \"{$name}\" created successfully!";
        $action = 'list';
    }
}

// Handle status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'toggle_status') {
    $scholarshipId = intval($_POST['scholarship_id']);
    $newStatus = trim($_POST['new_status']);
    if (in_array($newStatus, ['active','inactive'])) {
        $stmt = $db->prepare("UPDATE scholarships SET status=? WHERE id=?");
        $stmt->execute([$newStatus, $scholarshipId]);
        logActivity($userId, 'UPDATE_SCHOLARSHIP_STATUS', "Scholarship {$scholarshipId} set to {$newStatus}");
        $success = "Scholarship status updated.";
    }
}

// Fetch all scholarships
$scholarships = $db->query("
    SELECT s.*, u.name AS creator_name,
           (SELECT COUNT(*) FROM disbursements WHERE scholarship_id = s.id AND status='confirmed') AS disbursement_count,
           (SELECT COALESCE(SUM(amount),0) FROM disbursements WHERE scholarship_id = s.id AND status='confirmed') AS total_disbursed
    FROM scholarships s
    JOIN users u ON s.created_by = u.id
    ORDER BY s.created_at DESC
")->fetchAll();

renderHead('Scholarships');
renderSidebar('admin', 'scholarships');
?>

<?php if ($success): ?>
<div class="alert alert-success" data-autohide><i class="ti ti-check"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger" data-autohide><i class="ti ti-x"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-end">
  <div>
    <h1 class="page-title">Scholarships</h1>
    <p class="page-subtitle">Manage scholarship grants and funding pools.</p>
  </div>
  <button class="btn btn-primary" onclick="toggleCreateForm()">
    <i class="ti ti-plus"></i> New Scholarship
  </button>
</div>

<!-- Create Form -->
<div id="create-form" style="display:<?= ($action === 'create' || $error) ? 'block' : 'none' ?>;margin-bottom:24px;">
  <div class="card">
    <div class="card-header">
      <span class="card-title">Create New Scholarship</span>
      <button class="btn btn-sm btn-outline" onclick="toggleCreateForm()">
        <i class="ti ti-x"></i> Cancel
      </button>
    </div>
    <form method="POST" action="">
      <input type="hidden" name="_action" value="create">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Scholarship Name <span class="required">*</span></label>
          <input type="text" name="name" class="form-control"
                 value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                 placeholder="e.g. STEM Global Grant 2025" required>
        </div>
        <div class="form-group">
          <label class="form-label">Total Amount (USDC) <span class="required">*</span></label>
          <input type="number" name="total_amount" class="form-control"
                 value="<?= htmlspecialchars($_POST['total_amount'] ?? '') ?>"
                 placeholder="e.g. 500" min="1" step="0.01" required>
          <div class="form-hint">Enter in USDC (e.g. 500 = $500)</div>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" placeholder="Purpose of this scholarship fund…"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">USDC Token Contract Address</label>
        <input type="text" name="token_address" class="form-control mono"
               value="<?= htmlspecialchars($_POST['token_address'] ?? '') ?>"
               placeholder="CA2EY3LL6EI3ARMSRPSIUDG5K5YFS6T5367Z46OAP6FIHJFYDX5H6JUU">
        <div class="form-hint">Stellar testnet USDC contract. Leave blank to use default.</div>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:4px">
        <button type="button" class="btn btn-outline" onclick="toggleCreateForm()">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="ti ti-check"></i> Create Scholarship</button>
      </div>
    </form>
  </div>
</div>

<!-- Scholarships Table -->
<div class="card">
  <div class="card-header">
    <span class="card-title">All Scholarships (<?= count($scholarships) ?>)</span>
  </div>
  <?php if (empty($scholarships)): ?>
    <div class="empty-state">
      <div class="empty-icon"><i class="ti ti-award"></i></div>
      <p>No scholarships yet. Create your first one above.</p>
    </div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Total (USDC)</th>
          <th>Remaining</th>
          <th>Disbursements</th>
          <th>Status</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($scholarships as $sch): ?>
        <?php
          $pct = $sch['total_amount'] > 0
              ? round((($sch['total_amount'] - $sch['remaining_amount']) / $sch['total_amount']) * 100)
              : 0;
          $statusClass = ['active'=>'badge-success','inactive'=>'badge-neutral','depleted'=>'badge-danger'][$sch['status']] ?? 'badge-neutral';
        ?>
        <tr>
          <td>
            <div style="font-weight:500"><?= htmlspecialchars($sch['name']) ?></div>
            <?php if ($sch['description']): ?>
            <div style="font-size:12px;color:var(--text-muted);margin-top:2px"><?= htmlspecialchars(substr($sch['description'],0,60)) ?>…</div>
            <?php endif; ?>
          </td>
          <td style="font-weight:500"><?= number_format($sch['total_amount']/10000000,2) ?></td>
          <td>
            <div><?= number_format($sch['remaining_amount']/10000000,2) ?></div>
            <div style="margin-top:4px;background:var(--bg);border-radius:4px;height:4px;width:80px;overflow:hidden">
              <div style="height:4px;background:var(--accent);border-radius:4px;width:<?= $pct ?>%"></div>
            </div>
            <div style="font-size:11px;color:var(--text-hint)"><?= $pct ?>% used</div>
          </td>
          <td><?= $sch['disbursement_count'] ?></td>
          <td><span class="badge <?= $statusClass ?>"><?= ucfirst($sch['status']) ?></span></td>
          <td style="font-size:12px;color:var(--text-muted)"><?= date('M j, Y', strtotime($sch['created_at'])) ?></td>
          <td>
            <div style="display:flex;gap:6px">
              <a href="/scholarpay/admin/disburse.php?scholarship_id=<?= $sch['id'] ?>" class="btn btn-sm btn-primary">
                <i class="ti ti-send"></i> Disburse
              </a>
              <?php if ($sch['status'] === 'active'): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="_action" value="toggle_status">
                <input type="hidden" name="scholarship_id" value="<?= $sch['id'] ?>">
                <input type="hidden" name="new_status" value="inactive">
                <button class="btn btn-sm btn-outline" type="submit">Pause</button>
              </form>
              <?php else: ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="_action" value="toggle_status">
                <input type="hidden" name="scholarship_id" value="<?= $sch['id'] ?>">
                <input type="hidden" name="new_status" value="active">
                <button class="btn btn-sm btn-outline" type="submit">Activate</button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<script>
function toggleCreateForm() {
  const form = document.getElementById('create-form');
  form.style.display = form.style.display === 'none' ? 'block' : 'none';
}
</script>

<?php renderFooter(); ?>
