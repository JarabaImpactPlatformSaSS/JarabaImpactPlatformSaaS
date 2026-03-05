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
 * ROUTE-LANGPREFIX-001: Todas las URLs via drupalSettings (Url::fromRoute).
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
            var submitBtn = form.querySelector('[type="submit"]');
            var passwordField = form.querySelector('#password');
            var passwordToggle = form.querySelector('.ej-password-toggle');
            var domainInput = form.querySelector('#domain');

            // Toggle de visibilidad de contraseña
            if (passwordToggle && passwordField) {
                passwordToggle.addEventListener('click', function () {
                    var type = passwordField.type === 'password' ? 'text' : 'password';
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
            var formGroup = input.closest('.ej-form-group');
            var errorElement = formGroup ? formGroup.querySelector('.ej-form-error') : null;
            var isValid = true;
            var errorMessage = '';

            // Validar campo requerido
            if (input.required && !input.value.trim()) {
                isValid = false;
                errorMessage = Drupal.t('Este campo es obligatorio.');
            }
            // Validar email
            else if (input.type === 'email' && input.value) {
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(input.value)) {
                    isValid = false;
                    errorMessage = Drupal.t('El formato del email no es válido.');
                }
            }
            // Validar contraseña
            else if (input.name === 'password' && input.value) {
                if (input.value.length < 8) {
                    isValid = false;
                    errorMessage = Drupal.t('La contraseña debe tener al menos 8 caracteres.');
                } else if (!/[A-Z]/.test(input.value) || !/[0-9]/.test(input.value)) {
                    isValid = false;
                    errorMessage = Drupal.t('Debe contener al menos una mayúscula y un número.');
                }
            }
            // Validar dominio
            else if (input.name === 'domain' && input.value) {
                if (!/^[a-z0-9][a-z0-9-]{1,61}[a-z0-9]$/.test(input.value)) {
                    isValid = false;
                    errorMessage = Drupal.t('Formato inválido. Use solo letras minúsculas, números y guiones.');
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
            var allValid = true;
            form.querySelectorAll('.ej-form-input, [type="checkbox"]').forEach(function (input) {
                if (!Drupal.ecosistemaJarabaOnboarding.validateField(input)) {
                    allValid = false;
                }
            });

            // Validar checkbox de términos
            var termsCheckbox = form.querySelector('#accept_terms');
            if (termsCheckbox && !termsCheckbox.checked) {
                allValid = false;
                var termsGroup = termsCheckbox.closest('.ej-form-group');
                var termsError = termsGroup ? termsGroup.querySelector('.ej-form-error') : null;
                if (termsError) {
                    termsError.textContent = Drupal.t('Debes aceptar los términos y condiciones.');
                    termsError.classList.add('active');
                }
            }

            if (!allValid) {
                return;
            }

            // Preparar datos
            var formData = new FormData(form);
            var data = {};
            formData.forEach(function (value, key) {
                data[key] = value;
            });

            // Mostrar estado de carga
            submitBtn.disabled = true;
            var originalText = submitBtn.textContent;
            submitBtn.textContent = Drupal.t('Procesando...');

            // ROUTE-LANGPREFIX-001: URL from drupalSettings (Url::fromRoute).
            var settings = drupalSettings.ecosistemaJaraba || {};
            var registerUrl = settings.registerProcessUrl || '/registro/procesar';

            // Enviar petición
            fetch(registerUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': settings.csrfToken || ''
                },
                body: JSON.stringify(data)
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (result) {
                    if (result.success) {
                        // Éxito: redirigir
                        submitBtn.textContent = Drupal.t('¡Registro exitoso!');
                        if (result.redirect) {
                            window.location.href = result.redirect;
                        }
                    } else {
                        // Mostrar errores
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;

                        if (result.errors) {
                            Object.keys(result.errors).forEach(function (field) {
                                var input = form.querySelector('[name="' + field + '"]');
                                var inputGroup = input ? input.closest('.ej-form-group') : null;
                                var fieldError = inputGroup ? inputGroup.querySelector('.ej-form-error') : null;
                                if (fieldError) {
                                    fieldError.textContent = result.errors[field];
                                    fieldError.classList.add('active');
                                }
                            });
                        }

                        if (result.error) {
                            var generalError = document.getElementById('ej-register-error');
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
                    submitBtn.textContent = originalText;

                    var generalError = document.getElementById('ej-register-error');
                    if (generalError) {
                        generalError.textContent = Drupal.t('Error de conexión. Por favor, inténtalo de nuevo.');
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
            var toggle = container.querySelector('#pricing-toggle');
            var monthlyPrices = container.querySelectorAll('.ej-plan-price--monthly');
            var yearlyPrices = container.querySelectorAll('.ej-plan-price--yearly');
            var labels = container.querySelectorAll('.ej-pricing-toggle__label');

            if (!toggle) return;

            toggle.addEventListener('change', function () {
                var isYearly = this.checked;

                monthlyPrices.forEach(function (el) {
                    el.style.display = isYearly ? 'none' : 'block';
                });
                yearlyPrices.forEach(function (el) {
                    el.style.display = isYearly ? 'block' : 'none';
                });

                labels.forEach(function (label) {
                    var isActive = (isYearly && label.dataset.period === 'yearly') ||
                        (!isYearly && label.dataset.period === 'monthly');
                    label.classList.toggle('active', isActive);
                });
            });

            // Estado inicial
            if (labels[0]) {
                labels[0].classList.add('active');
            }
        },

        /**
         * Inicializa la animación de confetti en la página de bienvenida.
         *
         * @param {HTMLElement} container - Contenedor del confetti.
         */
        initConfetti: function (container) {
            if (!container) return;

            // Crear partículas de confetti
            var colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7', '#DDA0DD'];
            var particleCount = 50;

            for (var i = 0; i < particleCount; i++) {
                var particle = document.createElement('div');
                particle.className = 'ej-confetti-particle';
                particle.style.position = 'absolute';
                particle.style.width = '10px';
                particle.style.height = '10px';
                particle.style.background = colors[Math.floor(Math.random() * colors.length)];
                particle.style.left = (Math.random() * 100) + '%';
                particle.style.top = '-20px';
                particle.style.animation = 'confetti-fall ' + (2 + Math.random() * 2) + 's ease-out forwards';
                particle.style.animationDelay = (Math.random() * 0.5) + 's';
                particle.style.transform = 'rotate(' + (Math.random() * 360) + 'deg)';
                particle.style.borderRadius = Math.random() > 0.5 ? '50%' : '0';
                container.appendChild(particle);
            }

            // Añadir keyframes dinámicamente
            if (!document.getElementById('ej-confetti-styles')) {
                var style = document.createElement('style');
                style.id = 'ej-confetti-styles';
                style.textContent = '@keyframes confetti-fall { to { top: 100%; opacity: 0; transform: rotate(720deg) translateX(' + (Math.random() * 100 - 50) + 'px); } }';
                document.head.appendChild(style);
            }

            // Limpiar partículas después de la animación
            setTimeout(function () {
                container.textContent = '';
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
