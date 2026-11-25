<?php

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/middleware/csrf.php';
require_once dirname(__DIR__) . '/middleware/rate_limit.php';

if (Auth::isLoggedIn()) {
    header('Location: dashboard');
    exit;
}

$error = '';
$success = '';

if (isset($_GET['msg'])) {
    $messages = [
        'session_expired' => 'Sesi Anda telah berakhir. Silakan login kembali.',
        'logged_out'      => 'Anda telah berhasil keluar.',
        'unauthorized'    => 'Anda tidak memiliki akses ke halaman tersebut.',
    ];
    $error = $messages[$_GET['msg']] ?? '';
    if ($_GET['msg'] === 'logged_out') { $success = $messages[$_GET['msg']]; $error = ''; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    RateLimit::enforce('login', MAX_LOGIN_ATTEMPTS, LOCKOUT_DURATION);
    CSRF::validateRequest();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi.';
    } else {
        $result = Auth::login($username, $password);
        if ($result['success']) {
            header('Location: dashboard');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Login - <?= getSetting('site_name', 'Polda Bengkulu') ?></title>
  <meta name="robots" content="noindex, nofollow">
  <?= CSRF::meta() ?>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <div class="login-logo">
      <img src="../assets/img/logo-polri.png" alt="Logo Polri" onerror="this.style.display='none'">
    </div>
    <h1 class="login-title">Panel Admin</h1>
    <p class="login-subtitle"><?= htmlspecialchars(getSetting('site_name', 'Polda Bengkulu'), ENT_QUOTES) ?></p>

    <?php if ($error): ?>
    <div class="admin-alert error">
      <i class="bi bi-exclamation-triangle-fill"></i>
      <?= htmlspecialchars($error, ENT_QUOTES) ?>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="admin-alert success">
      <i class="bi bi-check-circle-fill"></i>
      <?= htmlspecialchars($success, ENT_QUOTES) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="" id="loginForm" novalidate autocomplete="off">
      <?= CSRF::field() ?>

      <div class="admin-form-group">
        <label class="admin-label" for="login-username">Username <span class="required">*</span></label>
        <div style="position:relative">
          <i class="bi bi-person" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none"></i>
          <input
            type="text"
            id="login-username"
            name="username"
            class="admin-input"
            style="padding-left:2.75rem"
            placeholder="Masukkan username"
            value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES) ?>"
            required
            autocomplete="username"
            maxlength="50"
          >
        </div>
      </div>

      <div class="admin-form-group">
        <label class="admin-label" for="login-password">Password <span class="required">*</span></label>
        <div style="position:relative">
          <i class="bi bi-lock" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none"></i>
          <input
            type="password"
            id="login-password"
            name="password"
            class="admin-input"
            style="padding-left:2.75rem;padding-right:3rem"
            placeholder="Masukkan password"
            required
            autocomplete="current-password"
            maxlength="128"
          >
          <button type="button" id="togglePassword" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;cursor:pointer;font-size:1rem" aria-label="Tampilkan password">
            <i class="bi bi-eye" id="eyeIcon"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-admin-primary w-100" style="justify-content:center;padding:.85rem;font-size:.98rem;margin-top:.5rem" id="btn-login-submit">
        <i class="bi bi-box-arrow-in-right"></i> Masuk ke Panel Admin
      </button>
    </form>

    <div style="text-align:center;margin-top:1.2rem">
      <a href="../" style="font-size:.85rem;color:var(--muted);font-weight:600">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Halaman Utama
      </a>
    </div>
  </div>
</div>

<script>
  
  const toggleBtn = document.getElementById('togglePassword');
  const pwInput = document.getElementById('login-password');
  const eyeIcon = document.getElementById('eyeIcon');
  if (toggleBtn) {
    toggleBtn.addEventListener('click', function() {
      const isPass = pwInput.type === 'password';
      pwInput.type = isPass ? 'text' : 'password';
      eyeIcon.className = isPass ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
  }

  const loginForm = document.getElementById('loginForm');
  if (loginForm) {
    loginForm.addEventListener('submit', function() {
      const btn = document.getElementById('btn-login-submit');
      setTimeout(() => {
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Memverifikasi...';
      }, 50);
    });
  }
</script>
</body>
</html>
