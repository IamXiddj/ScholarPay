<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Settings — ScholarPay</title>
<link rel="stylesheet" href="/assets/css/app.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
</head>
<body>
<div id="app"><div style="display:flex;align-items:center;justify-content:center;height:100vh"><div class="spinner"></div></div></div>
<script type="module">
import { requireAdminPage, getUser, saveAuth, getToken, apiFetch, showFlash } from '/assets/js/app.js';
import { pageShell } from '/assets/js/layout.js';

if (!requireAdminPage()) throw new Error('redirect');

let currentUser = getUser();

function render(successMsg = '', errorMsg = '') {
  document.getElementById('app').innerHTML = pageShell('admin', 'settings', `
    <div class="page-header">
      <h1 class="page-title">Settings</h1>
      <p class="page-subtitle">Manage your account profile and security.</p>
    </div>

    ${successMsg ? `<div class="alert alert-success" id="flash-inline"><i class="ti ti-check"></i> ${successMsg}</div>` : ''}
    ${errorMsg   ? `<div class="alert alert-danger"  id="flash-inline"><i class="ti ti-x"></i> ${errorMsg}</div>` : ''}

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

      <!-- Profile -->
      <div class="card">
        <div class="card-header"><span class="card-title">Profile</span></div>
        <div class="form-group">
          <label class="form-label">Full Name <span class="required">*</span></label>
          <input type="text" id="p-name" class="form-control" value="${currentUser.name || ''}">
        </div>
        <div class="form-group">
          <label class="form-label">Email <span class="required">*</span></label>
          <input type="email" id="p-email" class="form-control" value="${currentUser.email || ''}">
        </div>
        <div class="form-group">
          <label class="form-label">Institution</label>
          <input type="text" id="p-inst" class="form-control" value="${currentUser.institution || ''}" placeholder="e.g. ScholarPay Foundation">
        </div>
        <button class="btn btn-primary" id="profile-btn" onclick="saveProfile()">
          <i class="ti ti-check"></i> Save Profile
        </button>
      </div>

      <!-- Change Password -->
      <div class="card">
        <div class="card-header"><span class="card-title">Change Password</span></div>
        <div class="form-group">
          <label class="form-label">Current Password <span class="required">*</span></label>
          <input type="password" id="cp-current" class="form-control" placeholder="••••••••">
        </div>
        <div class="form-group">
          <label class="form-label">New Password <span class="required">*</span></label>
          <input type="password" id="cp-new" class="form-control" placeholder="At least 6 characters">
        </div>
        <div class="form-group">
          <label class="form-label">Confirm New Password <span class="required">*</span></label>
          <input type="password" id="cp-confirm" class="form-control" placeholder="Repeat new password">
        </div>
        <button class="btn btn-primary" id="pass-btn" onclick="changePassword()">
          <i class="ti ti-lock"></i> Change Password
        </button>
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
             target="_blank" class="hash-chip">CA2EY3LL6E…H6JUU <i class="ti ti-external-link" style="font-size:10px"></i></a>
        </p>
        <p style="margin-top:8px">
          <strong>Explorer:</strong>
          <a href="https://stellar.expert/explorer/testnet/tx/1b9a54974c38ddc0944e76180f966d4c94b1130f1c1ac635ec1316d147d3b7b0"
             target="_blank" class="btn btn-sm btn-outline" style="margin-left:8px">
            <i class="ti ti-external-link"></i> View Tx on Stellar Expert
          </a>
        </p>
        <p style="margin-top:8px"><strong>Stack:</strong> HTML · CSS · JavaScript · Node.js · Supabase · Vercel · Stellar Testnet · Soroban</p>
        <p style="margin-top:8px"><strong>Version:</strong> 1.0.0</p>
      </div>
    </div>
  `);

  // Auto-hide flash
  const flash = document.getElementById('flash-inline');
  if (flash) setTimeout(() => { flash.style.opacity='0'; flash.style.transition='opacity .4s'; setTimeout(()=>flash.remove(), 400); }, 3000);
}

window.saveProfile = async () => {
  const name        = document.getElementById('p-name')?.value?.trim();
  const email       = document.getElementById('p-email')?.value?.trim();
  const institution = document.getElementById('p-inst')?.value?.trim();

  if (!name || !email) return showFlash('Name and email are required.', 'danger');

  const btn = document.getElementById('profile-btn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Saving…';

  try {
    await apiFetch('/settings', { method: 'PATCH', body: { action: 'update_profile', name, email, institution } });

    // Update local session
    currentUser = { ...currentUser, name, email, institution };
    saveAuth(getToken(), currentUser);

    showFlash('Profile updated successfully!');
    render();
  } catch(e) {
    showFlash(e.message, 'danger');
    btn.disabled = false;
    btn.innerHTML = '<i class="ti ti-check"></i> Save Profile';
  }
};

window.changePassword = async () => {
  const current_password  = document.getElementById('cp-current')?.value;
  const new_password      = document.getElementById('cp-new')?.value;
  const confirm_password  = document.getElementById('cp-confirm')?.value;

  if (!current_password || !new_password || !confirm_password)
    return showFlash('All password fields are required.', 'danger');
  if (new_password.length < 6)
    return showFlash('New password must be at least 6 characters.', 'danger');
  if (new_password !== confirm_password)
    return showFlash('New passwords do not match.', 'danger');

  const btn = document.getElementById('pass-btn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Updating…';

  try {
    await apiFetch('/settings', { method: 'PATCH', body: { action: 'change_password', current_password, new_password } });
    showFlash('Password changed successfully!');
    document.getElementById('cp-current').value = '';
    document.getElementById('cp-new').value = '';
    document.getElementById('cp-confirm').value = '';
    btn.disabled = false;
    btn.innerHTML = '<i class="ti ti-lock"></i> Change Password';
  } catch(e) {
    showFlash(e.message, 'danger');
    btn.disabled = false;
    btn.innerHTML = '<i class="ti ti-lock"></i> Change Password';
  }
};

render();
</script>
</body>
</html>
