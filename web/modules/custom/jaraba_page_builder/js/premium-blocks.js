/**
 * @file
 * JavaScript para bloques premium del Page Builder.
 * 
 * Features:
 * - Typewriter effect
 * - Animated counters
 * - Scroll reveal (Intersection Observer)
 * - Comparison slider
 * - Parallax effects
 * - Tilt effects
 * - Spotlight cursor
 */

(function (Drupal, once) {
    'use strict';

    /**
     * Typewriter Effect
     * Tipos textos con efecto de máquina de escribir
     */
    Drupal.behaviors.jarabaPremiumTypewriter = {
        attach: function (context) {
            once('typewriter', '[data-typewriter]', context).forEach(function (element) {
                const phrases = JSON.parse(element.dataset.phrases || '[]');
                const typingSpeed = parseInt(element.dataset.typingSpeed) || 100;
                const deleteSpeed = parseInt(element.dataset.deleteSpeed) || 50;
                const pauseTime = parseInt(element.dataset.pauseTime) || 2000;
                const textEl = element.querySelector('.jaraba-typewriter__text');

                if (!textEl || phrases.length === 0) return;

                let phraseIndex = 0;
                let charIndex = 0;
                let isDeleting = false;

                function type() {
                    const currentPhrase = phrases[phraseIndex];

                    if (isDeleting) {
                        textEl.textContent = currentPhrase.substring(0, charIndex - 1);
                        charIndex--;
                    } else {
                        textEl.textContent = currentPhrase.substring(0, charIndex + 1);
                        charIndex++;
                    }

                    let timeout = isDeleting ? deleteSpeed : typingSpeed;

                    if (!isDeleting && charIndex === currentPhrase.length) {
                        timeout = pauseTime;
                        isDeleting = true;
                    } else if (isDeleting && charIndex === 0) {
                        isDeleting = false;
                        phraseIndex = (phraseIndex + 1) % phrases.length;
                    }

                    setTimeout(type, timeout);
                }

                type();
            });
        }
    };

    /**
     * Animated Counter
     * Cuenta números con animación
     */
    Drupal.behaviors.jarabaPremiumCounter = {
        attach: function (context) {
            once('counter', '[data-counter-duration]', context).forEach(function (section) {
                const duration = parseInt(section.dataset.counterDuration) || 2000;
                const counters = section.querySelectorAll('[data-target]');

                const observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            counters.forEach(function (counter) {
                                const target = parseFloat(counter.dataset.target);
                                const start = 0;
                                const startTime = performance.now();

                                function updateCounter(currentTime) {
                                    const elapsed = currentTime - startTime;
                                    const progress = Math.min(elapsed / duration, 1);
                                    const easeOutQuart = 1 - Math.pow(1 - progress, 4);
                                    const current = start + (target - start) * easeOutQuart;

                                    counter.textContent = target % 1 === 0
                                        ? Math.round(current).toLocaleString('es-ES')
                                        : current.toFixed(1).toLocaleString('es-ES');

                                    if (progress < 1) {
                                        requestAnimationFrame(updateCounter);
                                    }
                                }

                                requestAnimationFrame(updateCounter);
                            });
                            observer.disconnect();
                        }
                    });
                }, { threshold: 0.3 });

                observer.observe(section);
            });
        }
    };

    /**
     * Scroll Reveal
     * Revela elementos al hacer scroll con Intersection Observer
     */
    Drupal.behaviors.jarabaPremiumScrollReveal = {
        attach: function (context) {
            once('scrollReveal', '[data-reveal]', context).forEach(function (element) {
                const animation = element.dataset.animation || 'fade-up';
                const delay = parseInt(element.dataset.delay) || 0;

                element.classList.add('jaraba-reveal', `jaraba-reveal--${animation}`);
                element.style.transitionDelay = `${delay}ms`;

                const observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            observer.unobserve(entry.target);
                        }
                    });
                }, {
                    threshold: parseFloat(element.closest('[data-threshold]')?.dataset.threshold) || 0.2
                });

                observer.observe(element);
            });
        }
    };

    /**
     * Comparison Slider
     * Slider de antes/después
     */
    Drupal.behaviors.jarabaPremiumComparisonSlider = {
        attach: function (context) {
            once('comparison', '.jaraba-comparison-item', context).forEach(function (item) {
                const slider = item.querySelector('.jaraba-comparison-item__slider');
                const afterEl = item.querySelector('.jaraba-comparison-item__after');
                const initial = parseInt(item.dataset.initial) || 50;
                let isDragging = false;

                // Set initial position
                updatePosition(initial);

                function updatePosition(percent) {
                    percent = Math.max(0, Math.min(100, percent));
                    slider.style.left = percent + '%';
                    afterEl.style.clipPath = `inset(0 ${100 - percent}% 0 0)`;
                }

                function handleMove(e) {
                    if (!isDragging) return;
                    const rect = item.getBoundingClientRect();
                    const x = (e.clientX || e.touches[0].clientX) - rect.left;
                    const percent = (x / rect.width) * 100;
                    updatePosition(percent);
                }

                slider.addEventListener('mousedown', () => isDragging = true);
                slider.addEventListener('touchstart', () => isDragging = true);
                document.addEventListener('mouseup', () => isDragging = false);
                document.addEventListener('touchend', () => isDragging = false);
                item.addEventListener('mousemove', handleMove);
                item.addEventListener('touchmove', handleMove);
            });
        }
    };

    /**
     * Parallax Effect
     * Efecto parallax en scroll
     */
    Drupal.behaviors.jarabaPremiumParallax = {
        attach: function (context) {
            once('parallax', '[data-parallax]', context).forEach(function (section) {
                const background = section.querySelector('.jaraba-parallax-hero__background');
                const speed = parseFloat(section.dataset.parallaxSpeed) || 0.5;

                if (!background) return;

                function updateParallax() {
                    const rect = section.getBoundingClientRect();
                    const scrolled = window.scrollY;
                    const offset = rect.top + scrolled;
                    const yPos = -(scrolled - offset) * speed;
                    background.style.transform = `translate3d(0, ${yPos}px, 0)`;
                }

                window.addEventListener('scroll', updateParallax, { passive: true });
                updateParallax();
            });
        }
    };

    /**
     * 3D Tilt Effect
     * Efecto 3D tilt en tarjetas
     */
    Drupal.behaviors.jarabaPremiumTilt = {
        attach: function (context) {
            once('tilt', '[data-tilt]', context).forEach(function (card) {
                const intensity = card.closest('.jaraba-floating-cards--tilt-subtle') ? 5 :
                    card.closest('.jaraba-floating-cards--tilt-strong') ? 20 : 10;

                card.addEventListener('mousemove', function (e) {
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    const centerX = rect.width / 2;
                    const centerY = rect.height / 2;
                    const rotateX = ((y - centerY) / centerY) * -intensity;
                    const rotateY = ((x - centerX) / centerX) * intensity;

                    card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.02, 1.02, 1.02)`;
                });

                card.addEventListener('mouseleave', function () {
                    card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale3d(1, 1, 1)';
                });
            });
        }
    };

    /**
     * Spotlight Effect
     * Efecto de foco que sigue el cursor
     */
    Drupal.behaviors.jarabaPremiumSpotlight = {
        attach: function (context) {
            once('spotlight', '[data-spotlight]', context).forEach(function (section) {
                const effect = section.querySelector('.jaraba-spotlight__effect');
                if (!effect) return;

                section.addEventListener('mousemove', function (e) {
                    const rect = section.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    effect.style.background = `radial-gradient(circle at ${x}px ${y}px, rgba(255,255,255,0.15) 0%, transparent 50%)`;
                });

                section.addEventListener('mouseleave', function () {
                    effect.style.background = 'transparent';
                });
            });
        }
    };

    /**
     * Sticky Scroll
     * Contenido fijo con scroll text
     */
    Drupal.behaviors.jarabaPremiumStickyScroll = {
        attach: function (context) {
            once('stickyScroll', '.jaraba-sticky-scroll', context).forEach(function (section) {
                const sections = section.querySelectorAll('.jaraba-sticky-scroll__section');

                const observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            sections.forEach(s => s.classList.remove('is-active'));
                            entry.target.classList.add('is-active');
                        }
                    });
                }, {
                    threshold: 0.5,
                    rootMargin: '-40% 0px -40% 0px'
                });

                sections.forEach(s => observer.observe(s));
            });
        }
    };
    /**
     * Animated Beam Effect
     * Connects center element to surrounding icons with animated SVG lines
     */
    Drupal.behaviors.jarabaPremiumAnimatedBeam = {
        attach: function (context) {
            once('animatedBeam', '[data-block-type="animated_beam"]', context).forEach(function (section) {
                const svg = section.querySelector('.jaraba-animated-beam__svg');
                const center = section.querySelector('.jaraba-animated-beam__center');
                const elements = section.querySelectorAll('.jaraba-animated-beam__element');
                const lines = svg.querySelectorAll('.jaraba-animated-beam__line');
                const visualization = section.querySelector('.jaraba-animated-beam__visualization');

                if (!svg || !center || elements.length === 0) return;

                function updateBeams() {
                    const vizRect = visualization.getBoundingClientRect();
                    const centerRect = center.getBoundingClientRect();
                    const cx = centerRect.left + centerRect.width / 2 - vizRect.left;
                    const cy = centerRect.top + centerRect.height / 2 - vizRect.top;

                    // Scale to viewBox coordinates (600x400)
                    const scaleX = 600 / vizRect.width;
                    const scaleY = 400 / vizRect.height;

                    elements.forEach(function (el, i) {
                        const elRect = el.getBoundingClientRect();
                        const ex = elRect.left + elRect.width / 2 - vizRect.left;
                        const ey = elRect.top + elRect.height / 2 - vizRect.top;

                        if (lines[i]) {
                            lines[i].setAttribute('x1', cx * scaleX);
                            lines[i].setAttribute('y1', cy * scaleY);
                            lines[i].setAttribute('x2', ex * scaleX);
                            lines[i].setAttribute('y2', ey * scaleY);
                        }
                    });
                }

                // Initial update
                updateBeams();

                // Update on resize
                window.addEventListener('resize', updateBeams, { passive: true });

                // Add pulsing animation to lines
                lines.forEach(function (line, i) {
                    line.style.animationDelay = (i * 0.3) + 's';
                });
            });
        }
    };

    /**
     * Card Stack 3D Navigation
     * Navegación para el carrusel de tarjetas 3D
     */
    Drupal.behaviors.jarabaPremiumCardStack = {
        attach: function (context) {
            once('cardStack', '.jaraba-card-stack', context).forEach(function (section) {
                const cards = section.querySelectorAll('.jaraba-card-stack__card');
                const dots = section.querySelectorAll('.jaraba-card-stack__dot');
                const prevBtn = section.querySelector('.jaraba-card-stack__prev');
                const nextBtn = section.querySelector('.jaraba-card-stack__next');
                const autoplay = section.dataset.autoplay === 'true';
                const speed = section.dataset.speed || 'medium';

                if (cards.length === 0) return;

                let currentIndex = 0;
                let autoplayInterval = null;

                // Velocidades de autoplay
                const speeds = {
                    slow: 6000,
                    medium: 4000,
                    fast: 2000
                };

                /**
                 * Navega a la tarjeta específica
                 * @param {number} index - Índice de la tarjeta
                 */
                function goToCard(index) {
                    // Normalizar índice
                    if (index < 0) index = cards.length - 1;
                    if (index >= cards.length) index = 0;

                    currentIndex = index;

                    // Actualizar posición de tarjetas con transform 3D
                    cards.forEach(function (card, i) {
                        const offset = i - currentIndex;
                        const absOffset = Math.abs(offset);

                        // Calcular transformaciones 3D
                        const translateZ = -absOffset * 40;
                        const translateY = absOffset * 20;
                        const translateX = offset * 15;
                        const rotateY = offset * -5;
                        const rotateX = absOffset * 2;
                        const scale = 1 - absOffset * 0.08;
                        const opacity = 1 - absOffset * 0.25;

                        card.style.transform = `
                            translateZ(${translateZ}px)
                            translateY(${translateY}px)
                            translateX(${translateX}px)
                            rotateY(${rotateY}deg)
                            rotateX(${rotateX}deg)
                            scale(${Math.max(scale, 0.7)})
                        `;
                        card.style.opacity = Math.max(opacity, 0.3);
                        card.style.zIndex = cards.length - absOffset;

                        // Deshabilitar pointer events excepto para la tarjeta activa
                        card.style.pointerEvents = i === currentIndex ? 'auto' : 'none';
                    });

                    // Actualizar dots
                    dots.forEach(function (dot, i) {
                        dot.classList.toggle('is-active', i === currentIndex);
                        dot.setAttribute('aria-selected', i === currentIndex ? 'true' : 'false');
                    });
                }

                /**
                 * Va a la tarjeta siguiente
                 */
                function nextCard() {
                    goToCard(currentIndex + 1);
                }

                /**
                 * Va a la tarjeta anterior
                 */
                function prevCard() {
                    goToCard(currentIndex - 1);
                }

                /**
                 * Inicia el autoplay
                 */
                function startAutoplay() {
                    if (autoplay && !autoplayInterval) {
                        autoplayInterval = setInterval(nextCard, speeds[speed] || speeds.medium);
                    }
                }

                /**
                 * Detiene el autoplay
                 */
                function stopAutoplay() {
                    if (autoplayInterval) {
                        clearInterval(autoplayInterval);
                        autoplayInterval = null;
                    }
                }

                // Event listeners para navegación
                if (prevBtn) {
                    prevBtn.addEventListener('click', function () {
                        stopAutoplay();
                        prevCard();
                        startAutoplay();
                    });
                }

                if (nextBtn) {
                    nextBtn.addEventListener('click', function () {
                        stopAutoplay();
                        nextCard();
                        startAutoplay();
                    });
                }

                // Event listeners para dots
                dots.forEach(function (dot, i) {
                    dot.addEventListener('click', function () {
                        stopAutoplay();
                        goToCard(i);
                        startAutoplay();
                    });
                });

                // Pausar autoplay al hacer hover
                section.addEventListener('mouseenter', stopAutoplay);
                section.addEventListener('mouseleave', startAutoplay);

                // Inicializar
                goToCard(0);
                startAutoplay();
            });
        }
    };

})(Drupal, once);
