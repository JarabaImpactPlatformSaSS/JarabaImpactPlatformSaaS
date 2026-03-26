<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\jaraba_site_builder\Service\MetaSiteResolverService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Landing page para el Método Impacto Jaraba.
 *
 * Ruta pública /metodo orientada a jarabaimpact.com.
 * Presenta las 3 fases, 5 packs, resultados reales y certificaciones.
 *
 * MARKETING-TRUTH-001: Certificaciones marcadas como "Próximamente"
 * porque el backend de certificación no existe todavía.
 */
class MetodoLandingController extends ControllerBase {

  /**
   * The MetaSite resolver service (optional, cross-module).
   */
  protected ?MetaSiteResolverService $metaSiteResolver = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    if ($container->has('jaraba_site_builder.metasite_resolver')) {
      $instance->metaSiteResolver = $container->get('jaraba_site_builder.metasite_resolver');
    }
    return $instance;
  }

  /**
   * Renders the Método Impacto Jaraba landing page.
   *
   * @return array
   *   Render array with #theme 'metodo_landing'.
   */
  public function landing(): array {
    return [
      '#theme' => 'metodo_landing',
      '#hero' => $this->buildHero(),
      '#fases' => $this->buildFases(),
      '#packs' => $this->buildPacks(),
      '#resultados' => $this->buildResultados(),
      '#certificaciones' => $this->buildCertificaciones(),
      '#faq' => $this->buildFaq(),
      '#cta_final' => $this->buildCtaFinal(),
      '#cache' => [
        'contexts' => ['url.path'],
        'max-age' => 3600,
      ],
    ];
  }

  /**
   * Builds hero section data.
   */
  protected function buildHero(): array {
    $register_url = '';
    $login_url = '';
    try {
      $register_url = Url::fromRoute('user.register')->toString();
    }
    catch (\Exception $e) {
      $register_url = '/user/register';
    }
    try {
      $login_url = Url::fromRoute('user.login')->toString();
    }
    catch (\Exception $e) {
      $login_url = '/user/login';
    }

    return [
      'title' => $this->t('El Método Impacto Jaraba'),
      'subtitle' => $this->t('Tu Plan de Transformación Digital en 90 Días'),
      'description' => $this->t('Un método probado en 3 fases que combina tecnología SaaS, acompañamiento experto e inteligencia artificial para digitalizar tu negocio con resultados medibles.'),
      'cta_primary' => [
        'text' => $this->t('Empieza tu transformación'),
        'url' => $register_url,
      ],
      'cta_secondary' => [
        'text' => $this->t('Ya tengo cuenta'),
        'url' => $login_url,
      ],
    ];
  }

  /**
   * Builds the 3 phases of the method.
   */
  protected function buildFases(): array {
    return [
      [
        'number' => 1,
        'title' => $this->t('Diagnóstico'),
        'description' => $this->t('Analizamos tu negocio con herramientas de IA para identificar oportunidades de digitalización, puntos de dolor y quick wins. Recibes un informe personalizado con tu hoja de ruta.'),
        'icon' => ['category' => 'ui', 'name' => 'search'],
        'duration' => $this->t('Semanas 1-2'),
        'deliverables' => [
          $this->t('Auditoría digital completa'),
          $this->t('Mapa de oportunidades'),
          $this->t('Hoja de ruta personalizada'),
        ],
      ],
      [
        'number' => 2,
        'title' => $this->t('Implementación'),
        'description' => $this->t('Configuramos tu ecosistema digital: plataforma SaaS, automatizaciones, presencia online y herramientas de gestión. Tu equipo recibe formación práctica.'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'duration' => $this->t('Semanas 3-8'),
        'deliverables' => [
          $this->t('Plataforma configurada y operativa'),
          $this->t('Automatizaciones activas'),
          $this->t('Equipo formado'),
        ],
      ],
      [
        'number' => 3,
        'title' => $this->t('Optimización'),
        'description' => $this->t('Medimos resultados, ajustamos estrategias con datos reales y escalamos lo que funciona. El Copilot IA te acompaña en la toma de decisiones diaria.'),
        'icon' => ['category' => 'ui', 'name' => 'chart'],
        'duration' => $this->t('Semanas 9-12'),
        'deliverables' => [
          $this->t('Dashboard de métricas en tiempo real'),
          $this->t('Copilot IA configurado'),
          $this->t('Plan de escalado'),
        ],
      ],
    ];
  }

  /**
   * Builds the 5 service packs as method modules.
   */
  protected function buildPacks(): array {
    return [
      [
        'nombre' => $this->t('Pack Presencia Digital'),
        'descripcion' => $this->t('Web profesional, SEO local, Google Business Profile y redes sociales integradas. Tu negocio visible en internet en 48 horas.'),
        'icon' => ['category' => 'business', 'name' => 'globe'],
        'precio_desde' => 29,
      ],
      [
        'nombre' => $this->t('Pack Gestión Inteligente'),
        'descripcion' => $this->t('CRM, facturación, agenda y gestión de clientes con automatizaciones. Reduce 10 horas semanales de trabajo administrativo.'),
        'icon' => ['category' => 'business', 'name' => 'briefcase'],
        'precio_desde' => 49,
      ],
      [
        'nombre' => $this->t('Pack Comercio Digital'),
        'descripcion' => $this->t('Tienda online, catálogo de productos, cobro con Stripe, logística y trazabilidad. Vende online desde el primer día.'),
        'icon' => ['category' => 'business', 'name' => 'cart'],
        'precio_desde' => 59,
      ],
      [
        'nombre' => $this->t('Pack Formación y Talento'),
        'descripcion' => $this->t('LMS propio, itinerarios formativos, certificaciones y gestión del equipo. Forma a tu equipo sin depender de plataformas externas.'),
        'icon' => ['category' => 'verticals', 'name' => 'graduation'],
        'precio_desde' => 39,
      ],
      [
        'nombre' => $this->t('Pack IA y Automatización'),
        'descripcion' => $this->t('Copilot IA, análisis predictivo, automatización de procesos y asistente inteligente. La IA trabaja para tu negocio 24/7.'),
        'icon' => ['category' => 'ai', 'name' => 'brain'],
        'precio_desde' => 69,
      ],
    ];
  }

  /**
   * Builds the results section with PED S.L. case study data.
   */
  protected function buildResultados(): array {
    return [
      'case_study' => [
        'empresa' => 'PED S.L.',
        'programa' => $this->t('PIIL Andalucía 2023'),
        'participantes' => 50,
        'insercion_rate' => 46,
        'negocios_creados' => 12,
        'facturacion_media' => 2800,
        'satisfaccion' => 4.7,
        'duracion_meses' => 6,
      ],
      'metricas_globales' => [
        [
          'valor' => '90',
          'sufijo' => $this->t('días'),
          'label' => $this->t('Tiempo medio de transformación'),
        ],
        [
          'valor' => '46',
          'sufijo' => '%',
          'label' => $this->t('Tasa de inserción laboral'),
        ],
        [
          'valor' => '4.7',
          'sufijo' => '/5',
          'label' => $this->t('Satisfacción media'),
        ],
        [
          'valor' => '+2.800',
          'sufijo' => '€',
          'label' => $this->t('Facturación media mensual'),
        ],
      ],
    ];
  }

  /**
   * Builds certification levels.
   *
   * MARKETING-TRUTH-001: All marked as "Próximamente" since the
   * certification backend does not exist yet.
   */
  protected function buildCertificaciones(): array {
    return [
      [
        'level' => 1,
        'title' => $this->t('Consultor Certificado'),
        'description' => $this->t('Domina el Método Jaraba y aplícalo con tus propios clientes. Acceso a herramientas, formación continua y soporte técnico.'),
        'requisitos' => [
          $this->t('Completar formación de 40 horas'),
          $this->t('Caso práctico aprobado'),
          $this->t('Examen de certificación'),
        ],
        'proximamente' => TRUE,
      ],
      [
        'level' => 2,
        'title' => $this->t('Partner Oficial'),
        'description' => $this->t('Acceso exclusivo a la plataforma white-label, leads cualificados y comisiones recurrentes. Construye tu negocio sobre el método.'),
        'requisitos' => [
          $this->t('Certificación de Consultor activa'),
          $this->t('Mínimo 5 clientes gestionados'),
          $this->t('Acuerdo de partner firmado'),
        ],
        'proximamente' => TRUE,
      ],
      [
        'level' => 3,
        'title' => $this->t('Franquicia Jaraba'),
        'description' => $this->t('Modelo de negocio completo con marca, territorio exclusivo, formación de equipo y soporte integral. Emprende con un método probado.'),
        'requisitos' => [
          $this->t('Experiencia como Partner (12+ meses)'),
          $this->t('Equipo de mínimo 3 personas'),
          $this->t('Plan de negocio aprobado'),
        ],
        'proximamente' => TRUE,
      ],
    ];
  }

  /**
   * Builds FAQ items.
   */
  protected function buildFaq(): array {
    return [
      [
        'question' => $this->t('¿Qué incluye el Método Impacto Jaraba?'),
        'answer' => $this->t('El método incluye 3 fases (Diagnóstico, Implementación y Optimización) durante 90 días, con acceso a la plataforma SaaS, acompañamiento de un consultor y Copilot IA integrado.'),
      ],
      [
        'question' => $this->t('¿Para qué tipo de negocios es adecuado?'),
        'answer' => $this->t('Está diseñado para PYMEs, autónomos, cooperativas y emprendedores que quieran digitalizar su negocio. Funciona especialmente bien en comercio local, servicios profesionales, agroalimentación y formación.'),
      ],
      [
        'question' => $this->t('¿Cuánto cuesta el método completo?'),
        'answer' => $this->t('Depende de los packs que necesites. Puedes empezar con un solo pack desde 29 €/mes y ampliar según crezcas. Ofrecemos 14 días de prueba gratuita sin compromiso.'),
      ],
      [
        'question' => $this->t('¿Necesito conocimientos técnicos?'),
        'answer' => $this->t('No. La plataforma está diseñada para personas sin conocimientos técnicos. Incluye formación práctica, tutoriales paso a paso y un Copilot IA que te guía en cada decisión.'),
      ],
      [
        'question' => $this->t('¿Qué resultados puedo esperar en 90 días?'),
        'answer' => $this->t('Basándonos en el caso real de PED S.L. con 50 participantes: presencia digital completa, procesos automatizados, primeros clientes digitales y un 46%% de tasa de inserción laboral en programas de empleo.'),
      ],
    ];
  }

  /**
   * Builds the final CTA section.
   */
  protected function buildCtaFinal(): array {
    $register_url = '';
    try {
      $register_url = Url::fromRoute('user.register')->toString();
    }
    catch (\Exception $e) {
      $register_url = '/user/register';
    }

    return [
      'title' => $this->t('¿Listo para transformar tu negocio?'),
      'subtitle' => $this->t('Empieza hoy con 14 días de prueba gratuita. Sin tarjeta, sin compromiso.'),
      'cta_text' => $this->t('Comenzar prueba gratuita'),
      'cta_url' => $register_url,
    ];
  }

}
