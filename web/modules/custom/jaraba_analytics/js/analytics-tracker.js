/**
 * @file
 * Jaraba Analytics - JavaScript Tracker.
 *
 * Sistema de tracking 100% nativo para captura de eventos en frontend.
 * Implementa Beacon API para envio no bloqueante.
 *
 * AUDIT-CONS-N12: Migrated from IIFE to Drupal.behaviors for AJAX compatibility.
 *
 * @see Doc 178: Native Tracking Architecture
 */

(function (Drupal, drupalSettings, once) {
    'use strict';

    /**
     * Namespace principal del tracker.
     */
    Drupal.jarabaAnalytics = Drupal.jarabaAnalytics || {};

    /**
     * Configuracion del tracker.
     */
    var config = {
        endpoint: '/api/v1/analytics/event',
        batchEndpoint: '/api/v1/analytics/batch',
        visitorIdKey: 'jaraba_visitor_id',
        sessionIdKey: 'jaraba_session_id',
        sessionTimeout: 30 * 60 * 1000, // 30 minutos.
        batchSize: 10,
        flushInterval: 5000, // 5 segundos.
        debug: drupalSettings?.jarabaAnalytics?.debug || false,
    };

    /**
     * Cola de eventos pendientes.
     */
    var eventQueue = [];

    /**
     * ID del visitante (persistente).
     */
    var visitorId = null;

    /**
     * ID de sesion (expira con inactividad).
     */
    var sessionId = null;

    /**
     * Timestamp de ultima actividad.
     */
    var lastActivity = Date.now();

    /**
     * Genera un UUID v4.
     *
     * @returns {string} UUID generado.
     */
    function generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0;
            var v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    /**
     * Obtiene o genera el visitor ID.
     *
     * @returns {string} Visitor ID.
     */
    function getVisitorId() {
        if (visitorId) {
            return visitorId;
        }

        // Intentar recuperar de localStorage.
        visitorId = localStorage.getItem(config.visitorIdKey);

        if (!visitorId) {
            visitorId = generateUUID();
            localStorage.setItem(config.visitorIdKey, visitorId);
        }

        return visitorId;
    }

    /**
     * Obtiene o genera el session ID.
     *
     * @returns {string} Session ID.
     */
    function getSessionId() {
        var now = Date.now();

        // Verificar si la sesion expiro.
        if (sessionId && (now - lastActivity) > config.sessionTimeout) {
            sessionId = null;
            sessionStorage.removeItem(config.sessionIdKey);
        }

        if (sessionId) {
            lastActivity = now;
            return sessionId;
        }

        // Intentar recuperar de sessionStorage.
        sessionId = sessionStorage.getItem(config.sessionIdKey);

        if (!sessionId) {
            sessionId = generateUUID();
            sessionStorage.setItem(config.sessionIdKey, sessionId);
        }

        lastActivity = now;
        return sessionId;
    }

    /**
     * Obtiene informacion del dispositivo.
     *
     * @returns {Object} Informacion del dispositivo.
     */
    function getDeviceInfo() {
        var ua = navigator.userAgent;
        var deviceType = 'desktop';

        if (/Mobile|Android|iPhone|iPad|iPod/i.test(ua)) {
            deviceType = /iPad|Tablet/i.test(ua) ? 'tablet' : 'mobile';
        }

        return {
            device_type: deviceType,
            screen_width: window.screen.width,
            screen_height: window.screen.height,
            viewport_width: window.innerWidth,
            viewport_height: window.innerHeight,
            language: navigator.language,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        };
    }

    /**
     * Obtiene parametros UTM de la URL.
     *
     * @returns {Object} Parametros UTM.
     */
    function getUTMParams() {
        var params = new URLSearchParams(window.location.search);
        return {
            utm_source: params.get('utm_source') || null,
            utm_medium: params.get('utm_medium') || null,
            utm_campaign: params.get('utm_campaign') || null,
            utm_content: params.get('utm_content') || null,
            utm_term: params.get('utm_term') || null,
        };
    }

    /**
     * Log de debug.
     *
     * @param {...any} args Argumentos a loguear.
     */
    function debug() {
        if (config.debug) {
            var args = Array.prototype.slice.call(arguments);
            args.unshift('[Jaraba Analytics]');
            console.log.apply(console, args);
        }
    }

    /**
     * Envia un evento al servidor.
     *
     * @param {Object} eventData Datos del evento.
     * @returns {boolean} True si se envio correctamente.
     */
    function sendEvent(eventData) {
        var payload = JSON.stringify(eventData);

        // Usar Beacon API si esta disponible (no bloqueante).
        if (navigator.sendBeacon) {
            var blob = new Blob([payload], { type: 'application/json' });
            var sent = navigator.sendBeacon(config.endpoint, blob);
            debug('Beacon sent:', sent, eventData.event_type);
            return sent;
        }

        // Fallback a fetch.
        fetch(config.endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: payload,
            keepalive: true,
        }).then(function (response) {
            debug('Fetch response:', response.status, eventData.event_type);
        }).catch(function (error) {
            console.error('[Jaraba Analytics] Error:', error);
        });

        return true;
    }

    /**
     * Envia eventos en batch.
     */
    function flushQueue() {
        if (eventQueue.length === 0) {
            return;
        }

        var events = eventQueue.splice(0, config.batchSize);
        var payload = JSON.stringify({ events: events });

        if (navigator.sendBeacon) {
            var blob = new Blob([payload], { type: 'application/json' });
            navigator.sendBeacon(config.batchEndpoint, blob);
            debug('Batch sent:', events.length, 'events');
        } else {
            fetch(config.batchEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: payload,
                keepalive: true,
            }).catch(function (error) { console.error('[Jaraba Analytics] Batch error:', error); });
        }
    }

    /**
     * Registra un evento de analytics.
     *
     * @param {string} eventType Tipo de evento.
     * @param {Object} data Datos adicionales del evento.
     * @param {boolean} immediate Enviar inmediatamente (no usar cola).
     */
    Drupal.jarabaAnalytics.track = function (eventType, data, immediate) {
        data = data || {};
        immediate = immediate || false;

        var deviceInfo = getDeviceInfo();
        var utmParams = getUTMParams();
        var eventData = {
            event_type: eventType,
            data: Object.assign({}, data, deviceInfo, utmParams, {
                page_url: window.location.href,
                page_title: document.title,
                referrer: document.referrer,
                timestamp: Date.now(),
            }),
            visitor_id: getVisitorId(),
            session_id: getSessionId(),
            tenant_id: drupalSettings?.jarabaAnalytics?.tenantId || null,
        };

        if (immediate) {
            sendEvent(eventData);
        } else {
            eventQueue.push(eventData);

            if (eventQueue.length >= config.batchSize) {
                flushQueue();
            }
        }

        debug('Tracked:', eventType, data);
    };

    /**
     * Registra un page view.
     */
    Drupal.jarabaAnalytics.trackPageView = function () {
        this.track('page_view', {}, true);
    };

    // ========================================
    // EVENTOS E-COMMERCE
    // ========================================

    /**
     * Registra vista de producto.
     *
     * @param {Object} product Datos del producto.
     */
    Drupal.jarabaAnalytics.trackProductView = function (product) {
        this.track('product_view', {
            product_id: product.id,
            product_name: product.name,
            product_price: product.price,
            product_category: product.category,
            product_brand: product.brand,
        });
    };

    /**
     * Registra anadir al carrito.
     *
     * @param {Object} product Producto anadido.
     * @param {number} quantity Cantidad.
     */
    Drupal.jarabaAnalytics.trackAddToCart = function (product, quantity) {
        quantity = quantity || 1;
        this.track('add_to_cart', {
            product_id: product.id,
            product_name: product.name,
            product_price: product.price,
            quantity: quantity,
            cart_value: product.price * quantity,
        }, true);
    };

    /**
     * Registra eliminar del carrito.
     *
     * @param {Object} product Producto eliminado.
     * @param {number} quantity Cantidad.
     */
    Drupal.jarabaAnalytics.trackRemoveFromCart = function (product, quantity) {
        quantity = quantity || 1;
        this.track('remove_from_cart', {
            product_id: product.id,
            quantity: quantity,
        }, true);
    };

    /**
     * Registra inicio de checkout.
     *
     * @param {Array} items Items del carrito.
     * @param {number} value Valor total.
     */
    Drupal.jarabaAnalytics.trackBeginCheckout = function (items, value) {
        this.track('begin_checkout', {
            items: items,
            item_count: items.length,
            cart_value: value,
        }, true);
    };

    /**
     * Registra compra completada.
     *
     * @param {Object} order Datos del pedido.
     */
    Drupal.jarabaAnalytics.trackPurchase = function (order) {
        this.track('purchase', {
            order_id: order.id,
            order_value: order.value,
            items: order.items,
            tax: order.tax || 0,
            shipping: order.shipping || 0,
            coupon: order.coupon || null,
        }, true);
    };

    /**
     * Registra busqueda.
     *
     * @param {string} query Termino de busqueda.
     * @param {number} resultsCount Numero de resultados.
     */
    Drupal.jarabaAnalytics.trackSearch = function (query, resultsCount) {
        resultsCount = resultsCount || 0;
        this.track('search', {
            query: query,
            results_count: resultsCount,
        });
    };

    /**
     * Registra conversion de lead.
     *
     * @param {string} formName Nombre del formulario.
     * @param {string} leadType Tipo de lead.
     */
    Drupal.jarabaAnalytics.trackLead = function (formName, leadType) {
        leadType = leadType || 'contact';
        this.track('lead', {
            form_name: formName,
            lead_type: leadType,
        }, true);
    };

    /**
     * Registra registro de usuario.
     *
     * @param {string} method Metodo de registro.
     */
    Drupal.jarabaAnalytics.trackSignup = function (method) {
        method = method || 'email';
        this.track('signup', {
            method: method,
        }, true);
    };

    /**
     * Registra login.
     *
     * @param {string} method Metodo de login.
     */
    Drupal.jarabaAnalytics.trackLogin = function (method) {
        method = method || 'email';
        this.track('login', {
            method: method,
        });
    };

    // ========================================
    // AUDIT-CONS-N12: Drupal.behaviors initialization
    // ========================================

    /**
     * Behavior para inicializar el analytics tracker.
     *
     * Se ejecuta en el primer attach (page load) y en cada AJAX attach
     * gracias a Drupal.behaviors. once() previene la doble inicializacion
     * del tracker (setInterval, event listeners, page view).
     *
     * @type {Drupal~behavior}
     */
    Drupal.behaviors.jarabaAnalyticsTracker = {
        attach: function (context) {
            // AUDIT-CONS-N12: once() prevents double initialization on AJAX.
            once('jaraba-analytics-tracker', 'body', context).forEach(function () {
                // Obtener IDs al inicio.
                getVisitorId();
                getSessionId();

                // Registrar page view automatico si esta habilitado.
                if (drupalSettings?.jarabaAnalytics?.autoTrackPageViews !== false) {
                    Drupal.jarabaAnalytics.trackPageView();
                }

                // Flush periodico de la cola.
                setInterval(flushQueue, config.flushInterval);

                // Flush al cerrar la pagina.
                window.addEventListener('beforeunload', flushQueue);

                // Tracking de clicks en enlaces externos.
                document.addEventListener('click', function (e) {
                    var link = e.target.closest('a');
                    if (link && link.hostname !== window.location.hostname) {
                        Drupal.jarabaAnalytics.track('outbound_click', {
                            url: link.href,
                            text: link.textContent ? link.textContent.substring(0, 100) : '',
                        }, true);
                    }
                });

                debug('Tracker initialized');
            });
        }
    };

})(Drupal, drupalSettings, once);
