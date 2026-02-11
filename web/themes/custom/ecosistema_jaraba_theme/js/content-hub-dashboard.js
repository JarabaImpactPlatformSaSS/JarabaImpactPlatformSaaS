/**
 * @file
 * Content Hub Dashboard - Comportamientos interactivos.
 *
 * Este archivo gestiona la funcionalidad JavaScript del dashboard del Content Hub:
 * - Animación de partículas en el encabezado premium
 * - Filtros de artículos (Todo/Publicados/Borradores)
 * - Búsqueda en tiempo real de artículos
 *
 * @see content-hub-dashboard-frontend.html.twig
 */

(function (Drupal) {
    'use strict';

    /**
     * Inicializa la animación de partículas del encabezado.
     *
     * Crea un efecto visual premium con partículas flotantes y conexiones
     * entre ellas, similar al estilo de la landing page principal.
     * Las partículas se mueven lentamente y se conectan con líneas cuando
     * están cerca, creando un efecto de red dinámica.
     */
    Drupal.behaviors.contentHubParticles = {
        attach: function (context) {
            const canvas = context.querySelector('#dashboard-particles');
            if (!canvas || canvas.dataset.initialized) return;

            canvas.dataset.initialized = 'true';
            const ctx = canvas.getContext('2d');

            // Ajustar tamaño del canvas al contenedor
            function resize() {
                canvas.width = canvas.offsetWidth;
                canvas.height = canvas.offsetHeight;
            }
            resize();
            window.addEventListener('resize', resize);

            // FE-02: Guardar referencia para cancelar en detach.
            let animationId = null;

            // Configuración de partículas
            const particles = [];
            const particleCount = 30;
            const colors = ['rgba(255,255,255,0.3)', 'rgba(255,255,255,0.2)', 'rgba(255,255,255,0.1)'];

            // Crear partículas iniciales con posiciones y velocidades aleatorias
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

            // Bucle de animación
            function animate() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);

                particles.forEach(p => {
                    // Actualizar posición
                    p.x += p.vx;
                    p.y += p.vy;

                    // Envolver partículas que salen del borde (efecto continuo)
                    if (p.x < 0) p.x = canvas.width;
                    if (p.x > canvas.width) p.x = 0;
                    if (p.y < 0) p.y = canvas.height;
                    if (p.y > canvas.height) p.y = 0;

                    // Dibujar partícula como círculo
                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
                    ctx.fillStyle = p.color;
                    ctx.fill();
                });

                // Dibujar conexiones entre partículas cercanas (efecto red)
                particles.forEach((p1, i) => {
                    particles.slice(i + 1).forEach(p2 => {
                        const dx = p1.x - p2.x;
                        const dy = p1.y - p2.y;
                        const dist = Math.sqrt(dx * dx + dy * dy);

                        // Conectar si están a menos de 100px de distancia
                        if (dist < 100) {
                            ctx.beginPath();
                            ctx.moveTo(p1.x, p1.y);
                            ctx.lineTo(p2.x, p2.y);
                            // Opacidad proporcional a la cercanía
                            ctx.strokeStyle = `rgba(255,255,255,${0.1 * (1 - dist / 100)})`;
                            ctx.stroke();
                        }
                    });
                });

                animationId = requestAnimationFrame(animate);
            }

            animate();

            // FE-02: Guardar referencia para cleanup.
            canvas._particleAnimationId = animationId;
            canvas._particleResizeHandler = resize;
        },
        detach: function (context, settings, trigger) {
            if (trigger !== 'unload') return;
            const canvas = context.querySelector('#dashboard-particles');
            if (!canvas) return;
            if (canvas._particleAnimationId) {
                cancelAnimationFrame(canvas._particleAnimationId);
            }
            if (canvas._particleResizeHandler) {
                window.removeEventListener('resize', canvas._particleResizeHandler);
            }
            delete canvas.dataset.initialized;
        }
    };

    /**
     * Inicializa los filtros de artículos y la búsqueda.
     *
     * Los filtros permiten al usuario ver:
     * - Todo: todos los artículos
     * - Publicados: solo artículos con status='published'
     * - Borradores: solo artículos con status='draft'
     *
     * NOTA IMPORTANTE: Los filtros se inicializan siempre, incluso si no hay
     * artículos en la lista (#article-list no existe). Esto permite que el
     * toggle visual funcione correctamente aunque la lista esté vacía.
     * El filtrado real solo se aplica cuando hay artículos.
     *
     * @see _content-hub.scss para los estilos de .filter-pill--active
     */
    Drupal.behaviors.contentHubFilters = {
        attach: function (context) {
            // Botones de filtrado (pills)
            const pills = context.querySelectorAll('.filter-pill');
            const articleList = context.querySelector('#article-list');

            // Inicializar siempre los pills para el toggle visual,
            // incluso sin lista de artículos (cuando está vacía)
            if (pills.length) {
                pills.forEach(pill => {
                    // Evitar doble inicialización en re-renders AJAX
                    if (pill.dataset.initialized) return;
                    pill.dataset.initialized = 'true';

                    pill.addEventListener('click', function () {
                        // Actualizar estado activo visual (siempre funciona)
                        pills.forEach(p => p.classList.remove('filter-pill--active'));
                        this.classList.add('filter-pill--active');

                        // Filtrar artículos solo si la lista existe
                        if (articleList) {
                            const filter = this.dataset.filter;
                            const items = articleList.querySelectorAll('.article-list__item');

                            items.forEach(item => {
                                const status = item.dataset.status;
                                if (filter === 'all') {
                                    item.style.display = '';
                                } else if (filter === 'published' && status === 'published') {
                                    item.style.display = '';
                                } else if (filter === 'draft' && status === 'draft') {
                                    item.style.display = '';
                                } else {
                                    item.style.display = 'none';
                                }
                            });
                        }
                    });
                });
            }

            // Campo de búsqueda en tiempo real
            const searchInput = context.querySelector('#article-search');
            if (searchInput && articleList && !searchInput.dataset.initialized) {
                searchInput.dataset.initialized = 'true';

                searchInput.addEventListener('input', function () {
                    const query = this.value.toLowerCase();
                    const items = articleList.querySelectorAll('.article-list__item');

                    items.forEach(item => {
                        const title = item.querySelector('.article-list__title');
                        const text = title ? title.textContent.toLowerCase() : '';

                        // Mostrar si coincide con la búsqueda o si está vacía
                        if (query === '' || text.includes(query)) {
                            item.style.display = '';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            }
        }
    };

})(Drupal);
