/**
 * @file
 * CRM Dashboard particles animation.
 *
 * Partículas animadas para header premium del CRM.
 * Sigue el mismo patrón que content-hub y site-builder.
 */

(function (Drupal, once) {
    'use strict';

    /**
     * Particle animation behavior.
     */
    Drupal.behaviors.crmParticles = {
        attach: function (context) {
            // Dashboard particles
            once('crm-dashboard-particles', '#crm-particles', context).forEach(function (canvas) {
                initParticles(canvas, 'corporate');
            });

            // Pipeline particles
            once('crm-pipeline-particles', '#pipeline-particles', context).forEach(function (canvas) {
                initParticles(canvas, 'impulso');
            });
        }
    };

    /**
     * Initialize particles on canvas.
     *
     * @param {HTMLCanvasElement} canvas
     *   The canvas element.
     * @param {string} theme
     *   Color theme: 'corporate', 'impulso', 'innovacion'.
     */
    function initParticles(canvas, theme) {
        const ctx = canvas.getContext('2d');
        const particles = [];
        const particleCount = 50;

        // Theme colors
        const colors = {
            corporate: ['rgba(35, 61, 99, 0.6)', 'rgba(35, 61, 99, 0.3)', 'rgba(100, 116, 139, 0.4)'],
            impulso: ['rgba(255, 140, 66, 0.6)', 'rgba(255, 140, 66, 0.3)', 'rgba(255, 180, 120, 0.4)'],
            innovacion: ['rgba(0, 169, 165, 0.6)', 'rgba(0, 169, 165, 0.3)', 'rgba(100, 200, 200, 0.4)']
        };

        const palette = colors[theme] || colors.corporate;

        // Resize handler
        function resize() {
            canvas.width = canvas.offsetWidth;
            canvas.height = canvas.offsetHeight;
        }

        resize();
        window.addEventListener('resize', resize);

        // Create particles
        for (let i = 0; i < particleCount; i++) {
            particles.push({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                radius: Math.random() * 3 + 1,
                color: palette[Math.floor(Math.random() * palette.length)],
                speedX: (Math.random() - 0.5) * 0.5,
                speedY: (Math.random() - 0.5) * 0.5,
                opacity: Math.random() * 0.5 + 0.3
            });
        }

        // Animation loop
        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            particles.forEach(function (p) {
                // Update position
                p.x += p.speedX;
                p.y += p.speedY;

                // Wrap around edges
                if (p.x < 0) p.x = canvas.width;
                if (p.x > canvas.width) p.x = 0;
                if (p.y < 0) p.y = canvas.height;
                if (p.y > canvas.height) p.y = 0;

                // Draw particle
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
                ctx.fillStyle = p.color;
                ctx.globalAlpha = p.opacity;
                ctx.fill();
            });

            // PERF-07: Draw connections using spatial grid (O(n) vs O(n²)).
            ctx.globalAlpha = 0.1;
            ctx.strokeStyle = palette[0];
            ctx.lineWidth = 0.5;

            const cellSize = 100;
            const grid = {};
            particles.forEach(function (p, idx) {
                const cx = Math.floor(p.x / cellSize);
                const cy = Math.floor(p.y / cellSize);
                const key = cx + ',' + cy;
                if (!grid[key]) grid[key] = [];
                grid[key].push(idx);
            });

            const checked = {};
            particles.forEach(function (p, i) {
                const cx = Math.floor(p.x / cellSize);
                const cy = Math.floor(p.y / cellSize);
                for (let dx = -1; dx <= 1; dx++) {
                    for (let dy = -1; dy <= 1; dy++) {
                        const neighbors = grid[(cx + dx) + ',' + (cy + dy)];
                        if (!neighbors) continue;
                        neighbors.forEach(function (j) {
                            if (j <= i) return;
                            const pairKey = i + ':' + j;
                            if (checked[pairKey]) return;
                            checked[pairKey] = true;
                            const ddx = p.x - particles[j].x;
                            const ddy = p.y - particles[j].y;
                            if (ddx * ddx + ddy * ddy < 10000) {
                                ctx.beginPath();
                                ctx.moveTo(p.x, p.y);
                                ctx.lineTo(particles[j].x, particles[j].y);
                                ctx.stroke();
                            }
                        });
                    }
                }
            });

            ctx.globalAlpha = 1;
            requestAnimationFrame(animate);
        }

        animate();
    }

})(Drupal, once);
