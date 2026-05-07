<?php
// includes/layout.php
// Usage: require_once ROOT . '/includes/layout.php'; then call renderLayout('page title', 'admin|student', 'active-nav-key');
function getInitials($name) {
    $parts = explode(' ', trim($name));
    $initials = '';
    foreach ($parts as $p) { $initials .= strtoupper(substr($p, 0, 1)); }
    return substr($initials, 0, 2);
}

function renderHead($title = 'ScholarPay') {
    $base = '/scholarpay';
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$title} — ScholarPay</title>
<link rel="stylesheet" href="{$base}/assets/css/app.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
</head>
<body>
HTML;
}

function renderSidebar($role, $activeKey = '') {
    $user = getCurrentUser();
    $initials = getInitials($user['name']);
    $base = '/scholarpay';

    $adminNav = [
        'dashboard'   => ['Dashboard',     'ti-layout-dashboard', "{$base}/admin/dashboard.php"],
        'scholarships'=> ['Scholarships',   'ti-award',            "{$base}/admin/scholarships.php"],
        'disburse'    => ['Disburse',       'ti-send',             "{$base}/admin/disburse.php"],
        'students'    => ['Students',       'ti-users',            "{$base}/admin/students.php"],
        'audit'       => ['Audit Log',      'ti-list-search',      "{$base}/admin/audit.php"],
        'settings'    => ['Settings',       'ti-settings',         "{$base}/admin/settings.php"],
    ];

    $studentNav = [
        'dashboard'   => ['Dashboard',         'ti-layout-dashboard', "{$base}/student/dashboard.php"],
        'disbursements'=> ['My Disbursements', 'ti-cash',             "{$base}/student/disbursements.php"],
        'wallet'      => ['My Wallet',          'ti-wallet',           "{$base}/student/wallet.php"],
    ];

    $nav = ($role === 'admin') ? $adminNav : $studentNav;
    $roleLabel = ($role === 'admin') ? 'Admin' : 'Student';
    $logoutUrl = "{$base}/logout.php";

    $navHtml = '';
    foreach ($nav as $key => [$label, $icon, $href]) {
        $active = ($activeKey === $key) ? 'active' : '';
        $navHtml .= "<li><a href=\"{$href}\" class=\"{$active}\"><span class=\"nav-icon\"><i class=\"ti {$icon}\"></i></span>{$label}</a></li>";
    }

    echo <<<HTML
<div class="app-layout">
<aside class="sidebar">
  <div class="sidebar-logo">
    <a href="{$base}/index.php" class="brand">
      <div class="brand-icon">S</div>
      <div>
        ScholarPay
        <div class="brand-sub">Stellar · USDC</div>
      </div>
    </a>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-section-label">{$roleLabel} Menu</div>
    <ul class="sidebar-nav">
      {$navHtml}
    </ul>
  </div>
  <div class="sidebar-footer">
    <div class="user-chip">
      <div class="user-avatar">{$initials}</div>
      <div class="user-chip-info">
        <div class="user-chip-name">{$user['name']}</div>
        <div class="user-chip-role">{$user['role']}</div>
      </div>
    </div>
    <a href="{$logoutUrl}" class="btn btn-outline btn-sm" style="width:100%;margin-top:8px;justify-content:center;">
      <i class="ti ti-logout"></i> Logout
    </a>
  </div>
</aside>
<main class="main-content">
HTML;
}

function renderFooter() {
    echo <<<HTML
</main>
</div>
<div id="flash-msg"></div>
<script src="/scholarpay/assets/js/app.js"></script>
</body>
</html>
HTML;
}
