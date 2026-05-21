-- Data Awal (Seed) untuk Database Tracer Study (MySQL)

-- Hapus data lama agar bersih
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM surveys;
DELETE FROM alumni;
DELETE FROM admins;
ALTER TABLE surveys AUTO_INCREMENT = 1;
ALTER TABLE alumni AUTO_INCREMENT = 1;
ALTER TABLE admins AUTO_INCREMENT = 1;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Input Admin Default (Username: admin, Password: admin123)
-- Hash bcrypt untuk 'admin123'
INSERT INTO admins (username, password_hash, nama) 
VALUES ('admin', '$2y$10$l4.crqZY4vJkJFcYOZuGa.WPRhC3D21IiSZY5/SntrpGnVE94X66C', 'Administrator Master');

-- 2. Input Data Alumni Dummy
INSERT INTO alumni (nama_lengkap, nim, tahun_lulus, email) 
VALUES 
('Ahmad Budi', 'A71010001', 2020, 'a71010001@student.ums.ac.id'),
('Siti Aminah', 'A71010002', 2021, 'a71010002@student.ums.ac.id'),
('Joko Susilo', 'A71010003', 2019, 'a71010003@student.ums.ac.id'),
('Rina Wijayanti', 'A710180052', 2022, 'a710180052@student.ums.ac.id'),
('Bambang Pamungkas', 'A71020005', 2020, NULL),
('Dewi Lestari', 'A71021008', 2021, NULL);

-- 3. Input Data Survey Dummy (Ahmad Budi sudah mengisi)
INSERT INTO surveys (
    alumni_id, 
    tahun_lulus_konfirmasi, 
    status_pekerjaan, 
    nama_instansi, 
    nomor_hp, 
    lanjut_s2s3, 
    jurusan_s2s3, 
    universitas_s2s3, 
    lanjut_ppg, 
    tahun_ppg, 
    universitas_ppg, 
    pesan_saran
) VALUES (
    1, -- Referensi Ahmad Budi
    2020,
    'GURU',
    'SMA Muhammadiyah 1 Surakarta',
    '081234567890',
    0, NULL, NULL, -- Tidak lanjut S2/S3
    1, 2023, 'Universitas Muhammadiyah Surakarta', -- Lanjut PPG
    'Program tracer study sangat membantu prodi untuk akreditasi.'
);
