<?php
define('ROOT', dirname(__DIR__));
require_once ROOT . '/includes/auth.php';
require_once ROOT . '/includes/db.php';
require_once ROOT . '/includes/layout.php';
requireStudent();

$db = getDB();
$userId = $_SESSION['user_id'];
$success = $error = '';

// Allow student to submit their wallet address (admin must verify/approve)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stellar = trim($_POST['stellar_address'] ?? '');
    if (!preg_match('/^G[A-Z2-7]{55}$/', $stellar)) {
        $error = 'Invalid Stellar address. Must start with G and be exactly 56 characters.';
    } else {
        $db->prepare("UPDATE users SET stellar_address=? WHERE id=?")->execute([$stellar, $userId]);
        $_SESSION['stellar_address'] = $stellar;
        logActivity($userId, 'UPDATE_WALLET', "Student updated wallet to: {$stellar}");
        $success = 'Wallet address submitted! An admin will verify it shortly.';
    }
}

$student = $db->prepare("SELECT * FROM users WHERE id=? LIMIT 1");
$student->execute([$userId]);
$student = $student->fetch();

renderHead('My Wallet');
renderSidebar('student', 'wallet');
?>

<div class="page-header">
  <h1 class="page-title">My Wallet</h1>
  <p class="page-subtitle">Link your Stellar wallet to receive scholarship disbursements.</p>
</div>

<?php if ($success): ?>
<div class="alert alert-success" data-autohide><i class="ti ti-check"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger" data-autohide><i class="ti ti-x"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

  <!-- Current wallet -->
  <div class="card">
    <div class="card-header"><span class="card-title">Current Stellar Wallet</span></div>
    <?php if ($student['stellar_address']): ?>
    <div style="text-align:center;padding:16px 0">
      <div style="width:56px;height:56px;background:var(--accent-bg);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px">
        <i class="ti ti-wallet" style="font-size:26px;color:var(--accent)"></i>
      </div>
      <span class="badge badge-success" style="margin-bottom:10px"><i class="ti ti-check"></i> Linked</span>
      <div style="font-family:var(--mono);font-size:12px;color:var(--text-muted);background:var(--bg);padding:10px 14px;border-radius:var(--radius);word-break:break-all;margin:10px 0">
        <?= htmlspecialchars($student['stellar_address']) ?>
      </div>
      <button class="btn btn-sm btn-outline" onclick="copyText('<?= htmlspecialchars($student['stellar_address']) ?>', this)">
        <i class="ti ti-copy"></i> Copy address
      </button>
      <div style="margin-top:12px">
        <a href="https://stellar.expert/explorer/testnet/account/<?= urlencode($student['stellar_address']) ?>"
           target="_blank" class="btn btn-sm btn-outline">
          <i class="ti ti-external-link"></i> View on Explorer
        </a>
      </div>
    </div>
    <?php else: ?>
    <div class="empty-state">
      <div class="empty-icon"><i class="ti ti-wallet"></i></div>
      <p>No wallet linked yet. Submit your Stellar address below.</p>
    </div>
    <?php endif; ?>
  </div>

  <!-- Update wallet -->
  <div class="card">
    <div class="card-header"><span class="card-title">Update Wallet Address</span></div>
    <form method="POST">
      <div class="form-group">
        <label class="form-label">Stellar Public Key <span class="required">*</span></label>
        <input type="text" id="stellar_address" name="stellar_address" class="form-control mono"
               value="<?= htmlspecialchars($student['stellar_address'] ?? '') ?>"
               placeholder="GBSZ..." required>
        <div class="form-hint" id="wallet-hint">56-character address starting with G</div>
      </div>
      <div class="alert alert-info" style="font-size:12.5px">
        <i class="ti ti-info-circle"></i>
        <div>Make sure your wallet has a <strong>USDC trustline</strong> set up on Stellar, otherwise disbursements will fail.</div>
      </div>
      <button type="submit" class="btn btn-primary">
        <i class="ti ti-check"></i> Save Wallet Address
      </button>
    </form>
  </div>

</div>

<!-- Wallets guide -->
<div class="card" style="margin-top:20px">
  <div class="card-header"><span class="card-title">Setting up your Stellar wallet</span></div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;padding-top:4px">
    <div>
      <div style="font-weight:500;font-size:13.5px;margin-bottom:8px"><i class="ti ti-brand-chrome" style="color:var(--accent)"></i> Freighter (Browser Extension)</div>
      <ol style="font-size:13px;color:var(--text-muted);padding-left:18px;line-height:2">
        <li>Install Freighter from <a href="https://freighter.app" target="_blank">freighter.app</a></li>
        <li>Create a new wallet and save your seed phrase</li>
        <li>Switch to Testnet in Settings</li>
        <li>Add USDC trustline via the Assets tab</li>
        <li>Copy your public key and paste it above</li>
      </ol>
    </div>
    <div>
      <div style="font-weight:500;font-size:13.5px;margin-bottom:8px"><i class="ti ti-device-mobile" style="color:var(--success)"></i> LOBSTR (Mobile App)</div>
      <ol style="font-size:13px;color:var(--text-muted);padding-left:18px;line-height:2">
        <li>Download LOBSTR from <a href="https://lobstr.co" target="_blank">lobstr.co</a></li>
        <li>Create an account and secure your keys</li>
        <li>Search for USDC and add it as an asset</li>
        <li>Go to Settings → Wallet address</li>
        <li>Copy your Stellar address and paste it above</li>
      </ol>
    </div>
  </div>
</div>

<?php renderFooter(); ?>
