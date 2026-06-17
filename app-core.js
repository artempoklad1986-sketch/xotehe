// ============================================================
//  app-core.js — ядро: DB, sync, nav, modal, time, notify, utils
// ============================================================

const API_URL    = 'https://srm.itmag.site/api/api.php';
const API_KEY    = '12345';
const apiHeaders = { 'Content-Type': 'application/json', 'X-Api-Key': API_KEY };

/* ---------- CRM FRAMEWORK ---------- */
window.CRM = {
  _modules: {},

  registerModule(cfg) {
    this._modules[cfg.id] = cfg;
    this._injectPage(cfg);
    this._injectNav(cfg);
    console.log(`✅ Модуль "${cfg.name}" зарегистрирован`);
  },

  _injectPage(cfg) {
    if (document.getElementById('page-' + cfg.id)) return;
    const div     = document.createElement('div');
    div.className = 'page';
    div.id        = 'page-' + cfg.id;
    div.innerHTML = cfg.page || '';
    const main    = document.getElementById('mainContent');
    if (main) main.appendChild(div);
  },

  _injectNav(cfg) {
    if (document.getElementById('nav-' + cfg.id)) return;
    const btn     = document.createElement('button');
    btn.className = 'nav-btn';
    btn.id        = 'nav-' + cfg.id;
    btn.innerHTML = `<span>${cfg.icon}</span><span>${cfg.name}</span>`;
    btn.onclick   = () => showPage(cfg.id, btn);
    const section = document.getElementById('modulesNavSection');
    if (section) section.appendChild(btn);
  },

  api(module, action, body = null, params = {}) {
    const qs  = new URLSearchParams({ module, action, key: API_KEY, ...params }).toString();
    const url = `${API_URL}?${qs}`;
    return fetch(url, {
      method:  body ? 'POST' : 'GET',
      headers: apiHeaders,
      body:    body ? JSON.stringify(body) : undefined,
    })
    .then(r => r.json())
    .catch(e => {
      console.warn(`CRM.api(${module}/${action}) error:`, e);
      return { ok: false, error: e.message };
    });
  }
};

/* ---------- beforeunload — ЗАЩИТА ---------- */
window.addEventListener('beforeunload', () => {
  if (dbCache && saveTimer && isLoaded) {
    clearTimeout(saveTimer);
    navigator.sendBeacon(
      `${API_URL}?action=db&key=${API_KEY}`,
      JSON.stringify(dbCache)
    );
  }
});

/* ---------- СЛОЙ ДАННЫХ ---------- */
let dbCache   = null;
let saveTimer = null;
let isSyncing = false;
let isLoaded  = false;

function initDBStructure() {
  return {
    orders:       [],
    finance:      [],
    clients:      [],
    notes:        [],
    warehouse:    [],
    calEvents:    [],
    chatHistory:  [],
    orderCounter: 1,
    invoices:     [],
    salary:       { records: [], employees: [], shifts: [] },
    weborders:    [],
    debts:        [],
    checklists:   { templates: [], sessions: [] },
    printers:     [],
    templates:    [],
    reviews:      { list: [] },
    docs:         { documents: [], folders: [] },
    settings: {
      company:        '',
      inn:            '',
      ogrn:           '',
      address:        '',
      phone:          '',
      email:          '',
      website:        '',
      bankAcc:        '',
      bik:            '',
      bankName:       '',
      korAcc:         '',
      kpp:            '',
      receiptHeader:  'Спасибо за заказ! Ждём вас снова.',
      receiptFooter:  'Сохраняйте чек при получении заказа.',
      signatory:      '',
      signatoryTitle: 'Менеджер',
      vat:            '0',
      currency:       '₽',
      apiKey:         '',
      apiModel:       'deepseek-chat',
      modules:        {},
      tgToken:        '',
      tgBossId:       ''
    }
  };
}

function getDB() {
  if (!dbCache) dbCache = initDBStructure();
  return dbCache;
}

function saveDB(db) {
  dbCache = db;
  updateDBSize();

  try {
    localStorage.setItem('printcrm_backup', JSON.stringify({
      data:      db,
      timestamp: Date.now()
    }));
  } catch(e) {}

  if (!isLoaded) {
    console.warn('⚠️ Сервер не отвечает, данные в localStorage');
    showSyncStatus('error');
    return;
  }

  clearTimeout(saveTimer);
  saveTimer = setTimeout(() => pushToServer(db), 800);
  showSyncStatus('saving');
}

async function pushToServer(db) {
  if (isSyncing) return;
  if (!isLoaded) return;

  isSyncing = true;
  showSyncStatus('saving');

  try {
    const res = await fetch(`${API_URL}?action=db&key=${API_KEY}`, {
      method:  'POST',
      headers: apiHeaders,
      body:    JSON.stringify(db)
    });

    if (!res.ok) throw new Error('HTTP ' + res.status);

    const raw = await res.text();
    console.log('💾 Сервер ответил на сохранение:', raw.slice(0, 100));
    showSyncStatus('ok');
  } catch (e) {
    showSyncStatus('error');
    console.warn('Ошибка сохранения на сервер:', e.message);
    notify('⚠️ Не удалось сохранить: ' + e.message, 'error');
  } finally {
    isSyncing = false;
  }
}

async function loadFromServer() {
  showSyncStatus('loading');

  try {
    const res = await fetch(`${API_URL}?action=db&key=${API_KEY}`, {
      headers: apiHeaders
    });

    if (res.ok) {
      const raw = await res.text();

      if (!raw || raw.trim() === 'null' || raw.trim() === '') {
        console.log('📭 База на сервере пуста — создаём новую');
        dbCache  = initDBStructure();
        isLoaded = true;
        showSyncStatus('ok');
        await pushToServer(dbCache);
        return true;
      }

      const jsonMatch = raw.match(/(\{[\s\S]*\}|$$[\s\S]*$$)/);
      if (!jsonMatch) {
        console.warn('⚠️ Сервер вернул не-JSON:', raw.slice(0, 200));
        throw new Error('Не JSON: ' + raw.slice(0, 100));
      }

      const json = JSON.parse(jsonMatch[0]);
      const data = json.data || json;

      if (data && typeof data === 'object' && !Array.isArray(data)) {
        const base = initDBStructure();
        dbCache  = { ...base, ...data };
        isLoaded = true;
        showSyncStatus('ok');
        localStorage.removeItem('printcrm_backup');
        console.log('✅ Загружено с сервера — orders:',
          dbCache.orders?.length, 'finance:', dbCache.finance?.length);
        return true;
      } else {
        console.warn('⚠️ Неверная структура данных:', typeof data, data);
        throw new Error('Неверная структура БД');
      }
    } else {
      throw new Error('HTTP ' + res.status);
    }

  } catch (e) {
    console.warn('Сервер недоступен:', e.message);
  }

  const backupRaw = localStorage.getItem('printcrm_backup');
  if (backupRaw) {
    try {
      const backup = JSON.parse(backupRaw);
      if (backup.data && backup.data.orders) {
        const base = initDBStructure();
        dbCache  = { ...base, ...backup.data };
        isLoaded = false;
        showSyncStatus('error');
        console.log('💾 Восстановлено из localStorage');
        notify('⚠️ Восстановлены данные из локальной копии', 'info');
        return false;
      }
    } catch(e) {
      console.warn('Ошибка чтения backup:', e);
    }
  }

  dbCache  = initDBStructure();
  isLoaded = false;
  showSyncStatus('error');
  return false;
}

function showSyncStatus(status) {
  const el  = document.getElementById('syncStatus');
  if (!el) return;
  const map = {
    loading: ['⟳ Загрузка...',      'var(--accent4)'],
    saving:  ['⟳ Сохранение...',    'var(--accent4)'],
    ok:      ['☁ Синхронизировано', 'var(--accent3)'],
    error:   ['⚠ Ошибка сервера',   'var(--danger)' ],
  };
  const [text, color] = map[status] || ['', ''];
  el.textContent = text;
  el.style.color = color;
}

function updateDBSize() {
  const el   = document.getElementById('dbSizeInfo');
  if (!el) return;
  const size = dbCache ? (JSON.stringify(dbCache).length / 1024).toFixed(1) : '0';
  el.textContent = `БД: ${size} KB`;
}

function clearOldLocalStorage() {
  const OLD_KEYS = ['printcrm_v2', 'printcrm_local_cache', 'printcrm_db', 'printcrm_v1'];
  let cleared = false;
  OLD_KEYS.forEach(k => {
    if (localStorage.getItem(k) !== null) {
      localStorage.removeItem(k);
      cleared = true;
    }
  });
  if (cleared) {
    console.log('🧹 Старый localStorage очищен');
    notify('🧹 Локальный кэш очищен', 'info');
  }
}

/* ---------- ФОНОВАЯ СИНХРОНИЗАЦИЯ ---------- */
setInterval(async () => {
  if (isSyncing || !isLoaded) return;
  try {
    const res = await fetch(`${API_URL}?action=db&key=${API_KEY}`, {
      headers: apiHeaders
    });
    if (!res.ok) return;
    const json = await res.json();
    const data = json.data || json;
    if (!data || typeof data !== 'object') return;

    const incoming = JSON.stringify(data);
    const current  = JSON.stringify(dbCache);

    if (incoming !== current) {
      const base = initDBStructure();
      dbCache = { ...base, ...data };
      showSyncStatus('ok');
      refreshDashboard();
      const activePage = document.querySelector('.page.active')?.id;
      if (activePage === 'page-orders')  renderKanban();
      if (activePage === 'page-finance') renderFinancePage();
      notify('📡 Данные обновлены', 'info');
    }
  } catch {
    showSyncStatus('error');
  }
}, 30000);

/* ============================================================
   NAVIGATION
============================================================ */
function showPage(name, btn) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));

  const page = document.getElementById('page-' + name);
  if (page) page.classList.add('active');
  if (btn)  btn.classList.add('active');

  const renderMap = {
    dashboard:  refreshDashboard,
    orders:     renderKanban,
    finance:    renderFinancePage,
    stats:      renderStats,
    accounting: renderAccounting,
    clients:    renderClients,
    notes:      renderNotes,
    settings:   loadSettings,
    calendar:   renderCalendar,
  };

  if (renderMap[name]) {
    renderMap[name]();
    return;
  }

  const mod = CRM._modules && CRM._modules[name];
  if (mod && typeof mod.render === 'function') {
    Promise.resolve()
      .then(() => mod.render())
      .catch(e => {
        console.error('Ошибка рендера модуля ' + name + ':', e);
        notify && notify('Ошибка модуля «' + name + '»', 'error');
      });
  }
}

/* ============================================================
   MODAL
============================================================ */
function openModal(id) {
  const m = document.getElementById(id);
  if (!m) return;
  m.classList.add('open');
  m.addEventListener('click', e => e.stopPropagation(), { once: false });

  if (id === 'orderModal')   initOrderModal();
  if (id === 'incomeModal')  { const el = document.getElementById('inc_date'); if (el) el.value = nowDTLocal(); }
  if (id === 'expenseModal') { const el = document.getElementById('exp_date'); if (el) el.value = nowDTLocal(); }
}

function closeModal(id) {
  const m = document.getElementById(id);
  if (m) m.classList.remove('open');
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', e => {
      if (e.target === o) o.classList.remove('open');
    });
  });
});

/* ============================================================
   TIME
============================================================ */
function nowDTLocal() {
  const n = new Date(), p = v => String(v).padStart(2, '0');
  return `${n.getFullYear()}-${p(n.getMonth()+1)}-${p(n.getDate())}T${p(n.getHours())}:${p(n.getMinutes())}`;
}

function updateClock() {
  const n      = new Date(), p = v => String(v).padStart(2, '0');
  const days   = ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'];
  const months = ['янв','фев','мар','апр','май','июн','июл','авг','сен','окт','ноя','дек'];
  const tEl    = document.getElementById('clockTime');
  const dEl    = document.getElementById('clockDate');
  if (tEl) tEl.textContent = `${p(n.getHours())}:${p(n.getMinutes())}:${p(n.getSeconds())}`;
  if (dEl) dEl.textContent = `${days[n.getDay()]}, ${n.getDate()} ${months[n.getMonth()]}`;
}
setInterval(updateClock, 1000);
updateClock();

/* ============================================================
   NOTIFICATIONS
============================================================ */
function notify(msg, type = 'info') {
  const icons = { success: '✅', error: '❌', info: '💡' };
  const stack = document.getElementById('notifStack');
  if (!stack) return;
  const el     = document.createElement('div');
  el.className = `notification ${type}`;
  el.innerHTML = `<span>${icons[type] || 'ℹ'}</span><span>${msg}</span>`;
  stack.appendChild(el);
  setTimeout(() => {
    el.style.cssText += 'opacity:0;transform:translateX(20px);transition:all 0.3s;';
    setTimeout(() => el.remove(), 300);
  }, 3500);
}

/* ============================================================
   ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ (utils)
============================================================ */
function escapeHtml(str) {
  if (!str) return '';
  return str.replace(/[&<>]/g, function(m) {
    if (m === '&') return '&amp;';
    if (m === '<') return '&lt;';
    if (m === '>') return '&gt;';
    return m;
  });
}

function formatSize(bytes) {
  if (!bytes) return '0 Б';
  if (bytes < 1024) return bytes + ' Б';
  if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' КБ';
  return (bytes / 1048576).toFixed(1) + ' МБ';
}

function formatDate(str) {
  if (!str) return '—';
  try {
    const d    = new Date(str);
    const base = d.toLocaleDateString('ru-RU',
      { day: '2-digit', month: '2-digit', year: 'numeric' });
    return str.includes('T')
      ? base + ' ' + d.toLocaleTimeString('ru-RU',
          { hour: '2-digit', minute: '2-digit' })
      : base;
  } catch {
    return str;
  }
}

function formatMoney(val, currency) {
  const cur = currency || (getDB().settings?.currency || '₽');
  return (parseFloat(val) || 0).toLocaleString('ru-RU') + ' ' + cur;
}

function getStatusBadge(status) {
  const map = {
    new:    '<span class="badge badge-new">Новый</span>',
    work:   '<span class="badge badge-work">В работе</span>',
    ready:  '<span class="badge badge-ready">Готов</span>',
    done:   '<span class="badge badge-done">Выдан</span>',
    cancel: '<span class="badge badge-cancel">Отменён</span>',
  };
  return map[status] || `<span class="badge">${status}</span>`;
}

function exportDB() {
  const db   = getDB();
  const blob = new Blob([JSON.stringify(db, null, 2)], { type: 'application/json' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href     = url;
  a.download = `printcrm_${new Date().toISOString().slice(0, 10)}.json`;
  a.click();
  URL.revokeObjectURL(url);
  notify('База экспортирована', 'success');
}

function importDB() {
  document.getElementById('importFile')?.click();
}

function loadImportFile(e) {
  const file = e.target.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = ev => {
    try {
      const data = JSON.parse(ev.target.result);
      if (!confirm('Загрузить базу? Текущие данные будут заменены.')) return;
      saveDB(data);
      notify('База загружена', 'success');
      refreshDashboard();
    } catch { notify('Ошибка: неверный файл', 'error'); }
  };
  reader.readAsText(file);
}

function clearDB() {
  if (!confirm('УДАЛИТЬ ВСЕ ДАННЫЕ? Необратимо!')) return;
  if (!confirm('Вы точно уверены?')) return;
  dbCache  = initDBStructure();
  isLoaded = true;
  pushToServer(dbCache);
  notify('База очищена', 'error');
  refreshDashboard();
}

function renderWarehouse() {
  const mod = CRM._modules['warehouse'];
  if (mod?.render) { mod.render(); return; }
  _renderStub('page-warehouse', '📦', 'Склад', 'Модуль склада не подключён');
}

function renderCalendar() {
  const mod = CRM._modules['calendar'];
  if (mod?.render) { mod.render(); return; }
  _renderStub('page-calendar', '📅', 'Календарь', 'Модуль календаря не подключён');
}

function _renderStub(pageId, icon, title, desc) {
  const p = document.getElementById(pageId);
  if (!p) return;
  p.innerHTML = `<div class="empty-state" style="padding-top:80px;">
    <div class="icon">${icon}</div>
    <div class="title">${title}</div>
    <div class="desc">${desc}</div>
  </div>`;
}