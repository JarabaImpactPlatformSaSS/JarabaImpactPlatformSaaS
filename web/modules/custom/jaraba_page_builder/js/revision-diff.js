/**
 * @file
 * JavaScript para Diff Visual de Revisiones.
 *
 * PROPÓSITO:
 * Gestiona la interacción en la lista de revisiones y la comparación visual.
 * Incluye navegación por formulario, confirmación de rollback, y
 * highlighting de diferencias.
 *
 * Gap G: Diff Visual de Revisiones
 * @see docs/planificacion/20260202-Auditoria_Plan_Elevacion_Clase_Mundial_v1.md
 */

(function (Drupal, once) {
    'use strict';

    /**
     * Behavior para la lista de revisiones.
     *
     * Gestiona la selección de revisiones y navegación al comparador.
     */
    Drupal.behaviors.jarabaRevisionList = {
        attach: function (context, settings) {
            once('revision-list', '.revision-list', context).forEach(function (container) {
                const form = container.querySelector('#revision-compare-form');
                const compareBtn = container.querySelector('#compare-btn');
                const pageId = container.dataset.pageId;

                if (!form || !compareBtn || !pageId) {
                    return;
                }

                // Manejar envío del formulario para navegar al comparador.
                form.addEventListener('submit', function (e) {
                    e.preventDefault();

                    const olderRadio = form.querySelector('input[name="older"]:checked');
                    const newerRadio = form.querySelector('input[name="newer"]:checked');

                    if (!olderRadio || !newerRadio) {
                        Drupal.announce(Drupal.t('Selecciona dos revisiones para comparar.'));
                        return;
                    }

                    const older = olderRadio.value;
                    const newer = newerRadio.value;

                    // Validar que no sean iguales.
                    if (older === newer) {
                        Drupal.announce(Drupal.t('Las revisiones seleccionadas son iguales.'));
                        return;
                    }

                    // Construir URL y navegar.
                    const compareUrl = '/page/' + pageId + '/revisions/compare/' + older + '/' + newer;
                    window.location.href = compareUrl;
                });

                // Validación en tiempo real.
                form.addEventListener('change', function () {
                    const olderRadio = form.querySelector('input[name="older"]:checked');
                    const newerRadio = form.querySelector('input[name="newer"]:checked');

                    if (olderRadio && newerRadio && olderRadio.value === newerRadio.value) {
                        compareBtn.disabled = true;
                        compareBtn.classList.add('btn--disabled');
                    } else {
                        compareBtn.disabled = false;
                        compareBtn.classList.remove('btn--disabled');
                    }
                });
            });
        }
    };

    /**
     * Behavior para la página de comparación de revisiones.
     *
     * Añade confirmación antes del rollback.
     */
    Drupal.behaviors.jarabaRevisionDiff = {
        attach: function (context, settings) {
            once('revision-diff', '.revision-diff', context).forEach(function (container) {
                // Manejar confirmación de rollback.
                const revertBtns = container.querySelectorAll('[data-confirm]');

                revertBtns.forEach(function (btn) {
                    btn.addEventListener('click', function (e) {
                        const confirmMessage = btn.dataset.confirm;
                        if (confirmMessage && !confirm(confirmMessage)) {
                            e.preventDefault();
                        }
                    });
                });

                // Animar campos modificados al cargar.
                const modifiedFields = container.querySelectorAll('.revision-diff__field--modified');
                modifiedFields.forEach(function (field, index) {
                    field.style.animationDelay = (index * 0.1) + 's';
                    field.classList.add('fade-in');
                });
            });
        }
    };

    /**
     * Behavior para partículas animadas en el header premium.
     *
     * Crea un efecto de partículas flotantes en el canvas del hero.
     * Siguiendo el patrón de analytics-dashboard/pixel-stats.
     */
    Drupal.behaviors.jarabaRevisionParticles = {
        attach: function (context, settings) {
            once('revision-particles', '#revision-particles', context).forEach(function (canvas) {
                const ctx = canvas.getContext('2d');
                let animationFrame;
                const particles = [];
                const particleCount = 50;

                // Configurar canvas responsivo.
                function resizeCanvas() {
                    const parent = canvas.parentElement;
                    canvas.width = parent.offsetWidth;
                    canvas.height = parent.offsetHeight;
                }

                // Crear partícula.
                function createParticle() {
                    return {
                        x: Math.random() * canvas.width,
                        y: Math.random() * canvas.height,
                        size: Math.random() * 3 + 1,
                        speedX: (Math.random() - 0.5) * 0.5,
                        speedY: (Math.random() - 0.5) * 0.5,
                        opacity: Math.random() * 0.5 + 0.2
                    };
                }

                // Inicializar partículas.
                function initParticles() {
                    particles.length = 0;
                    for (let i = 0; i < particleCount; i++) {
                        particles.push(createParticle());
                    }
                }

                // Animar partículas.
                function animate() {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);

                    particles.forEach(function (particle) {
                        // Movimiento.
                        particle.x += particle.speedX;
                        particle.y += particle.speedY;

                        // Wrap around.
                        if (particle.x < 0) particle.x = canvas.width;
                        if (particle.x > canvas.width) particle.x = 0;
                        if (particle.y < 0) particle.y = canvas.height;
                        if (particle.y > canvas.height) particle.y = 0;

                        // Dibujar.
                        ctx.beginPath();
                        ctx.arc(particle.x, particle.y, particle.size, 0, Math.PI * 2);
                        ctx.fillStyle = 'rgba(255, 255, 255, ' + particle.opacity + ')';
                        ctx.fill();
                    });

                    // Dibujar líneas entre partículas cercanas.
                    for (let i = 0; i < particles.length; i++) {
                        for (let j = i + 1; j < particles.length; j++) {
                            const dx = particles[i].x - particles[j].x;
                            const dy = particles[i].y - particles[j].y;
                            const distance = Math.sqrt(dx * dx + dy * dy);

                            if (distance < 100) {
                                ctx.beginPath();
                                ctx.moveTo(particles[i].x, particles[i].y);
                                ctx.lineTo(particles[j].x, particles[j].y);
                                ctx.strokeStyle = 'rgba(255, 255, 255, ' + (0.15 * (1 - distance / 100)) + ')';
                                ctx.stroke();
                            }
                        }
                    }

                    animationFrame = requestAnimationFrame(animate);
                }

                // Iniciar.
                resizeCanvas();
                initParticles();
                animate();

                // Resize handler.
                window.addEventListener('resize', function () {
                    resizeCanvas();
                    initParticles();
                });
            });
        }
    };

})(Drupal, once);

