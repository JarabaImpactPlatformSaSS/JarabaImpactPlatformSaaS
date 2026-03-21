<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Landing page de conversión para Andalucía +ei.
 *
 * Renderiza una página con Zero Region Policy que ensambla
 * secciones del page builder en orden optimizado para conversión:
 * Hero > Stats > Features > Content > Testimonials > FAQ > CTA.
 */
class AndaluciaEiLandingController extends ControllerBase {

  /**
   * The extension path resolver.
   */
  protected ExtensionPathResolver $pathResolver;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->pathResolver = $container->get('extension.path.resolver');
    return $instance;
  }

  /**
   * Redirects to the canonical reclutamiento landing.
   *
   * /andalucia-ei/programa was a duplicate of /andaluciamasei.html with less
   * content. Consolidated into a single landing to avoid keyword cannibalization
   * and double maintenance. 301 = permanent redirect for SEO.
   */
  public function landing(): RedirectResponse {
    $url = Url::fromRoute('jaraba_andalucia_ei.reclutamiento')->toString();

    return new RedirectResponse($url, 301);
  }

  /**
   * Landing de reclutamiento para meta-sitio corporativo PED.
   *
   * Servida en /andaluciamasei.html para preservar la reputación SEO
   * acumulada desde la edición anterior del programa.
   *
   * @return array
   *   Render array.
   */
  public function reclutamiento(): array {
    $solicitarUrl = Url::fromRoute('jaraba_andalucia_ei.solicitar')->toString();
    $guiaUrl = Url::fromRoute('jaraba_andalucia_ei.guia_participante')->toString();

    // NO-HARDCODE-PRICE-001: Datos del programa desde config editable en UI.
    $config = $this->config('jaraba_andalucia_ei.settings');

    $programa = [
      'expediente' => $config->get('expediente') ?? 'SC/ICV/0111/2025',
      'subvencion' => $config->get('subvencion_total') ?? '202.500',
      'participantes' => $config->get('plazas_restantes') ?? 45,
      'plazas_totales' => $config->get('plazas_totales') ?? 45,
      'inserciones' => 18,
      'tasa_insercion' => $config->get('tasa_insercion_objetivo') ?? 40,
      'incentivo' => $config->get('incentivo_euros') ?? 528,
      'horas_orientacion' => (int) ($config->get('horas_minimas_orientacion') ?? 10),
      'horas_formacion' => (int) ($config->get('horas_minimas_formacion') ?? 50),
      'horas_insercion' => 40,
      'duracion_meses' => 18,
      'fecha_inicio' => '29/12/2025',
      'fecha_fin' => '28/06/2027',
      'fecha_limite_solicitudes' => $config->get('fecha_limite_solicitudes') ?? '2026-06-30',
      'mostrar_countdown' => (bool) ($config->get('mostrar_countdown') ?? TRUE),
      'sedes' => [
        [
          'ciudad' => 'Málaga',
          'plazas' => 15,
          'direccion' => 'Centro de Negocios Málaga, C. Palma del Río, 19',
          'cp' => '29004',
        ],
        [
          'ciudad' => 'Sevilla',
          'plazas' => 30,
          'direccion' => 'Avda. San Francisco Javier, 22, Edificio Hermes, 1.ª Planta, Módulo 14',
          'cp' => '41018',
        ],
      ],
      'colectivos' => [
        'Personas en desempleo de larga duración (más de 12 meses)',
        'Personas mayores de 45 años',
        'Personas migrantes',
        'Personas con discapacidad (grado igual o superior al 33%)',
        'Personas en situación de exclusión social',
        'Personas perceptoras de prestaciones, subsidio por desempleo o RAI',
      ],
      'financiadores' => [
        'Junta de Andalucía — Servicio Andaluz de Empleo (SAE)',
        'Fondo Social Europeo Plus (FSE+) — 85%',
      ],
      // P2-9: Sectores de empleo objetivo.
      'sectores' => [
        'Hostelería y turismo',
        'Comercio y distribución',
        'Servicios a empresas',
        'Logística y transporte',
        'Tecnología e informática',
        'Industria agroalimentaria',
        'Servicios sociales y sanitarios',
        'Construcción y mantenimiento',
      ],
      'equipo' => [
        [
          'nombre' => 'José Jaraba Muñoz',
          'cargo' => 'Coordinador del Programa',
          'foto' => 'equipo-pepe-jaraba.webp',
          'iniciales' => 'JJ',
          'bio' => 'Jurista especializado en Derecho Comunitario con más de 30 años de experiencia dirigiendo entidades públicas y privadas. Ha gestionado más de 100 millones de euros en fondos europeos y diseñado planes estratégicos para provincias enteras. Creador del Método Jaraba™ de transformación digital con IA aplicada a programas de empleo.',
          'badges' => [
            ['icono' => 'briefcase', 'texto' => '+30 años de experiencia'],
            ['icono' => 'euro', 'texto' => '+100M€ en fondos europeos'],
            ['icono' => 'users', 'texto' => '+500 proyectos acompañados'],
          ],
          'cita' => 'Mi trabajo es construir el puente entre los grandes recursos y las personas que realmente los necesitan.',
          'sede' => 'Sevilla',
          'linkedin' => 'https://www.linkedin.com/in/pepejaraba/',
        ],
        [
          'nombre' => 'Remedios Estévez Palomino',
          'cargo' => 'Técnica de Empleabilidad',
          'foto' => 'equipo-remedios-estevez.webp',
          'iniciales' => 'RE',
          'bio' => 'Licenciada en Economía (UNED) y Máster MBA en Servicios Sociales. Desde 2005 dirige una empresa pública municipal dedicada al desarrollo económico local, la formación para el empleo y el acompañamiento al emprendimiento. Formadora habilitada para docencia en FP con más de 20 años de experiencia en gestión de subvenciones, empleabilidad y servicios públicos en Andalucía.',
          'badges' => [
            ['icono' => 'briefcase', 'texto' => '+20 años en empleo público'],
            ['icono' => 'chart', 'texto' => 'MBA Servicios Sociales'],
            ['icono' => 'users', 'texto' => 'Formadora habilitada FP'],
          ],
          'cita' => 'Cada persona tiene un potencial profesional único; mi labor es ayudarle a descubrirlo y ponerlo en valor.',
          'sede' => 'Málaga',
          'linkedin' => 'https://www.linkedin.com/in/remedios-est%C3%A9vez-palomino/',
        ],
      ],
    ];

    // ZERO-REGION-003: #attached NOT processed in Zero Region pages.
    // Library attached via hook_page_attachments() in .module.
    $modulePath = $this->pathResolver->getPath('module', 'jaraba_andalucia_ei');

    return [
      '#theme' => 'andalucia_ei_reclutamiento',
      '#solicitar_url' => $solicitarUrl,
      '#guia_url' => $guiaUrl,
      '#programa' => $programa,
      '#module_path' => $modulePath,
      '#cache' => [
        'contexts' => ['url.path'],
        'tags' => ['config:jaraba_andalucia_ei.settings'],
        'max-age' => 3600,
      ],
    ];
  }

}
