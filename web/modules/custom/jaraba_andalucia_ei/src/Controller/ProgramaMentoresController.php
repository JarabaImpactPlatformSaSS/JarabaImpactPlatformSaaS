<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Mentores contextualizados para el programa Andalucía +ei.
 *
 * Muestra solo mentores vinculados al grupo/programa del participante,
 * filtrados por program_groups en MentorProfile. No reutiliza /mentors
 * (marketplace genérico) sino que contextualiza para el programa público.
 */
class ProgramaMentoresController extends ControllerBase {

  /**
   * Constructs a ProgramaMentoresController.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
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
      $container->get('file_url_generator'),
      $container->get('logger.channel.jaraba_andalucia_ei'),
    );
  }

  /**
   * Renders the contextualized mentors page for Andalucía +ei.
   *
   * @return array
   *   Render array.
   */
  public function mentores(): array {
    $participante = $this->getParticipanteActual();
    $mentors = $this->loadProgramMentors($participante);

    $mentorData = [];
    foreach ($mentors as $mentor) {
      $avatar = NULL;
      if (!$mentor->get('avatar')->isEmpty()) {
        $file = $mentor->get('avatar')->entity;
        if ($file) {
          $avatar = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
        }
      }

      $specializations = [];
      foreach ($mentor->get('specializations') as $item) {
        if ($item->value) {
          $specializations[] = $item->value;
        }
      }

      $sectors = [];
      foreach ($mentor->get('sectors') as $item) {
        if ($item->value) {
          $sectors[] = $item->value;
        }
      }

      $mentorData[] = [
        'id' => $mentor->id(),
        'display_name' => $mentor->getDisplayName(),
        'headline' => $mentor->getHeadline(),
        'avatar' => $avatar,
        'specializations' => $specializations,
        'sectors' => $sectors,
        'certification_level' => $mentor->getCertificationLevel(),
        'average_rating' => $mentor->getAverageRating(),
        'total_sessions' => (int) ($mentor->get('total_sessions')->value ?? 0),
        'is_available' => $mentor->isAvailable(),
        'profile_url' => Url::fromRoute('jaraba_mentoring.mentor_public_profile', ['mentor_profile' => $mentor->id()])->toString(),
      ];
    }

    $solicitarUrl = Url::fromRoute('jaraba_andalucia_ei.solicitar')->toString();

    return [
      '#theme' => 'programa_mentores',
      '#mentors' => $mentorData,
      '#total_mentors' => count($mentorData),
      '#participante' => $participante ? [
        'id' => $participante->id(),
        'nombre' => $participante->label(),
        'fase' => $participante->get('fase_actual')->value ?? 'acogida',
        'carril' => $participante->get('carril')->value ?? '',
      ] : NULL,
      '#solicitar_url' => $solicitarUrl,
      '#attached' => [
        'library' => [
          'jaraba_andalucia_ei/programa-mentores',
        ],
      ],
      '#cache' => [
        'contexts' => ['user', 'url.path'],
        'tags' => ['mentor_profile_list', 'config:jaraba_andalucia_ei.settings'],
        'max-age' => 900,
      ],
    ];
  }

  /**
   * Loads mentors linked to the participant's program group(s).
   *
   * @param mixed $participante
   *   The participant entity, or NULL for anonymous.
   *
   * @return array
   *   Array of MentorProfile entities.
   */
  protected function loadProgramMentors(mixed $participante): array {
    $storage = $this->entityTypeManager->getStorage('mentor_profile');

    // If we have a participante, filter by their group.
    // Otherwise show all active mentors (for landing/anonymous visitors).
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', 'active')
      ->condition('is_available', TRUE)
      ->sort('average_rating', 'DESC')
      ->sort('total_sessions', 'DESC')
      ->range(0, 50);

    if ($participante) {
      // Get the group from participante's tenant context.
      $tenantId = $participante->get('tenant_id')->target_id;
      if ($tenantId) {
        $query->condition('program_groups', $tenantId);
      }
    }

    $ids = $query->execute();
    if (empty($ids)) {
      return [];
    }

    return $storage->loadMultiple($ids);
  }

  /**
   * Gets the current user's participant entity.
   *
   * @return \Drupal\jaraba_andalucia_ei\Entity\ProgramaParticipanteEiInterface|null
   *   The participante or NULL.
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

      if (!empty($ids)) {
        return $storage->load(reset($ids));
      }
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error loading participante for user @uid: @msg', [
        '@uid' => $user->id(),
        '@msg' => $e->getMessage(),
      ]);
    }

    return NULL;
  }

}
