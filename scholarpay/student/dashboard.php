<?php
define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/layout.php';
requireStudent();

$db = getDB();
$userId = $_SESSION['user_id'];

// Student info
$student = $db->prepare("SELECT * FROM users WHERE id=? LIMIT 1");
$student->execute([$userId]);
$student = $student->fetch();

// Stats
$totalReceived = $db->prepare("SELECT COALESCE(SUM(amount),0) FROM disbursements WHERE student_id=? AND status='confirmed'");
$totalReceived->execute([$userId]);
$totalReceived = $totalReceived->fetchColumn();

$disbCount = $db->prepare("SELECT COUNT(*) FROM disbursements WHERE student_id=? AND status='confirmed'");
$disbCount->execute([$userId]);
$disbCount = $disbCount->fetchColumn();

$lastDisb = $db->prepare("SELECT d.*, s.name AS scholarship_name FROM disbursements d JOIN scholarships s ON d.scholarship_id=s.id WHERE d.student_id=? AND d.status='confirmed' ORDER BY d.disbursed_at DESC LIMIT 1");
$lastDisb->execute([$userId]);
$lastDisb = $lastDisb->fetch();

// Recent disbursements
$recent = $db->prepare("
    SELECT d.*, s.name AS scholarship_name
    FROM disbursements d
    JOIN scholarships s ON d.scholarship_id = s.id
    WHERE d.student_id = ?
    ORDER BY d.disbursed_at DESC LIMIT 5
");
$recent->execute([$userId]);
$recent = $recent->fetchAll();

renderHead('My Dashboard');
renderSidebar('student', 'dashboard');
?>

<div class="page-header">
  <h1 class="page-title">Welcome, <?= htmlspecialchars(explode(' ', $student['name'])[0]) ?>!</h1>
  <p class="page-subtitle">Your ScholarPay scholarship account overview.</p>
</div>

<!-- Wallet status banner -->
<?php if (empty($student['stellar_address'])): ?>
<div class="alert alert-warning" style="margin-bottom:24px">
  <i class="ti ti-wallet"></i>
  <div>
    <strong>No Stellar wallet linked.</strong>
    You won't be able to receive disbursements until an admin links your Stellar wallet address.
    Contact your scholarship coordinator with your Stellar public key.
    <a href="/scholarpay/student/wallet.php" style="margin-left:8px;font-weight:500">Manage wallet →</a>
  </div>
</div>
<?php else: ?>
<div class="alert alert-info" style="margin-bottom:24px">
  <i class="ti ti-wallet"></i>
  <div>
    <strong>Stellar wallet active.</strong>
    Your wallet is linked. Disbursements will arrive in seconds.
    <div style="margin-top:4px" data-stellar="<?= htmlspecialchars($student['stellar_address']) ?>"></div>
  </div>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-label"><i class="ti ti-cash"></i> Total Received</div>
    <div class="stat-value usdc"><?= number_format($totalReceived/10000000, 2) ?></div>
    <div class="stat-sub">Confirmed on Stellar</div>
  </div>
  <div class="stat-card">
    <div class="stat-label"><i class="ti ti-send"></i> Disbursements</div>
    <div class="stat-value"><?= $disbCount ?></div>
    <div class="stat-sub">Confirmed payments</div>
  </div>
  <div class="stat-card">
    <div class="stat-label"><i class="ti ti-calendar"></i> Last Payment</div>
    <?php if ($lastDisb): ?>
    <div class="stat-value" style="font-size:18px"><?= date('M j, Y', strtotime($lastDisb['disbursed_at'])) ?></div>
    <div class="stat-sub"><?= htmlspecialchars($lastDisb['scholarship_name']) ?></div>
    <?php else: ?>
    <div class="stat-value" style="font-size:16px;color:var(--text-hint)">None yet</div>
    <?php endif; ?>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

  <!-- Recent Disbursements -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Recent Payments</span>
      <a href="/scholarpay/student/disbursements.php" class="btn btn-sm btn-outline">View all</a>
    </div>
    <?php if (empty($recent)): ?>
      <div class="empty-state">
        <div class="empty-icon"><i class="ti ti-cash"></i></div>
        <p>No disbursements yet.</p>
      </div>
    <?php else: ?>
      <?php foreach ($recent as $d): ?>
      <div style="padding:10px 0;border-bottom:1px solid var(--border)">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <div>
            <div style="font-weight:500;font-size:13.5px">+<?= number_format($d['amount']/10000000,2) ?> USDC</div>
            <div style="font-size:12px;color:var(--text-muted)"><?= htmlspecialchars($d['scholarship_name']) ?> · <?= htmlspecialchars($d['purpose']) ?></div>
          </div>
          <div style="text-align:right">
            <?php $badgeClass = ['confirmed'=>'badge-success','pending'=>'badge-warning','failed'=>'badge-danger'][$d['status']] ?? 'badge-neutral'; ?>
            <span class="badge <?= $badgeClass ?>"><?= ucfirst($d['status']) ?></span>
            <div style="font-size:11px;color:var(--text-hint);margin-top:3px"><?= date('M j', strtotime($d['disbursed_at'])) ?></div>
          </div>
        </div>
        <?php if ($d['stellar_tx_hash']): ?>
        <div style="margin-top:4px" data-txhash="<?= htmlspecialchars($d['stellar_tx_hash']) ?>"></div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- How to receive -->
  <div class="card">
    <div class="card-title" style="margin-bottom:14px">How to receive scholarships</div>
    <?php
    $steps = [
      ['ti-wallet','Get a Stellar wallet','Download Freighter or LOBSTR app and create an account.'],
      ['ti-copy','Share your public key','Send your Stellar public key (starts with G) to your scholarship coordinator.'],
      ['ti-shield-check','Get whitelisted','Admin links your wallet to your account in ScholarPay.'],
      ['ti-bolt','Receive instantly','Disbursements arrive in your wallet in 3–5 seconds via USDC.'],
    ];
    foreach ($steps as $i => [$icon,$title,$desc]):
    ?>
    <div style="display:flex;gap:12px;padding:10px 0;border-bottom:1px solid var(--border)">
      <div style="width:30px;height:30px;background:var(--accent-bg);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:13px;font-weight:600;color:var(--accent)"><?= $i+1 ?></div>
      <div>
        <div style="font-weight:500;font-size:13.5px"><?= $title ?></div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:2px"><?= $desc ?></div>
      </div>
    </div>
    <?php endforeach; ?>
    <div style="margin-top:14px;display:flex;gap:8px;flex-wrap:wrap">
      <a href="https://freighter.app" target="_blank" class="btn btn-sm btn-outline">
        <i class="ti ti-external-link"></i> Freighter Wallet
      </a>
      <a href="https://lobstr.co" target="_blank" class="btn btn-sm btn-outline">
        <i class="ti ti-external-link"></i> LOBSTR Wallet
      </a>
    </div>
  </div>

</div>

<?php renderFooter(); ?>
