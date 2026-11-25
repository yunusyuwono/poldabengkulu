<?php

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/middleware/csrf.php';
require_once __DIR__ . '/_layout.php';

Auth::requireLogin();

$action  = $_GET['action'] ?? 'list';
$editId  = (int)($_GET['id'] ?? 0);
$message = '';
$msgType = 'success';
$post    = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validateRequest();

    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'save') {
        $title   = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $category= in_array($_POST['category'] ?? '', ['berita', 'pengumuman']) ? $_POST['category'] : 'berita';
        $status  = in_array($_POST['status'] ?? '', ['published', 'draft']) ? $_POST['status'] : 'draft';
        $id      = (int)($_POST['id'] ?? 0);

        if (empty($title)) {
            $message = 'Judul berita wajib diisi.';
            $msgType = 'error';
        } else {
            $slug = slugify($title);
            $excerpt = mb_substr(strip_tags((string)$content), 0, 200);

            $image = $_POST['existing_image'] ?? '';
            if (!empty($_FILES['image']['name'])) {
                $uploadResult = handleImageUpload('image');
                if ($uploadResult['success']) {
                    
                    if ($image && file_exists(UPLOAD_PATH . '/' . $image)) {
                        unlink(UPLOAD_PATH . '/' . $image);
                    }
                    $image = $uploadResult['filename'];
                } else {
                    $message = $uploadResult['error'];
                    $msgType = 'error';
                }
            }

            if (empty($message)) {
                if ($id > 0) {
                    
                    $existing = db()->fetchOne("SELECT id FROM posts WHERE slug = ? AND id != ?", [$slug, $id]);
                    if ($existing) $slug .= '-' . $id;

                    db()->query(
                        "UPDATE posts SET title=?, slug=?, content=?, excerpt=?, category=?, status=?, image=?, updated_at=CURRENT_TIMESTAMP WHERE id=?",
                        [$title, $slug, $content, $excerpt, $category, $status, $image, $id]
                    );
                    $message = 'Berita berhasil diperbarui.';
                } else {
                    $existing = db()->fetchOne("SELECT id FROM posts WHERE slug = ?", [$slug]);
                    if ($existing) $slug .= '-' . time();

                    db()->insert('posts', [
                        'title' => $title, 'slug' => $slug, 'content' => $content,
                        'excerpt' => $excerpt, 'category' => $category, 'status' => $status,
                        'image' => $image, 'author_id' => $_SESSION['user_id'],
                    ]);
                    $message = 'Berita berhasil ditambahkan.';
                }
                if ($msgType !== 'error') {
                    $action = 'list';
                }
            }
        }
    } elseif ($formAction === 'delete') {
        $delId = (int)($_POST['delete_id'] ?? 0);
        if ($delId > 0) {
            $p = db()->fetchOne("SELECT image FROM posts WHERE id = ?", [$delId]);
            if ($p && $p['image'] && file_exists(UPLOAD_PATH . '/' . $p['image'])) {
                unlink(UPLOAD_PATH . '/' . $p['image']);
            }
            db()->delete('posts', 'id = ?', [$delId]);
            $message = 'Berita berhasil dihapus.';
        }
    }
}

if ($action === 'edit' && $editId > 0) {
    $post = db()->fetchOne("SELECT * FROM posts WHERE id = ?", [$editId]);
    if (!$post) { $action = 'list'; }
}

function handleImageUpload(string $fieldName): array {
    $file = $_FILES[$fieldName] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload gagal.'];
    }
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'error' => 'Ukuran file maksimal 5MB.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, ALLOWED_IMAGE_TYPES)) {
        return ['success' => false, 'error' => 'Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP.'];
    }

    $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        return ['success' => false, 'error' => 'Ekstensi file tidak diizinkan.'];
    }

    $newName = bin2hex(random_bytes(16)) . '.' . $ext;
    $destPath = UPLOAD_PATH . '/' . $newName;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['success' => false, 'error' => 'Gagal menyimpan file.'];
    }

    return ['success' => true, 'filename' => $newName];
}

$posts = db()->fetchAll("SELECT p.*, u.username FROM posts p LEFT JOIN users u ON p.author_id = u.id ORDER BY p.created_at DESC");

$pageTitle = ($action === 'new' || $action === 'edit') ? (($action === 'edit') ? 'Edit Berita' : 'Tambah Berita') : 'Kelola Berita';
renderAdminHeader($pageTitle);
?>

<?php if ($message): ?>
<div class="admin-alert <?= $msgType ?>">
  <i class="bi bi-<?= $msgType === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?>"></i>
  <?= htmlspecialchars($message, ENT_QUOTES) ?>
</div>
<?php endif; ?>

<?php if ($action === 'list'): ?>

<div class="admin-card">
  <div class="admin-card-header">
    <h2 class="admin-card-title"><i class="bi bi-newspaper me-2"></i>Berita &amp; Pengumuman</h2>
    <a href="?action=new" class="btn-admin-primary" id="btn-add-post">
      <i class="bi bi-plus-lg"></i> Tambah Baru
    </a>
  </div>
  <div style="overflow-x:auto">
    <table class="admin-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Judul</th>
          <th>Kategori</th>
          <th>Status</th>
          <th>Gambar</th>
          <th>Tanggal</th>
          <th>Dilihat</th>
          <th class="no-sort">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($posts as $p): ?>
        <tr>
          <td style="color:var(--muted)"><?= $p['id'] ?></td>
          <td style="max-width:260px">
            <div style="font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
              <?= htmlspecialchars($p['title'], ENT_QUOTES) ?>
            </div>
            <div style="font-size:.78rem;color:var(--muted)"><?= htmlspecialchars($p['slug'], ENT_QUOTES) ?></div>
          </td>
          <td><span class="badge-status badge-<?= $p['category'] ?>"><?= ucfirst($p['category']) ?></span></td>
          <td><span class="badge-status badge-<?= $p['status'] ?>"><?= $p['status'] === 'published' ? 'Terbit' : 'Draft' ?></span></td>
          <td>
            <?php if ($p['image']): ?>
            <img src="../uploads/<?= htmlspecialchars($p['image'], ENT_QUOTES) ?>" alt="" style="width:52px;height:40px;object-fit:cover;border-radius:8px">
            <?php else: ?>
            <span style="color:var(--muted);font-size:.82rem">Tidak ada</span>
            <?php endif; ?>
          </td>
          <td style="white-space:nowrap;color:var(--muted);font-size:.84rem"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
          <td style="white-space:nowrap;font-weight:700;font-size:.85rem"><i class="bi bi-eye text-primary me-1"></i><?= number_format($p['views'] ?? 0) ?>x</td>
          <td style="white-space:nowrap">
            <a href="?action=edit&id=<?= $p['id'] ?>" class="btn-admin-edit me-1"><i class="bi bi-pencil"></i></a>
            <form method="POST" style="display:inline" onsubmit="return confirm('Hapus berita ini?')">
              <?= CSRF::field() ?>
              <input type="hidden" name="form_action" value="delete">
              <input type="hidden" name="delete_id" value="<?= $p['id'] ?>">
              <button type="submit" class="btn-admin-danger"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php else: ?>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="admin-card">
      <div class="admin-card-header">
        <h2 class="admin-card-title">
          <i class="bi bi-<?= $action === 'edit' ? 'pencil' : 'plus-lg' ?> me-2"></i>
          <?= $action === 'edit' ? 'Edit Berita' : 'Tambah Berita Baru' ?>
        </h2>
        <a href="posts" class="btn-admin-secondary" style="padding:.4rem .85rem;font-size:.82rem">
          <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
      </div>
      <div class="admin-card-body">
        <form method="POST" action="posts" enctype="multipart/form-data" id="postForm">
          <?= CSRF::field() ?>
          <input type="hidden" name="form_action" value="save">
          <input type="hidden" name="id" value="<?= $post['id'] ?? 0 ?>">
          <input type="hidden" name="existing_image" value="<?= htmlspecialchars($post['image'] ?? '', ENT_QUOTES) ?>">

          <div class="admin-form-group">
            <label class="admin-label" for="post-title">Judul <span class="required">*</span></label>
            <input type="text" id="post-title" name="title" class="admin-input"
              value="<?= htmlspecialchars($post['title'] ?? '', ENT_QUOTES) ?>"
              placeholder="Masukkan judul berita..." required maxlength="255">
          </div>

          <div class="admin-form-group">
            <label class="admin-label" for="post-content">Konten</label>
            <div class="editor-toolbar" id="editorToolbar">
              <button type="button" class="editor-btn" onclick="formatText('bold')" title="Bold"><i class="bi bi-type-bold"></i></button>
              <button type="button" class="editor-btn" onclick="formatText('italic')" title="Italic"><i class="bi bi-type-italic"></i></button>
              <button type="button" class="editor-btn" onclick="formatText('underline')" title="Underline"><i class="bi bi-type-underline"></i></button>
              <button type="button" class="editor-btn" onclick="insertElement('h3')" title="Heading">H3</button>
              <button type="button" class="editor-btn" onclick="insertElement('ul')" title="List"><i class="bi bi-list-ul"></i></button>
              <button type="button" class="editor-btn" onclick="formatText('insertOrderedList')" title="Numbered List"><i class="bi bi-list-ol"></i></button>
            </div>
            <div class="editor-area" id="postEditor" contenteditable="true"><?= $post['content'] ?? '' ?></div>
            <textarea id="post-content" name="content" style="display:none"><?= htmlspecialchars($post['content'] ?? '', ENT_QUOTES) ?></textarea>
          </div>

          <div class="admin-form-group">
            <label class="admin-label" for="post-image">Gambar</label>
            <?php if (!empty($post['image'])): ?>
            <div class="mb-2">
              <img src="../uploads/<?= htmlspecialchars($post['image'], ENT_QUOTES) ?>" alt="" class="upload-preview">
              <div class="admin-input-hint">Gambar saat ini. Upload baru untuk mengganti.</div>
            </div>
            <?php endif; ?>
            <div class="upload-area" id="uploadArea" onclick="document.getElementById('post-image').click()">
              <i class="bi bi-cloud-upload fs-2" style="color:var(--primary);opacity:.6"></i>
              <div style="font-weight:700;margin:.5rem 0 .25rem">Klik untuk upload gambar</div>
              <div class="admin-input-hint">JPG, PNG, GIF, WebP — maks 5MB</div>
              <img id="imagePreview" src="" alt="" class="upload-preview" style="display:none">
            </div>
            <input type="file" id="post-image" name="image" accept="image/*" style="display:none">
          </div>

          <div class="d-flex gap-3">
            <button type="submit" name="status" value="published" class="btn-admin-primary" id="btn-publish">
              <i class="bi bi-check-lg"></i> Terbitkan
            </button>
            <button type="submit" name="status" value="draft" class="btn-admin-secondary" id="btn-draft">
              <i class="bi bi-pencil"></i> Simpan Draft
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="admin-card">
      <div class="admin-card-header"><h2 class="admin-card-title">Pengaturan</h2></div>
      <div class="admin-card-body">
        <div class="admin-form-group">
          <label class="admin-label" for="post-category">Kategori</label>
          <select id="post-category" name="category" class="admin-select" form="postForm">
            <option value="berita" <?= ($post['category'] ?? 'berita') === 'berita' ? 'selected' : '' ?>>Berita</option>
            <option value="pengumuman" <?= ($post['category'] ?? '') === 'pengumuman' ? 'selected' : '' ?>>Pengumuman</option>
          </select>
        </div>
        <div class="admin-form-group mb-0">
          <label class="admin-label" for="post-status">Status</label>
          <select id="post-status" name="status" class="admin-select" form="postForm">
            <option value="published" <?= ($post['status'] ?? 'draft') === 'published' ? 'selected' : '' ?>>Terbit</option>
            <option value="draft" <?= ($post['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
          </select>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script>

function formatText(cmd) {
  document.execCommand(cmd, false, null);
  document.getElementById('postEditor').focus();
}
function insertElement(tag) {
  const editor = document.getElementById('postEditor');
  const sel = window.getSelection();
  if (tag === 'ul') {
    document.execCommand('insertUnorderedList', false, null);
  } else if (tag === 'h3') {
    document.execCommand('formatBlock', false, 'h3');
  }
  editor.focus();
}

const form = document.getElementById('postForm');
if (form) {
  form.addEventListener('submit', function() {
    const editor = document.getElementById('postEditor');
    const textarea = document.getElementById('post-content');
    if (editor && textarea) textarea.value = editor.innerHTML;
  });
}

const imgInput = document.getElementById('post-image');
if (imgInput) {
  imgInput.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        const preview = document.getElementById('imagePreview');
        preview.src = e.target.result;
        preview.style.display = 'block';
      };
      reader.readAsDataURL(file);
    }
  });
}

const uploadArea = document.getElementById('uploadArea');
if (uploadArea) {
  uploadArea.addEventListener('dragover', e => { e.preventDefault(); uploadArea.classList.add('dragover'); });
  uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('dragover'));
  uploadArea.addEventListener('drop', e => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file && imgInput) {
      const dt = new DataTransfer();
      dt.items.add(file);
      imgInput.files = dt.files;
      imgInput.dispatchEvent(new Event('change'));
    }
  });
}
</script>

<?php renderAdminFooter(); ?>
