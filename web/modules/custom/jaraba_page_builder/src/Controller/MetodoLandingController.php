<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Service\MegaMenuBridgeService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Landing page del Método Jaraba™ v2 para plataformadeecosistemas.com.
 *
 * 8 secciones que presentan el framework pedagógico central del ecosistema:
 * 3 capas (Criterio, Supervisión IA, Posicionamiento), 4 competencias
 * (Pedir, Evaluar, Iterar, Integrar), y el CID de 90 días.
 *
 * Audiencia: usuarios SaaS, prospects B2C/B2B, evaluadores de programas.
 * Tono: profesional, orientado a producto (3ª persona).
 *
 * @see docs/implementacion/20260327c-Plan_Implementacion_Metodo_Jaraba_SaaS_Clase_Mundial_v1_Claude.md
 */
class MetodoLandingController extends ControllerBase {

  /**
   * MARKETING-TRUTH-001: Dato verificable del Programa Andalucía +ei 1ª Ed.
   *
   * Fuente: PED S.L. - PIIL Andalucía 2023, 50 participantes.
   * Documentado en docs/tecnicos/20260327c-Auditoria_Specs_Metodo_Jaraba_SaaS_v1_Claude.md
   */
  public const INSERTION_RATE = '46';
  public const INSERTION_SOURCE_KEY = 'andalucia_ei_1e';

  /**
   * MegaMenuBridgeService para catálogo de verticales (optional cross-module).
   */
  protected ?MegaMenuBridgeService $megaMenuBridge = NULL;

  /**
   * Base domain del SaaS (DI via Settings).
   */
  protected string $saasBaseDomain = 'plataformadeecosistemas.com';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->megaMenuBridge = $container->get('ecosistema_jaraba_core.mega_menu_bridge');
    $instance->saasBaseDomain = \Drupal\Core\Site\Settings::get('jaraba_base_domain', 'plataformadeecosistemas.com');
    return $instance;
  }

  /**
   * Renders the Método Jaraba landing page (v2).
   *
   * @return array
   *   Render array with #theme 'metodo_landing'.
   */
  /**
   * @return array<string, mixed>
   */
  public function landing(): array {
    return [
      '#theme' => 'metodo_landing',
      '#hero' => $this->buildHero(),
      '#problema' => $this->buildProblema(),
      '#solucion' => $this->buildSolucion(),
      '#capas' => $this->buildCapas(),
      '#competencias' => $this->buildCompetencias(),
      '#cid' => $this->buildCid(),
      '#caminos' => $this->buildCaminos(),
      '#evidencia' => $this->buildEvidencia(),
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_theme/metodo-landing',
        ],
      ],
      '#cache' => [
        'contexts' => ['url.path', 'languages:language_content'],
        'tags' => ['config:saas_plan_tier_list'],
        'max-age' => 3600,
      ],
    ];
  }

  /**
   * Sección 1: Hero — Propuesta de valor.
   */
  /**
   * @return array<string, mixed>
   */
  protected function buildHero(): array {
    return [
      'badge' => $this->t('Método Jaraba™'),
      'title' => $this->t('Aprende a supervisar agentes de IA. Y que eso se convierta en tu profesión.'),
      'subtitle' => $this->t('Un sistema de capacitación en 90 días que te enseña a generar impacto económico real dirigiendo inteligencia artificial. Sin humo. Sin tecnicismos. Con resultados medibles.'),
      'stat_value' => self::INSERTION_RATE,
      'stat_suffix' => '%',
      'stat_label' => $this->t('de inserción laboral'),
      'stat_source' => $this->t('Programa Andalucía +ei, 1ª Edición'),
      'cta_primary' => [
        'text' => $this->t('Ver cómo funciona'),
        'url' => '#solucion',
        'track' => 'metodo_hero_scroll',
      ],
      'cta_secondary' => [
        'text' => $this->t('Empezar gratis'),
        'url' => $this->getRegisterUrl(),
        'track' => 'metodo_hero_register',
      ],
    ];
  }

  /**
   * Sección 2: El problema — Pain points.
   */
  /**
   * @return array<string, mixed>
   */
  protected function buildProblema(): array {
    return [
      'title' => $this->t('Vi un puente roto y decidí construirlo.'),
      'intro' => $this->t('Durante 30 años gestioné más de 100 millones de euros en fondos europeos. Diseñé planes estratégicos para provincias enteras. Y vi cómo esos recursos no llegaban a quien más los necesitaba.'),
      'pain_points' => [
        [
          'quote' => $this->t('Me dicen que use IA, pero no sé por dónde empezar'),
          'description' => $this->t('Hay miles de herramientas. Ninguna te enseña a pensar.'),
          'icon' => ['category' => 'ai', 'name' => 'challenge'],
          'color' => 'naranja-impulso',
        ],
        [
          'quote' => $this->t('Hice un curso y sigo sin saber cómo cobrar por esto'),
          'description' => $this->t('La formación tradicional enseña teoría. El método enseña a facturar.'),
          'icon' => ['category' => 'verticals', 'name' => 'formacion'],
          'color' => 'azul-corporativo',
        ],
        [
          'quote' => $this->t('Mi negocio es invisible en internet'),
          'description' => $this->t('No necesitas un experto. Necesitas aprender a dirigir uno (de IA).'),
          'icon' => ['category' => 'business', 'name' => 'store-digital'],
          'color' => 'verde-innovacion',
        ],
      ],
    ];
  }

  /**
   * Sección 3: La solución — Flujo invertido.
   */
  /**
   * @return array<string, mixed>
   */
  protected function buildSolucion(): array {
    return [
      'title' => $this->t('No aprendes a hacer las cosas. Aprendes a dirigir a quien las hace.'),
      'intro' => $this->t('En la formación tradicional, primero te explican la teoría, luego practicas, y meses después (si llegas) lo aplicas. El Método Jaraba lo invierte:'),
      'steps' => [
        [
          'number' => 1,
          'title' => $this->t('Haces la tarea con un agente de IA'),
          'description' => $this->t('Desde el día 1. Sin esperar a que alguien te dé permiso.'),
        ],
        [
          'number' => 2,
          'title' => $this->t('Supervisas el resultado'),
          'description' => $this->t('¿Está bien? ¿Falta algo? ¿Suena a ti?'),
        ],
        [
          'number' => 3,
          'title' => $this->t('Entiendes el concepto'),
          'description' => $this->t('Sin que nadie te dé clase. Aprendes haciendo.'),
        ],
      ],
      'metaphor_title' => $this->t('Tú eres el director de obra.'),
      'metaphor_text' => $this->t('La IA es tu equipo especializado. Tú decides qué se construye y cómo. Ellos ejecutan bajo tu supervisión.'),
    ];
  }

  /**
   * Sección 4: Las 3 capas del método.
   */
  /**
   * @return array<string, mixed>
   */
  protected function buildCapas(): array {
    return [
      'title' => $this->t('Tres capas. Una competencia profesional completa.'),
      'items' => [
        [
          'name' => $this->t('Criterio'),
          'question' => $this->t('¿Para qué?'),
          'description' => $this->t('Saber lo que quieres. Entender tu mercado. Tomar decisiones. Esto no lo sustituye ninguna IA.'),
          'icon' => ['category' => 'ai', 'name' => 'lightbulb'],
          'color' => 'naranja-impulso',
        ],
        [
          'name' => $this->t('Supervisión IA'),
          'question' => $this->t('¿Cómo con IA?'),
          'description' => $this->t('Pedir. Evaluar. Iterar. Integrar. Las 4 competencias que convierten a cualquier persona en director/a de un equipo de agentes de IA.'),
          'icon' => ['category' => 'ai', 'name' => 'copilot'],
          'color' => 'azul-corporativo',
        ],
        [
          'name' => $this->t('Posicionamiento'),
          'question' => $this->t('¿Cómo cobro?'),
          'description' => $this->t('Propuesta de valor. Presencia digital. Embudo de captación. Porque de nada sirve saber si no facturas.'),
          'icon' => ['category' => 'analytics', 'name' => 'target'],
          'color' => 'verde-innovacion',
        ],
      ],
    ];
  }

  /**
   * Sección 5: Las 4 competencias.
   */
  /**
   * @return array<string, mixed>
   */
  protected function buildCompetencias(): array {
    return [
      'title' => $this->t('4 competencias que se entrenan, se miden y se certifican.'),
      'items' => [
        [
          'name' => $this->t('Pedir'),
          'description' => $this->t('Formular instrucciones claras al agente IA.'),
          'example' => $this->t('«Calcula el punto de equilibrio de una cafetería con estos datos...»'),
          'icon' => ['category' => 'ai', 'name' => 'chat'],
          'color' => 'naranja-impulso',
        ],
        [
          'name' => $this->t('Evaluar'),
          'description' => $this->t('Determinar si el resultado es correcto y útil.'),
          'example' => $this->t('«La IA dice que la tarifa plana es 60 €. ¿Sigue siendo así en 2026?»'),
          'icon' => ['category' => 'compliance', 'name' => 'shield-check'],
          'color' => 'azul-corporativo',
        ],
        [
          'name' => $this->t('Iterar'),
          'description' => $this->t('Ajustar las instrucciones para mejorar el output.'),
          'example' => $this->t('«Suena demasiado formal. Reescríbelo como si hablaras con un vecino.»'),
          'icon' => ['category' => 'ai', 'name' => 'sparkles'],
          'color' => 'verde-innovacion',
        ],
        [
          'name' => $this->t('Integrar'),
          'description' => $this->t('Combinar outputs de varios agentes en un resultado final.'),
          'example' => $this->t('«Une el plan financiero, el Lean Canvas y el pitch en un solo documento.»'),
          'icon' => ['category' => 'verticals', 'name' => 'ecosystem'],
          'color' => 'naranja-impulso',
        ],
      ],
    ];
  }

  /**
   * Sección 6: CID — Ciclo de Impacto Digital de 90 días.
   */
  /**
   * @return array<string, mixed>
   */
  protected function buildCid(): array {
    return [
      'title' => $this->t('3 fases. 90 días. Resultados que puedes medir.'),
      'phases' => [
        [
          'number' => 1,
          'name' => $this->t('Criterio y primeras tareas con IA'),
          'days' => $this->t('Días 1-30'),
          'deliverable' => $this->t('Diagnóstico + hipótesis + primeras tareas productivas.'),
          'color' => 'naranja-impulso',
        ],
        [
          'number' => 2,
          'name' => $this->t('Supervisión y construcción'),
          'days' => $this->t('Días 31-60'),
          'deliverable' => $this->t('Portfolio con 5+ outputs profesionales reales.'),
          'color' => 'azul-corporativo',
        ],
        [
          'number' => 3,
          'name' => $this->t('Posicionamiento e impacto'),
          'days' => $this->t('Días 61-90'),
          'deliverable' => $this->t('Presencia digital + proyecto piloto + primer ingreso.'),
          'color' => 'verde-innovacion',
        ],
      ],
    ];
  }

  /**
   * Sección 7: 3 caminos — Aplicaciones del método.
   *
   * Links cross-domain al SaaS con UTM para tracking de campañas.
   * Reutiliza getVerticalCatalog() como SSOT de verticales.
   */
  /**
   * @return array<string, mixed>
   */
  protected function buildCaminos(): array {
    $baseUrl = 'https://' . $this->saasBaseDomain;
    $langPrefix = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $lp = ($langPrefix !== 'es') ? '/' . $langPrefix : '/es';

    return [
      'title' => $this->t('Un método. Tres aplicaciones. Tu elección.'),
      'paths' => [
        [
          'audience' => $this->t('Busco trabajo'),
          'description' => $this->t('CV con IA, entrevistas simuladas, perfil digital profesional.'),
          'cta_text' => $this->t('Impulsar mi carrera'),
          'cta_url' => $baseUrl . $lp . '/empleabilidad?utm_source=metodo&utm_medium=landing&utm_content=empleabilidad',
          'icon' => ['category' => 'verticals', 'name' => 'empleabilidad'],
          'color' => 'verde-innovacion',
          'track' => 'metodo_camino_empleabilidad',
        ],
        [
          'audience' => $this->t('Quiero emprender'),
          'description' => $this->t('Lean Canvas con IA, packs de servicios, primeros clientes, facturación.'),
          'cta_text' => $this->t('Lanzar mi negocio'),
          'cta_url' => $baseUrl . $lp . '/emprendimiento?utm_source=metodo&utm_medium=landing&utm_content=emprendimiento',
          'icon' => ['category' => 'verticals', 'name' => 'emprendimiento'],
          'color' => 'naranja-impulso',
          'track' => 'metodo_camino_emprendimiento',
        ],
        [
          'audience' => $this->t('Tengo negocio'),
          'description' => $this->t('Web profesional, redes sociales, reseñas, embudo de captación digital.'),
          'cta_text' => $this->t('Digitalizar mi negocio'),
          'cta_url' => $baseUrl . $lp . '/comercioconecta?utm_source=metodo&utm_medium=landing&utm_content=digitalizacion',
          'icon' => ['category' => 'verticals', 'name' => 'comercioconecta'],
          'color' => 'azul-corporativo',
          'track' => 'metodo_camino_digitalizacion',
        ],
      ],
    ];
  }

  /**
   * Sección 8: Evidencia + CTA final.
   *
   * MARKETING-TRUTH-001: El dato 46% proviene del caso real PED S.L.
   * Programa PIIL Andalucía 2023, 50 participantes.
   */
  /**
   * @return array<string, mixed>
   */
  protected function buildEvidencia(): array {
    return [
      'stat_value' => self::INSERTION_RATE,
      'stat_suffix' => '%',
      'stat_label' => $this->t('inserción laboral'),
      'stat_source' => $this->t('Programa Andalucía +ei, 1ª Edición. Colectivos vulnerables.'),
      'quote' => $this->t('Si funciona con el colectivo más difícil, funciona contigo.'),
      'trust_logos' => TRUE,
      'cta' => [
        'text' => $this->t('Empieza gratis'),
        'url' => $this->getRegisterUrl(),
        'track' => 'metodo_evidencia_register',
      ],
      'cta_secondary' => [
        'text' => $this->t('Ver planes y precios'),
        'url' => $this->getPlanesUrl(),
        'track' => 'metodo_evidencia_planes',
      ],
    ];
  }

  /**
   * Obtiene la URL de registro con fallback seguro.
   */
  protected function getRegisterUrl(): string {
    try {
      return Url::fromRoute('user.register')->toString();
    }
    catch (\Exception $e) {
      return '/user/register';
    }
  }

  /**
   * Obtiene la URL de planes con fallback seguro.
   */
  protected function getPlanesUrl(): string {
    try {
      return Url::fromRoute('jaraba_billing.pricing_page')->toString();
    }
    catch (\Exception $e) {
      return '/planes';
    }
  }

}
