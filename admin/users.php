<?php

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/middleware/csrf.php';
require_once __DIR__ . '/_layout.php';

Auth::requireLogin();
Auth::requireRole('superadmin');

$message = ''; $msgType = 'success';
$currentUserId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validateRequest();
    $fa = $_POST['form_action'] ?? '';

    if ($fa === 'add_user') {
        $username  = preg_replace('/[^a-zA-Z0-9_]/', '', trim($_POST['username'] ?? ''));
        $password  = $_POST['password'] ?? '';
        $email     = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: '';
        $fullName  = mb_substr(trim($_POST['full_name'] ?? ''), 0, 100);
        $role      = in_array($_POST['role'] ?? '', ['admin', 'superadmin']) ? $_POST['role'] : 'admin';

        if (empty($username) || empty($password)) {
            $message = 'Username dan password wajib diisi.'; $msgType = 'error';
        } elseif (strlen($password) < 8) {
            $message = 'Password minimal 8 karakter.'; $msgType = 'error';
        } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^a-zA-Z0-9]/', $password)) {
            $message = 'Password harus mengandung huruf besar, angka, dan simbol.'; $msgType = 'error';
        } else {
            $existing = db()->fetchOne("SELECT id FROM users WHERE username = ?", [$username]);
            if ($existing) { $message = 'Username sudah digunakan.'; $msgType = 'error'; }
            else {
                db()->insert('users', [
                    'username' => $username, 'password_hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
                    'email' => $email, 'full_name' => $fullName, 'role' => $role,
                ]);
                $message = 'Pengguna berhasil ditambahkan.';
            }
        }
    }

    elseif ($fa === 'change_password') {
        $userId  = (int)($_POST['user_id'] ?? 0);
        $newPass = $_POST['new_password'] ?? '';
        if ($userId <= 0) { $message = 'User tidak valid.'; $msgType = 'error'; }
        elseif (strlen($newPass) < 8) { $message = 'Password minimal 8 karakter.'; $msgType = 'error'; }
        else {
            db()->query("UPDATE users SET password_hash = ? WHERE id = ?", [password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]), $userId]);
            $message = 'Password berhasil diubah.';
        }
    }

    elseif ($fa === 'delete_user') {
        $delId = (int)($_POST['delete_id'] ?? 0);
        if ($delId == $currentUserId) { $message = 'Tidak bisa menghapus akun sendiri.'; $msgType = 'error'; }
        elseif ($delId > 0) { db()->delete('users', 'id = ?', [$delId]); $message = 'Pengguna berhasil dihapus.'; }
    }

    elseif ($fa === 'unlock_user') {
        $unlockId = (int)($_POST['unlock_id'] ?? 0);
        if ($unlockId > 0) {
            db()->query("UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?", [$unlockId]);
            $message = 'Akun berhasil dibuka kembali.';
        }
    }
}

$users = db()->fetchAll("SELECT id, username, full_name, email, role, created_at, last_login, failed_attempts, locked_until FROM users ORDER BY id");

renderAdminHeader('Kelola Pengguna');
?>

<?php if ($message): ?>
<div class="admin-alert <?= $msgType ?>">
  <i class="bi bi-<?= $msgType === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?>"></i>
  <?= htmlspecialchars($message, ENT_QUOTES) ?>
</div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="admin-card">
      <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-people me-2"></i>Pengguna Admin</h2>
      </div>
      <div style="overflow-x:auto">
        <table class="admin-table">
          <thead><tr><th>Username</th><th>Nama</th><th>Role</th><th>Login Terakhir</th><th>Status</th><th class="no-sort">Aksi</th></tr></thead>
          <tbody>
            <?php foreach ($users as $u): ?>
            <tr <?= $u['id'] == $currentUserId ? 'style="background:rgba(26,58,107,.03)"' : '' ?>>
              <td>
                <div style="font-weight:700"><?= htmlspecialchars($u['username'], ENT_QUOTES) ?></div>
                <?php if ($u['id'] == $currentUserId): ?><span class="badge-status badge-berita" style="font-size:.7rem">Anda</span><?php endif; ?>
              </td>
              <td>
                <div><?= htmlspecialchars($u['full_name'] ?? '-', ENT_QUOTES) ?></div>
                <div style="font-size:.8rem;color:var(--muted)"><?= htmlspecialchars($u['email'] ?? '', ENT_QUOTES) ?></div>
              </td>
              <td><span class="badge-status <?= $u['role'] === 'superadmin' ? 'badge-pengumuman' : 'badge-berita' ?>"><?= ucfirst($u['role']) ?></span></td>
              <td style="font-size:.84rem;color:var(--muted)"><?= $u['last_login'] ? date('d M Y H:i', strtotime($u['last_login'])) : 'Belum pernah' ?></td>
              <td>
                <?php if ($u['locked_until'] && strtotime($u['locked_until']) > time()): ?>
                <span class="badge-status" style="background:rgba(220,38,38,.1);color:var(--danger)">Terkunci</span>
                <?php else: ?>
                <span class="badge-status badge-published">Aktif</span>
                <?php endif; ?>
              </td>
              <td style="white-space:nowrap">
                <?php if ($u['locked_until'] && strtotime($u['locked_until']) > time()): ?>
                <form method="POST" style="display:inline">
                  <?= CSRF::field() ?>
                  <input type="hidden" name="form_action" value="unlock_user">
                  <input type="hidden" name="unlock_id" value="<?= $u['id'] ?>">
                  <button type="submit" class="btn-admin-edit me-1" title="Buka Kunci"><i class="bi bi-unlock"></i></button>
                </form>
                <?php endif; ?>
                <button onclick="showChangePwd(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>')"
                  class="btn-admin-edit me-1" title="Ganti Password"><i class="bi bi-key"></i></button>
                <?php if ($u['id'] != $currentUserId): ?>
                <form method="POST" style="display:inline" onsubmit="return confirm('Hapus pengguna ini?')">
                  <?= CSRF::field() ?>
                  <input type="hidden" name="form_action" value="delete_user">
                  <input type="hidden" name="delete_id" value="<?= $u['id'] ?>">
                  <button type="submit" class="btn-admin-danger"><i class="bi bi-trash"></i></button>
                </form>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    
    <div class="admin-card mb-3">
      <div class="admin-card-header"><h2 class="admin-card-title">Tambah Pengguna</h2></div>
      <div class="admin-card-body">
        <form method="POST">
          <?= CSRF::field() ?>
          <input type="hidden" name="form_action" value="add_user">
          <div class="admin-form-group"><label class="admin-label">Username <span class="required">*</span></label>
            <input type="text" name="username" class="admin-input" required maxlength="50" placeholder="huruf, angka, underscore">
          </div>
          <div class="admin-form-group"><label class="admin-label">Nama Lengkap</label>
            <input type="text" name="full_name" class="admin-input" maxlength="100">
          </div>
          <div class="admin-form-group"><label class="admin-label">Email</label>
            <input type="email" name="email" class="admin-input" maxlength="100">
          </div>
          <div class="admin-form-group"><label class="admin-label">Password <span class="required">*</span></label>
            <input type="password" name="password" class="admin-input" required minlength="8" placeholder="Min 8 karakter, harus ada huruf besar, angka, simbol">
          </div>
          <div class="admin-form-group"><label class="admin-label">Role</label>
            <select name="role" class="admin-select">
              <option value="admin">Admin</option>
              <option value="superadmin">Super Admin</option>
            </select>
          </div>
          <button type="submit" class="btn-admin-primary w-100"><i class="bi bi-person-plus me-1"></i> Tambah Pengguna</button>
        </form>
      </div>
    </div>

    <div class="admin-card" id="changePwdCard" style="display:none">
      <div class="admin-card-header"><h2 class="admin-card-title" id="changePwdTitle">Ganti Password</h2></div>
      <div class="admin-card-body">
        <form method="POST">
          <?= CSRF::field() ?>
          <input type="hidden" name="form_action" value="change_password">
          <input type="hidden" name="user_id" id="changePwdUserId">
          <div class="admin-form-group">
            <label class="admin-label">Password Baru <span class="required">*</span></label>
            <input type="password" name="new_password" class="admin-input" required minlength="8">
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn-admin-primary"><i class="bi bi-key me-1"></i> Ganti</button>
            <button type="button" onclick="document.getElementById('changePwdCard').style.display='none'" class="btn-admin-secondary">Batal</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function showChangePwd(userId, username) {
  document.getElementById('changePwdUserId').value = userId;
  document.getElementById('changePwdTitle').textContent = 'Ganti Password: ' + username;
  document.getElementById('changePwdCard').style.display = 'block';
  document.getElementById('changePwdCard').scrollIntoView({ behavior: 'smooth' });
}
</script>

<?php renderAdminFooter(); ?>
