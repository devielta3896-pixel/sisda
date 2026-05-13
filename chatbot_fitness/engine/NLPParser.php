<?php
// engine/NLPParser.php
// ================================================================
// Natural Language Parser (Rule-Based NLP)
// Tugasnya: baca teks bebas user → ekstrak intent + entitas
// ================================================================

class NLPParser {

    // ── Keyword dictionary ────────────────────────────────────
    private static array $dict = [

        // INTENT: greeting
        'greeting' => [
            'halo','hai','hi','hey','selamat','hei','alo','helo',
            'assalamu','salam','pagi','siang','sore','malam',
        ],

        // INTENT: goals
        'weight_loss' => [
            'turun berat','kurus','langsing','diet','gemuk','obesitas',
            'bakar lemak','bakar kalori','nurunin bb','nurunin berat',
            'ngurusin badan','badan gemuk','overweight','kegemukan',
        ],
        'muscle_gain' => [
            'otot','berotot','six pack','six-pack','massa otot','kekar',
            'badan ideal','bentuk badan','sixpack','naikin berat','bulking',
            'badan bagus','perut kotak','dada bidang',
        ],
        'stamina' => [
            'stamina','napas','kardio','cardio','daya tahan','nafas',
            'gampang capek','cepet capek','capek terus','ngos-ngosan',
            'lari','jogging','endurance',
        ],
        'general' => [
            'sehat','bugar','kebugaran','aktif','olahraga','gerak',
            'gaya hidup sehat','hidup sehat','badan sehat',
        ],

        // INTENT: ask_exercise
        'ask_exercise' => [
            'latihan apa','olahraga apa','gerakan apa','workout apa',
            'rekomendasikan','rekomendasi','saran','sarankan','kasih tau',
            'cocok','bagusnya','enaknya','sebaiknya','exercise','gym',
            'push up','squat','plank','lunge','pull up','sit up',
            'latihan untuk','olahraga untuk','gerak untuk',
        ],

        // INTENT: ask_bmi
        'ask_bmi' => [
            'bmi','body mass index','indeks massa','berat badan','berat ideal',
            'tinggi badan','berapa berat','berapa tinggi','hitung bmi',
            'berapa bmi','cek bmi',
        ],

        // INTENT: disabilitas
        'disabilitas' => [
            'cedera','sakit','lutut','punggung','bahu','luka','nyeri',
            'pegal','cidera','injured','injury','sakit lutut','sakit bahu',
            'sakit punggung','pantangan','tidak bisa','gabisa',
        ],

        // INTENT: frekuensi
        'frekuensi' => [
            'berapa kali','seminggu','per minggu','frekuensi','rutin',
            'setiap hari','tiap hari','jarang','belum pernah','pemula',
            'baru mulai','mulai olahraga','pertama kali','aktif',
        ],

        // INTENT: out_of_scope
        'out_of_scope' => [
            'politik','berita','cuaca','masak','resep','coding','program',
            'matematika','sejarah','film','musik','lagu','download',
            'game','belanja','harga','jual','beli',
        ],
    ];

    // ── Ekstrak intent dari teks bebas ─────────────────────────
    public static function parse(string $text): array {
        $lower  = mb_strtolower($text);
        $scores = [];

        foreach (self::$dict as $intent => $keywords) {
            $score = 0;
            foreach ($keywords as $kw) {
                if (str_contains($lower, $kw)) {
                    // Kata lebih panjang = lebih spesifik = skor lebih tinggi
                    $score += strlen($kw);
                }
            }
            if ($score > 0) $scores[$intent] = $score;
        }

        if (empty($scores)) {
            return ['intent' => 'unknown', 'confidence' => 0, 'raw' => $text];
        }

        arsort($scores);
        $topIntent = array_key_first($scores);

        // Ekstrak entitas numerik (tinggi/berat/umur)
        $entities = self::extractEntities($lower);

        return [
            'intent'     => $topIntent,
            'confidence' => $scores[$topIntent],
            'scores'     => $scores,
            'entities'   => $entities,
            'raw'        => $text,
        ];
    }

    // ── Ekstrak angka dari teks ────────────────────────────────
    private static function extractEntities(string $text): array {
        $entities = [];

        // Tinggi badan: angka diikuti cm / sentimeter
        if (preg_match('/(\d{2,3})\s*(?:cm|sentimeter|centimeter)/i', $text, $m)) {
            $entities['tinggi'] = (float) $m[1];
        }

        // Berat badan: angka diikuti kg / kilo
        if (preg_match('/(\d{2,3})\s*(?:kg|kilo|kilogram)/i', $text, $m)) {
            $entities['berat'] = (float) $m[1];
        }

        // Umur: angka diikuti tahun / thn / th
        if (preg_match('/(\d{1,3})\s*(?:tahun|thn|th\b)/i', $text, $m)) {
            $entities['umur'] = (int) $m[1];
        }

        // Jenis kelamin
        if (preg_match('/\b(pria|laki|cowok|male)\b/i', $text)) {
            $entities['gender'] = 'pria';
        } elseif (preg_match('/\b(wanita|perempuan|cewek|female)\b/i', $text)) {
            $entities['gender'] = 'wanita';
        }

        // Disabilitas spesifik
        if (str_contains($text, 'lutut'))    $entities['disabilitas'] = 'knee';
        if (str_contains($text, 'punggung')) $entities['disabilitas'] = 'back';
        if (str_contains($text, 'bahu'))     $entities['disabilitas'] = 'shoulder';

        return $entities;
    }

    // ── Apakah intent ini relevan dengan fitness? ──────────────
    public static function isFitnessRelated(string $intent): bool {
        return !in_array($intent, ['out_of_scope', 'unknown']);
    }
}