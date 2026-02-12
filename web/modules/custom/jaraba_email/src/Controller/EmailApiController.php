<?php

declare(strict_types=1);

namespace Drupal\jaraba_email\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jaraba_email\Service\CampaignService;
use Drupal\jaraba_email\Service\EmailListService;
use Drupal\jaraba_email\Service\SequenceManagerService;
use Drupal\jaraba_email\Service\SubscriberService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * REST API Controller para Email Marketing — spec 151 §4.
 *
 * 17 endpoints para gestion de listas, suscriptores, campanas,
 * plantillas y secuencias.
 */
class EmailApiController extends ControllerBase implements ContainerInjectionInterface {

  public function __construct(
    protected EmailListService $listService,
    protected SubscriberService $subscriberService,
    protected CampaignService $campaignService,
    protected SequenceManagerService $sequenceManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_email.list_service'),
      $container->get('jaraba_email.subscriber_service'),
      $container->get('jaraba_email.campaign_service'),
      $container->get('jaraba_email.sequence_manager'),
      $container->get('logger.channel.jaraba_email'),
    );
  }

  /**
   * Obtiene el tenant_id del usuario actual.
   */
  protected function getCurrentTenantId(): ?int {
    $user = $this->currentUser();
    if (!$user || $user->isAnonymous()) {
      return NULL;
    }
    $userEntity = $this->entityTypeManager()->getStorage('user')->load($user->id());
    if ($userEntity && $userEntity->hasField('field_tenant') && !$userEntity->get('field_tenant')->isEmpty()) {
      return (int) $userEntity->get('field_tenant')->target_id;
    }
    return NULL;
  }

  // ===== LISTAS =====

  /**
   * GET /api/v1/email/lists — Listar listas del tenant.
   */
  public function listLists(): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $entities = $this->listService->getByTenant($tenantId);
      $lists = [];
      foreach ($entities as $entity) {
        $lists[] = [
          'id' => (int) $entity->id(),
          'name' => $entity->label(),
          'type' => $entity->get('type')->value ?? 'static',
          'subscriber_count' => (int) ($entity->get('subscriber_count')->value ?? 0),
          'created' => $entity->get('created')->value,
        ];
      }

      return new JsonResponse(['success' => TRUE, 'data' => $lists]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error listando listas: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Internal error'], 500);
    }
  }

  /**
   * POST /api/v1/email/lists — Crear lista.
   */
  public function createList(Request $request): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    $body = json_decode($request->getContent(), TRUE) ?? [];
    $name = $body['name'] ?? NULL;

    if (!$name) {
      return new JsonResponse(['success' => FALSE, 'error' => 'name is required'], 400);
    }

    try {
      $entity = $this->listService->create([
        'name' => $name,
        'tenant_id' => $tenantId,
        'type' => $body['type'] ?? 'static',
        'description' => $body['description'] ?? NULL,
      ]);

      return new JsonResponse(['success' => TRUE, 'data' => [
        'id' => (int) $entity->id(),
        'name' => $entity->label(),
      ]]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error creando lista: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
    }
  }

  // ===== SUSCRIPTORES =====

  /**
   * GET /api/v1/email/subscribers — Listar suscriptores.
   */
  public function listSubscribers(Request $request): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $storage = $this->entityTypeManager()->getStorage('email_subscriber');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('tenant_id', $tenantId)
        ->sort('created', 'DESC')
        ->range(0, 100);

      $ids = $query->execute();
      $entities = $ids ? $storage->loadMultiple($ids) : [];

      $subscribers = [];
      foreach ($entities as $entity) {
        $subscribers[] = [
          'id' => (int) $entity->id(),
          'email' => $entity->get('email')->value ?? '',
          'status' => $entity->get('status')->value ?? 'pending',
          'engagement_score' => (int) ($entity->get('engagement_score')->value ?? 0),
          'created' => $entity->get('created')->value,
        ];
      }

      return new JsonResponse(['success' => TRUE, 'data' => $subscribers]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error listando suscriptores: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Internal error'], 500);
    }
  }

  /**
   * POST /api/v1/email/subscribers — Crear/suscribir.
   */
  public function createSubscriber(Request $request): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    $body = json_decode($request->getContent(), TRUE) ?? [];
    $email = $body['email'] ?? NULL;

    if (!$email) {
      return new JsonResponse(['success' => FALSE, 'error' => 'email is required'], 400);
    }

    try {
      $listId = $body['list_id'] ?? NULL;
      $result = $this->subscriberService->subscribe($email, $tenantId, [
        'list_id' => $listId,
        'source' => $body['source'] ?? 'api',
        'first_name' => $body['first_name'] ?? NULL,
        'last_name' => $body['last_name'] ?? NULL,
      ]);

      return new JsonResponse(['success' => TRUE, 'data' => $result]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error suscribiendo: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
    }
  }

  /**
   * DELETE /api/v1/email/subscribers/{id} — Desuscribir.
   */
  public function unsubscribe(string $id): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $entity = $this->entityTypeManager()->getStorage('email_subscriber')->load($id);
      if (!$entity || (int) ($entity->get('tenant_id')->target_id ?? 0) !== $tenantId) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Subscriber not found'], 404);
      }

      $this->subscriberService->unsubscribe($entity->get('email')->value, $tenantId);
      return new JsonResponse(['success' => TRUE, 'data' => ['unsubscribed' => TRUE]]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error desuscribiendo: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
    }
  }

  // ===== CAMPANAS =====

  /**
   * GET /api/v1/email/campaigns — Listar campanas.
   */
  public function listCampaigns(): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $storage = $this->entityTypeManager()->getStorage('email_campaign');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('tenant_id', $tenantId)
        ->sort('created', 'DESC')
        ->range(0, 50);

      $ids = $query->execute();
      $entities = $ids ? $storage->loadMultiple($ids) : [];

      $campaigns = [];
      foreach ($entities as $entity) {
        $campaigns[] = [
          'id' => (int) $entity->id(),
          'name' => $entity->label(),
          'status' => $entity->get('status')->value ?? 'draft',
          'total_sent' => (int) ($entity->get('total_sent')->value ?? 0),
          'total_opens' => (int) ($entity->get('total_opens')->value ?? 0),
          'total_clicks' => (int) ($entity->get('total_clicks')->value ?? 0),
          'created' => $entity->get('created')->value,
        ];
      }

      return new JsonResponse(['success' => TRUE, 'data' => $campaigns]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error listando campanas: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Internal error'], 500);
    }
  }

  /**
   * POST /api/v1/email/campaigns/{id}/send — Enviar campana.
   */
  public function sendCampaign(string $id): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $entity = $this->entityTypeManager()->getStorage('email_campaign')->load($id);
      if (!$entity || (int) ($entity->get('tenant_id')->target_id ?? 0) !== $tenantId) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Campaign not found'], 404);
      }

      $result = $this->campaignService->sendCampaign($entity);
      return new JsonResponse(['success' => TRUE, 'data' => $result]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error enviando campana: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
    }
  }

  /**
   * GET /api/v1/email/campaigns/{id}/stats — Estadisticas de campana.
   */
  public function getCampaignStats(string $id): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $entity = $this->entityTypeManager()->getStorage('email_campaign')->load($id);
      if (!$entity || (int) ($entity->get('tenant_id')->target_id ?? 0) !== $tenantId) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Campaign not found'], 404);
      }

      $stats = $this->campaignService->getCampaignStatistics($entity);
      return new JsonResponse(['success' => TRUE, 'data' => $stats]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo stats: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Internal error'], 500);
    }
  }

  // ===== SECUENCIAS =====

  /**
   * GET /api/v1/email/sequences — Listar secuencias.
   */
  public function listSequences(): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $storage = $this->entityTypeManager()->getStorage('email_sequence');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('tenant_id', $tenantId)
        ->sort('created', 'DESC');

      $ids = $query->execute();
      $entities = $ids ? $storage->loadMultiple($ids) : [];

      $sequences = [];
      foreach ($entities as $entity) {
        $sequences[] = [
          'id' => (int) $entity->id(),
          'name' => $entity->label(),
          'category' => $entity->get('category')->value ?? NULL,
          'is_active' => (bool) $entity->get('is_active')->value,
          'total_enrolled' => (int) ($entity->get('total_enrolled')->value ?? 0),
          'currently_enrolled' => (int) ($entity->get('currently_enrolled')->value ?? 0),
          'completed' => (int) ($entity->get('completed')->value ?? 0),
        ];
      }

      return new JsonResponse(['success' => TRUE, 'data' => $sequences]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error listando secuencias: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Internal error'], 500);
    }
  }

  /**
   * POST /api/v1/email/sequences/{id}/enroll — Inscribir suscriptor.
   */
  public function enrollInSequence(string $id, Request $request): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    $body = json_decode($request->getContent(), TRUE) ?? [];
    $subscriberId = $body['subscriber_id'] ?? NULL;

    if (!$subscriberId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'subscriber_id is required'], 400);
    }

    try {
      $result = $this->sequenceManager->enrollSubscriber((int) $subscriberId, (int) $id);
      return new JsonResponse(['success' => $result, 'data' => ['enrolled' => $result]]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error inscribiendo en secuencia: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
    }
  }

  // ===== PLANTILLAS =====

  /**
   * GET /api/v1/email/templates — Listar plantillas.
   */
  public function listTemplates(): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $storage = $this->entityTypeManager()->getStorage('email_template');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('tenant_id', $tenantId)
        ->sort('created', 'DESC');

      $ids = $query->execute();
      $entities = $ids ? $storage->loadMultiple($ids) : [];

      $templates = [];
      foreach ($entities as $entity) {
        $templates[] = [
          'id' => (int) $entity->id(),
          'name' => $entity->label(),
          'category' => $entity->get('category')->value ?? NULL,
          'vertical' => $entity->get('vertical')->value ?? NULL,
          'created' => $entity->get('created')->value,
        ];
      }

      return new JsonResponse(['success' => TRUE, 'data' => $templates]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error listando plantillas: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Internal error'], 500);
    }
  }

}
