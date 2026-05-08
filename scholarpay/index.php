<?php
define('ROOT', __DIR__);
require_once ROOT . '/includes/auth.php';

// If already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    $dest = isAdmin() ? '/scholarpay/admin/dashboard.php' : '/scholarpay/student/dashboard.php';
    header("Location: $dest");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once ROOT . '/includes/db.php';
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } else {
        $user = login($email, $password);
        if ($user) {
            $dest = ($user['role'] === 'admin') ? '/scholarpay/admin/dashboard.php' : '/scholarpay/student/dashboard.php';
            header("Location: $dest");
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ScholarPay — On-chain Scholarships</title>
<link rel="stylesheet" href="/scholarpay/assets/css/app.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<style>
body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: var(--bg); }

.login-wrap {
  width: 100%;
  max-width: 440px;
  padding: 24px;
}

.login-brand {
  text-align: center;
  margin-bottom: 32px;
}

.login-brand-icon {
  width: 52px; height: 52px;
  background: var(--stellar);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 22px;
  font-weight: 700;
  margin: 0 auto 12px;
}

.login-brand h1 {
  font-size: 22px;
  font-weight: 600;
  letter-spacing: -0.4px;
}

.login-brand p {
  font-size: 13.5px;
  color: var(--text-muted);
  margin-top: 4px;
}

.login-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 20px;
  padding: 28px;
  box-shadow: var(--shadow-md);
}

.login-card h2 {
  font-size: 16px;
  font-weight: 600;
  margin-bottom: 20px;
}

.demo-accounts {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid var(--border);
}

.demo-accounts p {
  font-size: 12px;
  color: var(--text-hint);
  margin-bottom: 10px;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  font-weight: 600;
}

.demo-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 10px 12px;
  cursor: pointer;
  font-family: var(--font);
  font-size: 13px;
  color: var(--text);
  margin-bottom: 8px;
  transition: background 0.12s;
  text-align: left;
}

.demo-btn:hover { background: var(--accent-bg); border-color: var(--accent-dim); }

.demo-btn .role-badge {
  font-size: 10px;
  font-weight: 600;
  padding: 2px 7px;
  border-radius: 10px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  flex-shrink: 0;
}

.stellar-features {
  margin-top: 24px;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.feature-chip {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 10px 12px;
  font-size: 12px;
}

.feature-chip .icon { font-size: 18px; margin-bottom: 4px; color: var(--accent); }
.feature-chip strong { display: block; font-weight: 500; font-size: 12.5px; }
.feature-chip span { color: var(--text-muted); }
</style>
</head>
<body>
<div class="login-wrap">
  <div class="login-brand">
    <div class="login-brand-icon">S</div>
    <h1>ScholarPay</h1>
    <p>On-chain scholarship disbursement on Stellar</p>
  </div>

  <div class="login-card">
    <h2>Sign in to your account</h2>

    <?php if ($error): ?>
    <div class="alert alert-danger" data-autohide>
      <i class="ti ti-alert-circle"></i>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label class="form-label" for="email">Email address <span class="required">*</span></label>
        <input type="email" id="email" name="email" class="form-control"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               placeholder="you@example.com" required autofocus>
      </div>
      <div class="form-group">
        <label class="form-label" for="password">Password <span class="required">*</span></label>
        <input type="password" id="password" name="password" class="form-control"
               placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary btn-lg" style="width:100%;margin-top:4px;">
        <i class="ti ti-login"></i> Sign In
      </button>
    </form>

    <div class="demo-accounts">
      <p>Demo accounts</p>
      <button class="demo-btn" onclick="fillDemo('admin@scholarpay.org', 'password')">
        <i class="ti ti-shield-check" style="font-size:18px;color:var(--accent)"></i>
        <div style="flex:1">
          <strong style="font-size:13px;font-weight:500">Admin User</strong>
          <div style="font-size:11.5px;color:var(--text-muted)">admin@scholarpay.org</div>
        </div>
        <span class="role-badge badge-info">Admin</span>
      </button>
      <button class="demo-btn" onclick="fillDemo('student@scholarpay.org', 'password')">
        <i class="ti ti-user-graduate" style="font-size:18px;color:var(--success)"></i>
        <div style="flex:1">
          <strong style="font-size:13px;font-weight:500">Maria Santos</strong>
          <div style="font-size:11.5px;color:var(--text-muted)">student@scholarpay.org</div>
        </div>
        <span class="role-badge badge-success">Student</span>
      </button>
      <p style="font-size:11px;color:var(--text-hint);margin-top:6px">Password: <code style="background:var(--bg);padding:1px 5px;border-radius:4px">password</code></p>
    </div>
  </div>

  <div class="stellar-features">
    <div class="feature-chip">
      <div class="icon"><i class="ti ti-bolt"></i></div>
      <strong>3–5 sec finality</strong>
      <span>Stellar Testnet</span>
    </div>
    <div class="feature-chip">
      <div class="icon"><i class="ti ti-currency-dollar"></i></div>
      <strong>USDC payments</strong>
      <span>SEP-41 token</span>
    </div>
    <div class="feature-chip">
      <div class="icon"><i class="ti ti-lock"></i></div>
      <strong>Audit trail</strong>
      <span>On-chain immutable</span>
    </div>
    <div class="feature-chip">
      <div class="icon"><i class="ti ti-world"></i></div>
      <strong>Global access</strong>
      <span>No bank needed</span>
    </div>
  </div>
</div>

<script src="/scholarpay/assets/js/app.js"></script>
<script>
function fillDemo(email, pass) {
  document.getElementById('email').value = email;
  document.getElementById('password').value = pass;
}
</script>
</body>
</html>
