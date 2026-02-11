/**
 * @file
 * Site Builder Dashboard - Interactive behaviors.
 *
 * Features:
 * - Particles animation on header (same pattern as Content Hub)
 *
 * NOTE: El slide-panel para "Nueva Página" es manejado por la library
 * global ecosistema_jaraba_theme/slide-panel via data-slide-panel attributes.
 * Ver: .agent/workflows/slide-panel-modales.md
 */

(function (Drupal, once) {
    'use strict';

    /**
     * Initialize dashboard particles animation.
     * Same implementation as contentHubParticles for consistency.
     */
    Drupal.behaviors.siteBuilderParticles = {
        attach: function (context) {
            once('site-builder-particles', '#site-builder-particles', context).forEach(function (canvas) {
                const ctx = canvas.getContext('2d');

                // Set canvas size
                function resize() {
                    canvas.width = canvas.offsetWidth;
                    canvas.height = canvas.offsetHeight;
                }
                resize();
                window.addEventListener('resize', resize);

                // Particles configuration
                const particles = [];
                const particleCount = 30;
                const colors = ['rgba(255,255,255,0.3)', 'rgba(255,255,255,0.2)', 'rgba(255,255,255,0.1)'];

                // Create particles
                for (let i = 0; i < particleCount; i++) {
                    particles.push({
                        x: Math.random() * canvas.width,
                        y: Math.random() * canvas.height,
                        radius: Math.random() * 3 + 1,
                        color: colors[Math.floor(Math.random() * colors.length)],
                        vx: (Math.random() - 0.5) * 0.5,
                        vy: (Math.random() - 0.5) * 0.5
                    });
                }

                // Animation loop
                function animate() {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);

                    particles.forEach(function (p) {
                        // Update position
                        p.x += p.vx;
                        p.y += p.vy;

                        // Wrap around edges
                        if (p.x < 0) p.x = canvas.width;
                        if (p.x > canvas.width) p.x = 0;
                        if (p.y < 0) p.y = canvas.height;
                        if (p.y > canvas.height) p.y = 0;

                        // Draw particle
                        ctx.beginPath();
                        ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
                        ctx.fillStyle = p.color;
                        ctx.fill();
                    });

                    // Draw connections between nearby particles
                    particles.forEach(function (p1, i) {
                        particles.slice(i + 1).forEach(function (p2) {
                            var dx = p1.x - p2.x;
                            var dy = p1.y - p2.y;
                            var dist = Math.sqrt(dx * dx + dy * dy);

                            if (dist < 100) {
                                ctx.beginPath();
                                ctx.moveTo(p1.x, p1.y);
                                ctx.lineTo(p2.x, p2.y);
                                ctx.strokeStyle = 'rgba(255,255,255,' + (0.1 * (1 - dist / 100)) + ')';
                                ctx.stroke();
                            }
                        });
                    });

                    requestAnimationFrame(animate);
                }

                animate();
            });
        }
    };

})(Drupal, once);
