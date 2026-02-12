/**
 * @file
 * JavaScript para el checkout de AgroConecta.
 *
 * Inicializa Stripe Payment Element, gestiona el formulario de checkout,
 * y procesa la confirmación de pago.
 *
 * Dependencias: Stripe.js v3 (externo), drupalSettings
 */
(function (Drupal, drupalSettings, once) {
    'use strict';

    Drupal.behaviors.agroconectaCheckout = {
        attach: function (context) {
            once('agro-checkout', '#checkout-submit', context).forEach(function (submitBtn) {
                var settings = drupalSettings.agroconecta || {};
                var processUrl = settings.checkoutProcessUrl || '/checkout/process';
                var confirmUrl = settings.checkoutConfirmUrl || '/checkout/confirm';

                // Habilitar botón cuando el formulario sea válido.
                var form = document.querySelector('.agro-checkout__forms');
                if (form) {
                    form.addEventListener('input', function () {
                        var email = document.getElementById('checkout-email');
                        submitBtn.disabled = !(email && email.value && email.validity.valid);
                    });
                }

                // Manejar envío del checkout.
                submitBtn.addEventListener('click', function (e) {
                    e.preventDefault();

                    if (submitBtn.disabled) return;

                    // Mostrar estado de procesamiento.
                    var textEl = submitBtn.querySelector('.agro-checkout__submit-text');
                    var spinnerEl = submitBtn.querySelector('.agro-checkout__submit-spinner');
                    if (textEl) textEl.style.display = 'none';
                    if (spinnerEl) spinnerEl.style.display = 'inline';
                    submitBtn.disabled = true;

                    // Recopilar datos del formulario.
                    var checkoutData = {
                        items: [], // Se rellenan desde el carrito en sesión.
                        customer: {
                            email: document.getElementById('checkout-email')?.value || '',
                            phone: document.getElementById('checkout-phone')?.value || '',
                            delivery_method: document.querySelector('input[name="delivery_method"]:checked')?.value || 'shipping',
                            delivery_notes: document.getElementById('checkout-notes')?.value || '',
                            shipping_address: {
                                address: document.getElementById('checkout-address')?.value || '',
                                city: document.getElementById('checkout-city')?.value || '',
                                postal_code: document.getElementById('checkout-postal')?.value || '',
                                province: document.getElementById('checkout-province')?.value || '',
                                country: 'ES',
                            },
                            billing_address: {
                                // Por defecto, igual que dirección de envío.
                                address: document.getElementById('checkout-address')?.value || '',
                                city: document.getElementById('checkout-city')?.value || '',
                                postal_code: document.getElementById('checkout-postal')?.value || '',
                                province: document.getElementById('checkout-province')?.value || '',
                                country: 'ES',
                            },
                        },
                    };

                    // Enviar al backend.
                    fetch(processUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify(checkoutData),
                    })
                        .then(function (response) { return response.json(); })
                        .then(function (data) {
                            if (data.error) {
                                showPaymentMessage(data.error, 'error');
                                resetSubmitButton();
                                return;
                            }

                            // Éxito — redirigir a confirmación.
                            if (data.order_number) {
                                // Confirmar pago.
                                return fetch(confirmUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                    body: JSON.stringify({
                                        payment_intent_id: data.client_secret?.split('_secret_')[0] || '',
                                    }),
                                })
                                    .then(function (r) { return r.json(); })
                                    .then(function (confirmData) {
                                        if (confirmData.redirect_url) {
                                            window.location.href = confirmData.redirect_url;
                                        }
                                    });
                            }
                        })
                        .catch(function (err) {
                            showPaymentMessage('Error de conexión. Inténtelo de nuevo.', 'error');
                            resetSubmitButton();
                        });
                });

                /**
                 * Muestra un mensaje en la sección de pago.
                 */
                function showPaymentMessage(message, type) {
                    var msgEl = document.getElementById('payment-message');
                    if (msgEl) {
                        msgEl.textContent = message;
                        msgEl.className = 'agro-checkout__payment-message agro-checkout__payment-message--' + type;
                        msgEl.style.display = 'block';
                    }
                }

                /**
                 * Restablece el botón de envío.
                 */
                function resetSubmitButton() {
                    var textEl = submitBtn.querySelector('.agro-checkout__submit-text');
                    var spinnerEl = submitBtn.querySelector('.agro-checkout__submit-spinner');
                    if (textEl) textEl.style.display = 'inline';
                    if (spinnerEl) spinnerEl.style.display = 'none';
                    submitBtn.disabled = false;
                }
            });
        },
    };
})(Drupal, drupalSettings, once);
