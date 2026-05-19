/**
 * ============================================================
 * FITBOT - Frontend JavaScript (app.js)
 * Handles: UI logic, API calls, chat rendering
 * ============================================================
 */

// ============================================================
// KONFIGURASI
// ============================================================
const API_URL = 'http://localhost:3001/api/chat';

// State aplikasi
let chatHistory = [];       // Riwayat pesan sesi ini
let isTyping = false;       // Apakah bot sedang "mengetik"
let sessionCount = 0;       // Jumlah sesi chat

// ============================================================
// DOM ELEMENTS
// ============================================================
const welcomeScreen  = document.getElementById('welcomeScreen');
const chatArea       = document.getElementById('chatArea');
const chatMessages   = document.getElementById('chatMessages');
const userInput      = document.getElementById('userInput');
const sendBtn        = document.getElementById('sendBtn');
const chatHistoryEl  = document.getElementById('chatHistory');
const newChatBtn     = document.getElementById('newChatBtn');
const sidebarToggle  = document.getElementById('sidebarToggle');
const sidebar        = document.getElementById('sidebar');

// ============================================================
// EVENT LISTENERS
// ============================================================
newChatBtn.addEventListener('click', resetChat);
sidebarToggle.addEventListener('click', toggleSidebar);

// Tutup sidebar saat klik di luar (mobile)
document.addEventListener('click', (e) => {
  if (window.innerWidth <= 768) {
    if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
      sidebar.classList.remove('open');
    }
  }
});

// ============================================================
// FUNGSI UTAMA: KIRIM PESAN
// ============================================================

/**
 * Dipanggil saat user menekan tombol kirim atau Enter
 */
async function sendMessage() {
  const text = userInput.value.trim();
  if (!text || isTyping) return;

  // Sembunyikan welcome screen, tampilkan chat area
  showChatArea();

  // Tampilkan bubble user
  appendUserBubble(text);
  chatHistory.push({ role: 'user', text });

  // Reset input
  userInput.value = '';
  autoResize(userInput);
  setSendDisabled(true);

  // Tampilkan typing indicator
  const typingEl = showTypingIndicator();

  try {
    // Kirim ke backend API
    const response = await fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message: text })
    });

    const data = await response.json();

    // Hapus typing indicator
    typingEl.remove();

    // Tampilkan respons bot
    if (data.success) {
      appendBotBubble(data.response);
      chatHistory.push({ role: 'bot', response: data.response });
    } else {
      appendBotBubble({
        type: 'error',
        message: 'Terjadi kesalahan. Coba lagi ya! 😅'
      });
    }

  } catch (err) {
    typingEl.remove();
    appendBotBubble({
      type: 'error',
      message: '❌ Tidak dapat terhubung ke server. Pastikan backend sudah berjalan di port 3001.'
    });
    console.error('API Error:', err);
  }

  setSendDisabled(false);
  scrollToBottom();
  updateHistorySidebar(text);
}

/**
 * Kirim pesan dari suggestion card di welcome screen
 */
function sendSuggestion(text) {
  userInput.value = text;
  sendMessage();
}

// ============================================================
// FUNGSI RENDER BUBBLE
// ============================================================

/**
 * Tambahkan bubble pesan user (kanan)
 */
function appendUserBubble(text) {
  const row = document.createElement('div');
  row.className = 'message-row user';
  row.innerHTML = `
    <div class="avatar user">🧑</div>
    <div class="bubble user">${escapeHtml(text)}</div>
  `;
  chatMessages.appendChild(row);
  scrollToBottom();
}

/**
 * Tambahkan bubble respons bot (kiri)
 * Merender konten sesuai tipe respons dari API
 */
function appendBotBubble(response) {
  const row = document.createElement('div');
  row.className = 'message-row bot';

  let content = '';

  switch (response.type) {
    case 'greeting':
    case 'thanks':
      content = `<p>${escapeHtml(response.message)}</p>`;
      break;

    case 'latihan':
      content = renderLatihanResponse(response);
      break;

    case 'panduan':
      content = renderPanduanResponse(response);
      break;

    case 'jadwal':
      content = renderJadwalResponse(response);
      break;

    case 'set_rep':
      content = renderSetRepResponse(response);
      break;

    case 'pemula':
      content = renderPemulaResponse(response);
      break;

    case 'out_of_topic':
    case 'unknown_fitness':
      content = `<p class="out-of-topic-msg">${escapeHtml(response.message)}</p>`;
      break;

    default:
      content = `<p>${escapeHtml(response.message || 'Maaf, terjadi kesalahan.')}</p>`;
  }

  row.innerHTML = `
    <div class="avatar bot">⚡</div>
    <div class="bubble bot">${content}</div>
  `;

  chatMessages.appendChild(row);
  scrollToBottom();
}

// ============================================================
// RENDERER UNTUK SETIAP TIPE RESPONS
// ============================================================

/**
 * Render daftar latihan (dada, punggung, kaki, dll)
 */
function renderLatihanResponse(response) {
  const latihanCards = response.latihan.map(l => `
    <div class="exercise-card">
      <div class="exercise-header">
        <span class="exercise-name">🏋️ ${escapeHtml(l.nama)}</span>
        <span class="exercise-level level-${l.level.toLowerCase()}">${escapeHtml(l.level)}</span>
      </div>
      <p class="exercise-desc">${escapeHtml(l.deskripsi)}</p>
      <p class="exercise-target">🎯 Target: ${escapeHtml(l.target_otot)}</p>
      <div class="exercise-meta">
        <span class="meta-item">
          <span class="meta-icon">🔁</span>
          <span>${l.set} Set</span>
        </span>
        <span class="meta-item">
          <span class="meta-icon">⚡</span>
          <span class="meta-val">${escapeHtml(String(l.repetisi))} Rep</span>
        </span>
      </div>
      ${l.tips ? `<div class="exercise-tip">💡 Tips: ${escapeHtml(l.tips)}</div>` : ''}
    </div>
  `).join('');

  return `
    <div class="response-header">
      <span class="response-label">${escapeHtml(response.kategori)}</span>
      <div class="response-title">Latihan ${capitalize(response.kategori)}</div>
      <div class="response-subtitle">Rekomendasi latihan berdasarkan knowledge base</div>
    </div>
    <div class="exercise-list">${latihanCards}</div>
  `;
}

/**
 * Render panduan bulking / cutting
 */
function renderPanduanResponse(response) {
  const panduanItems = response.panduan.map(p => `<li>${escapeHtml(p)}</li>`).join('');

  const rekomendasiCards = (response.latihan_rekomendasi || []).map(l => `
    <div class="exercise-card">
      <div class="exercise-header">
        <span class="exercise-name">🏋️ ${escapeHtml(l.nama)}</span>
      </div>
      <div class="exercise-meta">
        <span class="meta-item">🔁 ${l.set} Set</span>
        <span class="meta-item">⚡ <span class="meta-val">${escapeHtml(String(l.repetisi))} Rep</span></span>
      </div>
      ${l.catatan ? `<div class="exercise-tip">📝 ${escapeHtml(l.catatan)}</div>` : ''}
    </div>
  `).join('');

  return `
    <div class="response-header">
      <span class="response-label">${escapeHtml(response.kategori)}</span>
      <div class="response-title">Panduan ${capitalize(response.kategori)}</div>
    </div>
    <p style="color:var(--text-secondary);font-size:13.5px;margin-bottom:12px;">${escapeHtml(response.penjelasan)}</p>
    <ul class="panduan-list">${panduanItems}</ul>
    ${rekomendasiCards ? `<div style="margin-top:14px;"><div class="response-subtitle" style="margin-bottom:10px;">🏋️ Latihan Rekomendasi:</div><div class="exercise-list">${rekomendasiCards}</div></div>` : ''}
  `;
}

/**
 * Render jadwal latihan
 */
function renderJadwalResponse(response) {
  const jadwalCards = response.program.jadwal.map(j => `
    <div class="jadwal-card">
      <div class="jadwal-day">📅 ${escapeHtml(j.hari)}</div>
      <div class="jadwal-focus">${escapeHtml(j.fokus)}</div>
      <div class="jadwal-exercises">
        ${j.latihan.map(l => `<span class="jadwal-tag">${escapeHtml(l)}</span>`).join('')}
      </div>
    </div>
  `).join('');

  return `
    <div class="response-header">
      <span class="response-label">Jadwal</span>
      <div class="response-title">${escapeHtml(response.program.nama)}</div>
      <div class="response-subtitle">${escapeHtml(response.penjelasan)}</div>
    </div>
    <div class="jadwal-grid">${jadwalCards}</div>
  `;
}

/**
 * Render panduan set & repetisi
 */
function renderSetRepResponse(response) {
  const cards = response.panduan.map(p => `
    <div class="setrep-card">
      <div>
        <div class="setrep-tujuan">${escapeHtml(p.tujuan)}</div>
        <div class="setrep-catatan">${escapeHtml(p.catatan)}</div>
        <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">
          ⏱️ Istirahat: ${escapeHtml(p.istirahat)}
        </div>
      </div>
      <div class="setrep-stats">
        <div>
          <div class="setrep-val">${escapeHtml(p.set)}</div>
          <div class="setrep-key">Set</div>
        </div>
        <div style="margin-top:6px;">
          <div class="setrep-val">${escapeHtml(p.repetisi)}</div>
          <div class="setrep-key">Reps</div>
        </div>
      </div>
    </div>
  `).join('');

  return `
    <div class="response-header">
      <span class="response-label">Volume Training</span>
      <div class="response-title">Panduan Set & Repetisi</div>
      <div class="response-subtitle">${escapeHtml(response.penjelasan)}</div>
    </div>
    <div class="setrep-grid">${cards}</div>
  `;
}

/**
 * Render panduan pemula
 */
function renderPemulaResponse(response) {
  const tipsItems = response.tips.map(t => `<li>${escapeHtml(t)}</li>`).join('');
  const latihanCards = (response.latihan_awal || []).map(l => `
    <div class="exercise-card">
      <div class="exercise-header">
        <span class="exercise-name">🏋️ ${escapeHtml(l.nama)}</span>
      </div>
      <div class="exercise-meta">
        <span class="meta-item">🔁 ${l.set} Set</span>
        <span class="meta-item">⚡ <span class="meta-val">${escapeHtml(String(l.repetisi))}</span></span>
      </div>
    </div>
  `).join('');

  return `
    <div class="response-header">
      <span class="response-label">Pemula</span>
      <div class="response-title">Panduan Gym untuk Pemula 🏋️</div>
      <div class="response-subtitle">${escapeHtml(response.penjelasan)}</div>
    </div>
    <ul class="panduan-list">${tipsItems}</ul>
    <div style="margin-top:14px;">
      <div class="response-subtitle" style="margin-bottom:10px;">💪 Latihan Awal yang Disarankan:</div>
      <div class="exercise-list">${latihanCards}</div>
    </div>
  `;
}

// ============================================================
// TYPING INDICATOR
// ============================================================

function showTypingIndicator() {
  const row = document.createElement('div');
  row.className = 'message-row bot';
  row.id = 'typingRow';
  row.innerHTML = `
    <div class="avatar bot">⚡</div>
    <div class="bubble bot">
      <div class="typing-indicator">
        <div class="dot"></div>
        <div class="dot"></div>
        <div class="dot"></div>
      </div>
    </div>
  `;
  chatMessages.appendChild(row);
  scrollToBottom();
  isTyping = true;
  return row;
}

// ============================================================
// UI HELPERS
// ============================================================

function showChatArea() {
  if (welcomeScreen.style.display !== 'none') {
    welcomeScreen.style.display = 'none';
    chatArea.classList.add('active');
  }
}

function resetChat() {
  // Simpan chat lama ke sidebar jika ada
  if (chatHistory.length > 0) {
    // sudah di-update sebelumnya
  }

  // Reset state
  chatHistory = [];
  chatMessages.innerHTML = '';

  // Tampilkan kembali welcome screen
  welcomeScreen.style.display = '';
  chatArea.classList.remove('active');

  // Focus ke input
  userInput.focus();
}

function scrollToBottom() {
  chatArea.scrollTop = chatArea.scrollHeight;
}

function setSendDisabled(disabled) {
  sendBtn.disabled = disabled;
  isTyping = disabled;
}

function autoResize(el) {
  el.style.height = 'auto';
  el.style.height = Math.min(el.scrollHeight, 160) + 'px';
}

function handleKeyDown(e) {
  // Enter tanpa Shift = kirim; Shift+Enter = baris baru
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
}

function toggleSidebar() {
  sidebar.classList.toggle('open');
}

function openFlowModal() {
  document.getElementById('flowModal').classList.add('open');
}

function closeFlowModal() {
  document.getElementById('flowModal').classList.remove('open');
}

// Tutup modal saat klik backdrop
document.getElementById('flowModal').addEventListener('click', (e) => {
  if (e.target === document.getElementById('flowModal')) closeFlowModal();
});

// ============================================================
// SIDEBAR HISTORY
// ============================================================

function updateHistorySidebar(firstMessage) {
  // Hanya tambahkan saat pertama kali pesan dikirim di sesi ini
  if (chatHistory.filter(h => h.role === 'user').length === 1) {
    sessionCount++;

    const emptyEl = chatHistoryEl.querySelector('.history-empty');
    if (emptyEl) emptyEl.remove();

    // Hapus active dari item sebelumnya
    chatHistoryEl.querySelectorAll('.history-item').forEach(el => el.classList.remove('active'));

    const item = document.createElement('div');
    item.className = 'history-item active';
    item.innerHTML = `
      <span class="history-icon">💬</span>
      <span>${escapeHtml(firstMessage.slice(0, 28))}${firstMessage.length > 28 ? '…' : ''}</span>
    `;
    item.title = firstMessage;

    chatHistoryEl.insertBefore(item, chatHistoryEl.firstChild);
  }
}

// ============================================================
// UTILITY
// ============================================================

function escapeHtml(str) {
  if (typeof str !== 'string') return String(str);
  return str
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function capitalize(str) {
  return str.charAt(0).toUpperCase() + str.slice(1);
}

// ============================================================
// INIT
// ============================================================
userInput.focus();
console.log('🏋️ FitBot frontend siap!');
