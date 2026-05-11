<?php
// models/User.php

require_once __DIR__ . '/../config/database.php';

class User {

    // Simpan user baru, return user_id
    public static function create(array $data): int {
        $pdo = getDB();
        $sql = "INSERT INTO users (nama, tinggi_badan, berat_badan, jenis_kelamin, umur)
                VALUES (:nama, :tinggi, :berat, :gender, :umur)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nama'   => trim($data['nama']),
            ':tinggi' => (float) $data['tinggi_badan'],
            ':berat'  => (float) $data['berat_badan'],
            ':gender' => $data['jenis_kelamin'],
            ':umur'   => (int) $data['umur'],
        ]);
        return (int) $pdo->lastInsertId();
    }

    // Ambil user by ID beserta BMI & kategori (generated column)
    public static function findById(int $id): ?array {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row  = $stmt->fetch();
        return $row ?: null;
    }

    // Hitung BMI manual (untuk preview sebelum simpan)
    public static function hitungBMI(float $tinggi_cm, float $berat_kg): float {
        $tinggi_m = $tinggi_cm / 100;
        return round($berat_kg / ($tinggi_m * $tinggi_m), 2);
    }

    // Kategorikan BMI
    public static function kategoriBMI(float $bmi): string {
        if ($bmi < 18.5) return 'underweight';
        if ($bmi < 25.0) return 'normal';
        if ($bmi < 30.0) return 'overweight';
        return 'obesitas';
    }
}
