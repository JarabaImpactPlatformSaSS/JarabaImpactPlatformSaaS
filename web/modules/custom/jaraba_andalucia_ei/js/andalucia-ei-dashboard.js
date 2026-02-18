/**
 * @file
 * Premium JavaScript para el dashboard Andalucía +ei.
 *
 * - Partículas animadas en hero (canvas 2D)
 * - Counter animations para valores numéricos
 * - Intersection Observer para stagger entrance animations
 * - Bridge dismiss handler
 * - FAB proactive expansion
 */

(function (Drupal, once) {
    'use strict';

    // =========================================================================
    // Hero Particles
    // =========================================================================
    Drupal.behaviors.aeiHeroParticles = {
        attach: function (context) {
            once('aei-hero-particles', '#aei-hero-particles', context).forEach(function (canvas) {
                var ctx = canvas.getContext('2d');
                var particles = [];
                var particleCount = 60;
                var mouseX = -1;
                var mouseY = -1;
                var animId;

                function resize() {
                    // Clamp to viewport to prevent horizontal overflow.
                    var w = Math.min(canvas.parentElement.clientWidth, window.innerWidth);
                    var h = canvas.parentElement.clientHeight || 400;
                    canvas.width = w;
                    canvas.height = h;
                    canvas.style.width = '100%';
                    canvas.style.height = '100%';
                }

                function createParticle() {
                    return {
                        x: Math.random() * canvas.width,
                        y: Math.random() * canvas.height,
                        vx: (Math.random() - 0.5) * 0.4,
                        vy: (Math.random() - 0.5) * 0.4,
                        radius: Math.random() * 2.5 + 0.5,
                        opacity: Math.random() * 0.5 + 0.2,
                        color: Math.random() > 0.6
                            ? 'rgba(255, 140, 66, '   // naranja-impulso
                            : Math.random() > 0.5
                                ? 'rgba(0, 169, 165, '  // verde-innovacion
                                : 'rgba(255, 255, 255, ' // blanco
                    };
                }

                function init() {
                    resize();
                    particles = [];
                    for (var i = 0; i < particleCount; i++) {
                        particles.push(createParticle());
                    }
                }

                function drawParticle(p) {
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
                    ctx.fillStyle = p.color + p.opacity + ')';
                    ctx.fill();
                }

                function drawConnections() {
                    for (var i = 0; i < particles.length; i++) {
                        for (var j = i + 1; j < particles.length; j++) {
                            var dx = particles[i].x - particles[j].x;
                            var dy = particles[i].y - particles[j].y;
                            var dist = Math.sqrt(dx * dx + dy * dy);
                            if (dist < 120) {
                                ctx.beginPath();
                                ctx.moveTo(particles[i].x, particles[i].y);
                                ctx.lineTo(particles[j].x, particles[j].y);
                                ctx.strokeStyle = 'rgba(255, 255, 255, ' + (0.08 * (1 - dist / 120)) + ')';
                                ctx.lineWidth = 0.5;
                                ctx.stroke();
                            }
                        }
                    }
                }

                function animate() {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);

                    particles.forEach(function (p) {
                        // Mouse interaction
                        if (mouseX > 0 && mouseY > 0) {
                            var dx = p.x - mouseX;
                            var dy = p.y - mouseY;
                            var dist = Math.sqrt(dx * dx + dy * dy);
                            if (dist < 100) {
                                p.vx += dx * 0.0003;
                                p.vy += dy * 0.0003;
                            }
                        }

                        p.x += p.vx;
                        p.y += p.vy;

                        // Boundary wrap
                        if (p.x < 0) p.x = canvas.width;
                        if (p.x > canvas.width) p.x = 0;
                        if (p.y < 0) p.y = canvas.height;
                        if (p.y > canvas.height) p.y = 0;

                        // Damping
                        p.vx *= 0.999;
                        p.vy *= 0.999;

                        drawParticle(p);
                    });

                    drawConnections();
                    animId = requestAnimationFrame(animate);
                }

                // Mouse tracking on hero
                canvas.parentElement.addEventListener('mousemove', function (e) {
                    var rect = canvas.getBoundingClientRect();
                    mouseX = e.clientX - rect.left;
                    mouseY = e.clientY - rect.top;
                });

                canvas.parentElement.addEventListener('mouseleave', function () {
                    mouseX = -1;
                    mouseY = -1;
                });

                // Responsive resize
                var resizeTimer;
                window.addEventListener('resize', function () {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(function () {
                        resize();
                    }, 200);
                });

                // Reduce particles on mobile
                if (window.innerWidth < 768) {
                    particleCount = 25;
                }

                // Respect prefers-reduced-motion
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    return;
                }

                init();
                animate();
            });
        }
    };

    // =========================================================================
    // Counter Animation
    // =========================================================================
    Drupal.behaviors.aeiCounterAnimation = {
        attach: function (context) {
            once('aei-counter', '[data-count-target]', context).forEach(function (el) {
                var target = parseFloat(el.getAttribute('data-count-target')) || 0;
                var duration = 1200;
                var isDecimal = target % 1 !== 0;

                // Use IntersectionObserver to trigger on visibility
                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            animateCounter(el, target, duration, isDecimal);
                            observer.unobserve(el);
                        }
                    });
                }, { threshold: 0.3 });

                observer.observe(el);
            });
        }
    };

    function animateCounter(el, target, duration, isDecimal) {
        var start = performance.now();
        var startVal = 0;

        function update(now) {
            var elapsed = now - start;
            var progress = Math.min(elapsed / duration, 1);
            // Ease out cubic
            var eased = 1 - Math.pow(1 - progress, 3);
            var current = startVal + (target - startVal) * eased;

            el.textContent = isDecimal ? current.toFixed(1) : Math.round(current);

            if (progress < 1) {
                requestAnimationFrame(update);
            } else {
                el.textContent = isDecimal ? target.toFixed(1) : Math.round(target);
            }
        }

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            el.textContent = isDecimal ? target.toFixed(1) : Math.round(target);
            return;
        }

        requestAnimationFrame(update);
    }

    // =========================================================================
    // Stagger Entrance Animations
    // =========================================================================
    Drupal.behaviors.aeiStaggerEntrance = {
        attach: function (context) {
            once('aei-stagger', '.aei-animate-in', context).forEach(function (el) {
                var observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            var stagger = parseInt(el.style.getPropertyValue('--aei-stagger') || '0', 10);
                            var delay = stagger * 120;

                            setTimeout(function () {
                                el.classList.add('aei-animate-in--visible');
                            }, delay);

                            observer.unobserve(el);
                        }
                    });
                }, { threshold: 0.1 });

                observer.observe(el);
            });
        }
    };

    // =========================================================================
    // Bridge Card Dismiss
    // =========================================================================
    Drupal.behaviors.aeiBridgeDismiss = {
        attach: function (context) {
            once('aei-bridge-dismiss', '[data-dismiss-bridge]', context).forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var card = btn.closest('.aei-bridge-card');
                    if (card) {
                        card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                        card.style.opacity = '0';
                        card.style.transform = 'translateX(20px)';
                        setTimeout(function () {
                            card.remove();
                        }, 350);
                    }
                });
            });
        }
    };

    // =========================================================================
    // Proactive AI FAB
    // =========================================================================
    Drupal.behaviors.aeiFab = {
        attach: function (context) {
            once('aei-fab', '.aei-fab__trigger', context).forEach(function (trigger) {
                trigger.addEventListener('click', function () {
                    var fab = trigger.closest('.aei-fab');
                    var expanded = trigger.getAttribute('aria-expanded') === 'true';
                    trigger.setAttribute('aria-expanded', !expanded);
                    fab.classList.toggle('aei-fab--open', !expanded);
                });

                // Close on outside click
                document.addEventListener('click', function (e) {
                    var fab = trigger.closest('.aei-fab');
                    if (fab && !fab.contains(e.target)) {
                        trigger.setAttribute('aria-expanded', 'false');
                        fab.classList.remove('aei-fab--open');
                    }
                });
            });
        }
    };

})(Drupal, once);
