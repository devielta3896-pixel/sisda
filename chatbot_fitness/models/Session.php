<?php
// models/Session.php

require_once __DIR__ . '/../config/database.php';

class Session {

    // Buat sesi baru, return session_id
    public static function create(int $user_id, array $data): int {
        $pdo  = getDB();
        $stmt = $pdo->prepare(
            "INSERT INTO sessions (user_id, disabilitas, goals, frekuensi)
             VALUES (:uid, :dis, :goals, :frek)"
        );
        $stmt->execute([
            ':uid'   => $user_id,
            ':dis'   => $data['disabilitas'],
            ':goals' => $data['goals'],
            ':frek'  => $data['frekuensi'],
        ]);
        return (int) $pdo->lastInsertId();
    }

    // Ambil sesi beserta data user (JOIN)
    public static function findWithUser(int $session_id): ?array {
        $pdo  = getDB();
        $stmt = $pdo->prepare(
            "SELECT s.*, u.nama, u.bmi, u.bmi_kategori, u.umur_kategori, u.jenis_kelamin
             FROM sessions s
             JOIN users u ON u.id = s.user_id
             WHERE s.id = :sid"
        );
        $stmt->execute([':sid' => $session_id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
