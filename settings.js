/* ScholarPay — Global Styles */
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=DM+Mono:wght@400;500&display=swap');

:root {
  --bg:         #f7f6f3;
  --surface:    #ffffff;
  --border:     #e4e2db;
  --border-mid: #cac8c0;
  --text:       #1a1917;
  --text-muted: #6b6960;
  --text-hint:  #9c9a92;
  --accent:     #1a5cff;
  --accent-bg:  #eef2ff;
  --accent-dim: #dae2ff;
  --success:    #16a34a;
  --success-bg: #f0fdf4;
  --danger:     #dc2626;
  --danger-bg:  #fef2f2;
  --warning:    #d97706;
  --warning-bg: #fffbeb;
  --stellar:    #0040ff;
  --usdc:       #2775ca;
  --radius:     10px;
  --radius-lg:  16px;
  --shadow:     0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
  --shadow-md:  0 4px 16px rgba(0,0,0,0.08);
  --font:       'DM Sans', sans-serif;
  --mono:       'DM Mono', monospace;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

html { font-size: 15px; }

body {
  font-family: var(--font);
  background: var(--bg);
  color: var(--text);
  line-height: 1.6;
  min-height: 100vh;
}

/* ── Sidebar Layout ── */
.app-layout {
  display: flex;
  min-height: 100vh;
}

.sidebar {
  width: 240px;
  flex-shrink: 0;
  background: var(--surface);
  border-right: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0; left: 0;
  height: 100vh;
  z-index: 100;
  overflow-y: auto;
}

.sidebar-logo {
  padding: 24px 20px 20px;
  border-bottom: 1px solid var(--border);
}

.sidebar-logo .brand {
  font-size: 17px;
  font-weight: 600;
  letter-spacing: -0.3px;
  color: var(--text);
  display: flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
}

.sidebar-logo .brand-icon {
  width: 28px; height: 28px;
  background: var(--stellar);
  border-radius: 7px;
  display: flex; align-items: center; justify-content: center;
  color: white;
  font-size: 14px;
  font-weight: 700;
  flex-shrink: 0;
}

.sidebar-logo .brand-sub {
  font-size: 11px;
  color: var(--text-hint);
  font-weight: 400;
  letter-spacing: 0;
  margin-top: 2px;
}

.sidebar-section {
  padding: 12px 12px 4px;
}

.sidebar-section-label {
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--text-hint);
  padding: 0 8px;
  margin-bottom: 4px;
}

.sidebar-nav {
  list-style: none;
}

.sidebar-nav li a {
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 8px 10px;
  border-radius: var(--radius);
  color: var(--text-muted);
  text-decoration: none;
  font-size: 13.5px;
  font-weight: 400;
  transition: background 0.12s, color 0.12s;
}

.sidebar-nav li a:hover {
  background: var(--bg);
  color: var(--text);
}

.sidebar-nav li a.active {
  background: var(--accent-bg);
  color: var(--accent);
  font-weight: 500;
}

.sidebar-nav li a .nav-icon {
  font-size: 16px;
  width: 18px;
  text-align: center;
  flex-shrink: 0;
}

.sidebar-footer {
  margin-top: auto;
  padding: 16px 12px;
  border-top: 1px solid var(--border);
}

.user-chip {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 10px;
  border-radius: var(--radius);
  background: var(--bg);
}

.user-avatar {
  width: 30px; height: 30px;
  border-radius: 50%;
  background: var(--accent);
  color: white;
  font-size: 12px;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.user-chip-info {
  flex: 1;
  overflow: hidden;
}

.user-chip-name {
  font-size: 12.5px;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-chip-role {
  font-size: 11px;
  color: var(--text-hint);
  text-transform: capitalize;
}

/* ── Main Content ── */
.main-content {
  margin-left: 240px;
  flex: 1;
  padding: 32px;
  max-width: 1100px;
}

.page-header {
  margin-bottom: 28px;
}

.page-title {
  font-size: 22px;
  font-weight: 600;
  letter-spacing: -0.4px;
  color: var(--text);
}

.page-subtitle {
  font-size: 13.5px;
  color: var(--text-muted);
  margin-top: 3px;
}

/* ── Cards ── */
.card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 20px 24px;
  box-shadow: var(--shadow);
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 18px;
}

.card-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--text);
}

/* ── Stat Cards ── */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 14px;
  margin-bottom: 28px;
}

.stat-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 18px 20px;
}

.stat-label {
  font-size: 11.5px;
  color: var(--text-muted);
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin-bottom: 6px;
}

.stat-value {
  font-size: 26px;
  font-weight: 600;
  letter-spacing: -0.5px;
  color: var(--text);
  line-height: 1;
}

.stat-value.usdc::after {
  content: ' USDC';
  font-size: 13px;
  font-weight: 400;
  color: var(--text-muted);
}

.stat-sub {
  font-size: 11.5px;
  color: var(--text-hint);
  margin-top: 4px;
}

/* ── Tables ── */
.table-wrap {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13.5px;
}

thead th {
  text-align: left;
  padding: 10px 14px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--text-muted);
  border-bottom: 1px solid var(--border);
  white-space: nowrap;
}

tbody td {
  padding: 12px 14px;
  border-bottom: 1px solid var(--border);
  vertical-align: middle;
}

tbody tr:last-child td { border-bottom: none; }
tbody tr:hover td { background: var(--bg); }

/* ── Badges ── */
.badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 9px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 500;
  line-height: 1.4;
}

.badge-success  { background: var(--success-bg); color: var(--success); }
.badge-danger   { background: var(--danger-bg);  color: var(--danger); }
.badge-warning  { background: var(--warning-bg); color: var(--warning); }
.badge-info     { background: var(--accent-bg);  color: var(--accent); }
.badge-neutral  { background: var(--bg);         color: var(--text-muted); border: 1px solid var(--border); }

/* ── Buttons ── */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: var(--radius);
  font-size: 13.5px;
  font-weight: 500;
  font-family: var(--font);
  cursor: pointer;
  border: 1px solid transparent;
  transition: all 0.12s;
  text-decoration: none;
  white-space: nowrap;
}

.btn:active { transform: scale(0.98); }

.btn-primary {
  background: var(--accent);
  color: white;
  border-color: var(--accent);
}

.btn-primary:hover { background: #1248d4; border-color: #1248d4; }

.btn-outline {
  background: white;
  color: var(--text);
  border-color: var(--border-mid);
}

.btn-outline:hover { background: var(--bg); border-color: var(--border-mid); }

.btn-danger {
  background: var(--danger);
  color: white;
  border-color: var(--danger);
}

.btn-sm {
  padding: 5px 11px;
  font-size: 12px;
}

.btn-lg {
  padding: 11px 22px;
  font-size: 15px;
}

/* ── Forms ── */
.form-group {
  margin-bottom: 16px;
}

.form-label {
  display: block;
  font-size: 13px;
  font-weight: 500;
  color: var(--text);
  margin-bottom: 6px;
}

.form-label .required { color: var(--danger); margin-left: 2px; }

.form-control {
  width: 100%;
  padding: 9px 13px;
  border: 1px solid var(--border-mid);
  border-radius: var(--radius);
  font-size: 14px;
  font-family: var(--font);
  color: var(--text);
  background: var(--surface);
  transition: border-color 0.12s, box-shadow 0.12s;
  outline: none;
}

.form-control:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(26, 92, 255, 0.1);
}

.form-control.mono {
  font-family: var(--mono);
  font-size: 12.5px;
}

.form-hint {
  font-size: 11.5px;
  color: var(--text-hint);
  margin-top: 4px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

select.form-control { cursor: pointer; }

textarea.form-control {
  resize: vertical;
  min-height: 80px;
}

/* ── Alert ── */
.alert {
  padding: 12px 16px;
  border-radius: var(--radius);
  font-size: 13.5px;
  margin-bottom: 16px;
  display: flex;
  align-items: flex-start;
  gap: 10px;
}

.alert-success { background: var(--success-bg); color: var(--success); border: 1px solid #bbf7d0; }
.alert-danger  { background: var(--danger-bg);  color: var(--danger);  border: 1px solid #fecaca; }
.alert-warning { background: var(--warning-bg); color: var(--warning); border: 1px solid #fde68a; }
.alert-info    { background: var(--accent-bg);  color: var(--accent);  border: 1px solid var(--accent-dim); }

/* ── Hash chip ── */
.hash-chip {
  font-family: var(--mono);
  font-size: 11px;
  color: var(--usdc);
  background: #eef5ff;
  padding: 2px 8px;
  border-radius: 4px;
  display: inline-block;
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* ── Stellar chip ── */
.stellar-addr {
  font-family: var(--mono);
  font-size: 11px;
  color: var(--text-muted);
  background: var(--bg);
  padding: 3px 8px;
  border-radius: 4px;
  border: 1px solid var(--border);
  display: inline-block;
  max-width: 160px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* ── Divider ── */
.divider {
  border: none;
  border-top: 1px solid var(--border);
  margin: 20px 0;
}

/* ── Spinner ── */
@keyframes spin { to { transform: rotate(360deg); } }
.spinner {
  width: 18px; height: 18px;
  border: 2px solid var(--border);
  border-top-color: var(--accent);
  border-radius: 50%;
  display: inline-block;
  animation: spin 0.7s linear infinite;
}

/* ── Flash message ── */
#flash-msg {
  position: fixed;
  top: 20px;
  right: 24px;
  z-index: 999;
  min-width: 260px;
  max-width: 400px;
  opacity: 0;
  transform: translateY(-8px);
  transition: all 0.2s;
  pointer-events: none;
}

#flash-msg.show {
  opacity: 1;
  transform: translateY(0);
  pointer-events: auto;
}

/* ── Stellar badge ── */
.stellar-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 10px;
  background: linear-gradient(135deg, #0040ff11, #2775ca11);
  border: 1px solid #2775ca33;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 500;
  color: var(--usdc);
}

/* ── Responsive ── */
@media (max-width: 768px) {
  .sidebar {
    transform: translateX(-240px);
    transition: transform 0.2s;
  }
  .sidebar.open { transform: translateX(0); }
  .main-content { margin-left: 0; padding: 20px 16px; }
  .form-row { grid-template-columns: 1fr; }
}

/* ── Empty state ── */
.empty-state {
  text-align: center;
  padding: 48px 24px;
  color: var(--text-muted);
}

.empty-state .empty-icon {
  font-size: 36px;
  margin-bottom: 12px;
  opacity: 0.4;
}

.empty-state p {
  font-size: 14px;
  color: var(--text-hint);
}
