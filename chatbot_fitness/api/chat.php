<?php
// api/chat.php  (versi free-text NLP)

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Session.php';
require_once __DIR__ . '/../engine/RuleEngine.php';
require_once __DIR__ . '/../engine/NLPParser.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) { http_response_code(400); echo json_encode(['error'=>'Bad request']); exit; }

$step    = $body['step']    ?? '';
$message = trim($body['message'] ?? '');
$state   = $body['state']   ?? [];

function reply(string $text, array $extra = []) {
    echo json_encode(['reply' => $text, ...$extra]); exit;
}

function checkMissing(array $state): array {
    $required = ['tinggi','berat','umur','gender','disabilitas','frekuensi','goals'];
    return array_values(array_filter($required, fn($k) => empty($state[$k])));
}

function nextQuestion(string $need): array {
    return match($need) {
        'tinggi'      => ['text'=>"Boleh tau tinggi badanmu berapa? (contoh: *170 cm*) 📏"],
        'berat'       => ['text'=>"Berat badan sekarang berapa? (contoh: *65 kg*) ⚖️"],
        'umur'        => ['text'=>"Umurnya berapa tahun? 🎂"],
        'gender'      => ['text'=>"Jenis kelaminnya pria atau wanita? 👤",
                          'suggest'=>['Pria','Wanita']],
        'disabilitas' => ['text'=>"Ada cedera atau kondisi fisik khusus? Cedera lutut, punggung, bahu? Atau tidak ada? 🩺",
                          'suggest'=>['Tidak ada','Cedera Lutut','Cedera Punggung','Cedera Bahu']],
        'frekuensi'   => ['text'=>"Seberapa sering kamu olahraga sekarang? 🗓",
                          'suggest'=>['Belum pernah / jarang','1–2× seminggu','3–5× seminggu']],
        'goals'       => ['text'=>"Tujuan olahragamu apa nih? 🎯",
                          'suggest'=>['Turun Berat Badan','Bentuk Otot','Tingkatkan Stamina','Sehat Umum']],
        default       => ['text'=>"Bisa ceritain lebih lanjut?"],
    };
}

function extractFromMessage(string $msg, string $need, array $ents): mixed {
    return match($need) {
        'tinggi' => $ents['tinggi'] ?? (preg_match('/(\d{2,3})/', $msg, $m) ? (float)$m[1] : null),
        'berat'  => $ents['berat']  ?? (preg_match('/(\d{2,3})/', $msg, $m) ? (float)$m[1] : null),
        'umur'   => $ents['umur']   ?? (preg_match('/(\d{1,3})/', $msg, $m) ? (int)$m[1]   : null),
        'gender' => $ents['gender'] ?? (
            preg_match('/pria|laki|cowok/i',     $msg) ? 'pria'   :
           (preg_match('/wanita|perempuan|cewek/i',$msg) ? 'wanita' : null)
        ),
        'disabilitas' => $ents['disabilitas'] ?? (
            preg_match('/lutut/i',    $msg) ? 'knee'     :
           (preg_match('/punggung/i', $msg) ? 'back'     :
           (preg_match('/bahu/i',     $msg) ? 'shoulder' :
           (preg_match('/tidak|gaada|normal|none|aman/i', $msg) ? 'none' : null)))
        ),
        'frekuensi' => (
            preg_match('/jarang|belum|pemula|pertama|baru|tidak pernah/i', $msg) ? 'pemula' :
           (preg_match('/1|2|satu|dua|kadang|aktif/i',                     $msg) ? 'aktif'  :
           (preg_match('/3|4|5|rutin|sering|tiap|setiap/i',               $msg) ? 'rutin'  : null))
        ),
        'goals' => (function() use($msg) {
            $p = NLPParser::parse($msg);
            $map = ['weight_loss','muscle_gain','stamina','general'];
            return in_array($p['intent'], $map) ? $p['intent'] : (
                preg_match('/turun|diet|kurus|langsing/i',   $msg) ? 'weight_loss' :
               (preg_match('/otot|kekar|sixpack|six pack/i, $msg) ? 'muscle_gain' :
               (preg_match('/stamina|napas|cardio/i',        $msg) ? 'stamina'     :
               (preg_match('/sehat|bugar/i',                 $msg) ? 'general'     : null)))
            );
        })(),
        default => null,
    };
}

function runInference(array $s): array {
    $userId    = User::create([
        'nama'          => $s['nama'] ?? 'Pengguna',
        'tinggi_badan'  => $s['tinggi'],
        'berat_badan'   => $s['berat'],
        'jenis_kelamin' => $s['gender'],
        'umur'          => $s['umur'],
    ]);
    $user      = User::findById($userId);
    $sessionId = Session::create($userId, [
        'disabilitas' => $s['disabilitas'] ?? 'none',
        'goals'       => $s['goals'],
        'frekuensi'   => $s['frekuensi'],
    ]);
    $session   = Session::findWithUser($sessionId);
    $result    = RuleEngine::infer($session);

    if (empty($result)) {
        return ['text'=>"Hmm, belum ada rule yang cocok buat profilmu saat ini. Coba konsultasikan dengan trainer ya! 💪", 'exercises'=>[]];
    }

    RuleEngine::saveRecommendations($sessionId, $result['rule']['id'], $result['exercises']);

    $bmiLabel   = ['underweight'=>'Kurus','normal'=>'Normal ✅','overweight'=>'Gemuk','obesitas'=>'Obesitas'];
    $goalsLabel = ['weight_loss'=>'Turun Berat Badan','muscle_gain'=>'Bentuk Otot','stamina'=>'Tingkatkan Stamina','general'=>'Sehat Umum'];

    return [
        'text' =>
            "✅ Analisis selesai! Ini hasil untukmu:\n\n" .
            "📊 BMI **{$user['bmi']}** — {$bmiLabel[$user['bmi_kategori']]}\n" .
            "🎯 Tujuan: **{$goalsLabel[$s['goals']]}**\n\n" .
            "Berikut program latihan yang aku rekomendasikan 👇",
        'exercises' => $result['exercises'],
        'bmi'       => $user['bmi'],
        'bmi_kat'   => $user['bmi_kategori'],
    ];
}

// ══════════════════════════════════════════
// STEP: free_chat
// ══════════════════════════════════════════
if ($step === 'free_chat') {
    if ($message === '') reply('Silakan ketik sesuatu dulu ya! 😊');

    $parsed = NLPParser::parse($message);
    $intent = $parsed['intent'];
    $ents   = $parsed['entities'];

    // Out of scope
    if (!NLPParser::isFitnessRelated($intent)) {
        reply(
            "Waduh, itu kayaknya di luar bidang aku nih 😅\n\n" .
            "Aku spesialis **fitness & olahraga**. Aku bisa bantu soal:\n" .
            "- 💪 Rekomendasi program latihan\n" .
            "- 📊 Hitung & analisis BMI\n" .
            "- 🏋️ Latihan yang aman sesuai kondisi fisikmu\n\n" .
            "Ada yang mau ditanyain soal itu?",
            ['intent'=>'out_of_scope']
        );
    }

    // Greeting
    if ($intent === 'greeting') {
        reply(
            "Halo! 👋 Aku **FitBot**, asisten fitness berbasis sistem cerdas.\n\n" .
            "Aku bisa bantu kamu cari program latihan yang cocok berdasarkan kondisi tubuh dan tujuanmu.\n\n" .
            "Ceritain aja tujuan olahragamu, atau tanyain apapun soal fitness!",
            ['intent'=>'greeting']
        );
    }

    // Hitung BMI langsung kalau ada data
    if ($intent === 'ask_bmi' && !empty($ents['tinggi']) && !empty($ents['berat'])) {
        $bmi   = User::hitungBMI($ents['tinggi'], $ents['berat']);
        $kat   = User::kategoriBMI($bmi);
        $label = ['underweight'=>'Kurus 😟','normal'=>'Normal ✅','overweight'=>'Gemuk ⚠️','obesitas'=>'Obesitas 🔴'];
        $saran = [
            'underweight' => "Fokus ke latihan kekuatan ringan dan perbaiki pola makan.",
            'normal'      => "Kamu di kategori ideal! Tetap jaga dengan olahraga rutin.",
            'overweight'  => "Disarankan mulai program kardio + kekuatan untuk membakar lemak.",
            'obesitas'    => "Mulai dari intensitas rendah dulu, dan konsultasikan juga dengan dokter.",
        ];
        reply(
            "📊 **Hasil BMI:**\n\nTinggi: {$ents['tinggi']} cm | Berat: {$ents['berat']} kg\n" .
            "BMI kamu: **{$bmi}** → {$label[$kat]}\n\n" .
            $saran[$kat] . "\n\nMau aku rekomendasikan program latihan yang cocok?",
            ['intent'=>'ask_bmi','bmi'=>$bmi,'bmi_kategori'=>$kat]
        );
    }

    // Kalau intent mengarah ke rekomendasi, kumpulkan data
    $goalsFromIntent = match($intent) {
        'weight_loss','muscle_gain','stamina','general' => $intent,
        default => null
    };

    $currentState = $state;
    if ($goalsFromIntent) $currentState['goals'] = $goalsFromIntent;
    foreach (['tinggi','berat','umur','gender','disabilitas'] as $k) {
        if (!empty($ents[$k]) && empty($currentState[$k])) $currentState[$k] = $ents[$k];
    }

    $missing = checkMissing($currentState);
    if (!empty($missing)) {
        $q = nextQuestion($missing[0]);
        reply($q['text'], ['state'=>$currentState,'need'=>$missing[0],'suggest'=>$q['suggest']??[]]);
    }

    $r = runInference($currentState);
    reply($r['text'], ['intent'=>'recommendation','state'=>$currentState,'exercises'=>$r['exercises'],'bmi'=>$r['bmi']??null,'bmi_kat'=>$r['bmi_kat']??null]);
}

// ══════════════════════════════════════════
// STEP: continue_chat
// ══════════════════════════════════════════
if ($step === 'continue_chat') {
    $currentState = $state;
    $need         = $body['need'] ?? '';

    $parsed = NLPParser::parse($message);
    $ents   = $parsed['entities'];

    $val = extractFromMessage($message, $need, $ents);
    if ($val !== null) $currentState[$need] = $val;

    $missing = checkMissing($currentState);
    if (!empty($missing)) {
        $q = nextQuestion($missing[0]);
        reply($q['text'], ['state'=>$currentState,'need'=>$missing[0],'suggest'=>$q['suggest']??[]]);
    }

    $r = runInference($currentState);
    reply($r['text'], ['intent'=>'recommendation','state'=>$currentState,'exercises'=>$r['exercises'],'bmi'=>$r['bmi']??null,'bmi_kat'=>$r['bmi_kat']??null]);
}

http_response_code(400);
echo json_encode(['error'=>"Step '$step' tidak dikenali"]);