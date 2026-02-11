/**
 * @file
 * Tracker de Heatmaps para Jaraba SaaS.
 *
 * Captura interacciones de usuario (clicks, scroll, movimiento)
 * y las envía al servidor via Beacon API para mínima latencia.
 *
 * Características:
 * - Throttling configurable por tipo de evento
 * - Buffer de eventos con flush automático
 * - Detección de tipo de dispositivo
 * - IntersectionObserver para visibilidad
 * - Respeta Do Not Track
 *
 * Ref: Doc Técnico #180 - Native Heatmaps System
 */

(function (Drupal, drupalSettings, once) {
    'use strict';

    /**
     * Behavior de tracking de heatmaps.
     *
     * @type {Drupal~behavior}
     */
    Drupal.behaviors.jarabaHeatmap = {
        attach: function (context, settings) {
            // Solo ejecutar una vez en el documento.
            once('jarabaHeatmap', 'body', context).forEach(function () {
                var config = drupalSettings.jarabaHeatmap || {};

                // Verificar que el tracking está habilitado.
                if (!config.enabled) {
                    return;
                }

                // Verificar Do Not Track del navegador.
                if (navigator.doNotTrack === '1' || window.doNotTrack === '1') {
                    return;
                }

                // Inicializar tracker.
                JarabaHeatmapTracker.init(config);
            });
        }
    };

    /**
     * Tracker principal de heatmaps.
     */
    var JarabaHeatmapTracker = {

        /**
         * Configuración del tracker.
         */
        config: {
            endpoint: '/api/heatmap/collect',
            tenantId: 0,
            sessionId: '',
            bufferSize: 50,
            flushInterval: 10000,
            throttleMove: 100,
            throttleScroll: 200
        },

        /**
         * Buffer de eventos pendientes.
         */
        buffer: [],

        /**
         * Timestamps para throttling.
         */
        lastMove: 0,
        lastScroll: 0,

        /**
         * Estado de scroll depth alcanzado.
         */
        maxScrollDepth: 0,
        scrollMilestones: { 25: false, 50: false, 75: false, 100: false },

        /**
         * Timer de flush automático.
         */
        flushTimer: null,

        /**
         * Flag de inicialización.
         */
        initialized: false,

        /**
         * Inicializa el tracker con la configuración proporcionada.
         *
         * @param {object} config
         *   Configuración desde drupalSettings.
         */
        init: function (config) {
            if (this.initialized) {
                return;
            }

            // Merge configuración.
            Object.assign(this.config, config);

            // Detectar dispositivo.
            this.deviceType = this.detectDevice();

            // Obtener dimensiones del viewport.
            this.viewport = {
                w: window.innerWidth,
                h: window.innerHeight
            };

            // Obtener path de la página.
            this.pagePath = window.location.pathname + window.location.search;

            // Bind event listeners.
            this.bindEvents();

            // Iniciar timer de flush automático.
            this.startFlushTimer();

            // Flush al cerrar página.
            this.bindUnload();

            this.initialized = true;

            // Log en desarrollo.
            if (drupalSettings.debug) {
                console.log('[JarabaHeatmap] Tracker inicializado', this.config);
            }
        },

        /**
         * Detecta el tipo de dispositivo basándose en viewport.
         *
         * @return {string}
         *   'mobile', 'tablet' o 'desktop'.
         */
        detectDevice: function () {
            var width = window.innerWidth;
            if (width < 768) {
                return 'mobile';
            }
            if (width < 1024) {
                return 'tablet';
            }
            return 'desktop';
        },

        /**
         * Vincula los event listeners para tracking.
         */
        bindEvents: function () {
            var self = this;

            // Click tracking.
            document.addEventListener('click', function (e) {
                self.trackClick(e);
            }, { passive: true });

            // Mouse movement tracking (throttled).
            document.addEventListener('mousemove', function (e) {
                self.trackMove(e);
            }, { passive: true });

            // Scroll tracking (throttled).
            window.addEventListener('scroll', function () {
                self.trackScroll();
            }, { passive: true });

            // Resize - actualizar viewport.
            window.addEventListener('resize', function () {
                self.viewport = {
                    w: window.innerWidth,
                    h: window.innerHeight
                };
                self.deviceType = self.detectDevice();
            }, { passive: true });
        },

        /**
         * Captura evento de click.
         *
         * @param {MouseEvent} e
         *   Evento de click.
         */
        trackClick: function (e) {
            var scrollY = window.scrollY || window.pageYOffset;
            var target = e.target;

            // Obtener selector del elemento.
            var selector = this.getSelector(target);
            var text = this.getElementText(target);

            this.addEvent({
                t: 'click',
                x: Math.round(e.clientX),
                y: Math.round(e.clientY + scrollY),
                el: selector,
                txt: text
            });
        },

        /**
         * Captura evento de movimiento de mouse.
         *
         * @param {MouseEvent} e
         *   Evento de movimiento.
         */
        trackMove: function (e) {
            var now = Date.now();

            // Aplicar throttling.
            if (now - this.lastMove < this.config.throttleMove) {
                return;
            }
            this.lastMove = now;

            var scrollY = window.scrollY || window.pageYOffset;

            this.addEvent({
                t: 'move',
                x: Math.round(e.clientX),
                y: Math.round(e.clientY + scrollY)
            });
        },

        /**
         * Captura evento de scroll.
         */
        trackScroll: function () {
            var now = Date.now();

            // Aplicar throttling.
            if (now - this.lastScroll < this.config.throttleScroll) {
                return;
            }
            this.lastScroll = now;

            // Calcular profundidad de scroll.
            var scrollY = window.scrollY || window.pageYOffset;
            var docHeight = Math.max(
                document.body.scrollHeight,
                document.documentElement.scrollHeight
            );
            var viewportHeight = window.innerHeight;
            var scrollableHeight = docHeight - viewportHeight;

            var depth = 0;
            if (scrollableHeight > 0) {
                depth = Math.round(scrollY / scrollableHeight * 100);
                depth = Math.min(100, Math.max(0, depth));
            }

            // Actualizar máximo alcanzado.
            if (depth > this.maxScrollDepth) {
                this.maxScrollDepth = depth;
            }

            // Enviar evento en milestones (25%, 50%, 75%, 100%).
            var milestones = [25, 50, 75, 100];
            for (var i = 0; i < milestones.length; i++) {
                var m = milestones[i];
                if (depth >= m && !this.scrollMilestones[m]) {
                    this.scrollMilestones[m] = true;
                    this.addEvent({
                        t: 'scroll',
                        d: m,
                        y: Math.round(scrollY)
                    });
                }
            }
        },

        /**
         * Añade un evento al buffer.
         *
         * @param {object} event
         *   Datos del evento.
         */
        addEvent: function (event) {
            this.buffer.push(event);

            // Flush cuando se alcanza el tamaño del buffer.
            if (this.buffer.length >= this.config.bufferSize) {
                this.flush();
            }
        },

        /**
         * Envía los eventos del buffer al servidor.
         */
        flush: function () {
            if (this.buffer.length === 0) {
                return;
            }

            // Preparar payload.
            var payload = {
                tenant_id: this.config.tenantId,
                session_id: this.config.sessionId,
                page: this.pagePath,
                viewport: this.viewport,
                device: this.deviceType,
                events: this.buffer.slice()
            };

            // Limpiar buffer.
            this.buffer = [];

            // Enviar via Beacon API (preferido) o fetch.
            this.sendPayload(payload);
        },

        /**
         * Envía el payload al servidor.
         *
         * @param {object} payload
         *   Datos a enviar.
         */
        sendPayload: function (payload) {
            var endpoint = this.config.endpoint;
            var data = JSON.stringify(payload);

            // Intentar Beacon API primero (mejor para unload).
            if (navigator.sendBeacon) {
                var blob = new Blob([data], { type: 'application/json' });
                var sent = navigator.sendBeacon(endpoint, blob);

                if (sent) {
                    return;
                }
            }

            // Fallback a fetch con keepalive.
            if (window.fetch) {
                fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: data,
                    keepalive: true
                }).catch(function () {
                    // Ignorar errores silenciosamente.
                });
            }
        },

        /**
         * Inicia el timer de flush automático.
         */
        startFlushTimer: function () {
            var self = this;

            if (this.flushTimer) {
                clearInterval(this.flushTimer);
            }

            this.flushTimer = setInterval(function () {
                self.flush();
            }, this.config.flushInterval);
        },

        /**
         * Vincula flush al descargar la página.
         */
        bindUnload: function () {
            var self = this;

            // Usar visibilitychange para mejor compatibilidad.
            document.addEventListener('visibilitychange', function () {
                if (document.visibilityState === 'hidden') {
                    self.flush();
                }
            });

            // Fallback con pagehide.
            window.addEventListener('pagehide', function () {
                self.flush();
            });
        },

        /**
         * Genera un selector CSS para un elemento.
         *
         * @param {Element} el
         *   Elemento DOM.
         * @return {string}
         *   Selector CSS simplificado.
         */
        getSelector: function (el) {
            if (!el || el === document.body) {
                return 'body';
            }

            var selector = el.tagName.toLowerCase();

            if (el.id) {
                return '#' + el.id;
            }

            if (el.className && typeof el.className === 'string') {
                var classes = el.className.trim().split(/\s+/).slice(0, 2);
                if (classes.length > 0 && classes[0]) {
                    selector += '.' + classes.join('.');
                }
            }

            return selector.substring(0, 100);
        },

        /**
         * Obtiene el texto visible de un elemento (truncado).
         *
         * @param {Element} el
         *   Elemento DOM.
         * @return {string}
         *   Texto truncado a 100 caracteres.
         */
        getElementText: function (el) {
            if (!el) {
                return '';
            }

            var text = el.textContent || el.innerText || '';
            text = text.trim().replace(/\s+/g, ' ');

            return text.substring(0, 100);
        }

    };

    // Exponer globalmente para debugging.
    window.JarabaHeatmapTracker = JarabaHeatmapTracker;

})(Drupal, drupalSettings, once);
