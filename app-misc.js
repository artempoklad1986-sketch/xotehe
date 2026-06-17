// ============================================================
//  app-misc.js — Clients, Notes, Dashboard, Stats, Accounting,
//                Settings, Print, AI Chat, Init
// ============================================================

/* ============================================================
   CLIENTS
============================================================ */
function saveClient() {
  const name = document.getElementById('cli_name')?.value.trim();
  if (!name) { notify('Введите имя клиента', 'error'); return; }
  const db = getDB();
  db.clients.unshift({
    id:       Date.now(),
    name,
    type:     document.getElementById('cli_type')?.value     || '',
    phone:    document.getElementById('cli_phone')?.value    || '',
    email:    document.getElementById('cli_email')?.value    || '',
    bizcat:   document.getElementById('cli_bizcat')?.value   || '',
    address:  document.getElementById('cli_address')?.value  || '',
    inn:      document.getElementById('cli_inn')?.value      || '',
    discount: parseInt(document.getElementById('cli_discount')?.value) || 0,
    notes:    document.getElementById('cli_notes')?.value    || '',
    created:  new Date().toISOString(),
  });
  saveDB(db);
  closeModal('clientModal');
  renderClients();
  notify('Клиент добавлен: ' + name, 'success');
}

function renderClients() {
  const db     = getDB();
  const search = (document.getElementById('clientSearch')?.value || '').toLowerCase();
  let clients  = [...(db.clients || [])];
  if (search) clients = clients.filter(c =>
    (c.name  || '').toLowerCase().includes(search) ||
    (c.phone || '').includes(search) ||
    (c.email || '').toLowerCase().includes(search)
  );

  const grid = document.getElementById('clientsGrid');
  if (!grid) return;

  if (!clients.length) {
    grid.innerHTML = '<div class="empty-state card" style="grid-column:1/-1;">' +
      '<div class="icon">👥</div><div class="title">Клиентов нет</div></div>';
    return;
  }

  const orderCount = name => (db.orders || []).filter(o => o.client === name).length;
  const totalSpent = name => (db.orders || [])
    .filter(o => o.client === name && o.status === 'done')
    .reduce((a, b) => a + (b.total || 0), 0);

  grid.innerHTML = clients.map(c => `
    <div class="card">
      <div style="display:flex;gap:10px;margin-bottom:12px;">
        <div class="client-avatar">${(c.name || '?').charAt(0).toUpperCase()}</div>
        <div style="flex:1;min-width:0;">
          <div style="font-weight:700;font-size:0.9rem;overflow:hidden;
                      text-overflow:ellipsis;white-space:nowrap;">
            ${escapeHtml(c.name)}
          </div>
          <div class="text-xs text-muted">${c.type || ''}</div>
        </div>
        ${c.discount > 0 ? `
          <span style="background:rgba(245,158,11,0.2);color:var(--accent4);
                       border-radius:6px;padding:2px 6px;font-size:0.7rem;font-weight:700;">
            −${c.discount}%
          </span>` : ''}
      </div>
      ${c.phone  ? `<div class="text-sm" style="margin-bottom:4px;">📞 ${escapeHtml(c.phone)}</div>` : ''}
      ${c.email  ? `<div class="text-xs text-muted" style="margin-bottom:4px;">✉️ ${escapeHtml(c.email)}</div>` : ''}
      ${c.bizcat ? `<div class="text-xs text-muted" style="margin-bottom:8px;">🏷️ ${escapeHtml(c.bizcat)}</div>` : ''}
      <div style="display:flex;gap:10px;padding-top:10px;border-top:1px solid var(--border);">
        <div style="flex:1;text-align:center;">
          <div style="font-size:1.1rem;font-weight:800;color:var(--accent2);">
            ${orderCount(c.name)}
          </div>
          <div class="text-xs text-muted">заказов</div>
        </div>
        <div style="flex:1;text-align:center;">
          <div style="font-size:0.85rem;font-weight:700;color:var(--accent3);">
            ${formatMoney(totalSpent(c.name))}
          </div>
          <div class="text-xs text-muted">потрачено</div>
        </div>
      </div>
      <div style="display:flex;gap:6px;margin-top:10px;">
        <button class="btn btn-danger btn-xs" style="flex:1;"
                onclick="deleteClient(${c.id})">🗑️ Удалить</button>
        <button class="btn btn-primary btn-xs" style="flex:1;"
                onclick="newOrderForClient('${escapeHtml(c.name)}','${escapeHtml(c.phone || '')}')">
          + Заказ
        </button>
      </div>
    </div>
  `).join('');

  const sc = document.getElementById('statClients');
  if (sc) sc.textContent = db.clients.length;
}

function deleteClient(id) {
  if (!confirm('Удалить клиента?')) return;
  const db   = getDB();
  db.clients = db.clients.filter(c => c.id !== id);
  saveDB(db);
  renderClients();
  notify('Клиент удалён', 'error');
}

function newOrderForClient(name, phone) {
  openModal('orderModal');
  setTimeout(() => {
    const cn = document.getElementById('ord_client');
    const cp = document.getElementById('ord_phone');
    if (cn) cn.value = name;
    if (cp) cp.value = phone;
  }, 100);
}

/* ============================================================
   NOTES
============================================================ */
function saveNote() {
  const title = document.getElementById('note_title')?.value.trim() || '';
  const body  = document.getElementById('note_body')?.value.trim()  || '';
  if (!title && !body) { notify('Введите текст заметки', 'error'); return; }
  const db = getDB();
  db.notes.unshift({
    id:       Date.now(),
    title:    title || 'Без заголовка',
    body,
    priority: document.getElementById('note_priority')?.value || 'normal',
    shift:    document.getElementById('note_shift')?.value    || '',
    created:  new Date().toISOString(),
  });
  saveDB(db);
  closeModal('noteModal');
  renderNotes();
  updateNotesBadge();
  notify('Заметка сохранена', 'success');
}

function renderNotes() {
  const db   = getDB();
  const grid = document.getElementById('notesGrid');
  if (!grid) return;

  if (!(db.notes || []).length) {
    grid.innerHTML = '<div class="empty-state card" style="grid-column:1/-1;">' +
      '<div class="icon">📝</div><div class="title">Заметок нет</div></div>';
    return;
  }

  const labels = {
    normal:    'Обычная',
    info:      'Информация',
    important: '⚠️ Важная',
    urgent:    '🚨 Срочно!'
  };
  const colors = {
    normal:    'var(--text-muted)',
    info:      'var(--accent2)',
    important: 'var(--accent4)',
    urgent:    'var(--danger)'
  };

  grid.innerHTML = db.notes.map(n => `
    <div class="note-card ${n.priority}">
      <div style="display:flex;justify-content:space-between;
                  align-items:flex-start;gap:8px;margin-bottom:6px;">
        <div class="note-title">${escapeHtml(n.title)}</div>
        <span style="font-size:0.68rem;padding:2px 6px;border-radius:4px;
                     background:var(--bg-dark);
                     color:${colors[n.priority] || 'var(--text-muted)'};font-weight:700;">
          ${labels[n.priority] || n.priority}
        </span>
      </div>
      <div class="note-body">${escapeHtml(n.body || '')}</div>
      <div class="note-meta">
        <span>🕐 ${formatDate(n.created)}</span>
        ${n.shift ? `<span>👤 ${escapeHtml(n.shift)}</span>` : ''}
        <button class="btn btn-danger btn-xs" style="margin-left:auto;"
                onclick="deleteNote(${n.id})">🗑️</button>
      </div>
    </div>
  `).join('');
}

function deleteNote(id) {
  if (!confirm('Удалить заметку?')) return;
  const db = getDB();
  db.notes = db.notes.filter(n => n.id !== id);
  saveDB(db);
  renderNotes();
  updateNotesBadge();
}

function updateNotesBadge() {
  const db     = getDB();
  const urgent = (db.notes || []).filter(n =>
    n.priority === 'urgent' || n.priority === 'important'
  ).length;
  const badge = document.getElementById('notesNavBadge');
  if (badge) badge.style.display = urgent > 0 ? '' : 'none';
}

/* ============================================================
   DASHBOARD
============================================================ */
function refreshDashboard() {
  const db    = getDB();
  const now   = new Date();
  const today = now.toDateString();

  const monthFilter = arr => (arr || []).filter(i => {
    const d = new Date(i.date);
    return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
  });
  const todayFilter = arr => (arr || []).filter(i =>
    new Date(i.date).toDateString() === today
  );

  const ordersToday  = (db.orders || []).filter(o =>
    new Date(o.date).toDateString() === today
  ).length;
  const finIncome    = (db.finance || []).filter(f => f.type === 'income');
  const finExpense   = (db.finance || []).filter(f => f.type === 'expense');
  const incomeMonth  = monthFilter(finIncome).reduce((a, b) => a + (b.amount || 0), 0);
  const expenseMonth = monthFilter(finExpense).reduce((a, b) => a + (b.amount || 0), 0);
  const incomeToday  = todayFilter(finIncome).reduce((a, b) => a + (b.amount || 0), 0);
  const expenseToday = todayFilter(finExpense).reduce((a, b) => a + (b.amount || 0), 0);
  const profit       = incomeMonth - expenseMonth;

  const s = (id, v) => { const e = document.getElementById(id); if (e) e.textContent = v; };
  s('kpiOrdersToday',  ordersToday);
  s('kpiIncomeMonth',  formatMoney(incomeMonth));
  s('kpiExpenseMonth', formatMoney(expenseMonth));
  s('kpiProfitMonth',  formatMoney(profit));
  s('kpiIncomeToday',  'сегодня: ' + formatMoney(incomeToday));
  s('kpiExpenseToday', 'сегодня: ' + formatMoney(expenseToday));
  s('kpiProfitStatus', profit >= 0 ? '📈 Прибыльно' : '📉 Убыток');

  const ro = document.getElementById('dashRecentOrders');
  if (ro) {
    const recent = (db.orders || []).slice(0, 5);
    ro.innerHTML = recent.length
      ? '<div style="display:flex;flex-direction:column;gap:6px;">' +
        recent.map(o => `
          <div style="display:flex;align-items:center;gap:10px;padding:8px;
                      background:var(--bg-dark);border-radius:8px;cursor:pointer;"
               onclick="openOrderDetail(null,${o.id})">
            <div style="min-width:80px;font-weight:700;font-size:0.78rem;color:var(--accent2);">${o.num}</div>
            <div style="flex:1;min-width:0;">
              <div style="font-size:0.82rem;font-weight:600;overflow:hidden;
                          text-overflow:ellipsis;white-space:nowrap;">
                ${escapeHtml(o.client)}
              </div>
              <div style="font-size:0.7rem;color:var(--text-muted);">${o.serviceLabel || ''}</div>
            </div>
            <div style="font-weight:700;color:var(--accent3);font-size:0.82rem;">${formatMoney(o.total)}</div>
            ${getStatusBadge(o.status)}
          </div>
        `).join('') + '</div>'
      : '<div class="empty-state"><div class="icon">📋</div>' +
        '<div class="title">Заказов пока нет</div></div>';
  }

  updateOrdersBadge();
  updateNotesBadge();
  updateDBSize();
  refreshDashboardExtended();
}

/* ============================================================
   DASHBOARD EXTENDED
============================================================ */
function refreshDashboardExtended() {
  const db       = getDB();
  const now      = new Date();
  const todayStr = now.toDateString();
  const cur      = db.settings?.currency || '₽';

  const days   = ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'];
  const months = ['января','февраля','марта','апреля','мая','июня',
                  'июля','августа','сентября','октября','ноября','декабря'];
  const dateEl = document.getElementById('dashTodayDate');
  if (dateEl) dateEl.textContent =
    `${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]}`;

  const todayFin = (db.finance || []).filter(f =>
    new Date(f.date).toDateString() === todayStr
  );
  const todayInc = todayFin.filter(f => f.type === 'income');
  const todayExp = todayFin.filter(f => f.type === 'expense');
  const sumInc   = todayInc.reduce((a, b) => a + (b.amount || 0), 0);
  const sumExp   = todayExp.reduce((a, b) => a + (b.amount || 0), 0);
  const profit   = sumInc - sumExp;

  const todayOrders = (db.orders || []).filter(o =>
    new Date(o.date).toDateString() === todayStr
  );

  const s = (id, v) => { const e = document.getElementById(id); if (e) e.textContent = v; };
  s('dashTodayIncome',  formatMoney(sumInc, cur));
  s('dashTodayExpense', formatMoney(sumExp, cur));
  s('dashTodayProfit',  formatMoney(profit, cur));

  const barEl   = document.getElementById('dashTodayBar');
  const ratioEl = document.getElementById('dashTodayRatio');
  if (barEl && ratioEl) {
    const total = sumInc + sumExp;
    const pct   = total > 0 ? Math.round((sumInc / total) * 100) : 0;
    barEl.style.width = pct + '%';
    barEl.style.background = pct >= 60
      ? 'linear-gradient(to right,var(--accent3),var(--accent2))'
      : pct >= 40
        ? 'linear-gradient(to right,var(--accent4),var(--accent2))'
        : 'linear-gradient(to right,var(--danger),var(--accent4))';
    ratioEl.textContent = total > 0 ? `${pct}% доход` : '—';
  }

  const rfEl = document.getElementById('dashRecentFinance');
  if (rfEl) {
    const last4 = [...(db.finance || [])].slice(0, 4);
    rfEl.innerHTML = last4.length
      ? last4.map(f => `
          <div style="display:flex;align-items:center;gap:8px;padding:5px 8px;
                      background:var(--bg-dark);border-radius:7px;">
            <span style="font-size:0.85rem;">${f.type === 'income' ? '💚' : '🔴'}</span>
            <div style="flex:1;min-width:0;">
              <div style="font-size:0.75rem;font-weight:600;overflow:hidden;
                          text-overflow:ellipsis;white-space:nowrap;">
                ${escapeHtml(f.category || f.desc || '—')}
              </div>
              <div style="font-size:0.65rem;color:var(--text-muted);">${formatDate(f.date)}</div>
            </div>
            <div style="font-size:0.8rem;font-weight:700;white-space:nowrap;
                        color:${f.type === 'income' ? 'var(--accent3)' : 'var(--danger)'};">
              ${f.type === 'income' ? '+' : '−'}${formatMoney(f.amount, cur)}
            </div>
          </div>
        `).join('')
      : '<div style="text-align:center;padding:12px;color:var(--text-muted);font-size:0.78rem;">Операций пока нет</div>';
  }

  const hourlyEl = document.getElementById('dashHourlyChart');
  if (hourlyEl) {
    const hours = Array(24).fill(0);
    todayInc.forEach(f => {
      const h = new Date(f.date).getHours();
      hours[h] += f.amount || 0;
    });
    const STEP = 2;
    const bars = [];
    for (let h = 0; h < 24; h += STEP) {
      bars.push({ h, val: hours[h] + (hours[h + 1] || 0) });
    }
    const maxBar = Math.max(...bars.map(b => b.val), 1);
    const nowH   = now.getHours();

    hourlyEl.innerHTML = bars.map(({ h, val }) => {
      const pct     = Math.max(4, Math.round((val / maxBar) * 100));
      const isNow   = h <= nowH && nowH < h + STEP;
      const hasData = val > 0;
      return `
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;cursor:default;"
             title="${h}:00–${h + STEP}:00 • ${formatMoney(val, cur)}">
          ${hasData
            ? `<div style="font-size:0.55rem;color:var(--accent3);font-weight:700;line-height:1;">
                ${val >= 1000 ? Math.round(val / 1000) + 'к' : Math.round(val)}
               </div>`
            : '<div style="height:10px;"></div>'}
          <div style="width:100%;border-radius:3px 3px 0 0;height:${pct}%;min-height:3px;
                      transition:height 0.5s;opacity:${hasData ? '1' : '0.3'};
                      background:${isNow
                        ? 'linear-gradient(to top,var(--accent),var(--accent2))'
                        : hasData
                          ? 'linear-gradient(to top,var(--accent3),rgba(16,185,129,0.5))'
                          : 'var(--border)'};">
          </div>
        </div>`;
    }).join('');
  }

  const topEl = document.getElementById('dashTopIncome');
  if (topEl) {
    const cats   = {};
    todayInc.forEach(f => {
      cats[f.category || 'Прочее'] = (cats[f.category || 'Прочее'] || 0) + (f.amount || 0);
    });
    const sorted = Object.entries(cats).sort((a, b) => b[1] - a[1]).slice(0, 5);
    const maxV   = sorted[0]?.[1] || 1;
    const colors = ['#10b981','#06b6d4','#7c3aed','#f59e0b','#ef4444'];

    topEl.innerHTML = sorted.length
      ? sorted.map(([cat, val], i) => `
          <div>
            <div style="display:flex;justify-content:space-between;font-size:0.72rem;margin-bottom:3px;">
              <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:120px;">${escapeHtml(cat)}</span>
              <span style="font-weight:700;color:${colors[i % colors.length]};white-space:nowrap;">${formatMoney(val, cur)}</span>
            </div>
            <div style="height:4px;background:var(--border);border-radius:2px;overflow:hidden;">
              <div style="height:100%;width:${Math.round((val / maxV) * 100)}%;
                          background:${colors[i % colors.length]};border-radius:2px;transition:width 0.6s;"></div>
            </div>
          </div>
        `).join('')
      : '<div style="color:var(--text-muted);font-size:0.72rem;text-align:center;padding:16px 0;">Нет доходов сегодня</div>';
  }

  const emojiEl = document.getElementById('dashDayEmoji');
  const labelEl = document.getElementById('dashDayLabel');
  if (emojiEl && labelEl) {
    if (sumInc === 0 && sumExp === 0) {
      emojiEl.textContent = '😴'; labelEl.textContent = 'День не начат';
      labelEl.style.color = 'var(--text-muted)';
    } else if (profit > 5000) {
      emojiEl.textContent = '🤑'; labelEl.textContent = 'Отличный день!';
      labelEl.style.color = 'var(--accent3)';
    } else if (profit > 1000) {
      emojiEl.textContent = '😊'; labelEl.textContent = 'Хороший день';
      labelEl.style.color = 'var(--accent3)';
    } else if (profit > 0) {
      emojiEl.textContent = '🙂'; labelEl.textContent = 'Небольшой плюс';
      labelEl.style.color = 'var(--accent2)';
    } else if (profit === 0 && sumInc > 0) {
      emojiEl.textContent = '😐'; labelEl.textContent = 'В ноль';
      labelEl.style.color = 'var(--accent4)';
    } else {
      emojiEl.textContent = '😟'; labelEl.textContent = 'Расходы > доходов';
      labelEl.style.color = 'var(--danger)';
    }
  }

  const avgCheck = todayOrders.length
    ? Math.round(todayOrders.reduce((a, b) => a + (b.total || 0), 0) / todayOrders.length)
    : 0;
  s('dashTodayOpsCount',    todayFin.length);
  s('dashTodayOrdersCount', todayOrders.length);
  s('dashTodayAvgCheck',    formatMoney(avgCheck, cur));
}

/* ============================================================
   STATS
============================================================ */
function renderStats() {
  const db     = getDB();
  const period = document.getElementById('statsPeriod')?.value || 'month';
  const now    = new Date();
  let orders   = db.orders || [];

  if (period === 'month') orders = orders.filter(o => {
    const d = new Date(o.date);
    return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
  });
  if (period === 'week') {
    const weekAgo = new Date(now - 7 * 86400000);
    orders = orders.filter(o => new Date(o.date) >= weekAgo);
  }

  const s = (id, v) => { const e = document.getElementById(id); if (e) e.textContent = v; };
  s('statTotalOrders', orders.length);
  s('statDoneOrders',  orders.filter(o => o.status === 'done').length);
  s('statClients',     (db.clients || []).length);
  s('statAvgCheck',    formatMoney(
    orders.length
      ? Math.round(orders.reduce((a, b) => a + (b.total || 0), 0) / orders.length)
      : 0
  ));

  const byService = {};
  orders.forEach(o => {
    byService[o.serviceLabel || 'Прочее'] =
      (byService[o.serviceLabel || 'Прочее'] || 0) + 1;
  });
  renderBarChart('statsByService', byService, '#7c3aed');

  const byBiz = {};
  orders.forEach(o => {
    const k = o.bizcat || 'Не указано';
    byBiz[k] = (byBiz[k] || 0) + 1;
  });
  renderBarChart('statsByCategory', byBiz, '#06b6d4');
  renderServiceBars(orders);
}

function renderBarChart(containerId, data, color) {
  const el = document.getElementById(containerId);
  if (!el) return;
  const entries = Object.entries(data).sort((a, b) => b[1] - a[1]).slice(0, 8);
  if (!entries.length) {
    el.innerHTML = '<div class="text-muted text-sm" style="padding:16px;">Нет данных</div>';
    return;
  }
  const max = Math.max(...entries.map(e => e[1]));
  el.innerHTML = entries.map(([k, v]) => `
    <div style="margin-bottom:10px;">
      <div style="display:flex;justify-content:space-between;font-size:0.78rem;margin-bottom:4px;">
        <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:200px;">${escapeHtml(k)}</span>
        <span style="font-weight:700;color:${color};">${v}</span>
      </div>
      <div class="progress-bar">
        <div class="progress-fill"
             style="width:${max ? ((v / max) * 100).toFixed(0) : 0}%;background:${color};">
        </div>
      </div>
    </div>
  `).join('');
}

function renderServiceBars(orders) {
  const el = document.getElementById('statsServiceBars');
  if (!el) return;
  const data = {};
  orders.forEach(o => {
    data[o.serviceLabel || 'Прочее'] = (data[o.serviceLabel || 'Прочее'] || 0) + 1;
  });
  const entries = Object.entries(data).sort((a, b) => b[1] - a[1]).slice(0, 8);
  if (!entries.length) {
    el.innerHTML = '<div class="text-muted text-sm">Нет данных</div>';
    return;
  }
  const max    = Math.max(...entries.map(e => e[1]));
  const colors = ['#7c3aed','#06b6d4','#10b981','#f59e0b',
                  '#ef4444','#8b5cf6','#0ea5e9','#14b8a6'];
  el.innerHTML =
    '<div style="display:flex;align-items:flex-end;gap:12px;height:120px;padding-top:10px;">' +
    entries.map(([k, v], i) => `
      <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;">
        <div style="font-size:0.75rem;font-weight:700;color:${colors[i % colors.length]};">${v}</div>
        <div style="width:100%;border-radius:4px 4px 0 0;background:${colors[i % colors.length]};
                    height:${max ? Math.max(8, (v / max) * 90) : 8}px;transition:height 0.5s;"></div>
        <div style="font-size:0.62rem;color:var(--text-muted);text-align:center;max-width:60px;
                    overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
             title="${escapeHtml(k)}">
          ${escapeHtml(k).split(' ')[0]}
        </div>
      </div>
    `).join('') + '</div>';
}

/* ============================================================
   ACCOUNTING
============================================================ */
function renderAccounting() {
  const db     = getDB();
  const months = {};

  (db.finance || []).forEach(f => {
    const d   = new Date(f.date);
    const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
    if (!months[key]) months[key] = { income: 0, expense: 0 };
    if (f.type === 'income') months[key].income  += (f.amount || 0);
    else                     months[key].expense += (f.amount || 0);
  });

  const ordersByMonth = {};
  (db.orders || []).forEach(o => {
    const d   = new Date(o.date);
    const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
    ordersByMonth[key] = (ordersByMonth[key] || 0) + 1;
  });

  const MONTHS = ['Январь','Февраль','Март','Апрель','Май','Июнь',
                  'Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'];
  const tbody  = document.getElementById('accountingTable');
  if (!tbody) return;
  const arr    = Object.entries(months).sort((a, b) => b[0].localeCompare(a[0]));

  tbody.innerHTML = !arr.length
    ? '<tr><td colspan="6"><div class="empty-state">' +
      '<div class="icon">📊</div><div class="title">Нет данных</div></div></td></tr>'
    : arr.map(([k, v]) => {
        const profit = v.income - v.expense;
        const margin = v.income > 0 ? ((profit / v.income) * 100).toFixed(1) : '0';
        const [yr, mn] = k.split('-');
        return `<tr>
          <td style="font-weight:700;">${MONTHS[parseInt(mn) - 1]} ${yr}</td>
          <td style="color:var(--accent3);font-weight:700;">${formatMoney(v.income)}</td>
          <td style="color:var(--danger);font-weight:700;">${formatMoney(v.expense)}</td>
          <td style="color:${profit >= 0 ? 'var(--accent2)' : 'var(--danger)'};font-weight:700;">
            ${formatMoney(profit)}
          </td>
          <td>${ordersByMonth[k] || 0}</td>
          <td>
            <div style="display:flex;align-items:center;gap:6px;">
              <div class="progress-bar" style="flex:1;">
                <div class="progress-fill"
                     style="width:${Math.min(100, Math.max(0, parseFloat(margin)))}%;"></div>
              </div>
              <span style="font-size:0.78rem;font-weight:700;
                color:${parseFloat(margin) >= 0 ? 'var(--accent3)' : 'var(--danger)'};">
                ${margin}%
              </span>
            </div>
          </td>
        </tr>`;
      }).join('');

  const expByCat = {};
  (db.finance || []).filter(f => f.type === 'expense').forEach(f => {
    expByCat[f.category || 'Прочее'] = (expByCat[f.category || 'Прочее'] || 0) + (f.amount || 0);
  });
  renderBarChart('expenseByCategory', expByCat, '#ef4444');

  const incByCat = {};
  (db.finance || []).filter(f => f.type === 'income').forEach(f => {
    incByCat[f.category || 'Прочее'] = (incByCat[f.category || 'Прочее'] || 0) + (f.amount || 0);
  });
  renderBarChart('incomeByCategory', incByCat, '#10b981');
}

/* ============================================================
   SETTINGS
============================================================ */
function loadSettings() {
  const s    = getDB().settings || {};
  const flds = {
    setCompany:'company',           setInn:'inn',
    setOgrn:'ogrn',                 setAddress:'address',
    setPhone:'phone',               setEmail:'email',
    setWebsite:'website',           setBankAcc:'bankAcc',
    setBik:'bik',                   setBankName:'bankName',
    setKorAcc:'korAcc',             setKpp:'kpp',
    setReceiptHeader:'receiptHeader', setReceiptFooter:'receiptFooter',
    setSignatory:'signatory',       setSignatoryTitle:'signatoryTitle',
    setVat:'vat',                   setCurrency:'currency',
    setApiKey:'apiKey',             setApiModel:'apiModel',
  };
  Object.entries(flds).forEach(([id, key]) => {
    const el = document.getElementById(id);
    if (el) el.value = s[key] || '';
  });
  const tgT = document.getElementById('set_tgToken');
  const tgB = document.getElementById('set_tgBossId');
  if (tgT) tgT.value = s.tgToken  || '';
  if (tgB) tgB.value = s.tgBossId || '';
  renderModulesGrid();
}

function saveSettings() {
  const db   = getDB();
  const flds = {
    setCompany:'company',           setInn:'inn',
    setOgrn:'ogrn',                 setAddress:'address',
    setPhone:'phone',               setEmail:'email',
    setWebsite:'website',           setBankAcc:'bankAcc',
    setBik:'bik',                   setBankName:'bankName',
    setKorAcc:'korAcc',             setKpp:'kpp',
    setReceiptHeader:'receiptHeader', setReceiptFooter:'receiptFooter',
    setSignatory:'signatory',       setSignatoryTitle:'signatoryTitle',
    setVat:'vat',                   setCurrency:'currency',
    setApiKey:'apiKey',             setApiModel:'apiModel',
  };
  Object.entries(flds).forEach(([id, key]) => {
    const el = document.getElementById(id);
    if (el) db.settings[key] = el.value;
  });
  const tgT = document.getElementById('set_tgToken');
  const tgB = document.getElementById('set_tgBossId');
  if (tgT) db.settings.tgToken  = tgT.value;
  if (tgB) db.settings.tgBossId = tgB.value;
  saveDB(db);
  notify('Настройки сохранены!', 'success');
}

function renderModulesGrid() {
  const grid = document.getElementById('modulesGrid');
  if (!grid) return;
  const mods = Object.values(CRM._modules);
  if (!mods.length) {
    grid.innerHTML = '<div class="text-muted text-sm">Нет подключённых модулей</div>';
    return;
  }
  grid.innerHTML = mods.map(m => `
    <div class="card" style="display:flex;align-items:center;gap:12px;padding:12px;">
      <span style="font-size:1.5rem;">${m.icon}</span>
      <div style="flex:1;">
        <div style="font-weight:700;">${m.name}</div>
        <div class="text-xs text-muted">${m.id}</div>
      </div>
      <span style="width:10px;height:10px;border-radius:50%;background:var(--accent3);
                   flex-shrink:0;" title="Активен"></span>
    </div>
  `).join('');
}

async function testApiKey() {
  const key = document.getElementById('setApiKey')?.value;
  if (!key) { notify('Введите API ключ', 'error'); return; }
  notify('Проверяю ключ...', 'info');
  try {
    const res = await callDeepSeekAPI('Ответь одним словом: готов.', key,
      document.getElementById('setApiModel')?.value);
    if (res) notify('API ключ работает! ✅', 'success');
  } catch (e) { notify('Ошибка: ' + e.message, 'error'); }
}

/* ============================================================
   PRINT
============================================================ */
function printOrderForm(forWhom) {
  const db      = getDB();
  const s       = db.settings || {};
  const num     = document.getElementById('ord_num')?.value     || '';
  const client  = document.getElementById('ord_client')?.value  || 'Без имени';
  const phone   = document.getElementById('ord_phone')?.value   || '';
  const manager = document.getElementById('ord_manager')?.value || '';
  const date    = document.getElementById('ord_date')?.value    || '';
  const deadline= document.getElementById('ord_deadline')?.value|| '';
  const total   = document.getElementById('ord_total')?.value   || '0';
  const prepay  = document.getElementById('ord_prepay')?.value  || '0';
  const comment = document.getElementById('ord_comment')?.value || '';
  const payment = document.getElementById('ord_payment')?.value || '';
  const service = getServiceLabel(currentServiceTab);
  const selSize = document.querySelector('.size-btn.selected');
  const size    = selSize ? selSize.textContent.trim() : '';
  const isMan   = forWhom === 'manager';
  const remain  = (parseFloat(total) - parseFloat(prepay)).toFixed(0);

  const checkedItems = [];
  document.querySelector('.service-tab-content.active')
    ?.querySelectorAll('.checkbox-item.checked')
    .forEach(c => checkedItems.push(c.textContent.trim().replace('✓', '').trim()));

  const html = `<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
    body{font-family:Arial,sans-serif;font-size:12px;color:#000;padding:20px;}
    .wrap{max-width:800px;margin:0 auto;}
    .hdr{border-bottom:3px solid #333;padding-bottom:12px;margin-bottom:16px;
         display:flex;justify-content:space-between;}
    .co{font-size:20px;font-weight:bold;}
    .det{font-size:10px;color:#555;line-height:1.6;}
    .ord{font-size:18px;font-weight:bold;text-align:center;margin:12px 0;
         background:#f0f0f0;padding:8px;border-radius:4px;}
    table{width:100%;border-collapse:collapse;margin-bottom:12px;}
    td,th{border:1px solid #ccc;padding:6px 10px;font-size:11px;}
    th{background:#eee;font-weight:bold;text-align:left;}
    .total{font-size:16px;font-weight:bold;background:#e8f5e9;}
    .ftr{border-top:1px solid #ccc;margin-top:20px;padding-top:12px;
         font-size:10px;color:#555;}
    .sigs{display:flex;justify-content:space-between;margin-top:30px;}
    .sig{text-align:center;width:200px;border-top:1px solid #333;
         padding-top:4px;font-size:10px;}
    .chip{display:inline-block;border:1px solid #333;border-radius:3px;
          padding:2px 6px;margin:2px;font-size:10px;}
  </style></head><body><div class="wrap">
  <div class="hdr">
    <div>
      <div class="co">${escapeHtml(s.company || 'Фотокопицентр')}</div>
      <div class="det">
        ${escapeHtml(s.address || '')}
        <br>${s.phone ? 'Тел: ' + escapeHtml(s.phone) : ''}
        ${s.email ? ' • ' + escapeHtml(s.email) : ''}
        <br>${s.inn ? 'ИНН: ' + escapeHtml(s.inn) : ''}
        ${s.ogrn ? ' • ОГРН: ' + escapeHtml(s.ogrn) : ''}
      </div>
    </div>
    <div style="text-align:right;font-size:10px;color:#555;">
      <b>${isMan ? 'БЛАНК МЕНЕДЖЕРА' : 'КВИТАНЦИЯ КЛИЕНТА'}</b>
      <br>${new Date().toLocaleString('ru')}
    </div>
  </div>
  <div class="ord">ЗАКАЗ № ${escapeHtml(num)}</div>
  <table>
    <tr>
      <th width="140">Дата приёма</th><td>${formatDate(date)}</td>
      <th width="140">Срок выдачи</th><td>${deadline ? formatDate(deadline) : '—'}</td>
    </tr>
    <tr>
      <th>Клиент</th><td>${escapeHtml(client)}</td>
      <th>Телефон</th><td>${escapeHtml(phone)}</td>
    </tr>
    <tr>
      <th>Вид услуги</th><td>${escapeHtml(service)}</td>
      <th>Формат/Размер</th><td>${escapeHtml(size) || '—'}</td>
    </tr>
    <tr>
      <th>Менеджер</th><td>${escapeHtml(manager)}</td>
      <th>Оплата</th><td>${escapeHtml(payment)}</td>
    </tr>
  </table>
  ${checkedItems.length
    ? `<div style="margin-bottom:12px;"><b>Параметры:</b><br>
       ${checkedItems.map(i => `<span class="chip">✓ ${escapeHtml(i)}</span>`).join(' ')}</div>`
    : ''}
  ${comment
    ? `<div style="margin-bottom:12px;border:1px solid #ccc;padding:8px;border-radius:4px;">
         <b>Комментарий:</b> ${escapeHtml(comment)}
       </div>`
    : ''}
  <table>
    <tr class="total">
      <td colspan="2" style="text-align:right;"><b>ИТОГО:</b></td>
      <td style="font-size:18px;font-weight:bold;">
        ${formatMoney(parseFloat(total))} ${s.currency || '₽'}
      </td>
    </tr>
    ${parseFloat(prepay) > 0 ? `
      <tr>
        <td colspan="2" style="text-align:right;">Предоплата:</td>
        <td>${formatMoney(parseFloat(prepay))} ${s.currency || '₽'}</td>
      </tr>
      <tr>
        <td colspan="2" style="text-align:right;"><b>Остаток:</b></td>
        <td style="font-weight:bold;">${formatMoney(parseFloat(remain))} ${s.currency || '₽'}</td>
      </tr>
    ` : ''}
  </table>
  <div class="ftr">
    ${escapeHtml(s.receiptHeader || '')}
    <div class="sigs">
      <div class="sig">${escapeHtml(s.signatoryTitle || 'Менеджер')}: ${escapeHtml(s.signatory || '_______________')}</div>
      <div class="sig">Клиент: ${escapeHtml(client)}</div>
    </div>
    <div style="margin-top:12px;">${escapeHtml(s.receiptFooter || '')}</div>
  </div>
  </div><script>window.onload=()=>window.print();<\/script></body></html>`;

  const win = window.open('', '_blank');
  if (win) { win.document.write(html); win.document.close(); }
}

function printSingleOrder(id) {
  const db = getDB();
  const o  = db.orders.find(x => x.id === id || String(x.id) === String(id));
  if (!o) return;
  const s  = db.settings || {};

  const html = `<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
    body{font-family:Arial,sans-serif;font-size:12px;padding:20px;}
    .t{font-size:18px;font-weight:bold;margin-bottom:16px;text-align:center;
       border-bottom:2px solid #333;padding-bottom:8px;}
    table{width:100%;border-collapse:collapse;margin-bottom:12px;}
    td,th{border:1px solid #ccc;padding:6px 10px;}
    th{background:#eee;font-weight:bold;}
    .tot{font-size:16px;font-weight:bold;background:#e8f5e9;}
    .sigs{display:flex;justify-content:space-between;margin-top:30px;}
    .sig{border-top:1px solid #000;min-width:150px;padding-top:4px;
         text-align:center;font-size:11px;}
  </style></head><body>
  <div class="t">${escapeHtml(s.company || 'Фотокопицентр')} — Заказ ${escapeHtml(o.num)}</div>
  <table>
    <tr>
      <th>Клиент</th><td>${escapeHtml(o.client)}</td>
      <th>Телефон</th><td>${escapeHtml(o.phone || '—')}</td>
    </tr>
    <tr>
      <th>Дата</th><td>${formatDate(o.date)}</td>
      <th>Срок</th><td>${o.deadline ? formatDate(o.deadline) : '—'}</td>
    </tr>
    <tr>
      <th>Услуга</th><td>${escapeHtml(o.serviceLabel)}</td>
      <th>Размер</th><td>${escapeHtml(o.size || '—')}</td>
    </tr>
    <tr>
      <th>Параметры</th>
      <td colspan="3">${escapeHtml((o.checkedItems || []).join(', ') || '—')}</td>
    </tr>
    <tr>
      <th>Статус</th><td>${escapeHtml(o.status)}</td>
      <th>Оплата</th><td>${escapeHtml(o.payment)}</td>
    </tr>
    <tr>
      <th>Комментарий</th><td colspan="3">${escapeHtml(o.comment || '—')}</td>
    </tr>
    <tr class="tot">
      <td colspan="2"><b>ИТОГО:</b></td>
      <td colspan="2" style="font-size:16px;">${formatMoney(o.total)} ${s.currency || '₽'}</td>
    </tr>
  </table>
  <p>${escapeHtml(s.receiptFooter || '')}</p>
  <div class="sigs">
    <div class="sig">Менеджер: ${escapeHtml(o.manager || '')}</div>
    <div class="sig">Подпись клиента</div>
  </div>
  <script>window.onload=()=>window.print();<\/script></body></html>`;

  const win = window.open('', '_blank');
  if (win) { win.document.write(html); win.document.close(); }
}

/* ============================================================
   AI CHAT
============================================================ */
let chatContext = [];

const SYSTEM_PROMPT =
  `Ты Валера — эксперт-гений в типографии, фотокопицентре, экономике и полиграфическом бизнесе.
Помогаешь менеджеру на смене. Характер: весёлый, с юмором, но профессионал.
Знаешь всё о форматах, ценообразовании, материалах, технологиях.
Анализируешь данные о заказах и финансах. Отвечай развёрнуто. Язык — русский.
Данные системы:`;

async function callDeepSeekAPI(message, apiKey, model) {
  const db  = getDB();
  const key = apiKey || db.settings?.apiKey;
  const mdl = model  || db.settings?.apiModel || 'deepseek-chat';
  if (!key) throw new Error('Не указан API ключ. Перейдите в Настройки → DeepSeek API');

  const now  = new Date();
  const mfin = (db.finance || []).filter(f => {
    const d = new Date(f.date);
    return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
  });
  const incM = mfin.filter(f => f.type === 'income')
    .reduce((a, b) => a + (b.amount || 0), 0);
  const expM = mfin.filter(f => f.type === 'expense')
    .reduce((a, b) => a + (b.amount || 0), 0);

  const sysMsg = `${SYSTEM_PROMPT}
Дата: ${now.toLocaleDateString('ru')}.
Заказов: ${(db.orders || []).length}, активных: ${
  (db.orders || []).filter(o => o.status === 'new' || o.status === 'work').length
}.
Доходы/месяц: ${incM}₽, расходы: ${expM}₽, прибыль: ${incM - expM}₽.
Клиентов: ${(db.clients || []).length}.
Компания: ${db.settings?.company || 'не указана'}.`;

  const messages = [
    { role: 'system',    content: sysMsg },
    ...chatContext.slice(-20),
    { role: 'user',      content: message },
  ];

  const res = await fetch('https://api.deepseek.com/chat/completions', {
    method:  'POST',
    headers: {
      'Content-Type':  'application/json',
      'Authorization': `Bearer ${key}`
    },
    body: JSON.stringify({ model: mdl, messages, max_tokens: 2048, temperature: 0.8 }),
  });

  if (!res.ok) {
    const err = await res.json().catch(() => ({}));
    throw new Error(err.error?.message || `HTTP ${res.status}`);
  }
  const data = await res.json();
  return data.choices[0].message.content;
}

function appendChatMsg(role, text) {
  const container = document.getElementById('chatMessages');
  if (!container) return;
  const now  = new Date();
  const time = `${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')}`;
  const div  = document.createElement('div');
  div.className = `chat-msg ${role}`;
  const fmt  = t => t
    .replace(/\n/g, '<br>')
    .replace(/\*\*(.*?)\*\*/g, '<b>$1</b>')
    .replace(/\*(.*?)\*/g, '<i>$1</i>');
  div.innerHTML = role === 'ai'
    ? `<div class="chat-avatar">🤖</div>
       <div>
         <div class="chat-bubble">${fmt(text)}</div>
         <div class="chat-time">${time}</div>
       </div>`
    : `<div>
         <div class="chat-bubble">${fmt(text)}</div>
         <div class="chat-time" style="text-align:right;">${time}</div>
       </div>
       <div class="chat-avatar">👤</div>`;
  container.appendChild(div);
  container.scrollTop = container.scrollHeight;
}

function showTyping() {
  const container = document.getElementById('chatMessages');
  if (!container) return;
  const div = document.createElement('div');
  div.className = 'chat-msg ai';
  div.id = 'typingIndicator';
  div.innerHTML =
    `<div class="chat-avatar">🤖</div>
     <div class="chat-bubble" style="padding:14px 18px;">
       <div class="typing-indicator">
         <div class="typing-dot"></div>
         <div class="typing-dot"></div>
         <div class="typing-dot"></div>
       </div>
     </div>`;
  container.appendChild(div);
  container.scrollTop = container.scrollHeight;
}

function hideTyping() {
  document.getElementById('typingIndicator')?.remove();
}

async function sendChatMessage() {
  const input = document.getElementById('chatInput');
  const text  = input?.value.trim();
  if (!text) return;
  input.value = '';
  if (input.style) input.style.height = 'auto';
  appendChatMsg('user', text);
  chatContext.push({ role: 'user', content: text });
  showTyping();
  try {
    const reply = await callDeepSeekAPI(text);
    hideTyping();
    appendChatMsg('ai', reply);
    chatContext.push({ role: 'assistant', content: reply });
    const db = getDB();
    db.chatHistory = chatContext.slice(-50);
    saveDB(db);
  } catch (e) {
    hideTyping();
    appendChatMsg('ai', `⚠️ Ошибка: ${e.message}\n\nПроверьте API ключ в Настройках.`);
  }
}

function sendQuickChat(text) {
  const input = document.getElementById('chatInput');
  if (input) input.value = text;
  sendChatMessage();
}

/* ============================================================
   INIT
============================================================ */
async function init() {
  clearOldLocalStorage();
  showSyncStatus('loading');
  const serverOk = await loadFromServer();

  if (serverOk) {
    console.log('✅ Загружено с сервера — orders:',
      getDB().orders?.length, 'finance:', getDB().finance?.length);
    refreshDashboard();
    updateOrdersBadge();
    updateNotesBadge();
    updateDBSize();
  } else {
    dbCache  = initDBStructure();
    isLoaded = false;
    notify('❌ Сервер недоступен. Данные не загружены.', 'error');
    refreshDashboard();
  }

  const db = getDB();
  if (db.chatHistory?.length) {
    chatContext = db.chatHistory;
    const container = document.getElementById('chatMessages');
    if (container) {
      container.innerHTML = '';
      chatContext.forEach(m => {
        if (m.role === 'user' || m.role === 'assistant')
          appendChatMsg(m.role === 'assistant' ? 'ai' : 'user', m.content);
      });
    }
  } else {
    setTimeout(() => appendChatMsg('ai',
      `🎉 Привет! Я **Валера** — ваш эксперт по типографии!\n\n` +
      `Знаю всё о форматах, материалах, ценообразовании 😄\n\n` +
      `Добавьте API ключ DeepSeek в **Настройках** и начнём!`
    ), 400);
  }

  const fileInput = document.getElementById('order_files_input');
  if (fileInput) {
    fileInput.onchange = e => {
      const files = Array.from(e.target.files);
      handleOrderFiles(files);
    };
  }

  const modCount = Object.keys(window.CRM?._modules || {}).length;
  console.log(
    `✅ Система запущена | Модулей: ${modCount} | Сервер: ${serverOk ? 'OK' : 'недоступен'}`
  );
}

init();