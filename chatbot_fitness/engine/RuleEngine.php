<?php
// engine/RuleEngine.php
// =====================================================
// INI ADALAH INTI DARI KNOWLEDGE-BASED SYSTEM
// Tugasnya: cocokkan profil user → rule → latihan
// =====================================================

require_once __DIR__ . '/../config/database.php';

class RuleEngine {

    // -----------------------------------------------
    // FUNGSI UTAMA: jalankan inference engine
    // Input : data sesi + data user (dari JOIN)
    // Output: array latihan yang direkomendasikan
    // -----------------------------------------------
    public static function infer(array $session): array {
        $goals         = $session['goals'];
        $bmi_kat       = $session['bmi_kategori'];
        $umur_kat      = $session['umur_kategori'];
        $gender        = $session['jenis_kelamin'];
        $frekuensi     = $session['frekuensi'];
        $disabilitas   = $session['disabilitas'];

        // Step 1: Ambil semua rule yang cocok (pattern matching)
        $matched_rules = self::matchRules($goals, $bmi_kat, $umur_kat, $gender, $frekuensi);

        if (empty($matched_rules)) {
            return [];
        }

        // Step 2: Ambil rule dengan prioritas tertinggi (angka terkecil)
        $best_rule = $matched_rules[0];

        // Step 3: Ambil latihan dari rule terpilih
        $exercise_ids = json_decode($best_rule['exercise_ids'], true);

        // Step 4: Filter latihan berdasarkan disabilitas user
        $exercises = self::getFilteredExercises($exercise_ids, $disabilitas);

        return [
            'rule'      => $best_rule,
            'exercises' => $exercises,
        ];
    }

    // -----------------------------------------------
    // PATTERN MATCHING: cocokkan rule ke profil user
    // Urutan prioritas: spesifik dulu (angka kecil)
    // -----------------------------------------------
    private static function matchRules(
        string $goals,
        string $bmi_kat,
        string $umur_kat,
        string $gender,
        string $frekuensi
    ): array {
        $pdo  = getDB();

        // Query: ambil semua rule yang cocok
        // 'any' artinya rule berlaku untuk semua nilai variabel itu
        $sql = "SELECT * FROM rules
                WHERE goals = :goals
                  AND (bmi_kategori  = :bmi  OR bmi_kategori  = 'any')
                  AND (umur_kategori = :umur OR umur_kategori = 'any')
                  AND (jenis_kelamin = :gender OR jenis_kelamin = 'any')
                  AND (frekuensi     = :frek OR frekuensi     = 'any')
                ORDER BY prioritas ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':goals'  => $goals,
            ':bmi'    => $bmi_kat,
            ':umur'   => $umur_kat,
            ':gender' => $gender,
            ':frek'   => $frekuensi,
        ]);

        return $stmt->fetchAll();
    }

    // -----------------------------------------------
    // FILTER LATIHAN berdasarkan disabilitas
    // Latihan yang dilarang untuk disabilitas user
    // akan diganti dengan alternatif yang aman
    // -----------------------------------------------
    private static function getFilteredExercises(array $ids, string $disabilitas): array {
        if (empty($ids)) return [];

        $pdo         = getDB();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $stmt = $pdo->prepare(
            "SELECT * FROM exercises WHERE id IN ($placeholders) ORDER BY FIELD(id, $placeholders)"
        );
        // bind dua kali: untuk IN dan untuk FIELD (urutan tetap)
        $stmt->execute(array_merge($ids, $ids));
        $exercises = $stmt->fetchAll();

        // Filter: buang latihan yang dilarang, ganti dengan alternatif
        $result = [];
        foreach ($exercises as $ex) {
            $larangan = array_filter(explode(',', $ex['dilarang_untuk']));
            if ($disabilitas !== 'none' && in_array($disabilitas, $larangan)) {
                // Cari alternatif: latihan intensitas ringan yang aman
                $alt = self::findAlternative($ex['target_otot'], $disabilitas, $ids);
                if ($alt) {
                    $alt['catatan'] = "⚠️ Diganti karena cedera {$disabilitas}: latihan alternatif yang lebih aman.";
                    $result[] = $alt;
                }
                // Jika tidak ada alternatif, latihan ini dilewati
            } else {
                $result[] = $ex;
            }
        }

        return $result;
    }

    // -----------------------------------------------
    // CARI LATIHAN ALTERNATIF yang aman
    // -----------------------------------------------
    private static function findAlternative(string $target_otot, string $disabilitas, array $exclude_ids): ?array {
        $pdo = getDB();

        // Pisahkan target otot utama (ambil kata pertama)
        $otot_utama = explode(',', $target_otot)[0];

        $placeholders = implode(',', array_fill(0, count($exclude_ids), '?'));

        // Cari latihan ringan/sedang yang menarget otot sama dan tidak dilarang
        $stmt = $pdo->prepare(
            "SELECT * FROM exercises
             WHERE target_otot LIKE ?
               AND intensitas IN ('ringan', 'sedang')
               AND (dilarang_untuk = '' OR dilarang_untuk NOT LIKE ?)
               AND id NOT IN ($placeholders)
             LIMIT 1"
        );

        $params = [
            "%{$otot_utama}%",
            "%{$disabilitas}%",
            ...$exclude_ids
        ];
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    // -----------------------------------------------
    // SIMPAN REKOMENDASI ke tabel recommendations
    // -----------------------------------------------
    public static function saveRecommendations(int $session_id, int $rule_id, array $exercises): void {
        $pdo  = getDB();
        $stmt = $pdo->prepare(
            "INSERT INTO recommendations (session_id, rule_id, exercise_id, catatan)
             VALUES (:sid, :rid, :eid, :cat)"
        );
        foreach ($exercises as $ex) {
            $stmt->execute([
                ':sid' => $session_id,
                ':rid' => $rule_id,
                ':eid' => $ex['id'],
                ':cat' => $ex['catatan'] ?? null,
            ]);
        }
    }
}
