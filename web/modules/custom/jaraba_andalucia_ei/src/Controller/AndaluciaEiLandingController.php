<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * Renders the landing page.
   *
   * @return array
   *   Render array.
   */
  public function landing(): array {
    $solicitarUrl = Url::fromRoute('jaraba_andalucia_ei.solicitar')->toString();

    return [
      '#theme' => 'andalucia_ei_landing',
      '#solicitar_url' => $solicitarUrl,
      '#attached' => [
        'library' => [
          'jaraba_andalucia_ei/dashboard',
        ],
      ],
      '#cache' => [
        'contexts' => ['url.path'],
        'tags' => ['config:jaraba_andalucia_ei.settings'],
        'max-age' => 3600,
      ],
    ];
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

    $programa = [
      'expediente' => 'SC/ICV/0111/2025',
      'subvencion' => '202.500',
      'participantes' => 45,
      'inserciones' => 18,
      'tasa_insercion' => 40,
      'incentivo' => 528,
      'horas_orientacion' => 10,
      'horas_formacion' => 50,
      'horas_insercion' => 40,
      'duracion_meses' => 18,
      'fecha_inicio' => '29/12/2025',
      'fecha_fin' => '28/06/2027',
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
      'equipo' => [
        ['nombre' => 'José Jaraba Muñoz', 'titulo' => 'Coordinador — Licenciado en Derecho, Máster', 'sede' => 'Sevilla'],
        ['nombre' => 'Remedios Estévez Palomino', 'titulo' => 'Técnica — Licenciada en Economía', 'sede' => 'Málaga'],
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
