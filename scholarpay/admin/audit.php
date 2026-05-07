<?php
define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/layout.php';
requireAdmin();

$db = getDB();

// Filters
$filterStudent    = intval($_GET['student_id'] ?? 0);
$filterScholarship = intval($_GET['scholarship_id'] ?? 0);
$filterStatus     = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$where = ['1=1'];
$params = [];
if ($filterStudent) { $where[] = 'd.student_id = ?'; $params[] = $filterStudent; }
if ($filterScholarship) { $where[] = 'd.scholarship_id = ?'; $params[] = $filterScholarship; }
if ($filterStatus) { $where[] = 'd.status = ?'; $params[] = $filterStatus; }

$whereClause = implode(' AND ', $where);

$total = $db->prepare("SELECT COUNT(*) FROM disbursements d WHERE $whereClause");
$total->execute($params);
$totalCount = $total->fetchColumn();
$totalPages = max(1, ceil($totalCount / $perPage));

$params2 = array_merge($params, [$perPage, $offset]);
$disbursements = $db->prepare("
    SELECT d.*,
           u.name AS student_name, u.stellar_address, u.email AS student_email,
           a.name AS admin_name,
           s.name AS scholarship_name
    FROM disbursements d
    JOIN users u ON d.student_id = u.id
    JOIN users a ON d.admin_id   = a.id
    JOIN scholarships s ON d.scholarship_id = s.id
    WHERE $whereClause
    ORDER BY d.disbursed_at DESC
    LIMIT ? OFFSET ?
");
$disbursements->execute($params2);
$disbursements = $disbursements->fetchAll();

// For filters
$students     = $db->query("SELECT id, name FROM users WHERE role='student' ORDER BY name")->fetchAll();
$scholarships = $db->query("SELECT id, name FROM scholarships ORDER BY name")->fetchAll();

// Summary stats for filtered view
$summary = $db->prepare("
    SELECT COUNT(*) AS count, COALESCE(SUM(amount),0) AS total
    FROM disbursements d WHERE $whereClause AND status='confirmed'
");
$summary->execute($params);
$summary = $summary->fetch();

renderHead('Audit Log');
renderSidebar('admin', 'audit');
?>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-end">
  <div>
    <h1 class="page-title">Audit Log</h1>
    <p class="page-subtitle">Immutable record of all scholarship disbursements.</p>
  </div>
  <span class="stellar-badge"><i class="ti ti-lock"></i> On-chain verified</span>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:20px">
  <form method="GET" action="" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end">
    <div>
      <label class="form-label" style="font-size:11.5px">Student</label>
      <select name="student_id" class="form-control" style="width:180px">
        <option value="">All students</option>
        <?php foreach ($students as $s): ?>
        <option value="<?= $s['id'] ?>" <?= ($filterStudent==$s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="form-label" style="font-size:11.5px">Scholarship</label>
      <select name="scholarship_id" class="form-control" style="width:200px">
        <option value="">All scholarships</option>
        <?php foreach ($scholarships as $s): ?>
        <option value="<?= $s['id'] ?>" <?= ($filterScholarship==$s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="form-label" style="font-size:11.5px">Status</label>
      <select name="status" class="form-control" style="width:140px">
        <option value="">All statuses</option>
        <option value="confirmed" <?= $filterStatus==='confirmed' ? 'selected' : '' ?>>Confirmed</option>
        <option value="pending"   <?= $filterStatus==='pending'   ? 'selected' : '' ?>>Pending</option>
        <option value="failed"    <?= $filterStatus==='failed'    ? 'selected' : '' ?>>Failed</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary"><i class="ti ti-filter"></i> Filter</button>
    <a href="/scholarpay/admin/audit.php" class="btn btn-outline"><i class="ti ti-x"></i> Clear</a>
  </form>
</div>

<!-- Summary -->
<div class="stats-grid" style="margin-bottom:20px">
  <div class="stat-card">
    <div class="stat-label">Matching disbursements</div>
    <div class="stat-value"><?= number_format($summary['count']) ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Total confirmed USDC</div>
    <div class="stat-value usdc"><?= number_format($summary['total']/10000000,2) ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Page</div>
    <div class="stat-value"><?= $page ?> / <?= $totalPages ?></div>
  </div>
</div>

<!-- Disbursements Table -->
<div class="card">
  <div class="card-header">
    <span class="card-title">Disbursements (<?= $totalCount ?> total)</span>
  </div>
  <?php if (empty($disbursements)): ?>
    <div class="empty-state">
      <div class="empty-icon"><i class="ti ti-list-search"></i></div>
      <p>No disbursements match your filters.</p>
    </div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Student</th>
          <th>Scholarship</th>
          <th>Amount (USDC)</th>
          <th>Purpose</th>
          <th>Tx Hash</th>
          <th>Ledger</th>
          <th>Status</th>
          <th>Admin</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($disbursements as $d): ?>
        <tr>
          <td style="color:var(--text-hint);font-size:12px"><?= $d['id'] ?></td>
          <td>
            <div style="font-weight:500;font-size:13px"><?= htmlspecialchars($d['student_name']) ?></div>
            <div style="font-size:11px;color:var(--text-hint)"><?= htmlspecialchars($d['student_email']) ?></div>
            <span data-stellar="<?= htmlspecialchars($d['stellar_address']) ?>"></span>
          </td>
          <td style="font-size:12.5px;max-width:130px"><?= htmlspecialchars($d['scholarship_name']) ?></td>
          <td style="font-weight:600"><?= number_format($d['amount']/10000000,2) ?></td>
          <td><span class="badge badge-neutral"><?= htmlspecialchars($d['purpose']) ?></span></td>
          <td>
            <?php if ($d['stellar_tx_hash']): ?>
            <span data-txhash="<?= htmlspecialchars($d['stellar_tx_hash']) ?>"></span>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td>
            <?php if ($d['ledger_sequence']): ?>
            <span style="font-family:var(--mono);font-size:11px;color:var(--text-muted)">#<?= number_format($d['ledger_sequence']) ?></span>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td>
            <?php $badgeClass = ['confirmed'=>'badge-success','pending'=>'badge-warning','failed'=>'badge-danger'][$d['status']] ?? 'badge-neutral'; ?>
            <span class="badge <?= $badgeClass ?>"><?= ucfirst($d['status']) ?></span>
          </td>
          <td style="font-size:12px;color:var(--text-muted)"><?= htmlspecialchars($d['admin_name']) ?></td>
          <td style="font-size:12px;color:var(--text-muted);white-space:nowrap"><?= date('M j, Y H:i', strtotime($d['disbursed_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <div style="display:flex;justify-content:center;gap:8px;padding:16px 0 4px">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <a href="?page=<?= $i ?>&student_id=<?= $filterStudent ?>&scholarship_id=<?= $filterScholarship ?>&status=<?= $filterStatus ?>"
       class="btn btn-sm <?= $i === $page ? 'btn-primary' : 'btn-outline' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>

  <?php endif; ?>
</div>

<?php renderFooter(); ?>
