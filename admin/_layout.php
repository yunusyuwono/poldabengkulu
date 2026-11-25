<?php

function renderAdminHeader(string $pageTitle, array $breadcrumbs = []): void {
    $siteName = getSetting('site_name', 'Polda Bengkulu');
    $user = Auth::getCurrentUser();
    $userName = (string)($user['full_name'] ?? $user['username'] ?? 'A');
    $initial = strtoupper(mb_substr($userName, 0, 1));
    $currentFile = basename($_SERVER['PHP_SELF'], '.php');
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES) ?> - Admin <?= htmlspecialchars($siteName, ENT_QUOTES) ?></title>
  <meta name="robots" content="noindex, nofollow">
  <?= \CSRF::meta() ?>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
  <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <img src="../assets/img/logo-polri.png" alt="Logo" class="sidebar-logo" onerror="this.style.display='none'">
    <div>
      <div class="sidebar-brand"><?= htmlspecialchars($siteName, ENT_QUOTES) ?></div>
      <div class="sidebar-brand-sub">Panel Admin</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Utama</div>
    <a href="dashboard" class="sidebar-link <?= $currentFile === 'dashboard' ? 'active' : '' ?>">
      <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="nav-section-label">Konten</div>
    <a href="posts" class="sidebar-link <?= $currentFile === 'posts' ? 'active' : '' ?>">
      <i class="bi bi-newspaper"></i> Berita &amp; Pengumuman
    </a>
    <a href="layanan" class="sidebar-link <?= $currentFile === 'layanan' ? 'active' : '' ?>">
      <i class="bi bi-grid-1x2"></i> Layanan Publik
    </a>
    <a href="satker" class="sidebar-link <?= $currentFile === 'satker' ? 'active' : '' ?>">
      <i class="bi bi-diagram-3"></i> Satker &amp; Satwil
    </a>
    <a href="media" class="sidebar-link <?= $currentFile === 'media' ? 'active' : '' ?>">
      <i class="bi bi-images"></i> Galeri / Media
    </a>

    <div class="nav-section-label">Pengaturan</div>
    <a href="settings" class="sidebar-link <?= $currentFile === 'settings' ? 'active' : '' ?>">
      <i class="bi bi-sliders"></i> Pengaturan Situs
    </a>
    <?php if (($user['role'] ?? '') === 'superadmin'): ?>
    <a href="users" class="sidebar-link <?= $currentFile === 'users' ? 'active' : '' ?>">
      <i class="bi bi-people"></i> Kelola Pengguna
    </a>
    <?php endif; ?>

    <div class="nav-section-label">Aksi Cepat</div>
    <a href="../" target="_blank" class="sidebar-link">
      <i class="bi bi-box-arrow-up-right"></i> Lihat Website
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="sidebar-avatar"><?= htmlspecialchars($initial, ENT_QUOTES) ?></div>
      <div>
        <div class="sidebar-user-name"><?= htmlspecialchars((string)($user['full_name'] ?? $user['username'] ?? 'Admin'), ENT_QUOTES) ?></div>
        <div class="sidebar-user-role"><?= htmlspecialchars(ucfirst((string)($user['role'] ?? 'Admin')), ENT_QUOTES) ?></div>
      </div>
    </div>
    <a href="logout" class="sidebar-link" style="color:rgba(239,68,68,.75)" id="sidebar-logout-btn">
      <i class="bi bi-box-arrow-right"></i> Keluar
    </a>
  </div>
</aside>

<div class="admin-main">
  
  <header class="topbar">
    <button class="hamburger-btn" id="hamburgerBtn" aria-label="Toggle Sidebar">
      <i class="bi bi-list"></i>
    </button>
    <div class="topbar-title">
      <?= htmlspecialchars($pageTitle, ENT_QUOTES) ?>
      <?php if ($breadcrumbs): ?>
      <span style="font-size:.8rem;color:var(--muted);font-weight:600;margin-left:.5rem">
        / <?= implode(' / ', array_map(fn($b) => htmlspecialchars($b, ENT_QUOTES), $breadcrumbs)) ?>
      </span>
      <?php endif; ?>
    </div>
    <div class="topbar-actions">
      <a href="../" target="_blank" class="topbar-btn" id="topbar-view-site">
        <i class="bi bi-eye"></i> <span class="d-none d-md-inline">Lihat Situs</span>
      </a>
      <a href="logout" class="topbar-btn danger" id="topbar-logout-btn">
        <i class="bi bi-box-arrow-right"></i> <span class="d-none d-md-inline">Keluar</span>
      </a>
    </div>
  </header>

  <main class="page-content">
<?php
}

function renderAdminFooter(): void {
    ?>
  </main>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="../assets/js/admin.js"></script>
</body>
</html>
<?php
}

function renderAlert(string $type, string $message): string {
    $icons = ['success' => 'check-circle-fill', 'error' => 'exclamation-triangle-fill', 'warning' => 'exclamation-circle-fill', 'info' => 'info-circle-fill'];
    $icon = $icons[$type] ?? 'info-circle-fill';
    return '<div class="admin-alert ' . htmlspecialchars($type, ENT_QUOTES) . '"><i class="bi bi-' . $icon . '"></i>' . htmlspecialchars($message, ENT_QUOTES) . '</div>';
}

function getFlashMessage(): array {
    $flash = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flash;
}

function setFlashMessage(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function slugify(?string $text): string {
    $text = mb_strtolower((string)$text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9\s-]/', '', (string)$text);
    $text = preg_replace('/[\s-]+/', '-', (string)$text);
    return trim((string)$text, '-');
}

function sanitizeInput(?string $input, int $maxLen = 1000): string {
    return htmlspecialchars(mb_substr(trim(strip_tags((string)$input)), 0, $maxLen), ENT_QUOTES, 'UTF-8');
}

function validateRequired(array $fields, array $data): array {
    $errors = [];
    foreach ($fields as $field => $label) {
        if (empty(trim($data[$field] ?? ''))) {
            $errors[] = "$label wajib diisi.";
        }
    }
    return $errors;
}
