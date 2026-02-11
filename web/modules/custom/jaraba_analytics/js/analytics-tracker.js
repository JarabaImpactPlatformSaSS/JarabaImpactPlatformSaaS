/**
 * @file
 * Jaraba Analytics - JavaScript Tracker.
 *
 * Sistema de tracking 100% nativo para captura de eventos en frontend.
 * Implementa Beacon API para envío no bloqueante.
 *
 * @see Doc 178: Native Tracking Architecture
 */

(function (Drupal, drupalSettings) {
    'use strict';

    /**
     * Namespace principal del tracker.
     */
    Drupal.jarabaAnalytics = Drupal.jarabaAnalytics || {};

    /**
     * Configuración del tracker.
     */
    const config = {
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
    let eventQueue = [];

    /**
     * ID del visitante (persistente).
     */
    let visitorId = null;

    /**
     * ID de sesión (expira con inactividad).
     */
    let sessionId = null;

    /**
     * Timestamp de última actividad.
     */
    let lastActivity = Date.now();

    /**
     * Genera un UUID v4.
     *
     * @returns {string} UUID generado.
     */
    function generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
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
        const now = Date.now();

        // Verificar si la sesión expiró.
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
     * Obtiene información del dispositivo.
     *
     * @returns {Object} Información del dispositivo.
     */
    function getDeviceInfo() {
        const ua = navigator.userAgent;
        let deviceType = 'desktop';

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
     * Obtiene parámetros UTM de la URL.
     *
     * @returns {Object} Parámetros UTM.
     */
    function getUTMParams() {
        const params = new URLSearchParams(window.location.search);
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
    function debug(...args) {
        if (config.debug) {
            console.log('[Jaraba Analytics]', ...args);
        }
    }

    /**
     * Envía un evento al servidor.
     *
     * @param {Object} eventData Datos del evento.
     * @returns {boolean} True si se envió correctamente.
     */
    function sendEvent(eventData) {
        const payload = JSON.stringify(eventData);

        // Usar Beacon API si está disponible (no bloqueante).
        if (navigator.sendBeacon) {
            const blob = new Blob([payload], { type: 'application/json' });
            const sent = navigator.sendBeacon(config.endpoint, blob);
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
        }).then(response => {
            debug('Fetch response:', response.status, eventData.event_type);
        }).catch(error => {
            console.error('[Jaraba Analytics] Error:', error);
        });

        return true;
    }

    /**
     * Envía eventos en batch.
     */
    function flushQueue() {
        if (eventQueue.length === 0) {
            return;
        }

        const events = eventQueue.splice(0, config.batchSize);
        const payload = JSON.stringify({ events });

        if (navigator.sendBeacon) {
            const blob = new Blob([payload], { type: 'application/json' });
            navigator.sendBeacon(config.batchEndpoint, blob);
            debug('Batch sent:', events.length, 'events');
        } else {
            fetch(config.batchEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: payload,
                keepalive: true,
            }).catch(error => console.error('[Jaraba Analytics] Batch error:', error));
        }
    }

    /**
     * Registra un evento de analytics.
     *
     * @param {string} eventType Tipo de evento.
     * @param {Object} data Datos adicionales del evento.
     * @param {boolean} immediate Enviar inmediatamente (no usar cola).
     */
    Drupal.jarabaAnalytics.track = function (eventType, data = {}, immediate = false) {
        const eventData = {
            event_type: eventType,
            data: {
                ...data,
                ...getDeviceInfo(),
                ...getUTMParams(),
                page_url: window.location.href,
                page_title: document.title,
                referrer: document.referrer,
                timestamp: Date.now(),
            },
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
     * Registra añadir al carrito.
     *
     * @param {Object} product Producto añadido.
     * @param {number} quantity Cantidad.
     */
    Drupal.jarabaAnalytics.trackAddToCart = function (product, quantity = 1) {
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
    Drupal.jarabaAnalytics.trackRemoveFromCart = function (product, quantity = 1) {
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
     * Registra búsqueda.
     *
     * @param {string} query Término de búsqueda.
     * @param {number} resultsCount Número de resultados.
     */
    Drupal.jarabaAnalytics.trackSearch = function (query, resultsCount = 0) {
        this.track('search', {
            query: query,
            results_count: resultsCount,
        });
    };

    /**
     * Registra conversión de lead.
     *
     * @param {string} formName Nombre del formulario.
     * @param {string} leadType Tipo de lead.
     */
    Drupal.jarabaAnalytics.trackLead = function (formName, leadType = 'contact') {
        this.track('lead', {
            form_name: formName,
            lead_type: leadType,
        }, true);
    };

    /**
     * Registra registro de usuario.
     *
     * @param {string} method Método de registro.
     */
    Drupal.jarabaAnalytics.trackSignup = function (method = 'email') {
        this.track('signup', {
            method: method,
        }, true);
    };

    /**
     * Registra login.
     *
     * @param {string} method Método de login.
     */
    Drupal.jarabaAnalytics.trackLogin = function (method = 'email') {
        this.track('login', {
            method: method,
        });
    };

    // ========================================
    // INICIALIZACIÓN
    // ========================================

    /**
     * Inicializa el tracker.
     */
    function init() {
        // Obtener IDs al inicio.
        getVisitorId();
        getSessionId();

        // Registrar page view automático si está habilitado.
        if (drupalSettings?.jarabaAnalytics?.autoTrackPageViews !== false) {
            Drupal.jarabaAnalytics.trackPageView();
        }

        // Flush periódico de la cola.
        setInterval(flushQueue, config.flushInterval);

        // Flush al cerrar la página.
        window.addEventListener('beforeunload', flushQueue);

        // Tracking de clicks en enlaces externos.
        document.addEventListener('click', function (e) {
            const link = e.target.closest('a');
            if (link && link.hostname !== window.location.hostname) {
                Drupal.jarabaAnalytics.track('outbound_click', {
                    url: link.href,
                    text: link.textContent?.substring(0, 100),
                }, true);
            }
        });

        debug('Tracker initialized');
    }

    // Inicializar cuando el DOM esté listo.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})(Drupal, drupalSettings);
