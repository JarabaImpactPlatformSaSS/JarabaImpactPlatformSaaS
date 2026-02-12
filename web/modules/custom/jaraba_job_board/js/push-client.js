/**
 * @file
 * Cliente de Web Push para suscripción del navegador.
 */

(function (Drupal, drupalSettings) {
    'use strict';

    /**
     * Inicializa Web Push.
     */
    Drupal.behaviors.jarabaPushNotifications = {
        attach: function (context, settings) {
            if (context !== document) {
                return;
            }

            // Verificar soporte del navegador
            if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                console.log('[Push] Navegador no soporta Web Push');
                return;
            }

            // Obtener VAPID key desde settings
            const vapidKey = settings.jaraba_job_board?.vapid_public_key;
            if (!vapidKey) {
                console.log('[Push] VAPID key no configurada');
                return;
            }

            // Registrar Service Worker
            this.registerServiceWorker(vapidKey);
        },

        /**
         * Registra el Service Worker.
         */
        registerServiceWorker: async function (vapidKey) {
            try {
                const registration = await navigator.serviceWorker.register(
                    '/modules/custom/jaraba_job_board/js/push-sw.js',
                    { scope: '/' }
                );
                console.log('[Push] Service Worker registrado:', registration.scope);

                // Esperar a que esté activo
                await navigator.serviceWorker.ready;

                // Verificar permiso actual
                const permission = Notification.permission;
                if (permission === 'denied') {
                    console.log('[Push] Permiso denegado');
                    return;
                }

                // Verificar si ya está suscrito
                const subscription = await registration.pushManager.getSubscription();
                if (subscription) {
                    console.log('[Push] Ya suscrito:', subscription.endpoint.substring(0, 50));
                    return;
                }

                // Si tiene permiso granted, suscribir automáticamente
                if (permission === 'granted') {
                    await this.subscribe(registration, vapidKey);
                }
            } catch (error) {
                console.error('[Push] Error registrando SW:', error);
            }
        },

        /**
         * Solicita permiso y suscribe al usuario.
         */
        requestPermissionAndSubscribe: async function () {
            try {
                const permission = await Notification.requestPermission();
                if (permission !== 'granted') {
                    console.log('[Push] Permiso no concedido:', permission);
                    return false;
                }

                const registration = await navigator.serviceWorker.ready;
                const vapidKey = drupalSettings.jaraba_job_board?.vapid_public_key;

                if (vapidKey) {
                    await this.subscribe(registration, vapidKey);
                    return true;
                }
            } catch (error) {
                console.error('[Push] Error solicitando permiso:', error);
            }
            return false;
        },

        /**
         * Suscribe al usuario.
         */
        subscribe: async function (registration, vapidKey) {
            try {
                const subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: this.urlBase64ToUint8Array(vapidKey)
                });

                console.log('[Push] Suscrito exitosamente');

                // Enviar suscripción al servidor
                await this.sendSubscriptionToServer(subscription);
            } catch (error) {
                console.error('[Push] Error suscribiendo:', error);
            }
        },

        /**
         * Envía la suscripción al servidor.
         */
        sendSubscriptionToServer: async function (subscription) {
            try {
                const response = await fetch('/api/v1/push/subscribe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': drupalSettings.csrfToken || ''
                    },
                    body: JSON.stringify(subscription.toJSON())
                });

                if (response.ok) {
                    console.log('[Push] Suscripción guardada en servidor');
                } else {
                    console.error('[Push] Error guardando suscripción:', response.status);
                }
            } catch (error) {
                console.error('[Push] Error enviando suscripción:', error);
            }
        },

        /**
         * Convierte VAPID key de base64 a Uint8Array.
         */
        urlBase64ToUint8Array: function (base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding)
                .replace(/-/g, '+')
                .replace(/_/g, '/');

            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);

            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }
    };

    /**
     * API global para componentes.
     */
    Drupal.jarabaPush = {
        /**
         * Solicita permiso de notificaciones.
         */
        requestPermission: function () {
            return Drupal.behaviors.jarabaPushNotifications.requestPermissionAndSubscribe();
        },

        /**
         * Verifica si las notificaciones están habilitadas.
         */
        isEnabled: function () {
            return Notification.permission === 'granted';
        },

        /**
         * Verifica soporte del navegador.
         */
        isSupported: function () {
            return 'serviceWorker' in navigator && 'PushManager' in window;
        }
    };

})(Drupal, drupalSettings);
