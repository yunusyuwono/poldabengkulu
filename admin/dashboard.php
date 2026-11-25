<?php

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/middleware/csrf.php';
require_once __DIR__ . '/_layout.php';

Auth::requireLogin();

$postCount      = db()->fetchOne("SELECT COUNT(*) as c FROM posts WHERE status='published'")['c'];
$draftCount     = db()->fetchOne("SELECT COUNT(*) as c FROM posts WHERE status='draft'")['c'];
$satkerCount    = db()->fetchOne("SELECT COUNT(*) as c FROM satkers WHERE active=1")['c'];
$layananCount   = (int)db()->fetchOne("SELECT COUNT(*) as c FROM layanan WHERE active=1")['c'];
$mediaCount     = db()->fetchOne("SELECT COUNT(*) as c FROM gallery")['c'];
$siteViews      = (int)getSetting('site_views', '0');
$totalPostViews = (int)db()->fetchOne("SELECT SUM(COALESCE(views, 0)) as c FROM posts")['c'];
$recentPosts    = db()->fetchAll("SELECT * FROM posts ORDER BY created_at DESC LIMIT 5");
$recentMedia    = db()->fetchAll("SELECT * FROM gallery ORDER BY created_at DESC LIMIT 6");
$preset    = $_GET['preset'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate   = $_GET['end_date'] ?? '';

if ($preset === '7days') {
    $startDate = date('Y-m-d', strtotime('-7 days'));
    $endDate   = date('Y-m-d');
} elseif ($preset === '30days') {
    $startDate = date('Y-m-d', strtotime('-30 days'));
    $endDate   = date('Y-m-d');
} elseif ($preset === 'this_month') {
    $startDate = date('Y-m-01');
    $endDate   = date('Y-m-t');
}

if (empty($startDate) || empty($endDate)) {
    $startDate = date('Y-m-d', strtotime('-14 days'));
    $endDate   = date('Y-m-d');
}

$chartLogs = db()->fetchAll("
    SELECT visited_date, COUNT(*) as visit_count 
    FROM visitor_logs 
    WHERE visited_date BETWEEN ? AND ? 
    GROUP BY visited_date 
    ORDER BY visited_date ASC
", [$startDate, $endDate]);

$chartLabels = [];
$chartData   = [];
$logMap      = [];
foreach ($chartLogs as $cl) {
    $logMap[$cl['visited_date']] = (int)$cl['visit_count'];
}

try {
    $currObj = new DateTime($startDate);
    $lastObj = new DateTime($endDate);
    while ($currObj <= $lastObj) {
        $dStr = $currObj->format('Y-m-d');
        $chartLabels[] = $currObj->format('d M');
        $chartData[]   = $logMap[$dStr] ?? 0;
        $currObj->modify('+1 day');
    }
} catch (\Throwable $e) {}

$rangeTotalVisits = array_sum($chartData);

renderAdminHeader('Dashboard');
?>

<div class="row g-3 mb-4">
  <div class="col-sm-6 col-md-4 col-xl">
    <div class="stat-card">
      <div class="stat-icon blue"><i class="bi bi-eye"></i></div>
      <div>
        <div class="stat-num"><?= number_format($siteViews) ?></div>
        <div class="stat-label">Kunjungan Web</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-md-4 col-xl">
    <div class="stat-card">
      <div class="stat-icon green"><i class="bi bi-book-half"></i></div>
      <div>
        <div class="stat-num"><?= number_format($totalPostViews) ?></div>
        <div class="stat-label">Berita Dibaca</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-md-4 col-xl">
    <div class="stat-card">
      <div class="stat-icon blue"><i class="bi bi-newspaper"></i></div>
      <div>
        <div class="stat-num"><?= $postCount ?></div>
        <div class="stat-label">Berita Terbit</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-md-4 col-xl">
    <div class="stat-card">
      <div class="stat-icon gold"><i class="bi bi-diagram-3"></i></div>
      <div>
        <div class="stat-num"><?= $satkerCount ?></div>
        <div class="stat-label">Satker Aktif</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-md-4 col-xl">
    <div class="stat-card">
      <div class="stat-icon red"><i class="bi bi-grid-1x2"></i></div>
      <div>
        <div class="stat-num"><?= $layananCount ?></div>
        <div class="stat-label">Layanan Aktif</div>
      </div>
    </div>
  </div>
</div>

<?php if ($draftCount > 0): ?>
<div class="admin-alert warning mb-4">
  <i class="bi bi-pencil-square"></i>
  Ada <strong><?= $draftCount ?></strong> berita dalam status draft. <a href="posts" style="color:inherit;text-decoration:underline">Kelola sekarang</a>.
</div>
<?php endif; ?>

<!-- Visitor Analytics Chart Card -->
<div class="admin-card mb-4">
  <div class="admin-card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
      <h2 class="admin-card-title"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Grafik Kunjungan Website</h2>
      <div class="small text-muted font-weight-semibold mt-1">Total <strong><?= number_format($rangeTotalVisits) ?></strong> kunjungan pada rentang tanggal <?= date('d M Y', strtotime($startDate)) ?> s/d <?= date('d M Y', strtotime($endDate)) ?></div>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
      <div class="btn-group btn-group-sm">
        <a href="?preset=7days" class="btn btn-outline-secondary btn-sm <?= $preset === '7days' ? 'active' : '' ?>">7 Hari</a>
        <a href="?preset=30days" class="btn btn-outline-secondary btn-sm <?= $preset === '30days' ? 'active' : '' ?>">30 Hari</a>
        <a href="?preset=this_month" class="btn btn-outline-secondary btn-sm <?= $preset === 'this_month' ? 'active' : '' ?>">Bulan Ini</a>
      </div>
      <form method="GET" class="d-flex align-items-center gap-2 m-0">
        <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($startDate, ENT_QUOTES) ?>" required style="max-width:145px">
        <span class="text-muted small">s/d</span>
        <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($endDate, ENT_QUOTES) ?>" required style="max-width:145px">
        <button type="submit" class="btn btn-sm btn-admin-primary px-3"><i class="bi bi-funnel-fill me-1"></i> Filter</button>
      </form>
    </div>
  </div>
  <div class="admin-card-body">
    <div style="height:270px;position:relative">
      <canvas id="visitorChart"></canvas>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const ctx = document.getElementById('visitorChart');
  if (ctx && window.Chart) {
    const chartCtx = ctx.getContext('2d');
    const gradient = chartCtx.createLinearGradient(0, 0, 0, 240);
    gradient.addColorStop(0, 'rgba(26, 58, 107, 0.35)');
    gradient.addColorStop(1, 'rgba(26, 58, 107, 0.00)');

    new Chart(ctx, {
      type: 'line',
      data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
          label: 'Kunjungan Website',
          data: <?= json_encode($chartData) ?>,
          borderColor: '#1a3a6b',
          borderWidth: 2.5,
          backgroundColor: gradient,
          fill: true,
          tension: 0.35,
          pointBackgroundColor: '#d4af37',
          pointBorderColor: '#ffffff',
          pointBorderWidth: 2,
          pointRadius: 4,
          pointHoverRadius: 7
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            padding: 10,
            cornerRadius: 8,
            callbacks: {
              label: function(c) { return ' ' + c.parsed.y + ' Kunjungan'; }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: { precision: 0 },
            grid: { color: 'rgba(0,0,0,0.04)' }
          },
          x: {
            grid: { display: false }
          }
        }
      }
    });
  }
});
</script>

<div class="row g-3">
  
  <div class="col-lg-4">
    <div class="admin-card mb-3">
      <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-lightning-charge me-1"></i> Aksi Cepat</h2>
      </div>
      <div class="admin-card-body">
        <div class="d-grid gap-2">
          <a href="posts?action=new" class="btn-admin-primary" id="btn-new-post">
            <i class="bi bi-plus-lg"></i> Tambah Berita Baru
          </a>
          <a href="settings" class="btn-admin-secondary" id="btn-settings">
            <i class="bi bi-sliders"></i> Edit Pengaturan Situs
          </a>
          <a href="media" class="btn-admin-secondary" id="btn-media">
            <i class="bi bi-cloud-upload"></i> Upload Foto/Media
          </a>
          <a href="satker?action=new" class="btn-admin-secondary" id="btn-new-satker">
            <i class="bi bi-building-add"></i> Tambah Satker
          </a>
        </div>
        <div class="admin-alert info mt-3 mb-0" style="font-size:.82rem">
          <i class="bi bi-info-circle"></i>
          Terakhir login: <?= date('d M Y H:i', strtotime(Auth::getCurrentUser()['last_login'] ?? 'now')) ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="admin-card mb-3">
      <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-newspaper me-1"></i> Berita Terbaru</h2>
        <a href="posts" class="btn-admin-secondary" style="padding:.4rem .85rem;font-size:.82rem">
          <i class="bi bi-list-ul me-1"></i> Lihat Semua
        </a>
      </div>
      <div style="overflow-x:auto">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Judul</th>
              <th>Kategori</th>
              <th>Status</th>
              <th>Tanggal</th>
              <th>Dilihat</th>
              <th class="no-sort">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentPosts as $post): ?>
            <tr>
              <td style="max-width:220px">
                <div style="font-weight:700;font-size:.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                  <?= htmlspecialchars($post['title'], ENT_QUOTES) ?>
                </div>
              </td>
              <td>
                <span class="badge-status badge-<?= $post['category'] === 'pengumuman' ? 'pengumuman' : 'berita' ?>">
                  <?= htmlspecialchars(ucfirst($post['category']), ENT_QUOTES) ?>
                </span>
              </td>
              <td>
                <span class="badge-status badge-<?= $post['status'] ?>">
                  <?= $post['status'] === 'published' ? 'Terbit' : 'Draft' ?>
                </span>
              </td>
              <td style="white-space:nowrap;color:var(--muted);font-size:.85rem">
                <?= date('d M Y', strtotime($post['created_at'])) ?>
              </td>
              <td style="white-space:nowrap;font-size:.85rem;font-weight:700">
                <i class="bi bi-eye text-primary me-1"></i><?= number_format($post['views'] ?? 0) ?>x
              </td>
              <td style="white-space:nowrap">
                <a href="posts?action=edit&id=<?= $post['id'] ?>" class="btn-admin-edit">
                  <i class="bi bi-pencil"></i>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php if (!empty($recentMedia)): ?>
    <div class="admin-card">
      <div class="admin-card-header">
        <h2 class="admin-card-title"><i class="bi bi-images me-1"></i> Media Terbaru</h2>
        <a href="media" class="btn-admin-secondary" style="padding:.4rem .85rem;font-size:.82rem">
          <i class="bi bi-collection me-1"></i> Lihat Semua
        </a>
      </div>
      <div class="admin-card-body">
        <div class="admin-gallery-grid">
          <?php foreach ($recentMedia as $img): ?>
          <div class="admin-gallery-item">
            <img src="../uploads/<?= htmlspecialchars($img['filename'], ENT_QUOTES) ?>" alt="<?= htmlspecialchars($img['title'] ?? '', ENT_QUOTES) ?>" loading="lazy">
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php renderAdminFooter(); ?>
