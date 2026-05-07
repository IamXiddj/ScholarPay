// assets/js/app.js — ScholarPay frontend client

const API = '/api';

// ── Token / Auth ──────────────────────────────────────────────
export function getToken()  { return localStorage.getItem('sp_token'); }
export function getUser()   { 
    const u = localStorage.getItem('sp_user');
    return u ? JSON.parse(u) : null;
}
export function saveAuth(token, user) {
    localStorage.setItem('sp_token', token);
    localStorage.setItem('sp_user', JSON.stringify(user));
}
export function clearAuth() {
    localStorage.removeItem('sp_token');
    localStorage.removeItem('sp_user');
}
export function isLoggedIn() { return !!getToken(); }
export function isAdmin()    { return getUser()?.role === 'admin'; }
export function isStudent()  { return getUser()?.role === 'student'; }

// Redirect if not logged in
export function requireLogin() {
    if (!isLoggedIn()) { window.location.href = '/'; return false; }
    return true;
}
export function requireAdminPage() {
    if (!requireLogin()) return false;
    if (!isAdmin()) { window.location.href = '/student/dashboard'; return false; }
    return true;
}
export function requireStudentPage() {
    if (!requireLogin()) return false;
    if (!isStudent()) { window.location.href = '/admin/dashboard'; return false; }
    return true;
}

// ── API Fetch wrapper ─────────────────────────────────────────
export async function apiFetch(path, options = {}) {
    const token = getToken();
    const res = await fetch(`${API}${path}`, {
        ...options,
        headers: {
            'Content-Type': 'application/json',
            ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
            ...(options.headers || {})
        },
        body: options.body ? JSON.stringify(options.body) : undefined
    });

    if (res.status === 401) {
        clearAuth();
        window.location.href = '/';
        return null;
    }

    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Request failed');
    return data;
}

// ── Flash messages ────────────────────────────────────────────
export function showFlash(msg, type = 'success') {
    let el = document.getElementById('flash-msg');
    if (!el) {
        el = document.createElement('div');
        el.id = 'flash-msg';
        document.body.appendChild(el);
    }
    const icons = { success:'ti-check', danger:'ti-x', warning:'ti-alert-triangle', info:'ti-info-circle' };
    el.className = `alert alert-${type} show`;
    el.innerHTML = `<i class="ti ${icons[type]||'ti-info-circle'}"></i><span>${msg}</span>`;
    setTimeout(() => el.classList.remove('show'), 3500);
}

// ── Formatters ────────────────────────────────────────────────
export function formatUSDC(raw) {
    return (raw / 10000000).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

export function formatDate(str) {
    return new Date(str).toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' });
}

export function formatDateTime(str) {
    return new Date(str).toLocaleString('en-US', { month:'short', day:'numeric', year:'numeric', hour:'2-digit', minute:'2-digit' });
}

export function shortAddr(addr) {
    if (!addr) return '—';
    return addr.slice(0,6) + '...' + addr.slice(-6);
}

export function shortHash(hash) {
    if (!hash) return '—';
    return hash.slice(0,8) + '...' + hash.slice(-8);
}

export function isValidStellarAddress(addr) {
    return /^G[A-Z2-7]{55}$/.test(addr);
}

// ── Copy to clipboard ─────────────────────────────────────────
export function copyText(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="ti ti-check"></i>';
        setTimeout(() => btn.innerHTML = orig, 1400);
    });
}

// ── Sidebar user chip ─────────────────────────────────────────
export function renderUserChip() {
    const user = getUser();
    if (!user) return;
    const initials = user.name.split(' ').map(p => p[0]).join('').slice(0,2).toUpperCase();
    const chip = document.getElementById('user-chip');
    if (chip) {
        chip.querySelector('.user-avatar').textContent   = initials;
        chip.querySelector('.user-chip-name').textContent = user.name;
        chip.querySelector('.user-chip-role').textContent = user.role;
    }
}

// ── Logout ────────────────────────────────────────────────────
export async function logout() {
    try { await apiFetch('/logout', { method: 'POST' }); } catch {}
    clearAuth();
    window.location.href = '/';
}

// ── Badge helper ──────────────────────────────────────────────
export function statusBadge(status) {
    const map = { confirmed:'badge-success', pending:'badge-warning', failed:'badge-danger', active:'badge-success', inactive:'badge-neutral', depleted:'badge-danger' };
    return `<span class="badge ${map[status]||'badge-neutral'}">${status.charAt(0).toUpperCase()+status.slice(1)}</span>`;
}

// ── TX hash chip ──────────────────────────────────────────────
export function txHashChip(hash) {
    if (!hash) return '—';
    const url = `https://stellar.expert/explorer/testnet/tx/${hash}`;
    return `<a href="${url}" target="_blank" class="hash-chip" title="${hash}">${shortHash(hash)} <i class="ti ti-external-link" style="font-size:10px"></i></a>`;
}

// ── Stellar address chip ──────────────────────────────────────
export function stellarChip(addr, copyable = true) {
    if (!addr) return '<span style="color:var(--text-hint);font-size:12px">No wallet</span>';
    const short = shortAddr(addr);
    const copy  = copyable
        ? `<button class="btn btn-sm btn-outline" style="padding:2px 7px;margin-left:4px;" onclick="import('/assets/js/app.js').then(m=>m.copyText('${addr}',this))" title="Copy"><i class="ti ti-copy"></i></button>`
        : '';
    return `<span class="stellar-addr" title="${addr}">${short}</span>${copy}`;
}

// Expose logout globally for onclick use in HTML
window.spLogout = logout;
window.spCopy   = copyText;
