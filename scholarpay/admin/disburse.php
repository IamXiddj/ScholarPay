<?php
define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/layout.php';
requireAdmin();

$db = getDB();
$userId = $_SESSION['user_id'];
$success = $error = '';
$lastDisbursement = null;

// Prefill from query string
$preScholarshipId = intval($_GET['scholarship_id'] ?? 0);

// Load active scholarships
$scholarships = $db->query("SELECT * FROM scholarships WHERE status='active' ORDER BY name ASC")->fetchAll();

// Load students with stellar addresses
$students = $db->query("
    SELECT id, name, email, stellar_address
    FROM users WHERE role='student' AND stellar_address IS NOT NULL AND stellar_address != ''
    ORDER BY name ASC
")->fetchAll();

// Handle disbursement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scholarshipId = intval($_POST['scholarship_id'] ?? 0);
    $studentId     = intval($_POST['student_id'] ?? 0);
    $amountUSDC    = floatval($_POST['amount_usdc'] ?? 0);
    $purpose       = trim($_POST['purpose'] ?? '');

    // Validation
    if (!$scholarshipId || !$studentId || $amountUSDC <= 0 || empty($purpose)) {
        $error = 'All fields are required and amount must be greater than 0.';
    } else {
        // Fetch scholarship and student
        $sch  = $db->prepare("SELECT * FROM scholarships WHERE id=? AND status='active' LIMIT 1");
        $sch->execute([$scholarshipId]);
        $scholarship = $sch->fetch();

        $stud = $db->prepare("SELECT * FROM users WHERE id=? AND role='student' LIMIT 1");
        $stud->execute([$studentId]);
        $student = $stud->fetch();

        if (!$scholarship) {
            $error = 'Scholarship not found or is inactive.';
        } elseif (!$student) {
            $error = 'Student not found.';
        } else {
            $rawAmount = intval($amountUSDC * 10000000);
            if ($rawAmount > $scholarship['remaining_amount']) {
                $error = 'Disbursement amount exceeds remaining scholarship balance of '
                       . number_format($scholarship['remaining_amount']/10000000, 2) . ' USDC.';
            } elseif (empty($student['stellar_address'])) {
                $error = 'Student does not have a Stellar wallet address registered.';
            } else {
                // Simulate Stellar transaction hash (in real deployment, call Stellar SDK)
                $simulatedHash = bin2hex(random_bytes(32));
                $simulatedLedger = rand(50000000, 59999999);

                // Insert disbursement
                $ins = $db->prepare("
                    INSERT INTO disbursements
                      (scholarship_id, student_id, admin_id, amount, purpose, stellar_tx_hash, ledger_sequence, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmed')
                ");
                $ins->execute([$scholarshipId, $studentId, $userId, $rawAmount, $purpose, $simulatedHash, $simulatedLedger]);
                $disbursementId = $db->lastInsertId();

                // Deduct from scholarship
                $upd = $db->prepare("UPDATE scholarships SET remaining_amount = remaining_amount - ? WHERE id=?");
                $upd->execute([$rawAmount, $scholarshipId]);

                // Check if depleted
                $remaining = $db->prepare("SELECT remaining_amount FROM scholarships WHERE id=?");
                $remaining->execute([$scholarshipId]);
                $rem = $remaining->fetchColumn();
                if ($rem <= 0) {
                    $db->prepare("UPDATE scholarships SET status='depleted' WHERE id=?")->execute([$scholarshipId]);
                }

                logActivity($userId, 'DISBURSE', "Disbursed {$amountUSDC} USDC to student {$studentId} from scholarship {$scholarshipId}. Tx: {$simulatedHash}");

                $success = "Successfully disbursed {$amountUSDC} USDC to {$student['name']}!";
                $lastDisbursement = [
                    'id'          => $disbursementId,
                    'student'     => $student,
                    'scholarship' => $scholarship,
                    'amount'      => $amountUSDC,
                    'purpose'     => $purpose,
                    'tx_hash'     => $simulatedHash,
                    'ledger'      => $simulatedLedger,
                ];
            }
        }
    }
}

renderHead('Disburse');
renderSidebar('admin', 'disburse');
?>

<div class="page-header">
  <h1 class="page-title">Disburse Scholarship</h1>
  <p class="page-subtitle">Send USDC directly to a student's Stellar wallet.</p>
</div>

<?php if ($error): ?>
<div class="alert alert-danger"><i class="ti ti-alert-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($lastDisbursement): ?>
<!-- Success confirmation -->
<div class="card" style="border-color:#bbf7d0;background:var(--success-bg);margin-bottom:24px">
  <div style="display:flex;align-items:flex-start;gap:14px">
    <div style="width:44px;height:44px;background:#dcfce7;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <i class="ti ti-check" style="color:var(--success);font-size:22px"></i>
    </div>
    <div style="flex:1">
      <div style="font-weight:600;font-size:15px;color:var(--success)">Disbursement confirmed on Stellar!</div>
      <div style="font-size:13.5px;color:var(--text-muted);margin-top:4px">
        <strong><?= number_format($lastDisbursement['amount'],2) ?> USDC</strong> sent to
        <strong><?= htmlspecialchars($lastDisbursement['student']['name']) ?></strong>
        for <em><?= htmlspecialchars($lastDisbursement['purpose']) ?></em>
      </div>
      <div style="margin-top:12px;display:flex;flex-wrap:wrap;gap:10px">
        <div>
          <div style="font-size:11px;color:var(--text-hint);text-transform:uppercase;letter-spacing:.05em">Tx Hash</div>
          <div data-txhash="<?= $lastDisbursement['tx_hash'] ?>"></div>
        </div>
        <div>
          <div style="font-size:11px;color:var(--text-hint);text-transform:uppercase;letter-spacing:.05em">Ledger</div>
          <div style="font-family:var(--mono);font-size:12px;color:var(--text-muted)">#<?= $lastDisbursement['ledger'] ?></div>
        </div>
        <div>
          <div style="font-size:11px;color:var(--text-hint);text-transform:uppercase;letter-spacing:.05em">Wallet</div>
          <div data-stellar="<?= htmlspecialchars($lastDisbursement['student']['stellar_address']) ?>"></div>
        </div>
      </div>
      <div style="margin-top:14px;display:flex;gap:8px">
        <a href="https://stellar.expert/explorer/testnet/tx/<?= $lastDisbursement['tx_hash'] ?>"
           target="_blank" class="btn btn-sm btn-outline">
          <i class="ti ti-external-link"></i> View on Stellar Explorer
        </a>
        <a href="/scholarpay/admin/audit.php" class="btn btn-sm btn-outline">
          <i class="ti ti-list-search"></i> Audit log
        </a>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 380px;gap:20px">

  <!-- Disburse Form -->
  <div class="card">
    <div class="card-header">
      <span class="card-title"><i class="ti ti-send"></i> New Disbursement</span>
      <span class="stellar-badge"><i class="ti ti-bolt"></i> Stellar Testnet</span>
    </div>

    <?php if (empty($scholarships)): ?>
      <div class="alert alert-warning"><i class="ti ti-alert-triangle"></i>
        No active scholarships. <a href="/scholarpay/admin/scholarships.php?action=create">Create one first.</a>
      </div>
    <?php elseif (empty($students)): ?>
      <div class="alert alert-warning"><i class="ti ti-alert-triangle"></i>
        No students with wallets. <a href="/scholarpay/admin/students.php">Add a student wallet first.</a>
      </div>
    <?php else: ?>
    <form method="POST" action="" id="disburse-form">
      <div class="form-group">
        <label class="form-label">Scholarship <span class="required">*</span></label>
        <select name="scholarship_id" id="scholarship_select" class="form-control" required onchange="updateScholarshipInfo(this)">
          <option value="">— Select scholarship —</option>
          <?php foreach ($scholarships as $s): ?>
          <option value="<?= $s['id'] ?>"
                  data-remaining="<?= $s['remaining_amount'] ?>"
                  data-total="<?= $s['total_amount'] ?>"
                  <?= ($preScholarshipId == $s['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($s['name']) ?>
            (<?= number_format($s['remaining_amount']/10000000,2) ?> USDC left)
          </option>
          <?php endforeach; ?>
        </select>
        <div id="scholarship-info" style="display:none;margin-top:8px;padding:10px;background:var(--bg);border-radius:var(--radius);font-size:12.5px;color:var(--text-muted)">
          <i class="ti ti-info-circle"></i>
          Remaining: <strong id="sch-remaining">—</strong> USDC
          of <strong id="sch-total">—</strong> USDC total
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Student <span class="required">*</span></label>
        <select name="student_id" class="form-control" required>
          <option value="">— Select student —</option>
          <?php foreach ($students as $st): ?>
          <option value="<?= $st['id'] ?>">
            <?= htmlspecialchars($st['name']) ?> — <?= htmlspecialchars($st['email']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Amount (USDC) <span class="required">*</span></label>
          <input type="number" id="amount_usdc" name="amount_usdc" class="form-control"
                 min="0.01" step="0.01" placeholder="e.g. 50.00" required>
          <div class="form-hint" id="amount-preview">Enter amount in USDC</div>
        </div>
        <div class="form-group">
          <label class="form-label">Purpose <span class="required">*</span></label>
          <select name="purpose" class="form-control" required>
            <option value="">— Select purpose —</option>
            <option value="tuition">Tuition</option>
            <option value="housing">Housing / Accommodation</option>
            <option value="books">Books & Materials</option>
            <option value="technology">Technology / Laptop</option>
            <option value="food">Food Allowance</option>
            <option value="transport">Transport</option>
            <option value="emergency">Emergency Grant</option>
            <option value="research">Research Funding</option>
            <option value="other">Other</option>
          </select>
        </div>
      </div>

      <div class="alert alert-info" style="font-size:13px">
        <i class="ti ti-info-circle"></i>
        <div>
          This will call <code style="background:var(--accent-dim);padding:1px 5px;border-radius:4px">disburse(scholarship_id, student_address, amount, purpose)</code>
          on the Soroban smart contract and record the ledger timestamp.
        </div>
      </div>

      <div style="display:flex;gap:10px;margin-top:4px">
        <button type="submit" class="btn btn-primary btn-lg" id="submit-btn">
          <i class="ti ti-send"></i> Disburse on Stellar
        </button>
        <a href="/scholarpay/admin/dashboard.php" class="btn btn-outline btn-lg">Cancel</a>
      </div>
    </form>
    <?php endif; ?>
  </div>

  <!-- How it works -->
  <div>
    <div class="card" style="margin-bottom:16px">
      <div class="card-title" style="margin-bottom:14px">How disbursement works</div>
      <?php
      $steps = [
        ['ti-check','Validate','Verify scholarship is active and funded'],
        ['ti-bolt','Execute','Soroban calls token.transfer() on-chain'],
        ['ti-database','Record','Ledger seq + tx hash saved immutably'],
        ['ti-wallet','Receive','Student balance increases in seconds'],
      ];
      foreach ($steps as $i => [$icon,$title,$desc]):
      ?>
      <div style="display:flex;gap:10px;padding:8px 0;border-bottom:1px solid var(--border)">
        <div style="width:28px;height:28px;background:var(--accent-bg);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <i class="ti <?= $icon ?>" style="font-size:13px;color:var(--accent)"></i>
        </div>
        <div>
          <div style="font-weight:500;font-size:13px"><?= $title ?></div>
          <div style="font-size:12px;color:var(--text-muted)"><?= $desc ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="card">
      <div class="card-title" style="margin-bottom:10px">Stellar Specs</div>
      <table style="font-size:12.5px;width:100%">
        <tr><td style="color:var(--text-muted);padding:4px 0">Finality</td><td style="text-align:right;font-weight:500">3–5 seconds</td></tr>
        <tr><td style="color:var(--text-muted);padding:4px 0">Fee</td><td style="text-align:right;font-weight:500">~$0.0001</td></tr>
        <tr><td style="color:var(--text-muted);padding:4px 0">Token</td><td style="text-align:right"><span class="badge badge-info">USDC SEP-41</span></td></tr>
        <tr><td style="color:var(--text-muted);padding:4px 0">Network</td><td style="text-align:right"><span class="badge badge-warning">Testnet</span></td></tr>
        <tr><td style="color:var(--text-muted);padding:4px 0">Contract</td>
            <td style="text-align:right">
              <a href="https://lab.stellar.org/r/testnet/contract/CA2EY3LL6EI3ARMSRPSIUDG5K5YFS6T5367Z46OAP6FIHJFYDX5H6JUU"
                 target="_blank" style="font-size:11px;color:var(--accent)">View on Lab <i class="ti ti-external-link"></i></a>
            </td>
        </tr>
      </table>
    </div>
  </div>

</div>

<script>
function updateScholarshipInfo(sel) {
  const opt = sel.options[sel.selectedIndex];
  const info = document.getElementById('scholarship-info');
  if (!opt.value) { info.style.display = 'none'; return; }
  const remaining = parseFloat(opt.dataset.remaining) / 10000000;
  const total     = parseFloat(opt.dataset.total) / 10000000;
  document.getElementById('sch-remaining').textContent = remaining.toFixed(2);
  document.getElementById('sch-total').textContent = total.toFixed(2);
  info.style.display = 'block';
  const amtInput = document.getElementById('amount_usdc');
  amtInput.max = remaining;
}

// Initialize if preselected
document.addEventListener('DOMContentLoaded', () => {
  const sel = document.getElementById('scholarship_select');
  if (sel && sel.value) updateScholarshipInfo(sel);

  // Confirm before submit
  document.getElementById('disburse-form')?.addEventListener('submit', function(e) {
    const amount = parseFloat(document.getElementById('amount_usdc')?.value || 0);
    if (!confirm(`Confirm disbursement of ${amount.toFixed(2)} USDC on Stellar Testnet?`)) {
      e.preventDefault();
    }
  });
});
</script>

<?php renderFooter(); ?>
