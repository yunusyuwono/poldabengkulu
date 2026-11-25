<?php

require_once __DIR__ . '/config.php';

class Database {
    private static ?self $instance = null;
    private PDO $pdo;

    private function __construct() {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ]);
            $this->initialize();
        } catch (PDOException $e) {
            error_log('DB Error: ' . $e->getMessage());
            die('Database connection failed.');
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPdo(): PDO {
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): PDOStatement {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll(string $sql, array $params = []): array {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetchOne(string $sql, array $params = []): ?array {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }

    public function insert(string $table, array $data): int {
        $columns      = implode(', ', array_map(fn($k) => "`$k`", array_keys($data)));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql          = "INSERT INTO `{$table}` ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, $data);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int {
        $setClause = implode(', ', array_map(fn($k) => "`{$k}` = :{$k}", array_keys($data)));
        $sql       = "UPDATE `{$table}` SET {$setClause} WHERE {$where}";
        $stmt      = $this->query($sql, array_merge($data, $whereParams));
        return $stmt->rowCount();
    }

    public function delete(string $table, string $where, array $params = []): int {
        $stmt = $this->query("DELETE FROM `{$table}` WHERE {$where}", $params);
        return $stmt->rowCount();
    }

    private function initialize(): void {
        $this->seedDefaults();
    }

    private function seedDefaults(): void {
        $admin = $this->fetchOne("SELECT id FROM users WHERE username = 'admin'");
        if (!$admin) {
            $this->insert('users', [
                'username'      => DEFAULT_ADMIN_USER,
                'password_hash' => password_hash(DEFAULT_ADMIN_PASS, PASSWORD_BCRYPT, ['cost' => 12]),
                'email'         => 'pidbidhumaspoldabengkulu@gmail.com',
                'full_name'     => 'Administrator Polda Bengkulu',
                'role'          => 'superadmin',
            ]);
        }

        $defaults = [
            'site_name'            => 'Polda Bengkulu',
            'site_tagline'         => 'Presisi Melayani Masyarakat Bengkulu',
            'site_description'     => 'Portal Resmi Kepolisian Daerah Bengkulu (Polda Bengkulu). Menyediakan informasi terkini, profil instansi, jajaran Polres/Satker, dan pelayanan publik Polri Presisi 24 jam.',
            'kapolda_name'         => 'Irjen Pol Yudhi Sulistianto Wahid, S.I.K.',
            'kapolda_title'        => 'Kepala Kepolisian Daerah Bengkulu',
            'kapolda_quote'        => 'Polda Bengkulu berkomitmen mewujudkan Polri Presisi yang prediktif, responsibilitas, dan transparansi berkeadilan demi memberikan perlindungan, pengayoman, dan pelayanan prima kepada seluruh masyarakat Provinsi Bengkulu.',
            'kapolda_photo'        => 'assets/img/kapolda.jpg',
            'contact_address'      => 'Jl. H. Adam Malik No. KM. 9, Sido Mulyo, Kec. Gading Cempaka, Kota Bengkulu, Bengkulu 38211',
            'contact_phone'        => '(0736) 51213 / 52089',
            'contact_email'        => 'pidbidhumaspoldabengkulu@gmail.com',
            'contact_whatsapp'     => '08119224545',
            'contact_maps_embed'   => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3980.895318536109!2d102.31682187497426!3d-3.832717096141065!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e36b0460c5db6f3%3A0x629528dbebe6b579!2sPolda%20Bengkulu!5e0!3m2!1sid!2sid!4v1700000000000!5m2!1sid!2sid',
            'hero_stat_satker'     => '24',
            'hero_stat_polres'     => '10',
            'hero_stat_personil'   => '5.000+',
            'site_views'           => '0',
            'marquee_text'         => 'Selamat datang di Website Resmi Kepolisian Daerah Bengkulu | Layanan Darurat Bebas Pulsa: Call Center 110 | Hotline WhatsApp: 0811-9224-545 | SKCK Online: skck.polri.go.id | SIM Online: Digital Korlantas Polri | Dumas Presisi: dumaspresisi.polri.go.id',
            'facebook_url'         => 'https://facebook.com/poldabengkulu',
            'instagram_url'        => 'https://instagram.com/polda_bengkulu',
            'youtube_url'          => 'https://youtube.com/@poldabengkuluofficial',
            'twitter_url'          => 'https://x.com/poldabengkulu',
            'footer_text'          => '© ' . date('Y') . ' Kepolisian Daerah Bengkulu. Semua Hak Dilindungi.',
        ];

        foreach ($defaults as $key => $value) {
            $existing = $this->fetchOne("SELECT `key` FROM settings WHERE `key` = ?", [$key]);
            if (!$existing) {
                $this->insert('settings', ['key' => $key, 'value' => $value]);
            }
        }

        $satkerCount = $this->fetchOne("SELECT COUNT(*) as cnt FROM satkers")['cnt'];
        if ($satkerCount == 0) {
            $satkers = [
                ['Polresta Bengkulu', 'polres', 'Kepolisian Resor Kota Bengkulu', 'Jl. A. Yani No. 1, Malabero, Kec. Teluk Segara, Kota Bengkulu', '(0736) 21176', 'bi-building-fill', 1],
                ['Polres Bengkulu Utara', 'polres', 'Kepolisian Resor Bengkulu Utara', 'Jl. M. Yamin SH, Gn. Agung, Arga Makmur, Kab. Bengkulu Utara', '(0737) 71144', 'bi-building-fill', 2],
                ['Polres Bengkulu Selatan', 'polres', 'Kepolisian Resor Bengkulu Selatan', 'Jl. Syamsul Bahrun, Manna, Kab. Bengkulu Selatan', '(0739) 21044', 'bi-building-fill', 3],
                ['Polres Rejang Lebong', 'polres', 'Kepolisian Resor Rejang Lebong', 'Jl. Basuki Rahmad No. 8, Curup, Kab. Rejang Lebong', '(0732) 21440', 'bi-building-fill', 4],
                ['Polres Kepahiang', 'polres', 'Kepolisian Resor Kepahiang', 'Jl. Pelangkian, Kec. Kepahiang, Kab. Kepahiang', '(0732) 392316', 'bi-building-fill', 5],
                ['Polres Lebong', 'polres', 'Kepolisian Resor Lebong', 'Jl. Raya Desa Tanjung Agung, Lebong Atas, Kab. Lebong', '(0738) 21004', 'bi-building-fill', 6],
                ['Polres Mukomuko', 'polres', 'Kepolisian Resor Mukomuko', 'Jl. Danau Nimbung KM 2.2, SP IV, Kab. Mukomuko', '(0737) 771999', 'bi-building-fill', 7],
                ['Polres Kaur', 'polres', 'Kepolisian Resor Kaur', 'Jl. Sinar Pagi, Bintuhan, Kec. Kaur Selatan, Kab. Kaur', '(0739) 6670014', 'bi-building-fill', 8],
                ['Polres Seluma', 'polres', 'Kepolisian Resor Seluma', 'Jl. Raya Bengkulu-Manna KM 65, Seluma Timur, Kab. Seluma', '0811-7381-110', 'bi-building-fill', 9],
                ['Polres Bengkulu Tengah', 'polres', 'Kepolisian Resor Bengkulu Tengah', 'Jl. Raya Bengkulu-Curup KM 10, Talang Empat, Kab. Bengkulu Tengah', '0811-7390-110', 'bi-building-fill', 10],
                ['Dit Reskrimum', 'satker', 'Direktorat Reserse Kriminal Umum', 'Mapolda Bengkulu, Jl. Adam Malik KM 9, Kota Bengkulu', '(0736) 51213', 'bi-search', 11],
                ['Dit Reskrimsus', 'satker', 'Direktorat Reserse Kriminal Khusus', 'Mapolda Bengkulu, Jl. Adam Malik KM 9, Kota Bengkulu', '(0736) 51213', 'bi-cpu', 12],
                ['Dit Resnarkoba', 'satker', 'Direktorat Reserse Narkoba', 'Mapolda Bengkulu, Jl. Adam Malik KM 9, Kota Bengkulu', '(0736) 51213', 'bi-shield-x', 13],
                ['Dit Lantas', 'satker', 'Direktorat Lalu Lintas', 'Jl. Adam Malik KM 9, Kota Bengkulu', '(0736) 51213', 'bi-car-front', 14],
                ['Dit Intelkam', 'satker', 'Direktorat Intelijen Keamanan', 'Mapolda Bengkulu, Jl. Adam Malik KM 9, Kota Bengkulu', '(0736) 51213', 'bi-eye', 15],
                ['Dit Polairud', 'satker', 'Direktorat Kepolisian Perairan dan Udara', 'Kawasan Pelabuhan Pulau Baai, Kota Bengkulu', '(0736) 51213', 'bi-tsunami', 16],
                ['Sat Brimob Polda Bengkulu', 'satker', 'Satuan Brigade Mobil Polda Bengkulu', 'Mako Brimob, Surabaya, Kota Bengkulu', '(0736) 51213', 'bi-shield-shaded', 17],
                ['Bidang Humas', 'satker', 'Bidang Hubungan Masyarakat', 'Mapolda Bengkulu, Jl. Adam Malik KM 9, Kota Bengkulu', '08119224545', 'bi-megaphone', 18],
                ['Biro SDM', 'satker', 'Biro Sumber Daya Manusia', 'Mapolda Bengkulu, Jl. Adam Malik KM 9, Kota Bengkulu', '(0736) 51213', 'bi-people', 19],
                ['RS Bhayangkara', 'satker', 'Rumah Sakit Bhayangkara Tk. III Bengkulu', 'Jl. Veteran No. 2, Pasar Jik, Kota Bengkulu', '(0736) 21118', 'bi-hospital', 20],
            ];
            foreach ($satkers as $s) {
                $this->insert('satkers', [
                    'name' => $s[0], 'type' => $s[1], 'description' => $s[2],
                    'address' => $s[3], 'phone' => $s[4], 'icon' => $s[5], 'order_num' => $s[6]
                ]);
            }
        }

        $layananCount = $this->fetchOne("SELECT COUNT(*) as cnt FROM layanan")['cnt'];
        if ($layananCount == 0) {
            $layanans = [
                ['Call Center 110', 'Layanan Darurat Bebas Pulsa 24 Jam Kepolisian Respon Cepat', 'bi-telephone-inbound-fill', 'tel:110', 'darurat', 1],
                ['SKCK Online Presisi', 'Penerbitan Surat Keterangan Catatan Kepolisian secara online', 'bi-file-earmark-check-fill', 'https://skck.polri.go.id', 'layanan', 2],
                ['SIM Online (SINAR)', 'Pendaftaran & Perpanjangan SIM Nasional Presisi via Digital Korlantas', 'bi-card-text', 'https://digitalkorlantas.id', 'layanan', 3],
                ['Dumas Presisi', 'Layanan Pengaduan Masyarakat Terpadu Secara Transparan dan Akuntabel', 'bi-chat-dots-fill', 'https://dumaspresisi.polri.go.id', 'layanan', 4],
                ['SPKT / Laporan Polisi', 'Sentra Pelayanan Kepolisian Terpadu Siap Melayani 24 Jam Non-Stop', 'bi-shield-check', '#kontak', 'layanan', 5],
                ['SIGNAL (Samsat Digital)', 'Pembayaran Pajak Kendaraan Bermotor (PKB) & SWDKLLJ Online', 'bi-truck', 'https://samsatdigital.id', 'layanan', 6],
                ['Hotline WhatsApp Polda', 'Layanan Informasi dan Layanan Cepat Masyarakat Polda Bengkulu', 'bi-whatsapp', 'https://wa.me/628119224545', 'darurat', 7],
                ['Izin Keramaian Online', 'Pelayanan Izin Keramaian dan Kegiatan Masyarakat Terpadu Dit Intelkam', 'bi-file-earmark-text', '#kontak', 'layanan', 8],
            ];
            foreach ($layanans as $l) {
                $this->insert('layanan', [
                    'name' => $l[0], 'description' => $l[1], 'icon' => $l[2],
                    'link' => $l[3], 'category' => $l[4], 'order_num' => $l[5]
                ]);
            }
        }

        $postCount = $this->fetchOne("SELECT COUNT(*) as cnt FROM posts")['cnt'];
        if ($postCount == 0) {
            $posts = [
                [
                    'Ditlantas Polda Bengkulu Peringati Hari Lalu Lintas Bhayangkara ke-71 dengan Pelayanan Presisi',
                    'hut-lalu-lintas-bhayangkara-71-polda-bengkulu',
                    'Dalam rangka memperingati Hari Lalu Lintas Bhayangkara ke-71 tahun 2026, Direktorat Lalu Lintas (Ditlantas) Polda Bengkulu menyelenggarakan serangkaian kegiatan bakti sosial, edukasi tertib berlalu lintas di sekolah-sekolah, dan penguatan pelayanan SIM/Samsat keliling bagi masyarakat Bengkulu.',
                    'berita', 1
                ],
                [
                    'Ditreskrimsus Polda Bengkulu Ungkap Kasus Praktik Ilegal Tanpa Izin di Kota Bengkulu',
                    'ditreskrimsus-ungkap-praktik-ilegal',
                    'Subdit Indagsi Ditreskrimsus Polda Bengkulu berhasil mengungkap praktik ilegal pelayanan medis/estetika tanpa izin resmi di wilayah Kota Bengkulu.',
                    'berita', 1
                ],
                [
                    'Subdit Ditresnarkoba Polda Bengkulu Amankan Tersangka Pengedar Narkotika dalam Pengembangan Kasus',
                    'ditresnarkoba-amankan-pengedar-sabu-bengkulu',
                    'Jajaran Ditresnarkoba Polda Bengkulu kembali menunjukkan komitmen pemberantasan narkotika dengan mengamankan terduga pengedar narkotika di kawasan Kota Bengkulu.',
                    'berita', 1
                ],
                [
                    'Capaian Operasi Musang Nala 2026: Jajaran Polda Bengkulu Berhasil Ungkap 93 Kasus Kejahatan 3C',
                    'capaian-operasi-musang-nala-2026',
                    'Hasil evaluasi Operasi Musang Nala 2026 mencatat Polda Bengkulu dan Polres jajaran berhasil mengungkap 93 kasus tindak pidana konvensional dengan 110 tersangka.',
                    'berita', 1
                ],
                [
                    'Kapolda Bengkulu Irjen Pol Yudhi Sulistianto Wahid Pimpin Apel Kesiapsiagaan Kamtibmas',
                    'kapolda-bengkulu-pimpin-apel-kesiapsiagaan-2026',
                    'Kapolda Bengkulu Irjen Pol Yudhi Sulistianto Wahid, S.I.K. memimpin apel gelar pasukan kesiapsiagaan personel jajaran dalam rangka menjaga stabilitas keamanan dan ketertiban masyarakat.',
                    'berita', 1
                ],
                [
                    'Wakapolda Bengkulu Kombes Pol Prianto Teguh Nugroho Tinjau Inovasi Pelayanan Publik Presisi',
                    'wakapolda-tinjau-pelayanan-publik-presisi-2026',
                    'Wakapolda Bengkulu Kombes Pol. Prianto Teguh Nugroho, S.I.K., M.H. melaksanakan kunjungan kerja sekaligus supervisi ke sentra-sentra pelayanan publik SPKT, SKCK, dan Satpas SIM.',
                    'pengumuman', 1
                ],
            ];
            foreach ($posts as $p) {
                $this->insert('posts', [
                    'title'     => $p[0],
                    'slug'      => $p[1],
                    'content'   => $p[2],
                    'excerpt'   => mb_substr($p[2], 0, 200) . '...',
                    'category'  => $p[3],
                    'status'    => 'published',
                    'author_id' => 1,
                ]);
            }
        }

        $visitorCount = $this->fetchOne("SELECT COUNT(*) as cnt FROM visitor_logs")['cnt'];
        if ($visitorCount == 0) {
            for ($d = 30; $d >= 0; $d--) {
                $dateStr     = date('Y-m-d', strtotime("-$d days"));
                $dailyVisits = mt_rand(18, 48);
                for ($v = 0; $v < $dailyVisits; $v++) {
                    $this->insert('visitor_logs', [
                        'ip'           => '127.0.0.' . mt_rand(1, 250),
                        'user_agent'   => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                        'visited_date' => $dateStr,
                        'created_at'   => $dateStr . ' ' . sprintf('%02d:%02d:%02d', mt_rand(7, 22), mt_rand(0, 59), mt_rand(0, 59)),
                    ]);
                }
            }
        }
    }

    private function __clone() {}
    public function __wakeup(): void { throw new \Exception("Cannot unserialize singleton"); }
}

function db(): Database {
    return Database::getInstance();
}

function getSetting(string $key, string $default = ''): string {
    $row = db()->fetchOne("SELECT value FROM settings WHERE `key` = ?", [$key]);
    return ($row && $row['value'] !== null) ? (string)$row['value'] : $default;
}
