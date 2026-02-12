/**
 * @file
 * JavaScript para el dashboard de contenido interactivo.
 *
 * Incluye:
 * - Sistema de partículas animadas (Canvas)
 * - Slide-panel para generación IA
 * - Manejo de formulario de generación
 * - Filtrado de contenido
 */

(function (Drupal, once) {
    'use strict';

    /**
     * Dashboard Particles - Network Animation
     */
    Drupal.behaviors.interactiveDashboardParticles = {
        attach: function (context) {
            once('interactive-particles', '#dashboard-particles', context).forEach(function (canvas) {
                const ctx = canvas.getContext('2d');
                let animationId;
                let particles = [];
                const particleCount = 50;

                function resize() {
                    const parent = canvas.parentElement;
                    canvas.width = parent.offsetWidth;
                    canvas.height = parent.offsetHeight;
                }

                function createParticle() {
                    return {
                        x: Math.random() * canvas.width,
                        y: Math.random() * canvas.height,
                        vx: (Math.random() - 0.5) * 0.5,
                        vy: (Math.random() - 0.5) * 0.5,
                        radius: Math.random() * 2 + 1
                    };
                }

                function init() {
                    particles = [];
                    for (let i = 0; i < particleCount; i++) {
                        particles.push(createParticle());
                    }
                }

                function draw() {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);

                    // Draw connections
                    ctx.strokeStyle = 'rgba(255, 255, 255, 0.15)';
                    ctx.lineWidth = 0.5;
                    for (let i = 0; i < particles.length; i++) {
                        for (let j = i + 1; j < particles.length; j++) {
                            const dx = particles[i].x - particles[j].x;
                            const dy = particles[i].y - particles[j].y;
                            const dist = Math.sqrt(dx * dx + dy * dy);
                            if (dist < 100) {
                                ctx.beginPath();
                                ctx.moveTo(particles[i].x, particles[i].y);
                                ctx.lineTo(particles[j].x, particles[j].y);
                                ctx.stroke();
                            }
                        }
                    }

                    // Draw particles
                    ctx.fillStyle = 'rgba(255, 255, 255, 0.6)';
                    particles.forEach(function (p) {
                        ctx.beginPath();
                        ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
                        ctx.fill();
                    });
                }

                function update() {
                    particles.forEach(function (p) {
                        p.x += p.vx;
                        p.y += p.vy;

                        // Bounce off edges
                        if (p.x < 0 || p.x > canvas.width) p.vx *= -1;
                        if (p.y < 0 || p.y > canvas.height) p.vy *= -1;
                    });
                }

                function animate() {
                    update();
                    draw();
                    animationId = requestAnimationFrame(animate);
                }

                // Initialize
                resize();
                init();
                animate();

                // Handle resize
                window.addEventListener('resize', function () {
                    cancelAnimationFrame(animationId);
                    resize();
                    init();
                    animate();
                });
            });
        }
    };

    /**
     * AI Generator Slide-Panel
     */
    Drupal.behaviors.interactiveAIGenerator = {
        attach: function (context) {
            const panel = once('ai-generator-panel', '#ai-generator-panel', context)[0];
            if (!panel) return;

            const openBtn = document.getElementById('btn-ai-generate');
            const closeBtn = panel.querySelector('.slide-panel__close');
            const overlay = panel.querySelector('.slide-panel__overlay');
            const form = document.getElementById('ai-generator-form');
            const typeSelect = document.getElementById('ai-content-type');
            const groupDifficulty = document.getElementById('group-difficulty');
            const groupCount = document.getElementById('group-count');
            const groupObjective = document.getElementById('group-objective');
            const submitBtn = document.getElementById('btn-generate-submit');
            const resultPreview = document.getElementById('ai-result-preview');

            let generatedData = null;

            // Open panel
            if (openBtn) {
                openBtn.addEventListener('click', function () {
                    panel.classList.add('slide-panel--open');
                    panel.setAttribute('aria-hidden', 'false');
                    document.body.style.overflow = 'hidden';
                });
            }

            // Close panel
            function closePanel() {
                panel.classList.remove('slide-panel--open');
                panel.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }

            if (closeBtn) {
                closeBtn.addEventListener('click', closePanel);
            }
            if (overlay) {
                overlay.addEventListener('click', closePanel);
            }

            // ESC key closes panel
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && panel.classList.contains('slide-panel--open')) {
                    closePanel();
                }
            });

            // Type change - toggle fields
            if (typeSelect) {
                typeSelect.addEventListener('change', function () {
                    const type = this.value;

                    // Quiz: show difficulty and count
                    groupDifficulty.classList.toggle('hidden', type === 'scenario');
                    groupCount.classList.toggle('hidden', false);
                    groupObjective.classList.toggle('hidden', type !== 'scenario');
                });
            }

            // Form submit - generate content
            if (form) {
                form.addEventListener('submit', async function (e) {
                    e.preventDefault();

                    const formData = new FormData(form);
                    const data = Object.fromEntries(formData.entries());

                    // Show loading state
                    const btnText = submitBtn.querySelector('.btn__text');
                    const btnLoading = submitBtn.querySelector('.btn__loading');
                    btnText.classList.add('hidden');
                    btnLoading.classList.remove('hidden');
                    submitBtn.disabled = true;

                    try {
                        const response = await fetch('/api/v1/interactive/generate', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                type: data.type,
                                title: data.title,
                                source_text: data.source_text,
                                difficulty: data.difficulty,
                                count: parseInt(data.count, 10),
                                learning_objective: data.learning_objective,
                                create_entity: false
                            })
                        });

                        const result = await response.json();

                        if (result.status === 'success') {
                            generatedData = {
                                title: data.title,
                                type: data.type,
                                difficulty: data.difficulty,
                                content_data: result.content_data
                            };
                            showPreview(result.content_data, data.type);
                        } else {
                            alert(result.message || Drupal.t('Error al generar contenido'));
                        }
                    } catch (error) {
                        console.error('AI generation error:', error);
                        alert(Drupal.t('Error de conexión. Por favor, inténtalo de nuevo.'));
                    } finally {
                        // Hide loading state
                        btnText.classList.remove('hidden');
                        btnLoading.classList.add('hidden');
                        submitBtn.disabled = false;
                    }
                });
            }

            // Show preview of generated content
            function showPreview(contentData, type) {
                form.classList.add('hidden');
                resultPreview.classList.remove('hidden');

                const previewContent = resultPreview.querySelector('.ai-result-preview__content');
                let html = '';

                if (type === 'quiz' && contentData.questions) {
                    html = '<ul class="ai-preview-list">';
                    contentData.questions.forEach(function (q, i) {
                        html += '<li class="ai-preview-list__item">';
                        html += '<strong>' + (i + 1) + '.</strong> ' + q.question;
                        html += '</li>';
                    });
                    html += '</ul>';
                    html += '<p class="ai-preview-summary">' +
                        Drupal.t('@count preguntas generadas', { '@count': contentData.questions.length }) +
                        '</p>';
                } else if (type === 'flashcards' && contentData.cards) {
                    html = '<ul class="ai-preview-list">';
                    contentData.cards.forEach(function (card, i) {
                        html += '<li class="ai-preview-list__item">';
                        html += '<strong>' + Drupal.t('Tarjeta') + ' ' + (i + 1) + ':</strong> ' + card.front;
                        html += '</li>';
                    });
                    html += '</ul>';
                } else if (type === 'scenario') {
                    html = '<p><strong>' + (contentData.title || Drupal.t('Escenario')) + '</strong></p>';
                    html += '<p>' + (contentData.introduction || '') + '</p>';
                    if (contentData.nodes) {
                        html += '<p class="ai-preview-summary">' +
                            Drupal.t('@count nodos de decisión', { '@count': contentData.nodes.length }) +
                            '</p>';
                    }
                } else {
                    html = '<pre>' + JSON.stringify(contentData, null, 2) + '</pre>';
                }

                previewContent.innerHTML = html;
            }

            // Discard button
            const discardBtn = document.getElementById('btn-discard');
            if (discardBtn) {
                discardBtn.addEventListener('click', function () {
                    generatedData = null;
                    resultPreview.classList.add('hidden');
                    form.classList.remove('hidden');
                    form.reset();
                });
            }

            // Save button
            const saveBtn = document.getElementById('btn-save-content');
            if (saveBtn) {
                saveBtn.addEventListener('click', async function () {
                    if (!generatedData) return;

                    saveBtn.disabled = true;
                    saveBtn.innerHTML = '<span class="spinner spinner--sm"></span> ' + Drupal.t('Guardando...');

                    try {
                        const response = await fetch('/api/v1/interactive/generate', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                type: generatedData.type,
                                title: generatedData.title,
                                difficulty: generatedData.difficulty,
                                source_text: 'Generated content',
                                content_data: generatedData.content_data,
                                create_entity: true
                            })
                        });

                        const result = await response.json();

                        if (result.status === 'success' && result.entity_id) {
                            // Redirect to edit page or show success
                            window.location.href = '/es/interactive/' + result.entity_id + '/edit';
                        } else {
                            alert(result.message || Drupal.t('Error al guardar contenido'));
                            saveBtn.disabled = false;
                            saveBtn.innerHTML = Drupal.t('Guardar Contenido');
                        }
                    } catch (error) {
                        console.error('Save error:', error);
                        alert(Drupal.t('Error de conexión.'));
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = Drupal.t('Guardar Contenido');
                    }
                });
            }
        }
    };

    /**
     * Content Search Filter
     */
    Drupal.behaviors.interactiveDashboardSearch = {
        attach: function (context) {
            const searchInput = once('interactive-search', '#interactive-search', context)[0];
            if (!searchInput) return;

            const cards = document.querySelectorAll('.pb-card');

            searchInput.addEventListener('input', function () {
                const query = this.value.toLowerCase().trim();

                cards.forEach(function (card) {
                    const title = card.querySelector('.pb-card__title')?.textContent?.toLowerCase() || '';
                    const match = title.includes(query);
                    card.style.display = match ? '' : 'none';
                });
            });
        }
    };

    /**
     * Smart Import - URL and Video AI Import (Sprint 5)
     */
    Drupal.behaviors.interactiveSmartImport = {
        attach: function (context) {
            const importPanel = once('smart-import-panel', '#smart-import-panel', context)[0];
            if (!importPanel) return;

            const openBtn = document.getElementById('btn-smart-import');
            const closeBtn = importPanel.querySelector('.slide-panel__close');
            const overlay = importPanel.querySelector('.slide-panel__overlay');
            const tabs = importPanel.querySelectorAll('.smart-import-tab');
            const tabContents = importPanel.querySelectorAll('.smart-import-content');

            // Panel open/close
            if (openBtn) {
                openBtn.addEventListener('click', function () {
                    importPanel.classList.add('slide-panel--open');
                    importPanel.setAttribute('aria-hidden', 'false');
                    document.body.style.overflow = 'hidden';
                });
            }

            function closePanel() {
                importPanel.classList.remove('slide-panel--open');
                importPanel.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }

            if (closeBtn) closeBtn.addEventListener('click', closePanel);
            if (overlay) overlay.addEventListener('click', closePanel);

            // Tab switching
            tabs.forEach(tab => {
                tab.addEventListener('click', function () {
                    const target = this.dataset.tab;

                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));

                    this.classList.add('active');
                    document.getElementById('tab-content-' + target)?.classList.add('active');
                });
            });

            // URL Import Form
            const urlForm = document.getElementById('smart-import-url-form');
            if (urlForm) {
                urlForm.addEventListener('submit', async function (e) {
                    e.preventDefault();

                    const urlInput = urlForm.querySelector('[name="import_url"]');
                    const typeSelect = urlForm.querySelector('[name="import_content_type"]');
                    const submitBtn = urlForm.querySelector('.btn-import-url');
                    const resultArea = document.getElementById('url-import-result');

                    if (!urlInput.value.trim()) {
                        alert(Drupal.t('Por favor ingresa una URL'));
                        return;
                    }

                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner spinner--sm"></span> ' + Drupal.t('Extrayendo...');

                    try {
                        const response = await fetch('/api/v1/interactive/import-url', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                url: urlInput.value.trim(),
                                content_type: typeSelect?.value || 'quiz',
                                difficulty: urlForm.querySelector('[name="difficulty"]')?.value || 'intermediate',
                                count: parseInt(urlForm.querySelector('[name="count"]')?.value || 5),
                                create_entity: false
                            })
                        });

                        const result = await response.json();

                        if (result.success) {
                            resultArea.classList.remove('hidden');
                            resultArea.innerHTML = `
                                <div class="import-success">
                                    <h4>✅ ${Drupal.t('Contenido extraído')}</h4>
                                    <p><strong>${Drupal.t('Fuente')}:</strong> ${result.source?.title || result.source?.url}</p>
                                    <p><strong>${Drupal.t('Caracteres extraídos')}:</strong> ${result.source?.extracted_text_length}</p>
                                    <p><strong>${Drupal.t('Tipo generado')}:</strong> ${result.content_type}</p>
                                    <button type="button" class="btn btn--primary btn-save-import" data-result='${JSON.stringify(result)}'>
                                        ${Drupal.t('Guardar como Contenido')}
                                    </button>
                                </div>
                            `;
                        } else {
                            resultArea.classList.remove('hidden');
                            resultArea.innerHTML = `<div class="import-error">❌ ${result.error || result.message}</div>`;
                        }
                    } catch (error) {
                        console.error('URL import error:', error);
                        resultArea.innerHTML = `<div class="import-error">❌ ${Drupal.t('Error de conexión')}</div>`;
                        resultArea.classList.remove('hidden');
                    } finally {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = Drupal.t('Importar desde URL');
                    }
                });
            }

            // Video Import Form
            const videoForm = document.getElementById('smart-import-video-form');
            if (videoForm) {
                videoForm.addEventListener('submit', async function (e) {
                    e.preventDefault();

                    const videoInput = videoForm.querySelector('[name="video_url"]');
                    const typeSelect = videoForm.querySelector('[name="video_content_type"]');
                    const submitBtn = videoForm.querySelector('.btn-import-video');
                    const resultArea = document.getElementById('video-import-result');

                    if (!videoInput.value.trim()) {
                        alert(Drupal.t('Por favor ingresa una URL de video'));
                        return;
                    }

                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner spinner--sm"></span> ' + Drupal.t('Transcribiendo...');

                    try {
                        const response = await fetch('/api/v1/interactive/import-video', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                video_url: videoInput.value.trim(),
                                content_type: typeSelect?.value || 'quiz',
                                difficulty: videoForm.querySelector('[name="video_difficulty"]')?.value || 'intermediate',
                                count: parseInt(videoForm.querySelector('[name="video_count"]')?.value || 5),
                                include_timestamps: true,
                                create_entity: false
                            })
                        });

                        const result = await response.json();

                        if (result.success) {
                            resultArea.classList.remove('hidden');
                            resultArea.innerHTML = `
                                <div class="import-success">
                                    <h4>✅ ${Drupal.t('Video transcrito')}</h4>
                                    <p><strong>${Drupal.t('Duración')}:</strong> ${result.source?.duration}</p>
                                    <p><strong>${Drupal.t('Caracteres transcritos')}:</strong> ${result.source?.transcript_length}</p>
                                    <p><strong>${Drupal.t('Tipo generado')}:</strong> ${result.content_type}</p>
                                    <details>
                                        <summary>${Drupal.t('Ver transcripción')}</summary>
                                        <pre class="transcript-preview">${result.transcript?.substring(0, 500)}...</pre>
                                    </details>
                                    <button type="button" class="btn btn--primary btn-save-import" data-result='${JSON.stringify(result)}'>
                                        ${Drupal.t('Guardar como Contenido')}
                                    </button>
                                </div>
                            `;
                        } else {
                            resultArea.classList.remove('hidden');
                            resultArea.innerHTML = `<div class="import-error">❌ ${result.error || result.message}</div>`;
                        }
                    } catch (error) {
                        console.error('Video import error:', error);
                        resultArea.innerHTML = `<div class="import-error">❌ ${Drupal.t('Error de conexión')}</div>`;
                        resultArea.classList.remove('hidden');
                    } finally {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = Drupal.t('Importar desde Video');
                    }
                });
            }

            // Save imported content
            document.addEventListener('click', async function (e) {
                if (!e.target.classList.contains('btn-save-import')) return;

                const resultData = JSON.parse(e.target.dataset.result || '{}');
                if (!resultData.content_data) return;

                e.target.disabled = true;
                e.target.innerHTML = '<span class="spinner spinner--sm"></span> ' + Drupal.t('Guardando...');

                try {
                    const endpoint = resultData.source?.type === 'video'
                        ? '/api/v1/interactive/import-video'
                        : '/api/v1/interactive/import-url';

                    const payload = resultData.source?.type === 'video'
                        ? { video_url: resultData.source.video_url, content_type: resultData.content_type, create_entity: true, title: 'Imported from Video' }
                        : { url: resultData.source.url, content_type: resultData.content_type, create_entity: true };

                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });

                    const saved = await response.json();

                    if (saved.entity_id) {
                        window.location.href = '/interactive/play/' + saved.entity_id;
                    } else {
                        alert(saved.error || Drupal.t('Error al guardar'));
                        e.target.disabled = false;
                        e.target.innerHTML = Drupal.t('Guardar como Contenido');
                    }
                } catch (err) {
                    console.error('Save error:', err);
                    e.target.disabled = false;
                    e.target.innerHTML = Drupal.t('Guardar como Contenido');
                }
            });
        }
    };

})(Drupal, once);
