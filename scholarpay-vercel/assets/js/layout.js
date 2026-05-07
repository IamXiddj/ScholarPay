// assets/js/layout.js — renders the sidebar and page shell
import { getUser, logout, renderUserChip } from '/assets/js/app.js';

export function renderSidebar(role, activeKey) {
  const adminNav = [
    { key:'dashboard',    label:'Dashboard',    icon:'ti-layout-dashboard', href:'/admin/dashboard' },
    { key:'scholarships', label:'Scholarships', icon:'ti-award',            href:'/admin/scholarships' },
    { key:'disburse',     label:'Disburse',     icon:'ti-send',             href:'/admin/disburse' },
    { key:'students',     label:'Students',     icon:'ti-users',            href:'/admin/students' },
    { key:'audit',        label:'Audit Log',    icon:'ti-list-search',      href:'/admin/audit' },
    { key:'settings',     label:'Settings',     icon:'ti-settings',         href:'/admin/settings' },
  ];
  const studentNav = [
    { key:'dashboard',     label:'Dashboard',       icon:'ti-layout-dashboard', href:'/student/dashboard' },
    { key:'disbursements', label:'My Disbursements', icon:'ti-cash',            href:'/student/disbursements' },
    { key:'wallet',        label:'My Wallet',        icon:'ti-wallet',          href:'/student/wallet' },
  ];

  const nav = role === 'admin' ? adminNav : studentNav;
  const user = getUser();
  const initials = (user?.name || 'U').split(' ').map(p=>p[0]).join('').slice(0,2).toUpperCase();

  const navHtml = nav.map(item => `
    <li><a href="${item.href}" class="${activeKey===item.key?'active':''}">
      <span class="nav-icon"><i class="ti ${item.icon}"></i></span>${item.label}
    </a></li>
  `).join('');

  return `
  <aside class="sidebar">
    <div class="sidebar-logo">
      <a href="/" class="brand">
        <div class="brand-icon">S</div>
        <div>ScholarPay<div class="brand-sub">Stellar · USDC</div></div>
      </a>
    </div>
    <div class="sidebar-section">
      <div class="sidebar-section-label">${role==='admin'?'Admin':'Student'} Menu</div>
      <ul class="sidebar-nav">${navHtml}</ul>
    </div>
    <div class="sidebar-footer">
      <div class="user-chip" id="user-chip">
        <div class="user-avatar">${initials}</div>
        <div class="user-chip-info">
          <div class="user-chip-name">${user?.name||''}</div>
          <div class="user-chip-role">${user?.role||''}</div>
        </div>
      </div>
      <button onclick="window.spLogout()" class="btn btn-outline btn-sm" style="width:100%;margin-top:8px;justify-content:center;">
        <i class="ti ti-logout"></i> Logout
      </button>
    </div>
  </aside>`;
}

export function pageShell(role, activeKey, bodyHtml) {
  return `
  <div class="app-layout">
    ${renderSidebar(role, activeKey)}
    <main class="main-content">${bodyHtml}</main>
  </div>
  <div id="flash-msg"></div>`;
}
