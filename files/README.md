# 🏋️ FitBot — Chatbot Fitness Berbasis Rule-Based

> **Mata Kuliah:** Sistem Cerdas  
> **Judul Project:** Implementasi Chatbot Fitness Berbasis Rule-Based dengan Pendekatan Knowledge-Based System

---

## 📁 Struktur Folder

```
fitness-chatbot/
├── frontend/
│   ├── index.html      → Halaman utama UI (tampilan ChatGPT)
│   ├── style.css       → Styling dark modern
│   └── app.js          → Logic frontend (kirim pesan, render bubble)
│
├── backend/
│   ├── server.js       → Server Express + Rule Engine
│   └── package.json    → Dependensi Node.js
│
├── knowledge-base/
│   └── fitness_knowledge.json  → Basis pengetahuan (30+ latihan)
│
└── README.md           → Dokumentasi ini
```

---

## 🚀 Cara Menjalankan Project

### 1. Install Dependensi Backend

```bash
cd backend
npm install
```

### 2. Jalankan Server Backend

```bash
node server.js
```

Server akan berjalan di: `http://localhost:3001`

### 3. Buka Frontend

Buka file `frontend/index.html` langsung di browser.  
Atau gunakan Live Server di VS Code (klik kanan → Open with Live Server).

---

## 🧠 Alur Sistem (Diagram)

```
User Input
    ↓
Normalisasi Teks (lowercase, hapus karakter khusus)
    ↓
Rule Matching Engine
IF input mengandung keyword → THEN cocokkan kategori
    ↓
Knowledge Base (JSON)
Ambil data latihan / panduan sesuai kategori
    ↓
Format Response
    ↓
Tampilkan ke User (bubble chat)
```

---

## 📚 Kategori Knowledge Base

| Kategori     | Contoh Keyword              | Tipe Respons     |
|--------------|-----------------------------|------------------|
| dada         | dada, chest, pecs           | Daftar latihan   |
| punggung     | punggung, back, lat         | Daftar latihan   |
| kaki         | kaki, leg, paha, squat      | Daftar latihan   |
| bahu         | bahu, shoulder, deltoid     | Daftar latihan   |
| lengan       | lengan, bicep, tricep       | Daftar latihan   |
| cardio       | cardio, lari, aerobik       | Daftar latihan   |
| bulking      | bulking, naik berat, massa  | Panduan + latihan|
| cutting      | cutting, diet, lemak        | Panduan + latihan|
| pemanasan    | pemanasan, warm up          | Daftar latihan   |
| jadwal       | jadwal, program, seminggu   | Program latihan  |
| pemula       | pemula, beginner, baru      | Tips + latihan   |
| set_rep      | set, repetisi, berapa       | Tabel panduan    |

---

## 💬 Contoh Percakapan

### Contoh 1: Latihan Dada
**User:** "Latihan dada apa yang cocok untuk pemula?"  
**FitBot:** Menampilkan daftar latihan (Push Up, Bench Press, Dumbbell Fly, Incline Bench Press) lengkap dengan set, repetisi, level, dan tips.

### Contoh 2: Program Bulking
**User:** "Jelaskan cara bulking yang benar"  
**FitBot:** Menampilkan penjelasan bulking, 7 panduan nutrisi & latihan, dan rekomendasi latihan compound.

### Contoh 3: Di luar topik
**User:** "Siapa presiden Indonesia?"  
**FitBot:** "Maaf, pertanyaanmu berada di luar topik chatbot fitness ini. Silakan tanyakan seputar latihan gym, workout, bulking, cutting, atau fitness dasar lainnya."

### Contoh 4: Jadwal Gym
**User:** "Kasih jadwal gym untuk pemula seminggu"  
**FitBot:** Menampilkan program 3 hari Full Body (Senin, Rabu, Jumat) dengan detail latihan tiap hari.

---

## ⚙️ Teknologi yang Digunakan

| Layer      | Teknologi                    |
|------------|------------------------------|
| Frontend   | HTML5, CSS3, Vanilla JS      |
| Backend    | Node.js + Express.js         |
| Database   | JSON lokal (knowledge base)  |
| Pendekatan | Rule-Based + Knowledge-Based |
| AI         | ❌ Tidak menggunakan AI eksternal |

---

## 🔧 Rule-Based Logic (Pseudocode)

```
FUNCTION processInput(userMessage):
  input = normalize(userMessage)   // lowercase + clean

  IF input contains greetingKeyword:
    RETURN greetingResponse

  IF input contains thankKeyword:
    RETURN thankResponse

  FOR each category in knowledgeBase:
    score = 0
    FOR each keyword in category.keywords:
      IF input contains keyword:
        score += keyword.length    // keyword lebih panjang = lebih spesifik

  IF highestScore > 0:
    RETURN formatResponse(bestMatchCategory)

  IF input contains fitnessHint:
    RETURN "topik belum ada di knowledge base"
  
  RETURN "di luar topik fitness"
```

---

## 📝 Penjelasan Tiap File

### `backend/server.js`
- Membuat server HTTP dengan Express
- Memuat knowledge base dari JSON
- Mengimplementasikan rule engine (`findMatchingCategory`)
- Mengekspos endpoint `POST /api/chat`

### `frontend/index.html`
- Struktur halaman: sidebar, welcome screen, chat area, input
- Tombol suggestion untuk pertanyaan cepat
- Modal diagram alur sistem

### `frontend/style.css`
- Dark theme elegan (warna utama #0f0f0f)
- Bubble chat kiri (bot) dan kanan (user)
- Responsive untuk laptop dan mobile
- Animasi smooth

### `frontend/app.js`
- Mengirim pesan ke API backend dengan `fetch()`
- Merender respons sesuai tipe (latihan, panduan, jadwal, dll)
- Menampilkan typing indicator
- Mengelola riwayat sidebar

### `knowledge-base/fitness_knowledge.json`
- 13 kategori pengetahuan
- 30+ data latihan fitness dasar
- Setiap latihan: nama, deskripsi, set, rep, level, tips

---

## 🎓 Untuk Presentasi

**Poin yang bisa dijelaskan:**
1. Sistem tidak menggunakan AI eksternal (OpenAI, dll)
2. Semua jawaban berasal dari knowledge base sendiri
3. Rule engine mencocokkan keyword dengan scoring
4. Pendekatan Knowledge-Based System yang transparan dan dapat diaudit
5. Mudah dikembangkan: tinggal tambah data di JSON

**Klik tombol 🔄 di pojok kanan bawah** untuk melihat diagram alur sistem saat demo.
