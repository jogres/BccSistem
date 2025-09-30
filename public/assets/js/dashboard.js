// public/assets/js/dashboard.js
// Dashboard (Chart.js v3+) — barras com cor fixa por funcionário, sem “desmarcar” a seleção

document.addEventListener('DOMContentLoaded', function () {
  // --- referêcias de DOM ---
  const form      = document.querySelector('#filters-form');
  const modeSel   = document.querySelector('#mode');
  const groupBy   = document.querySelector('#groupBy');

  const week      = document.querySelector('#range-week');
  const month     = document.querySelector('#range-month');
  const day       = document.querySelector('#range-day');

  const toggler   = document.querySelector('#toggle-compare');           // checkbox “Comparar usuários”
  const multi     = document.querySelector('#multi-users');              // container do multiselect
  const usersSel  = document.querySelector('select[name="users[]"]');    // <select multiple>

  const errBox    = document.querySelector('#dashboard-error');
  const captionEl = document.querySelector('#chart-caption');
  const ctxEl     = document.getElementById('kpi-chart');
  const ctx       = ctxEl && ctxEl.getContext ? ctxEl.getContext('2d') : null;

  const monthInp  = document.querySelector('input[name="month"]');
  const isAdmin   = !!(window.APP && window.APP.isAdmin);
  const meId      = (window.APP && Number(window.APP.userId)) || null;

  // ---------- Defaults de UI ----------
  // Modo padrão = "month"
  if (modeSel && modeSel.value !== 'month') modeSel.value = 'month';

  // Garante mês atual se o input vier vazio (YYYY-MM)
  if (monthInp && !monthInp.value) {
    const d = new Date();
    monthInp.value = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2, '0')}`;
  }

  // Seleção padrão:
  // - Admin => todos selecionados
  // - Não-admin => apenas o próprio
  function preselectUsers() {
    if (!usersSel) return;
    const opts = Array.from(usersSel.options);
    if (isAdmin) {
      opts.forEach(o => (o.selected = true));
    } else if (meId != null) {
      opts.forEach(o => (o.selected = Number(o.value) === meId));
    }
  }
  preselectUsers();

  // Visualização inicial: COMPARAR ligado (para já vir por usuário)
  function applyCompareVisibility() {
    const on = !!(toggler && toggler.checked);
    if (multi) {
      // só esconde/mostra o bloco; NÃO mexe na seleção!
      multi.classList.toggle('hidden', !on);
      multi.style.display = on ? 'block' : 'none';
    }
  }
  if (toggler) toggler.checked = true; // <- ligado por padrão
  applyCompareVisibility();
  toggler?.addEventListener('change', applyCompareVisibility);

  // Alternância de campos por modo
  function syncModeFields() {
    const m = modeSel.value;
    week?.classList.toggle('hidden',  m !== 'week');
    month?.classList.toggle('hidden', m !== 'month');
    day?.classList.toggle('hidden',   m !== 'day');
  }
  syncModeFields();
  modeSel?.addEventListener('change', syncModeFields);

  // ---------- Cores estáveis por funcionário (HSL) ----------
  function colorFromUserId(uid) {
    // espalha matizes com “ângulo dourado”
    const h = ((uid % 360) + (137.508 * ((uid % 89) + 1))) % 360;
    const border = `hsl(${h} 62% 48%)`;
    const fill   = `hsl(${h} 62% 48% / 0.35)`;
    return { border, fill };
  }

  // ---------- ISO Week helpers (para tooltips) ----------
  function isoWeekStartUTC(year, week) {
    const simple = new Date(Date.UTC(year, 0, 1 + (week - 1) * 7));
    const dow = simple.getUTCDay() || 7; // 1..7 (dom=7)
    if (dow <= 4) simple.setUTCDate(simple.getUTCDate() - (dow - 1));
    else simple.setUTCDate(simple.getUTCDate() + (8 - dow));
    return simple; // segunda-feira
  }
  function isoWeekRangeStr(year, week) {
    const start = isoWeekStartUTC(year, week);
    const end   = new Date(start);
    end.setUTCDate(start.getUTCDate() + 6);
    const fmt = d => `${String(d.getUTCDate()).padStart(2,'0')}/${String(d.getUTCMonth()+1).padStart(2,'0')}/${d.getUTCFullYear()}`;
    return `${fmt(start)} – ${fmt(end)}`;
  }

  // ---------- Parsing/formatadores (rótulos “amigáveis”) ----------
  // Esperado do backend: 'YYYY-W##' | 'YYYY-MM' | 'YYYY-MM-DD'
  function parsePeriod(raw) {
    if (/^\d{4}-W(\d{1,2})$/.test(raw)) {
      const m = raw.match(/^(\d{4})-W(\d{1,2})$/);
      return { type: 'week', year: +m[1], week: +m[2] };
    }
    if (/^\d{4}-\d{2}$/.test(raw)) {
      const [y, mo] = raw.split('-').map(Number);
      return { type: 'month', year: y, month: mo };
    }
    if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
      const [y, mo, d] = raw.split('-').map(Number);
      return { type: 'day', year: y, month: mo, day: d };
    }
    return { type: 'raw', raw };
  }
  function prettyForAxis(raw, groupMode) {
    if (groupMode === 'user') return raw; // eixo mostra nomes
    const p = parsePeriod(raw);
    if (p.type === 'week')  return `${p.year}-SEMANA`;
    if (p.type === 'month') return `${p.year}-MÊS`;
    if (p.type === 'day')   return `${p.year}-DIA`;
    return raw;
  }
  function prettyForTooltipTitle(raw, groupMode) {
    const p = parsePeriod(raw);
    if (groupMode === 'user') return `Usuário: ${raw}`;
    if (p.type === 'week')  return `Semana ${p.week} (${isoWeekRangeStr(p.year, p.week)})`;
    if (p.type === 'month') return `Mês ${String(p.month).padStart(2,'0')}/${p.year}`;
    if (p.type === 'day')   return `Dia ${String(p.day).padStart(2,'0')}/${String(p.month).padStart(2,'0')}/${p.year}`;
    return raw;
  }

  // ---------- API ----------
  async function fetchData() {
    const params = new URLSearchParams(new FormData(form));
    // NUNCA apague users[] — preserva seleção e evita cair em TOTAL involuntário
    const res = await fetch('api/dashboard_counts.php?' + params.toString(), { cache: 'no-store' });
    if (!res.ok) throw new Error('Falha ao consultar a API.');
    const json = await res.json();
    if (!json.ok) throw new Error(json.error || 'Erro ao consultar a API.');
    return json; // {mode,start,end,labels,series}
  }

  // ---------- Transformação p/ barras com cor por funcionário ----------
  function transformForBar(json) {
    const { labels: periodLabels, series } = json;
    const entries = Object.entries(series || {}); // [['12',{name,data}], ...]

    const wantCompare = !!(toggler && toggler.checked);

    if (!wantCompare) {
      // TOTAL por período (soma de todos os usuários selecionados)
      const totals = (periodLabels || []).map((_, i) =>
        entries.reduce((acc, [,u]) => acc + Number(u.data[i] || 0), 0)
      );
      const c = { border: 'hsl(208 70% 45%)', fill: 'hsl(208 70% 45% / .35)' };
      return {
        labels: periodLabels,
        datasets: [{
          label: 'TOTAL',
          data: totals,
          borderColor: c.border,
          backgroundColor: c.fill,
          borderWidth: 1,
          maxBarThickness: 42,
          categoryPercentage: 0.7,
          barPercentage: 0.9
        }],
        groupBy: 'period'
      };
    }

    const group = groupBy ? (groupBy.value || 'period') : 'period';

    if (group === 'user') {
      // X = usuários; DATASETS = períodos — cada barra pintada pela cor do funcionário
      const userIds   = entries.map(([uid]) => parseInt(uid, 10));
      const userNames = entries.map(([,u])   => u.name);

      const datasets = (periodLabels || []).map((periodLabel, idx) => {
        const data = entries.map(([,u]) => Number(u.data[idx] || 0));
        const fillArr   = userIds.map(uid => colorFromUserId(uid).fill);
        const borderArr = userIds.map(uid => colorFromUserId(uid).border);

        return {
          label: prettyForTooltipTitle(periodLabel, 'period'),
          data,
          backgroundColor: fillArr,   // <- cor por BARRA = funcionário
          borderColor: borderArr,
          borderWidth: 1,
          maxBarThickness: 42,
          categoryPercentage: 0.7,
          barPercentage: 0.9
        };
      });

      return { labels: userNames, datasets, groupBy: 'user' };
    }

    // group === 'period' -> X = períodos; DATASETS = usuários (um dataset por funcionário)
    const datasets = entries.map(([uid, u]) => {
      const c = colorFromUserId(parseInt(uid, 10));
      return {
        label: u.name,
        data: u.data,
        borderColor: c.border,
        backgroundColor: c.fill,
        borderWidth: 1,
        maxBarThickness: 42,
        categoryPercentage: 0.7,
        barPercentage: 0.9
      };
    });

    return { labels: periodLabels, datasets, groupBy: 'period' };
  }

  // ---------- Render (Chart.js) ----------
  let chart;

  // Registra datalabels se carregado (v3+ exige register)
  if (typeof Chart !== 'undefined' && typeof window.ChartDataLabels !== 'undefined') {
    Chart.register(window.ChartDataLabels);
  }

  function renderChart(config, meta) {
    if (!ctx || typeof Chart === 'undefined') return;
    if (chart) chart.destroy();

    chart = new Chart(ctx, {
      type: 'bar',
      data: { labels: config.labels, datasets: config.datasets },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        elements: { bar: { borderRadius: 4 } },
        plugins: {
          legend: { position: 'top', labels: { boxWidth: 12 } },
          title: { display: false },
          datalabels: {
            anchor: 'end',
            align: 'end',
            offset: 2,
            clamp: true,
            color: getComputedStyle(document.body).color,
            formatter: v => (typeof v === 'number' ? v : '')
          },
          tooltip: {
            callbacks: {
              title: (items) => {
                if (!items?.length) return '';
                const idx = items[0].dataIndex;
                if (config.groupBy === 'user') {
                  const userName = config.labels[idx];
                  return prettyForTooltipTitle(userName, 'user');
                } else {
                  const raw = config.labels[idx];
                  return prettyForTooltipTitle(raw, 'period');
                }
              },
              label: (ctx) => {
                const v = ctx.parsed?.y ?? ctx.raw ?? 0;
                return `${ctx.dataset.label}: ${v}`;
              }
            }
          }
        },
        scales: {
          x: {
            type: 'category',
            ticks: {
              autoSkip: false,
              maxRotation: 45,
              minRotation: 0,
              // rótulo do eixo via callback
              callback: function (value, index) {
                const raw = this.getLabelForValue ? this.getLabelForValue(value)
                         : (this.chart?.data?.labels?.[index] ?? value);
                return prettyForAxis(raw, config.groupBy);
              }
            }
          },
          y: {
            beginAtZero: true,
            suggestedMax: 30,     // ajuste o teto default
            ticks: { stepSize: 10, precision: 0 }
          }
        }
      }
    });

    // caption/rodapé textual do gráfico
    if (captionEl) {
      const y = (meta.start || '').slice(0, 4) || new Date().getFullYear();
      captionEl.textContent = (meta.mode === 'week') ? `${y}-SEMANA`
                            : (meta.mode === 'month') ? `${y}-MÊS`
                            : `${y}-DIA`;
    }
  }

  // ---------- Fluxo ----------
  async function run() {
    try {
      if (errBox) { errBox.classList.add('hidden'); errBox.textContent = ''; }
      const json = await fetchData();
      const cfg  = transformForBar(json);
      renderChart(cfg, { mode: json.mode, start: json.start, end: json.end });
    } catch (e) {
      if (errBox) { errBox.classList.remove('hidden'); errBox.textContent = e.message || 'Erro inesperado.'; }
    }
  }

  // submit via JS
  form?.addEventListener('submit', e => { e.preventDefault(); run(); });

  // primeira renderização
  run();
});
