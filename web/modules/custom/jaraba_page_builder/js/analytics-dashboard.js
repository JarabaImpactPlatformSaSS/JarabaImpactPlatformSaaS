/**
 * @file
 * Analytics Dashboard JavaScript.
 *
 * GAP C: Integrated Analytics Dashboard.
 * Inicializa gráficos Chart.js, partículas y contadores animados.
 */

(function (Drupal, drupalSettings, once) {
    'use strict';

    /**
     * Inicializa las partículas del hero.
     */
    function initParticles(canvas) {
        const ctx = canvas.getContext('2d');
        const particles = [];
        const particleCount = 50;

        // Crear partículas
        for (let i = 0; i < particleCount; i++) {
            particles.push({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                radius: Math.random() * 2 + 1,
                opacity: Math.random() * 0.5 + 0.2,
                vx: (Math.random() - 0.5) * 0.5,
                vy: (Math.random() - 0.5) * 0.5
            });
        }

        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            particles.forEach(particle => {
                particle.x += particle.vx;
                particle.y += particle.vy;

                // Wrap around edges
                if (particle.x < 0) particle.x = canvas.width;
                if (particle.x > canvas.width) particle.x = 0;
                if (particle.y < 0) particle.y = canvas.height;
                if (particle.y > canvas.height) particle.y = 0;

                ctx.beginPath();
                ctx.arc(particle.x, particle.y, particle.radius, 0, Math.PI * 2);
                ctx.fillStyle = `rgba(255, 255, 255, ${particle.opacity})`;
                ctx.fill();
            });

            requestAnimationFrame(animate);
        }

        // Ajustar tamaño del canvas
        function resizeCanvas() {
            canvas.width = canvas.offsetWidth;
            canvas.height = canvas.offsetHeight;
        }

        window.addEventListener('resize', resizeCanvas);
        resizeCanvas();
        animate();
    }

    /**
     * Inicializa el gráfico de tendencias con Chart.js.
     */
    function initTrendsChart(canvas, data) {
        if (!window.Chart) {
            console.warn('Chart.js not loaded');
            return;
        }

        new Chart(canvas, {
            type: 'line',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                family: 'Inter, system-ui, sans-serif',
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 82, 155, 0.9)',
                        titleFont: {
                            family: 'Inter, system-ui, sans-serif',
                            size: 13
                        },
                        bodyFont: {
                            family: 'Inter, system-ui, sans-serif',
                            size: 12
                        },
                        padding: 12,
                        cornerRadius: 8
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                family: 'Inter, system-ui, sans-serif',
                                size: 11
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            font: {
                                family: 'Inter, system-ui, sans-serif',
                                size: 11
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Anima los contadores de estadísticas.
     */
    function animateCounters(context) {
        const counters = context.querySelectorAll('.analytics-stat__value[data-count]');

        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-count'), 10);
            if (isNaN(target)) return;

            const originalText = counter.textContent;
            const duration = 1500;
            const startTime = performance.now();

            function updateCounter(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);

                // Easing function
                const easeOutQuart = 1 - Math.pow(1 - progress, 4);
                const current = Math.floor(target * easeOutQuart);

                // Preserve format (e.g., "4.7%" or "2m 7s")
                if (originalText.includes('%')) {
                    counter.textContent = (current / 10).toFixed(1) + '%';
                } else if (originalText.includes('m ')) {
                    const minutes = Math.floor(current / 60);
                    const seconds = current % 60;
                    counter.textContent = minutes + 'm ' + seconds + 's';
                } else {
                    counter.textContent = current.toLocaleString();
                }

                if (progress < 1) {
                    requestAnimationFrame(updateCounter);
                }
            }

            requestAnimationFrame(updateCounter);
        });
    }

    /**
     * Inicializa el filtro de período temporal.
     */
    function initPeriodFilter(select, chartCanvas) {
        select.addEventListener('change', function () {
            const period = this.value;
            // Muestra indicador de carga
            const chartContainer = chartCanvas.closest('.analytics-dashboard__chart-container');
            if (chartContainer) {
                chartContainer.classList.add('is-loading');
            }

            // Simula recarga de datos (en producción sería una llamada AJAX)
            setTimeout(() => {
                // Genera nuevos datos simulados basado en el período
                const newData = generateTrendsData(parseInt(period, 10));

                // Destruye el gráfico anterior y crea uno nuevo
                const chart = Chart.getChart(chartCanvas);
                if (chart) {
                    chart.data = newData;
                    chart.update('active');
                }

                if (chartContainer) {
                    chartContainer.classList.remove('is-loading');
                }

                // Muestra notificación
                Drupal.announce(Drupal.t('Datos actualizados para @days días', { '@days': period }));

                // Actualiza el título del período
                const periodLabel = document.getElementById('trends-period-label');
                if (periodLabel) {
                    const selectedOption = select.options[select.selectedIndex].text;
                    periodLabel.textContent = '(' + selectedOption + ')';
                }
            }, 500);
        });
    }

    /**
     * Genera datos de tendencias simulados.
     */
    function generateTrendsData(days) {
        const labels = [];
        const viewsData = [];
        const ctaData = [];

        for (let i = days - 1; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            labels.push(date.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' }));
            viewsData.push(Math.floor(Math.random() * 150) + 100);
            ctaData.push(Math.floor(Math.random() * 50) + 10);
        }

        return {
            labels: labels,
            datasets: [
                {
                    label: Drupal.t('Visualizaciones'),
                    data: viewsData,
                    borderColor: 'rgba(0, 82, 155, 1)',
                    backgroundColor: 'rgba(0, 82, 155, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: Drupal.t('Clicks CTA'),
                    data: ctaData,
                    borderColor: 'rgba(229, 49, 112, 1)',
                    backgroundColor: 'rgba(229, 49, 112, 0.1)',
                    fill: true,
                    tension: 0.4
                }
            ]
        };
    }

    /**
     * Inicializa la exportación de datos.
     */
    function initExportAction() {
        document.addEventListener('click', function (e) {
            const exportBtn = e.target.closest('a[href="#export"]');
            if (!exportBtn) return;

            e.preventDefault();

            // Obtiene datos de la tabla
            const table = document.querySelector('.analytics-dashboard__table');
            if (!table) {
                Drupal.announce(Drupal.t('No hay datos para exportar'));
                return;
            }

            // Genera CSV
            let csv = 'Página,Plantilla,Vistas,CTR,Bounce,Tiempo\n';
            table.querySelectorAll('tbody tr').forEach(row => {
                const cells = row.querySelectorAll('td');
                const title = cells[0]?.querySelector('.analytics-dashboard__page-title')?.textContent?.trim() || '';
                const template = cells[0]?.querySelector('.analytics-dashboard__page-template')?.textContent?.trim() || '';
                const views = cells[1]?.textContent?.trim() || '';
                const ctr = cells[2]?.textContent?.trim() || '';
                const bounce = cells[3]?.textContent?.trim() || '';
                const time = cells[4]?.textContent?.trim() || '';
                csv += `"${title}","${template}","${views}","${ctr}","${bounce}","${time}"\n`;
            });

            // Descarga el archivo con BOM UTF-8 para compatibilidad con Excel
            const BOM = '\uFEFF';
            const blob = new Blob([BOM + csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'analytics-paginas-' + new Date().toISOString().split('T')[0] + '.csv';
            link.click();

            Drupal.announce(Drupal.t('Datos exportados correctamente'));
        });
    }

    /**
     * Inicializa la búsqueda en la tabla de páginas.
     */
    function initTableSearch(input, table) {
        if (!input || !table) return;

        input.addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase().trim();
            const rows = table.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const titleCell = row.querySelector('.analytics-dashboard__page-title');
                const templateCell = row.querySelector('.analytics-dashboard__page-template');
                const title = titleCell ? titleCell.textContent.toLowerCase() : '';
                const template = templateCell ? templateCell.textContent.toLowerCase() : '';

                const matches = title.includes(searchTerm) || template.includes(searchTerm);
                row.style.display = matches ? '' : 'none';
            });
        });
    }

    /**
     * Inicializa la ordenación de columnas de la tabla.
     */
    function initTableSort(table) {
        if (!table) return;

        const headers = table.querySelectorAll('.analytics-dashboard__th--sortable');
        let currentSort = { column: null, direction: 'asc' };

        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', function () {
                const sortKey = this.getAttribute('data-sort');
                const tbody = table.querySelector('tbody');
                const rows = Array.from(tbody.querySelectorAll('tr'));

                // Alternar dirección si es la misma columna.
                if (currentSort.column === sortKey) {
                    currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
                } else {
                    currentSort.column = sortKey;
                    currentSort.direction = 'asc';
                }

                // Quitar clase activa de otras columnas.
                headers.forEach(h => h.classList.remove('analytics-dashboard__th--sorted-asc', 'analytics-dashboard__th--sorted-desc'));
                this.classList.add('analytics-dashboard__th--sorted-' + currentSort.direction);

                // Ordenar filas.
                rows.sort((a, b) => {
                    let aVal, bVal;

                    switch (sortKey) {
                        case 'title':
                            aVal = a.querySelector('.analytics-dashboard__page-title')?.textContent.toLowerCase() || '';
                            bVal = b.querySelector('.analytics-dashboard__page-title')?.textContent.toLowerCase() || '';
                            break;
                        case 'views':
                            aVal = parseMetricValue(a.querySelectorAll('.analytics-dashboard__metric')[0]?.textContent);
                            bVal = parseMetricValue(b.querySelectorAll('.analytics-dashboard__metric')[0]?.textContent);
                            break;
                        case 'ctr':
                            aVal = parseMetricValue(a.querySelectorAll('.analytics-dashboard__metric')[1]?.textContent);
                            bVal = parseMetricValue(b.querySelectorAll('.analytics-dashboard__metric')[1]?.textContent);
                            break;
                        case 'bounce':
                            aVal = parseMetricValue(a.querySelectorAll('.analytics-dashboard__metric')[2]?.textContent);
                            bVal = parseMetricValue(b.querySelectorAll('.analytics-dashboard__metric')[2]?.textContent);
                            break;
                        case 'time':
                            aVal = parseTimeValue(a.querySelectorAll('.analytics-dashboard__metric')[3]?.textContent);
                            bVal = parseTimeValue(b.querySelectorAll('.analytics-dashboard__metric')[3]?.textContent);
                            break;
                        default:
                            aVal = '';
                            bVal = '';
                    }

                    if (typeof aVal === 'string') {
                        return currentSort.direction === 'asc'
                            ? aVal.localeCompare(bVal)
                            : bVal.localeCompare(aVal);
                    }
                    return currentSort.direction === 'asc' ? aVal - bVal : bVal - aVal;
                });

                // Reinsertar filas ordenadas.
                rows.forEach(row => tbody.appendChild(row));
            });
        });
    }

    /**
     * Parsea un valor de métrica (ej: "1,234" o "45.2%").
     */
    function parseMetricValue(text) {
        if (!text) return 0;
        return parseFloat(text.replace(/[^0-9.]/g, '')) || 0;
    }

    /**
     * Parsea un valor de tiempo (ej: "2m 15s").
     */
    function parseTimeValue(text) {
        if (!text) return 0;
        const match = text.match(/(\d+)m\s*(\d+)s/);
        if (match) {
            return parseInt(match[1], 10) * 60 + parseInt(match[2], 10);
        }
        const secMatch = text.match(/(\d+)s/);
        return secMatch ? parseInt(secMatch[1], 10) : 0;
    }

    /**
     * Convierte un select en un buscador tipo Chosen/Select2.
     * Ligero, sin dependencias externas.
     */
    function initSearchableSelect(select) {
        if (!select || select.classList.contains('searchable-initialized')) return;
        select.classList.add('searchable-initialized');

        // Crear wrapper.
        const wrapper = document.createElement('div');
        wrapper.className = 'searchable-select';
        select.parentNode.insertBefore(wrapper, select);

        // Crear input.
        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'searchable-select__input';
        input.placeholder = Drupal.t('Buscar página...');
        input.setAttribute('aria-label', Drupal.t('Buscar página'));

        // Crear dropdown.
        const dropdown = document.createElement('div');
        dropdown.className = 'searchable-select__dropdown';
        dropdown.style.display = 'none';

        wrapper.appendChild(input);
        wrapper.appendChild(dropdown);

        // Ocultar select original pero mantenerlo para el valor.
        select.style.position = 'absolute';
        select.style.opacity = '0';
        select.style.pointerEvents = 'none';
        select.style.width = '1px';
        select.style.height = '1px';
        wrapper.appendChild(select);

        // Renderizar opciones.
        function renderOptions(filter = '') {
            dropdown.innerHTML = '';
            const options = Array.from(select.options).slice(1); // Saltar "Seleccionar"
            const lowerFilter = filter.toLowerCase();

            options.forEach(option => {
                const text = option.textContent;
                if (!filter || text.toLowerCase().includes(lowerFilter)) {
                    const item = document.createElement('div');
                    item.className = 'searchable-select__option';
                    item.textContent = text;
                    item.setAttribute('data-value', option.value);
                    item.addEventListener('click', function () {
                        select.value = option.value;
                        input.value = text;
                        dropdown.style.display = 'none';
                        select.dispatchEvent(new Event('change'));
                    });
                    dropdown.appendChild(item);
                }
            });

            if (dropdown.children.length === 0) {
                const noResults = document.createElement('div');
                noResults.className = 'searchable-select__no-results';
                noResults.textContent = Drupal.t('Sin resultados');
                dropdown.appendChild(noResults);
            }
        }

        // Eventos.
        input.addEventListener('focus', function () {
            renderOptions(this.value);
            dropdown.style.display = 'block';
        });

        input.addEventListener('input', function () {
            renderOptions(this.value);
            dropdown.style.display = 'block';
        });

        document.addEventListener('click', function (e) {
            if (!wrapper.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });

        // Si el select ya tiene un valor seleccionado, mostrarlo.
        if (select.selectedIndex > 0) {
            input.value = select.options[select.selectedIndex].textContent;
        }
    }

    /**
     * Behavior principal del dashboard de analytics.
     */
    Drupal.behaviors.analyticsDashboard = {
        attach: function (context) {
            // Inicializar partículas (múltiples selectores para diferentes dashboards)
            once('analytics-particles', '#analytics-particles, #pixel-particles, #particles-canvas', context).forEach(function (canvas) {
                initParticles(canvas);
            });

            // Inicializar gráfico de tendencias
            once('trends-chart', '#trends-chart', context).forEach(function (canvas) {
                const trendsData = drupalSettings.jarabaAnalytics?.trendsData;
                if (trendsData) {
                    initTrendsChart(canvas, trendsData);
                }

                // Inicializar filtro de período
                const periodFilter = document.getElementById('trends-period');
                if (periodFilter) {
                    initPeriodFilter(periodFilter, canvas);
                }
            });

            // Animar contadores
            once('analytics-counters', '.analytics-dashboard', context).forEach(function (dashboard) {
                // Pequeño delay para que sea visible
                setTimeout(() => animateCounters(dashboard), 300);
            });

            // Inicializar exportación (una sola vez)
            once('analytics-export', 'body', context).forEach(function () {
                initExportAction();
            });

            // Inicializar slide-panel de heatmaps nativos
            // Incluye tanto botones con clase específica como enlaces con data-slide-panel-target
            once('heatmaps-panel', '[data-slide-panel-target="#heatmap-slide-panel"]', context).forEach(function (trigger) {
                initNativeHeatmapPanel(trigger);
            });

            // Inicializar búsqueda y ordenación de tabla de páginas.
            once('pages-table-features', '#pages-table', context).forEach(function (table) {
                const searchInput = document.getElementById('pages-search');
                initTableSearch(searchInput, table);
                initTableSort(table);
            });

            // Inicializar buscador tipo Chosen en selector de heatmaps.
            once('heatmap-searchable', '#heatmap-page-select', context).forEach(function (select) {
                initSearchableSelect(select);
            });
        }
    };

    /**
     * Inicializa el slide-panel de heatmaps nativos.
     * Usa la API global Drupal.behaviors.slidePanel para paneles.
     *
     * @param {HTMLElement} trigger - Elemento que abre el panel.
     */
    function initNativeHeatmapPanel(trigger) {
        const panelId = trigger.getAttribute('data-slide-panel-target');
        if (!panelId) return;

        // Remover # del selector para obtener el ID.
        const cleanPanelId = panelId.replace('#', '');
        const panel = document.getElementById(cleanPanelId);
        if (!panel) return;

        const pageSelect = panel.querySelector('#heatmap-page-select');
        const typeSelect = panel.querySelector('#heatmap-type-select');
        const refreshBtn = panel.querySelector('#heatmap-refresh-btn');
        const heatmapType = trigger.getAttribute('data-heatmap-type') || 'click';

        // Abrir panel usando API del tema.
        trigger.addEventListener('click', function (e) {
            e.preventDefault();

            // Usar API global del slide-panel.
            if (Drupal.behaviors.slidePanel && Drupal.behaviors.slidePanel.openById) {
                Drupal.behaviors.slidePanel.openById(cleanPanelId);
            } else {
                // Fallback si la API no está disponible.
                panel.classList.add('slide-panel--open');
                panel.setAttribute('aria-hidden', 'false');
                document.body.classList.add('slide-panel-open');
            }

            // Pre-seleccionar tipo si viene del trigger.
            if (heatmapType && typeSelect) {
                typeSelect.value = heatmapType;
            }

            // Cargar lista de páginas si no está cargada.
            if (pageSelect && pageSelect.options.length <= 1) {
                loadHeatmapPages(pageSelect);
            }
        });

        // Configurar botón refresh.
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function () {
                loadHeatmapData(panel);
            });
        }
    }

    /**
     * Carga la lista de páginas disponibles para heatmaps.
     */
    function loadHeatmapPages(selectElement) {
        fetch('/api/heatmap/pages')
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.success && data.data) {
                    data.data.forEach(function (page) {
                        var option = document.createElement('option');
                        option.value = page.path;
                        option.textContent = page.path + ' (' + page.events + ' eventos)';
                        selectElement.appendChild(option);
                    });
                }
            })
            .catch(function (err) {
                console.error('Error loading heatmap pages:', err);
            });
    }

    /**
     * Carga los datos del heatmap seleccionado.
     */
    function loadHeatmapData(panel) {
        var pageSelect = panel.querySelector('#heatmap-page-select');
        var typeSelect = panel.querySelector('#heatmap-type-select');
        var periodSelect = panel.querySelector('#heatmap-period-select');
        var deviceSelect = panel.querySelector('#heatmap-device-select');
        var canvas = panel.querySelector('#heatmap-canvas');
        var placeholder = panel.querySelector('.heatmap-viewer__placeholder');

        if (!pageSelect || !pageSelect.value) {
            alert(Drupal.t('Por favor selecciona una página'));
            return;
        }

        var page = encodeURIComponent(pageSelect.value);
        var type = typeSelect ? typeSelect.value : 'click';
        var days = periodSelect ? periodSelect.value : '7';
        var device = deviceSelect ? deviceSelect.value : 'all';

        var endpoint = '/api/heatmap/pages/' + page + '/' + (type === 'click' ? 'clicks' : type);
        endpoint += '?days=' + days + '&device=' + device;

        // Mostrar loading
        if (placeholder) {
            placeholder.innerHTML = '<p>' + Drupal.t('Cargando heatmap...') + '</p>';
        }

        fetch(endpoint)
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.success && canvas) {
                    renderHeatmap(canvas, data, placeholder);
                }
            })
            .catch(function (err) {
                console.error('Error loading heatmap data:', err);
                if (placeholder) {
                    placeholder.innerHTML = '<p>' + Drupal.t('Error al cargar el heatmap') + '</p>';
                }
            });
    }

    /**
     * Renderiza el heatmap en el canvas.
     */
    function renderHeatmap(canvas, data, placeholder) {
        if (!data.buckets || data.buckets.length === 0) {
            if (placeholder) {
                placeholder.style.display = 'flex';
                placeholder.innerHTML = '<p>' + Drupal.t('No hay datos de heatmap para esta página') + '</p>';
            }
            return;
        }

        // Ocultar placeholder, mostrar canvas.
        if (placeholder) placeholder.style.display = 'none';
        canvas.style.display = 'block';

        var ctx = canvas.getContext('2d');
        var width = canvas.offsetWidth || 800;
        var height = canvas.offsetHeight || 600;
        canvas.width = width;
        canvas.height = height;

        // Limpiar canvas.
        ctx.clearRect(0, 0, width, height);

        // Renderizar buckets como círculos de calor.
        data.buckets.forEach(function (bucket) {
            var x = (bucket.x / 100) * width;
            var y = (bucket.y / 100) * height;
            var intensity = bucket.intensity || 0.5;
            var radius = 20 + intensity * 30;

            var gradient = ctx.createRadialGradient(x, y, 0, x, y, radius);
            gradient.addColorStop(0, 'rgba(255, 0, 0, ' + intensity + ')');
            gradient.addColorStop(0.5, 'rgba(255, 165, 0, ' + (intensity * 0.6) + ')');
            gradient.addColorStop(1, 'rgba(255, 255, 0, 0)');

            ctx.beginPath();
            ctx.arc(x, y, radius, 0, Math.PI * 2);
            ctx.fillStyle = gradient;
            ctx.fill();
        });
    }

})(Drupal, drupalSettings, once);

