// assets/js/app.js — ScholarPay frontend JS

// Flash message utility
function showFlash(msg, type = 'success') {
  const el = document.getElementById('flash-msg');
  if (!el) return;
  const icons = { success: 'ti-check', danger: 'ti-x', warning: 'ti-alert-triangle', info: 'ti-info-circle' };
  el.className = `alert alert-${type} show`;
  el.innerHTML = `<i class="ti ${icons[type] || 'ti-info-circle'}"></i><span>${msg}</span>`;
  setTimeout(() => { el.classList.remove('show'); }, 3500);
}

// Copy to clipboard
function copyText(text, btn) {
  navigator.clipboard.writeText(text).then(() => {
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="ti ti-check"></i>';
    setTimeout(() => { btn.innerHTML = orig; }, 1400);
  });
}

// Confirm before destructive actions
document.addEventListener('DOMContentLoaded', () => {
  // Auto-hide flash alerts already in DOM
  document.querySelectorAll('.alert[data-autohide]').forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity 0.4s';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 400);
    }, 3000);
  });

  // Stellar address formatter: show shortened with copy button
  document.querySelectorAll('[data-stellar]').forEach(el => {
    const addr = el.dataset.stellar;
    if (!addr) return;
    const short = addr.slice(0, 6) + '...' + addr.slice(-6);
    el.innerHTML = `<span class="stellar-addr" title="${addr}">${short}</span>
      <button class="btn btn-sm btn-outline" style="padding:2px 7px;margin-left:4px;" onclick="copyText('${addr}', this)" title="Copy address">
        <i class="ti ti-copy"></i>
      </button>`;
  });

  // Tx hash formatter
  document.querySelectorAll('[data-txhash]').forEach(el => {
    const hash = el.dataset.txhash;
    if (!hash) return;
    const short = hash.slice(0, 8) + '...' + hash.slice(-8);
    const explorerUrl = `https://stellar.expert/explorer/testnet/tx/${hash}`;
    el.innerHTML = `<a href="${explorerUrl}" target="_blank" class="hash-chip" title="${hash}">${short} <i class="ti ti-external-link" style="font-size:10px"></i></a>`;
  });
});

// Stellar wallet address validation (basic)
function isValidStellarAddress(address) {
  return /^G[A-Z2-7]{55}$/.test(address);
}

// Amount formatter (USDC 7 decimals stored as cents)
function formatUSDC(raw) {
  return (parseFloat(raw) / 10000000).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Live wallet validation feedback
const walletInput = document.getElementById('stellar_address');
if (walletInput) {
  walletInput.addEventListener('input', function() {
    const hint = document.getElementById('wallet-hint');
    if (!hint) return;
    const val = this.value.trim();
    if (!val) { hint.textContent = ''; return; }
    if (isValidStellarAddress(val)) {
      hint.innerHTML = '<span style="color:var(--success)"><i class="ti ti-check"></i> Valid Stellar address</span>';
    } else {
      hint.innerHTML = '<span style="color:var(--danger)"><i class="ti ti-alert-circle"></i> Must start with G and be 56 characters</span>';
    }
  });
}

// Disbursement amount live preview
const amountInput = document.getElementById('amount_usdc');
if (amountInput) {
  amountInput.addEventListener('input', function() {
    const preview = document.getElementById('amount-preview');
    if (!preview) return;
    const val = parseFloat(this.value) || 0;
    preview.textContent = `= ${val.toFixed(2)} USDC on Stellar`;
  });
}
