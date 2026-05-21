-- Skema Database Tracer Study PTI UMS (MySQL Dialect)

-- Hapus tabel jika sudah ada (sesuai urutan dependency)
DROP TABLE IF EXISTS surveys;
DROP TABLE IF EXISTS alumni;
DROP TABLE IF EXISTS admins;

-- 1. Tabel Admins
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nama VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT chk_admins_username CHECK (TRIM(username) <> ''),
    CONSTRAINT chk_admins_password CHECK (TRIM(password_hash) <> ''),
    CONSTRAINT chk_admins_nama CHECK (TRIM(nama) <> '')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabel Alumni
CREATE TABLE alumni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(255) NOT NULL,
    nim VARCHAR(50) NOT NULL UNIQUE,
    tahun_lulus INT NULL,
    tanggal_lahir DATE NULL,
    email VARCHAR(255) NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT chk_alumni_nama CHECK (TRIM(nama_lengkap) <> ''),
    CONSTRAINT chk_alumni_nim CHECK (TRIM(nim) <> ''),
    CONSTRAINT chk_alumni_tahun CHECK (tahun_lulus IS NULL OR (tahun_lulus >= 1950 AND tahun_lulus <= 2100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index untuk mempercepat pencarian alumni berdasarkan nama
CREATE INDEX idx_alumni_nama_lengkap ON alumni(nama_lengkap);
CREATE INDEX idx_alumni_tahun_lulus ON alumni(tahun_lulus);

-- 3. Tabel Surveys
CREATE TABLE surveys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alumni_id INT NOT NULL UNIQUE,
    tahun_lulus_konfirmasi INT NOT NULL,
    status_pekerjaan ENUM('BELUM_BEKERJA', 'GURU', 'NON_PENDIDIKAN', 'MAHASISWA_S2_S3', 'LAINNYA') NOT NULL,
    status_pekerjaan_detail VARCHAR(255) NULL,
    nama_instansi VARCHAR(255) NOT NULL,
    nomor_hp VARCHAR(20) NOT NULL,
    lanjut_s2s3 TINYINT(1) NOT NULL,
    jurusan_s2s3 VARCHAR(255) NULL,
    universitas_s2s3 VARCHAR(255) NULL,
    lanjut_ppg TINYINT(1) NOT NULL,
    tahun_ppg INT NULL,
    universitas_ppg VARCHAR(255) NULL,
    pesan_saran TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_surveys_instansi CHECK (TRIM(nama_instansi) <> ''),
    CONSTRAINT chk_surveys_nomor_hp CHECK (nomor_hp REGEXP '^[0-9]{10,15}$'),
    CONSTRAINT chk_surveys_tahun_lulus CHECK (tahun_lulus_konfirmasi >= 1950 AND tahun_lulus_konfirmasi <= 2100),
    CONSTRAINT chk_surveys_tahun_ppg CHECK (tahun_ppg IS NULL OR (tahun_ppg >= 1950 AND tahun_ppg <= 2100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index untuk relasi dan agregasi dashboard
CREATE INDEX idx_surveys_status_pekerjaan ON surveys(status_pekerjaan);
