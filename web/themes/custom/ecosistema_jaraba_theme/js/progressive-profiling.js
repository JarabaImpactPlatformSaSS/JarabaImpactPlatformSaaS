/**
 * @file
 * Progressive Profiling - Captura de perfil del usuario.
 *
 * Funcionalidades:
 * - Captura click en intention-cards
 * - Guarda perfil en localStorage
 * - Personaliza hero según perfil guardado
 * - Envía contexto al copiloto
 */

(function (Drupal) {
    'use strict';

    // Inyectar estilos CSS para el profile-badge
    const injectStyles = () => {
        if (document.getElementById('profile-badge-styles')) return;

        const style = document.createElement('style');
        style.id = 'profile-badge-styles';
        style.textContent = `
      .profile-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: rgba(255, 140, 66, 0.2);
        border: 1px solid var(--ej-color-primary, #FF8C42);
        border-radius: 50px;
        margin-bottom: 1rem;
        animation: fadeInUp 0.5s ease;
      }
      .profile-badge__icon {
        font-size: 1.25rem;
      }
      .profile-badge__text {
        color: #fff;
        font-weight: 500;
        font-size: 0.875rem;
      }
      .profile-badge__reset {
        background: rgba(255,255,255,0.2);
        border: none;
        color: #fff;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s;
        margin-left: 0.25rem;
      }
      .profile-badge__reset:hover {
        background: rgba(255,255,255,0.3);
      }
      .hero-landing__eyebrow.personalized,
      .hero-landing__title.personalized,
      .hero-landing__subtitle.personalized {
        animation: fadeInUp 0.6s ease;
      }
      .intention-card--selected {
        border-color: var(--ej-color-primary, #FF8C42) !important;
        background: rgba(255, 140, 66, 0.15) !important;
        box-shadow: 0 0 20px rgba(255, 140, 66, 0.3) !important;
      }
      @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
      }
    `;
        document.head.appendChild(style);
    };

    const STORAGE_KEY = 'jaraba_user_profile';

    // LOW-04: Profiles are configurable via drupalSettings, with hardcoded defaults as fallback.
    const DEFAULT_PROFILES = {
        empleo: {
            id: 'jobseeker',
            label: 'Candidato',
            vertical: 'empleabilidad',
            greeting: '¡Bienvenido! Veo que buscas empleo. ¿En qué sector te gustaría trabajar?',
            hero: {
                eyebrow: 'Tu próximo empleo te espera',
                title: 'Encuentra el trabajo perfecto para ti',
                subtitle: 'Ofertas personalizadas con IA, preparación de entrevistas y seguimiento profesional'
            }
        },
        talento: {
            id: 'recruiter',
            label: 'Reclutador',
            vertical: 'empleabilidad',
            greeting: '¡Hola! Veo que buscas talento. ¿Qué perfil necesitas para tu equipo?',
            hero: {
                eyebrow: 'El talento que necesitas',
                title: 'Conecta con candidatos cualificados',
                subtitle: 'Filtrado inteligente, matching por competencias y gestión de procesos simplificada'
            }
        },
        emprender: {
            id: 'entrepreneur',
            label: 'Emprendedor',
            vertical: 'emprendimiento',
            greeting: '¡Hola emprendedor! Cuéntame sobre tu idea de negocio.',
            hero: {
                eyebrow: 'Tu startup empieza aquí',
                title: 'Valida tu idea con metodología',
                subtitle: 'Lean Startup, Business Model Canvas y un copiloto IA que te guía paso a paso'
            }
        },
        comercio: {
            id: 'producer',
            label: 'Productor',
            vertical: 'comercio',
            greeting: '¡Hola! Veo que tienes un negocio. ¿Qué productos o servicios ofreces?',
            hero: {
                eyebrow: 'Tu negocio online',
                title: 'Vende en el Marketplace de impacto',
                subtitle: 'Visibilidad local, pagos seguros y conexión directa con compradores'
            }
        },
        b2g: {
            id: 'institution',
            label: 'Institución',
            vertical: 'institucional',
            greeting: '¡Bienvenido! ¿Qué programas de desarrollo territorial le interesan?',
            hero: {
                eyebrow: 'Programas de impacto',
                title: 'Impulsa tu territorio con tecnología',
                subtitle: 'Dashboards de impacto, gestión de subvenciones y medición ODS'
            }
        }
    };

    const PROFILES = (drupalSettings && drupalSettings.jarabaProfiles)
        ? { ...DEFAULT_PROFILES, ...drupalSettings.jarabaProfiles }
        : DEFAULT_PROFILES;

    /**
     * Guarda el perfil del usuario.
     */
    function saveProfile(profileKey) {
        const profile = PROFILES[profileKey];
        if (!profile) return;

        const data = {
            key: profileKey,
            ...profile,
            timestamp: Date.now(),
            visits: (getProfile()?.visits || 0) + 1
        };

        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(data));

            // Disparar evento custom
            window.dispatchEvent(new CustomEvent('jaraba:profile-captured', {
                detail: data
            }));

            return data;
        } catch (e) {
            return null;
        }
    }

    /**
     * Obtiene el perfil guardado.
     */
    function getProfile() {
        try {
            const stored = localStorage.getItem(STORAGE_KEY);
            return stored ? JSON.parse(stored) : null;
        } catch (e) {
            return null;
        }
    }

    /**
     * Personaliza el hero según el perfil.
     */
    function personalizeHero(profile) {
        if (!profile || !profile.hero) return;

        const eyebrow = document.querySelector('.hero-landing__eyebrow');
        const title = document.querySelector('.hero-landing__title');
        const subtitle = document.querySelector('.hero-landing__subtitle');

        if (eyebrow && profile.hero.eyebrow) {
            eyebrow.textContent = profile.hero.eyebrow;
            eyebrow.classList.add('personalized');
        }

        if (title && profile.hero.title) {
            title.textContent = profile.hero.title;
            title.classList.add('personalized');
        }

        if (subtitle && profile.hero.subtitle) {
            subtitle.textContent = profile.hero.subtitle;
            subtitle.classList.add('personalized');
        }

        // Añadir badge de perfil
        const heroContent = document.querySelector('.hero-landing__content');
        if (heroContent && !heroContent.querySelector('.profile-badge')) {
            const badge = document.createElement('div');
            badge.className = 'profile-badge';
            badge.innerHTML = `
        <span class="profile-badge__icon">👋</span>
        <span class="profile-badge__text">Hola, ${profile.label}</span>
        <button class="profile-badge__reset" title="Cambiar perfil" aria-label="Cambiar perfil">✕</button>
      `;
            heroContent.insertBefore(badge, heroContent.firstChild);

            // Reset handler
            badge.querySelector('.profile-badge__reset').addEventListener('click', (e) => {
                e.preventDefault();
                localStorage.removeItem(STORAGE_KEY);
                location.reload();
            });
        }

        // Destacar card del perfil
        const cards = document.querySelectorAll('.intention-card');
        cards.forEach(card => {
            const href = card.getAttribute('href');
            if (href && href.includes(profile.key)) {
                card.classList.add('intention-card--selected');
            }
        });

    }

    /**
     * Envía contexto al copiloto.
     */
    function sendToCopilot(profile) {
        // Exponer globalmente para el copiloto
        window.jarabaUserProfile = profile;

        // Si el copiloto ya existe, actualizar su contexto
        if (window.JarabaCopilot && typeof window.JarabaCopilot.setContext === 'function') {
            window.JarabaCopilot.setContext({
                userProfile: profile.key,
                vertical: profile.vertical,
                greeting: profile.greeting
            });
        }
    }

    /**
     * Inicializa el sistema de progressive profiling.
     */
    Drupal.behaviors.progressiveProfiling = {
        attach: function (context) {
            // Solo ejecutar una vez
            if (context !== document) return;

            // Inyectar estilos CSS
            injectStyles();

            // Verificar si hay perfil guardado
            const savedProfile = getProfile();
            if (savedProfile) {
                personalizeHero(savedProfile);
                sendToCopilot(savedProfile);
            }

            // Capturar clicks en intention cards
            const cards = document.querySelectorAll('.intention-card');
            cards.forEach(card => {
                card.addEventListener('click', (e) => {
                    const href = card.getAttribute('href');
                    if (!href) return;

                    // Extraer key del href (/empleo -> empleo)
                    const key = href.replace('/', '');
                    if (PROFILES[key]) {
                        saveProfile(key);
                    }
                });
            });

        }
    };

    // Exponer API global
    window.JarabaProfile = {
        get: getProfile,
        save: saveProfile,
        PROFILES: PROFILES
    };

})(Drupal);
