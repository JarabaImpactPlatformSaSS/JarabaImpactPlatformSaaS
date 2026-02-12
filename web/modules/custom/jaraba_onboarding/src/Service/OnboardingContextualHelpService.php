<?php

declare(strict_types=1);

namespace Drupal\jaraba_onboarding\Service;

use Psr\Log\LoggerInterface;

/**
 * Servicio de ayuda contextual para onboarding.
 *
 * Proporciona contenido de ayuda y tips basados en el paso
 * actual del onboarding o la ruta visitada por el usuario.
 */
class OnboardingContextualHelpService {

  /**
   * Mapeo de pasos a contenido de ayuda.
   *
   * @var array<string, array{title: string, body: string, position: string}>
   */
  protected const STEP_HELP = [
    'profile_complete' => [
      'title' => 'Completa tu perfil',
      'body' => 'Agrega tu foto, nombre y datos de contacto para personalizar tu experiencia.',
      'position' => 'bottom',
    ],
    'first_login' => [
      'title' => 'Bienvenido a la plataforma',
      'body' => 'Este es tu primer inicio de sesion. Explora el dashboard para conocer las funcionalidades.',
      'position' => 'center',
    ],
    'tour_complete' => [
      'title' => 'Tour interactivo',
      'body' => 'Sigue el tour guiado para descubrir las funcionalidades principales.',
      'position' => 'top',
    ],
    'first_action' => [
      'title' => 'Realiza tu primera accion',
      'body' => 'Crea tu primer recurso o realiza una accion clave en tu vertical.',
      'position' => 'right',
    ],
    'team_invite' => [
      'title' => 'Invita a tu equipo',
      'body' => 'Agrega miembros a tu organizacion para colaborar en la plataforma.',
      'position' => 'bottom',
    ],
    'integration_setup' => [
      'title' => 'Configura integraciones',
      'body' => 'Conecta herramientas externas para potenciar tu experiencia.',
      'position' => 'left',
    ],
  ];

  /**
   * Mapeo de rutas a contenido de ayuda contextual.
   *
   * @var array<string, array<array{title: string, body: string, selector: string, position: string}>>
   */
  protected const ROUTE_HELP = [
    'ecosistema_jaraba_core.dashboard' => [
      [
        'title' => 'Dashboard principal',
        'body' => 'Aqui veras un resumen de la actividad de tu organizacion.',
        'selector' => '.dashboard__header',
        'position' => 'bottom',
      ],
      [
        'title' => 'Metricas clave',
        'body' => 'Estas tarjetas muestran los KPIs mas importantes de tu negocio.',
        'selector' => '.dashboard__metrics',
        'position' => 'bottom',
      ],
    ],
    'jaraba_onboarding.dashboard' => [
      [
        'title' => 'Tu progreso',
        'body' => 'Sigue tu avance en el onboarding y completa cada paso.',
        'selector' => '.onboarding-dashboard__progress',
        'position' => 'bottom',
      ],
    ],
  ];

  public function __construct(
    protected LoggerInterface $logger,
  ) {}

  /**
   * Obtiene la ayuda para un paso de onboarding.
   *
   * @param string $stepId
   *   Identificador del paso.
   *
   * @return array
   *   Datos de ayuda: title, body, position. Vacio si no hay ayuda.
   */
  public function getHelpForStep(string $stepId): array {
    try {
      return self::STEP_HELP[$stepId] ?? [];
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo ayuda para paso @step: @error', [
        '@step' => $stepId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene la ayuda contextual para una ruta.
   *
   * @param string $routeName
   *   Nombre de la ruta de Drupal.
   *
   * @return array
   *   Array de tips contextuales para la ruta. Vacio si no hay ayuda.
   */
  public function getHelpForRoute(string $routeName): array {
    try {
      return self::ROUTE_HELP[$routeName] ?? [];
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo ayuda para ruta @route: @error', [
        '@route' => $routeName,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

}
