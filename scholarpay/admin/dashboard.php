<?php
define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/layout.php';
requireAdmin();

$db = getDB();
$userId = $_SESSION['user_id'];

// Stats
$totalScholarships = $db->query("SELECT COUNT(*) FROM scholarships")->fetchColumn();
$activeScholarships = $db->query("SELECT COUNT(*) FROM scholarships WHERE status='active'")->fetchColumn();
$totalDisbursed = $db->query("SELECT COALESCE(SUM(amount),0) FROM disbursements WHERE status='confirmed'")->fetchColumn();
$totalStudents = $db->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$pendingDisbursements = $db->query("SELECT COUNT(*) FROM disbursements WHERE status='pending'")->fetchColumn();

// Recent disbursements
$recentDisbursements = $db->query("
    SELECT d.*, u.name AS student_name, u.stellar_address, s.name AS scholarship_name
    FROM disbursements d
    JOIN users u ON d.student_id = u.id
    JOIN scholarships s ON d.scholarship_id = s.id
    ORDER BY d.disbursed_at DESC LIMIT 8
")->fetchAll();

// Active scholarships
$activeScholarshipList = $db->query("
    SELECT s.*, u.name AS creator_name,
           (SELECT COUNT(*) FROM disbursements WHERE scholarship_id = s.id) AS disbursement_count
    FROM scholarships s
    JOIN users u ON s.created_by = u.id
    WHERE s.status = 'active'
    ORDER BY s.created_at DESC LIMIT 5
")->fetchAll();

renderHead('Dashboard');
renderSidebar('admin', 'dashboard');
?>

<div class="page-header">
  <h1 class="page-title">Dashboard</h1>
  <p class="page-subtitle">Welcome back, <?= htmlspecialchars($_SESSION['name']) ?>. Here's your ScholarPay overview.</p>
</div>

<!-- Stats -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-label"><i class="ti ti-award"></i> Active Scholarships</div>
    <div class="stat-value"><?= $activeScholarships ?></div>
    <div class="stat-sub"><?= $totalScholarships ?> total registered</div>
  </div>
  <div class="stat-card">
    <div class="stat-label"><i class="ti ti-send"></i> Total Disbursed</div>
    <div class="stat-value usdc"><?= number_format($totalDisbursed / 10000000, 2) ?></div>
    <div class="stat-sub">Confirmed on Stellar</div>
  </div>
  <div class="stat-card">
    <div class="stat-label"><i class="ti ti-users"></i> Students</div>
    <div class="stat-value"><?= $totalStudents ?></div>
    <div class="stat-sub">Registered recipients</div>
  </div>
  <div class="stat-card">
    <div class="stat-label"><i class="ti ti-clock"></i> Pending</div>
    <div class="stat-value"><?= $pendingDisbursements ?></div>
    <div class="stat-sub">Awaiting confirmation</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">

  <!-- Quick Actions -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Quick Actions</span>
    </div>
    <div style="display:flex;flex-direction:column;gap:10px;">
      <a href="/scholarpay/admin/disburse.php" class="btn btn-primary">
        <i class="ti ti-send"></i> Disburse Scholarship
      </a>
      <a href="/scholarpay/admin/scholarships.php?action=create" class="btn btn-outline">
        <i class="ti ti-plus"></i> Create Scholarship
      </a>
      <a href="/scholarpay/admin/students.php?action=add" class="btn btn-outline">
        <i class="ti ti-user-plus"></i> Add Student
      </a>
      <a href="/scholarpay/admin/audit.php" class="btn btn-outline">
        <i class="ti ti-list-search"></i> View Audit Log
      </a>
    </div>
  </div>

  <!-- Active Scholarships -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Active Scholarships</span>
      <a href="/scholarpay/admin/scholarships.php" class="btn btn-sm btn-outline">View all</a>
    </div>
    <?php if (empty($activeScholarshipList)): ?>
      <div class="empty-state">
        <div class="empty-icon"><i class="ti ti-award"></i></div>
        <p>No active scholarships yet.</p>
        <a href="/scholarpay/admin/scholarships.php?action=create" class="btn btn-sm btn-primary" style="margin-top:10px;">Create one</a>
      </div>
    <?php else: ?>
      <?php foreach ($activeScholarshipList as $sch): ?>
      <div style="padding:10px 0;border-bottom:1px solid var(--border);last-child:border-none">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;">
          <div>
            <div style="font-weight:500;font-size:13.5px"><?= htmlspecialchars($sch['name']) ?></div>
            <div style="font-size:12px;color:var(--text-muted);margin-top:2px">
              <?= $sch['disbursement_count'] ?> disbursements · By <?= htmlspecialchars($sch['creator_name']) ?>
            </div>
          </div>
          <div style="text-align:right">
            <div style="font-weight:600;font-size:13.5px"><?= number_format($sch['remaining_amount']/10000000,2) ?> USDC</div>
            <div style="font-size:11px;color:var(--text-hint)">remaining</div>
          </div>
        </div>
        <?php
          $pct = $sch['total_amount'] > 0
              ? round((($sch['total_amount'] - $sch['remaining_amount']) / $sch['total_amount']) * 100)
              : 0;
        ?>
        <div style="margin-top:6px;background:var(--bg);border-radius:4px;height:4px;overflow:hidden">
          <div style="height:4px;background:var(--accent);border-radius:4px;width:<?= $pct ?>%"></div>
        </div>
        <div style="font-size:11px;color:var(--text-hint);margin-top:2px"><?= $pct ?>% disbursed</div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>

<!-- Recent Disbursements -->
<div class="card">
  <div class="card-header">
    <span class="card-title">Recent Disbursements</span>
    <a href="/scholarpay/admin/audit.php" class="btn btn-sm btn-outline">Full audit log</a>
  </div>
  <?php if (empty($recentDisbursements)): ?>
    <div class="empty-state">
      <div class="empty-icon"><i class="ti ti-send"></i></div>
      <p>No disbursements yet. <a href="/scholarpay/admin/disburse.php">Disburse a scholarship</a> to get started.</p>
    </div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Student</th>
          <th>Scholarship</th>
          <th>Amount (USDC)</th>
          <th>Purpose</th>
          <th>Tx Hash</th>
          <th>Status</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recentDisbursements as $d): ?>
        <tr>
          <td>
            <strong style="font-size:13px"><?= htmlspecialchars($d['student_name']) ?></strong><br>
            <span data-stellar="<?= htmlspecialchars($d['stellar_address']) ?>"></span>
          </td>
          <td style="font-size:12.5px;max-width:140px"><?= htmlspecialchars($d['scholarship_name']) ?></td>
          <td style="font-weight:600"><?= number_format($d['amount']/10000000,2) ?></td>
          <td><span class="badge badge-neutral"><?= htmlspecialchars($d['purpose']) ?></span></td>
          <td>
            <?php if ($d['stellar_tx_hash']): ?>
            <span data-txhash="<?= htmlspecialchars($d['stellar_tx_hash']) ?>"></span>
            <?php else: ?>
            <span style="color:var(--text-hint);font-size:12px">—</span>
            <?php endif; ?>
          </td>
          <td>
            <?php
            $badgeClass = ['confirmed'=>'badge-success','pending'=>'badge-warning','failed'=>'badge-danger'][$d['status']] ?? 'badge-neutral';
            ?>
            <span class="badge <?= $badgeClass ?>"><?= ucfirst($d['status']) ?></span>
          </td>
          <td style="font-size:12px;color:var(--text-muted)"><?= date('M j, Y', strtotime($d['disbursed_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php renderFooter(); ?>
