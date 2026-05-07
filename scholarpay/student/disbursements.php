<?php
define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/layout.php';
requireStudent();

$db = getDB();
$userId = $_SESSION['user_id'];

$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

$total = $db->prepare("SELECT COUNT(*) FROM disbursements WHERE student_id=?");
$total->execute([$userId]);
$totalCount = $total->fetchColumn();
$totalPages = max(1, ceil($totalCount / $perPage));

$disbursements = $db->prepare("
    SELECT d.*, s.name AS scholarship_name
    FROM disbursements d
    JOIN scholarships s ON d.scholarship_id = s.id
    WHERE d.student_id = ?
    ORDER BY d.disbursed_at DESC
    LIMIT ? OFFSET ?
");
$disbursements->execute([$userId, $perPage, $offset]);
$disbursements = $disbursements->fetchAll();

$totalReceived = $db->prepare("SELECT COALESCE(SUM(amount),0) FROM disbursements WHERE student_id=? AND status='confirmed'");
$totalReceived->execute([$userId]);
$totalReceived = $totalReceived->fetchColumn();

renderHead('My Disbursements');
renderSidebar('student', 'disbursements');
?>

<div class="page-header">
  <h1 class="page-title">My Disbursements</h1>
  <p class="page-subtitle">Full history of scholarship payments to your Stellar wallet.</p>
</div>

<div class="stats-grid" style="margin-bottom:20px">
  <div class="stat-card">
    <div class="stat-label">Total received</div>
    <div class="stat-value usdc"><?= number_format($totalReceived/10000000,2) ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Payments</div>
    <div class="stat-value"><?= number_format($totalCount) ?></div>
  </div>
</div>

<div class="card">
  <?php if (empty($disbursements)): ?>
    <div class="empty-state">
      <div class="empty-icon"><i class="ti ti-cash"></i></div>
      <p>No disbursements yet.</p>
    </div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Scholarship</th>
          <th>Amount (USDC)</th>
          <th>Purpose</th>
          <th>Transaction</th>
          <th>Ledger</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($disbursements as $d): ?>
        <tr>
          <td style="font-size:12.5px;white-space:nowrap"><?= date('M j, Y H:i', strtotime($d['disbursed_at'])) ?></td>
          <td style="font-size:13px"><?= htmlspecialchars($d['scholarship_name']) ?></td>
          <td style="font-weight:600;color:var(--success)">+<?= number_format($d['amount']/10000000,2) ?></td>
          <td><span class="badge badge-neutral"><?= htmlspecialchars($d['purpose']) ?></span></td>
          <td>
            <?php if ($d['stellar_tx_hash']): ?>
            <span data-txhash="<?= htmlspecialchars($d['stellar_tx_hash']) ?>"></span>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td style="font-family:var(--mono);font-size:11px;color:var(--text-muted)">
            <?= $d['ledger_sequence'] ? '#'.number_format($d['ledger_sequence']) : '—' ?>
          </td>
          <td>
            <?php $bc = ['confirmed'=>'badge-success','pending'=>'badge-warning','failed'=>'badge-danger'][$d['status']] ?? 'badge-neutral'; ?>
            <span class="badge <?= $bc ?>"><?= ucfirst($d['status']) ?></span>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($totalPages > 1): ?>
  <div style="display:flex;justify-content:center;gap:8px;padding:16px 0 4px">
    <?php for ($i=1;$i<=$totalPages;$i++): ?>
    <a href="?page=<?= $i ?>" class="btn btn-sm <?= $i===$page ? 'btn-primary' : 'btn-outline' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<?php renderFooter(); ?>
