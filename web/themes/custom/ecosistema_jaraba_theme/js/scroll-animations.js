/**
 * @file
 * Scroll Animations - Intersection Observer for reveal effects.
 *
 * Compatible with Drupal behaviors.
 * Uses Web Animations API and Intersection Observer.
 */

(function (Drupal) {
  'use strict';

  /**
   * Scroll-triggered animations behavior.
   */
  Drupal.behaviors.scrollAnimations = {
    attach: function (context, settings) {

      // Only run once per element
      const animatedElements = context.querySelectorAll('.animate-on-scroll:not(.is-observed)');

      if (animatedElements.length === 0) {
        return;
      }

      // Mark as observed
      animatedElements.forEach(el => el.classList.add('is-observed'));

      // Observer config
      const observerOptions = {
        threshold: 0.2,
        rootMargin: '0px 0px -50px 0px'
      };

      // Create observer
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            // Add visible class with slight delay for stagger effect
            const delay = entry.target.dataset.delay || 0;

            setTimeout(() => {
              entry.target.classList.add('is-visible');
            }, delay);

            // Stop observing once visible
            observer.unobserve(entry.target);
          }
        });
      }, observerOptions);

      // Observe all elements
      animatedElements.forEach(el => {
        observer.observe(el);
      });
    }
  };

  /**
   * Smooth scroll for anchor links.
   */
  Drupal.behaviors.smoothScroll = {
    attach: function (context) {
      const anchors = context.querySelectorAll('a[href^="#"]:not(.smooth-scroll-attached)');

      anchors.forEach(anchor => {
        anchor.classList.add('smooth-scroll-attached');

        anchor.addEventListener('click', function (e) {
          const targetId = this.getAttribute('href');

          if (targetId === '#') {
            return;
          }

          const target = document.querySelector(targetId);

          if (target) {
            e.preventDefault();
            target.scrollIntoView({
              behavior: 'smooth',
              block: 'start'
            });
          }
        });
      });
    }
  };

  /**
   * Card tilt effect (3D hover).
   */
  Drupal.behaviors.cardTilt = {
    attach: function (context) {
      const cards = context.querySelectorAll('.intention-card:not(.tilt-attached)');

      cards.forEach(card => {
        card.classList.add('tilt-attached');

        card.addEventListener('mousemove', function (e) {
          const rect = this.getBoundingClientRect();
          const x = e.clientX - rect.left;
          const y = e.clientY - rect.top;

          const centerX = rect.width / 2;
          const centerY = rect.height / 2;

          const rotateX = (y - centerY) / 20;
          const rotateY = (centerX - x) / 20;

          this.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-8px) scale(1.02)`;
        });

        card.addEventListener('mouseleave', function () {
          this.style.transform = '';
        });
      });
    }
  };

  /**
   * Typed effect for hero title (optional progressive reveal).
   */
  Drupal.behaviors.heroTyped = {
    attach: function (context) {
      const typedElements = context.querySelectorAll('[data-typed]:not(.typed-attached)');

      typedElements.forEach(el => {
        el.classList.add('typed-attached');

        const text = el.textContent;
        const speed = parseInt(el.dataset.typedSpeed, 10) || 50;

        el.textContent = '';
        el.style.visibility = 'visible';

        let i = 0;
        const typeWriter = () => {
          if (i < text.length) {
            el.textContent += text.charAt(i);
            i++;
            setTimeout(typeWriter, speed);
          }
        };

        // Start typing when visible
        const observer = new IntersectionObserver((entries) => {
          if (entries[0].isIntersecting) {
            setTimeout(typeWriter, 500);
            observer.disconnect();
          }
        }, { threshold: 0.5 });

        observer.observe(el);
      });
    }
  };

  /**
   * Animated counter for stats.
   * Animates numbers from 0 to target value when visible.
   */
  Drupal.behaviors.animatedCounter = {
    attach: function (context) {
      const counters = context.querySelectorAll('.stat-item__number[data-count]:not(.counter-attached)');

      if (counters.length === 0) {
        return;
      }

      counters.forEach(counter => {
        counter.classList.add('counter-attached');
      });

      const observerOptions = {
        threshold: 0.5,
        rootMargin: '0px'
      };

      const animateCounter = (el) => {
        const target = parseInt(el.dataset.count, 10);
        const duration = 2000; // 2 seconds
        const startTime = performance.now();

        const updateCounter = (currentTime) => {
          const elapsed = currentTime - startTime;
          const progress = Math.min(elapsed / duration, 1);

          // Easing function (ease-out-cubic)
          const easeOut = 1 - Math.pow(1 - progress, 3);
          const current = Math.floor(easeOut * target);

          el.textContent = current.toLocaleString('es-ES');

          if (progress < 1) {
            requestAnimationFrame(updateCounter);
          } else {
            el.textContent = target.toLocaleString('es-ES');
          }
        };

        requestAnimationFrame(updateCounter);
      };

      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            animateCounter(entry.target);
            observer.unobserve(entry.target);
          }
        });
      }, observerOptions);

      counters.forEach(counter => {
        observer.observe(counter);
      });
    }
  };

  /**
   * Mobile menu toggle.
   */
  Drupal.behaviors.mobileMenu = {
    attach: function (context) {
      const toggle = context.querySelector('.landing-header__toggle:not(.menu-attached)');

      if (!toggle) {
        return;
      }

      toggle.classList.add('menu-attached');

      const nav = document.querySelector('.landing-header__nav');
      const actions = document.querySelector('.landing-header__actions');
      const body = document.body;

      // Create overlay
      let overlay = document.querySelector('.mobile-menu-overlay');
      if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'mobile-menu-overlay';
        document.body.appendChild(overlay);
      }

      const closeMenu = () => {
        toggle.classList.remove('is-active');
        toggle.setAttribute('aria-expanded', 'false');
        if (nav) nav.classList.remove('is-open');
        if (actions) actions.classList.remove('is-open');
        overlay.classList.remove('is-visible');
        body.classList.remove('menu-open');
      };

      const openMenu = () => {
        toggle.classList.add('is-active');
        toggle.setAttribute('aria-expanded', 'true');
        if (nav) nav.classList.add('is-open');
        if (actions) actions.classList.add('is-open');
        overlay.classList.add('is-visible');
        body.classList.add('menu-open');
      };

      toggle.addEventListener('click', function () {
        if (this.classList.contains('is-active')) {
          closeMenu();
        } else {
          openMenu();
        }
      });

      overlay.addEventListener('click', closeMenu);

      // FE-01: Close on escape key - store handler for cleanup.
      const escapeHandler = (e) => {
        if (e.key === 'Escape' && toggle.classList.contains('is-active')) {
          closeMenu();
        }
      };
      document.addEventListener('keydown', escapeHandler);
      toggle._escapeHandler = escapeHandler;

      // Close on link click
      if (nav) {
        nav.querySelectorAll('a').forEach(link => {
          link.addEventListener('click', closeMenu);
        });
      }
    }
  };

  /**
   * Header shrink on scroll.
   */
  Drupal.behaviors.headerShrink = {
    attach: function (context) {
      const header = context.querySelector('.landing-header:not(.shrink-attached)');

      if (!header) {
        return;
      }

      header.classList.add('shrink-attached');

      let lastScroll = 0;
      const shrinkThreshold = 100;

      const handleScroll = () => {
        const currentScroll = window.scrollY;

        if (currentScroll > shrinkThreshold) {
          header.classList.add('scrolled');
        } else {
          header.classList.remove('scrolled');
        }

        lastScroll = currentScroll;
      };

      // FE-01: Throttle scroll events - store handler reference for cleanup.
      let ticking = false;
      const scrollHandler = () => {
        if (!ticking) {
          requestAnimationFrame(() => {
            handleScroll();
            ticking = false;
          });
          ticking = true;
        }
      };
      window.addEventListener('scroll', scrollHandler, { passive: true });
      header._scrollHandler = scrollHandler;

      // Initial check
      handleScroll();
    },
    detach: function (context, settings, trigger) {
      if (trigger !== 'unload') return;
      const header = context.querySelector('.landing-header.shrink-attached');
      if (header && header._scrollHandler) {
        window.removeEventListener('scroll', header._scrollHandler);
      }
    }
  };

  /**
   * Scroll reveal for sections.
   */
  Drupal.behaviors.scrollReveal = {
    attach: function (context) {
      const sections = context.querySelectorAll('.features-section, .stats-section:not(.reveal-attached)');

      sections.forEach(section => {
        section.classList.add('reveal-attached', 'reveal-hidden');
      });

      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('reveal-visible');
            entry.target.classList.remove('reveal-hidden');
            observer.unobserve(entry.target);
          }
        });
      }, {
        threshold: 0.15,
        rootMargin: '0px 0px -50px 0px'
      });

      sections.forEach(section => {
        observer.observe(section);
      });
    }
  };

  /**
   * Landing Page Copilot - FAB and chat panel.
   * Follows the agent-fab pattern from jaraba_job_board.
   */
  Drupal.behaviors.landingCopilot = {
    attach: function (context) {
      const container = context.querySelector('.agent-fab-container.landing-copilot');

      if (!container || container.dataset.initialized) {
        return;
      }
      container.dataset.initialized = 'true';

      const trigger = container.querySelector('.agent-fab-trigger');
      const panel = container.querySelector('.agent-panel');
      const closeBtn = container.querySelector('.agent-close');
      const actionButtons = container.querySelectorAll('.action-button');
      const input = container.querySelector('.agent-input');
      const sendBtn = container.querySelector('.agent-send');
      const chatMessages = container.querySelector('.chat-messages');
      const agentChat = container.querySelector('.agent-chat');

      // Toggle panel
      trigger.addEventListener('click', () => {
        const isOpen = panel.classList.contains('is-open');
        panel.classList.toggle('is-open');
        trigger.setAttribute('aria-expanded', !isOpen);
        panel.setAttribute('aria-hidden', isOpen);

        if (!isOpen) {
          setTimeout(() => input?.focus(), 300);
        }
      });

      // Close panel
      closeBtn?.addEventListener('click', () => {
        panel.classList.remove('is-open');
        trigger.setAttribute('aria-expanded', 'false');
        panel.setAttribute('aria-hidden', 'true');
      });

      // FE-01: Close on outside click - store handler for potential cleanup.
      const outsideClickHandler = (e) => {
        if (!container.contains(e.target) && panel.classList.contains('is-open')) {
          panel.classList.remove('is-open');
          trigger.setAttribute('aria-expanded', 'false');
          panel.setAttribute('aria-hidden', 'true');
        }
      };
      document.addEventListener('click', outsideClickHandler);
      container._outsideClickHandler = outsideClickHandler;

      // Action buttons
      actionButtons.forEach(btn => {
        btn.addEventListener('click', () => {
          const actionId = btn.dataset.action;
          const vertical = btn.dataset.vertical;
          const actionLabel = btn.querySelector('.action-label').textContent;

          addMessage(chatMessages, agentChat, actionLabel, 'user');
          executeAction(actionId, vertical, chatMessages, agentChat);
        });
      });

      // Send message
      const sendMessage = () => {
        const message = input?.value.trim();
        if (!message) return;

        addMessage(chatMessages, agentChat, message, 'user');
        input.value = '';

        // Show typing indicator
        showTyping(chatMessages, agentChat);

        setTimeout(() => {
          removeTyping(chatMessages);
          addAgentResponse(chatMessages, agentChat, {
            message: Drupal.t('Entendido. Estoy analizando tu consulta: "@query"', { '@query': message }),
            followUp: Drupal.t('¿Te gustaría más detalles sobre alguna vertical?')
          });
        }, 1000);
      };

      sendBtn?.addEventListener('click', sendMessage);
      input?.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          sendMessage();
        }
      });

      /**
       * Adds a message to chat with auto-scroll.
       */
      function addMessage(container, scrollContainer, text, sender) {
        const msg = document.createElement('div');
        msg.className = `chat-message from-${sender}`;
        msg.textContent = text;
        container.appendChild(msg);

        // Auto-scroll
        setTimeout(() => {
          msg.scrollIntoView({ behavior: 'smooth', block: 'end' });
        }, 50);
      }

      /**
       * Shows typing indicator.
       */
      function showTyping(container, scrollContainer) {
        const el = document.createElement('div');
        el.className = 'chat-message from-agent loading-message';
        el.id = 'typing-indicator';
        el.innerHTML = '<span class="typing-dots"><span>.</span><span>.</span><span>.</span></span>';
        container.appendChild(el);
        el.scrollIntoView({ behavior: 'smooth', block: 'end' });
      }

      /**
       * Removes typing indicator.
       */
      function removeTyping(container) {
        const el = document.getElementById('typing-indicator');
        if (el) el.remove();
      }

      /**
       * Adds agent response with CTAs and rating.
       */
      function addAgentResponse(container, scrollContainer, response) {
        const wrapper = document.createElement('div');
        wrapper.className = 'agent-response-wrapper';

        // Main message
        const msg = document.createElement('div');
        msg.className = 'chat-message from-agent';
        msg.innerHTML = response.message;
        wrapper.appendChild(msg);

        // Tips
        if (response.tips && response.tips.length) {
          response.tips.forEach(tip => {
            const tipEl = document.createElement('div');
            tipEl.className = 'chat-message from-agent tip-message';
            tipEl.textContent = tip;
            wrapper.appendChild(tipEl);
          });
        }

        // CTA buttons
        if (response.actions && response.actions.length) {
          const actionsContainer = document.createElement('div');
          actionsContainer.className = 'response-actions';

          response.actions.forEach(action => {
            const btn = document.createElement('a');
            btn.href = action.url;
            btn.className = 'response-cta';
            btn.innerHTML = `<span class="cta-icon">${action.icon || '→'}</span> ${action.label}`;
            actionsContainer.appendChild(btn);
          });
          wrapper.appendChild(actionsContainer);
        }

        // Follow-up
        if (response.followUp) {
          const followUp = document.createElement('div');
          followUp.className = 'chat-message from-agent follow-up';
          followUp.textContent = response.followUp;
          wrapper.appendChild(followUp);
        }

        // Rating buttons
        const rating = document.createElement('div');
        rating.className = 'response-rating';
        rating.innerHTML = `
          <span class="rating-label">${Drupal.t('¿Te fue útil?')}</span>
          <button class="rating-btn rating-up" data-rating="up" title="${Drupal.t('Sí, útil')}">👍</button>
          <button class="rating-btn rating-down" data-rating="down" title="${Drupal.t('No, mejorar')}">👎</button>
        `;

        rating.querySelectorAll('.rating-btn').forEach(btn => {
          btn.addEventListener('click', function () {
            const val = this.dataset.rating;
            this.parentElement.innerHTML = val === 'up'
              ? `<span class="rating-thanks">✅ ${Drupal.t('¡Gracias!')}</span>`
              : `<span class="rating-thanks">📝 ${Drupal.t('Anotado para mejorar')}</span>`;
          });
        });
        wrapper.appendChild(rating);

        container.appendChild(wrapper);

        // Auto-scroll
        setTimeout(() => {
          wrapper.scrollIntoView({ behavior: 'smooth', block: 'end' });
        }, 50);
      }

      /**
       * Execute action with contextual response.
       */
      function executeAction(actionId, vertical, chatContainer, scrollContainer) {
        showTyping(chatContainer, scrollContainer);

        setTimeout(() => {
          removeTyping(chatContainer);

          const responses = {
            find_job: {
              message: Drupal.t('¡Perfecto! 🎯 Te ayudo a encontrar empleo en nuestro ecosistema.'),
              tips: [
                Drupal.t('📝 Crea tu perfil profesional'),
                Drupal.t('💼 Explora ofertas activas'),
                Drupal.t('🤖 Recibe recomendaciones personalizadas')
              ],
              actions: [
                { label: Drupal.t('Ver ofertas de empleo'), url: '/empleabilidad', icon: '💼' },
                { label: Drupal.t('Registrarme'), url: '/user/register', icon: '✨' }
              ],
              followUp: Drupal.t('¿Tienes algún sector o puesto en mente?')
            },
            find_talent: {
              message: Drupal.t('¡Excelente! 👥 Te ayudo a reclutar talento cualificado.'),
              tips: [
                Drupal.t('📋 Publica ofertas de trabajo'),
                Drupal.t('🔍 Filtra candidatos por habilidades'),
                Drupal.t('📊 Ranking IA de compatibilidad')
              ],
              actions: [
                { label: Drupal.t('Acceso Empresas'), url: '/talento', icon: '🏢' },
                { label: Drupal.t('Publicar oferta'), url: '/user/register', icon: '📝' }
              ],
              followUp: Drupal.t('¿Qué tipo de talento buscas?')
            },
            start_business: {
              message: Drupal.t('¡Fantástico! 🚀 Te acompaño en tu viaje emprendedor.'),
              tips: [
                Drupal.t('💡 Valida tu idea de negocio'),
                Drupal.t('📈 Planifica con IA'),
                Drupal.t('🎓 Accede a formación práctica')
              ],
              actions: [
                { label: Drupal.t('Empezar a emprender'), url: '/emprendimiento', icon: '🚀' },
                { label: Drupal.t('Ver cursos'), url: '/cursos', icon: '📚' }
              ],
              followUp: Drupal.t('¿En qué fase está tu proyecto?')
            },
            sell_online: {
              message: Drupal.t('¡Genial! 🛒 Te ayudo a vender tus productos online.'),
              tips: [
                Drupal.t('🏪 Crea tu tienda digital'),
                Drupal.t('📦 Gestiona inventario'),
                Drupal.t('💳 Acepta pagos seguros')
              ],
              actions: [
                { label: Drupal.t('Ir a Comercio'), url: '/comercio', icon: '🛍️' },
                { label: Drupal.t('Crear mi tienda'), url: '/user/register', icon: '✨' }
              ],
              followUp: Drupal.t('¿Qué tipo de productos vendes?')
            }
          };

          addAgentResponse(chatContainer, scrollContainer, responses[actionId] || {
            message: Drupal.t('¿En qué más puedo ayudarte?')
          });
        }, 1200);
      }
    }
  };

})(Drupal);
