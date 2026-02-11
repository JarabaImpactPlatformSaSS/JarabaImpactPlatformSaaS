<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Controlador para landing pages de los verticales.
 *
 * Cada vertical tiene su propia landing page pública que:
 * - Presenta el valor del vertical
 * - Explica beneficios clave
 * - Muestra call-to-actions (registro/login)
 * - Usa iconos SVG via jaraba_icon() desde template Twig
 */
class VerticalLandingController extends ControllerBase
{

    /**
     * Landing page de Empleabilidad - Candidatos.
     *
     * Ruta: /empleo
     */
    public function empleo(): array
    {
        return $this->buildLanding([
            'key' => 'empleo',
            'title' => $this->t('Encuentra tu próximo empleo'),
            'subtitle' => $this->t('Ofertas personalizadas con IA, preparación de entrevistas y seguimiento profesional'),
            'icon_category' => 'verticals',
            'icon_name' => 'briefcase',
            'color' => 'innovation',
            'benefits' => [
                [
                    'icon_category' => 'business',
                    'icon_name' => 'target',
                    'title' => $this->t('Matching inteligente'),
                    'description' => $this->t('Ofertas que realmente encajan contigo basadas en tus competencias'),
                ],
                [
                    'icon_category' => 'business',
                    'icon_name' => 'cv-optimized',
                    'title' => $this->t('CV optimizado'),
                    'description' => $this->t('El copiloto te ayuda a mejorar tu CV para cada oferta'),
                ],
                [
                    'icon_category' => 'business',
                    'icon_name' => 'interview',
                    'title' => $this->t('Prepara entrevistas'),
                    'description' => $this->t('Simulaciones con IA para llegar seguro a tus entrevistas'),
                ],

                [
                    'icon_category' => 'business',
                    'icon_name' => 'tracking-board',
                    'title' => $this->t('Seguimiento de candidaturas'),
                    'description' => $this->t('Dashboard para ver el estado de todas tus aplicaciones'),
                ],
            ],
            'cta_primary' => [
                'text' => $this->t('Crear perfil gratis'),
                'url' => Url::fromRoute('user.register')->toString(),
            ],
            'cta_secondary' => [
                'text' => $this->t('Ya tengo cuenta'),
                'url' => Url::fromRoute('user.login')->toString(),
            ],
        ]);
    }

    /**
     * Landing page de Empleabilidad - Empresas/Reclutadores.
     *
     * Ruta: /talento
     */
    public function talento(): array
    {
        return $this->buildLanding([
            'key' => 'talento',
            'title' => $this->t('Encuentra el talento que necesitas'),
            'subtitle' => $this->t('Filtrado inteligente, matching por competencias y gestión simplificada'),
            'icon_category' => 'business',
            'icon_name' => 'talent-search',
            'color' => 'innovation',
            'benefits' => [
                [
                    'icon_category' => 'ui',
                    'icon_name' => 'search',
                    'title' => $this->t('Búsqueda avanzada'),
                    'description' => $this->t('Filtra por skills, experiencia, disponibilidad y ubicación'),
                ],
                [
                    'icon_category' => 'ai',
                    'icon_name' => 'screening',
                    'title' => $this->t('Preselección con IA'),
                    'description' => $this->t('El copiloto analiza CVs y sugiere los mejores candidatos'),
                ],
                [
                    'icon_category' => 'analytics',
                    'icon_name' => 'dashboard',
                    'title' => $this->t('Analytics de contratación'),
                    'description' => $this->t('Métricas de tiempo, conversión y calidad de contrataciones'),
                ],
                [
                    'icon_category' => 'ui',
                    'icon_name' => 'users',
                    'title' => $this->t('Gestión colaborativa'),
                    'description' => $this->t('Tu equipo puede evaluar y comentar candidatos en tiempo real'),
                ],
            ],
            'cta_primary' => [
                'text' => $this->t('Publicar oferta'),
                'url' => Url::fromRoute('user.register')->toString(),
            ],
            'cta_secondary' => [
                'text' => $this->t('Ver planes'),
                'url' => '/planes',
            ],
        ]);
    }

    /**
     * Landing page de Emprendimiento.
     *
     * Ruta: /emprender
     */
    public function emprender(): array
    {
        return $this->buildLanding([
            'key' => 'emprender',
            'title' => $this->t('Valida tu idea con metodología'),
            'subtitle' => $this->t('Lean Startup, Business Model Canvas y un copiloto IA que te guía paso a paso'),
            'icon_category' => 'verticals',
            'icon_name' => 'rocket',
            'color' => 'impulse',
            'benefits' => [
                [
                    'icon_category' => 'ai',
                    'icon_name' => 'lightbulb',
                    'title' => $this->t('Valida tu idea'),
                    'description' => $this->t('Metodología Lean para validar hipótesis antes de invertir'),
                ],
                [
                    'icon_category' => 'business',
                    'icon_name' => 'canvas',
                    'title' => $this->t('Business Model Canvas'),
                    'description' => $this->t('Diseña tu modelo de negocio con asistencia del copiloto'),
                ],
                [
                    'icon_category' => 'business',
                    'icon_name' => 'target',
                    'title' => $this->t('Tareas guiadas'),
                    'description' => $this->t('El copiloto te asigna tareas y desbloquea etapas progresivas'),
                ],
                [
                    'icon_category' => 'business',
                    'icon_name' => 'achievement',
                    'title' => $this->t('Acceso a mentores'),
                    'description' => $this->t('Conecta con expertos que te ayudan a crecer'),
                ],
            ],
            'cta_primary' => [
                'text' => $this->t('Empezar ahora'),
                'url' => Url::fromRoute('user.register')->toString(),
            ],
            'cta_secondary' => [
                'text' => $this->t('Ver metodología'),
                'url' => '/metodologia',
            ],
        ]);
    }

    /**
     * Landing page de Comercio/Marketplace.
     *
     * Ruta: /comercio
     */
    public function comercio(): array
    {
        return $this->buildLanding([
            'key' => 'comercio',
            'title' => $this->t('Vende tus productos y servicios'),
            'subtitle' => $this->t('Marketplace de impacto con visibilidad local, pagos seguros y gestión simplificada'),
            'icon_category' => 'business',
            'icon_name' => 'cart',
            'color' => 'success',
            'benefits' => [
                [
                    'icon_category' => 'ui',
                    'icon_name' => 'storefront',
                    'title' => $this->t('Tu tienda online'),
                    'description' => $this->t('Perfil profesional con catálogo de productos y servicios'),
                ],
                [
                    'icon_category' => 'business',
                    'icon_name' => 'money',
                    'title' => $this->t('Pagos seguros'),
                    'description' => $this->t('Stripe Connect para cobros directos en tu cuenta'),
                ],
                [
                    'icon_category' => 'verticals',
                    'icon_name' => 'leaf',
                    'title' => $this->t('Producción local'),
                    'description' => $this->t('Destaca tu origen local y prácticas sostenibles'),
                ],
                [
                    'icon_category' => 'ui',
                    'icon_name' => 'package',
                    'title' => $this->t('Gestión de pedidos'),
                    'description' => $this->t('Dashboard para gestionar pedidos y envíos'),
                ],
            ],
            'cta_primary' => [
                'text' => $this->t('Crear mi tienda'),
                'url' => Url::fromRoute('user.register')->toString(),
            ],
            'cta_secondary' => [
                'text' => $this->t('Ver marketplace'),
                'url' => '/marketplace',
            ],
        ]);
    }

    /**
     * Landing page de Instituciones B2G.
     *
     * Ruta: /instituciones
     */
    public function instituciones(): array
    {
        return $this->buildLanding([
            'key' => 'instituciones',
            'title' => $this->t('Tu plataforma de desarrollo local'),
            'subtitle' => $this->t('Formación, empleo y emprendimiento con tu marca. Impulsado por IA.'),
            'icon_category' => 'business',
            'icon_name' => 'institution',
            'color' => 'corporate',
            'benefits' => [
                [
                    'icon_category' => 'ui',
                    'icon_name' => 'building',
                    'title' => $this->t('Tu marca, tu plataforma'),
                    'description' => $this->t('Identidad corporativa propia: logo, colores y dominio personalizado'),
                ],
                [
                    'icon_category' => 'business',
                    'icon_name' => 'ecosystem',
                    'title' => $this->t('Formación y empleo'),
                    'description' => $this->t('Conecta talento local con empresas de tu territorio'),
                ],
                [
                    'icon_category' => 'ai',
                    'icon_name' => 'screening',
                    'title' => $this->t('Copiloto IA incluido'),
                    'description' => $this->t('Asistencia inteligente para candidatos y emprendedores'),
                ],
                [
                    'icon_category' => 'analytics',
                    'icon_name' => 'dashboard',
                    'title' => $this->t('Métricas de impacto'),
                    'description' => $this->t('Dashboards ODS y reportes para justificar subvenciones'),
                ],
            ],
            'cta_primary' => [
                'text' => $this->t('Solicitar demo'),
                'url' => '/demo',
            ],
            'cta_secondary' => [
                'text' => $this->t('Ver casos de éxito'),
                'url' => '/casos',
            ],
        ]);
    }

    /**
     * Construye la estructura de render usando template Twig.
     *
     * @param array $data
     *   Datos de la landing (title, subtitle, benefits, ctas, etc.)
     *
     * @return array
     *   Render array con template theme.
     */
    protected function buildLanding(array $data): array
    {
        return [
            '#theme' => 'vertical_landing_content',
            '#vertical_data' => $data,
            '#attached' => [
                'library' => [
                    'ecosistema_jaraba_core/global',
                    'ecosistema_jaraba_theme/progressive-profiling',
                ],
            ],
        ];
    }
}
