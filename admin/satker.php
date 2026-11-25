<?php

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/middleware/csrf.php';
require_once __DIR__ . '/_layout.php';

Auth::requireLogin();

$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);
$message = '';
$msgType = 'success';
$editItem = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validateRequest();
    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'save') {
        $name   = mb_substr(trim($_POST['name'] ?? ''), 0, 150);
        $type   = in_array($_POST['type'] ?? '', ['polres', 'satker']) ? $_POST['type'] : 'satker';
        $desc   = mb_substr(trim($_POST['description'] ?? ''), 0, 500);
        $address= mb_substr(trim($_POST['address'] ?? ''), 0, 200);
        $phone  = mb_substr(trim($_POST['phone'] ?? ''), 0, 50);
        $icon   = preg_replace('/[^a-z0-9\-]/', '', $_POST['icon'] ?? 'bi-building');
        $order  = (int)($_POST['order_num'] ?? 0);
        $active = isset($_POST['active']) ? 1 : 0;
        $id     = (int)($_POST['id'] ?? 0);

        if (empty($name)) {
            $message = 'Nama satker wajib diisi.'; $msgType = 'error';
        } else {
            if ($id > 0) {
                db()->query("UPDATE satkers SET name=?, type=?, description=?, address=?, phone=?, icon=?, order_num=?, active=? WHERE id=?",
                    [$name, $type, $desc, $address, $phone, $icon, $order, $active, $id]);
                $message = 'Satker berhasil diperbarui.';
            } else {
                db()->insert('satkers', ['name'=>$name,'type'=>$type,'description'=>$desc,'address'=>$address,'phone'=>$phone,'icon'=>'bi-'.$icon,'order_num'=>$order,'active'=>$active]);
                $message = 'Satker berhasil ditambahkan.';
            }
            $action = 'list';
        }
    } elseif ($formAction === 'delete') {
        $delId = (int)($_POST['delete_id'] ?? 0);
        if ($delId > 0) { db()->delete('satkers', 'id = ?', [$delId]); $message = 'Satker berhasil dihapus.'; }
    } elseif ($formAction === 'toggle') {
        $togId = (int)($_POST['toggle_id'] ?? 0);
        if ($togId > 0) {
            db()->query("UPDATE satkers SET active = CASE WHEN active = 1 THEN 0 ELSE 1 END WHERE id = ?", [$togId]);
            $message = 'Status satker diperbarui.';
        }
    }
}

if ($action === 'edit' && $editId > 0) {
    $editItem = db()->fetchOne("SELECT * FROM satkers WHERE id = ?", [$editId]);
    if (!$editItem) $action = 'list';
}

$satkers = db()->fetchAll("SELECT * FROM satkers ORDER BY type, order_num, name");

$icons = ['bi-building','bi-building-fill','bi-shield-fill','bi-people-fill','bi-search','bi-car-front','bi-megaphone','bi-briefcase','bi-shield-lock','bi-gear','bi-bank','bi-hospital','bi-person-badge'];

renderAdminHeader('Kelola Satker');
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
        <h2 class="admin-card-title"><i class="bi bi-diagram-3 me-2"></i>Satker &amp; Satwil</h2>
        <a href="?action=new" class="btn-admin-primary" id="btn-add-satker"><i class="bi bi-plus-lg"></i> Tambah</a>
      </div>
      <div style="overflow-x:auto">
        <table class="admin-table">
          <thead><tr><th>Nama</th><th>Tipe</th><th>Telp</th><th>Urutan</th><th>Status</th><th class="no-sort">Aksi</th></tr></thead>
          <tbody>
            <?php foreach ($satkers as $sk): ?>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:.6rem">
                  <span style="color:var(--primary);font-size:1.1rem"><i class="bi <?= htmlspecialchars($sk['icon'], ENT_QUOTES) ?>"></i></span>
                  <div>
                    <div style="font-weight:700"><?= htmlspecialchars($sk['name'], ENT_QUOTES) ?></div>
                    <div style="font-size:.78rem;color:var(--muted)"><?= htmlspecialchars(mb_substr($sk['description'] ?? '', 0, 60), ENT_QUOTES) ?>...</div>
                  </div>
                </div>
              </td>
              <td><span class="badge-status <?= $sk['type'] === 'polres' ? 'badge-berita' : 'badge-pengumuman' ?>"><?= ucfirst($sk['type']) ?></span></td>
              <td style="font-size:.85rem;color:var(--muted)"><?= htmlspecialchars($sk['phone'] ?: '-', ENT_QUOTES) ?></td>
              <td style="text-align:center"><?= $sk['order_num'] ?></td>
              <td>
                <form method="POST" style="display:inline">
                  <?= CSRF::field() ?>
                  <input type="hidden" name="form_action" value="toggle">
                  <input type="hidden" name="toggle_id" value="<?= $sk['id'] ?>">
                  <button type="submit" class="badge-status <?= $sk['active'] ? 'badge-published' : 'badge-draft' ?>" style="border:none;cursor:pointer;background:none">
                    <?= $sk['active'] ? 'Aktif' : 'Nonaktif' ?>
                  </button>
                </form>
              </td>
              <td style="white-space:nowrap">
                <a href="?action=edit&id=<?= $sk['id'] ?>" class="btn-admin-edit me-1"><i class="bi bi-pencil"></i></a>
                <form method="POST" style="display:inline" onsubmit="return confirm('Hapus satker ini?')">
                  <?= CSRF::field() ?>
                  <input type="hidden" name="form_action" value="delete">
                  <input type="hidden" name="delete_id" value="<?= $sk['id'] ?>">
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
      <div class="admin-card-header">
        <h2 class="admin-card-title"><?= $action === 'edit' ? 'Edit Satker' : 'Tambah Satker' ?></h2>
      </div>
      <div class="admin-card-body">
        <form method="POST">
          <?= CSRF::field() ?>
          <input type="hidden" name="form_action" value="save">
          <input type="hidden" name="id" value="<?= $editItem['id'] ?? 0 ?>">

          <div class="admin-form-group">
            <label class="admin-label">Nama Satker <span class="required">*</span></label>
            <input type="text" name="name" class="admin-input" value="<?= htmlspecialchars($editItem['name'] ?? '', ENT_QUOTES) ?>" required maxlength="150">
          </div>
          <div class="admin-form-group">
            <label class="admin-label">Tipe</label>
            <select name="type" class="admin-select">
              <option value="polres" <?= ($editItem['type'] ?? '') === 'polres' ? 'selected' : '' ?>>Polres</option>
              <option value="satker" <?= ($editItem['type'] ?? 'satker') === 'satker' ? 'selected' : '' ?>>Satker Fungsi</option>
            </select>
          </div>
          <div class="admin-form-group">
            <label class="admin-label">Deskripsi</label>
            <textarea name="description" class="admin-textarea" rows="3" maxlength="500"><?= htmlspecialchars($editItem['description'] ?? '', ENT_QUOTES) ?></textarea>
          </div>
          <div class="admin-form-group">
            <label class="admin-label">Alamat</label>
            <input type="text" name="address" class="admin-input" value="<?= htmlspecialchars($editItem['address'] ?? '', ENT_QUOTES) ?>" maxlength="200">
          </div>
          <div class="admin-form-group">
            <label class="admin-label">Telepon</label>
            <input type="text" name="phone" class="admin-input" value="<?= htmlspecialchars($editItem['phone'] ?? '', ENT_QUOTES) ?>" maxlength="50">
          </div>
          <div class="admin-form-group">
            <label class="admin-label">Icon (Bootstrap Icons)</label>
            <select name="icon" class="admin-select">
              <?php foreach ($icons as $icon): ?>
              <option value="<?= $icon ?>" <?= ($editItem['icon'] ?? '') === $icon ? 'selected' : '' ?>><?= $icon ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="admin-form-group">
            <label class="admin-label">Urutan</label>
            <input type="number" name="order_num" class="admin-input" value="<?= $editItem['order_num'] ?? 0 ?>" min="0">
          </div>
          <div class="admin-form-group">
            <label class="toggle-switch">
              <input type="checkbox" class="toggle-input" name="active" <?= ($editItem['active'] ?? 1) ? 'checked' : '' ?>>
              <span class="toggle-track"></span>
              <span class="toggle-thumb"></span>
              <span style="font-weight:600;font-size:.9rem">Aktif / Tampilkan</span>
            </label>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn-admin-primary"><i class="bi bi-save me-1"></i> Simpan</button>
            <a href="satker" class="btn-admin-secondary">Batal</a>
          </div>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php renderAdminFooter(); ?>
