/**
 * @file
 * JavaScript para el flujo de onboarding de tenants.
 *
 * Este archivo gestiona la interactividad de las páginas de onboarding:
 * - Validación de formularios en tiempo real
 * - Toggle de precios mensual/anual
 * - Mostrar/ocultar contraseña
 * - Envío del formulario de registro vía AJAX
 * - Animación de confetti en página de bienvenida
 *
 * @module ecosistemaJarabaOnboarding
 */

(function (Drupal, drupalSettings, once) {
    'use strict';

    /**
     * Namespace principal para funciones de onboarding.
     *
     * @namespace
     */
    Drupal.ecosistemaJarabaOnboarding = {

        /**
         * Inicializa el formulario de registro.
         *
         * @param {HTMLFormElement} form - El formulario de registro.
         */
        initRegisterForm: function (form) {
            const submitBtn = form.querySelector('[type="submit"]');
            const passwordField = form.querySelector('#password');
            const passwordToggle = form.querySelector('.ej-password-toggle');
            const domainInput = form.querySelector('#domain');

            // Toggle de visibilidad de contraseña
            if (passwordToggle && passwordField) {
                passwordToggle.addEventListener('click', function () {
                    const type = passwordField.type === 'password' ? 'text' : 'password';
                    passwordField.type = type;
                    this.querySelector('i').classList.toggle('bi-eye');
                    this.querySelector('i').classList.toggle('bi-eye-slash');
                });
            }

            // Formatear dominio automáticamente (lowercase, sin espacios)
            if (domainInput) {
                domainInput.addEventListener('input', function () {
                    this.value = this.value
                        .toLowerCase()
                        .replace(/[^a-z0-9-]/g, '')
                        .replace(/--+/g, '-');
                });
            }

            // Validación en tiempo real
            form.querySelectorAll('.ej-form-input').forEach(function (input) {
                input.addEventListener('blur', function () {
                    Drupal.ecosistemaJarabaOnboarding.validateField(input);
                });
            });

            // Envío del formulario
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                Drupal.ecosistemaJarabaOnboarding.submitForm(form, submitBtn);
            });
        },

        /**
         * Valida un campo del formulario.
         *
         * @param {HTMLInputElement} input - El campo a validar.
         * @returns {boolean} - True si el campo es válido.
         */
        validateField: function (input) {
            const errorElement = input.closest('.ej-form-group')?.querySelector('.ej-form-error');
            let isValid = true;
            let errorMessage = '';

            // Validar campo requerido
            if (input.required && !input.value.trim()) {
                isValid = false;
                errorMessage = 'Este campo es obligatorio.';
            }
            // Validar email
            else if (input.type === 'email' && input.value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(input.value)) {
                    isValid = false;
                    errorMessage = 'El formato del email no es válido.';
                }
            }
            // Validar contraseña
            else if (input.name === 'password' && input.value) {
                if (input.value.length < 8) {
                    isValid = false;
                    errorMessage = 'La contraseña debe tener al menos 8 caracteres.';
                } else if (!/[A-Z]/.test(input.value) || !/[0-9]/.test(input.value)) {
                    isValid = false;
                    errorMessage = 'Debe contener al menos una mayúscula y un número.';
                }
            }
            // Validar dominio
            else if (input.name === 'domain' && input.value) {
                if (!/^[a-z0-9][a-z0-9-]{1,61}[a-z0-9]$/.test(input.value)) {
                    isValid = false;
                    errorMessage = 'Formato inválido. Use solo letras minúsculas, números y guiones.';
                }
            }

            // Mostrar/ocultar error
            if (errorElement) {
                errorElement.textContent = errorMessage;
                errorElement.classList.toggle('active', !isValid);
            }
            input.classList.toggle('ej-form-input--error', !isValid);

            return isValid;
        },

        /**
         * Envía el formulario de registro vía AJAX.
         *
         * @param {HTMLFormElement} form - El formulario a enviar.
         * @param {HTMLButtonElement} submitBtn - El botón de envío.
         */
        submitForm: function (form, submitBtn) {
            // Validar todos los campos primero
            let allValid = true;
            form.querySelectorAll('.ej-form-input, [type="checkbox"]').forEach(function (input) {
                if (!Drupal.ecosistemaJarabaOnboarding.validateField(input)) {
                    allValid = false;
                }
            });

            // Validar checkbox de términos
            const termsCheckbox = form.querySelector('#accept_terms');
            if (termsCheckbox && !termsCheckbox.checked) {
                allValid = false;
                const errorElement = termsCheckbox.closest('.ej-form-group')?.querySelector('.ej-form-error');
                if (errorElement) {
                    errorElement.textContent = 'Debes aceptar los términos y condiciones.';
                    errorElement.classList.add('active');
                }
            }

            if (!allValid) {
                return;
            }

            // Preparar datos
            const formData = new FormData(form);
            const data = {};
            formData.forEach(function (value, key) {
                data[key] = value;
            });

            // Mostrar estado de carga
            submitBtn.disabled = true;
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Procesando...';

            // Enviar petición
            fetch('/registro/procesar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': drupalSettings.ecosistemaJaraba?.csrfToken || ''
                },
                body: JSON.stringify(data)
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (result) {
                    if (result.success) {
                        // Éxito: redirigir
                        submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> ¡Registro exitoso!';
                        if (result.redirect) {
                            window.location.href = result.redirect;
                        }
                    } else {
                        // Mostrar errores
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;

                        if (result.errors) {
                            Object.keys(result.errors).forEach(function (field) {
                                const input = form.querySelector('[name="' + field + '"]');
                                const errorElement = input?.closest('.ej-form-group')?.querySelector('.ej-form-error');
                                if (errorElement) {
                                    errorElement.textContent = result.errors[field];
                                    errorElement.classList.add('active');
                                }
                            });
                        }

                        if (result.error) {
                            const generalError = document.getElementById('ej-register-error');
                            if (generalError) {
                                generalError.textContent = result.error;
                                generalError.style.display = 'block';
                            }
                        }
                    }
                })
                .catch(function (error) {
                    console.error('Error en registro:', error);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;

                    const generalError = document.getElementById('ej-register-error');
                    if (generalError) {
                        generalError.textContent = 'Error de conexión. Por favor, inténtalo de nuevo.';
                        generalError.style.display = 'block';
                    }
                });
        },

        /**
         * Inicializa el toggle de precios mensual/anual.
         *
         * @param {HTMLElement} container - Contenedor de la página de planes.
         */
        initPricingToggle: function (container) {
            const toggle = container.querySelector('#pricing-toggle');
            const monthlyPrices = container.querySelectorAll('.ej-plan-price--monthly');
            const yearlyPrices = container.querySelectorAll('.ej-plan-price--yearly');
            const labels = container.querySelectorAll('.ej-pricing-toggle__label');

            if (!toggle) return;

            toggle.addEventListener('change', function () {
                const isYearly = this.checked;

                monthlyPrices.forEach(function (el) {
                    el.style.display = isYearly ? 'none' : 'block';
                });
                yearlyPrices.forEach(function (el) {
                    el.style.display = isYearly ? 'block' : 'none';
                });

                labels.forEach(function (label) {
                    const isActive = (isYearly && label.dataset.period === 'yearly') ||
                        (!isYearly && label.dataset.period === 'monthly');
                    label.classList.toggle('active', isActive);
                });
            });

            // Estado inicial
            labels[0]?.classList.add('active');
        },

        /**
         * Inicializa la animación de confetti en la página de bienvenida.
         *
         * @param {HTMLElement} container - Contenedor del confetti.
         */
        initConfetti: function (container) {
            if (!container) return;

            // Crear partículas de confetti
            const colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7', '#DDA0DD'];
            const particleCount = 50;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'ej-confetti-particle';
                particle.style.cssText = `
          position: absolute;
          width: 10px;
          height: 10px;
          background: ${colors[Math.floor(Math.random() * colors.length)]};
          left: ${Math.random() * 100}%;
          top: -20px;
          animation: confetti-fall ${2 + Math.random() * 2}s ease-out forwards;
          animation-delay: ${Math.random() * 0.5}s;
          transform: rotate(${Math.random() * 360}deg);
          border-radius: ${Math.random() > 0.5 ? '50%' : '0'};
        `;
                container.appendChild(particle);
            }

            // Añadir keyframes dinámicamente
            if (!document.getElementById('ej-confetti-styles')) {
                const style = document.createElement('style');
                style.id = 'ej-confetti-styles';
                style.textContent = `
          @keyframes confetti-fall {
            to {
              top: 100%;
              opacity: 0;
              transform: rotate(720deg) translateX(${Math.random() * 100 - 50}px);
            }
          }
        `;
                document.head.appendChild(style);
            }

            // Limpiar partículas después de la animación
            setTimeout(function () {
                container.innerHTML = '';
            }, 5000);
        }
    };

    /**
     * Behavior de Drupal para el onboarding.
     *
     * @type {Drupal~behavior}
     */
    Drupal.behaviors.ecosistemaJarabaOnboarding = {
        attach: function (context) {
            // Inicializar formulario de registro
            once('ej-register-form', '#ej-register-form', context).forEach(function (form) {
                Drupal.ecosistemaJarabaOnboarding.initRegisterForm(form);
            });

            // Inicializar toggle de precios
            once('ej-pricing-toggle', '.ej-select-plan-page', context).forEach(function (container) {
                Drupal.ecosistemaJarabaOnboarding.initPricingToggle(container);
            });

            // Inicializar confetti en página de bienvenida
            once('ej-confetti', '#ej-confetti-container', context).forEach(function (container) {
                Drupal.ecosistemaJarabaOnboarding.initConfetti(container);
            });
        }
    };

})(Drupal, drupalSettings, once);
