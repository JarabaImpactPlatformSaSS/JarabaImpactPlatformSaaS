/**
 * @file
 * Integración con Stripe para configuración de pagos en onboarding.
 *
 * Este archivo gestiona la interacción con Stripe Elements o Checkout
 * para configurar el método de pago durante el onboarding de tenants.
 *
 * Funcionalidades:
 * - Inicialización de Stripe Elements
 * - Validación de tarjeta en tiempo real
 * - Creación de suscripción vía API
 * - Manejo de errores de pago
 * - Feedback visual del proceso
 *
 * Requiere que la clave pública de Stripe esté disponible en drupalSettings.
 *
 * @module ecosistemaJarabaStripe
 */

(function (Drupal, drupalSettings, once) {
    'use strict';

    /**
     * Namespace principal para funciones de Stripe.
     *
     * @namespace
     */
    Drupal.ecosistemaJarabaStripe = {

        /**
         * Instancia de Stripe.
         * @type {Stripe|null}
         */
        stripe: null,

        /**
         * Instancia de Elements.
         * @type {StripeElements|null}
         */
        elements: null,

        /**
         * Elemento de tarjeta montado.
         * @type {StripeCardElement|null}
         */
        cardElement: null,

        /**
         * Inicializa la integración con Stripe.
         *
         * @param {HTMLElement} container - Contenedor del formulario de pago.
         */
        init: function (container) {
            const self = this;
            const publishableKey = drupalSettings.ecosistemaJaraba?.stripePublicKey;

            if (!publishableKey) {
                console.error('Stripe: Clave pública no configurada');
                this.showError(container, 'Error de configuración. Contacta con soporte.');
                return;
            }

            // Inicializar Stripe
            this.stripe = Stripe(publishableKey);

            // Crear instancia de Elements con estilos personalizados
            this.elements = this.stripe.elements({
                fonts: [
                    {
                        cssSrc: 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap'
                    }
                ],
                locale: 'es'
            });

            // Montar elemento de tarjeta
            this.mountCardElement(container);

            // Configurar formulario
            this.setupForm(container);

            // Configurar toggle de periodo de facturación
            this.setupBillingToggle(container);
        },

        /**
         * Monta el elemento de tarjeta de Stripe.
         *
         * @param {HTMLElement} container - Contenedor principal.
         */
        mountCardElement: function (container) {
            const self = this;
            const cardContainer = container.querySelector('#ej-card-element');

            if (!cardContainer) {
                console.error('Stripe: Contenedor de tarjeta no encontrado');
                return;
            }

            // Estilos para el elemento de tarjeta
            const style = {
                base: {
                    fontFamily: 'Inter, -apple-system, BlinkMacSystemFont, sans-serif',
                    fontSize: '16px',
                    fontWeight: '400',
                    color: '#212121',
                    '::placeholder': {
                        color: '#9E9E9E'
                    }
                },
                invalid: {
                    color: '#E53935',
                    iconColor: '#E53935'
                }
            };

            // Crear y montar elemento
            this.cardElement = this.elements.create('card', {
                style: style,
                hidePostalCode: true  // No pedimos código postal en España
            });

            this.cardElement.mount(cardContainer);

            // Manejar cambios y errores
            this.cardElement.on('change', function (event) {
                const errorContainer = container.querySelector('#ej-card-errors');
                if (errorContainer) {
                    if (event.error) {
                        errorContainer.textContent = event.error.message;
                        errorContainer.style.display = 'block';
                    } else {
                        errorContainer.textContent = '';
                        errorContainer.style.display = 'none';
                    }
                }

                // Habilitar/deshabilitar botón según completitud
                const submitBtn = container.querySelector('#ej-submit-payment');
                if (submitBtn) {
                    submitBtn.disabled = !event.complete;
                }
            });

            // Estado de focus
            this.cardElement.on('focus', function () {
                cardContainer.classList.add('ej-card-element--focused');
            });

            this.cardElement.on('blur', function () {
                cardContainer.classList.remove('ej-card-element--focused');
            });
        },

        /**
         * Configura el formulario de pago.
         *
         * @param {HTMLElement} container - Contenedor principal.
         */
        setupForm: function (container) {
            const self = this;
            const form = container.querySelector('#ej-payment-form');
            const submitBtn = container.querySelector('#ej-submit-payment');

            if (!form) return;

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                if (submitBtn.disabled) return;

                self.submitPayment(container, submitBtn);
            });
        },

        /**
         * Configura el toggle de periodo de facturación.
         *
         * @param {HTMLElement} container - Contenedor principal.
         */
        setupBillingToggle: function (container) {
            const toggle = container.querySelector('#billing-period-toggle');
            const monthlyLabel = container.querySelector('[data-period="monthly"]');
            const yearlyLabel = container.querySelector('[data-period="yearly"]');
            const priceDisplay = container.querySelector('#ej-price-display');
            const settings = drupalSettings.ecosistemaJaraba || {};

            if (!toggle) return;

            toggle.addEventListener('change', function () {
                const isYearly = this.checked;

                if (monthlyLabel) monthlyLabel.classList.toggle('active', !isYearly);
                if (yearlyLabel) yearlyLabel.classList.toggle('active', isYearly);

                // Actualizar precio mostrado
                if (priceDisplay) {
                    if (isYearly) {
                        const yearlyMonthly = (parseFloat(settings.priceYearly) / 12).toFixed(2);
                        priceDisplay.innerHTML = `
              <span class="ej-price-amount">${yearlyMonthly}€</span>
              <span class="ej-price-period">/mes</span>
              <span class="ej-price-billed">Facturado anualmente (${settings.priceYearly}€/año)</span>
            `;
                    } else {
                        priceDisplay.innerHTML = `
              <span class="ej-price-amount">${settings.priceMonthly}€</span>
              <span class="ej-price-period">/mes</span>
            `;
                    }
                }
            });

            // Estado inicial
            if (monthlyLabel) monthlyLabel.classList.add('active');
        },

        /**
         * Procesa el pago y crea la suscripción.
         *
         * @param {HTMLElement} container - Contenedor principal.
         * @param {HTMLButtonElement} submitBtn - Botón de envío.
         */
        submitPayment: async function (container, submitBtn) {
            const self = this;
            const originalText = submitBtn.innerHTML;

            // Estado de carga
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Procesando pago...';

            try {
                // 1. Crear método de pago con Stripe
                const { paymentMethod, error: pmError } = await this.stripe.createPaymentMethod({
                    type: 'card',
                    card: this.cardElement,
                    billing_details: this.getBillingDetails(container)
                });

                if (pmError) {
                    throw new Error(pmError.message);
                }

                // 2. Determinar periodo de facturación
                const billingToggle = container.querySelector('#billing-period-toggle');
                const billingPeriod = billingToggle?.checked ? 'yearly' : 'monthly';

                // 3. Enviar al backend para crear suscripción
                const response = await fetch('/api/v1/stripe/create-subscription', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': drupalSettings.ecosistemaJaraba?.csrfToken || ''
                    },
                    body: JSON.stringify({
                        payment_method_id: paymentMethod.id,
                        plan_id: drupalSettings.ecosistemaJaraba?.planId,
                        billing_period: billingPeriod
                    })
                });

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.error || 'Error al crear la suscripción');
                }

                // 4. Manejar confirmación de pago si requiere 3D Secure
                if (result.requires_action) {
                    const { error: confirmError } = await this.stripe.confirmCardPayment(
                        result.client_secret
                    );

                    if (confirmError) {
                        throw new Error(confirmError.message);
                    }

                    // Confirmar al backend que el pago fue exitoso
                    await fetch('/api/v1/stripe/confirm-subscription', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': drupalSettings.ecosistemaJaraba?.csrfToken || ''
                        },
                        body: JSON.stringify({
                            subscription_id: result.subscription_id
                        })
                    });
                }

                // 5. Éxito - redirigir a página de bienvenida
                submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> ¡Pago completado!';
                this.showSuccess(container, '¡Suscripción activada correctamente!');

                setTimeout(function () {
                    window.location.href = result.redirect || '/onboarding/bienvenida';
                }, 1500);

            } catch (error) {
                console.error('Error en pago:', error);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                this.showError(container, error.message);
            }
        },

        /**
         * Obtiene los detalles de facturación del formulario.
         *
         * @param {HTMLElement} container - Contenedor principal.
         * @returns {Object} - Detalles de facturación para Stripe.
         */
        getBillingDetails: function (container) {
            const nameInput = container.querySelector('#billing-name');
            const emailInput = container.querySelector('#billing-email');

            return {
                name: nameInput?.value || '',
                email: emailInput?.value || ''
            };
        },

        /**
         * Muestra un mensaje de error.
         *
         * @param {HTMLElement} container - Contenedor principal.
         * @param {string} message - Mensaje de error.
         */
        showError: function (container, message) {
            const errorContainer = container.querySelector('#ej-payment-error');
            if (errorContainer) {
                errorContainer.textContent = message;
                errorContainer.style.display = 'block';

                // Scroll hacia el error
                errorContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });

                // Ocultar después de un tiempo
                setTimeout(function () {
                    errorContainer.style.display = 'none';
                }, 8000);
            }
        },

        /**
         * Muestra un mensaje de éxito.
         *
         * @param {HTMLElement} container - Contenedor principal.
         * @param {string} message - Mensaje de éxito.
         */
        showSuccess: function (container, message) {
            const successContainer = container.querySelector('#ej-payment-success');
            if (successContainer) {
                successContainer.textContent = message;
                successContainer.style.display = 'block';
            }
        },

        /**
         * Inicia el flujo de Stripe Checkout (alternativa a Elements).
         *
         * Útil para planes que prefieren una página de pago hospedada por Stripe.
         *
         * @param {string} sessionId - ID de la sesión de Checkout creada en backend.
         */
        redirectToCheckout: async function (sessionId) {
            const { error } = await this.stripe.redirectToCheckout({ sessionId });

            if (error) {
                console.error('Error al redirigir a Checkout:', error);
            }
        }
    };

    /**
     * Behavior de Drupal para Stripe.
     *
     * @type {Drupal~behavior}
     */
    Drupal.behaviors.ecosistemaJarabaStripe = {
        attach: function (context) {
            once('ej-stripe-payment', '.ej-setup-payment-page', context).forEach(function (container) {
                Drupal.ecosistemaJarabaStripe.init(container);
            });
        }
    };

})(Drupal, drupalSettings, once);
