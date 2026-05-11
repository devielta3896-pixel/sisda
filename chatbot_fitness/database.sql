-- ============================================================
--  DATABASE: Chatbot Fitness Rule-Based
--  Sistem Cerdas - Knowledge-Based System
-- ============================================================

CREATE DATABASE IF NOT EXISTS chatbot_fitness
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE chatbot_fitness;

-- ============================================================
-- TABEL 1: users
-- Menyimpan data profil user
-- ============================================================
CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama          VARCHAR(100) NOT NULL,
  tinggi_badan  DECIMAL(5,2) NOT NULL COMMENT 'dalam cm',
  berat_badan   DECIMAL(5,2) NOT NULL COMMENT 'dalam kg',
  bmi           DECIMAL(5,2) GENERATED ALWAYS AS
                  (berat_badan / ((tinggi_badan / 100) * (tinggi_badan / 100)))
                  STORED COMMENT 'dihitung otomatis',
  bmi_kategori  VARCHAR(20)  GENERATED ALWAYS AS (
                  CASE
                    WHEN (berat_badan / ((tinggi_badan/100)*(tinggi_badan/100))) < 18.5
                      THEN 'underweight'
                    WHEN (berat_badan / ((tinggi_badan/100)*(tinggi_badan/100))) < 25.0
                      THEN 'normal'
                    WHEN (berat_badan / ((tinggi_badan/100)*(tinggi_badan/100))) < 30.0
                      THEN 'overweight'
                    ELSE 'obesitas'
                  END
                ) STORED COMMENT 'dikategorikan otomatis dari BMI',
  jenis_kelamin ENUM('pria', 'wanita') NOT NULL,
  umur          TINYINT UNSIGNED NOT NULL COMMENT 'dalam tahun',
  umur_kategori VARCHAR(20)  GENERATED ALWAYS AS (
                  CASE
                    WHEN umur BETWEEN 13 AND 17 THEN 'remaja'
                    WHEN umur BETWEEN 18 AND 35 THEN 'dewasa_muda'
                    WHEN umur BETWEEN 36 AND 50 THEN 'dewasa'
                    ELSE 'lansia'
                  END
                ) STORED COMMENT 'dikategorikan otomatis dari umur',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- ============================================================
-- TABEL 2: sessions
-- Setiap sesi konsultasi chatbot
-- ============================================================
CREATE TABLE sessions (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  disabilitas ENUM('none', 'knee', 'back', 'shoulder') NOT NULL DEFAULT 'none',
  goals       ENUM('weight_loss', 'muscle_gain', 'stamina', 'general') NOT NULL,
  frekuensi   ENUM('pemula', 'aktif', 'rutin') NOT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_sessions_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;


-- ============================================================
-- TABEL 3: exercises
-- Knowledge base semua latihan yang tersedia
-- ============================================================
CREATE TABLE exercises (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama_latihan     VARCHAR(100) NOT NULL,
  deskripsi        TEXT NOT NULL,
  durasi_menit     TINYINT UNSIGNED NULL  COMMENT 'isi jika berbasis waktu',
  set_jumlah       TINYINT UNSIGNED NULL  COMMENT 'isi jika berbasis set/rep',
  repetisi         TINYINT UNSIGNED NULL  COMMENT 'jumlah repetisi per set',
  target_otot      VARCHAR(150) NOT NULL  COMMENT 'contoh: paha, betis, core',
  intensitas       ENUM('ringan', 'sedang', 'tinggi') NOT NULL DEFAULT 'sedang',
  dilarang_untuk   SET('knee', 'back', 'shoulder') DEFAULT ''
                   COMMENT 'kosong = aman untuk semua disabilitas'
) ENGINE=InnoDB;


-- ============================================================
-- TABEL 4: rules
-- Aturan IF-THEN sistem cerdas
-- ============================================================
CREATE TABLE rules (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  goals          ENUM('weight_loss', 'muscle_gain', 'stamina', 'general') NOT NULL,
  bmi_kategori   ENUM('underweight', 'normal', 'overweight', 'obesitas', 'any') NOT NULL DEFAULT 'any',
  umur_kategori  ENUM('remaja', 'dewasa_muda', 'dewasa', 'lansia', 'any') NOT NULL DEFAULT 'any',
  jenis_kelamin  ENUM('pria', 'wanita', 'any') NOT NULL DEFAULT 'any',
  frekuensi      ENUM('pemula', 'aktif', 'rutin', 'any') NOT NULL DEFAULT 'any',
  exercise_ids   JSON NOT NULL COMMENT 'array ID latihan yang direkomendasikan',
  keterangan     VARCHAR(255) NULL COMMENT 'deskripsi rule untuk keperluan debug',
  prioritas      TINYINT UNSIGNED NOT NULL DEFAULT 5
                 COMMENT 'makin kecil = makin spesifik, makin diprioritaskan'
) ENGINE=InnoDB;


-- ============================================================
-- TABEL 5: recommendations
-- Hasil rekomendasi yang diberikan ke user per sesi
-- ============================================================
CREATE TABLE recommendations (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_id   INT UNSIGNED NOT NULL,
  rule_id      INT UNSIGNED NOT NULL,
  exercise_id  INT UNSIGNED NOT NULL,
  catatan      TEXT NULL COMMENT 'catatan khusus, misal: modifikasi karena disabilitas',
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_rec_session
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
  CONSTRAINT fk_rec_rule
    FOREIGN KEY (rule_id) REFERENCES rules(id),
  CONSTRAINT fk_rec_exercise
    FOREIGN KEY (exercise_id) REFERENCES exercises(id)
) ENGINE=InnoDB;


-- ============================================================
-- DATA AWAL: exercises (knowledge base latihan)
-- ============================================================
INSERT INTO exercises
  (nama_latihan, deskripsi, durasi_menit, set_jumlah, repetisi, target_otot, intensitas, dilarang_untuk)
VALUES
-- Latihan ringan / cardio
('Jalan cepat',      'Berjalan dengan kecepatan 5-6 km/jam, jaga postur tegak.',                              30, NULL, NULL, 'seluruh tubuh, kardiovaskular', 'ringan',  ''),
('Jalan santai',     'Berjalan santai dengan kecepatan 3-4 km/jam, cocok untuk pemulihan.',                   20, NULL, NULL, 'seluruh tubuh, kardiovaskular', 'ringan',  ''),
('Jogging ringan',   'Lari pelan dengan napas terkontrol, kecepatan 7-8 km/jam.',                             20, NULL, NULL, 'kaki, kardiovaskular',          'sedang',  'knee'),
('Bersepeda statis', 'Kayuh dengan resistensi rendah, jaga lutut agar tidak terlalu menekuk.',                30, NULL, NULL, 'paha, betis, kardiovaskular',   'sedang',  ''),
('Renang',           'Gaya bebas atau punggung, fokus pada teknik pernapasan.',                                30, NULL, NULL, 'seluruh tubuh, kardiovaskular', 'sedang',  ''),

-- Latihan kekuatan tubuh bagian atas
('Push-up',          'Posisi plank, turunkan dada hingga hampir menyentuh lantai, dorong kembali.',           NULL, 3, 12, 'dada, trisep, bahu',            'sedang',  'shoulder'),
('Push-up modifikasi','Push-up dengan lutut menyentuh lantai, lebih mudah untuk pemula.',                     NULL, 3, 10, 'dada, trisep',                  'ringan',  'shoulder'),
('Pull-up',          'Gantung di bar, tarik tubuh ke atas hingga dagu melewati bar.',                         NULL, 3,  8, 'punggung, bisep',               'tinggi',  'shoulder'),
('Pike push-up',     'Posisi V terbalik, tekuk siku untuk melatih bahu tanpa beban berlebih.',                NULL, 3, 10, 'bahu, trisep',                  'sedang',  'shoulder'),
('Tricep dips',      'Duduk di pinggir kursi, turunkan tubuh dengan lengan, dorong kembali.',                 NULL, 3, 12, 'trisep, bahu',                  'sedang',  'shoulder'),

-- Latihan kekuatan tubuh bagian bawah
('Squat',            'Berdiri kaki selebar bahu, turunkan panggul seperti duduk, punggung lurus.',            NULL, 3, 12, 'paha depan, gluteus, betis',    'sedang',  'knee'),
('Wall sit',         'Bersandar di dinding, tekuk lutut 90 derajat, tahan posisi.',                          NULL, 3, 30, 'paha depan, gluteus',           'sedang',  'knee'),
('Glute bridge',     'Berbaring, tekuk lutut, angkat pinggul hingga membentuk garis lurus.',                  NULL, 3, 15, 'gluteus, paha belakang, core',  'ringan',  ''),
('Lunge',            'Langkahkan kaki ke depan, turunkan lutut belakang hampir ke lantai.',                   NULL, 3, 10, 'paha, gluteus',                 'sedang',  'knee'),
('Step-up',          'Naik turun anak tangga atau kotak rendah, bergantian kiri kanan.',                      NULL, 3, 12, 'paha, gluteus, betis',          'ringan',  'knee'),

-- Latihan core
('Plank',            'Posisi push-up, tahan tubuh lurus seperti papan, jaga core tetap kencang.',             NULL, 3, 30, 'core, bahu, punggung',          'sedang',  'shoulder'),
('Plank modifikasi', 'Plank dengan lutut di lantai, lebih mudah untuk pemula atau cedera bahu.',              NULL, 3, 30, 'core',                          'ringan',  ''),
('Crunch',           'Berbaring, angkat kepala dan bahu perlahan menggunakan otot perut.',                    NULL, 3, 15, 'perut atas',                    'ringan',  'back'),
('Dead bug',         'Berbaring, luruskan lengan dan kaki berlawanan secara bergantian, punggung tetap datar.',NULL, 3, 10, 'core, stabilitas punggung',     'ringan',  ''),
('Bird dog',         'Posisi merangkak, luruskan tangan kanan dan kaki kiri secara bersamaan.',               NULL, 3, 10, 'core, punggung, keseimbangan',  'ringan',  ''),

-- Latihan fleksibilitas / mobilitas
('Peregangan paha',  'Berdiri, pegang pergelangan kaki ke belakang, tahan 20 detik.',                        NULL, 2, 20, 'paha depan',                    'ringan',  ''),
('Cat-cow stretch',  'Merangkak, lengkungkan dan bulatkan punggung bergantian, bernapas teratur.',            NULL, 2, 10, 'punggung, fleksibilitas',       'ringan',  ''),
('Child pose',       'Duduk di atas tumit, condongkan tubuh ke depan, tangan lurus ke depan.',               NULL, 2, 30, 'punggung, bahu, pinggul',       'ringan',  '');


-- ============================================================
-- DATA AWAL: rules (contoh rule IF-THEN)
-- prioritas: 1=sangat spesifik, 10=sangat umum
-- ============================================================
INSERT INTO rules
  (goals, bmi_kategori, umur_kategori, jenis_kelamin, frekuensi, exercise_ids, keterangan, prioritas)
VALUES

-- WEIGHT LOSS - Overweight/Obesitas - Pemula
('weight_loss', 'overweight', 'any', 'any', 'pemula',
 '[1, 11, 16, 13]',
 'Turun BB, overweight, pemula: jalan cepat + squat + plank + glute bridge', 3),

('weight_loss', 'obesitas', 'any', 'any', 'pemula',
 '[2, 13, 17, 22]',
 'Turun BB, obesitas, pemula: jalan santai + glute bridge + plank modif + cat-cow', 3),

-- WEIGHT LOSS - Aktif
('weight_loss', 'overweight', 'any', 'any', 'aktif',
 '[3, 11, 14, 16, 18]',
 'Turun BB, overweight, aktif: jogging + squat + lunge + plank + crunch', 2),

('weight_loss', 'obesitas', 'any', 'any', 'aktif',
 '[1, 13, 15, 17, 20]',
 'Turun BB, obesitas, aktif: jalan cepat + glute bridge + step-up + plank modif + bird dog', 2),

-- MUSCLE GAIN - Pria
('muscle_gain', 'any', 'dewasa_muda', 'pria', 'rutin',
 '[6, 8, 11, 14, 16]',
 'Bentuk otot, pria dewasa muda, rutin: push-up + pull-up + squat + lunge + plank', 2),

('muscle_gain', 'any', 'dewasa_muda', 'pria', 'aktif',
 '[6, 11, 14, 16, 13]',
 'Bentuk otot, pria dewasa muda, aktif: push-up + squat + lunge + plank + glute bridge', 2),

-- MUSCLE GAIN - Wanita
('muscle_gain', 'any', 'any', 'wanita', 'rutin',
 '[7, 13, 11, 16, 18]',
 'Bentuk otot, wanita, rutin: push-up modif + glute bridge + squat + plank + crunch', 2),

('muscle_gain', 'any', 'any', 'wanita', 'aktif',
 '[7, 13, 15, 17, 19]',
 'Bentuk otot, wanita, aktif: push-up modif + glute bridge + step-up + plank modif + dead bug', 2),

-- MUSCLE GAIN - Lansia
('muscle_gain', 'any', 'lansia', 'any', 'any',
 '[13, 15, 17, 20, 21]',
 'Bentuk otot, lansia: glute bridge + step-up + plank modif + bird dog + peregangan paha', 1),

-- STAMINA
('stamina', 'any', 'remaja',      'any', 'any',   '[3, 5, 11, 16, 14]',   'Stamina, remaja: jogging + renang + squat + plank + lunge', 3),
('stamina', 'any', 'dewasa_muda', 'any', 'rutin', '[3, 5, 11, 16, 14]',   'Stamina, dewasa muda, rutin: jogging + renang + squat + plank + lunge', 3),
('stamina', 'any', 'dewasa_muda', 'any', 'aktif', '[1, 4, 11, 16, 13]',   'Stamina, dewasa muda, aktif: jalan cepat + sepeda + squat + plank + glute bridge', 3),
('stamina', 'any', 'dewasa',      'any', 'any',   '[1, 4, 13, 17, 20]',   'Stamina, dewasa: jalan cepat + sepeda + glute bridge + plank modif + bird dog', 3),
('stamina', 'any', 'lansia',      'any', 'any',   '[2, 4, 13, 22, 23]',   'Stamina, lansia: jalan santai + sepeda + glute bridge + cat-cow + child pose', 1),

-- GENERAL HEALTH (fallback umum)
('general', 'underweight', 'any', 'any', 'any', '[7, 13, 17, 20, 21]',   'Sehat umum, underweight: latihan ringan + fleksibilitas', 5),
('general', 'normal',      'any', 'any', 'pemula', '[1, 13, 17, 19, 21]', 'Sehat umum, normal, pemula: jalan + glute bridge + plank modif + dead bug + peregangan', 5),
('general', 'normal',      'any', 'any', 'aktif',  '[1, 11, 16, 18, 20]', 'Sehat umum, normal, aktif: jalan + squat + plank + crunch + bird dog', 4),
('general', 'any',         'lansia', 'any', 'any', '[2, 13, 17, 22, 23]', 'Sehat umum, lansia: latihan sangat ringan + fleksibilitas', 1);


-- ============================================================
-- INDEX untuk performa query
-- ============================================================
CREATE INDEX idx_sessions_user     ON sessions(user_id);
CREATE INDEX idx_rec_session       ON recommendations(session_id);
CREATE INDEX idx_rules_goals       ON rules(goals, bmi_kategori, frekuensi);
CREATE INDEX idx_rules_prioritas   ON rules(prioritas);