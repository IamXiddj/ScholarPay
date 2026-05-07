<?php
define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/layout.php';
requireAdmin();

$db = getDB();
$userId = $_SESSION['user_id'];
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['_action'] ?? '';

    if ($action === 'update_profile') {
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $inst  = trim($_POST['institution'] ?? '');
        if (empty($name) || empty($email)) {
            $error = 'Name and email are required.';
        } else {
            $db->prepare("UPDATE users SET name=?, email=?, institution=? WHERE id=?")->execute([$name, $email, $inst, $userId]);
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            logActivity($userId, 'UPDATE_PROFILE', 'Admin updated profile');
            $success = 'Profile updated.';
        }
    }

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $user    = $db->prepare("SELECT * FROM users WHERE id=? LIMIT 1");
        $user->execute([$userId]);
        $user = $user->fetch();
        if (!password_verify($current, $user['password'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($new !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([hashPassword($new), $userId]);
            logActivity($userId, 'CHANGE_PASSWORD', 'Admin changed password');
            $success = 'Password changed successfully.';
        }
    }
}

$admin = $db->prepare("SELECT * FROM users WHERE id=? LIMIT 1");
$admin->execute([$userId]);
$admin = $admin->fetch();

renderHead('Settings');
renderSidebar('admin', 'settings');
?>

<?php if ($success): ?>
<div class="alert alert-success" data-autohide><i class="ti ti-check"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger" data-autohide><i class="ti ti-x"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="page-header">
  <h1 class="page-title">Settings</h1>
  <p class="page-subtitle">Manage your account profile and security.</p>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

  <!-- Profile -->
  <div class="card">
    <div class="card-header"><span class="card-title">Profile</span></div>
    <form method="POST">
      <input type="hidden" name="_action" value="update_profile">
      <div class="form-group">
        <label class="form-label">Full Name <span class="required">*</span></label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($admin['name']) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Email <span class="required">*</span></label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($admin['email']) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Institution</label>
        <input type="text" name="institution" class="form-control" value="<?= htmlspecialchars($admin['institution'] ?? '') ?>">
      </div>
      <button type="submit" class="btn btn-primary"><i class="ti ti-check"></i> Save Profile</button>
    </form>
  </div>

  <!-- Change Password -->
  <div class="card">
    <div class="card-header"><span class="card-title">Change Password</span></div>
    <form method="POST">
      <input type="hidden" name="_action" value="change_password">
      <div class="form-group">
        <label class="form-label">Current Password <span class="required">*</span></label>
        <input type="password" name="current_password" class="form-control" required>
      </div>
      <div class="form-group">
        <label class="form-label">New Password <span class="required">*</span></label>
        <input type="password" name="new_password" class="form-control" required minlength="6">
      </div>
      <div class="form-group">
        <label class="form-label">Confirm New Password <span class="required">*</span></label>
        <input type="password" name="confirm_password" class="form-control" required minlength="6">
      </div>
      <button type="submit" class="btn btn-primary"><i class="ti ti-lock"></i> Change Password</button>
    </form>
  </div>

</div>

<!-- About -->
<div class="card" style="margin-top:20px">
  <div class="card-header"><span class="card-title">About ScholarPay</span></div>
  <div style="font-size:13.5px;color:var(--text-muted);line-height:1.8">
    <p>ScholarPay is a Soroban smart contract on Stellar that enables instant, on-chain scholarship disbursements via USDC.</p>
    <p style="margin-top:8px">
      <strong>Contract:</strong>
      <a href="https://lab.stellar.org/r/testnet/contract/CA2EY3LL6EI3ARMSRPSIUDG5K5YFS6T5367Z46OAP6FIHJFYDX5H6JUU"
         target="_blank" class="hash-chip">CA2EY3LL6E…H6JUU</a>
      &nbsp;
      <a href="https://stellar.expert/explorer/testnet/tx/1b9a54974c38ddc0944e76180f966d4c94b1130f1c1ac635ec1316d147d3b7b0"
         target="_blank" class="btn btn-sm btn-outline"><i class="ti ti-external-link"></i> Explorer</a>
    </p>
    <p style="margin-top:8px"><strong>Stack:</strong> PHP · MySQL · HTML/JS · Stellar Testnet · Soroban</p>
  </div>
</div>

<?php renderFooter(); ?>
