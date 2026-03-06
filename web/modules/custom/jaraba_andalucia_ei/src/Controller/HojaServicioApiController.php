<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API controller for service sheet signature operations.
 *
 * Handles digital signature status updates for mentoring session
 * service sheets (hojas de servicio).
 */
class HojaServicioApiController extends ControllerBase {

  /**
   * Constructs a HojaServicioApiController.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    protected ?object $firmaDigitalService,
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
      $container->has('ecosistema_jaraba_core.firma_digital')
        ? $container->get('ecosistema_jaraba_core.firma_digital')
        : NULL,
      $container->get('logger.channel.jaraba_andalucia_ei'),
    );
  }

  /**
   * Signs a service sheet for the current user's role (participant or mentor).
   *
   * @param int $session_id
   *   The mentoring_session entity ID.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with signature status.
   */
  public function firmar(int $session_id, Request $request): JsonResponse {
    try {
      $session = $this->entityTypeManager->getStorage('mentoring_session')->load($session_id);
      if (!$session) {
        return new JsonResponse(['error' => 'Sesión no encontrada.'], 404);
      }

      if ($session->get('status')->value !== 'completed') {
        return new JsonResponse(['error' => 'La sesión debe estar completada para firmar.'], 400);
      }

      $currentUserId = (int) $this->currentUser()->id();
      $menteeId = (int) $session->get('mentee_id')->target_id;
      $mentorEntity = $session->get('mentor_id')->entity;
      $mentorOwnerId = $mentorEntity ? (int) $mentorEntity->getOwnerId() : 0;

      // Determine role.
      $role = NULL;
      if ($currentUserId === $menteeId) {
        $role = 'participante';
      }
      elseif ($currentUserId === $mentorOwnerId) {
        $role = 'orientador';
      }

      if (!$role) {
        return new JsonResponse(['error' => 'No tienes permiso para firmar esta hoja de servicio.'], 403);
      }

      $statusField = $role === 'participante' ? 'firma_participante_status' : 'firma_orientador_status';
      $currentStatus = $session->get($statusField)->value;

      if ($currentStatus === 'signed') {
        return new JsonResponse(['error' => 'Ya has firmado esta hoja de servicio.'], 400);
      }

      // If FirmaDigitalService is available, invoke digital signature.
      $signatureResult = NULL;
      $docId = $session->get('service_sheet_doc')->value;
      if ($this->firmaDigitalService && $docId) {
        try {
          $signatureResult = $this->firmaDigitalService->signPdf("expediente_documento:$docId", [
            'signer_uid' => $currentUserId,
            'role' => $role,
          ]);
        }
        catch (\Throwable $e) {
          $this->logger->warning('Digital signature failed for session @id, role @role: @msg', [
            '@id' => $session_id,
            '@role' => $role,
            '@msg' => $e->getMessage(),
          ]);
        }
      }

      // Update signature status.
      $session->set($statusField, 'signed');
      $session->save();

      $this->logger->info('Service sheet signed for session @id by @role (user @uid).', [
        '@id' => $session_id,
        '@role' => $role,
        '@uid' => $currentUserId,
      ]);

      return new JsonResponse([
        'success' => TRUE,
        'role' => $role,
        'status' => 'signed',
        'firma_participante' => $session->get('firma_participante_status')->value,
        'firma_orientador' => $session->get('firma_orientador_status')->value,
        'digital_signature' => $signatureResult ? TRUE : FALSE,
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->error('Error signing service sheet for session @id: @msg', [
        '@id' => $session_id,
        '@msg' => $e->getMessage(),
      ]);
      return new JsonResponse(['error' => 'Error interno al firmar.'], 500);
    }
  }

  /**
   * Gets the signature status of a service sheet.
   *
   * @param int $session_id
   *   The mentoring_session entity ID.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with current signature statuses.
   */
  public function estado(int $session_id): JsonResponse {
    try {
      $session = $this->entityTypeManager->getStorage('mentoring_session')->load($session_id);
      if (!$session) {
        return new JsonResponse(['error' => 'Sesión no encontrada.'], 404);
      }

      return new JsonResponse([
        'session_id' => (int) $session->id(),
        'status' => $session->get('status')->value,
        'firma_participante' => $session->get('firma_participante_status')->value ?? 'pending',
        'firma_orientador' => $session->get('firma_orientador_status')->value ?? 'pending',
        'service_sheet_doc' => $session->get('service_sheet_doc')->value,
        'both_signed' => ($session->get('firma_participante_status')->value === 'signed'
          && $session->get('firma_orientador_status')->value === 'signed'),
      ]);
    }
    catch (\Throwable $e) {
      return new JsonResponse(['error' => 'Error al consultar estado.'], 500);
    }
  }

}
