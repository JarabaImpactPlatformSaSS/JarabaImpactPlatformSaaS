/**
 * @file
 * Partículas animadas para Vista Previa Premium.
 *
 * Canvas con partículas flotantes conectadas por líneas.
 * Efecto premium para header de vista previa de plantillas.
 */

(function (Drupal, once) {
    'use strict';

    Drupal.behaviors.previewParticles = {
        attach: function (context) {
            const canvas = once('preview-particles', '#preview-particles', context);

            if (!canvas.length) return;

            const c = canvas[0];
            const ctx = c.getContext('2d');
            let animationId;
            let particles = [];

            // Configuración.
            const config = {
                particleCount: 50,
                particleColor: 'rgba(134, 205, 253, 0.5)',
                lineColor: 'rgba(134, 205, 253, 0.15)',
                maxDistance: 120,
                speed: 0.3,
            };

            /**
             * Redimensiona el canvas.
             */
            function resize() {
                const rect = c.parentElement.getBoundingClientRect();
                c.width = rect.width;
                c.height = rect.height;
            }

            /**
             * Crea una partícula.
             */
            function createParticle() {
                return {
                    x: Math.random() * c.width,
                    y: Math.random() * c.height,
                    vx: (Math.random() - 0.5) * config.speed,
                    vy: (Math.random() - 0.5) * config.speed,
                    size: Math.random() * 2 + 1,
                };
            }

            /**
             * Inicializa partículas.
             */
            function init() {
                particles = [];
                for (let i = 0; i < config.particleCount; i++) {
                    particles.push(createParticle());
                }
            }

            /**
             * Actualiza posiciones.
             */
            function update() {
                particles.forEach(p => {
                    p.x += p.vx;
                    p.y += p.vy;

                    // Bounce en bordes.
                    if (p.x < 0 || p.x > c.width) p.vx *= -1;
                    if (p.y < 0 || p.y > c.height) p.vy *= -1;
                });
            }

            /**
             * Dibuja partículas y líneas.
             */
            function draw() {
                ctx.clearRect(0, 0, c.width, c.height);

                // PERF-07: Spatial grid for O(n) connection drawing.
                ctx.lineWidth = 1;
                const cellSize = config.maxDistance;
                const maxDistSq = config.maxDistance * config.maxDistance;
                const grid = {};

                particles.forEach((p, idx) => {
                    const key = Math.floor(p.x / cellSize) + ',' + Math.floor(p.y / cellSize);
                    if (!grid[key]) grid[key] = [];
                    grid[key].push(idx);
                });

                particles.forEach((p, i) => {
                    const cx = Math.floor(p.x / cellSize);
                    const cy = Math.floor(p.y / cellSize);
                    for (let gx = -1; gx <= 1; gx++) {
                        for (let gy = -1; gy <= 1; gy++) {
                            const neighbors = grid[(cx + gx) + ',' + (cy + gy)];
                            if (!neighbors) continue;
                            for (const j of neighbors) {
                                if (j <= i) continue;
                                const dx = p.x - particles[j].x;
                                const dy = p.y - particles[j].y;
                                const distSq = dx * dx + dy * dy;
                                if (distSq < maxDistSq) {
                                    const opacity = 1 - (Math.sqrt(distSq) / config.maxDistance);
                                    ctx.strokeStyle = `rgba(134, 205, 253, ${opacity * 0.15})`;
                                    ctx.beginPath();
                                    ctx.moveTo(p.x, p.y);
                                    ctx.lineTo(particles[j].x, particles[j].y);
                                    ctx.stroke();
                                }
                            }
                        }
                    }
                });

                // Dibujar partículas.
                ctx.fillStyle = config.particleColor;
                particles.forEach(p => {
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
                    ctx.fill();
                });
            }

            /**
             * Loop de animación.
             */
            function animate() {
                update();
                draw();
                animationId = requestAnimationFrame(animate);
            }

            // Iniciar.
            resize();
            init();
            animate();

            // Responsive.
            window.addEventListener('resize', () => {
                resize();
                init();
            });

            // Cleanup.
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    mutation.removedNodes.forEach((node) => {
                        if (node.contains && node.contains(c)) {
                            cancelAnimationFrame(animationId);
                            observer.disconnect();
                        }
                    });
                });
            });

            observer.observe(document.body, { childList: true, subtree: true });
        },
    };

})(Drupal, once);
