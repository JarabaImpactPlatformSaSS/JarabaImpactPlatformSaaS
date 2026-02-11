/**
 * Jaraba PWA Registration
 * 
 * Registra el Service Worker y maneja funcionalidades PWA:
 * - Service Worker registration
 * - Install prompt handling
 * - Push notification subscription
 */
(function (Drupal, drupalSettings) {
    'use strict';

    Drupal.behaviors.jarabaPwa = {
        attach: function (context, settings) {
            if (context !== document) {
                return;
            }

            const PWA = {
                deferredPrompt: null,
                swRegistration: null,

                /**
                 * Initialize PWA features.
                 */
                init: function () {
                    this.registerServiceWorker();
                    this.handleInstallPrompt();
                    this.checkOnlineStatus();
                },

                /**
                 * Register the Service Worker.
                 */
                registerServiceWorker: function () {
                    if (!('serviceWorker' in navigator)) {
                        console.log('[PWA] Service Workers not supported');
                        return;
                    }

                    navigator.serviceWorker.register('/sw.js', { scope: '/' })
                        .then((registration) => {
                            this.swRegistration = registration;
                            console.log('[PWA] Service Worker registered:', registration.scope);

                            // Check for updates
                            registration.addEventListener('updatefound', () => {
                                const newWorker = registration.installing;
                                console.log('[PWA] New Service Worker found');

                                newWorker.addEventListener('statechange', () => {
                                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                        this.showUpdateNotification();
                                    }
                                });
                            });
                        })
                        .catch((error) => {
                            console.error('[PWA] Service Worker registration failed:', error);
                        });

                    // Handle controller change (update)
                    let refreshing = false;
                    navigator.serviceWorker.addEventListener('controllerchange', () => {
                        if (refreshing) return;
                        refreshing = true;
                        window.location.reload();
                    });
                },

                /**
                 * Handle the beforeinstallprompt event.
                 */
                handleInstallPrompt: function () {
                    window.addEventListener('beforeinstallprompt', (e) => {
                        e.preventDefault();
                        this.deferredPrompt = e;
                        console.log('[PWA] Install prompt captured');
                        this.showInstallButton();
                    });

                    window.addEventListener('appinstalled', () => {
                        console.log('[PWA] App installed');
                        this.deferredPrompt = null;
                        this.hideInstallButton();
                    });
                },

                /**
                 * Show install button.
                 */
                showInstallButton: function () {
                    const existing = document.getElementById('pwa-install-button');
                    if (existing) return;

                    const button = document.createElement('button');
                    button.id = 'pwa-install-button';
                    button.innerHTML = '📱 Instalar App';
                    button.style.cssText = `
            position: fixed;
            bottom: 90px;
            right: 24px;
            padding: 12px 20px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.4);
            z-index: 9999;
            transition: transform 0.2s;
            font-family: 'Inter', sans-serif;
          `;

                    button.addEventListener('click', () => this.promptInstall());
                    button.addEventListener('mouseenter', () => {
                        button.style.transform = 'scale(1.05)';
                    });
                    button.addEventListener('mouseleave', () => {
                        button.style.transform = 'scale(1)';
                    });

                    document.body.appendChild(button);
                },

                /**
                 * Hide install button.
                 */
                hideInstallButton: function () {
                    const button = document.getElementById('pwa-install-button');
                    if (button) {
                        button.remove();
                    }
                },

                /**
                 * Prompt user to install the app.
                 */
                promptInstall: async function () {
                    if (!this.deferredPrompt) {
                        console.log('[PWA] No install prompt available');
                        return;
                    }

                    this.deferredPrompt.prompt();
                    const { outcome } = await this.deferredPrompt.userChoice;
                    console.log('[PWA] Install prompt outcome:', outcome);
                    this.deferredPrompt = null;
                    this.hideInstallButton();
                },

                /**
                 * Show update notification.
                 */
                showUpdateNotification: function () {
                    const notification = document.createElement('div');
                    notification.id = 'pwa-update-notification';
                    notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 12px;">
              <span>🔄 Nueva versión disponible</span>
              <button id="pwa-update-button" style="
                background: white;
                color: #3b82f6;
                border: none;
                padding: 8px 16px;
                border-radius: 8px;
                font-weight: 600;
                cursor: pointer;
              ">Actualizar</button>
            </div>
          `;
                    notification.style.cssText = `
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%);
            padding: 16px 24px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            z-index: 10001;
            font-family: 'Inter', sans-serif;
          `;

                    document.body.appendChild(notification);

                    document.getElementById('pwa-update-button').addEventListener('click', () => {
                        if (this.swRegistration && this.swRegistration.waiting) {
                            this.swRegistration.waiting.postMessage({ type: 'SKIP_WAITING' });
                        }
                        notification.remove();
                    });
                },

                /**
                 * Check and display online/offline status.
                 */
                checkOnlineStatus: function () {
                    const updateStatus = () => {
                        if (!navigator.onLine) {
                            this.showOfflineIndicator();
                        } else {
                            this.hideOfflineIndicator();
                        }
                    };

                    window.addEventListener('online', updateStatus);
                    window.addEventListener('offline', updateStatus);
                    updateStatus();
                },

                /**
                 * Show offline indicator.
                 */
                showOfflineIndicator: function () {
                    if (document.getElementById('offline-indicator')) return;

                    const indicator = document.createElement('div');
                    indicator.id = 'offline-indicator';
                    indicator.innerHTML = '📡 Sin conexión';
                    indicator.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            padding: 8px;
            background: #ef4444;
            color: white;
            text-align: center;
            font-size: 14px;
            font-weight: 600;
            z-index: 10002;
            font-family: 'Inter', sans-serif;
          `;
                    document.body.appendChild(indicator);
                    document.body.style.paddingTop = '40px';
                },

                /**
                 * Hide offline indicator.
                 */
                hideOfflineIndicator: function () {
                    const indicator = document.getElementById('offline-indicator');
                    if (indicator) {
                        indicator.remove();
                        document.body.style.paddingTop = '';
                    }
                },

                /**
                 * Subscribe to push notifications.
                 */
                subscribeToPush: async function () {
                    if (!this.swRegistration) {
                        console.log('[PWA] No Service Worker registration');
                        return null;
                    }

                    try {
                        const permission = await Notification.requestPermission();
                        if (permission !== 'granted') {
                            console.log('[PWA] Notification permission denied');
                            return null;
                        }

                        // TODO: Get VAPID public key from server
                        const vapidPublicKey = drupalSettings?.jaraba?.vapidPublicKey || '';

                        if (!vapidPublicKey) {
                            console.log('[PWA] No VAPID key configured');
                            return null;
                        }

                        const subscription = await this.swRegistration.pushManager.subscribe({
                            userVisibleOnly: true,
                            applicationServerKey: this.urlBase64ToUint8Array(vapidPublicKey),
                        });

                        console.log('[PWA] Push subscription:', subscription);
                        return subscription;
                    } catch (error) {
                        console.error('[PWA] Push subscription failed:', error);
                        return null;
                    }
                },

                /**
                 * Convert base64 to Uint8Array for VAPID.
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
                },
            };

            // Initialize PWA
            PWA.init();

            // Expose for debugging
            window.JarabaPWA = PWA;
        },
    };

})(Drupal, drupalSettings);
