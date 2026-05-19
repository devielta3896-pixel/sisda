/**
 * ============================================================
 * BACKEND SERVER - Chatbot Fitness Berbasis Rule-Based
 * Mata Kuliah: Sistem Cerdas
 * ============================================================
 * 
 * ALUR SISTEM:
 * User Input → Rule Matching → Knowledge Base → Response
 * 
 * 1. User mengirim pesan
 * 2. Server menerima dan memproses input
 * 3. Rule engine mencocokkan keyword
 * 4. Data diambil dari knowledge base (JSON)
 * 5. Response dikirim ke frontend
 */

const express = require('express');
const cors = require('cors');
const fs = require('fs');
const path = require('path');

const app = express();
const PORT = 3001;

// Middleware: parsing JSON dan CORS
app.use(express.json());
app.use(cors());
app.use(express.static(path.join(__dirname, '../frontend')));

// ============================================================
// LOAD KNOWLEDGE BASE
// Membaca file JSON sebagai basis pengetahuan chatbot
// ============================================================
const knowledgeBasePath = path.join(__dirname, '../knowledge-base/fitness_knowledge.json');
let knowledgeBase = [];

try {
  const raw = fs.readFileSync(knowledgeBasePath, 'utf-8');
  const data = JSON.parse(raw);
  knowledgeBase = data.pengetahuan;
  console.log(`✅ Knowledge base berhasil dimuat: ${knowledgeBase.length} kategori`);
} catch (err) {
  console.error('❌ Gagal memuat knowledge base:', err.message);
}

// ============================================================
// RULE-BASED ENGINE
// Fungsi utama untuk memproses input user
// ============================================================

/**
 * Normalisasi teks: ubah ke lowercase dan hapus karakter khusus
 * @param {string} text - Input user mentah
 * @returns {string} - Teks yang sudah dinormalisasi
 */
function normalizeInput(text) {
  return text
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '') // hapus aksen
    .replace(/[^a-z0-9\s]/g, ' ')   // hapus karakter khusus
    .trim();
}

/**
 * Rule Matching Engine
 * Mencocokkan keyword dari input user dengan aturan yang ada
 * 
 * RULE: IF input mengandung keyword tertentu THEN ambil dari knowledge base
 * 
 * @param {string} input - Input user yang sudah dinormalisasi
 * @returns {Object|null} - Kategori yang cocok atau null jika tidak ditemukan
 */
function findMatchingCategory(input) {
  let bestMatch = null;
  let highestScore = 0;

  for (const kategori of knowledgeBase) {
    let score = 0;

    // Cek setiap keyword dalam kategori
    for (const keyword of kategori.keywords) {
      if (input.includes(keyword)) {
        // Skor lebih tinggi untuk keyword yang lebih panjang (lebih spesifik)
        score += keyword.length;
      }
    }

    // Simpan kategori dengan skor tertinggi
    if (score > highestScore) {
      highestScore = score;
      bestMatch = kategori;
    }
  }

  return highestScore > 0 ? bestMatch : null;
}

/**
 * Format Response dari Knowledge Base
 * Mengubah data JSON menjadi teks yang mudah dibaca
 * 
 * @param {Object} kategori - Data kategori dari knowledge base
 * @returns {Object} - Response terformat dengan data terstruktur
 */
function formatResponse(kategori) {
  // Kategori dengan data latihan (dada, punggung, kaki, dll)
  if (kategori.latihan && Array.isArray(kategori.latihan)) {
    return {
      type: 'latihan',
      kategori: kategori.kategori,
      latihan: kategori.latihan,
      message: `Berikut adalah rekomendasi latihan untuk kategori **${kategori.kategori.toUpperCase()}**:`
    };
  }

  // Kategori bulking / cutting (panduan + latihan rekomendasi)
  if (kategori.panduan && kategori.penjelasan) {
    return {
      type: 'panduan',
      kategori: kategori.kategori,
      penjelasan: kategori.penjelasan,
      panduan: kategori.panduan,
      latihan_rekomendasi: kategori.latihan_rekomendasi || [],
      message: `Berikut panduan lengkap untuk **${kategori.kategori.toUpperCase()}**:`
    };
  }

  // Kategori jadwal
  if (kategori.program) {
    return {
      type: 'jadwal',
      kategori: kategori.kategori,
      penjelasan: kategori.penjelasan,
      program: kategori.program,
      message: `Berikut adalah **jadwal dan program latihan** yang direkomendasikan:`
    };
  }

  // Kategori set & repetisi
  if (kategori.kategori === 'set_rep') {
    return {
      type: 'set_rep',
      kategori: kategori.kategori,
      penjelasan: kategori.penjelasan,
      panduan: kategori.panduan,
      message: `Berikut **panduan jumlah set & repetisi** sesuai tujuanmu:`
    };
  }

  // Kategori pemula
  if (kategori.tips) {
    return {
      type: 'pemula',
      kategori: kategori.kategori,
      penjelasan: kategori.penjelasan,
      tips: kategori.tips,
      latihan_awal: kategori.latihan_awal || [],
      message: `Berikut **panduan untuk pemula** yang baru mulai gym:`
    };
  }

  // Default fallback
  return {
    type: 'info',
    kategori: kategori.kategori,
    message: 'Saya menemukan informasi relevan, namun formatnya belum tersedia.'
  };
}

// ============================================================
// SALAM & GREETING RULES
// ============================================================
const greetingKeywords = ['halo', 'hai', 'hello', 'hi', 'selamat', 'hei', 'hy', 'hellow'];
const greetingResponses = [
  "Halo! Selamat datang di FitBot 💪 Saya siap membantu kamu belajar tentang fitness dan gym. Tanyakan apa saja tentang latihan, program, atau nutrisi!",
  "Hai! Saya FitBot, asisten fitness kamu. Apa yang ingin kamu pelajari hari ini? Coba tanyakan tentang latihan dada, kaki, punggung, atau program bulking/cutting!",
  "Hello! Senang bertemu denganmu 🏋️ Saya bisa membantu dengan informasi latihan gym, jadwal workout, dan tips fitness. Silakan bertanya!"
];

// ============================================================
// TERIMA KASIH RULES
// ============================================================
const thankKeywords = ['terima kasih', 'makasih', 'thanks', 'thank', 'thx'];

// ============================================================
// API ENDPOINT - PROSES CHAT
// ============================================================

/**
 * POST /api/chat
 * Endpoint utama untuk menerima pesan dari user dan mengembalikan respons
 * 
 * Body: { message: "string" }
 * Response: { success: boolean, response: Object }
 */
app.post('/api/chat', (req, res) => {
  const { message } = req.body;

  // Validasi input kosong
  if (!message || message.trim() === '') {
    return res.json({
      success: false,
      response: {
        type: 'error',
        message: 'Pesan tidak boleh kosong!'
      }
    });
  }

  // Normalisasi input user
  const normalizedInput = normalizeInput(message);
  console.log(`📩 Input: "${message}" → Normalized: "${normalizedInput}"`);

  // RULE 1: Cek apakah ini salam/greeting
  const isGreeting = greetingKeywords.some(kw => normalizedInput.includes(kw));
  if (isGreeting) {
    const randomResponse = greetingResponses[Math.floor(Math.random() * greetingResponses.length)];
    return res.json({
      success: true,
      response: {
        type: 'greeting',
        message: randomResponse
      }
    });
  }

  // RULE 2: Cek terima kasih
  const isThanks = thankKeywords.some(kw => normalizedInput.includes(kw));
  if (isThanks) {
    return res.json({
      success: true,
      response: {
        type: 'thanks',
        message: 'Sama-sama! Semangat latihan ya! 💪 Kalau ada pertanyaan lain seputar fitness, jangan ragu untuk bertanya.'
      }
    });
  }

  // RULE 3: Rule-Based Matching dengan Knowledge Base
  // IF input mengandung keyword → THEN ambil dari knowledge base
  const matchedCategory = findMatchingCategory(normalizedInput);

  if (matchedCategory) {
    const formattedResponse = formatResponse(matchedCategory);
    console.log(`✅ Match ditemukan: kategori "${matchedCategory.kategori}"`);
    return res.json({
      success: true,
      response: formattedResponse
    });
  }

  // RULE 4: Tidak ada yang cocok - topik di luar fitness
  // Deteksi apakah pertanyaan mungkin berhubungan fitness tapi tidak dikenali
  const fitnessHints = ['gym', 'latih', 'olahraga', 'workout', 'fitness', 'otot', 'tubuh', 'sehat'];
  const mightBeFitness = fitnessHints.some(hint => normalizedInput.includes(hint));

  if (mightBeFitness) {
    // Kemungkinan tentang fitness tapi topik tidak ada di knowledge base
    return res.json({
      success: true,
      response: {
        type: 'unknown_fitness',
        message: `Maaf, saya belum memiliki pengetahuan tentang topik tersebut. 🤔\n\nCoba tanyakan tentang:\n• Latihan dada, punggung, kaki, bahu, atau lengan\n• Program bulking atau cutting\n• Jadwal gym untuk pemula\n• Tips pemanasan\n• Panduan set & repetisi`
      }
    });
  }

  // Di luar topik fitness sama sekali
  return res.json({
    success: true,
    response: {
      type: 'out_of_topic',
      message: `Maaf, pertanyaanmu berada di luar topik chatbot fitness ini. 🏋️\n\nSilakan tanyakan seputar:\n• Latihan gym (dada, punggung, kaki, bahu, lengan)\n• Program workout (bulking, cutting)\n• Jadwal latihan untuk pemula\n• Pemanasan dan stretching\n• Panduan repetisi dan set`
    }
  });
});

// ============================================================
// ENDPOINT TAMBAHAN
// ============================================================

// GET /api/categories - Ambil semua kategori yang tersedia
app.get('/api/categories', (req, res) => {
  const categories = knowledgeBase.map(k => ({
    id: k.id,
    kategori: k.kategori,
    keywords: k.keywords
  }));
  res.json({ success: true, data: categories });
});

// GET /api/health - Health check server
app.get('/api/health', (req, res) => {
  res.json({
    status: 'OK',
    message: 'Fitness Chatbot Server berjalan normal',
    knowledge_base_loaded: knowledgeBase.length,
    timestamp: new Date().toISOString()
  });
});

// ============================================================
// JALANKAN SERVER
// ============================================================
app.listen(PORT, () => {
  console.log('============================================================');
  console.log('  🏋️  CHATBOT FITNESS - SISTEM CERDAS SERVER');
  console.log('============================================================');
  console.log(`  ✅ Server berjalan di: http://localhost:${PORT}`);
  console.log(`  📚 Knowledge base: ${knowledgeBase.length} kategori dimuat`);
  console.log(`  🔗 API endpoint: http://localhost:${PORT}/api/chat`);
  console.log('============================================================');
});
