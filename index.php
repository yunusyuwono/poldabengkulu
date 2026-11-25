<?php

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

if (empty($_SESSION['site_visited'])) {
    db()->query("UPDATE settings SET value = CAST(COALESCE(value, 0) AS UNSIGNED) + 1 WHERE `key` = 'site_views'");
    $_SESSION['site_visited'] = true;
}

$todayDate = date('Y-m-d');
if (empty($_SESSION['logged_visit_' . $todayDate])) {
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $userAgent = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    db()->insert('visitor_logs', [
        'ip' => $clientIp,
        'user_agent' => $userAgent,
        'visited_date' => $todayDate
    ]);
    $_SESSION['logged_visit_' . $todayDate] = true;
}

if (isset($_GET['api']) && $_GET['api'] === 'view_post' && !empty($_GET['id'])) {
    $postId = (int)$_GET['id'];
    if ($postId > 0) {
        db()->query("UPDATE posts SET views = COALESCE(views, 0) + 1 WHERE id = ?", [$postId]);
        header('Content-Type: application/json');
        $newViews = db()->fetchOne("SELECT views FROM posts WHERE id = ?", [$postId])['views'] ?? 0;
        echo json_encode(['success' => true, 'views' => (int)$newViews]);
        exit;
    }
}

$s = function(string $key, string $default = ''): string {
    return htmlspecialchars(getSetting($key, $default), ENT_QUOTES, 'UTF-8');
};

$posts   = db()->fetchAll("SELECT * FROM posts WHERE status = 'published' ORDER BY created_at DESC LIMIT 6");
$satkers = db()->fetchAll("SELECT * FROM satkers WHERE active = 1 ORDER BY order_num ASC");
$layanan = db()->fetchAll("SELECT * FROM layanan WHERE active = 1 ORDER BY order_num ASC");

$polres  = array_filter($satkers, fn($x) => $x['type'] === 'polres');
$satkerFungsi = array_filter($satkers, fn($x) => $x['type'] === 'satker');

function e(?string $str): string { return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8'); }
function excerptText(?string $text, int $len = 160): string {
    $text = strip_tags((string)$text);
    return (mb_strlen($text) > $len) ? mb_substr($text, 0, $len) . '...' : $text;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $s('site_name') ?> - <?= $s('site_tagline') ?></title>
  <meta name="description" content="<?= $s('site_description') ?>">
  <meta name="theme-color" content="#0a192f">
  <meta property="og:title" content="<?= $s('site_name') ?> - <?= $s('site_tagline') ?>">
  <meta property="og:description" content="<?= $s('site_description') ?>">
  <meta property="og:type" content="website">
  <meta name="robots" content="index, follow">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800;900&family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top navbar-custom" id="mainNavbar">
  <div class="container">
    <a class="navbar-brand" href="#top">
      <div class="brand-logo-wrap">
        <img src="assets/img/logo-polri.png" alt="Logo Polri" class="brand-logo" onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><circle cx=%2250%22 cy=%2250%22 r=%2248%22 fill=%22%230a192f%22/><text y=%221.1em%22 x=%2250%22 text-anchor=%22middle%22 font-size=%2240%22 fill=%22%23eab308%22 font-weight=%22bold%22>P</text></svg>'">
      </div>
      <div class="brand-text">
        <span class="brand-title"><?= $s('site_name') ?></span>
        <span class="brand-subtitle">Polri Presisi Bengkulu</span>
      </div>
    </a>

    <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-label="Toggle navigation">
      <i class="bi bi-list fs-2 text-dark"></i>
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
        <li class="nav-item"><a class="nav-link" href="#profil">Profil Presisi</a></li>
        <li class="nav-item"><a class="nav-link" href="#berita">Berita</a></li>
        <li class="nav-item"><a class="nav-link" href="#layanan">Layanan Publik</a></li>
        <li class="nav-item"><a class="nav-link" href="#satker">Satker &amp; Polres</a></li>
        <li class="nav-item"><a class="nav-link" href="#galeri">Galeri</a></li>
        <li class="nav-item"><a class="nav-link" href="#kontak">Kontak</a></li>
        <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
          <a href="tel:110" class="btn-nav-emergency">
            <i class="bi bi-telephone-fill"></i> Call 110
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<header class="hero-section" id="top">
  <div class="hero-bg-overlay"></div>
  <div class="hero-grid-pattern"></div>
  <div class="hero-glow-orb hero-glow-1"></div>
  <div class="hero-glow-orb hero-glow-2"></div>

  <div class="container hero-content">
    <div class="row align-items-center g-5">
      <div class="col-lg-7">
        <div class="hero-pill-badge">
          <span class="pulse-indicator"></span>
          <i class="bi bi-shield-check text-warning"></i>
          Portal Pelayanan Resmi Polda Bengkulu • 2026
        </div>

        <h1 class="hero-headline">
          Mewujudkan <span class="text-gold-gradient">Polri Presisi</span> yang Melindungi &amp; <span class="text-cyan-neon">Melayani</span> Masyarakat Bengkulu
        </h1>

        <p class="hero-description">
          Selamat datang di portal resmi Kepolisian Daerah Bengkulu. Akses cepat layanan publik online,
          informasi operasional 10 Polres jajaran, serta transparansi penegakan hukum yang akuntabel dan humanis.
        </p>

        <div class="hero-quicksearch-bar">
          <i class="bi bi-search text-warning ms-2"></i>
          <input type="text" class="hero-quicksearch-input" id="heroSearchInput" placeholder="Cari layanan (SKCK, SIM, SPKT, Dumas, Satker)...">
          <a href="#layanan" class="hero-quicksearch-btn" id="heroSearchBtn">
            <span>Cari</span> <i class="bi bi-arrow-right"></i>
          </a>
        </div>

        <div class="hero-cta-group">
          <a href="#layanan" class="btn-hero-primary">
            <i class="bi bi-grid-fill"></i> Layanan Online Presisi
          </a>
          <a href="#profil" class="btn-hero-glass">
            <i class="bi bi-info-circle"></i> Profil &amp; Visi Presisi
          </a>
        </div>

        <div class="hero-stats-grid">
          <div class="stat-glass-card">
            <div class="stat-number"><?= $s('hero_stat_polres', '10') ?></div>
            <div class="stat-label">Polres / Polresta</div>
          </div>
          <div class="stat-glass-card">
            <div class="stat-number"><?= $s('hero_stat_satker', '24') ?></div>
            <div class="stat-label">Satker Fungsi</div>
          </div>
          <div class="stat-glass-card">
            <div class="stat-number"><?= $s('hero_stat_personil', '5.000+') ?></div>
            <div class="stat-label">Personil Presisi</div>
          </div>
          <div class="stat-glass-card">
            <div class="stat-number">24/7</div>
            <div class="stat-label">Siap Melayani</div>
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="kapolda-executive-card">
          <div class="kapolda-photo-wrapper">
            <img src="<?= $s('kapolda_photo', 'assets/img/kapolda.jpg') ?>"
                 alt="<?= $s('kapolda_name') ?>" class="kapolda-photo-img"
                 onerror="this.src='assets/img/kapolda.jpg'">
            <div class="kapolda-badge-ribbon">
              <i class="bi bi-shield-shaded"></i> KAPOLDA BENGKULU
            </div>
            <div class="kapolda-stars">
              <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
            </div>
          </div>

          <div class="kapolda-card-info">
            <div class="kapolda-official-name"><?= $s('kapolda_name', 'Irjen Pol Yudhi Sulistianto Wahid, S.I.K.') ?></div>
            <div class="kapolda-official-role"><?= $s('kapolda_title', 'Kepala Kepolisian Daerah Bengkulu') ?></div>
            <div class="kapolda-quote-bubble">
              <i class="bi bi-quote fs-5 text-warning me-1"></i>
              <?= $s('kapolda_quote', 'Polda Bengkulu berkomitmen mewujudkan Polri Presisi yang responsif, transparan, dan berkeadilan bagi seluruh masyarakat Provinsi Bengkulu.') ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</header>

<section class="section-wrapper section-light" id="profil">
  <div class="container">
    <div class="text-center mb-5 reveal">
      <span class="section-badge-pill"><i class="bi bi-award-fill"></i> Nilai Utama Institusi</span>
      <h2 class="section-heading-title">Fondasi &amp; Komitmen Polri Presisi</h2>
      <p class="section-heading-desc">
        Polda Bengkulu mengedepankan 4 pilar transformasi menuju Polri yang Prediktif, Responsibilitas, dan Transparansi Berkeadilan untuk melayani Bumi Rafflesia.
      </p>
    </div>

    <div class="row g-4 mb-5">
      <div class="col-md-6 col-lg-3 reveal" style="transition-delay: 0.05s">
        <div class="presisi-pillar-card">
          <div class="presisi-pillar-icon"><i class="bi bi-graph-up-arrow"></i></div>
          <h3 class="presisi-pillar-title">Prediktif</h3>
          <p class="presisi-pillar-desc">
            Mengedepankan pemolisian prediktif berbasis data dan teknologi intelijen untuk mencegah potensi gangguan Kamtibmas sebelum terjadi.
          </p>
        </div>
      </div>

      <div class="col-md-6 col-lg-3 reveal" style="transition-delay: 0.1s">
        <div class="presisi-pillar-card">
          <div class="presisi-pillar-icon"><i class="bi bi-lightning-charge-fill"></i></div>
          <h3 class="presisi-pillar-title">Responsibilitas</h3>
          <p class="presisi-pillar-desc">
            Cepat tanggap, berempati, dan solutif dalam merespons setiap laporan pengaduan, kebutuhan, dan perlindungan masyarakat.
          </p>
        </div>
      </div>

      <div class="col-md-6 col-lg-3 reveal" style="transition-delay: 0.15s">
        <div class="presisi-pillar-card">
          <div class="presisi-pillar-icon"><i class="bi bi-shield-check"></i></div>
          <h3 class="presisi-pillar-title">Transparansi Berkeadilan</h3>
          <p class="presisi-pillar-desc">
            Menjamin proses penegakan hukum yang berkeadilan, terbuka, profesional, dan bebas dari diskriminasi maupun intervensi.
          </p>
        </div>
      </div>

      <div class="col-md-6 col-lg-3 reveal" style="transition-delay: 0.2s">
        <div class="presisi-pillar-card">
          <div class="presisi-pillar-icon"><i class="bi bi-heart-pulse-fill"></i></div>
          <h3 class="presisi-pillar-title">Pelayanan Prima</h3>
          <p class="presisi-pillar-desc">
            Modernisasi sarana dan prasarana publik SPKT, SKCK, dan Satpas untuk kenyamanan maksimal seluruh warga Provinsi Bengkulu.
          </p>
        </div>
      </div>
    </div>

    <div class="row g-4 reveal">
      <div class="col-lg-6">
        <div class="quick-action-hotline">
          <div>
            <span class="badge bg-danger text-white mb-2 px-3 py-2 fw-bold text-uppercase">
              <i class="bi bi-broadcast me-1"></i> Layanan Bebas Pulsa 24 Jam
            </span>
            <h4 class="fw-800 text-danger mb-1 font-display">Call Center Darurat Polri</h4>
            <div class="hotline-number-big">110</div>
            <p class="text-muted small mb-0">
              Hubungi segera untuk bantuan darurat kriminalitas, kecelakaan lalu lintas, bencana, atau gangguan kamtibmas di manapun Anda berada.
            </p>
          </div>
          <div class="mt-4">
            <a href="tel:110" class="btn btn-danger w-100 py-3 rounded-3 fw-bold fs-6">
              <i class="bi bi-telephone-inbound-fill me-2"></i> Panggil Hotline 110 Sekarang
            </a>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="quick-action-wa">
          <div>
            <span class="badge bg-success text-white mb-2 px-3 py-2 fw-bold text-uppercase">
              <i class="bi bi-whatsapp me-1"></i> Hotline WhatsApp Resmi
            </span>
            <h4 class="fw-800 text-success mb-1 font-display">Bantuan Polisi Polda Bengkulu</h4>
            <div class="hotline-number-big text-success"><?= $s('contact_whatsapp', '08119224545') ?></div>
            <p class="text-muted small mb-0">
              Layanan pesan cepat untuk pengaduan, konsultasi pelayanan kepolisian, dan verifikasi informasi resmi Polda Bengkulu.
            </p>
          </div>
          <div class="mt-4">
            <a href="https://wa.me/62<?= ltrim($s('contact_whatsapp', '08119224545'), '0') ?>" target="_blank" rel="noopener" class="btn btn-success w-100 py-3 rounded-3 fw-bold fs-6">
              <i class="bi bi-whatsapp me-2"></i> Kirim Pesan WhatsApp Resmi
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section-wrapper section-slate" id="berita">
  <div class="container">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-5 reveal">
      <div>
        <span class="section-badge-pill"><i class="bi bi-newspaper"></i> Publikasi &amp; Informasi</span>
        <h2 class="section-heading-title mb-0">Kabar &amp; Berita Terkini 2026</h2>
      </div>
      <div class="mt-3 mt-md-0">
        <a href="admin/" class="btn btn-outline-primary rounded-pill px-4 fw-bold">
          <i class="bi bi-grid me-1"></i> Arsip Berita Lengkap
        </a>
      </div>
    </div>

    <?php if (empty($posts)): ?>
    <div class="text-center py-5 text-muted">
      <i class="bi bi-newspaper fs-1 d-block mb-3 opacity-25"></i>
      <p>Belum ada berita terpublikasi.</p>
    </div>
    <?php else: ?>
    <div class="row g-4">
      <?php foreach ($posts as $i => $post): ?>
      <div class="col-md-6 col-lg-4 reveal" style="transition-delay: <?= $i * 0.08 ?>s">
        <div class="news-bento-card">
          <div class="news-img-container">
            <?php if (!empty($post['image'])): ?>
              <img src="uploads/<?= e($post['image']) ?>" alt="<?= e($post['title']) ?>" class="news-img">
            <?php else: ?>
              <div class="w-100 h-100 d-flex align-items-center justify-content-center text-white-50" style="background:linear-gradient(135deg,#0a192f,#1b3d7a)">
                <i class="bi bi-newspaper fs-1"></i>
              </div>
            <?php endif; ?>
            <span class="news-category-badge">
              <i class="bi bi-tag-fill me-1"></i> <?= e(ucfirst($post['category'])) ?>
            </span>
          </div>

          <div class="news-body-content">
            <div class="news-date-meta d-flex justify-content-between align-items-center mb-2">
              <span><i class="bi bi-calendar-event me-1"></i><?= date('d M Y', strtotime($post['created_at'])) ?></span>
              <span class="badge bg-light text-primary border" style="font-size:0.75rem"><i class="bi bi-eye-fill me-1"></i><span id="post-view-count-<?= $post['id'] ?>"><?= number_format($post['views'] ?? 0) ?></span> dibaca</span>
            </div>
            <h3 class="news-headline-title"><?= e($post['title']) ?></h3>
            <p class="news-snippet-text"><?= e($post['excerpt'] ?: excerptText($post['content'])) ?></p>
            <div class="pt-2 border-top">
              <button type="button" class="btn btn-link p-0 news-read-more border-0 bg-transparent text-decoration-none" onclick="openNewsModal(<?= $post['id'] ?>, <?= htmlspecialchars(json_encode([
                'id' => $post['id'],
                'title' => $post['title'],
                'category' => ucfirst($post['category']),
                'date' => date('d F Y', strtotime($post['created_at'])),
                'image' => !empty($post['image']) ? 'uploads/' . $post['image'] : '',
                'content' => nl2br(e($post['content'] ?: $post['excerpt']))
              ]), ENT_QUOTES) ?>)">
                Baca Rilis Lengkap <i class="bi bi-arrow-right"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="section-wrapper section-light" id="layanan">
  <div class="container">
    <div class="text-center mb-5 reveal">
      <span class="section-badge-pill"><i class="bi bi-shield-lock-fill"></i> Layanan Kepolisian</span>
      <h2 class="section-heading-title">Portal Layanan Publik Presisi</h2>
      <p class="section-heading-desc">
        Akses cepat seluruh layanan administrasi kepolisian, perizinan, dan laporan masyarakat secara digital dan terintegrasi.
      </p>
    </div>

    <div class="priority-service-banner reveal">
      <div class="row g-3 align-items-center">
        <div class="col-12 mb-2">
          <div class="d-flex align-items-center gap-2 text-warning fw-bold text-uppercase small">
            <i class="bi bi-stars"></i> Layanan Online Prioritas Nasional
          </div>
        </div>

        <div class="col-sm-6 col-lg-3">
          <a href="https://skck.polri.go.id" target="_blank" rel="noopener" class="service-pill-item">
            <div class="service-pill-icon"><i class="bi bi-file-earmark-check"></i></div>
            <div>
              <div class="fw-800 text-white font-display">SKCK Online</div>
              <div class="small text-white-50">skck.polri.go.id</div>
            </div>
          </a>
        </div>

        <div class="col-sm-6 col-lg-3">
          <a href="https://digitalkorlantas.id" target="_blank" rel="noopener" class="service-pill-item">
            <div class="service-pill-icon"><i class="bi bi-card-text"></i></div>
            <div>
              <div class="fw-800 text-white font-display">SIM Nasional (SINAR)</div>
              <div class="small text-white-50">digitalkorlantas.id</div>
            </div>
          </a>
        </div>

        <div class="col-sm-6 col-lg-3">
          <a href="https://dumaspresisi.polri.go.id" target="_blank" rel="noopener" class="service-pill-item">
            <div class="service-pill-icon"><i class="bi bi-chat-dots-fill"></i></div>
            <div>
              <div class="fw-800 text-white font-display">Dumas Presisi</div>
              <div class="small text-white-50">Pengaduan Online</div>
            </div>
          </a>
        </div>

        <div class="col-sm-6 col-lg-3">
          <a href="https://samsatdigital.id" target="_blank" rel="noopener" class="service-pill-item">
            <div class="service-pill-icon"><i class="bi bi-truck"></i></div>
            <div>
              <div class="fw-800 text-white font-display">SIGNAL E-Samsat</div>
              <div class="small text-white-50">Pajak Kendaraan</div>
            </div>
          </a>
        </div>
      </div>
    </div>

    <?php if (!empty($layanan)): ?>
    <div class="row g-4">
      <?php foreach ($layanan as $i => $lay): ?>
      <div class="col-sm-6 col-lg-3 reveal" style="transition-delay: <?= $i * 0.06 ?>s">
        <div class="layanan-bento-card">
          <div>
            <div class="layanan-icon-box">
              <i class="bi <?= e($lay['icon']) ?>"></i>
            </div>
            <h3 class="layanan-title"><?= e($lay['name']) ?></h3>
            <p class="layanan-desc"><?= e($lay['description']) ?></p>
          </div>
          <div>
            <?php if (!empty($lay['link'])): ?>
            <a href="<?= e($lay['link']) ?>" <?= (strpos($lay['link'], 'http') === 0) ? 'target="_blank" rel="noopener"' : '' ?> class="btn-layanan-action">
              <span>Buka Layanan</span>
              <i class="bi bi-arrow-right-circle-fill fs-6"></i>
            </a>
            <?php else: ?>
            <a href="#kontak" class="btn-layanan-action">
              <span>Informasi SPKT</span>
              <i class="bi bi-info-circle-fill fs-6"></i>
            </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="section-wrapper section-slate" id="satker">
  <div class="container">
    <div class="text-center mb-5 reveal">
      <span class="section-badge-pill"><i class="bi bi-diagram-3-fill"></i> Jajaran Kewilayahan</span>
      <h2 class="section-heading-title">Satuan Kerja &amp; 10 Polres Jajaran</h2>
      <p class="section-heading-desc">
        Temukan informasi alamat, kontak telepon, dan wilayah operasional Polres dan Satker Fungsi di bawah naungan Polda Bengkulu.
      </p>
    </div>

    <div class="row justify-content-center mb-4 reveal">
      <div class="col-lg-6 mb-3 mb-lg-0">
        <div class="input-group shadow-sm bg-white rounded-3 p-1 border">
          <span class="input-group-text bg-white border-0 text-muted"><i class="bi bi-search"></i></span>
          <input type="text" class="form-control border-0 shadow-none" id="satkerSearch" placeholder="Cari nama Polres, Ditlantas, Reskrim, Polairud..." autocomplete="off">
        </div>
      </div>
      <div class="col-lg-6">
        <div class="satker-filter-bar justify-content-lg-end">
          <button class="satker-filter-tab active" data-filter="all">
            <i class="bi bi-grid-fill"></i> Semua (<?= count($satkers) ?>)
          </button>
          <button class="satker-filter-tab" data-filter="polres">
            <i class="bi bi-building"></i> 10 Polres (<?= count($polres) ?>)
          </button>
          <button class="satker-filter-tab" data-filter="satker">
            <i class="bi bi-shield"></i> Satker Fungsi (<?= count($satkerFungsi) ?>)
          </button>
        </div>
      </div>
    </div>

    <div class="row g-4" id="satkerContainer">
      <?php foreach ($satkers as $sk): ?>
      <div class="col-md-6 col-lg-4 satker-item" data-type="<?= e($sk['type']) ?>" data-name="<?= strtolower(e($sk['name'] . ' ' . $sk['description'])) ?>">
        <div class="satker-card-modern">
          <div>
            <span class="satker-type-pill <?= $sk['type'] === 'polres' ? 'satker-type-polres' : 'satker-type-satker' ?>">
              <i class="bi <?= e($sk['icon']) ?>"></i>
              <?= $sk['type'] === 'polres' ? 'Polres / Polresta' : 'Satker Fungsi Polda' ?>
            </span>
            <h3 class="satker-name-title"><?= e($sk['name']) ?></h3>
            <p class="satker-address-text">
              <i class="bi bi-geo-alt text-danger me-1"></i>
              <?= e($sk['address'] ?: 'Mapolda Bengkulu, Jl. H. Adam Malik KM. 9') ?>
            </p>
          </div>

          <div class="pt-3 border-top d-flex align-items-center justify-content-between">
            <?php if (!empty($sk['phone'])): ?>
            <a href="tel:<?= preg_replace('/[^0-9]/', '', $sk['phone']) ?>" class="satker-phone-btn">
              <i class="bi bi-telephone-fill text-success"></i> <?= e($sk['phone']) ?>
            </a>
            <?php else: ?>
            <a href="tel:110" class="satker-phone-btn">
              <i class="bi bi-telephone-fill text-danger"></i> Call 110
            </a>
            <?php endif; ?>
            <span class="badge bg-light text-secondary border">Presisi</span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php
$gallery = db()->fetchAll("SELECT * FROM gallery ORDER BY created_at DESC LIMIT 8");
?>
<section class="section-wrapper section-light" id="galeri">
  <div class="container">
    <div class="text-center mb-5 reveal">
      <span class="section-badge-pill"><i class="bi bi-camera-fill"></i> Dokumentasi &amp; Multimedia</span>
      <h2 class="section-heading-title">Galeri Kegiatan Polda Bengkulu</h2>
      <p class="section-heading-desc">
        Dokumentasi visual patroli, pengamanan, kegiatan sosial, dan pelayanan masyarakat jajaran Polda Bengkulu.
      </p>
    </div>

    <?php if (!empty($gallery)): ?>
    <div class="gallery-grid-modern reveal">
      <?php foreach ($gallery as $img): ?>
      <div class="gallery-card-item" onclick="openLightbox('uploads/<?= e($img['filename']) ?>', '<?= e($img['title'] ?? 'Dokumentasi Polda Bengkulu') ?>')">
        <img src="uploads/<?= e($img['filename']) ?>" alt="<?= e($img['title'] ?? 'Galeri') ?>" loading="lazy">
        <div class="gallery-card-overlay">
          <div class="fw-bold font-display fs-6 text-white"><?= e($img['title'] ?? 'Dokumentasi Kegiatan') ?></div>
          <div class="small text-warning"><i class="bi bi-zoom-in"></i> Klik untuk memperbesar</div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    
    <div class="gallery-grid-modern reveal">
      <div class="gallery-card-item" style="background:linear-gradient(135deg,#0a192f,#1e40af)">
        <div class="d-flex flex-column align-items-center justify-content-center h-100 text-white-50 p-4 text-center">
          <i class="bi bi-shield-shaded fs-1 text-warning mb-2"></i>
          <span class="fw-bold text-white">Patroli Presisi Polda Bengkulu</span>
        </div>
      </div>
      <div class="gallery-card-item" style="background:linear-gradient(135deg,#0e2246,#0284c7)">
        <div class="d-flex flex-column align-items-center justify-content-center h-100 text-white-50 p-4 text-center">
          <i class="bi bi-car-front-fill fs-1 text-cyan-neon mb-2"></i>
          <span class="fw-bold text-white">Hari Lalu Lintas Bhayangkara</span>
        </div>
      </div>
      <div class="gallery-card-item" style="background:linear-gradient(135deg,#1b3d7a,#d97706)">
        <div class="d-flex flex-column align-items-center justify-content-center h-100 text-white-50 p-4 text-center">
          <i class="bi bi-heart-pulse-fill fs-1 text-danger mb-2"></i>
          <span class="fw-bold text-white">Bakti Kesehatan Bhayangkara</span>
        </div>
      </div>
      <div class="gallery-card-item" style="background:linear-gradient(135deg,#050d1a,#142d5c)">
        <div class="d-flex flex-column align-items-center justify-content-center h-100 text-white-50 p-4 text-center">
          <i class="bi bi-people-fill fs-1 text-warning mb-2"></i>
          <span class="fw-bold text-white">Jumat Curhat Polda Bengkulu</span>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="section-wrapper section-slate" id="kontak">
  <div class="container">
    <div class="text-center mb-5 reveal">
      <span class="section-badge-pill"><i class="bi bi-geo-alt-fill"></i> Pusat Pelayanan Terpadu</span>
      <h2 class="section-heading-title">Hubungi &amp; Kunjungi Mapolda Bengkulu</h2>
      <p class="section-heading-desc">
        Kantor Kepolisian Daerah Bengkulu dan Sentra Pelayanan Kepolisian Terpadu (SPKT) siap melayani 24 Jam.
      </p>
    </div>

    <div class="row g-4 align-items-stretch">
      <div class="col-lg-6 reveal">
        <div class="contact-glass-box h-100">
          <h3 class="font-display fw-800 text-navy-900 mb-4" style="color:var(--navy-900)">
            <i class="bi bi-building me-2 text-primary"></i> Markas Komando Polda Bengkulu
          </h3>

          <div class="contact-info-row">
            <div class="contact-info-icon"><i class="bi bi-geo-alt-fill"></i></div>
            <div>
              <div class="contact-info-title">Alamat Kantor</div>
              <div class="contact-info-value"><?= $s('contact_address') ?></div>
            </div>
          </div>

          <div class="contact-info-row">
            <div class="contact-info-icon"><i class="bi bi-telephone-inbound-fill"></i></div>
            <div>
              <div class="contact-info-title">Telepon &amp; Call Center</div>
              <div class="contact-info-value">
                <a href="tel:<?= $s('contact_phone') ?>" class="text-primary fw-bold"><?= $s('contact_phone') ?></a>
                &nbsp;&bull;&nbsp; Hotline Bebas Pulsa: <a href="tel:110" class="text-danger fw-800">110 (24 Jam)</a>
              </div>
            </div>
          </div>

          <div class="contact-info-row">
            <div class="contact-info-icon"><i class="bi bi-envelope-at-fill"></i></div>
            <div>
              <div class="contact-info-title">Email Resmi Humas</div>
              <div class="contact-info-value">
                <a href="mailto:<?= $s('contact_email') ?>" class="text-primary"><?= $s('contact_email') ?></a>
              </div>
            </div>
          </div>

          <div class="contact-info-row">
            <div class="contact-info-icon text-success" style="background:rgba(16,185,129,0.12)"><i class="bi bi-whatsapp"></i></div>
            <div>
              <div class="contact-info-title">Hotline WhatsApp Pengaduan</div>
              <div class="contact-info-value">
                <a href="https://wa.me/62<?= ltrim($s('contact_whatsapp', '08119224545'), '0') ?>" target="_blank" rel="noopener" class="text-success fw-bold">
                  <?= $s('contact_whatsapp') ?> (Respon Cepat)
                </a>
              </div>
            </div>
          </div>

          <div class="mt-4 pt-3 border-top d-flex flex-wrap gap-2">
            <?php if ($s('facebook_url')): ?>
            <a href="<?= $s('facebook_url') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-light border fw-bold">
              <i class="bi bi-facebook text-primary me-1"></i> Facebook
            </a>
            <?php endif; ?>
            <?php if ($s('instagram_url')): ?>
            <a href="<?= $s('instagram_url') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-light border fw-bold">
              <i class="bi bi-instagram text-danger me-1"></i> Instagram
            </a>
            <?php endif; ?>
            <?php if ($s('youtube_url')): ?>
            <a href="<?= $s('youtube_url') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-light border fw-bold">
              <i class="bi bi-youtube text-danger me-1"></i> YouTube
            </a>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-lg-6 reveal">
        <div class="map-embed-frame">
          <iframe
            src="<?= $s('contact_maps_embed', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3980.895318536109!2d102.31682187497426!3d-3.832717096141065!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e36b0460c5db6f3%3A0x629528dbebe6b579!2sPolda%20Bengkulu!5e0!3m2!1sid!2sid!4v1700000000000!5m2!1sid!2sid') ?>"
            allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
            title="Peta Lokasi Mapolda Bengkulu"></iframe>
        </div>
      </div>
    </div>
  </div>
</section>

<footer class="footer-modern">
  <div class="footer-grid-pattern"></div>
  <div class="container position-relative">
    <div class="row g-5">
      <div class="col-lg-4">
        <div class="d-flex align-items-center gap-3 mb-3">
          <img src="assets/img/logo-polri.png" alt="Logo Polri" style="width:50px;height:50px;object-fit:contain" onerror="this.style.display='none'">
          <div>
            <div class="font-display fw-900 fs-5 text-white"><?= $s('site_name') ?></div>
            <div class="small text-warning fw-bold text-uppercase">Polri Presisi Bengkulu</div>
          </div>
        </div>
        <p class="small text-white-50 mb-4 lh-lg">
          <?= $s('site_description') ?>
        </p>
        <div class="d-flex gap-2">
          <?php if ($s('facebook_url')): ?><a href="<?= $s('facebook_url') ?>" target="_blank" rel="noopener" class="footer-social-pill" title="Facebook"><i class="bi bi-facebook"></i></a><?php endif; ?>
          <?php if ($s('instagram_url')): ?><a href="<?= $s('instagram_url') ?>" target="_blank" rel="noopener" class="footer-social-pill" title="Instagram"><i class="bi bi-instagram"></i></a><?php endif; ?>
          <?php if ($s('youtube_url')): ?><a href="<?= $s('youtube_url') ?>" target="_blank" rel="noopener" class="footer-social-pill" title="YouTube"><i class="bi bi-youtube"></i></a><?php endif; ?>
          <?php if ($s('twitter_url')): ?><a href="<?= $s('twitter_url') ?>" target="_blank" rel="noopener" class="footer-social-pill" title="Twitter/X"><i class="bi bi-twitter-x"></i></a><?php endif; ?>
        </div>
      </div>

      <div class="col-sm-6 col-lg-2">
        <h4 class="footer-title">Navigasi Utama</h4>
        <ul class="footer-link-list">
          <li><a href="#profil"><i class="bi bi-chevron-right text-warning small"></i> Profil Presisi</a></li>
          <li><a href="#berita"><i class="bi bi-chevron-right text-warning small"></i> Kabar Berita</a></li>
          <li><a href="#layanan"><i class="bi bi-chevron-right text-warning small"></i> Layanan Publik</a></li>
          <li><a href="#satker"><i class="bi bi-chevron-right text-warning small"></i> Satker &amp; Polres</a></li>
          <li><a href="#galeri"><i class="bi bi-chevron-right text-warning small"></i> Dokumentasi</a></li>
          <li><a href="#kontak"><i class="bi bi-chevron-right text-warning small"></i> Kontak &amp; Lokasi</a></li>
        </ul>
      </div>

      <div class="col-sm-6 col-lg-3">
        <h4 class="footer-title">Layanan Digital</h4>
        <ul class="footer-link-list">
          <li><a href="https://skck.polri.go.id" target="_blank" rel="noopener"><i class="bi bi-chevron-right text-warning small"></i> SKCK Online Presisi</a></li>
          <li><a href="https://digitalkorlantas.id" target="_blank" rel="noopener"><i class="bi bi-chevron-right text-warning small"></i> SINAR SIM Online</a></li>
          <li><a href="https://dumaspresisi.polri.go.id" target="_blank" rel="noopener"><i class="bi bi-chevron-right text-warning small"></i> Dumas Presisi Online</a></li>
          <li><a href="https://samsatdigital.id" target="_blank" rel="noopener"><i class="bi bi-chevron-right text-warning small"></i> SIGNAL E-Samsat</a></li>
        </ul>
      </div>

      <div class="col-lg-3">
        <h4 class="footer-title">Layanan Darurat 24 Jam</h4>
        <div class="font-display fw-900 text-warning" style="font-size:2.8rem;line-height:1">110</div>
        <div class="small text-white-50 mb-3">Call Center Kepolisian Bebas Pulsa</div>
        <div class="small text-white-50">
          <i class="bi bi-geo-alt text-warning me-1"></i> <?= $s('contact_address') ?>
        </div>
      </div>
    </div>

    <div class="footer-bottom-bar d-flex flex-column flex-md-row justify-content-between align-items-center">
      <div>
        <?= $s('footer_text', '&copy; ' . date('Y') . ' Kepolisian Daerah Bengkulu. Semua Hak Dilindungi.') ?>
      </div>
      <div class="mt-2 mt-md-0 d-flex align-items-center gap-3">
        <a href="admin/" class="text-white-50 text-decoration-underline small">Panel Admin CMS</a>
        <span>&bull;</span>
        <span class="text-warning fw-bold small">Polri Presisi 2026</span>
      </div>
    </div>
  </div>
</footer>

<nav class="mobile-bottom-bar">
  <a href="#top" class="mobile-nav-btn active"><i class="bi bi-house-door-fill"></i><span>Beranda</span></a>
  <a href="#berita" class="mobile-nav-btn"><i class="bi bi-newspaper"></i><span>Berita</span></a>
  <a href="tel:110" class="mobile-nav-btn text-danger"><i class="bi bi-telephone-fill"></i><span>110</span></a>
  <a href="#layanan" class="mobile-nav-btn"><i class="bi bi-grid-fill"></i><span>Layanan</span></a>
  <a href="#kontak" class="mobile-nav-btn"><i class="bi bi-geo-alt-fill"></i><span>Kontak</span></a>
</nav>

<a href="https://wa.me/62<?= ltrim($s('contact_whatsapp', '08119224545'), '0') ?>" target="_blank" rel="noopener" class="wa-floating-btn" aria-label="WhatsApp Hotline Polda Bengkulu">
  <i class="bi bi-whatsapp"></i>
</a>

<div id="lightboxModal" style="display:none;position:fixed;inset:0;background:rgba(5,13,26,0.95);z-index:9999;align-items:center;justify-content:center;padding:1.5rem">
  <button onclick="closeLightbox()" style="position:absolute;top:1.5rem;right:1.5rem;background:none;border:none;color:#ffffff;font-size:2rem;cursor:pointer" aria-label="Tutup modal">
    <i class="bi bi-x-circle-fill"></i>
  </button>
  <div style="max-width:90vw;max-height:90vh;text-align:center">
    <img id="lightboxImg" src="" alt="" style="max-width:100%;max-height:80vh;border-radius:16px;object-fit:contain;border:1px solid rgba(255,255,255,0.2)">
    <p id="lightboxCaption" style="color:#ffffff;margin-top:1rem;font-weight:700;font-size:1.1rem"></p>
  </div>
</div>

<div class="modal fade" id="newsModal" tabindex="-1" aria-labelledby="newsModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
      <div class="modal-header border-0 bg-dark text-white p-4">
        <div>
          <span class="badge bg-warning text-dark mb-2" id="newsModalCategory">Berita</span>
          <h4 class="modal-title font-display fw-bold text-white mb-0" id="newsModalTitle">Judul Berita</h4>
          <div class="small text-white-50 mt-1" id="newsModalMeta"><i class="bi bi-calendar-event me-1"></i><span id="newsModalDate"></span></div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body p-4">
        <div id="newsModalImgWrap" class="mb-4 text-center" style="display:none">
          <img id="newsModalImg" src="" alt="" class="img-fluid rounded-3 shadow-sm" style="max-height:380px;object-fit:cover">
        </div>
        <div id="newsModalContent" style="line-height:1.8;color:#0f172a;font-size:1.02rem"></div>
      </div>
      <div class="modal-footer border-top bg-light p-3">
        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<script>
  function openNewsModal(postId, data) {
    document.getElementById('newsModalTitle').textContent = data.title;
    document.getElementById('newsModalCategory').textContent = data.category;
    document.getElementById('newsModalDate').textContent = data.date;
    document.getElementById('newsModalContent').innerHTML = data.content;
    
    const imgWrap = document.getElementById('newsModalImgWrap');
    const img = document.getElementById('newsModalImg');
    if (data.image) {
      img.src = data.image;
      imgWrap.style.display = 'block';
    } else {
      imgWrap.style.display = 'none';
    }
    
    const modal = new bootstrap.Modal(document.getElementById('newsModal'));
    modal.show();

    fetch('?api=view_post&id=' + postId)
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          const el = document.getElementById('post-view-count-' + postId);
          if (el) el.textContent = res.views;
        }
      }).catch(e => console.error(e));
  }

  const heroSearchInput = document.getElementById('heroSearchInput');
  const heroSearchBtn = document.getElementById('heroSearchBtn');
  if (heroSearchInput && heroSearchBtn) {
    heroSearchInput.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        const val = heroSearchInput.value.toLowerCase().trim();
        if (val) {
          const satkerInput = document.getElementById('satkerSearch');
          if (satkerInput) {
            satkerInput.value = val;
            satkerInput.dispatchEvent(new Event('input'));
            document.getElementById('satker')?.scrollIntoView({ behavior: 'smooth' });
          }
        }
      }
    });
  }
</script>
</body>
</html>
