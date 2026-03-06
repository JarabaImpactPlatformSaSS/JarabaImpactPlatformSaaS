<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Service\ExpedienteCompletenessService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Document hub controller for Andalucía +ei participants.
 *
 * Renders a structured view of the participant's full document portfolio,
 * grouped by category (STO, programa, mentoría, inserción) with
 * completeness indicators and action buttons.
 */
class ExpedienteHubController extends ControllerBase {

  /**
   * Constructs an ExpedienteHubController.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    protected ExpedienteCompletenessService $completenessService,
    protected LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('jaraba_andalucia_ei.expediente_completeness'),
      $container->get('logger.channel.jaraba_andalucia_ei'),
    );
  }

  /**
   * Renders the document hub for the current participant.
   *
   * @return array
   *   Render array.
   */
  public function hub(): array {
    $participante = $this->getParticipanteActual();
    if (!$participante) {
      throw new AccessDeniedHttpException('No active participant found.');
    }

    $participanteId = (int) $participante->id();
    $completeness = $this->completenessService->getCompleteness($participanteId);
    $documentsByCategory = $this->completenessService->getDocumentsByCategory($participanteId);

    return [
      '#theme' => 'expediente_hub',
      '#participante' => [
        'id' => $participanteId,
        'nombre' => $participante->label(),
        'fase' => $participante->get('fase_actual')->value ?? 'atencion',
      ],
      '#completeness' => $completeness,
      '#documents_by_category' => $documentsByCategory,
      '#total_percent' => $completeness['total_percent'] ?? 0,
      '#attached' => [
        'library' => [
          'jaraba_andalucia_ei/dashboard',
        ],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['expediente_documento_list'],
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Gets the current user's participant entity.
   */
  protected function getParticipanteActual(): mixed {
    $user = $this->currentUser();
    if ($user->isAnonymous()) {
      return NULL;
    }

    try {
      $storage = $this->entityTypeManager->getStorage('programa_participante_ei');
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('uid', $user->id())
        ->sort('created', 'DESC')
        ->range(0, 1)
        ->execute();

      return !empty($ids) ? $storage->load(reset($ids)) : NULL;
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error loading participante: @msg', ['@msg' => $e->getMessage()]);
      return NULL;
    }
  }

}
