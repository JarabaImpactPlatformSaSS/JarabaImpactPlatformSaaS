/**
 * @file
 * Customer Portal - AgroConecta
 *
 * Interactividad del portal del cliente:
 * - Cancelación de pedidos
 * - Animaciones de interfaz
 */
(function (Drupal, drupalSettings, once) {
    'use strict';

    Drupal.behaviors.agroconectaCustomerPortal = {
        attach: function (context) {
            // === Cancelar pedido ===
            const cancelButtons = once('cancel-order', '[data-action="cancel"]', context);
            cancelButtons.forEach(function (button) {
                button.addEventListener('click', function (e) {
                    e.preventDefault();
                    const orderNumber = this.dataset.orderNumber;

                    if (!confirm(Drupal.t('¿Estás seguro de que quieres cancelar el pedido @number?', { '@number': orderNumber }))) {
                        return;
                    }

                    this.disabled = true;
                    this.textContent = Drupal.t('Cancelando...');

                    fetch('/api/v1/agro/orders/' + orderNumber + '/cancel', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    })
                        .then(function (response) { return response.json(); })
                        .then(function (data) {
                            if (data.status === 'success' || data.success) {
                                window.location.href = '/mi-cuenta/pedidos';
                            } else {
                                alert(data.message || Drupal.t('No se pudo cancelar el pedido.'));
                                button.disabled = false;
                                button.textContent = Drupal.t('Cancelar pedido');
                            }
                        })
                        .catch(function () {
                            alert(Drupal.t('Error de conexión.'));
                            button.disabled = false;
                            button.textContent = Drupal.t('Cancelar pedido');
                        });
                });
            });

            // === Animaciones de entrada ===
            const cards = once('card-animate', '.customer-portal__order-card, .customer-portal__product-card, .customer-portal__stat-card', context);
            cards.forEach(function (card, index) {
                card.style.opacity = '0';
                card.style.transform = 'translateY(12px)';
                setTimeout(function () {
                    card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 60);
            });
        }
    };

})(Drupal, drupalSettings, once);
