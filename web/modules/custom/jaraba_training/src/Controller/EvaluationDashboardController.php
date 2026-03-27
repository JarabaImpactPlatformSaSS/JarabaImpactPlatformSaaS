<?php

declare(strict_types=1);

namespace Drupal\jaraba_training\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_training\Service\MethodRubricService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Dashboard del evaluador para certificaciones Método Jaraba.
 *
 * CERT-06: Lista de portfolios pendientes de evaluación.
 * Vista del portfolio completo del participante.
 * Acceso: permiso 'evaluate method portfolio'.
 *
 * @see docs/implementacion/20260327c-Plan_Implementacion_Metodo_Jaraba_SaaS_Clase_Mundial_v1_Claude.md
 */
class EvaluationDashboardController extends ControllerBase {

  protected MethodRubricService $rubricService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->rubricService = $container->get('jaraba_training.method_rubric');
    return $instance;
  }

  /**
   * Renders the evaluation dashboard.
   *
   * @return array<string, mixed>
   *   Render array con listado de certificaciones pendientes.
   */
  public function dashboard(): array {
    $pending = [];
    $completed = [];

    try {
      /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
      $storage = $this->entityTypeManager()->getStorage('user_certification');

      // Certificaciones en evaluación (pendientes).
      $pendingIds = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('certification_status', 'in_progress')
        ->sort('created', 'DESC')
        ->range(0, 50)
        ->execute();

      foreach ($storage->loadMultiple($pendingIds) as $cert) {
        /** @var \Drupal\Core\Entity\ContentEntityInterface $cert */
        $userId = $cert->get('user_id')->target_id;
        $user = $userId !== '' ? $this->entityTypeManager()->getStorage('user')->load($userId) : NULL;
        $pending[] = [
          'id' => $cert->id(),
          'user_name' => ($user !== NULL) ? $user->getDisplayName() : (string) $this->t('Usuario desconocido'),
          'program' => $cert->get('program_id')->entity?->label() ?? $this->t('Sin programa'),
          'created' => $cert->get('created')->value,
          'overall_level' => $cert->get('overall_level')->value,
        ];
      }

      // Certificaciones evaluadas recientemente.
      $completedIds = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('certification_status', 'completed')
        ->sort('changed', 'DESC')
        ->range(0, 20)
        ->execute();

      foreach ($storage->loadMultiple($completedIds) as $cert) {
        /** @var \Drupal\Core\Entity\ContentEntityInterface $cert */
        $userId = $cert->get('user_id')->target_id;
        $user = $userId !== '' ? $this->entityTypeManager()->getStorage('user')->load($userId) : NULL;
        $completed[] = [
          'id' => $cert->id(),
          'user_name' => ($user !== NULL) ? $user->getDisplayName() : (string) $this->t('Usuario desconocido'),
          'overall_level' => $cert->get('overall_level')->value,
          'certification_date' => $cert->get('certification_date')->value,
        ];
      }
    }
    catch (\Throwable $e) {
      $this->getLogger('jaraba_certificacion')->error('Dashboard error: @e', ['@e' => $e->getMessage()]);
    }

    return [
      '#theme' => 'evaluation_dashboard',
      '#pending' => $pending,
      '#completed' => $completed,
      '#competencies' => MethodRubricService::COMPETENCIES,
      '#levels' => MethodRubricService::LEVELS,
      '#cache' => [
        'contexts' => ['user'],
        'max-age' => 300,
      ],
    ];
  }

}
