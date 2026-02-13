/**
 * @file
 * JavaScript para el panel de configuración de Pixels.
 *
 * Maneja:
 * - Test de conexión con plataformas
 * - Feedback visual de estados
 */

(function (Drupal, once) {
    'use strict';

    Drupal.behaviors.pixelSettings = {
        attach: function (context) {
            // Test Connection buttons
            once('pixel-test-connection', '.js-test-connection', context).forEach(function (button) {
                button.addEventListener('click', function (e) {
                    e.preventDefault();
                    const platform = this.dataset.platform;
                    const originalText = this.innerHTML;

                    // Mostrar estado de carga
                    this.disabled = true;
                    this.innerHTML = '<span class="spinner-small"></span> ' + Drupal.t('Verificando...');

                    // Llamar API de test
                    fetch('/api/v1/pixels/test/' + platform, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    })
                        .then(function (response) {
                            return response.json();
                        })
                        .then(function (data) {
                            if (data.success) {
                                // Éxito: mostrar feedback positivo
                                button.classList.add('btn--success');
                                button.innerHTML = '✓ ' + Drupal.t('Conexión OK');

                                // Actualizar badge de la tarjeta
                                const card = button.closest('.pixel-platform-card');
                                if (card) {
                                    const badge = card.querySelector('.pixel-badge');
                                    if (badge) {
                                        badge.className = 'pixel-badge pixel-badge--active';
                                        badge.textContent = Drupal.t('Activo');
                                    }
                                }

                                setTimeout(function () {
                                    button.classList.remove('btn--success');
                                    button.innerHTML = originalText;
                                    button.disabled = false;
                                }, 3000);
                            } else {
                                // Error: mostrar mensaje
                                button.classList.add('btn--danger');
                                button.innerHTML = '✗ ' + (data.message || Drupal.t('Error'));

                                setTimeout(function () {
                                    button.classList.remove('btn--danger');
                                    button.innerHTML = originalText;
                                    button.disabled = false;
                                }, 4000);
                            }
                        })
                        .catch(function (error) {
                            console.error('Pixel test error:', error);
                            button.classList.add('btn--danger');
                            button.innerHTML = '✗ ' + Drupal.t('Error de red');

                            setTimeout(function () {
                                button.classList.remove('btn--danger');
                                button.innerHTML = originalText;
                                button.disabled = false;
                            }, 3000);
                        });
                });
            });
        }
    };

})(Drupal, once);
