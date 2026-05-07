<?php
define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/layout.php';
requireAdmin();

$db = getDB();
$userId = $_SESSION['user_id'];
$success = $error = '';

// Handle add student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'add_student') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $pass    = trim($_POST['password'] ?? '');
    $stellar = trim($_POST['stellar_address'] ?? '');
    $inst    = trim($_POST['institution'] ?? '');

    if (empty($name) || empty($email) || empty($pass)) {
        $error = 'Name, email, and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (!empty($stellar) && !preg_match('/^G[A-Z2-7]{55}$/', $stellar)) {
        $error = 'Invalid Stellar address format. Must start with G and be 56 characters.';
    } else {
        // Check email uniqueness
        $exists = $db->prepare("SELECT id FROM users WHERE email = ?");
        $exists->execute([$email]);
        if ($exists->fetch()) {
            $error = 'A user with this email already exists.';
        } else {
            $hashed = hashPassword($pass);
            $ins = $db->prepare("INSERT INTO users (name, email, password, role, stellar_address, institution) VALUES (?,?,?,'student',?,?)");
            $ins->execute([$name, $email, $hashed, $stellar ?: null, $inst ?: null]);
            logActivity($userId, 'ADD_STUDENT', "Added student: {$name} ({$email})");
            $success = "Student \"{$name}\" added successfully!";
        }
    }
}

// Handle update wallet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'update_wallet') {
    $studentId = intval($_POST['student_id']);
    $stellar   = trim($_POST['stellar_address'] ?? '');
    if (!preg_match('/^G[A-Z2-7]{55}$/', $stellar)) {
        $error = 'Invalid Stellar address format.';
    } else {
        $db->prepare("UPDATE users SET stellar_address=? WHERE id=? AND role='student'")->execute([$stellar, $studentId]);
        logActivity($userId, 'UPDATE_WALLET', "Updated wallet for student ID {$studentId}");
        $success = 'Wallet address updated.';
    }
}

// Fetch students
$students = $db->query("
    SELECT u.*,
           (SELECT COUNT(*) FROM disbursements WHERE student_id = u.id AND status='confirmed') AS disbursement_count,
           (SELECT COALESCE(SUM(amount),0) FROM disbursements WHERE student_id = u.id AND status='confirmed') AS total_received
    FROM users u WHERE role='student'
    ORDER BY u.created_at DESC
")->fetchAll();

renderHead('Students');
renderSidebar('admin', 'students');
?>

<?php if ($success): ?>
<div class="alert alert-success" data-autohide><i class="ti ti-check"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger" data-autohide><i class="ti ti-x"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-end">
  <div>
    <h1 class="page-title">Students</h1>
    <p class="page-subtitle">Manage scholarship recipients and their Stellar wallets.</p>
  </div>
  <button class="btn btn-primary" onclick="toggleForm('add-form')">
    <i class="ti ti-user-plus"></i> Add Student
  </button>
</div>

<!-- Add Student Form -->
<div id="add-form" style="display:none;margin-bottom:24px;">
  <div class="card">
    <div class="card-header">
      <span class="card-title">Add New Student</span>
      <button class="btn btn-sm btn-outline" onclick="toggleForm('add-form')"><i class="ti ti-x"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="_action" value="add_student">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Full Name <span class="required">*</span></label>
          <input type="text" name="name" class="form-control" placeholder="e.g. Maria Santos" required>
        </div>
        <div class="form-group">
          <label class="form-label">Email <span class="required">*</span></label>
          <input type="email" name="email" class="form-control" placeholder="student@example.com" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Password <span class="required">*</span></label>
          <input type="password" name="password" class="form-control" placeholder="Set a temporary password" required>
        </div>
        <div class="form-group">
          <label class="form-label">Institution</label>
          <input type="text" name="institution" class="form-control" placeholder="e.g. University of Manila">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Stellar Wallet Address</label>
        <input type="text" id="stellar_address" name="stellar_address" class="form-control mono"
               placeholder="GBSZ2NFPQZJRRLSQ6TA5D5GNJNKBQVQVMWFP6LRGWUQZGFHK7ZFDXQ">
        <div class="form-hint" id="wallet-hint">Stellar public key starting with G (56 chars). Can be added later.</div>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button type="button" class="btn btn-outline" onclick="toggleForm('add-form')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="ti ti-check"></i> Add Student</button>
      </div>
    </form>
  </div>
</div>

<!-- Students Table -->
<div class="card">
  <div class="card-header">
    <span class="card-title">All Students (<?= count($students) ?>)</span>
  </div>
  <?php if (empty($students)): ?>
    <div class="empty-state">
      <div class="empty-icon"><i class="ti ti-users"></i></div>
      <p>No students yet. Add your first student above.</p>
    </div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Stellar Wallet</th>
          <th>Institution</th>
          <th>Received</th>
          <th>Disbursements</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($students as $st): ?>
        <tr>
          <td style="font-weight:500"><?= htmlspecialchars($st['name']) ?></td>
          <td style="font-size:12.5px;color:var(--text-muted)"><?= htmlspecialchars($st['email']) ?></td>
          <td>
            <?php if ($st['stellar_address']): ?>
              <span data-stellar="<?= htmlspecialchars($st['stellar_address']) ?>"></span>
            <?php else: ?>
              <span style="color:var(--warning);font-size:12px"><i class="ti ti-alert-triangle"></i> No wallet</span>
            <?php endif; ?>
          </td>
          <td style="font-size:12.5px;color:var(--text-muted)"><?= htmlspecialchars($st['institution'] ?? '—') ?></td>
          <td style="font-weight:500"><?= number_format($st['total_received']/10000000,2) ?> USDC</td>
          <td><?= $st['disbursement_count'] ?></td>
          <td>
            <div style="display:flex;gap:6px">
              <button class="btn btn-sm btn-outline" onclick="openWalletEdit(<?= $st['id'] ?>, '<?= htmlspecialchars($st['stellar_address'] ?? '') ?>', '<?= htmlspecialchars($st['name']) ?>')">
                <i class="ti ti-wallet"></i> Wallet
              </button>
              <a href="/scholarpay/admin/disburse.php" class="btn btn-sm btn-primary">
                <i class="ti ti-send"></i>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Wallet Edit Modal (simple inline) -->
<div id="wallet-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:200;align-items:center;justify-content:center">
  <div class="card" style="width:100%;max-width:480px;margin:24px">
    <div class="card-header">
      <span class="card-title">Update Wallet — <span id="modal-student-name"></span></span>
      <button class="btn btn-sm btn-outline" onclick="closeWalletModal()"><i class="ti ti-x"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="_action" value="update_wallet">
      <input type="hidden" name="student_id" id="modal-student-id">
      <div class="form-group">
        <label class="form-label">Stellar Wallet Address <span class="required">*</span></label>
        <input type="text" id="modal-stellar" name="stellar_address" class="form-control mono"
               placeholder="GBSZ..." required>
        <div class="form-hint">Public key starting with G, 56 characters total.</div>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button type="button" class="btn btn-outline" onclick="closeWalletModal()">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="ti ti-check"></i> Save Wallet</button>
      </div>
    </form>
  </div>
</div>

<script>
function toggleForm(id) {
  const el = document.getElementById(id);
  el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

function openWalletEdit(studentId, currentAddr, name) {
  document.getElementById('modal-student-id').value = studentId;
  document.getElementById('modal-stellar').value = currentAddr;
  document.getElementById('modal-student-name').textContent = name;
  document.getElementById('wallet-modal').style.display = 'flex';
}

function closeWalletModal() {
  document.getElementById('wallet-modal').style.display = 'none';
}
</script>

<?php renderFooter(); ?>
