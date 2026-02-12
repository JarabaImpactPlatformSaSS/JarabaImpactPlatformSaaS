<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_credentials\Service\StackProgressTracker;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * REST API endpoints para stacks de credenciales.
 */
class StacksApiController extends ControllerBase {

  protected StackProgressTracker $progressTracker;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = new static();
    $instance->progressTracker = $container->get('jaraba_credentials.stack_progress');
    return $instance;
  }

  /**
   * Listado de stacks disponibles.
   */
  public function listStacks(): JsonResponse {
    $ids = $this->entityTypeManager()->getStorage('credential_stack')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', TRUE)
      ->sort('name', 'ASC')
      ->execute();

    $stacks = $this->entityTypeManager()->getStorage('credential_stack')->loadMultiple($ids);
    $data = [];

    foreach ($stacks as $stack) {
      /** @var \Drupal\jaraba_credentials\Entity\CredentialStack $stack */
      $data[] = $this->serializeStack($stack);
    }

    return new JsonResponse(['stacks' => $data]);
  }

  /**
   * Detalle de un stack con sus componentes.
   */
  public function getStack(string $id): JsonResponse {
    $stack = $this->entityTypeManager()->getStorage('credential_stack')->load($id);
    if (!$stack) {
      return new JsonResponse(['error' => 'Stack not found'], 404);
    }

    $data = $this->serializeStack($stack);

    // Cargar nombres de templates requeridos.
    $requiredIds = $stack->getRequiredTemplateIds();
    $data['required_template_details'] = $this->loadTemplateDetails($requiredIds);

    $optionalIds = $stack->getOptionalTemplateIds();
    if (!empty($optionalIds)) {
      $data['optional_template_details'] = $this->loadTemplateDetails($optionalIds);
    }

    return new JsonResponse($data);
  }

  /**
   * Progreso del usuario actual en todos los stacks.
   */
  public function myProgress(): JsonResponse {
    $uid = (int) $this->currentUser()->id();
    $progress = $this->progressTracker->getProgressForUser($uid);

    $data = [];
    foreach ($progress as $item) {
      $data[] = [
        'stack_id' => $item['stack']->id(),
        'stack_name' => $item['stack']->get('name')->value,
        'percent' => $item['percent'],
        'completed_templates' => $item['completed_templates'],
        'status' => $item['status'],
      ];
    }

    return new JsonResponse(['progress' => $data]);
  }

  /**
   * Progreso del usuario actual en un stack específico.
   */
  public function stackProgress(string $id): JsonResponse {
    $stack = $this->entityTypeManager()->getStorage('credential_stack')->load($id);
    if (!$stack) {
      return new JsonResponse(['error' => 'Stack not found'], 404);
    }

    $uid = (int) $this->currentUser()->id();
    $allProgress = $this->progressTracker->getProgressForUser($uid);

    $stackProgress = NULL;
    foreach ($allProgress as $item) {
      if ((string) $item['stack']->id() === $id) {
        $stackProgress = $item;
        break;
      }
    }

    if (!$stackProgress) {
      return new JsonResponse([
        'stack_id' => $id,
        'stack_name' => $stack->get('name')->value,
        'percent' => 0,
        'completed_templates' => [],
        'status' => 'not_started',
      ]);
    }

    return new JsonResponse([
      'stack_id' => $id,
      'stack_name' => $stackProgress['stack']->get('name')->value,
      'percent' => $stackProgress['percent'],
      'completed_templates' => $stackProgress['completed_templates'],
      'status' => $stackProgress['status'],
    ]);
  }

  /**
   * Stacks recomendados para el usuario actual.
   */
  public function recommended(): JsonResponse {
    $uid = (int) $this->currentUser()->id();
    $recommended = $this->progressTracker->getRecommendedStacks($uid);

    $data = [];
    foreach ($recommended as $item) {
      $data[] = [
        'stack_id' => $item['stack']->id(),
        'stack_name' => $item['stack']->get('name')->value,
        'percent' => $item['percent'],
        'completed_templates' => $item['completed_templates'],
        'status' => $item['status'],
      ];
    }

    return new JsonResponse(['recommended' => $data]);
  }

  /**
   * Serializa un stack para la respuesta JSON.
   */
  protected function serializeStack($stack): array {
    return [
      'id' => $stack->id(),
      'uuid' => $stack->uuid(),
      'name' => $stack->get('name')->value,
      'machine_name' => $stack->get('machine_name')->value,
      'description' => $stack->get('description')->value ?? '',
      'required_templates' => $stack->getRequiredTemplateIds(),
      'optional_templates' => $stack->getOptionalTemplateIds(),
      'min_required' => $stack->getMinRequired(),
      'bonus_credits' => (int) ($stack->get('bonus_credits')->value ?? 0),
      'bonus_xp' => (int) ($stack->get('bonus_xp')->value ?? 0),
      'eqf_level' => $stack->get('eqf_level')->value,
      'ects_credits' => $stack->get('ects_credits')->value,
    ];
  }

  /**
   * Carga detalles de templates por IDs.
   */
  protected function loadTemplateDetails(array $ids): array {
    $details = [];
    foreach ($ids as $id) {
      $template = $this->entityTypeManager()->getStorage('credential_template')->load($id);
      if ($template) {
        $details[] = [
          'id' => $template->id(),
          'name' => $template->get('name')->value,
          'machine_name' => $template->get('machine_name')->value ?? '',
        ];
      }
    }
    return $details;
  }

}
