<?php

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/middleware/csrf.php';
require_once __DIR__ . '/_layout.php';

Auth::requireLogin();

$message = '';
$msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validateRequest();

    $section = $_POST['section'] ?? '';

    if ($section === 'general') {
        $fields = ['site_name', 'site_tagline', 'site_description', 'footer_text'];
        foreach ($fields as $f) {
            $val = mb_substr(trim($_POST[$f] ?? ''), 0, 500);
            db()->query("INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)", [$f, $val]);
        }
        $message = 'Pengaturan umum berhasil disimpan.';
    }

    elseif ($section === 'kapolda') {
        $fields = ['kapolda_name', 'kapolda_title', 'kapolda_quote'];
        foreach ($fields as $f) {
            $val = mb_substr(trim($_POST[$f] ?? ''), 0, 500);
            db()->query("INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)", [$f, $val]);
        }

        if (!empty($_FILES['kapolda_photo']['name'])) {
            $file = $_FILES['kapolda_photo'];
            if ($file['error'] === UPLOAD_ERR_OK && $file['size'] <= MAX_UPLOAD_SIZE) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);
                if (in_array($mime, ALLOWED_IMAGE_TYPES)) {
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $newName = 'kapolda_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], UPLOAD_PATH . '/' . $newName)) {
                        db()->query("INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)", ['kapolda_photo', 'uploads/' . $newName]);
                    }
                } else {
                    $message = 'Format foto tidak valid. Gunakan JPG/PNG.';
                    $msgType = 'error';
                }
            }
        }
        if ($msgType !== 'error') $message = 'Data Kapolda berhasil disimpan.';
    }

    elseif ($section === 'contact') {
        $fields = ['contact_address', 'contact_phone', 'contact_email', 'contact_whatsapp', 'contact_maps_embed'];
        foreach ($fields as $f) {
            $val = mb_substr(trim($_POST[$f] ?? ''), 0, 2000);
            db()->query("INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)", [$f, $val]);
        }
        $message = 'Informasi kontak berhasil disimpan.';
    }

    elseif ($section === 'social') {
        $fields = ['facebook_url', 'instagram_url', 'youtube_url', 'twitter_url'];
        foreach ($fields as $f) {
            $val = filter_var(trim($_POST[$f] ?? ''), FILTER_SANITIZE_URL);
            db()->query("INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)", [$f, $val]);
        }
        $message = 'Media sosial berhasil disimpan.';
    }

    elseif ($section === 'hero') {
        $fields = ['hero_stat_satker', 'hero_stat_polres', 'hero_stat_personil'];
        foreach ($fields as $f) {
            $val = mb_substr(trim($_POST[$f] ?? ''), 0, 50);
            db()->query("INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)", [$f, $val]);
        }
        $message = 'Statistik hero berhasil disimpan.';
    }
}

$g = fn(string $k) => htmlspecialchars(getSetting($k), ENT_QUOTES);

renderAdminHeader('Pengaturan Situs');
?>

<?php if ($message): ?>
<div class="admin-alert <?= $msgType ?>">
  <i class="bi bi-<?= $msgType === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?>"></i>
  <?= htmlspecialchars($message, ENT_QUOTES) ?>
</div>
<?php endif; ?>

<div class="admin-tab-nav" id="settingsTabs">
  <button type="button" class="admin-tab-btn active" data-tab="general" onclick="switchSettingTab('general', this)">
    <i class="bi bi-sliders"></i>
    <span>Umum</span>
  </button>
  <button type="button" class="admin-tab-btn" data-tab="kapolda" onclick="switchSettingTab('kapolda', this)">
    <i class="bi bi-person-badge"></i>
    <span>Kapolda</span>
  </button>
  <button type="button" class="admin-tab-btn" data-tab="contact" onclick="switchSettingTab('contact', this)">
    <i class="bi bi-telephone"></i>
    <span>Kontak</span>
  </button>
  <button type="button" class="admin-tab-btn" data-tab="social" onclick="switchSettingTab('social', this)">
    <i class="bi bi-share"></i>
    <span>Sosial Media</span>
  </button>
  <button type="button" class="admin-tab-btn" data-tab="hero" onclick="switchSettingTab('hero', this)">
    <i class="bi bi-bar-chart"></i>
    <span>Statistik Hero</span>
  </button>
</div>

<div class="settings-tab" id="tab-general">
  <div class="admin-card">
    <div class="admin-card-header"><h2 class="admin-card-title">Pengaturan Umum</h2></div>
    <div class="admin-card-body">
      <form method="POST">
        <?= CSRF::field() ?>
        <input type="hidden" name="section" value="general">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="admin-form-group">
              <label class="admin-label">Nama Situs</label>
              <input type="text" name="site_name" class="admin-input" value="<?= $g('site_name') ?>" maxlength="100">
            </div>
          </div>
          <div class="col-md-6">
            <div class="admin-form-group">
              <label class="admin-label">Tagline</label>
              <input type="text" name="site_tagline" class="admin-input" value="<?= $g('site_tagline') ?>" maxlength="150">
            </div>
          </div>
          <div class="col-12">
            <div class="admin-form-group">
              <label class="admin-label">Deskripsi Situs (SEO)</label>
              <textarea name="site_description" class="admin-textarea" rows="3" maxlength="300"><?= $g('site_description') ?></textarea>
              <div class="admin-input-hint">Digunakan untuk meta description di search engine.</div>
            </div>
          </div>
          <div class="col-12">
            <div class="admin-form-group mb-0">
              <label class="admin-label">Teks Footer</label>
              <input type="text" name="footer_text" class="admin-input" value="<?= $g('footer_text') ?>" maxlength="200">
            </div>
          </div>
        </div>
        <div class="mt-4">
          <button type="submit" class="btn-admin-primary"><i class="bi bi-save me-1"></i> Simpan Pengaturan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="settings-tab" id="tab-kapolda" style="display:none">
  <div class="admin-card">
    <div class="admin-card-header"><h2 class="admin-card-title">Data Kapolda</h2></div>
    <div class="admin-card-body">
      <form method="POST" enctype="multipart/form-data">
        <?= CSRF::field() ?>
        <input type="hidden" name="section" value="kapolda">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="admin-form-group">
              <label class="admin-label">Nama Lengkap &amp; Gelar</label>
              <input type="text" name="kapolda_name" class="admin-input" value="<?= $g('kapolda_name') ?>" maxlength="200">
            </div>
          </div>
          <div class="col-md-6">
            <div class="admin-form-group">
              <label class="admin-label">Jabatan</label>
              <input type="text" name="kapolda_title" class="admin-input" value="<?= $g('kapolda_title') ?>" maxlength="200">
            </div>
          </div>
          <div class="col-12">
            <div class="admin-form-group">
              <label class="admin-label">Kutipan / Pesan Kapolda</label>
              <textarea name="kapolda_quote" class="admin-textarea" rows="3" maxlength="400"><?= $g('kapolda_quote') ?></textarea>
            </div>
          </div>
          <div class="col-12">
            <div class="admin-form-group mb-0">
              <label class="admin-label">Foto Kapolda</label>
              <?php $kPhoto = getSetting('kapolda_photo'); ?>
              <?php if ($kPhoto): ?>
              <div class="mb-2">
                <img src="../<?= htmlspecialchars($kPhoto, ENT_QUOTES) ?>" alt="Kapolda" style="height:140px;border-radius:12px;object-fit:cover">
              </div>
              <?php endif; ?>
              <input type="file" name="kapolda_photo" class="admin-input" accept="image/*" style="padding:.5rem">
              <div class="admin-input-hint">JPG/PNG, maks 5MB. Ukuran ideal: portrait 3:4.</div>
            </div>
          </div>
        </div>
        <div class="mt-4">
          <button type="submit" class="btn-admin-primary"><i class="bi bi-save me-1"></i> Simpan Data Kapolda</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="settings-tab" id="tab-contact" style="display:none">
  <div class="admin-card">
    <div class="admin-card-header"><h2 class="admin-card-title">Informasi Kontak</h2></div>
    <div class="admin-card-body">
      <form method="POST">
        <?= CSRF::field() ?>
        <input type="hidden" name="section" value="contact">
        <div class="row g-3">
          <div class="col-12">
            <div class="admin-form-group">
              <label class="admin-label">Alamat</label>
              <textarea name="contact_address" class="admin-textarea" rows="2" maxlength="300"><?= $g('contact_address') ?></textarea>
            </div>
          </div>
          <div class="col-md-6">
            <div class="admin-form-group">
              <label class="admin-label">Nomor Telepon</label>
              <input type="text" name="contact_phone" class="admin-input" value="<?= $g('contact_phone') ?>" maxlength="50">
            </div>
          </div>
          <div class="col-md-6">
            <div class="admin-form-group">
              <label class="admin-label">Email</label>
              <input type="email" name="contact_email" class="admin-input" value="<?= $g('contact_email') ?>" maxlength="100">
            </div>
          </div>
          <div class="col-md-6">
            <div class="admin-form-group">
              <label class="admin-label">Nomor WhatsApp</label>
              <input type="text" name="contact_whatsapp" class="admin-input" value="<?= $g('contact_whatsapp') ?>" placeholder="08xxx" maxlength="20">
              <div class="admin-input-hint">Format: 081234567890 (tanpa + atau kode negara)</div>
            </div>
          </div>
          <div class="col-12">
            <div class="admin-form-group mb-0">
              <label class="admin-label">Embed Google Maps URL</label>
              <textarea name="contact_maps_embed" class="admin-textarea" rows="3" maxlength="2000"><?= $g('contact_maps_embed') ?></textarea>
              <div class="admin-input-hint">
                Buka <a href="https://maps.google.com" target="_blank" rel="noopener">Google Maps</a>, cari lokasi, klik Share → Embed a map, salin URL dari src="..." saja.
              </div>
            </div>
          </div>
        </div>
        <div class="mt-4">
          <button type="submit" class="btn-admin-primary"><i class="bi bi-save me-1"></i> Simpan Kontak</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="settings-tab" id="tab-social" style="display:none">
  <div class="admin-card">
    <div class="admin-card-header"><h2 class="admin-card-title">Media Sosial</h2></div>
    <div class="admin-card-body">
      <form method="POST">
        <?= CSRF::field() ?>
        <input type="hidden" name="section" value="social">
        <div class="row g-3">
          <?php
          $socials = ['facebook_url' => ['Facebook', 'bi-facebook'], 'instagram_url' => ['Instagram', 'bi-instagram'], 'youtube_url' => ['YouTube', 'bi-youtube'], 'twitter_url' => ['Twitter/X', 'bi-twitter-x']];
          foreach ($socials as $key => [$label, $icon]):
          ?>
          <div class="col-md-6">
            <div class="admin-form-group">
              <label class="admin-label"><i class="bi <?= $icon ?> me-1"></i><?= $label ?></label>
              <input type="url" name="<?= $key ?>" class="admin-input" value="<?= $g($key) ?>" placeholder="https://" maxlength="200">
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="mt-4">
          <button type="submit" class="btn-admin-primary"><i class="bi bi-save me-1"></i> Simpan Media Sosial</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="settings-tab" id="tab-hero" style="display:none">
  <div class="admin-card">
    <div class="admin-card-header"><h2 class="admin-card-title">Statistik Hero Section</h2></div>
    <div class="admin-card-body">
      <form method="POST">
        <?= CSRF::field() ?>
        <input type="hidden" name="section" value="hero">
        <div class="row g-3">
          <div class="col-md-4">
            <div class="admin-form-group">
              <label class="admin-label">Jumlah Satker Fungsi</label>
              <input type="text" name="hero_stat_satker" class="admin-input" value="<?= $g('hero_stat_satker') ?>" maxlength="10">
            </div>
          </div>
          <div class="col-md-4">
            <div class="admin-form-group">
              <label class="admin-label">Jumlah Polres</label>
              <input type="text" name="hero_stat_polres" class="admin-input" value="<?= $g('hero_stat_polres') ?>" maxlength="10">
            </div>
          </div>
          <div class="col-md-4">
            <div class="admin-form-group">
              <label class="admin-label">Jumlah Personil</label>
              <input type="text" name="hero_stat_personil" class="admin-input" value="<?= $g('hero_stat_personil') ?>" placeholder="3.500+" maxlength="20">
            </div>
          </div>
        </div>
        <div class="mt-4">
          <button type="submit" class="btn-admin-primary"><i class="bi bi-save me-1"></i> Simpan Statistik</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function switchSettingTab(tab, btn) {
  document.querySelectorAll('.settings-tab').forEach(t => t.style.display = 'none');
  const target = document.getElementById('tab-' + tab);
  if (target) target.style.display = 'block';
  
  document.querySelectorAll('#settingsTabs .admin-tab-btn').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');
}
</script>

<?php renderAdminFooter(); ?>
