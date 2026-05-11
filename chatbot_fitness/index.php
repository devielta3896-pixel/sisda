<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FitBot — Chatbot Fitness Cerdas</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:        #0f1117;
      --surface:   #1a1d27;
      --surface2:  #22263a;
      --accent:    #4ade80;
      --accent2:   #22d3ee;
      --text:      #e8eaf0;
      --text-muted:#7a7f94;
      --border:    #2e3247;
      --user-bubble: #1e3a2f;
      --bot-bubble:  #1a1d27;
      --danger:    #f87171;
      --radius:    14px;
      --mono:      'Space Mono', monospace;
      --sans:      'DM Sans', sans-serif;
    }

    body {
      font-family: var(--sans);
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* HEADER */
    header {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 16px 24px;
      border-bottom: 1px solid var(--border);
      background: var(--surface);
    }
    .logo {
      width: 38px; height: 38px;
      background: var(--accent);
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-family: var(--mono);
      font-weight: 700;
      font-size: 16px;
      color: #0f1117;
    }
    .header-info h1 { font-size: 17px; font-weight: 600; }
    .header-info p  { font-size: 12px; color: var(--text-muted); font-family: var(--mono); }
    .status-dot {
      width: 8px; height: 8px;
      background: var(--accent);
      border-radius: 50%;
      margin-left: auto;
      box-shadow: 0 0 8px var(--accent);
      animation: pulse 2s infinite;
    }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }

    /* CHAT AREA */
    #chat-area {
      flex: 1;
      overflow-y: auto;
      padding: 24px 16px;
      display: flex;
      flex-direction: column;
      gap: 16px;
      max-width: 760px;
      width: 100%;
      margin: 0 auto;
    }

    /* BUBBLE */
    .bubble-wrap {
      display: flex;
      gap: 10px;
      animation: fadeUp .3s ease;
    }
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(10px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .bubble-wrap.user { flex-direction: row-reverse; }

    .avatar {
      width: 32px; height: 32px; border-radius: 50%;
      background: var(--surface2);
      display: flex; align-items: center; justify-content: center;
      font-size: 14px; flex-shrink: 0;
      font-family: var(--mono);
    }
    .bubble-wrap.user .avatar { background: var(--user-bubble); color: var(--accent); }

    .bubble {
      max-width: 78%;
      padding: 12px 16px;
      border-radius: var(--radius);
      font-size: 14.5px;
      line-height: 1.6;
      border: 1px solid var(--border);
    }
    .bubble-wrap.bot  .bubble { background: var(--bot-bubble); border-radius: 4px var(--radius) var(--radius) var(--radius); }
    .bubble-wrap.user .bubble { background: var(--user-bubble); border-radius: var(--radius) 4px var(--radius) var(--radius); color: #cfffdf; }

    /* FORM ELEMENTS dalam bubble */
    .form-group { margin-bottom: 14px; }
    .form-group label {
      display: block;
      font-size: 12px;
      color: var(--text-muted);
      font-family: var(--mono);
      margin-bottom: 6px;
      text-transform: uppercase;
      letter-spacing: .05em;
    }
    .form-group input,
    .form-group select {
      width: 100%;
      background: var(--surface2);
      border: 1px solid var(--border);
      color: var(--text);
      padding: 10px 12px;
      border-radius: 8px;
      font-family: var(--sans);
      font-size: 14px;
      outline: none;
      transition: border-color .2s;
    }
    .form-group input:focus,
    .form-group select:focus { border-color: var(--accent); }
    .form-group select option { background: var(--surface2); }

    .row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

    /* TOMBOL */
    .btn {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 10px 20px;
      border-radius: 8px;
      border: none;
      cursor: pointer;
      font-family: var(--sans);
      font-size: 14px;
      font-weight: 500;
      transition: all .2s;
    }
    .btn-primary {
      background: var(--accent);
      color: #0f1117;
      width: 100%;
      justify-content: center;
      margin-top: 8px;
    }
    .btn-primary:hover { background: #22c55e; }
    .btn-primary:disabled { opacity: .5; cursor: not-allowed; }

    /* BMI BADGE */
    .bmi-badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 20px;
      font-family: var(--mono);
      font-size: 12px;
      font-weight: 700;
      margin-left: 6px;
    }
    .bmi-underweight { background: #1e3a5f; color: var(--accent2); }
    .bmi-normal      { background: #1e3a2f; color: var(--accent); }
    .bmi-overweight  { background: #3a2e1e; color: #fbbf24; }
    .bmi-obesitas    { background: #3a1e1e; color: var(--danger); }

    /* KARTU LATIHAN */
    .exercise-cards { display: flex; flex-direction: column; gap: 10px; margin-top: 10px; }
    .exercise-card {
      background: var(--surface2);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 14px;
      transition: border-color .2s;
    }
    .exercise-card:hover { border-color: var(--accent); }
    .exercise-card.warning { border-color: #fbbf24; }
    .exercise-header {
      display: flex; align-items: center;
      justify-content: space-between;
      margin-bottom: 6px;
    }
    .exercise-name { font-weight: 600; font-size: 15px; }
    .badge {
      font-family: var(--mono);
      font-size: 10px;
      padding: 3px 8px;
      border-radius: 20px;
      background: var(--surface);
      border: 1px solid var(--border);
      color: var(--text-muted);
    }
    .badge.ringan  { color: var(--accent);  border-color: var(--accent); }
    .badge.sedang  { color: #fbbf24; border-color: #fbbf24; }
    .badge.tinggi  { color: var(--danger);  border-color: var(--danger); }
    .exercise-desc { font-size: 13px; color: var(--text-muted); line-height: 1.5; margin-bottom: 8px; }
    .exercise-meta {
      display: flex; gap: 12px; flex-wrap: wrap;
      font-family: var(--mono); font-size: 11px; color: var(--accent2);
    }
    .exercise-note {
      font-size: 12px; color: #fbbf24;
      margin-top: 8px; padding-top: 8px;
      border-top: 1px solid #3a2e1e;
    }

    /* PROFIL SUMMARY */
    .profil-summary {
      background: var(--surface2);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 14px;
      margin-bottom: 12px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px;
    }
    .profil-item span:first-child {
      font-size: 11px; color: var(--text-muted);
      font-family: var(--mono); text-transform: uppercase;
      display: block; margin-bottom: 2px;
    }
    .profil-item span:last-child { font-size: 14px; font-weight: 500; }

    /* TYPING INDICATOR */
    .typing {
      display: flex; gap: 4px; align-items: center;
      padding: 14px 16px;
    }
    .typing span {
      width: 7px; height: 7px;
      background: var(--text-muted);
      border-radius: 50%;
      animation: bounce .8s infinite;
    }
    .typing span:nth-child(2) { animation-delay: .15s; }
    .typing span:nth-child(3) { animation-delay: .3s; }
    @keyframes bounce { 0%,80%,100%{transform:scale(.8)} 40%{transform:scale(1.2)} }

    /* ERROR */
    .error-msg {
      color: var(--danger);
      font-size: 13px;
      margin-top: 6px;
      font-family: var(--mono);
    }

    /* RESTART */
    .btn-restart {
      background: transparent;
      border: 1px solid var(--border);
      color: var(--text-muted);
      padding: 8px 16px;
      border-radius: 8px;
      cursor: pointer;
      font-family: var(--mono);
      font-size: 12px;
      margin-top: 12px;
      transition: all .2s;
    }
    .btn-restart:hover { border-color: var(--accent); color: var(--accent); }

    /* SCROLLBAR */
    #chat-area::-webkit-scrollbar { width: 4px; }
    #chat-area::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }
  </style>
</head>
<body>

<header>
  <div class="logo">FB</div>
  <div class="header-info">
    <h1>FitBot</h1>
    <p>Knowledge-Based Fitness Advisor</p>
  </div>
  <div class="status-dot"></div>
</header>

<div id="chat-area"></div>

<script>
const chat   = document.getElementById('chat-area');
let   userId = null;

// ── Render bubble ──────────────────────────────────────────
function addBubble(who, html) {
  const emo  = who === 'bot' ? '🤖' : '👤';
  const wrap = document.createElement('div');
  wrap.className = `bubble-wrap ${who}`;
  wrap.innerHTML = `
    <div class="avatar">${emo}</div>
    <div class="bubble">${html}</div>`;
  chat.appendChild(wrap);
  chat.scrollTop = chat.scrollHeight;
  return wrap;
}

// ── Typing indicator ───────────────────────────────────────
function showTyping() {
  return addBubble('bot',
    `<div class="typing"><span></span><span></span><span></span></div>`);
}

// ── Teks BMI berwarna ──────────────────────────────────────
function bmiBadge(bmi, kat) {
  const label = {underweight:'Kurus', normal:'Normal', overweight:'Gemuk', obesitas:'Obesitas'};
  return `${bmi} <span class="bmi-badge bmi-${kat}">${label[kat]}</span>`;
}

// ── Kartu latihan ──────────────────────────────────────────
function buildExerciseCards(exercises) {
  if (!exercises.length) return '<p style="color:var(--text-muted)">Tidak ada latihan ditemukan.</p>';

  return exercises.map(ex => {
    const metaDurasi = ex.durasi_menit
      ? `⏱ ${ex.durasi_menit} menit`
      : `🔁 ${ex.set_jumlah} set × ${ex.repetisi} reps`;
    const note = ex.catatan
      ? `<div class="exercise-note">${ex.catatan}</div>` : '';

    return `
      <div class="exercise-card ${ex.catatan ? 'warning' : ''}">
        <div class="exercise-header">
          <span class="exercise-name">${ex.nama_latihan}</span>
          <span class="badge ${ex.intensitas}">${ex.intensitas}</span>
        </div>
        <p class="exercise-desc">${ex.deskripsi}</p>
        <div class="exercise-meta">
          <span>${metaDurasi}</span>
          <span>🎯 ${ex.target_otot}</span>
        </div>
        ${note}
      </div>`;
  }).join('');
}

// ── STEP 1: Sapa user ──────────────────────────────────────
function greet() {
  setTimeout(() => {
    addBubble('bot', `
      Halo! 👋 Aku <strong>FitBot</strong>, asisten fitness berbasis sistem cerdas.<br><br>
      Aku akan merekomendasikan program latihan dasar yang disesuaikan dengan profil tubuh dan tujuanmu.<br><br>
      Yuk mulai! Isi data dirimu dulu ya 💪
    `);
    setTimeout(showFormProfil, 600);
  }, 400);
}

// ── STEP 2: Form profil ────────────────────────────────────
function showFormProfil() {
  const wrap = addBubble('bot', `
    <div class="form-group">
      <label>Nama</label>
      <input id="f-nama" type="text" placeholder="Nama kamu...">
    </div>
    <div class="row-2">
      <div class="form-group">
        <label>Tinggi Badan (cm)</label>
        <input id="f-tinggi" type="number" placeholder="170">
      </div>
      <div class="form-group">
        <label>Berat Badan (kg)</label>
        <input id="f-berat" type="number" placeholder="65">
      </div>
    </div>
    <div class="row-2">
      <div class="form-group">
        <label>Umur (tahun)</label>
        <input id="f-umur" type="number" placeholder="25">
      </div>
      <div class="form-group">
        <label>Jenis Kelamin</label>
        <select id="f-gender">
          <option value="">— Pilih —</option>
          <option value="pria">Pria</option>
          <option value="wanita">Wanita</option>
        </select>
      </div>
    </div>
    <div id="err-profil" class="error-msg"></div>
    <button class="btn btn-primary" onclick="submitProfil(this)">Lanjut →</button>
  `);
}

// ── STEP 3: Submit profil ke API ───────────────────────────
async function submitProfil(btn) {
  const nama   = document.getElementById('f-nama').value.trim();
  const tinggi = document.getElementById('f-tinggi').value;
  const berat  = document.getElementById('f-berat').value;
  const umur   = document.getElementById('f-umur').value;
  const gender = document.getElementById('f-gender').value;
  const errEl  = document.getElementById('err-profil');

  if (!nama || !tinggi || !berat || !umur || !gender) {
    errEl.textContent = '⚠ Semua field wajib diisi.'; return;
  }
  errEl.textContent = '';
  btn.disabled = true;

  addBubble('user', `${nama} · ${tinggi} cm · ${berat} kg · ${umur} thn · ${gender}`);
  const typing = showTyping();

  try {
    const res  = await fetch('api/chat.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({
        step: 'save_user',
        nama, tinggi_badan: tinggi, berat_badan: berat,
        umur, jenis_kelamin: gender
      })
    });
    const data = await res.json();
    typing.remove();

    if (data.error) { addBubble('bot', `❌ ${data.error}`); btn.disabled = false; return; }

    userId = data.user_id;
    const p = data.preview;

    addBubble('bot', `
      Oke <strong>${nama}</strong>! Berikut hasil analisis tubuhmu:<br><br>
      📊 <strong>BMI kamu:</strong> ${bmiBadge(p.bmi, p.bmi_kategori)}<br>
      🗂 <strong>Kategori umur:</strong> ${p.umur_kategori.replace('_', ' ')}<br><br>
      Sekarang aku butuh info tambahan nih 👇
    `);
    setTimeout(showFormGoals, 500);

  } catch(e) {
    typing.remove();
    addBubble('bot', '❌ Gagal terhubung ke server. Pastikan PHP sudah berjalan.');
    btn.disabled = false;
  }
}

// ── STEP 4: Form goals & kondisi ──────────────────────────
function showFormGoals() {
  addBubble('bot', `
    <div class="form-group">
      <label>Tujuan Olahraga</label>
      <select id="f-goals">
        <option value="">— Pilih —</option>
        <option value="weight_loss">Turun Berat Badan</option>
        <option value="muscle_gain">Bentuk / Tambah Otot</option>
        <option value="stamina">Tingkatkan Stamina</option>
        <option value="general">Sehat Umum</option>
      </select>
    </div>
    <div class="form-group">
      <label>Frekuensi Olahraga Saat Ini</label>
      <select id="f-frekuensi">
        <option value="">— Pilih —</option>
        <option value="pemula">Pemula (jarang / tidak pernah)</option>
        <option value="aktif">Aktif (1–2× seminggu)</option>
        <option value="rutin">Rutin (3–5× seminggu)</option>
      </select>
    </div>
    <div class="form-group">
      <label>Kondisi / Disabilitas</label>
      <select id="f-dis">
        <option value="none">Tidak ada</option>
        <option value="knee">Cedera Lutut</option>
        <option value="back">Cedera Punggung</option>
        <option value="shoulder">Cedera Bahu</option>
      </select>
    </div>
    <div id="err-goals" class="error-msg"></div>
    <button class="btn btn-primary" onclick="submitGoals(this)">Dapatkan Rekomendasi 🎯</button>
  `);
}

// ── STEP 5: Submit goals & tampilkan rekomendasi ───────────
async function submitGoals(btn) {
  const goals   = document.getElementById('f-goals').value;
  const frek    = document.getElementById('f-frekuensi').value;
  const dis     = document.getElementById('f-dis').value;
  const errEl   = document.getElementById('err-goals');

  if (!goals || !frek) { errEl.textContent = '⚠ Goals dan frekuensi wajib dipilih.'; return; }
  errEl.textContent = '';
  btn.disabled = true;

  const goalsLabel = {weight_loss:'Turun BB', muscle_gain:'Bentuk Otot', stamina:'Stamina', general:'Sehat Umum'};
  const disLabel   = {none:'Tidak ada', knee:'Cedera Lutut', back:'Cedera Punggung', shoulder:'Cedera Bahu'};
  addBubble('user', `${goalsLabel[goals]} · ${frek} · ${disLabel[dis]}`);
  const typing = showTyping();

  try {
    const res  = await fetch('api/chat.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({
        step: 'get_recommendation',
        user_id: userId,
        goals, frekuensi: frek, disabilitas: dis
      })
    });
    const data = await res.json();
    typing.remove();

    if (!data.success) {
      addBubble('bot', `⚠️ ${data.message || data.error}`);
      btn.disabled = false;
      return;
    }

    const p = data.profil;
    const bmiKat = ['underweight','normal','overweight','obesitas']
      .find(k => data.profil.bmi_kategori === k) || 'normal';

    addBubble('bot', `
      ✅ Analisis selesai! Berikut rekomendasi untuk <strong>${p.nama}</strong>:<br><br>
      <div class="profil-summary">
        <div class="profil-item"><span>BMI</span><span>${bmiBadge(p.bmi, p.bmi_kategori || 'normal')}</span></div>
        <div class="profil-item"><span>Tujuan</span><span>${p.goals}</span></div>
        <div class="profil-item"><span>Level</span><span>${p.frekuensi}</span></div>
        <div class="profil-item"><span>Kondisi</span><span>${p.disabilitas}</span></div>
      </div>
      <strong>📋 Program latihan (${data.total} latihan):</strong>
      <div class="exercise-cards">${buildExerciseCards(data.exercises)}</div>
      <br>
      💡 <em style="color:var(--text-muted);font-size:13px">Lakukan latihan ini secara konsisten. Tingkatkan intensitas bertahap setelah 2–3 minggu.</em>
      <br><br>
      <button class="btn-restart" onclick="restartChat()">🔄 Mulai konsultasi baru</button>
    `);

  } catch(e) {
    typing.remove();
    addBubble('bot', '❌ Gagal terhubung ke server.');
    btn.disabled = false;
  }
}

// ── Restart ────────────────────────────────────────────────
function restartChat() {
  userId = null;
  chat.innerHTML = '';
  greet();
}

// ── Init ───────────────────────────────────────────────────
greet();
</script>
</body>
</html>
