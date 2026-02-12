/**
 * @file
 * AI Skills Dashboard - Interactive behaviors.
 *
 * Features:
 * - Particles animation on header (neural network effect)
 * - Skill hierarchy visualization
 *
 * @see site-builder-dashboard.js (mismo patrón de partículas)
 */

(function (Drupal, once) {
    'use strict';

    /**
     * Initialize dashboard particles animation.
     * Neural network effect representing AI skill connections.
     */
    Drupal.behaviors.skillsParticles = {
        attach: function (context) {
            once('skills-particles', '#skills-particles', context).forEach(function (canvas) {
                const ctx = canvas.getContext('2d');

                // Set canvas size
                function resize() {
                    canvas.width = canvas.offsetWidth;
                    canvas.height = canvas.offsetHeight;
                }
                resize();
                window.addEventListener('resize', resize);

                // Particles configuration - neural network style
                const particles = [];
                const particleCount = 40;
                // Colores de IA: verde innovación y blanco
                const colors = [
                    'rgba(0, 169, 165, 0.4)',  // innovation
                    'rgba(0, 169, 165, 0.25)',
                    'rgba(255, 255, 255, 0.3)',
                    'rgba(255, 255, 255, 0.2)'
                ];

                // Create particles
                for (let i = 0; i < particleCount; i++) {
                    particles.push({
                        x: Math.random() * canvas.width,
                        y: Math.random() * canvas.height,
                        radius: Math.random() * 3 + 1,
                        color: colors[Math.floor(Math.random() * colors.length)],
                        vx: (Math.random() - 0.5) * 0.6,
                        vy: (Math.random() - 0.5) * 0.6
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

                    // Draw neural connections between nearby particles
                    particles.forEach(function (p1, i) {
                        particles.slice(i + 1).forEach(function (p2) {
                            var dx = p1.x - p2.x;
                            var dy = p1.y - p2.y;
                            var dist = Math.sqrt(dx * dx + dy * dy);

                            if (dist < 120) {
                                ctx.beginPath();
                                ctx.moveTo(p1.x, p1.y);
                                ctx.lineTo(p2.x, p2.y);
                                // Verde innovación para las conexiones
                                ctx.strokeStyle = 'rgba(0, 169, 165,' + (0.15 * (1 - dist / 120)) + ')';
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
