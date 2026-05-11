<?php
// api/chat.php
// Endpoint AJAX yang dipanggil oleh frontend chatbot
// Method: POST | Content-Type: application/json

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Session.php';
require_once __DIR__ . '/../engine/RuleEngine.php';

// Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Ambil body JSON
$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    http_response_code(400);
    echo json_encode(['error' => 'Request body tidak valid']);
    exit;
}

$step = $body['step'] ?? '';

// ============================================================
// STEP 1: Simpan data user → return user_id + preview BMI
// ============================================================
if ($step === 'save_user') {
    $required = ['nama', 'tinggi_badan', 'berat_badan', 'jenis_kelamin', 'umur'];
    foreach ($required as $field) {
        if (empty($body[$field])) {
            echo json_encode(['error' => "Field '$field' wajib diisi"]);
            exit;
        }
    }

    // Validasi range
    $tinggi = (float) $body['tinggi_badan'];
    $berat  = (float) $body['berat_badan'];
    $umur   = (int)   $body['umur'];

    if ($tinggi < 100 || $tinggi > 250) {
        echo json_encode(['error' => 'Tinggi badan tidak valid (100-250 cm)']); exit;
    }
    if ($berat < 20 || $berat > 300) {
        echo json_encode(['error' => 'Berat badan tidak valid (20-300 kg)']); exit;
    }
    if ($umur < 10 || $umur > 100) {
        echo json_encode(['error' => 'Umur tidak valid (10-100 tahun)']); exit;
    }
    if (!in_array($body['jenis_kelamin'], ['pria', 'wanita'])) {
        echo json_encode(['error' => 'Jenis kelamin tidak valid']); exit;
    }

    $user_id = User::create($body);
    $user    = User::findById($user_id);

    $bmi_label = [
        'underweight' => 'Kurus',
        'normal'      => 'Normal',
        'overweight'  => 'Gemuk',
        'obesitas'    => 'Obesitas',
    ];

    echo json_encode([
        'success' => true,
        'user_id' => $user_id,
        'preview' => [
            'bmi'          => $user['bmi'],
            'bmi_kategori' => $user['bmi_kategori'],
            'bmi_label'    => $bmi_label[$user['bmi_kategori']],
            'umur_kategori'=> $user['umur_kategori'],
        ],
    ]);
    exit;
}

// ============================================================
// STEP 2: Jalankan inference engine → return rekomendasi
// ============================================================
if ($step === 'get_recommendation') {
    $required = ['user_id', 'disabilitas', 'goals', 'frekuensi'];
    foreach ($required as $field) {
        if (!isset($body[$field])) {
            echo json_encode(['error' => "Field '$field' wajib diisi"]);
            exit;
        }
    }

    $valid_dis   = ['none', 'knee', 'back', 'shoulder'];
    $valid_goals = ['weight_loss', 'muscle_gain', 'stamina', 'general'];
    $valid_frek  = ['pemula', 'aktif', 'rutin'];

    if (!in_array($body['disabilitas'], $valid_dis))   { echo json_encode(['error' => 'Disabilitas tidak valid']); exit; }
    if (!in_array($body['goals'],       $valid_goals)) { echo json_encode(['error' => 'Goals tidak valid']); exit; }
    if (!in_array($body['frekuensi'],   $valid_frek))  { echo json_encode(['error' => 'Frekuensi tidak valid']); exit; }

    // Buat sesi
    $session_id = Session::create((int) $body['user_id'], $body);

    // Ambil data sesi + user untuk inference
    $session = Session::findWithUser($session_id);

    // Jalankan rule engine
    $result = RuleEngine::infer($session);

    if (empty($result)) {
        echo json_encode([
            'success'    => false,
            'message'    => 'Tidak ada rule yang cocok dengan profil kamu. Coba konsultasikan dengan trainer.',
        ]);
        exit;
    }

    // Simpan rekomendasi ke database
    RuleEngine::saveRecommendations($session_id, $result['rule']['id'], $result['exercises']);

    // Label display
    $goals_label = [
        'weight_loss'  => 'Turun Berat Badan',
        'muscle_gain'  => 'Bentuk Otot',
        'stamina'      => 'Tingkatkan Stamina',
        'general'      => 'Sehat Umum',
    ];
    $dis_label = [
        'none'     => 'Tidak ada',
        'knee'     => 'Cedera Lutut',
        'back'     => 'Cedera Punggung',
        'shoulder' => 'Cedera Bahu',
    ];

    echo json_encode([
        'success'     => true,
        'session_id'  => $session_id,
        'profil'      => [
            'nama'         => $session['nama'],
            'bmi'          => $session['bmi'],
            'bmi_kategori' => $session['bmi_kategori'],
            'goals'        => $goals_label[$body['goals']],
            'disabilitas'  => $dis_label[$body['disabilitas']],
            'frekuensi'    => ucfirst($body['frekuensi']),
        ],
        'rule_matched' => $result['rule']['keterangan'],
        'exercises'    => $result['exercises'],
        'total'        => count($result['exercises']),
    ]);
    exit;
}

// Jika step tidak dikenali
http_response_code(400);
echo json_encode(['error' => "Step '$step' tidak dikenali"]);
