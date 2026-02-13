/**
 * @file
 * Visor de Heatmaps con Canvas para Jaraba SaaS.
 *
 * Renderiza heatmaps sobre screenshots de páginas usando Canvas 2D
 * con gradientes de calor y controles interactivos.
 *
 * Características:
 * - Canvas 2D con gradientes radiales
 * - Palette de colores configurable
 * - Controles de filtro (tipo, fecha, dispositivo)
 * - Overlay sobre screenshot de página
 * - i18n con Drupal.t()
 *
 * Ref: Doc Técnico #180 - Native Heatmaps System
 */

(function (Drupal, drupalSettings, once) {
    'use strict';

    /**
     * Behavior del visor de heatmaps.
     *
     * @type {Drupal~behavior}
     */
    Drupal.behaviors.jarabaHeatmapViewer = {
        attach: function (context, settings) {
            once('jarabaHeatmapViewer', '.heatmap-viewer-container', context).forEach(function (container) {
                var viewer = new JarabaHeatmapViewer(container);
                viewer.init();
            });
        }
    };

    /**
     * Constructor del visor de heatmaps.
     *
     * @param {Element} container
     *   Elemento contenedor del visor.
     */
    function JarabaHeatmapViewer(container) {
        this.container = container;
        this.canvas = null;
        this.ctx = null;
        this.screenshot = null;
        this.currentData = null;

        // Configuración por defecto.
        this.config = {
            bucketWidth: 5,
            bucketHeight: 50,
            radius: 40,
            blur: 15,
            maxOpacity: 0.8,
            gradient: {
                0.0: 'rgba(0, 0, 255, 0)',
                0.25: 'rgba(0, 0, 255, 0.5)',
                0.5: 'rgba(0, 255, 0, 0.5)',
                0.75: 'rgba(255, 255, 0, 0.7)',
                1.0: 'rgba(255, 0, 0, 0.9)'
            }
        };
    }

    /**
     * Inicializa el visor.
     */
    JarabaHeatmapViewer.prototype.init = function () {
        this.createElements();
        this.bindEvents();
        this.loadPages();
    };

    /**
     * Crea los elementos del DOM necesarios.
     * Detecta si ya existen controles para evitar duplicación.
     */
    JarabaHeatmapViewer.prototype.createElements = function () {
        // Solo crear controles si NO existen previamente (evita duplicación).
        var existingControls = this.container.querySelector('.heatmap-viewer__controls, .heatmap-controls');
        if (!existingControls) {
            var controls = document.createElement('div');
            controls.className = 'heatmap-controls';
            controls.innerHTML = this.getControlsHtml();
            this.container.appendChild(controls);
        }

        // Solo crear canvas si NO existe previamente.
        this.canvas = this.container.querySelector('.heatmap-viewer__canvas, .heatmap-canvas');
        if (!this.canvas) {
            var canvasWrapper = document.createElement('div');
            canvasWrapper.className = 'heatmap-canvas-wrapper';

            this.canvas = document.createElement('canvas');
            this.canvas.className = 'heatmap-canvas';
            canvasWrapper.appendChild(this.canvas);

            this.container.appendChild(canvasWrapper);
        }

        this.ctx = this.canvas.getContext('2d');

        // Solo crear mensaje vacío si NO existe.
        this.emptyMessage = this.container.querySelector('.heatmap-viewer__placeholder, .heatmap-empty');
        if (!this.emptyMessage) {
            this.emptyMessage = document.createElement('div');
            this.emptyMessage.className = 'heatmap-empty';
            this.emptyMessage.textContent = Drupal.t('Selecciona una página para ver su heatmap');
            this.container.appendChild(this.emptyMessage);
        }
    };

    /**
     * Genera el HTML de los controles.
     *
     * @return {string}
     *   HTML de controles.
     */
    JarabaHeatmapViewer.prototype.getControlsHtml = function () {
        return '<div class="heatmap-control-group">' +
            '<label for="heatmap-page-select">' + Drupal.t('Página') + '</label>' +
            '<select id="heatmap-page-select" class="heatmap-select">' +
            '<option value="">' + Drupal.t('-- Seleccionar --') + '</option>' +
            '</select>' +
            '</div>' +
            '<div class="heatmap-control-group">' +
            '<label for="heatmap-type-select">' + Drupal.t('Tipo') + '</label>' +
            '<select id="heatmap-type-select" class="heatmap-select">' +
            '<option value="click">' + Drupal.t('Clics') + '</option>' +
            '<option value="movement">' + Drupal.t('Movimiento') + '</option>' +
            '<option value="scroll">' + Drupal.t('Scroll') + '</option>' +
            '</select>' +
            '</div>' +
            '<div class="heatmap-control-group">' +
            '<label for="heatmap-days-select">' + Drupal.t('Período') + '</label>' +
            '<select id="heatmap-days-select" class="heatmap-select">' +
            '<option value="7">' + Drupal.t('Últimos 7 días') + '</option>' +
            '<option value="30">' + Drupal.t('Últimos 30 días') + '</option>' +
            '<option value="90">' + Drupal.t('Últimos 90 días') + '</option>' +
            '</select>' +
            '</div>' +
            '<div class="heatmap-control-group">' +
            '<label for="heatmap-device-select">' + Drupal.t('Dispositivo') + '</label>' +
            '<select id="heatmap-device-select" class="heatmap-select">' +
            '<option value="all">' + Drupal.t('Todos') + '</option>' +
            '<option value="desktop">' + Drupal.t('Escritorio') + '</option>' +
            '<option value="tablet">' + Drupal.t('Tablet') + '</option>' +
            '<option value="mobile">' + Drupal.t('Móvil') + '</option>' +
            '</select>' +
            '</div>' +
            '<div class="heatmap-control-group">' +
            '<button type="button" class="heatmap-btn heatmap-btn-refresh">' +
            Drupal.t('Actualizar') +
            '</button>' +
            '</div>';
    };

    /**
     * Vincula eventos de los controles.
     */
    JarabaHeatmapViewer.prototype.bindEvents = function () {
        var self = this;

        // Selector de página.
        var pageSelect = this.container.querySelector('#heatmap-page-select');
        if (pageSelect) {
            pageSelect.addEventListener('change', function () {
                self.loadHeatmap();
            });
        }

        // Botón actualizar (buscar ambos selectores: clase del JS y ID del template).
        var refreshBtn = this.container.querySelector('.heatmap-btn-refresh, #heatmap-refresh-btn, .heatmap-viewer__refresh');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function () {
                self.loadHeatmap();
            });
        }
    };

    /**
     * Carga la lista de páginas disponibles.
     */
    JarabaHeatmapViewer.prototype.loadPages = function () {
        var self = this;
        var select = this.container.querySelector('#heatmap-page-select');

        fetch('/api/v1/heatmap/pages')
            .then(function (response) {
                return response.json();
            })
            .then(function (result) {
                if (result.success && result.data) {
                    result.data.forEach(function (page) {
                        var option = document.createElement('option');
                        // Usar ID para las llamadas a la API (path no funciona con slashes).
                        option.value = page.id || page.path;
                        // Mostrar título si existe, sino usar path.
                        var label = page.title || page.path;
                        var eventsInfo = page.events > 0 ? ' (' + page.events + ' eventos)' : ' (sin datos)';
                        option.textContent = label + eventsInfo;
                        select.appendChild(option);
                    });
                }
            })
            .catch(function (error) {
                console.error('[JarabaHeatmap] Error cargando páginas:', error);
            });
    };

    /**
     * Carga y renderiza el heatmap para la página seleccionada.
     */
    JarabaHeatmapViewer.prototype.loadHeatmap = function () {
        var self = this;

        // Buscar selectores con fallbacks (template usa IDs diferentes a los dinámicos).
        var pageEl = this.container.querySelector('#heatmap-page-select');
        var typeEl = this.container.querySelector('#heatmap-type-select');
        var daysEl = this.container.querySelector('#heatmap-period-select, #heatmap-days-select');
        var deviceEl = this.container.querySelector('#heatmap-device-select');

        var page = pageEl ? pageEl.value : '';
        var type = typeEl ? typeEl.value : 'click';
        var days = daysEl ? daysEl.value : '7';
        var device = deviceEl ? deviceEl.value : 'all';

        if (!page) {
            this.showEmpty();
            return;
        }

        // Mapear tipo a endpoint correcto.
        var endpointType;
        switch (type) {
            case 'click':
                endpointType = 'clicks';
                break;
            case 'scroll':
                endpointType = 'scroll';
                break;
            case 'movement':
            default:
                endpointType = 'movement';
                break;
        }

        // El valor de page ahora es un ID numérico, no necesita encoding.
        var endpoint = '/api/v1/heatmap/pages/' + page + '/' + endpointType;
        var url = endpoint + '?days=' + days + '&device=' + device;

        fetch(url)
            .then(function (response) {
                return response.json();
            })
            .then(function (result) {
                if (result.success && result.buckets) {
                    self.currentData = result;
                    self.renderHeatmap(result.buckets, result.maxCount);
                }
                else {
                    self.showEmpty(Drupal.t('No hay datos para esta página'));
                }
            })
            .catch(function (error) {
                console.error('[JarabaHeatmap] Error cargando heatmap:', error);
                self.showEmpty(Drupal.t('Error al cargar datos'));
            });
    };

    /**
     * Renderiza el heatmap en el canvas.
     *
     * @param {Array} buckets
     *   Array de buckets con datos de intensidad.
     * @param {number} maxCount
     *   Máximo conteo para normalización.
     */
    JarabaHeatmapViewer.prototype.renderHeatmap = function (buckets, maxCount) {
        // Mostrar canvas, ocultar mensaje vacío.
        this.emptyMessage.style.display = 'none';
        this.canvas.parentElement.style.display = 'block';

        // Calcular dimensiones del canvas.
        var maxY = 0;
        buckets.forEach(function (b) {
            if (b.y > maxY) {
                maxY = b.y;
            }
        });

        var width = 1280;
        var height = Math.max(900, (maxY + 1) * this.config.bucketHeight);

        this.canvas.width = width;
        this.canvas.height = height;

        // Fondo blanco.
        this.ctx.fillStyle = '#ffffff';
        this.ctx.fillRect(0, 0, width, height);

        // Renderizar puntos de calor.
        var self = this;
        buckets.forEach(function (bucket) {
            self.drawHeatPoint(bucket, width, height);
        });
    };

    /**
     * Dibuja un punto de calor con gradiente radial.
     *
     * @param {object} bucket
     *   Datos del bucket: x, y, intensity.
     * @param {number} canvasWidth
     *   Ancho del canvas.
     * @param {number} canvasHeight
     *   Alto del canvas.
     */
    JarabaHeatmapViewer.prototype.drawHeatPoint = function (bucket, canvasWidth, canvasHeight) {
        // Calcular posición central del bucket.
        var x = (bucket.x * this.config.bucketWidth / 100 + this.config.bucketWidth / 200) * canvasWidth;
        var y = bucket.y * this.config.bucketHeight + this.config.bucketHeight / 2;

        var intensity = bucket.intensity;
        var radius = this.config.radius * (0.5 + intensity * 0.5);

        // Crear gradiente radial.
        var gradient = this.ctx.createRadialGradient(x, y, 0, x, y, radius);

        // Aplicar colores según intensidad.
        var color = this.getColorForIntensity(intensity);
        gradient.addColorStop(0, color);
        gradient.addColorStop(1, 'rgba(0, 0, 0, 0)');

        // Dibujar círculo.
        this.ctx.globalAlpha = this.config.maxOpacity * intensity;
        this.ctx.fillStyle = gradient;
        this.ctx.beginPath();
        this.ctx.arc(x, y, radius, 0, Math.PI * 2);
        this.ctx.fill();
        this.ctx.globalAlpha = 1;
    };

    /**
     * Obtiene el color para una intensidad dada.
     *
     * @param {number} intensity
     *   Valor de intensidad (0-1).
     * @return {string}
     *   Color CSS.
     */
    JarabaHeatmapViewer.prototype.getColorForIntensity = function (intensity) {
        // Gradiente de azul -> verde -> amarillo -> rojo.
        if (intensity < 0.25) {
            return 'rgba(0, 0, 255, 0.6)';
        }
        if (intensity < 0.5) {
            return 'rgba(0, 255, 0, 0.6)';
        }
        if (intensity < 0.75) {
            return 'rgba(255, 255, 0, 0.7)';
        }
        return 'rgba(255, 0, 0, 0.8)';
    };

    /**
     * Muestra mensaje de estado vacío.
     *
     * @param {string} message
     *   Mensaje opcional a mostrar.
     */
    JarabaHeatmapViewer.prototype.showEmpty = function (message) {
        this.canvas.parentElement.style.display = 'none';
        this.emptyMessage.style.display = 'flex';
        if (message) {
            this.emptyMessage.textContent = message;
        }
    };

    // Exponer constructor globalmente para debugging.
    window.JarabaHeatmapViewer = JarabaHeatmapViewer;

})(Drupal, drupalSettings, once);
