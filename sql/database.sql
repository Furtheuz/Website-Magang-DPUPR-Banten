CREATE DATABASE IF NOT EXISTS sistem_magang;
USE sistem_magang;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100),
    email VARCHAR(100),
    password VARCHAR(255),
    role ENUM('admin', 'pembimbing', 'user')
);

CREATE TABLE institusi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100),
    alamat TEXT
);

CREATE TABLE peserta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100),
    institusi_id INT,
    user_id INT,
    status ENUM('aktif','selesai','verifikasi') DEFAULT 'aktif',
    FOREIGN KEY (institusi_id) REFERENCES institusi(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE jadwal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    peserta_id INT,
    tanggal DATE,
    pembimbing_id INT,
    tugas TEXT,
    FOREIGN KEY (peserta_id) REFERENCES peserta(id),
    FOREIGN KEY (pembimbing_id) REFERENCES users(id)
);

CREATE TABLE laporan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    peserta_id INT,
    tanggal DATE,
    kegiatan TEXT,
    validasi ENUM('belum','valid') DEFAULT 'belum',
    FOREIGN KEY (peserta_id) REFERENCES peserta(id)
);

CREATE TABLE arsip (
    id INT AUTO_INCREMENT PRIMARY KEY,
    peserta_id INT,
    keterangan TEXT,
    tanggal_arsip DATE,
    FOREIGN KEY (peserta_id) REFERENCES peserta(id)
);