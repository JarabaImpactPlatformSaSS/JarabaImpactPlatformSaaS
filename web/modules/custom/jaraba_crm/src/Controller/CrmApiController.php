<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jaraba_crm\Service\ActivityService;
use Drupal\jaraba_crm\Service\CompanyService;
use Drupal\jaraba_crm\Service\ContactService;
use Drupal\jaraba_crm\Service\CrmForecastingService;
use Drupal\jaraba_crm\Service\OpportunityService;
use Drupal\jaraba_crm\Service\PipelineStageService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * REST API Controller para CRM — spec 150 §4.
 *
 * 22 endpoints para gestion de empresas, contactos, oportunidades,
 * actividades, etapas de pipeline y forecasting.
 */
class CrmApiController extends ControllerBase implements ContainerInjectionInterface {

  public function __construct(
    protected CompanyService $companyService,
    protected ContactService $contactService,
    protected OpportunityService $opportunityService,
    protected ActivityService $activityService,
    protected PipelineStageService $pipelineStageService,
    protected CrmForecastingService $forecastingService,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_crm.company'),
      $container->get('jaraba_crm.contact'),
      $container->get('jaraba_crm.opportunity'),
      $container->get('jaraba_crm.activity'),
      $container->get('jaraba_crm.pipeline_stage'),
      $container->get('jaraba_crm.forecasting'),
      $container->get('logger.channel.jaraba_crm'),
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

  // ===== EMPRESAS (Companies) =====

  /**
   * GET /api/v1/crm/companies — Listar empresas del tenant.
   */
  public function listCompanies(): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $entities = $this->companyService->getByTenant($tenantId);
      $companies = [];
      foreach ($entities as $entity) {
        $companies[] = [
          'id' => (int) $entity->id(),
          'name' => $entity->label(),
          'industry' => $entity->get('industry')->value ?? NULL,
          'size' => $entity->get('size')->value ?? NULL,
          'website' => $entity->get('website')->value ?? NULL,
          'phone' => $entity->get('phone')->value ?? NULL,
          'created' => $entity->get('created')->value,
        ];
      }

      return new JsonResponse(['success' => TRUE, 'data' => $companies]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error listando empresas: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Internal error'], 500);
    }
  }

  /**
   * GET /api/v1/crm/companies/{id} — Detalle de empresa.
   */
  public function getCompany(string $id): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $entity = $this->entityTypeManager()->getStorage('crm_company')->load($id);
      if (!$entity || (int) ($entity->get('tenant_id')->target_id ?? 0) !== $tenantId) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Company not found'], 404);
      }

      $data = [
        'id' => (int) $entity->id(),
        'name' => $entity->label(),
        'industry' => $entity->get('industry')->value ?? NULL,
        'size' => $entity->get('size')->value ?? NULL,
        'website' => $entity->get('website')->value ?? NULL,
        'phone' => $entity->get('phone')->value ?? NULL,
        'email' => $entity->get('email')->value ?? NULL,
        'address' => $entity->get('address')->value ?? NULL,
        'notes' => $entity->get('notes')->value ?? NULL,
        'created' => $entity->get('created')->value,
        'changed' => $entity->get('changed')->value,
      ];

      return new JsonResponse(['success' => TRUE, 'data' => $data]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo empresa: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Internal error'], 500);
    }
  }

  /**
   * POST /api/v1/crm/companies — Crear empresa.
   */
  public function createCompany(Request $request): JsonResponse {
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
      $entity = $this->companyService->create([
        'name' => $name,
        'tenant_id' => $tenantId,
        'industry' => $body['industry'] ?? NULL,
        'size' => $body['size'] ?? NULL,
        'website' => $body['website'] ?? NULL,
        'phone' => $body['phone'] ?? NULL,
        'email' => $body['email'] ?? NULL,
        'address' => $body['address'] ?? NULL,
        'notes' => $body['notes'] ?? NULL,
      ]);

      return new JsonResponse(['success' => TRUE, 'data' => [
        'id' => (int) $entity->id(),
        'name' => $entity->label(),
      ]]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error creando empresa: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
    }
  }

  /**
   * PUT /api/v1/crm/companies/{id} — Actualizar empresa.
   */
  public function updateCompany(string $id, Request $request): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    $body = json_decode($request->getContent(), TRUE) ?? [];

    try {
      $entity = $this->entityTypeManager()->getStorage('crm_company')->load($id);
      if (!$entity || (int) ($entity->get('tenant_id')->target_id ?? 0) !== $tenantId) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Company not found'], 404);
      }

      $allowedFields = ['name', 'industry', 'size', 'website', 'phone', 'email', 'address', 'notes'];
      foreach ($allowedFields as $field) {
        if (isset($body[$field])) {
          $entity->set($field, $body[$field]);
        }
      }
      $entity->save();

      return new JsonResponse(['success' => TRUE, 'data' => ['updated' => TRUE]]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error actualizando empresa: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
    }
  }

  /**
   * DELETE /api/v1/crm/companies/{id} — Eliminar empresa.
   */
  public function deleteCompany(string $id): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $entity = $this->entityTypeManager()->getStorage('crm_company')->load($id);
      if (!$entity || (int) ($entity->get('tenant_id')->target_id ?? 0) !== $tenantId) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Company not found'], 404);
      }

      $entity->delete();
      return new JsonResponse(['success' => TRUE, 'data' => ['deleted' => TRUE]]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error eliminando empresa: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
    }
  }

  // ===== CONTACTOS =====

  /**
   * GET /api/v1/crm/contacts — Listar contactos del tenant.
   */
  public function listContacts(): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $entities = $this->contactService->getByTenant($tenantId);
      $contacts = [];
      foreach ($entities as $entity) {
        $contacts[] = [
          'id' => (int) $entity->id(),
          'full_name' => $entity->label(),
          'email' => $entity->get('email')->value ?? NULL,
          'phone' => $entity->get('phone')->value ?? NULL,
          'company_id' => $entity->get('company_id')->target_id ?? NULL,
          'engagement_score' => (int) ($entity->get('engagement_score')->value ?? 0),
          'source' => $entity->get('source')->value ?? NULL,
          'created' => $entity->get('created')->value,
        ];
      }

      return new JsonResponse(['success' => TRUE, 'data' => $contacts]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error listando contactos: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Internal error'], 500);
    }
  }

  /**
   * GET /api/v1/crm/contacts/{id} — Detalle de contacto.
   */
  public function getContact(string $id): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $entity = $this->entityTypeManager()->getStorage('crm_contact')->load($id);
      if (!$entity || (int) ($entity->get('tenant_id')->target_id ?? 0) !== $tenantId) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Contact not found'], 404);
      }

      $data = [
        'id' => (int) $entity->id(),
        'full_name' => $entity->label(),
        'first_name' => $entity->get('first_name')->value ?? NULL,
        'last_name' => $entity->get('last_name')->value ?? NULL,
        'email' => $entity->get('email')->value ?? NULL,
        'phone' => $entity->get('phone')->value ?? NULL,
        'company_id' => $entity->get('company_id')->target_id ?? NULL,
        'position' => $entity->get('position')->value ?? NULL,
        'engagement_score' => (int) ($entity->get('engagement_score')->value ?? 0),
        'source' => $entity->get('source')->value ?? NULL,
        'notes' => $entity->get('notes')->value ?? NULL,
        'created' => $entity->get('created')->value,
        'changed' => $entity->get('changed')->value,
      ];

      return new JsonResponse(['success' => TRUE, 'data' => $data]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo contacto: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Internal error'], 500);
    }
  }

  /**
   * POST /api/v1/crm/contacts — Crear contacto.
   */
  public function createContact(Request $request): JsonResponse {
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
      $entity = $this->contactService->create([
        'first_name' => $body['first_name'] ?? '',
        'last_name' => $body['last_name'] ?? '',
        'email' => $email,
        'phone' => $body['phone'] ?? NULL,
        'tenant_id' => $tenantId,
        'company_id' => $body['company_id'] ?? NULL,
        'position' => $body['position'] ?? NULL,
        'source' => $body['source'] ?? 'api',
        'notes' => $body['notes'] ?? NULL,
      ]);

      return new JsonResponse(['success' => TRUE, 'data' => [
        'id' => (int) $entity->id(),
        'full_name' => $entity->label(),
      ]]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error creando contacto: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
    }
  }

  /**
   * PUT /api/v1/crm/contacts/{id} — Actualizar contacto.
   */
  public function updateContact(string $id, Request $request): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    $body = json_decode($request->getContent(), TRUE) ?? [];

    try {
      $entity = $this->entityTypeManager()->getStorage('crm_contact')->load($id);
      if (!$entity || (int) ($entity->get('tenant_id')->target_id ?? 0) !== $tenantId) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Contact not found'], 404);
      }

      $allowedFields = ['first_name', 'last_name', 'email', 'phone', 'company_id', 'position', 'source', 'notes'];
      foreach ($allowedFields as $field) {
        if (isset($body[$field])) {
          $entity->set($field, $body[$field]);
        }
      }
      $entity->save();

      return new JsonResponse(['success' => TRUE, 'data' => ['updated' => TRUE]]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error actualizando contacto: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
    }
  }

  /**
   * DELETE /api/v1/crm/contacts/{id} — Eliminar contacto.
   */
  public function deleteContact(string $id): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $entity = $this->entityTypeManager()->getStorage('crm_contact')->load($id);
      if (!$entity || (int) ($entity->get('tenant_id')->target_id ?? 0) !== $tenantId) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Contact not found'], 404);
      }

      $entity->delete();
      return new JsonResponse(['success' => TRUE, 'data' => ['deleted' => TRUE]]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error eliminando contacto: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
    }
  }

  // ===== OPORTUNIDADES =====

  /**
   * GET /api/v1/crm/opportunities — Listar oportunidades.
   */
  public function listOpportunities(): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $pipeline = $this->opportunityService->getByStage($tenantId);
      $opportunities = [];

      foreach ($pipeline as $stage => $stageOpps) {
        foreach ($stageOpps as $opp) {
          $opportunities[] = [
            'id' => (int) $opp->id(),
            'title' => $opp->label(),
            'value' => (float) ($opp->get('value')->value ?? 0),
            'stage' => $stage,
            'probability' => (int) ($opp->get('probability')->value ?? 50),
            'contact_id' => $opp->get('contact_id')->target_id ?? NULL,
            'expected_close' => $opp->get('expected_close')->value ?? NULL,
            'created' => $opp->get('created')->value,
          ];
        }
      }

      return new JsonResponse(['success' => TRUE, 'data' => $opportunities]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error listando oportunidades: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Internal error'], 500);
    }
  }

  /**
   * GET /api/v1/crm/opportunities/{id} — Detalle de oportunidad.
   */
  public function getOpportunity(string $id): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $entity = $this->opportunityService->load((int) $id);
      if (!$entity || (int) ($entity->get('tenant_id')->target_id ?? 0) !== $tenantId) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Opportunity not found'], 404);
      }

      $data = [
        'id' => (int) $entity->id(),
        'title' => $entity->label(),
        'value' => (float) ($entity->get('value')->value ?? 0),
        'stage' => $entity->get('stage')->value,
        'probability' => (int) ($entity->get('probability')->value ?? 50),
        'contact_id' => $entity->get('contact_id')->target_id ?? NULL,
        'expected_close' => $entity->get('expected_close')->value ?? NULL,
        'notes' => $entity->get('notes')->value ?? NULL,
        'created' => $entity->get('created')->value,
        'changed' => $entity->get('changed')->value,
      ];

      return new JsonResponse(['success' => TRUE, 'data' => $data]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo oportunidad: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Internal error'], 500);
    }
  }

  /**
   * POST /api/v1/crm/opportunities — Crear oportunidad.
   */
  public function createOpportunity(Request $request): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    $body = json_decode($request->getContent(), TRUE) ?? [];
    $title = $body['title'] ?? NULL;

    if (!$title) {
      return new JsonResponse(['success' => FALSE, 'error' => 'title is required'], 400);
    }

    try {
      $entity = $this->opportunityService->create([
        'title' => $title,
        'tenant_id' => $tenantId,
        'contact_id' => $body['contact_id'] ?? NULL,
        'value' => $body['value'] ?? '0',
        'stage' => $body['stage'] ?? 'lead',
        'probability' => $body['probability'] ?? 50,
        'expected_close' => $body['expected_close'] ?? NULL,
        'notes' => $body['notes'] ?? NULL,
      ]);

      return new JsonResponse(['success' => TRUE, 'data' => [
        'id' => (int) $entity->id(),
        'title' => $entity->label(),
      ]]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error creando oportunidad: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
    }
  }

  /**
   * PUT /api/v1/crm/opportunities/{id} — Actualizar oportunidad.
   */
  public function updateOpportunity(string $id, Request $request): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    $body = json_decode($request->getContent(), TRUE) ?? [];

    try {
      $entity = $this->opportunityService->load((int) $id);
      if (!$entity || (int) ($entity->get('tenant_id')->target_id ?? 0) !== $tenantId) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Opportunity not found'], 404);
      }

      $allowedFields = ['title', 'contact_id', 'value', 'stage', 'probability', 'expected_close', 'notes'];
      foreach ($allowedFields as $field) {
        if (isset($body[$field])) {
          $entity->set($field, $body[$field]);
        }
      }
      $entity->save();

      return new JsonResponse(['success' => TRUE, 'data' => ['updated' => TRUE]]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error actualizando oportunidad: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
    }
  }

  /**
   * DELETE /api/v1/crm/opportunities/{id} — Eliminar oportunidad.
   */
  public function deleteOpportunity(string $id): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $entity = $this->opportunityService->load((int) $id);
      if (!$entity || (int) ($entity->get('tenant_id')->target_id ?? 0) !== $tenantId) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Opportunity not found'], 404);
      }

      $entity->delete();
      return new JsonResponse(['success' => TRUE, 'data' => ['deleted' => TRUE]]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error eliminando oportunidad: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
    }
  }

  /**
   * POST /api/v1/crm/opportunities/{id}/stage — Cambiar etapa.
   */
  public function changeStage(string $id, Request $request): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    $body = json_decode($request->getContent(), TRUE) ?? [];
    $newStage = $body['stage'] ?? NULL;

    if (!$newStage) {
      return new JsonResponse(['success' => FALSE, 'error' => 'stage is required'], 400);
    }

    try {
      $moved = $this->opportunityService->moveToStage((int) $id, $newStage);
      if (!$moved) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Could not move opportunity'], 400);
      }

      return new JsonResponse(['success' => TRUE, 'data' => ['stage' => $newStage]]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error cambiando etapa: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
    }
  }

  /**
   * POST /api/v1/crm/opportunities/{id}/won — Marcar como ganada.
   */
  public function markWon(string $id): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $moved = $this->opportunityService->moveToStage((int) $id, 'won');
      if (!$moved) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Could not mark as won'], 400);
      }

      return new JsonResponse(['success' => TRUE, 'data' => ['stage' => 'won']]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error marcando ganada: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
    }
  }

  /**
   * POST /api/v1/crm/opportunities/{id}/lost — Marcar como perdida.
   */
  public function markLost(string $id, Request $request): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $moved = $this->opportunityService->moveToStage((int) $id, 'lost');
      if (!$moved) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Could not mark as lost'], 400);
      }

      $body = json_decode($request->getContent(), TRUE) ?? [];
      if (isset($body['reason'])) {
        $entity = $this->opportunityService->load((int) $id);
        if ($entity) {
          $currentNotes = $entity->get('notes')->value ?? '';
          $entity->set('notes', $currentNotes . "\n[Razon de perdida]: " . $body['reason']);
          $entity->save();
        }
      }

      return new JsonResponse(['success' => TRUE, 'data' => ['stage' => 'lost']]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error marcando perdida: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
    }
  }

  // ===== PIPELINE STAGES =====

  /**
   * GET /api/v1/crm/pipeline-stages — Listar etapas del pipeline.
   */
  public function listPipelineStages(): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $stages = $this->pipelineStageService->getStagesForTenant($tenantId);
      $data = [];
      foreach ($stages as $stage) {
        $data[] = [
          'id' => (int) $stage->id(),
          'name' => $stage->get('name')->value,
          'machine_name' => $stage->get('machine_name')->value,
          'color' => $stage->get('color')->value,
          'position' => (int) $stage->get('position')->value,
          'default_probability' => (float) $stage->get('default_probability')->value,
          'is_won_stage' => (bool) $stage->get('is_won_stage')->value,
          'is_lost_stage' => (bool) $stage->get('is_lost_stage')->value,
          'rotting_days' => (int) $stage->get('rotting_days')->value,
        ];
      }

      return new JsonResponse(['success' => TRUE, 'data' => $data]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error listando etapas: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Internal error'], 500);
    }
  }

  /**
   * POST /api/v1/crm/pipeline-stages/reorder — Reordenar etapas.
   */
  public function reorderPipelineStages(Request $request): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    $body = json_decode($request->getContent(), TRUE) ?? [];
    $order = $body['order'] ?? NULL;

    if (!$order || !is_array($order)) {
      return new JsonResponse(['success' => FALSE, 'error' => 'order array is required'], 400);
    }

    try {
      $result = $this->pipelineStageService->reorderStages($tenantId, $order);
      return new JsonResponse(['success' => $result, 'data' => ['reordered' => $result]]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error reordenando etapas: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
    }
  }

  /**
   * POST /api/v1/crm/pipeline-stages/defaults — Crear etapas por defecto.
   */
  public function createDefaultStages(): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $existing = $this->pipelineStageService->count($tenantId);
      if ($existing > 0) {
        return new JsonResponse(['success' => FALSE, 'error' => 'Stages already exist for this tenant'], 400);
      }

      $stages = $this->pipelineStageService->createDefaultStages($tenantId);
      return new JsonResponse(['success' => TRUE, 'data' => ['created' => count($stages)]]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error creando etapas por defecto: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
    }
  }

  // ===== ACTIVIDADES =====

  /**
   * GET /api/v1/crm/activities — Listar actividades recientes.
   */
  public function listActivities(): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $entities = $this->activityService->getRecent($tenantId, 50);
      $activities = [];
      foreach ($entities as $entity) {
        $activities[] = [
          'id' => (int) $entity->id(),
          'type' => $entity->get('activity_type')->value ?? NULL,
          'subject' => $entity->label(),
          'contact_id' => $entity->get('contact_id')->target_id ?? NULL,
          'notes' => $entity->get('notes')->value ?? NULL,
          'created' => $entity->get('created')->value,
        ];
      }

      return new JsonResponse(['success' => TRUE, 'data' => $activities]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error listando actividades: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Internal error'], 500);
    }
  }

  /**
   * POST /api/v1/crm/activities — Crear actividad.
   */
  public function createActivity(Request $request): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    $body = json_decode($request->getContent(), TRUE) ?? [];
    $subject = $body['subject'] ?? NULL;

    if (!$subject) {
      return new JsonResponse(['success' => FALSE, 'error' => 'subject is required'], 400);
    }

    try {
      $entity = $this->activityService->create([
        'subject' => $subject,
        'tenant_id' => $tenantId,
        'activity_type' => $body['type'] ?? 'note',
        'contact_id' => $body['contact_id'] ?? NULL,
        'notes' => $body['notes'] ?? NULL,
      ]);

      return new JsonResponse(['success' => TRUE, 'data' => [
        'id' => (int) $entity->id(),
        'subject' => $entity->label(),
      ]]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error creando actividad: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => $e->getMessage()], 500);
    }
  }

  // ===== FORECASTING =====

  /**
   * GET /api/v1/crm/forecast — Forecast del pipeline.
   */
  public function getForecast(): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse(['success' => FALSE, 'error' => 'Tenant not found'], 403);
    }

    try {
      $forecast = $this->forecastingService->getForecast($tenantId);
      $forecast['win_rate'] = $this->forecastingService->getWinRate($tenantId);
      $forecast['avg_deal_size'] = $this->forecastingService->getAvgDealSize($tenantId);
      $forecast['sales_cycle_days'] = $this->forecastingService->getSalesCycleAvg($tenantId);

      return new JsonResponse(['success' => TRUE, 'data' => $forecast]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo forecast: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => 'Internal error'], 500);
    }
  }

}
