document.addEventListener('DOMContentLoaded', function () {
  const form    = document.querySelector('#filters-form');
  const modeSel = document.querySelector('#mode');
  const groupBy = document.querySelector('#groupBy');
  const week    = document.querySelector('#range-week');
  const month   = document.querySelector('#range-month');
  const day     = document.querySelector('#range-day');
  const toggler = document.querySelector('#toggle-compare');
  const multi   = document.querySelector('#multi-users');
  const errBox  = document.querySelector('#dashboard-error');
  const captionEl = document.querySelector('#chart-caption');
  const ctx     = document.getElementById('kpi-chart').getContext('2d');

  // === UI: alterna campos por modo ===
  function syncModeFields() {
    const m = modeSel.value;
    week.classList.toggle('hidden', m !== 'week');
    month.classList.toggle('hidden', m !== 'month');
    day.classList.toggle('hidden', m !== 'day');
  }
  syncModeFields();
  modeSel.addEventListener('change', syncModeFields);

  if (toggler && multi) {
    toggler.addEventListener('change', () => {
      multi.style.display = toggler.checked ? 'block' : 'none';
    });
  }

  let chart;
  // === Util: pega tokens de cor do CSS ===
  function cssVar(name) {
    return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
  }
  function colorFromSeed(i) {
    const base = [cssVar('--brand-blue'), cssVar('--brand-orange'), cssVar('--brand-green')];
    const b = base[i % base.length] || '#0b3f91';
    return { border: b, fill: b + '33' };
  }

  // === ISO week helpers (segunda->domingo) ===
  // Fonte do algoritmo (sem libs): SO / referência ISO week date
  // https://stackoverflow.com/q/16590500  https://en.wikipedia.org/wiki/ISO_week_date
  function isoWeekStartUTC(year, week) {
    const simple = new Date(Date.UTC(year, 0, 1 + (week - 1) * 7));
    const dow = simple.getUTCDay() || 7; // 1..7 (dom=7)
    if (dow <= 4) {
      // se for Mon..Thu, volta para Monday
      simple.setUTCDate(simple.getUTCDate() - (dow - 1));
    } else {
      // se Fri..Sun, avança até próxima Monday
      simple.setUTCDate(simple.getUTCDate() + (8 - dow));
    }
    return simple; // Monday (00:00 UTC)
  }
  function isoWeekRangeStr(year, week) {
    const start = isoWeekStartUTC(year, week);
    const end   = new Date(start);
    end.setUTCDate(start.getUTCDate() + 6);
    const fmt = d => {
      const dd = String(d.getUTCDate()).padStart(2, '0');
      const mm = String(d.getUTCMonth() + 1).padStart(2, '0');
      const yyyy = d.getUTCFullYear();
      return `${dd}/${mm}/${yyyy}`;
    };
    return `${fmt(start)} – ${fmt(end)}`;
  }

  // === Parse rótulos técnicos vindos do backend ===
  // 'YYYY-W##' | 'YYYY-MM' | 'YYYY-MM-DD'
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

  // === Formatação amigável (eixo X / tooltips) ===
  function prettyForAxis(raw, groupMode) {
    // Se estamos agrupando por usuário, o eixo mostra nomes — não formata.
    if (groupMode === 'user') return raw;
    const p = parsePeriod(raw);
    if (p.type === 'week') return `${p.year}-SEMANA`;
    if (p.type === 'month') return `${p.year}-MÊS`;
    if (p.type === 'day') return `${p.year}-DIA`;
    return raw;
  }
  function prettyForTooltipTitle(raw, groupMode) {
    const p = parsePeriod(raw);
    if (groupMode === 'user') {
      // nesse modo o "raw" será o nome do usuário (label do eixo)
      return `Usuário: ${raw}`;
    }
    if (p.type === 'week') {
      return `Semana ${p.week} (${isoWeekRangeStr(p.year, p.week)})`;
    }
    if (p.type === 'month') {
      const mm = String(p.month).padStart(2, '0');
      return `Mês ${mm}/${p.year}`;
    }
    if (p.type === 'day') {
      const dd = String(p.day).padStart(2, '0');
      const mm = String(p.month).padStart(2, '0');
      return `Dia ${dd}/${mm}/${p.year}`;
    }
    return raw;
  }

  // === Busca API ===
  async function fetchData() {
    const params = new URLSearchParams(new FormData(form));
    if (!toggler || !toggler.checked) params.delete('users[]');
    const res = await fetch('api/dashboard_counts.php?' + params.toString(), { cache: 'no-store' });
    const json = await res.json();
    if (!json.ok) throw new Error(json.error || 'Erro ao consultar a API.');
    return json; // {mode,start,end,labels,series}
  }

  // === Transforma dados para barras, respeitando "Agrupar por" ===
  function transformForBar(json) {
    const { labels: periodLabels, series } = json;
    const users = Object.values(series || {});
    const group = groupBy ? (groupBy.value || 'period') : 'period';

    if (group === 'user') {
      // X = nomes de usuários; dataset = períodos (transformamos os labels dos datasets para títulos amigáveis)
      const userNames = users.map(u => u.name);
      const datasets = (periodLabels || []).map((periodLabel, idx) => {
        const data = users.map(u => Number(u.data[idx] || 0));
        const c = colorFromSeed(idx);
        return {
          // dataset.label vira o período "bonito" para tooltip
          label: prettyForTooltipTitle(periodLabel, 'period'),
          _rawLabel: periodLabel, // guardo o raw caso precise
          data,
          borderColor: c.border,
          backgroundColor: c.fill,
          borderWidth: 1,
          maxBarThickness: 42,
          categoryPercentage: 0.7,
          barPercentage: 0.9
        };
      });
      return { labels: userNames, datasets, groupBy: 'user' };
    } else {
      // group === 'period': X = períodos; dataset = usuários
      const datasets = users.map((u, i) => {
        const c = colorFromSeed(i);
        return {
          label: u.name,
          _rawLabel: u.name,
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
  }

  // Plugin DataLabels (mostrar valores sobre as barras)
  // Doc: https://chartjs-plugin-datalabels.netlify.app/
  Chart.register(ChartDataLabels); // :contentReference[oaicite:3]{index=3}

  function renderChart(config, meta) {
    if (chart) chart.destroy();

    chart = new Chart(ctx, {
      type: 'bar',
      data: { labels: config.labels, datasets: config.datasets },
      options: {
        responsive: true,
        maintainAspectRatio: false, // recomendado para responsividade do canvas. :contentReference[oaicite:4]{index=4}
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
              // Título do tooltip SEM rótulo técnico (usa formatador amigável)
              title: (items) => {
                if (!items?.length) return '';
                const idx = items[0].dataIndex;
                if (config.groupBy === 'user') {
                  // x-label é o nome do usuário
                  const userName = config.labels[idx];
                  return prettyForTooltipTitle(userName, 'user');
                } else {
                  // x-label é o período bruto (YYYY-W## | YYYY-MM | YYYY-MM-DD)
                  const raw = config.labels[idx];
                  return prettyForTooltipTitle(raw, 'period');
                }
              },
              // Cada linha: série (usuário ou período) + valor
              label: (ctx) => {
                // Chart.js recomenda personalizar label callback para controlar o texto. :contentReference[oaicite:5]{index=5}
                const v = ctx.parsed.y ?? ctx.raw ?? 0;
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
              // Troca o que aparece no eixo X para algo amigável. Doc: ticks.callback. :contentReference[oaicite:6]{index=6}
              callback: function (value, index) {
                const raw = this.getLabelForValue ? this.getLabelForValue(value) : (this.chart?.data?.labels?.[index] ?? value);
                return prettyForAxis(raw, config.groupBy);
              }
            }
          },
          y: { 
            beginAtZero: true,
            suggestedMax: 10,
            ticks: { 
              stepSize: 1,
              precision: 0 
            }
          }
        }
      }
    });

    // Rodapé descritivo (caption) abaixo do gráfico
    if (captionEl) {
      const y = (meta.start || '').slice(0, 4) || new Date().getFullYear();
      captionEl.textContent = (meta.mode === 'week') ? `${y}-SEMANA`
                            : (meta.mode === 'month') ? `${y}-MÊS`
                            : `${y}-DIA`;
    }
  }

  async function run() {
    try {
      if (errBox) { errBox.classList.add('hidden'); errBox.textContent = ''; }
      const json = await fetchData();
      const cfg  = transformForBar(json);
      renderChart(cfg, { mode: json.mode, start: json.start, end: json.end });
    } catch (e) {
      if (errBox) { errBox.classList.remove('hidden'); errBox.textContent = e.message; }
    }
  }

  form?.addEventListener('submit', e => { e.preventDefault(); run(); });
  run(); // primeira renderização
});
