<?php

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/middleware/csrf.php';
require_once __DIR__ . '/_layout.php';

Auth::requireLogin();

$action  = $_GET['action'] ?? 'list';
$editId  = (int)($_GET['id'] ?? 0);
$message = ''; $msgType = 'success'; $editItem = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validateRequest();
    $fa = $_POST['form_action'] ?? '';

    if ($fa === 'save') {
        $name   = mb_substr(trim($_POST['name'] ?? ''), 0, 100);
        $desc   = mb_substr(trim($_POST['description'] ?? ''), 0, 300);
        $icon   = preg_replace('/[^a-z0-9\-]/', '', $_POST['icon'] ?? 'bi-grid');
        $link   = filter_var(trim($_POST['link'] ?? ''), FILTER_SANITIZE_URL);
        $order  = (int)($_POST['order_num'] ?? 0);
        $active = isset($_POST['active']) ? 1 : 0;
        $id     = (int)($_POST['id'] ?? 0);

        if (empty($name)) { $message = 'Nama layanan wajib diisi.'; $msgType = 'error'; }
        else {
            if ($id > 0) {
                db()->query("UPDATE layanan SET name=?, description=?, icon=?, link=?, order_num=?, active=? WHERE id=?",
                    [$name, $desc, 'bi-'.$icon, $link, $order, $active, $id]);
                $message = 'Layanan berhasil diperbarui.';
            } else {
                db()->insert('layanan', ['name'=>$name,'description'=>$desc,'icon'=>'bi-'.$icon,'link'=>$link,'order_num'=>$order,'active'=>$active,'category'=>'layanan']);
                $message = 'Layanan berhasil ditambahkan.';
            }
            $action = 'list';
        }
    } elseif ($fa === 'delete') {
        $delId = (int)($_POST['delete_id'] ?? 0);
        if ($delId > 0) { db()->delete('layanan', 'id = ?', [$delId]); $message = 'Layanan berhasil dihapus.'; }
    }
}

if ($action === 'edit' && $editId > 0) {
    $editItem = db()->fetchOne("SELECT * FROM layanan WHERE id = ?", [$editId]);
    if (!$editItem) $action = 'list';
}

$layanans = db()->fetchAll("SELECT * FROM layanan ORDER BY order_num, name");
$icons = ['grid','file-earmark-check','card-text','truck','chat-dots','people-fill','search','file-earmark-text','shield-check','telephone','megaphone','info-circle','geo-alt'];

renderAdminHeader('Kelola Layanan');
?>

<?php if ($message): ?>
<div class="admin-alert <?= $msgType ?>">
  <i class="bi bi-<?= $msgType === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?>"></i>
  <?= htmlspecialchars($message, ENT_QUOTES) ?>
</div>
<?php endif; ?>

<div class="row g-3">
  <div class="<?= ($action === 'new' || $action === 'edit') ? 'col-lg-7 col-xl-8' : 'col-12' ?>">
    <div class="admin-card">
      <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-grid-1x2 me-2"></i>Layanan Publik</h2>
        <a href="?action=new" class="btn-admin-primary"><i class="bi bi-plus-lg"></i> Tambah</a>
      </div>
      <div style="overflow-x:auto">
        <table class="admin-table">
          <thead><tr><th>Nama</th><th>Deskripsi</th><th>Link</th><th>Urutan</th><th>Aktif</th><th class="no-sort">Aksi</th></tr></thead>
          <tbody>
            <?php foreach ($layanans as $lay): ?>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:.6rem">
                  <span style="color:var(--primary);font-size:1.1rem"><i class="bi <?= htmlspecialchars($lay['icon'], ENT_QUOTES) ?>"></i></span>
                  <span style="font-weight:700"><?= htmlspecialchars($lay['name'], ENT_QUOTES) ?></span>
                </div>
              </td>
              <td style="max-width:200px;font-size:.85rem;color:var(--muted)"><?= htmlspecialchars(mb_substr($lay['description'] ?? '', 0, 80), ENT_QUOTES) ?>...</td>
              <td style="font-size:.82rem">
                <?php if ($lay['link']): ?>
                <a href="<?= htmlspecialchars($lay['link'], ENT_QUOTES) ?>" target="_blank" rel="noopener" style="color:var(--primary)" class="text-truncate d-inline-block" style="max-width:120px">
                  <?= htmlspecialchars($lay['link'], ENT_QUOTES) ?>
                </a>
                <?php else: ?>-<?php endif; ?>
              </td>
              <td style="text-align:center"><?= $lay['order_num'] ?></td>
              <td><span class="badge-status <?= $lay['active'] ? 'badge-published' : 'badge-draft' ?>"><?= $lay['active'] ? 'Ya' : 'Tidak' ?></span></td>
              <td style="white-space:nowrap">
                <a href="?action=edit&id=<?= $lay['id'] ?>" class="btn-admin-edit me-1"><i class="bi bi-pencil"></i></a>
                <form method="POST" style="display:inline" onsubmit="return confirm('Hapus layanan ini?')">
                  <?= CSRF::field() ?>
                  <input type="hidden" name="form_action" value="delete">
                  <input type="hidden" name="delete_id" value="<?= $lay['id'] ?>">
                  <button type="submit" class="btn-admin-danger"><i class="bi bi-trash"></i></button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <?php if ($action === 'new' || $action === 'edit'): ?>
  <div class="col-lg-5 col-xl-4">
    <div class="admin-card">
      <div class="admin-card-header"><h2 class="admin-card-title"><?= $action === 'edit' ? 'Edit Layanan' : 'Tambah Layanan' ?></h2></div>
      <div class="admin-card-body">
        <form method="POST">
          <?= CSRF::field() ?>
          <input type="hidden" name="form_action" value="save">
          <input type="hidden" name="id" value="<?= $editItem['id'] ?? 0 ?>">

          <div class="admin-form-group">
            <label class="admin-label">Nama Layanan <span class="required">*</span></label>
            <input type="text" name="name" class="admin-input" value="<?= htmlspecialchars($editItem['name'] ?? '', ENT_QUOTES) ?>" required maxlength="100">
          </div>
          <div class="admin-form-group">
            <label class="admin-label">Deskripsi</label>
            <textarea name="description" class="admin-textarea" rows="3" maxlength="300"><?= htmlspecialchars($editItem['description'] ?? '', ENT_QUOTES) ?></textarea>
          </div>
          <div class="admin-form-group">
            <label class="admin-label">Icon</label>
            <select name="icon" class="admin-select">
              <?php foreach ($icons as $icon):
                $fullIcon = 'bi-' . $icon;
                $selected = ($editItem['icon'] ?? '') === $fullIcon ? 'selected' : '';
              ?>
              <option value="<?= $icon ?>" <?= $selected ?>><?= $fullIcon ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="admin-form-group">
            <label class="admin-label">Link URL</label>
            <input type="text" name="link" class="admin-input" value="<?= htmlspecialchars($editItem['link'] ?? '', ENT_QUOTES) ?>" placeholder="https:// atau #section" maxlength="200">
            <div class="admin-input-hint">Bisa URL eksternal atau anchor (#kontak, #profil)</div>
          </div>
          <div class="admin-form-group">
            <label class="admin-label">Urutan Tampil</label>
            <input type="number" name="order_num" class="admin-input" value="<?= $editItem['order_num'] ?? 0 ?>" min="0">
          </div>
          <div class="admin-form-group">
            <label class="toggle-switch">
              <input type="checkbox" class="toggle-input" name="active" <?= ($editItem['active'] ?? 1) ? 'checked' : '' ?>>
              <span class="toggle-track"></span>
              <span class="toggle-thumb"></span>
              <span style="font-weight:600;font-size:.9rem">Aktif</span>
            </label>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn-admin-primary"><i class="bi bi-save me-1"></i> Simpan</button>
            <a href="layanan" class="btn-admin-secondary">Batal</a>
          </div>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php renderAdminFooter(); ?>
