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

  const toggler   = document.querySelector('#toggle-compare');           // checkbox "Comparar usuários"
  const multi     = document.querySelector('#multi-users');              // container do multiselect
  const usersSel  = document.querySelector('select[name="users[]"]');    // <select multiple>

  const errBox    = document.querySelector('#dashboard-error');
  const captionEl = document.querySelector('#chart-caption');
  const ctxEl     = document.getElementById('kpi-chart');
  const ctx       = ctxEl && ctxEl.getContext ? ctxEl.getContext('2d') : null;

  const monthInp  = document.querySelector('input[name="month"]');
  const isAdmin   = !!(window.APP && window.APP.isAdmin);
  const meId      = (window.APP && Number(window.APP.userId)) || null;

  // --- Novas referências para funcionalidades melhoradas ---
  const selectedCountEl = document.querySelector('#selected-count');
  const loadingSpinner = document.querySelector('.loading-spinner');
  
  // Verificar se elementos existem
  if (!form) {
    console.error('❌ Formulário de filtros não encontrado');
    return;
  }
  
  if (!ctxEl) {
    console.error('❌ Canvas do gráfico não encontrado');
    return;
  }

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
    if (!usersSel) {
      console.warn('usersSel não encontrado');
      return;
    }
    console.log('Preselecionando usuários. Total de opções:', usersSel.options.length);
    const opts = Array.from(usersSel.options);
    if (isAdmin) {
      opts.forEach(o => (o.selected = true));
      console.log('Admin: todos os usuários selecionados');
    } else if (meId != null) {
      opts.forEach(o => (o.selected = Number(o.value) === meId));
      console.log('Usuário normal: apenas ID', meId, 'selecionado');
    }
    updateSelectedCount(); // Atualizar contador após preseleção
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
    console.log('🔄 Alternando modo para:', m);
    
    // Usar display style em vez de classe hidden para garantir funcionamento
    if (week) {
      week.style.display = (m === 'week') ? 'block' : 'none';
      console.log('📅 Campo semana display:', week.style.display);
      
      // Se mudou para modo semanal, inicializar com dados da semana atual
      if (m === 'week') {
        const startInput = document.getElementById('start');
        const endInput = document.getElementById('end');
        if (startInput && !startInput.value) {
          setDateRange('thisWeek');
        }
      }
    }
    if (month) {
      month.style.display = (m === 'month') ? 'block' : 'none';
      console.log('📆 Campo mês display:', month.style.display);
    }
    if (day) {
      day.style.display = (m === 'day') ? 'block' : 'none';
      console.log('📅 Campo dia display:', day.style.display);
    }
  }
  syncModeFields();
  modeSel?.addEventListener('change', syncModeFields);

  // ---------- Paleta de cores moderna e acessível ----------
  // Paleta de cores harmoniosa baseada na empresa BCC
  const colorPalette = [
    '#012980', // Azul principal da empresa
    '#1a4a9e', // Azul médio
    '#3b5bff', // Azul claro
    '#E19005', // Laranja principal
    '#f4a733', // Laranja médio
    '#ffb366', // Laranja claro
    '#00BA67', // Verde principal
    '#33c881', // Verde médio
    '#86efac', // Verde claro
    '#6b8eff', // Azul vibrante
    '#ffcc9f', // Laranja vibrante
    '#bbf7d0', // Verde vibrante
    '#c7d6ff', // Azul suave
    '#ffe2c7', // Laranja suave
    '#dcfce8'  // Verde suave
  ];
  
  // Cache de cores para garantir consistência
  const userColorCache = new Map();
  
  function colorFromUserId(uid) {
    // Garantir que uid é um número válido
    const userId = parseInt(uid) || 1;
    
    // Verificar cache primeiro
    if (userColorCache.has(userId)) {
      return userColorCache.get(userId);
    }
    
    // Calcular cor única baseada no ID
    const colorIndex = (userId - 1) % colorPalette.length;
    const baseColor = colorPalette[colorIndex] || colorPalette[0];
    
    // Usar cores hex diretamente para melhor compatibilidade
    const colorObj = {
      border: String(baseColor),
      fill: String(baseColor) + '33', // Adicionar transparência hex (20%)
      base: baseColor,
      id: userId
    };
    
    // Armazenar no cache
    userColorCache.set(userId, colorObj);
    
    return colorObj;
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

  // ---------- Render (Chart.js Avançado) ----------
  let chart;

  // Registra datalabels se carregado (v3+ exige register)
  if (typeof Chart !== 'undefined' && typeof window.ChartDataLabels !== 'undefined') {
    Chart.register(window.ChartDataLabels);
  }

  function renderChart(config, meta, stats = null) {
    if (!ctx || typeof Chart === 'undefined') return;
    if (chart) chart.destroy();

    // Configurações avançadas do gráfico
    const chartConfig = {
      type: 'bar',
      data: { 
        labels: config.labels, 
        datasets: config.datasets.map((dataset, index) => {
          // Usar a cor original do dataset (que já vem com a cor correta do usuário)
          let backgroundColor;
          
          // Se é um array de cores (modo user), usar as cores diretamente
          if (Array.isArray(dataset.backgroundColor)) {
            backgroundColor = dataset.backgroundColor;
          } else {
            // Se é uma cor única, criar gradiente
            const borderColor = String(dataset.borderColor || colorPalette[index % colorPalette.length]);
            backgroundColor = createGradient(ctx, borderColor);
          }
          
          return {
            ...dataset,
            backgroundColor: backgroundColor,
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false,
            // Animações personalizadas
            animation: {
              duration: 1200,
              easing: 'easeOutQuart'
            }
          };
        })
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { 
          mode: 'index', 
          intersect: false,
          axis: 'x'
        },
        elements: { 
          bar: { 
            borderRadius: 8,
            borderSkipped: false,
            // Sombra suave
            shadowOffsetX: 0,
            shadowOffsetY: 4,
            shadowBlur: 8,
            shadowColor: 'rgba(0, 0, 0, 0.1)'
          } 
        },
        plugins: {
          legend: { 
            position: 'top', 
            labels: { 
              boxWidth: 16,
              boxHeight: 16,
              padding: 20,
              usePointStyle: true,
              pointStyle: 'circle',
              font: {
                size: 13,
                weight: '500'
              }
            },
            onClick: (e, legendItem, legend) => {
              const index = legendItem.datasetIndex;
              const chart = legend.chart;
              const meta = chart.getDatasetMeta(index);
              meta.hidden = meta.hidden === null ? !chart.data.datasets[index].hidden : null;
              chart.update();
            }
          },
          title: { 
            display: true,
            text: generateChartTitle(config, meta, stats),
            font: {
              size: 18,
              weight: '600'
            },
            padding: {
              top: 10,
              bottom: 30
            }
          },
          datalabels: {
            anchor: 'end',
            align: 'end',
            offset: 4,
            clamp: true,
            color: getComputedStyle(document.body).color,
            font: {
              size: 11,
              weight: '600'
            },
            formatter: (value, context) => {
              if (typeof value === 'number' && value > 0) {
                return value;
              }
              return '';
            }
          },
          tooltip: {
            backgroundColor: 'rgba(0, 0, 0, 0.8)',
            titleColor: '#fff',
            bodyColor: '#fff',
            borderColor: 'rgba(255, 255, 255, 0.1)',
            borderWidth: 1,
            cornerRadius: 8,
            displayColors: true,
            callbacks: {
              title: (items) => {
                if (!items?.length) return '';
                const idx = items[0].dataIndex;
                if (config.groupBy === 'user') {
                  const userName = config.labels[idx];
                  return `👤 ${prettyForTooltipTitle(userName, 'user')}`;
                } else {
                  const raw = config.labels[idx];
                  return `📅 ${prettyForTooltipTitle(raw, 'period')}`;
                }
              },
              label: (ctx) => {
                const v = ctx.parsed?.y ?? ctx.raw ?? 0;
                const percentage = stats ? ((v / stats.total_clients) * 100).toFixed(1) : '';
                return `${ctx.dataset.label}: ${v} clientes${percentage ? ` (${percentage}%)` : ''}`;
              },
              afterBody: (items) => {
                if (stats && stats.total_clients > 0) {
                  const total = items.reduce((sum, item) => sum + (item.parsed?.y || 0), 0);
                  const percentage = ((total / stats.total_clients) * 100).toFixed(1);
                  return [`Total: ${total} clientes (${percentage}%)`];
                }
                return [];
              }
            }
          }
        },
        scales: {
          x: {
            type: 'category',
            grid: {
              display: false
            },
            ticks: {
              autoSkip: false,
              maxRotation: 45,
              minRotation: 0,
              font: {
                size: 12,
                weight: '500'
              },
              color: getComputedStyle(document.body).color,
              callback: function (value, index) {
                const raw = this.getLabelForValue ? this.getLabelForValue(value)
                         : (this.chart?.data?.labels?.[index] ?? value);
                return prettyForAxis(raw, config.groupBy);
              }
            }
          },
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(0, 0, 0, 0.05)',
              drawBorder: false
            },
            ticks: { 
              stepSize: 10, 
              precision: 0,
              font: {
                size: 12,
                weight: '500'
              },
              color: getComputedStyle(document.body).color,
              callback: function(value) {
                if (value >= 1000) {
                  return (value / 1000).toFixed(1) + 'k';
                }
                return value;
              }
            }
          }
        },
        animation: {
          duration: 1200,
          easing: 'easeOutQuart'
        }
      }
    };

    chart = new Chart(ctx, chartConfig);

    // Atualizar estatísticas e caption
    updateChartInfo(config, meta, stats);
  }

  // Função para criar gradiente
  function createGradient(ctx, color) {
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    
    // Garantir que color é uma string
    const colorStr = String(color || '#3B82F6');
    
    // Converter cor para rgba
    let startColor, endColor;
    
    if (colorStr.startsWith && colorStr.startsWith('#')) {
      // Para cores hex, converter para rgba
      const hex = colorStr.replace('#', '');
      const r = parseInt(hex.substr(0, 2), 16);
      const g = parseInt(hex.substr(2, 2), 16);
      const b = parseInt(hex.substr(4, 2), 16);
      
      startColor = `rgba(${r}, ${g}, ${b}, 0.4)`;
      endColor = `rgba(${r}, ${g}, ${b}, 0.1)`;
    } else if (colorStr.startsWith && colorStr.startsWith('hsl(')) {
      // Para cores HSL, usar fallback simples
      startColor = colorStr.replace(')', ', 0.4)').replace('hsl', 'hsla');
      endColor = colorStr.replace(')', ', 0.1)').replace('hsl', 'hsla');
    } else if (colorStr.startsWith && colorStr.startsWith('rgba(')) {
      // Para cores rgba, ajustar opacidade
      startColor = colorStr.replace(/[\d\.]+\)$/, '0.4)');
      endColor = colorStr.replace(/[\d\.]+\)$/, '0.1)');
    } else {
      // Fallback para cor padrão
      startColor = 'rgba(59, 130, 246, 0.4)';
      endColor = 'rgba(59, 130, 246, 0.1)';
    }
    
    gradient.addColorStop(0, startColor);
    gradient.addColorStop(1, endColor);
    return gradient;
  }

  // Função para gerar título do gráfico
  function generateChartTitle(config, meta, stats) {
    if (!stats) return 'Dashboard de Clientes';
    
    const periodLabel = stats.period_label;
    const totalClients = stats.total_clients;
    const totalUsers = stats.total_users;
    
    return `📊 ${totalClients} clientes cadastrados por ${totalUsers} funcionários neste ${periodLabel}`;
  }

  // Função para atualizar informações do gráfico
  function updateChartInfo(config, meta, stats) {
    if (!captionEl) return;
    
    if (stats) {
      const startDate = stats.date_range.start_formatted;
      const endDate = stats.date_range.end_formatted;
      const duration = stats.date_range.duration_days;
      
      let caption = `📅 Período: ${startDate} a ${endDate}`;
      if (duration > 1) {
        caption += ` (${duration} dias)`;
      }
      
      if (stats.top_performer) {
        caption += ` | 🏆 Top: ${stats.top_performer.name} (${stats.top_performer.total} clientes)`;
      }
      
      caption += ` | 📈 Média: ${stats.average_per_user} clientes/usuário`;
      
      captionEl.innerHTML = caption;
    } else {
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
      renderChart(cfg, { mode: json.mode, start: json.start, end: json.end }, json.stats);
      
      // Atualizar estatísticas na interface se disponível
      updateStatsDisplay(json.stats);
    } catch (e) {
      if (errBox) { errBox.classList.remove('hidden'); errBox.textContent = e.message || 'Erro inesperado.'; }
    }
  }

  // Função para atualizar display de estatísticas
  function updateStatsDisplay(stats) {
    if (!stats) return;
    
    // Criar ou atualizar painel de estatísticas
    let statsPanel = document.getElementById('stats-panel');
    if (!statsPanel) {
      statsPanel = document.createElement('div');
      statsPanel.id = 'stats-panel';
      statsPanel.className = 'stats-panel';
      
      const chartContainer = document.querySelector('.table-wrap');
      if (chartContainer && chartContainer.parentNode) {
        chartContainer.parentNode.insertBefore(statsPanel, chartContainer);
      }
    }
    
    statsPanel.innerHTML = `
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">👥</div>
          <div class="stat-content">
            <div class="stat-value">${stats.total_users}</div>
            <div class="stat-label">Funcionários</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">📊</div>
          <div class="stat-content">
            <div class="stat-value">${stats.total_clients}</div>
            <div class="stat-label">Total de Clientes</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">📈</div>
          <div class="stat-content">
            <div class="stat-value">${stats.average_per_user}</div>
            <div class="stat-label">Média por Usuário</div>
          </div>
        </div>
        ${stats.top_performer ? `
        <div class="stat-card top-performer">
          <div class="stat-icon">🏆</div>
          <div class="stat-content">
            <div class="stat-value">${stats.top_performer.total}</div>
            <div class="stat-label">Top: ${stats.top_performer.name}</div>
          </div>
        </div>
        ` : ''}
      </div>
    `;
  }

  // ---------- Novas Funcionalidades Melhoradas ----------

  // Atualizar contador de usuários selecionados
  function updateSelectedCount() {
    if (!usersSel) return;
    const selected = Array.from(usersSel.selectedOptions).length;
    if (selectedCountEl) {
      selectedCountEl.textContent = selected;
    }
  }

  // Presets de data para semana
  window.setDateRange = function(preset) {
    const today = new Date();
    let start, end;
    
    console.log('📅 Configurando período:', preset);
    
    switch (preset) {
      case 'thisWeek':
        start = new Date(today);
        start.setDate(today.getDate() - today.getDay() + 1); // Segunda-feira
        end = new Date(start);
        end.setDate(start.getDate() + 6); // Domingo
        break;
      case 'lastWeek':
        start = new Date(today);
        start.setDate(today.getDate() - today.getDay() - 6); // Segunda da semana passada
        end = new Date(start);
        end.setDate(start.getDate() + 6);
        break;
      case 'lastMonth':
        start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        end = new Date(today.getFullYear(), today.getMonth(), 0);
        break;
    }
    
    const startInput = document.getElementById('start');
    const endInput = document.getElementById('end');
    if (startInput) startInput.value = start.toISOString().split('T')[0];
    if (endInput) endInput.value = end.toISOString().split('T')[0];
    
    console.log('📅 Datas configuradas:', {
      start: start.toISOString().split('T')[0],
      end: end.toISOString().split('T')[0],
      startInputValue: startInput?.value,
      endInputValue: endInput?.value
    });
  };

  // Presets de data para mês
  window.setMonth = function(preset) {
    const today = new Date();
    let monthInput = document.getElementById('month');
    if (!monthInput) return;
    
    switch (preset) {
      case 'current':
        monthInput.value = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`;
        break;
      case 'previous':
        const prevMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        monthInput.value = `${prevMonth.getFullYear()}-${String(prevMonth.getMonth() + 1).padStart(2, '0')}`;
        break;
      case 'last3':
        // Para múltiplos meses, vamos usar o modo semanal
        modeSel.value = 'week';
        syncModeFields();
        const threeMonthsAgo = new Date(today.getFullYear(), today.getMonth() - 3, 1);
        const startInput = document.getElementById('start');
        const endInput = document.getElementById('end');
        if (startInput) startInput.value = threeMonthsAgo.toISOString().split('T')[0];
        if (endInput) endInput.value = today.toISOString().split('T')[0];
        break;
    }
  };

  // Presets de data para dia
  window.setDay = function(preset) {
    const today = new Date();
    let dayInput = document.getElementById('day');
    if (!dayInput) return;
    
    switch (preset) {
      case 'today':
        dayInput.value = today.toISOString().split('T')[0];
        break;
      case 'yesterday':
        const yesterday = new Date(today);
        yesterday.setDate(today.getDate() - 1);
        dayInput.value = yesterday.toISOString().split('T')[0];
        break;
      case 'last7days':
        // Mudar para modo semanal e configurar
        modeSel.value = 'week';
        syncModeFields();
        const weekAgo = new Date(today);
        weekAgo.setDate(today.getDate() - 7);
        const startInput = document.getElementById('start');
        const endInput = document.getElementById('end');
        if (startInput) startInput.value = weekAgo.toISOString().split('T')[0];
        if (endInput) endInput.value = today.toISOString().split('T')[0];
        break;
    }
  };

  // Controles de seleção de usuários
  window.selectAllUsers = function() {
    if (!usersSel) return;
    Array.from(usersSel.options).forEach(option => option.selected = true);
    updateSelectedCount();
  };

  window.selectNoneUsers = function() {
    if (!usersSel) return;
    Array.from(usersSel.options).forEach(option => option.selected = false);
    updateSelectedCount();
  };

  window.toggleUserSelection = function() {
    if (!usersSel) return;
    Array.from(usersSel.options).forEach(option => option.selected = !option.selected);
    updateSelectedCount();
  };

  // Resetar filtros
  window.resetFilters = function() {
    if (!form) return;
    
    // Reset para valores padrão
    modeSel.value = 'month';
    groupBy.value = 'period';
    
    // Reset datas
    const today = new Date();
    document.getElementById('month').value = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`;
    
    // Reset filtros avançados
    document.getElementById('minClients').value = '';
    document.getElementById('sortBy').value = 'name';
    
    // Reset checkbox
    if (toggler) toggler.checked = true;
    applyCompareVisibility();
    
    // Reset usuários usando a função de preseleção
    preselectUsers();
    syncModeFields();
    
    // Limpar filtros salvos
    localStorage.removeItem('dashboard_filters');
  };

  // Salvar filtros (localStorage)
  window.saveFilters = function() {
    if (!form) return;
    
    const filters = {
      mode: modeSel.value,
      groupBy: groupBy.value,
      month: document.getElementById('month')?.value || '',
      start: document.getElementById('start')?.value || '',
      end: document.getElementById('end')?.value || '',
      day: document.getElementById('day')?.value || '',
      minClients: document.getElementById('minClients')?.value || '',
      sortBy: document.getElementById('sortBy')?.value || 'name',
      compareUsers: toggler?.checked || false,
      selectedUsers: usersSel ? Array.from(usersSel.selectedOptions).map(o => o.value) : []
    };
    
    localStorage.setItem('dashboard_filters', JSON.stringify(filters));
    
    // Feedback visual
    const saveBtn = document.querySelector('button[onclick="saveFilters()"]');
    const originalText = saveBtn.textContent;
    saveBtn.textContent = '✅ Salvo!';
    saveBtn.style.background = '#4caf50';
    setTimeout(() => {
      saveBtn.textContent = originalText;
      saveBtn.style.background = '';
    }, 2000);
  };

  // Carregar filtros salvos
  function loadSavedFilters() {
    const saved = localStorage.getItem('dashboard_filters');
    if (!saved) {
      // Se não há filtros salvos, aplicar seleção padrão
      preselectUsers();
      return;
    }
    
    try {
      const filters = JSON.parse(saved);
      
      if (filters.mode) modeSel.value = filters.mode;
      if (filters.groupBy) groupBy.value = filters.groupBy;
      if (filters.month) document.getElementById('month').value = filters.month;
      if (filters.start) document.getElementById('start').value = filters.start;
      if (filters.end) document.getElementById('end').value = filters.end;
      if (filters.day) document.getElementById('day').value = filters.day;
      if (filters.minClients) document.getElementById('minClients').value = filters.minClients;
      if (filters.sortBy) document.getElementById('sortBy').value = filters.sortBy;
      if (filters.compareUsers !== undefined && toggler) toggler.checked = filters.compareUsers;
      
      if (filters.selectedUsers && usersSel && filters.selectedUsers.length > 0) {
        // Limpar todas as seleções primeiro
        Array.from(usersSel.options).forEach(option => option.selected = false);
        // Aplicar seleções salvas
        Array.from(usersSel.options).forEach(option => {
          option.selected = filters.selectedUsers.includes(option.value);
        });
      } else {
        // Se não há usuários salvos, aplicar seleção padrão
        preselectUsers();
      }
      
      applyCompareVisibility();
      updateSelectedCount();
      syncModeFields();
    } catch (e) {
      console.warn('Erro ao carregar filtros salvos:', e);
      // Em caso de erro, aplicar seleção padrão
      preselectUsers();
    }
  }

  // Exportar gráfico com opções avançadas
  window.exportChart = function(format = 'png', quality = 0.9) {
    if (!chart) return;
    
    const canvas = chart.canvas;
    const timestamp = new Date().toISOString().split('T')[0];
    let mimeType, extension;
    
    switch (format) {
      case 'png':
        mimeType = 'image/png';
        extension = 'png';
        break;
      case 'jpeg':
        mimeType = 'image/jpeg';
        extension = 'jpg';
        break;
      case 'pdf':
        // Para PDF, usar biblioteca jsPDF (seria necessário incluir)
        alert('Exportação para PDF requer biblioteca adicional. Usando PNG.');
        format = 'png';
        mimeType = 'image/png';
        extension = 'png';
        break;
      default:
        mimeType = 'image/png';
        extension = 'png';
    }
    
    const link = document.createElement('a');
    link.download = `dashboard-${timestamp}.${extension}`;
    link.href = canvas.toDataURL(mimeType, quality);
    link.click();
    
    // Feedback visual
    const exportBtn = document.querySelector('button[onclick*="exportChart"]');
    if (exportBtn) {
      const originalText = exportBtn.textContent;
      exportBtn.textContent = '✅ Exportado!';
      exportBtn.style.background = '#10B981';
      setTimeout(() => {
        exportBtn.textContent = originalText;
        exportBtn.style.background = '';
      }, 2000);
    }
  };

  // Função para alternar tipo de gráfico
  window.toggleChartType = function() {
    if (!chart) return;
    
    const currentType = chart.config.type;
    const newType = currentType === 'bar' ? 'line' : 'bar';
    
    // Atualizar configuração do gráfico
    chart.config.type = newType;
    
    // Ajustar configurações específicas do tipo
    if (newType === 'line') {
      chart.config.options.elements.point = {
        radius: 6,
        hoverRadius: 8,
        borderWidth: 2
      };
      chart.config.options.elements.line = {
        tension: 0.4,
        borderWidth: 3
      };
    } else {
      chart.config.options.elements.bar = {
        borderRadius: 8,
        borderSkipped: false
      };
    }
    
    chart.update('active');
    
    // Atualizar botão
    const toggleBtn = document.querySelector('button[onclick*="toggleChartType"]');
    if (toggleBtn) {
      toggleBtn.innerHTML = newType === 'bar' ? '📊' : '📈';
      toggleBtn.title = newType === 'bar' ? 'Gráfico de Barras' : 'Gráfico de Linhas';
    }
  };

  // Função para alternar modo de agrupamento
  window.toggleGroupMode = function() {
    if (!groupBy) return;
    
    const currentMode = groupBy.value;
    const newMode = currentMode === 'period' ? 'user' : 'period';
    
    groupBy.value = newMode;
    
    // Disparar evento de mudança
    groupBy.dispatchEvent(new Event('change'));
    
    // Atualizar botão
    const toggleBtn = document.querySelector('button[onclick*="toggleGroupMode"]');
    if (toggleBtn) {
      toggleBtn.innerHTML = newMode === 'period' ? '📅' : '👥';
      toggleBtn.title = newMode === 'period' ? 'Agrupar por Período' : 'Agrupar por Usuário';
    }
  };

  // Função para alternar visibilidade da legenda
  window.toggleLegend = function() {
    if (!chart) {
      console.error('❌ Gráfico não inicializado');
      return;
    }
    
    try {
      const legend = chart.options.plugins.legend;
      legend.display = !legend.display;
      chart.update();
      
      // Atualizar estado do botão
      const toggleBtn = document.querySelector('button[onclick*="toggleLegend"]');
      if (toggleBtn) {
        toggleBtn.innerHTML = legend.display ? '👁️' : '🙈';
        toggleBtn.title = legend.display ? 'Ocultar Legenda' : 'Mostrar Legenda';
        toggleBtn.classList.toggle('active', legend.display);
      }
    } catch (error) {
      console.error('❌ Erro ao alternar legenda:', error);
    }
  };

  // Função para resetar o gráfico
  window.resetChart = function() {
    if (!chart) {
      console.error('❌ Gráfico não inicializado');
      return;
    }
    
    try {
      // Resetar zoom se disponível
      if (chart.resetZoom) {
        chart.resetZoom();
      }
      
      // Resetar animações
      chart.update('none');
      
      // Feedback visual
      const resetBtn = document.querySelector('button[onclick*="resetChart"]');
      if (resetBtn) {
        const originalHTML = resetBtn.innerHTML;
        resetBtn.innerHTML = '✅';
        resetBtn.style.background = '#10B981';
        setTimeout(() => {
          resetBtn.innerHTML = originalHTML;
          resetBtn.style.background = '';
        }, 1000);
      }
    } catch (error) {
      console.error('❌ Erro ao resetar gráfico:', error);
    }
  };

  // ---------- Event Listeners Adicionais ----------
  
  // Atualizar contador quando usuários são selecionados
  if (usersSel) {
    usersSel.addEventListener('change', updateSelectedCount);
  }

  // Carregar filtros salvos ao iniciar
  loadSavedFilters();

  // Loading spinner no submit
  if (form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      if (loadingSpinner) {
        loadingSpinner.classList.remove('hidden');
      }
      run().finally(() => {
        if (loadingSpinner) {
          loadingSpinner.classList.add('hidden');
        }
      });
    });
  }

  // submit via JS
  form?.addEventListener('submit', e => { e.preventDefault(); run(); });

  // primeira renderização
  run();
});
