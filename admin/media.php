<?php

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/middleware/csrf.php';
require_once __DIR__ . '/_layout.php';

Auth::requireLogin();

$message = ''; $msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validateRequest();
    $fa = $_POST['form_action'] ?? '';

    if ($fa === 'upload') {
        $title = mb_substr(trim($_POST['title'] ?? ''), 0, 150);
        $uploaded = 0;
        $errors = [];

        if (!empty($_FILES['images'])) {
            $files = $_FILES['images'];
            $count = count($files['name']);

            for ($i = 0; $i < $count; $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
                if ($files['size'][$i] > MAX_UPLOAD_SIZE) { $errors[] = $files['name'][$i] . ': Ukuran terlalu besar.'; continue; }

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($files['tmp_name'][$i]);
                if (!in_array($mime, ALLOWED_IMAGE_TYPES)) { $errors[] = $files['name'][$i] . ': Format tidak didukung.'; continue; }

                $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) { $errors[] = $files['name'][$i] . ': Ekstensi tidak valid.'; continue; }

                $newName = bin2hex(random_bytes(16)) . '.' . $ext;
                if (move_uploaded_file($files['tmp_name'][$i], UPLOAD_PATH . '/' . $newName)) {
                    db()->insert('gallery', ['title' => $title ?: $files['name'][$i], 'filename' => $newName, 'file_type' => $mime, 'file_size' => $files['size'][$i], 'uploaded_by' => $_SESSION['user_id']]);
                    $uploaded++;
                }
            }
        }

        if ($uploaded > 0) $message = "$uploaded foto berhasil diupload.";
        elseif (!empty($errors)) { $message = 'Upload gagal: ' . implode(', ', $errors); $msgType = 'error'; }
        else { $message = 'Tidak ada file yang dipilih.'; $msgType = 'error'; }
    }

    elseif ($fa === 'delete') {
        $delId = (int)($_POST['delete_id'] ?? 0);
        if ($delId > 0) {
            $img = db()->fetchOne("SELECT filename FROM gallery WHERE id = ?", [$delId]);
            if ($img) {
                $path = UPLOAD_PATH . '/' . $img['filename'];
                if (file_exists($path)) unlink($path);
                db()->delete('gallery', 'id = ?', [$delId]);
                $message = 'Foto berhasil dihapus.';
            }
        }
    }

    elseif ($fa === 'update_title') {
        $updId = (int)($_POST['update_id'] ?? 0);
        $newTitle = mb_substr(trim($_POST['new_title'] ?? ''), 0, 150);
        if ($updId > 0) {
            db()->query("UPDATE gallery SET title = ? WHERE id = ?", [$newTitle, $updId]);
            $message = 'Judul foto diperbarui.';
        }
    }
}

$gallery = db()->fetchAll("SELECT * FROM gallery ORDER BY created_at DESC");
renderAdminHeader('Galeri & Media');
?>

<?php if ($message): ?>
<div class="admin-alert <?= $msgType ?>">
  <i class="bi bi-<?= $msgType === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?>"></i>
  <?= htmlspecialchars($message, ENT_QUOTES) ?>
</div>
<?php endif; ?>

<div class="row g-3">
  
  <div class="col-12">
    <div class="admin-card">
      <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-cloud-upload me-2"></i>Upload Foto</h2>
      </div>
      <div class="admin-card-body">
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
          <?= CSRF::field() ?>
          <input type="hidden" name="form_action" value="upload">
          
          <div class="row g-3 align-items-end">
            <div class="col-md-4">
              <div class="admin-form-group mb-0">
                <label class="admin-label">Judul Foto</label>
                <input type="text" name="title" class="admin-input" placeholder="Opsional — judul foto" maxlength="150">
              </div>
            </div>

            <div class="col-md-6">
              <div class="admin-form-group mb-0">
                <label class="admin-label">Pilih Foto <span class="required">*</span></label>
                <div class="upload-dropzone py-3" id="mediaUploadArea" onclick="document.getElementById('mediaFiles').click()">
                  <div class="d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-images fs-4 text-primary"></i>
                    <span class="fw-bold" style="color: var(--ink); font-size:0.92rem">Klik atau drag &amp; drop foto di sini</span>
                  </div>
                </div>
                <input type="file" id="mediaFiles" name="images[]" accept="image/*" multiple style="display:none">
              </div>
            </div>

            <div class="col-md-2">
              <button type="submit" class="btn-admin-primary w-100 py-2 justify-content-center" id="btnUploadSubmit">
                <i class="bi bi-upload me-1"></i> Upload
              </button>
            </div>

            <div class="col-12">
              <div id="filePreviewContainer" class="media-preview-container" style="display:none"></div>
              <div id="fileSummary" class="mt-2 text-muted fw-semibold" style="font-size:0.85rem"></div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="admin-card">
      <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-images me-2"></i>Foto Galeri (<?= count($gallery) ?>)</h2>
      </div>
      <div class="admin-card-body">
        <?php if (empty($gallery)): ?>
        <div class="text-center py-5" style="color:var(--muted)">
          <i class="bi bi-images fs-1 d-block mb-3" style="opacity:.2"></i>
          <p class="mb-0">Belum ada foto. Upload foto untuk mulai mengisi galeri.</p>
        </div>
        <?php else: ?>
        <div class="admin-gallery-grid">
          <?php foreach ($gallery as $img): ?>
          <div class="admin-gallery-item" id="gallery-item-<?= $img['id'] ?>">
            <img src="../uploads/<?= htmlspecialchars($img['filename'], ENT_QUOTES) ?>" alt="<?= htmlspecialchars($img['title'] ?? '', ENT_QUOTES) ?>" loading="lazy">
            <div class="admin-gallery-item-actions">
              <button type="button" onclick="editTitle(<?= $img['id'] ?>, '<?= addslashes(htmlspecialchars($img['title'] ?? '', ENT_QUOTES)) ?>')"
                style="background:rgba(255,255,255,.25);backdrop-filter:blur(4px);border:none;color:#fff;border-radius:8px;padding:.4rem .7rem;cursor:pointer;font-size:.85rem">
                <i class="bi bi-pencil"></i>
              </button>
              <form method="POST" onsubmit="return confirm('Hapus foto ini dari galeri?')">
                <?= CSRF::field() ?>
                <input type="hidden" name="form_action" value="delete">
                <input type="hidden" name="delete_id" value="<?= $img['id'] ?>">
                <button type="submit" style="background:rgba(220,38,38,.85);backdrop-filter:blur(4px);border:none;color:#fff;border-radius:8px;padding:.4rem .7rem;cursor:pointer;font-size:.85rem">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </div>
            <?php if ($img['title']): ?>
            <div style="position:absolute;bottom:0;left:0;right:0;background:rgba(15,23,42,.75);backdrop-filter:blur(4px);color:#fff;font-size:.78rem;font-weight:600;padding:.4rem .6rem;pointer-events:none">
              <?= htmlspecialchars(mb_substr($img['title'], 0, 40), ENT_QUOTES) ?>
            </div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<form method="POST" id="editTitleForm" style="display:none">
  <?= CSRF::field() ?>
  <input type="hidden" name="form_action" value="update_title">
  <input type="hidden" name="update_id" id="editTitleId">
  <input type="text" name="new_title" id="editTitleInput" maxlength="150">
</form>

<script>
const mediaFiles = document.getElementById('mediaFiles');
const fileSummary = document.getElementById('fileSummary');
const filePreviewContainer = document.getElementById('filePreviewContainer');

if (mediaFiles) {
  mediaFiles.addEventListener('change', function() {
    filePreviewContainer.innerHTML = '';
    const files = Array.from(this.files);
    
    if (files.length > 0) {
      filePreviewContainer.style.display = 'grid';
      fileSummary.textContent = files.length + ' foto siap diupload:';
      
      files.forEach(file => {
        if (file.type.startsWith('image/')) {
          const reader = new FileReader();
          reader.onload = function(e) {
            const thumb = document.createElement('div');
            thumb.className = 'media-preview-thumb';
            thumb.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
            filePreviewContainer.appendChild(thumb);
          };
          reader.readAsDataURL(file);
        }
      });
    } else {
      filePreviewContainer.style.display = 'none';
      fileSummary.textContent = '';
    }
  });
}

const mediaArea = document.getElementById('mediaUploadArea');
if (mediaArea) {
  ['dragenter', 'dragover'].forEach(eventName => {
    mediaArea.addEventListener(eventName, e => {
      e.preventDefault();
      mediaArea.classList.add('dragover');
    }, false);
  });
  
  ['dragleave', 'drop'].forEach(eventName => {
    mediaArea.addEventListener(eventName, e => {
      e.preventDefault();
      mediaArea.classList.remove('dragover');
    }, false);
  });

  mediaArea.addEventListener('drop', e => {
    if (e.dataTransfer.files.length) {
      mediaFiles.files = e.dataTransfer.files;
      mediaFiles.dispatchEvent(new Event('change'));
    }
  });
}

function editTitle(id, currentTitle) {
  const newTitle = prompt('Edit judul foto:', currentTitle);
  if (newTitle !== null) {
    document.getElementById('editTitleId').value = id;
    document.getElementById('editTitleInput').value = newTitle;
    document.getElementById('editTitleForm').submit();
  }
}
</script>

<?php renderAdminFooter(); ?>
