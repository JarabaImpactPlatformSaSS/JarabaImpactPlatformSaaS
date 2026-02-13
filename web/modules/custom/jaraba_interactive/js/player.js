/**
 * @file
 * Jaraba Interactive Player Core.
 *
 * Sistema de reproducción de contenido interactivo.
 * Integración xAPI para tracking LMS.
 */

(function (Drupal, drupalSettings, once) {
    'use strict';

    /**
     * Feedback Engine - Efectos visuales para respuestas.
     * Confetti para éxito, shake para errores.
     */
    class FeedbackEngine {
        constructor(container) {
            this.container = container;
            this.canvas = null;
            this.ctx = null;
            this.particles = [];
            this.animationId = null;
        }

        /**
         * Muestra explosión de confetti.
         */
        confetti() {
            this.createCanvas();
            this.particles = [];

            // Crear partículas
            const colors = ['#FF7C00', '#00A9A5', '#FFD700', '#FF69B4', '#4169E1', '#32CD32'];
            for (let i = 0; i < 150; i++) {
                this.particles.push({
                    x: this.canvas.width / 2,
                    y: this.canvas.height / 2,
                    vx: (Math.random() - 0.5) * 20,
                    vy: (Math.random() - 0.5) * 20 - 10,
                    color: colors[Math.floor(Math.random() * colors.length)],
                    size: Math.random() * 8 + 4,
                    rotation: Math.random() * 360,
                    rotationSpeed: (Math.random() - 0.5) * 10,
                    gravity: 0.3,
                    decay: 0.98
                });
            }

            this.animateConfetti();

            // Limpiar después de 3 segundos
            setTimeout(() => this.cleanup(), 3000);
        }

        createCanvas() {
            if (this.canvas) return;

            this.canvas = document.createElement('canvas');
            this.canvas.className = 'feedback-canvas';
            this.canvas.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                pointer-events: none;
                z-index: 9999;
            `;
            document.body.appendChild(this.canvas);
            this.ctx = this.canvas.getContext('2d');
            this.resizeCanvas();

            window.addEventListener('resize', () => this.resizeCanvas());
        }

        resizeCanvas() {
            if (this.canvas) {
                this.canvas.width = window.innerWidth;
                this.canvas.height = window.innerHeight;
            }
        }

        animateConfetti() {
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

            let activeParticles = 0;

            this.particles.forEach(p => {
                // Física
                p.vy += p.gravity;
                p.vx *= p.decay;
                p.vy *= p.decay;
                p.x += p.vx;
                p.y += p.vy;
                p.rotation += p.rotationSpeed;

                // Renderizar
                if (p.y < this.canvas.height + 50) {
                    this.ctx.save();
                    this.ctx.translate(p.x, p.y);
                    this.ctx.rotate((p.rotation * Math.PI) / 180);
                    this.ctx.fillStyle = p.color;
                    this.ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size * 0.6);
                    this.ctx.restore();
                    activeParticles++;
                }
            });

            if (activeParticles > 0) {
                this.animationId = requestAnimationFrame(() => this.animateConfetti());
            } else {
                this.cleanup();
            }
        }

        cleanup() {
            if (this.animationId) {
                cancelAnimationFrame(this.animationId);
                this.animationId = null;
            }
            if (this.canvas && this.canvas.parentNode) {
                this.canvas.parentNode.removeChild(this.canvas);
                this.canvas = null;
            }
            this.particles = [];
        }

        /**
         * Efecto shake para errores.
         */
        shake(element) {
            if (!element) return;
            element.classList.add('feedback-shake');
            setTimeout(() => element.classList.remove('feedback-shake'), 600);
        }

        /**
         * Efecto de éxito (pulso verde).
         */
        success(element) {
            if (!element) return;
            element.classList.add('feedback-success');
            setTimeout(() => element.classList.remove('feedback-success'), 600);
        }

        /**
         * Efecto de error (pulso rojo).
         */
        error(element) {
            if (!element) return;
            element.classList.add('feedback-error');
            this.shake(element);
            setTimeout(() => element.classList.remove('feedback-error'), 600);
        }
    }

    /**
     * Interactive Video Engine (S3)
     * Motor de video con checkpoints y overlays.
     */
    class InteractiveVideoEngine {
        constructor(container, data, feedbackEngine, emitXapi) {
            this.container = container;
            this.data = data;
            this.feedback = feedbackEngine;
            this.emitXapi = emitXapi;
            this.video = null;
            this.checkpoints = data.checkpoints || [];
            this.overlays = data.overlays || [];
            this.completedCheckpoints = new Set();
            this.activeOverlay = null;
            this.isPaused = false;
        }

        /**
         * Renderiza el player de video.
         */
        render() {
            const videoUrl = this.data.video_url || this.data.src || '';
            const poster = this.data.poster || '';

            return `
                <div class="interactive-video">
                    <div class="interactive-video__wrapper">
                        <video class="interactive-video__player" 
                               poster="${poster}"
                               playsinline>
                            <source src="${videoUrl}" type="video/mp4">
                            ${Drupal.t('Tu navegador no soporta video HTML5.')}
                        </video>
                        <div class="interactive-video__overlays"></div>
                        <div class="interactive-video__checkpoint-modal hidden">
                            <div class="checkpoint-modal__content">
                                <div class="checkpoint-modal__question"></div>
                                <div class="checkpoint-modal__options"></div>
                                <button class="checkpoint-modal__submit btn btn--primary hidden">
                                    ${Drupal.t('Confirmar')}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="interactive-video__controls">
                        <button class="video-control video-control--play" data-action="play">
                            <span class="icon-play">▶</span>
                            <span class="icon-pause hidden">❚❚</span>
                        </button>
                        <div class="video-progress">
                            <div class="video-progress__bar">
                                <div class="video-progress__fill"></div>
                                ${this.renderCheckpointMarkers()}
                            </div>
                            <span class="video-progress__time">0:00 / 0:00</span>
                        </div>
                        <button class="video-control video-control--fullscreen" data-action="fullscreen">
                            ⛶
                        </button>
                    </div>
                </div>
            `;
        }

        /**
         * Renderiza marcadores de checkpoints en la barra de progreso.
         */
        renderCheckpointMarkers() {
            const duration = this.data.duration || 100;
            return this.checkpoints.map(cp => {
                const position = (cp.time / duration) * 100;
                return `<div class="video-progress__checkpoint" 
                            style="left: ${position}%" 
                            data-checkpoint-id="${cp.id}"
                            title="${Drupal.t('Punto interactivo')}"></div>`;
            }).join('');
        }

        /**
         * Inicializa eventos del video.
         */
        init() {
            this.video = this.container.querySelector('.interactive-video__player');
            if (!this.video) return;

            this.video.addEventListener('timeupdate', () => this.onTimeUpdate());
            this.video.addEventListener('play', () => this.onPlay());
            this.video.addEventListener('pause', () => this.onPause());
            this.video.addEventListener('ended', () => this.onEnded());
            this.video.addEventListener('loadedmetadata', () => this.onLoadedMetadata());

            // Controles personalizados
            const playBtn = this.container.querySelector('[data-action="play"]');
            const progressBar = this.container.querySelector('.video-progress__bar');
            const fullscreenBtn = this.container.querySelector('[data-action="fullscreen"]');

            if (playBtn) {
                playBtn.addEventListener('click', () => this.togglePlay());
            }
            if (progressBar) {
                progressBar.addEventListener('click', (e) => this.seek(e));
            }
            if (fullscreenBtn) {
                fullscreenBtn.addEventListener('click', () => this.toggleFullscreen());
            }

            // Eventos del checkpoint modal
            const submitBtn = this.container.querySelector('.checkpoint-modal__submit');
            if (submitBtn) {
                submitBtn.addEventListener('click', () => this.submitCheckpointAnswer());
            }
        }

        onLoadedMetadata() {
            this.updateProgress();
        }

        togglePlay() {
            if (this.isPaused) return; // Bloqueado por checkpoint

            if (this.video.paused) {
                this.video.play();
            } else {
                this.video.pause();
            }
        }

        onPlay() {
            const playBtn = this.container.querySelector('[data-action="play"]');
            if (playBtn) {
                playBtn.querySelector('.icon-play').classList.add('hidden');
                playBtn.querySelector('.icon-pause').classList.remove('hidden');
            }
            this.emitXapi('played', { currentTime: this.video.currentTime });
        }

        onPause() {
            const playBtn = this.container.querySelector('[data-action="play"]');
            if (playBtn) {
                playBtn.querySelector('.icon-play').classList.remove('hidden');
                playBtn.querySelector('.icon-pause').classList.add('hidden');
            }
            if (!this.isPaused) {
                this.emitXapi('paused', { currentTime: this.video.currentTime });
            }
        }

        onEnded() {
            this.emitXapi('completed', { duration: this.video.duration });
        }

        onTimeUpdate() {
            this.updateProgress();
            this.checkCheckpoints();
            this.updateOverlays();
        }

        updateProgress() {
            const progress = this.video.duration ? (this.video.currentTime / this.video.duration) * 100 : 0;
            const fill = this.container.querySelector('.video-progress__fill');
            const timeLabel = this.container.querySelector('.video-progress__time');

            if (fill) fill.style.width = `${progress}%`;
            if (timeLabel) {
                timeLabel.textContent = `${this.formatTime(this.video.currentTime)} / ${this.formatTime(this.video.duration || 0)}`;
            }
        }

        formatTime(seconds) {
            const m = Math.floor(seconds / 60);
            const s = Math.floor(seconds % 60);
            return `${m}:${s.toString().padStart(2, '0')}`;
        }

        seek(e) {
            if (this.isPaused) return;

            const rect = e.currentTarget.getBoundingClientRect();
            const pos = (e.clientX - rect.left) / rect.width;
            this.video.currentTime = pos * this.video.duration;
            this.emitXapi('seeked', { currentTime: this.video.currentTime });
        }

        toggleFullscreen() {
            const wrapper = this.container.querySelector('.interactive-video__wrapper');
            if (document.fullscreenElement) {
                document.exitFullscreen();
            } else if (wrapper) {
                wrapper.requestFullscreen();
            }
        }

        /**
         * Verifica si hay checkpoints que activar.
         */
        checkCheckpoints() {
            const currentTime = this.video.currentTime;

            for (const cp of this.checkpoints) {
                if (this.completedCheckpoints.has(cp.id)) continue;

                // Activar checkpoint si estamos dentro de 0.5s del tiempo
                if (Math.abs(currentTime - cp.time) < 0.5) {
                    this.activateCheckpoint(cp);
                    break;
                }
            }
        }

        /**
         * Activa un checkpoint: pausa video y muestra interacción.
         */
        activateCheckpoint(checkpoint) {
            this.video.pause();
            this.isPaused = true;
            this.activeCheckpoint = checkpoint;

            const modal = this.container.querySelector('.interactive-video__checkpoint-modal');
            const questionEl = modal.querySelector('.checkpoint-modal__question');
            const optionsEl = modal.querySelector('.checkpoint-modal__options');
            const submitBtn = modal.querySelector('.checkpoint-modal__submit');

            // Renderizar pregunta
            const interaction = checkpoint.interaction;
            questionEl.innerHTML = `<h4>${interaction.question || ''}</h4>`;

            // Renderizar opciones según tipo
            if (interaction.type === 'multiple_choice') {
                optionsEl.innerHTML = (interaction.options || []).map((opt, i) => `
                    <label class="checkpoint-option">
                        <input type="radio" name="checkpoint-answer" value="${opt.id || i}">
                        <span>${opt.text || opt}</span>
                    </label>
                `).join('');
            } else if (interaction.type === 'true_false') {
                optionsEl.innerHTML = `
                    <label class="checkpoint-option">
                        <input type="radio" name="checkpoint-answer" value="true">
                        <span>${Drupal.t('Verdadero')}</span>
                    </label>
                    <label class="checkpoint-option">
                        <input type="radio" name="checkpoint-answer" value="false">
                        <span>${Drupal.t('Falso')}</span>
                    </label>
                `;
            }

            submitBtn.classList.remove('hidden');
            modal.classList.remove('hidden');

            this.emitXapi('checkpoint-started', { checkpointId: checkpoint.id });
        }

        /**
         * Procesa respuesta del checkpoint.
         */
        submitCheckpointAnswer() {
            const selected = this.container.querySelector('input[name="checkpoint-answer"]:checked');
            if (!selected) return;

            const answer = selected.value;
            const interaction = this.activeCheckpoint.interaction;
            let isCorrect = false;

            // Verificar respuesta
            if (interaction.type === 'multiple_choice') {
                const correctOption = (interaction.options || []).find(o => o.correct);
                isCorrect = correctOption && (correctOption.id == answer || interaction.options.indexOf(correctOption).toString() == answer);
            } else if (interaction.type === 'true_false') {
                isCorrect = interaction.correct_answer === answer;
            }

            const modal = this.container.querySelector('.interactive-video__checkpoint-modal');

            if (isCorrect || !this.activeCheckpoint.required) {
                // Éxito
                this.feedback.success(modal);
                this.completedCheckpoints.add(this.activeCheckpoint.id);

                // Marcar checkpoint como completado visualmente
                const marker = this.container.querySelector(`[data-checkpoint-id="${this.activeCheckpoint.id}"]`);
                if (marker) marker.classList.add('completed');

                setTimeout(() => {
                    modal.classList.add('hidden');
                    this.isPaused = false;
                    this.activeCheckpoint = null;
                    this.video.play();
                }, 800);

                this.emitXapi('checkpoint-completed', {
                    checkpointId: this.activeCheckpoint.id,
                    success: true
                });
            } else {
                // Error - debe reintentar
                this.feedback.error(modal);
                this.emitXapi('checkpoint-failed', { checkpointId: this.activeCheckpoint.id });
            }
        }

        /**
         * Gestiona overlays visibles según tiempo actual.
         */
        updateOverlays() {
            const currentTime = this.video.currentTime;
            const overlayContainer = this.container.querySelector('.interactive-video__overlays');
            if (!overlayContainer) return;

            overlayContainer.innerHTML = '';

            for (const overlay of this.overlays) {
                if (currentTime >= overlay.start && currentTime <= overlay.end) {
                    const pos = overlay.position || { x: 10, y: 10 };
                    overlayContainer.innerHTML += `
                        <div class="video-overlay" 
                             style="left: ${pos.x}%; top: ${pos.y}%;">
                            ${overlay.content || ''}
                        </div>
                    `;
                }
            }
        }
    }

    /**
     * Course Presentation Engine (S4)
     * Motor de slides interactivos con quizzes embebidos.
     */
    class CoursePresentationEngine {
        constructor(container, data, feedbackEngine, emitXapi) {
            this.container = container;
            this.data = data;
            this.feedback = feedbackEngine;
            this.emitXapi = emitXapi;
            this.slides = data.slides || [];
            this.currentSlide = 0;
            this.responses = {};
            this.settings = data.settings || {};
        }

        /**
         * Renderiza la estructura HTML de la presentación.
         */
        render() {
            const totalSlides = this.slides.length;
            return `
                <div class="course-presentation" data-total="${totalSlides}">
                    <div class="presentation-container">
                        <div class="slides-wrapper">
                            ${this.slides.map((slide, i) => this.renderSlide(slide, i)).join('')}
                        </div>
                    </div>
                    
                    <div class="presentation-controls">
                        <button class="presentation-btn presentation-btn--prev" disabled>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M15 18l-6-6 6-6"/>
                            </svg>
                            <span>${Drupal.t('Anterior')}</span>
                        </button>
                        
                        <div class="presentation-progress">
                            ${this.slides.map((_, i) => `
                                <button class="progress-dot ${i === 0 ? 'active' : ''}" data-slide="${i}"></button>
                            `).join('')}
                        </div>
                        
                        <button class="presentation-btn presentation-btn--next">
                            <span>${Drupal.t('Siguiente')}</span>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 18l6-6-6-6"/>
                            </svg>
                        </button>
                    </div>
                </div>
            `;
        }

        /**
         * Renderiza un slide individual según su tipo.
         */
        renderSlide(slide, index) {
            const isActive = index === 0 ? 'active' : '';
            const bgStyle = slide.background ? `background-image: url('${slide.background}')` : '';

            let content = '';
            switch (slide.type) {
                case 'quiz':
                    content = this.renderQuizSlide(slide);
                    break;
                case 'intro':
                case 'content':
                default:
                    content = this.renderContentSlide(slide);
            }

            return `
                <div class="slide ${isActive}" data-slide="${index}" data-type="${slide.type}" style="${bgStyle}">
                    <div class="slide-inner">
                        ${slide.title ? `<h2 class="slide-title">${slide.title}</h2>` : ''}
                        <div class="slide-content">
                            ${content}
                        </div>
                    </div>
                </div>
            `;
        }

        /**
         * Renderiza slide de contenido estándar.
         */
        renderContentSlide(slide) {
            if (slide.content) {
                return slide.content;
            }

            if (slide.elements) {
                return slide.elements.map(el => {
                    switch (el.type) {
                        case 'text':
                            return `<div class="slide-text">${el.content}</div>`;
                        case 'image':
                            return `<img class="slide-image" src="${el.src}" alt="${el.alt || ''}" />`;
                        case 'video':
                            return `<video class="slide-video" src="${el.src}" controls></video>`;
                        default:
                            return '';
                    }
                }).join('');
            }

            return '';
        }

        /**
         * Renderiza slide con quiz embebido.
         */
        renderQuizSlide(slide) {
            const question = slide.question;
            if (!question) return '';

            const slideId = slide.id || `slide-${this.slides.indexOf(slide)}`;

            return `
                <div class="slide-quiz" data-slide-id="${slideId}" data-required="${slide.required || false}">
                    <p class="quiz-question">${question.text}</p>
                    <div class="quiz-options">
                        ${question.options.map(opt => `
                            <label class="quiz-option">
                                <input type="radio" name="quiz-${slideId}" value="${opt.id}" data-correct="${opt.correct}">
                                <span class="quiz-option-text">${opt.text}</span>
                            </label>
                        `).join('')}
                    </div>
                    <div class="quiz-feedback"></div>
                </div>
            `;
        }

        /**
         * Inicializa eventos de la presentación.
         */
        init() {
            const wrapper = this.container.querySelector('.course-presentation');
            if (!wrapper) return;

            // Botones de navegación
            const prevBtn = wrapper.querySelector('.presentation-btn--prev');
            const nextBtn = wrapper.querySelector('.presentation-btn--next');

            prevBtn?.addEventListener('click', () => this.prev());
            nextBtn?.addEventListener('click', () => this.next());

            // Dots de progreso
            wrapper.querySelectorAll('.progress-dot').forEach(dot => {
                dot.addEventListener('click', () => {
                    const index = parseInt(dot.dataset.slide, 10);
                    this.goToSlide(index);
                });
            });

            // Navegación por teclado
            if (this.settings.keyboard_navigation !== false) {
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'ArrowRight') this.next();
                    if (e.key === 'ArrowLeft') this.prev();
                });
            }

            // Quiz responses
            wrapper.querySelectorAll('.slide-quiz input[type="radio"]').forEach(input => {
                input.addEventListener('change', (e) => this.onQuizAnswer(e));
            });

            this.emitXapi('initialized', { slides: this.slides.length });
        }

        /**
         * Navega al slide anterior.
         */
        prev() {
            if (this.currentSlide > 0) {
                this.goToSlide(this.currentSlide - 1);
            }
        }

        /**
         * Navega al siguiente slide.
         */
        next() {
            const currentSlideEl = this.container.querySelector(`.slide[data-slide="${this.currentSlide}"]`);
            const quiz = currentSlideEl?.querySelector('.slide-quiz[data-required="true"]');

            if (quiz) {
                const slideId = quiz.dataset.slideId;
                if (!this.responses[slideId]) {
                    this.feedback.shake(quiz);
                    return;
                }
            }

            if (this.currentSlide < this.slides.length - 1) {
                this.goToSlide(this.currentSlide + 1);
            } else {
                this.onComplete();
            }
        }

        /**
         * Navega a un slide específico.
         */
        goToSlide(index) {
            if (index < 0 || index >= this.slides.length) return;

            const wrapper = this.container.querySelector('.course-presentation');
            const slides = wrapper.querySelectorAll('.slide');
            const dots = wrapper.querySelectorAll('.progress-dot');

            slides.forEach((slide, i) => {
                slide.classList.toggle('active', i === index);
            });

            dots.forEach((dot, i) => {
                dot.classList.toggle('active', i === index);
                dot.classList.toggle('completed', i < index);
            });

            this.currentSlide = index;
            this.updateNavButtons();

            this.emitXapi('progressed', {
                slide: index + 1,
                total: this.slides.length,
                progress: Math.round(((index + 1) / this.slides.length) * 100)
            });
        }

        /**
         * Actualiza estado de botones de navegación.
         */
        updateNavButtons() {
            const wrapper = this.container.querySelector('.course-presentation');
            const prevBtn = wrapper.querySelector('.presentation-btn--prev');
            const nextBtn = wrapper.querySelector('.presentation-btn--next');

            prevBtn.disabled = this.currentSlide === 0;

            if (this.currentSlide === this.slides.length - 1) {
                nextBtn.innerHTML = `<span>${Drupal.t('Finalizar')}</span>`;
            }
        }

        /**
         * Procesa respuesta de quiz embebido.
         */
        onQuizAnswer(e) {
            const input = e.target;
            const quiz = input.closest('.slide-quiz');
            const slideId = quiz.dataset.slideId;
            const isCorrect = input.dataset.correct === 'true';

            this.responses[slideId] = {
                answer: input.value,
                correct: isCorrect
            };

            const feedbackEl = quiz.querySelector('.quiz-feedback');
            if (isCorrect) {
                feedbackEl.innerHTML = `<span class="feedback-correct">✓ ${Drupal.t('¡Correcto!')}</span>`;
                this.feedback.success(quiz);
            } else {
                feedbackEl.innerHTML = `<span class="feedback-incorrect">✗ ${Drupal.t('Incorrecto')}</span>`;
            }

            this.emitXapi('answered', { slideId, answer: input.value, correct: isCorrect });
        }

        /**
         * Maneja la finalización de la presentación.
         */
        onComplete() {
            const correctCount = Object.values(this.responses).filter(r => r.correct).length;
            const totalQuizzes = Object.keys(this.responses).length;

            this.feedback.confetti();

            this.emitXapi('completed', {
                slides: this.slides.length,
                quizzes: totalQuizzes,
                correctAnswers: correctCount
            });
        }
    }

    /**
     * Branching Scenario Engine (S4)
     * Motor de escenarios ramificados con árboles de decisión.
     */
    class BranchingScenarioEngine {
        constructor(container, data, feedbackEngine, emitXapi) {
            this.container = container;
            this.data = data;
            this.feedback = feedbackEngine;
            this.emitXapi = emitXapi;
            this.nodes = data.nodes || {};
            this.startNode = data.start_node || Object.keys(this.nodes)[0];
            this.currentNode = this.startNode;
            this.path = [];
            this.totalScore = 0;
            this.settings = data.settings || {};
        }

        /**
         * Renderiza la estructura HTML inicial.
         */
        render() {
            return `
                <div class="branching-scenario">
                    <div class="scenario-header">
                        <h3 class="scenario-title">${this.data.title || Drupal.t('Escenario Interactivo')}</h3>
                        ${this.settings.show_score ? `
                            <div class="scenario-score">
                                <span class="score-label">${Drupal.t('Puntuación')}:</span>
                                <span class="score-value">0</span>
                            </div>
                        ` : ''}
                    </div>
                    
                    <div class="scenario-content"></div>
                    
                    ${this.settings.show_path_taken ? `
                        <div class="scenario-path">
                            <span class="path-label">${Drupal.t('Tu camino')}:</span>
                            <div class="path-items"></div>
                        </div>
                    ` : ''}
                </div>
            `;
        }

        /**
         * Inicializa el escenario.
         */
        init() {
            this.goToNode(this.startNode);
            this.emitXapi('initialized', { startNode: this.startNode });
        }

        /**
         * Navega a un nodo específico.
         */
        goToNode(nodeId) {
            const node = this.nodes[nodeId];
            if (!node) {
                console.error(`Nodo no encontrado: ${nodeId}`);
                return;
            }

            this.currentNode = nodeId;
            const contentArea = this.container.querySelector('.scenario-content');

            if (node.type === 'ending') {
                contentArea.innerHTML = this.renderEndingNode(node);
                this.onComplete(node);
            } else {
                contentArea.innerHTML = this.renderScenarioNode(node, nodeId);
                this.bindChoiceEvents();
            }

            this.updatePathDisplay();
        }

        /**
         * Renderiza un nodo de escenario con opciones.
         */
        renderScenarioNode(node, nodeId) {
            return `
                <div class="scenario-node" data-node="${nodeId}">
                    ${node.image ? `
                        <div class="node-image">
                            <img src="${node.image}" alt="${node.title || ''}" />
                        </div>
                    ` : ''}
                    
                    <div class="node-body">
                        ${node.title ? `<h4 class="node-title">${node.title}</h4>` : ''}
                        <div class="node-content">${node.content || ''}</div>
                    </div>
                    
                    <div class="node-choices">
                        ${(node.choices || []).map(choice => `
                            <button class="choice-btn" data-choice="${choice.id}" data-next="${choice.next}" data-score="${choice.score || 0}">
                                ${choice.text}
                            </button>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        /**
         * Renderiza un nodo de final.
         */
        renderEndingNode(node) {
            return `
                <div class="scenario-ending">
                    <div class="ending-icon">
                        ${this.totalScore > 50 ? '🏆' : '📋'}
                    </div>
                    
                    <h3 class="ending-title">${node.title || Drupal.t('Fin del Escenario')}</h3>
                    
                    <div class="ending-content">${node.content || ''}</div>
                    
                    ${node.score_label ? `
                        <div class="ending-badge">${node.score_label}</div>
                    ` : ''}
                    
                    ${node.feedback ? `
                        <div class="ending-feedback">${node.feedback}</div>
                    ` : ''}
                    
                    <div class="ending-score">
                        <span class="score-final">${Drupal.t('Puntuación final')}: ${this.totalScore}</span>
                    </div>
                    
                    ${this.settings.allow_restart ? `
                        <button class="scenario-restart-btn">
                            ${Drupal.t('Intentar de nuevo')}
                        </button>
                    ` : ''}
                </div>
            `;
        }

        /**
         * Vincula eventos de las opciones.
         */
        bindChoiceEvents() {
            this.container.querySelectorAll('.choice-btn').forEach(btn => {
                btn.addEventListener('click', () => this.makeChoice(btn));
            });

            this.container.querySelector('.scenario-restart-btn')?.addEventListener('click', () => this.restart());
        }

        /**
         * Procesa una decisión del usuario.
         */
        makeChoice(btn) {
            const choiceId = btn.dataset.choice;
            const nextNode = btn.dataset.next;
            const score = parseInt(btn.dataset.score, 10) || 0;

            this.path.push({
                node: this.currentNode,
                choice: choiceId,
                score: score
            });

            this.totalScore += score;
            this.updateScoreDisplay();

            btn.classList.add('selected');

            this.emitXapi('chose', {
                node: this.currentNode,
                choice: choiceId,
                score: score,
                totalScore: this.totalScore
            });

            setTimeout(() => {
                this.goToNode(nextNode);
            }, 300);
        }

        /**
         * Actualiza visualización del score.
         */
        updateScoreDisplay() {
            const scoreEl = this.container.querySelector('.score-value');
            if (scoreEl) {
                scoreEl.textContent = this.totalScore;
            }
        }

        /**
         * Actualiza visualización del path recorrido.
         */
        updatePathDisplay() {
            const pathContainer = this.container.querySelector('.path-items');
            if (!pathContainer) return;

            pathContainer.innerHTML = this.path.map((step, i) => `
                <span class="path-step" data-step="${i + 1}">${i + 1}</span>
            `).join('<span class="path-arrow">→</span>');
        }

        /**
         * Maneja la finalización del escenario.
         */
        onComplete(endingNode) {
            if (this.totalScore > 70) {
                this.feedback.confetti();
            }

            this.emitXapi('completed', {
                ending: endingNode.title,
                totalScore: this.totalScore,
                stepsCount: this.path.length,
                path: this.path
            });

            setTimeout(() => {
                this.container.querySelector('.scenario-restart-btn')?.addEventListener('click', () => this.restart());
            }, 100);
        }

        /**
         * Reinicia el escenario desde el principio.
         */
        restart() {
            this.currentNode = this.startNode;
            this.path = [];
            this.totalScore = 0;

            this.updateScoreDisplay();
            this.goToNode(this.startNode);

            this.emitXapi('restarted', {});
        }
    }

    /**
     * Clase principal del Player Interactivo.
     */
    Drupal.jarabaInteractivePlayer = class JarabaInteractivePlayer {
        constructor(container, config) {
            this.container = container;
            this.config = config;
            this.state = {
                currentIndex: 0,
                responses: {},
                startTime: Date.now(),
                completed: false,
            };
            this.feedback = new FeedbackEngine(container);

            this.init();
        }

        /**
         * Inicializa el player.
         */
        init() {
            this.bindEvents();
            this.loadContent();
            this.emitXapi('initialized');
        }

        /**
         * Vincula eventos de UI.
         */
        bindEvents() {
            const btnPrevious = this.container.querySelector('#btn-previous');
            const btnNext = this.container.querySelector('#btn-next');
            const btnSubmit = this.container.querySelector('#btn-submit');
            const btnRetry = this.container.querySelector('#btn-retry');
            const btnReview = this.container.querySelector('#btn-review');

            if (btnPrevious) {
                btnPrevious.addEventListener('click', () => this.navigate(-1));
            }
            if (btnNext) {
                btnNext.addEventListener('click', () => this.navigate(1));
            }
            if (btnSubmit) {
                btnSubmit.addEventListener('click', () => this.submit());
            }
            if (btnRetry) {
                btnRetry.addEventListener('click', () => this.retry());
            }
            if (btnReview) {
                btnReview.addEventListener('click', () => this.showReview());
            }
        }

        /**
         * Carga el contenido en el container.
         */
        loadContent() {
            const contentContainer = this.container.querySelector('#interactive-content-container');
            if (!contentContainer) return;

            // Renderizar según el tipo de contenido.
            const renderer = this.getRenderer(this.config.contentType);
            if (renderer) {
                contentContainer.innerHTML = renderer(this.config.contentData);
                this.bindContentEvents();
            }

            // Ocultar loading.
            const loading = contentContainer.querySelector('.interactive-player__loading');
            if (loading) {
                loading.classList.add('hidden');
            }
        }

        /**
         * Obtiene el renderer para un tipo de contenido.
         */
        getRenderer(type) {
            const renderers = {
                question_set: (data) => this.renderQuestionSet(data),
                interactive_video: (data) => this.renderVideo(data),
                course_presentation: (data) => this.renderPresentation(data),
                branching_scenario: (data) => this.renderBranchingScenario(data),
            };
            return renderers[type] || null;
        }

        /**
         * Renderiza un Question Set.
         */
        renderQuestionSet(data) {
            const questions = data.questions || [];
            if (questions.length === 0) {
                return '<p>' + Drupal.t('No hay preguntas disponibles.') + '</p>';
            }

            let html = '<div class="question-set">';
            questions.forEach((q, index) => {
                const isActive = index === 0 ? 'active' : '';
                html += `<div class="question question--${q.type} ${isActive}" data-question-id="${q.id}" data-index="${index}">
          <div class="question__header">
            <span class="question__number">${index + 1}/${questions.length}</span>
            <h3 class="question__text">${q.text}</h3>
          </div>
          <div class="question__options">
            ${this.renderOptions(q)}
          </div>
          ${q.hint ? `<button type="button" class="btn btn--ghost btn--sm hint-btn">${Drupal.t('Ver pista')}</button>` : ''}
          <div class="question__feedback hidden"></div>
        </div>`;
            });
            html += '</div>';
            return html;
        }

        /**
         * Renderiza las opciones de una pregunta.
         */
        renderOptions(question) {
            if (question.type === 'true_false') {
                return `
          <label class="option-item">
            <input type="radio" name="q_${question.id}" value="true">
            <span>${Drupal.t('Verdadero')}</span>
          </label>
          <label class="option-item">
            <input type="radio" name="q_${question.id}" value="false">
            <span>${Drupal.t('Falso')}</span>
          </label>
        `;
            }

            if (question.options) {
                return question.options.map(opt => `
          <label class="option-item">
            <input type="radio" name="q_${question.id}" value="${opt.id}">
            <span>${opt.text}</span>
          </label>
        `).join('');
            }

            return `<input type="text" class="form-control" name="q_${question.id}" placeholder="${Drupal.t('Escribe tu respuesta...')}">`;
        }

        /**
         * Renderiza video interactivo con checkpoints y overlays.
         */
        renderVideo(data) {
            // Crear instancia del engine de video
            this.videoEngine = new InteractiveVideoEngine(
                this.container,
                data,
                this.feedback,
                (verb, extra) => this.emitXapi(verb, extra)
            );

            // Renderizar HTML del video
            const html = this.videoEngine.render();

            // Inicializar después de renderizar (async)
            setTimeout(() => {
                if (this.videoEngine) {
                    this.videoEngine.init();
                }
            }, 100);

            return html;
        }

        /**
         * Renderiza presentación interactiva con slides y quizzes embebidos.
         */
        renderPresentation(data) {
            // Crear instancia del engine de presentación
            this.presentationEngine = new CoursePresentationEngine(
                this.container,
                data,
                this.feedback,
                (verb, extra) => this.emitXapi(verb, extra)
            );

            // Renderizar HTML de la presentación
            const html = this.presentationEngine.render();

            // Inicializar después de renderizar
            setTimeout(() => {
                if (this.presentationEngine) {
                    this.presentationEngine.init();
                }
            }, 100);

            return html;
        }

        /**
         * Renderiza escenario ramificado con árboles de decisión.
         */
        renderBranchingScenario(data) {
            // Crear instancia del engine de branching
            this.branchingEngine = new BranchingScenarioEngine(
                this.container,
                data,
                this.feedback,
                (verb, extra) => this.emitXapi(verb, extra)
            );

            // Renderizar HTML del escenario
            const html = this.branchingEngine.render();

            // Inicializar después de renderizar
            setTimeout(() => {
                if (this.branchingEngine) {
                    this.branchingEngine.init();
                }
            }, 100);

            return html;
        }

        /**
         * Vincula eventos del contenido renderizado.
         */
        bindContentEvents() {
            // Capturar cambios en inputs.
            const inputs = this.container.querySelectorAll('input[type="radio"], input[type="text"], textarea');
            inputs.forEach(input => {
                input.addEventListener('change', (e) => this.onResponseChange(e));
            });

            // Botones de pista.
            const hintButtons = this.container.querySelectorAll('.hint-btn');
            hintButtons.forEach(btn => {
                btn.addEventListener('click', (e) => this.showHint(e));
            });
        }

        /**
         * Maneja cambios en respuestas.
         */
        onResponseChange(e) {
            const input = e.target;
            const question = input.closest('.question');
            if (question) {
                const questionId = question.dataset.questionId;
                this.state.responses[questionId] = input.value;
            }
            this.updateProgress();
        }

        /**
         * Navega entre preguntas.
         */
        navigate(direction) {
            const questions = this.container.querySelectorAll('.question');
            const totalQuestions = questions.length;

            if (totalQuestions === 0) return;

            // Ocultar pregunta actual.
            questions[this.state.currentIndex].classList.remove('active');

            // Calcular nuevo índice.
            this.state.currentIndex = Math.max(0, Math.min(totalQuestions - 1, this.state.currentIndex + direction));

            // Mostrar nueva pregunta.
            questions[this.state.currentIndex].classList.add('active');

            // Actualizar botones.
            this.updateNavigationButtons(totalQuestions);

            // Emitir xAPI.
            this.emitXapi('progressed');
        }

        /**
         * Actualiza estado de botones de navegación.
         */
        updateNavigationButtons(total) {
            const btnPrevious = this.container.querySelector('#btn-previous');
            const btnNext = this.container.querySelector('#btn-next');
            const btnSubmit = this.container.querySelector('#btn-submit');

            if (btnPrevious) {
                btnPrevious.disabled = this.state.currentIndex === 0;
            }
            if (btnNext) {
                btnNext.classList.toggle('hidden', this.state.currentIndex === total - 1);
            }
            if (btnSubmit) {
                btnSubmit.classList.toggle('hidden', this.state.currentIndex !== total - 1);
            }
        }

        /**
         * Actualiza barra de progreso.
         */
        updateProgress() {
            const progressFill = this.container.querySelector('.progress-bar__fill');
            const progressLabel = this.container.querySelector('.progress-bar__label');

            const total = Object.keys(this.config.contentData?.questions || {}).length || 1;
            const answered = Object.keys(this.state.responses).length;
            const percentage = Math.round((answered / total) * 100);

            if (progressFill) {
                progressFill.style.width = `${percentage}%`;
            }
            if (progressLabel) {
                progressLabel.textContent = `${percentage}%`;
            }
        }

        /**
         * Envía respuestas para evaluación.
         */
        submit() {
            this.emitXapi('attempted');

            // Calcular resultados.
            const results = this.calculateScore();
            this.state.completed = true;

            // Mostrar panel de resultados.
            this.showResults(results);

            // Emitir resultado final.
            this.emitXapi(results.passed ? 'passed' : 'failed', {
                score: {
                    scaled: results.score / 100,
                    raw: results.rawScore,
                    max: results.rawMax,
                },
                success: results.passed,
            });

            // Guardar resultado en servidor.
            this.saveResult(results);
        }

        /**
         * Calcula la puntuación.
         */
        calculateScore() {
            const questions = this.config.contentData?.questions || [];
            let correct = 0;
            let total = questions.length;

            questions.forEach(q => {
                const userAnswer = this.state.responses[q.id];
                if (this.isCorrect(q, userAnswer)) {
                    correct++;
                }
            });

            const score = total > 0 ? Math.round((correct / total) * 100) : 0;
            const passingScore = this.config.settings?.passing_score || 70;

            return {
                score: score,
                rawScore: correct,
                rawMax: total,
                passed: score >= passingScore,
                correct: correct,
                incorrect: total - correct,
                timeSpent: Math.round((Date.now() - this.state.startTime) / 1000),
            };
        }

        /**
         * Verifica si una respuesta es correcta.
         */
        isCorrect(question, userAnswer) {
            if (!userAnswer) return false;

            switch (question.type) {
                case 'multiple_choice':
                    const correctOption = (question.options || []).find(o => o.correct);
                    return correctOption && correctOption.id === userAnswer;
                case 'true_false':
                    return question.correct_answer === userAnswer;
                case 'short_answer':
                    return question.correct_answer?.toLowerCase().trim() === userAnswer.toLowerCase().trim();
                default:
                    return false;
            }
        }

        /**
         * Muestra panel de resultados.
         */
        showResults(results) {
            const resultsPanel = document.querySelector('#results-panel');
            const playerContent = this.container.querySelector('.interactive-player__content');

            if (resultsPanel && playerContent) {
                playerContent.classList.add('hidden');
                resultsPanel.classList.remove('hidden');

                // Actualizar valores.
                const percentage = resultsPanel.querySelector('.results-card__percentage');
                const statCorrect = resultsPanel.querySelector('#stat-correct');
                const statIncorrect = resultsPanel.querySelector('#stat-incorrect');
                const statTime = resultsPanel.querySelector('#stat-time');

                if (percentage) percentage.textContent = `${results.score}%`;
                if (statCorrect) statCorrect.textContent = results.correct;
                if (statIncorrect) statIncorrect.textContent = results.incorrect;
                if (statTime) statTime.textContent = this.formatTime(results.timeSpent);

                // Estilo según resultado.
                if (percentage) {
                    percentage.classList.toggle('results-card__percentage--passed', results.passed);
                    percentage.classList.toggle('results-card__percentage--failed', !results.passed);
                }

                // Feedback visual
                if (results.passed) {
                    this.feedback.confetti();
                    this.feedback.success(resultsPanel);
                } else {
                    this.feedback.shake(resultsPanel);
                }
            }
        }

        /**
         * Formatea tiempo en mm:ss.
         */
        formatTime(seconds) {
            const m = Math.floor(seconds / 60);
            const s = seconds % 60;
            return `${m}:${s.toString().padStart(2, '0')}`;
        }

        /**
         * Reintenta el cuestionario.
         */
        retry() {
            this.state.responses = {};
            this.state.currentIndex = 0;
            this.state.startTime = Date.now();
            this.state.completed = false;

            const resultsPanel = document.querySelector('#results-panel');
            const playerContent = this.container.querySelector('.interactive-player__content');

            if (resultsPanel) resultsPanel.classList.add('hidden');
            if (playerContent) playerContent.classList.remove('hidden');

            // Limpiar inputs.
            const inputs = this.container.querySelectorAll('input');
            inputs.forEach(input => {
                if (input.type === 'radio') input.checked = false;
                else input.value = '';
            });

            // Resetear navegación.
            const questions = this.container.querySelectorAll('.question');
            questions.forEach((q, i) => q.classList.toggle('active', i === 0));
            this.updateNavigationButtons(questions.length);
            this.updateProgress();

            this.emitXapi('initialized');
        }

        /**
         * Muestra revisión detallada de respuestas.
         *
         * Genera un overlay dentro del player con el listado completo
         * de preguntas, la respuesta del usuario (correcta/incorrecta),
         * la respuesta correcta y la puntuación global.
         * Incluye botón para reintentar el cuestionario.
         */
        showReview() {
            const questions = this.config.contentData?.questions || [];
            if (questions.length === 0) {
                return;
            }

            // Calcular resultados para el resumen.
            const results = this.calculateScore();

            // Construir HTML de la revisión.
            var reviewHtml = '<div class="jaraba-player-review">';

            // Cabecera con puntuación.
            reviewHtml += '<div class="jaraba-player-review__header">';
            reviewHtml += '<h3 class="jaraba-player-review__title">' + Drupal.t('Revisión de respuestas') + '</h3>';
            reviewHtml += '<div class="jaraba-player-review__score">';
            reviewHtml += '<span class="jaraba-player-review__score-value">' + results.correct + '/' + results.rawMax + '</span>';
            reviewHtml += '<span class="jaraba-player-review__score-label">' + Drupal.t('respuestas correctas') + '</span>';
            reviewHtml += '</div>';
            reviewHtml += '</div>';

            // Listado de preguntas.
            reviewHtml += '<div class="jaraba-player-review__items">';

            for (var i = 0; i < questions.length; i++) {
                var q = questions[i];
                var userAnswer = this.state.responses[q.id];
                var isCorrect = this.isCorrect(q, userAnswer);
                var statusClass = userAnswer ? (isCorrect ? 'jaraba-player-review__item--correct' : 'jaraba-player-review__item--incorrect') : 'jaraba-player-review__item--skipped';

                reviewHtml += '<div class="jaraba-player-review__item ' + statusClass + '">';

                // Número y estado.
                reviewHtml += '<div class="jaraba-player-review__item-header">';
                reviewHtml += '<span class="jaraba-player-review__item-number">' + (i + 1) + '</span>';
                if (userAnswer) {
                    if (isCorrect) {
                        reviewHtml += '<span class="jaraba-player-review__item-status jaraba-player-review__item-status--correct">' + Drupal.t('Correcta') + '</span>';
                    } else {
                        reviewHtml += '<span class="jaraba-player-review__item-status jaraba-player-review__item-status--incorrect">' + Drupal.t('Incorrecta') + '</span>';
                    }
                } else {
                    reviewHtml += '<span class="jaraba-player-review__item-status jaraba-player-review__item-status--skipped">' + Drupal.t('Sin responder') + '</span>';
                }
                reviewHtml += '</div>';

                // Texto de la pregunta.
                reviewHtml += '<p class="jaraba-player-review__item-question">' + (q.text || '') + '</p>';

                // Respuesta del usuario.
                if (userAnswer) {
                    var userAnswerText = this.getAnswerText(q, userAnswer);
                    reviewHtml += '<div class="jaraba-player-review__item-answer">';
                    reviewHtml += '<span class="jaraba-player-review__item-answer-label">' + Drupal.t('Tu respuesta') + ':</span> ';
                    reviewHtml += '<span>' + userAnswerText + '</span>';
                    reviewHtml += '</div>';
                }

                // Respuesta correcta (solo si la del usuario fue incorrecta).
                if (userAnswer && !isCorrect) {
                    var correctText = this.getCorrectAnswerText(q);
                    if (correctText) {
                        reviewHtml += '<div class="jaraba-player-review__item-correct">';
                        reviewHtml += '<span class="jaraba-player-review__item-answer-label">' + Drupal.t('Respuesta correcta') + ':</span> ';
                        reviewHtml += '<span>' + correctText + '</span>';
                        reviewHtml += '</div>';
                    }
                }

                reviewHtml += '</div>';
            }

            reviewHtml += '</div>';

            // Botón reintentar.
            reviewHtml += '<div class="jaraba-player-review__actions">';
            reviewHtml += '<button type="button" class="btn btn--primary jaraba-player-review__retry-btn">' + Drupal.t('Reintentar') + '</button>';
            reviewHtml += '</div>';

            reviewHtml += '</div>';

            // Insertar en el contenedor del player.
            var playerContent = this.container.querySelector('.interactive-player__content');
            var resultsPanel = document.querySelector('#results-panel');

            if (resultsPanel) {
                resultsPanel.classList.add('hidden');
            }
            if (playerContent) {
                playerContent.classList.remove('hidden');
                playerContent.innerHTML = reviewHtml;
            }

            // Vincular evento de reintentar.
            var retryBtn = this.container.querySelector('.jaraba-player-review__retry-btn');
            if (retryBtn) {
                retryBtn.addEventListener('click', () => this.retry());
            }

            this.emitXapi('reviewed');
        }

        /**
         * Obtiene el texto legible de la respuesta del usuario.
         *
         * @param {Object} question - Datos de la pregunta.
         * @param {string} answer - Valor de la respuesta del usuario.
         * @return {string} Texto de la respuesta.
         */
        getAnswerText(question, answer) {
            if (question.type === 'true_false') {
                return answer === 'true' ? Drupal.t('Verdadero') : Drupal.t('Falso');
            }
            if (question.type === 'multiple_choice' && question.options) {
                var option = question.options.find(function (o) { return o.id === answer; });
                return option ? option.text : answer;
            }
            return answer;
        }

        /**
         * Obtiene el texto de la respuesta correcta de una pregunta.
         *
         * @param {Object} question - Datos de la pregunta.
         * @return {string|null} Texto de la respuesta correcta.
         */
        getCorrectAnswerText(question) {
            if (question.type === 'true_false') {
                return question.correct_answer === 'true' ? Drupal.t('Verdadero') : Drupal.t('Falso');
            }
            if (question.type === 'multiple_choice' && question.options) {
                var correct = question.options.find(function (o) { return o.correct; });
                return correct ? correct.text : null;
            }
            if (question.type === 'short_answer') {
                return question.correct_answer || null;
            }
            return null;
        }

        /**
         * Muestra pista de pregunta.
         */
        showHint(e) {
            const question = e.target.closest('.question');
            const questionId = question?.dataset.questionId;
            const questionData = (this.config.contentData?.questions || []).find(q => q.id === questionId);

            if (questionData?.hint) {
                alert(questionData.hint);
            }
        }

        /**
         * Emite un statement xAPI.
         */
        emitXapi(verb, extension = {}) {
            const statement = {
                verb: verb,
                object: {
                    id: this.config.contentId,
                    type: this.config.contentType,
                },
                timestamp: new Date().toISOString(),
                ...extension,
            };

            // Enviar al endpoint si disponible.
            if (this.config.xapiEndpoint) {
                fetch(this.config.xapiEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': drupalSettings.csrfToken || '',
                    },
                    body: JSON.stringify(statement),
                }).catch(err => console.warn('xAPI emit failed:', err));
            }
        }

        /**
         * Guarda resultado en servidor.
         */
        saveResult(results) {
            const endpoint = `/api/v1/interactive/result`;

            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': drupalSettings.csrfToken || '',
                },
                body: JSON.stringify({
                    content_id: this.config.contentId,
                    responses: this.state.responses,
                    score: results.score,
                    max_score: 100,
                    passed: results.passed,
                    time_spent: results.timeSpent,
                }),
            }).catch(err => console.warn('Save result failed:', err));
        }
    };

    /**
     * Drupal behavior para inicializar el player.
     */
    Drupal.behaviors.jarabaInteractivePlayer = {
        attach: function (context) {
            once('jaraba-interactive-player', '.interactive-player', context).forEach(function (element) {
                const config = drupalSettings.jarabaInteractive || {};
                new Drupal.jarabaInteractivePlayer(element, config);
            });
        },
    };

})(Drupal, drupalSettings, once);
