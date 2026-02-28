<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_templates\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_legal_templates\Service\DocumentGeneratorService;
use Drupal\jaraba_legal_templates\Service\TemplateManagerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API REST para plantillas juridicas y generacion de documentos.
 *
 * Estructura: API-NAMING-001 — POST store(), GET list/detail.
 * Logica: 5 endpoints para plantillas y documentos.
 */
class TemplatesApiController extends ControllerBase {

  public function __construct(
    protected readonly TemplateManagerService $templateManager,
    protected readonly DocumentGeneratorService $documentGenerator,
    EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_legal_templates.template_manager'),
      $container->get('jaraba_legal_templates.document_generator'),
      $container->get('entity_type.manager'),
      $container->get('logger.channel.jaraba_legal_templates'),
    );
  }

  /**
   * GET /api/v1/legal/templates
   */
  public function listTemplates(Request $request): JsonResponse {
    $type = $request->query->get('type', '');
    $limit = min((int) $request->query->get('limit', 25), 100);
    $offset = max((int) $request->query->get('offset', 0), 0);

    if ($type) {
      $items = $this->templateManager->listByType($type, $limit, $offset);
    }
    else {
      $items = $this->templateManager->getSystemTemplates();
    }

    return new JsonResponse(['success' => TRUE, 'data' => $items, 'meta' => ['total' => count($items), 'limit' => $limit, 'offset' => $offset]]);
  }

  /**
   * POST /api/v1/legal/templates
   */
  public function store(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE) ?? [];
    if (empty($data['name']) || empty($data['template_type']) || empty($data['template_body'])) {
      return // AUDIT-CONS-N08: Standardized JSON envelope.
        new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Campos requeridos: name, template_type, template_body.']], 422);
    }

    try {
      $storage = $this->entityTypeManager->getStorage('legal_template');
      $template = $storage->create([
        'uid' => $this->currentUser()->id(),
        'tenant_id' => $data['tenant_id'] ?? NULL,
        'name' => $data['name'],
        'template_type' => $data['template_type'],
        'practice_area_tid' => $data['practice_area_tid'] ?? NULL,
        'jurisdiction_tid' => $data['jurisdiction_tid'] ?? NULL,
        'template_body' => $data['template_body'],
        'merge_fields' => $data['merge_fields'] ?? [],
        'ai_instructions' => $data['ai_instructions'] ?? '',
        'is_system' => FALSE,
        'is_active' => TRUE,
      ]);
      $template->save();

      return new JsonResponse(['success' => TRUE, 'data' => $this->templateManager->serializeTemplate($template), 'meta' => ['timestamp' => time()]], 201);
    }
    catch (\Exception $e) {
      $this->logger->error('Error creating template: @msg', ['@msg' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.']], 500);
    }
  }

  /**
   * POST /api/v1/legal/documents/generate
   */
  public function generate(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE) ?? [];
    if (empty($data['template_id'])) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Campo requerido: template_id.']], 422);
    }

    $result = $this->documentGenerator->generateFromTemplate(
      (int) $data['template_id'],
      isset($data['case_id']) ? (int) $data['case_id'] : NULL,
      $data['merge_values'] ?? [],
    );

    if (isset($result['error'])) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => $result['error']]], 422);
    }

    return new JsonResponse(['success' => TRUE, 'data' => $result, 'meta' => ['timestamp' => time()]], 201);
  }

  /**
   * POST /api/v1/legal/documents/generate-ai
   */
  public function generateAi(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE) ?? [];
    if (empty($data['template_id']) || empty($data['case_id'])) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Campos requeridos: template_id, case_id.']], 422);
    }

    $result = $this->documentGenerator->generateWithAi(
      (int) $data['template_id'],
      (int) $data['case_id'],
    );

    if (isset($result['error'])) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => $result['error']]], 422);
    }

    return new JsonResponse(['success' => TRUE, 'data' => $result, 'meta' => ['timestamp' => time()]], 201);
  }

  /**
   * GET /api/v1/legal/documents/{uuid}
   */
  public function documentDetail(string $uuid): JsonResponse {
    try {
      $docs = $this->entityTypeManager->getStorage('generated_document')
        ->loadByProperties(['uuid' => $uuid]);
      $doc = reset($docs);

      if (!$doc) {
        return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Documento no encontrado.']], 404);
      }

      return new JsonResponse(['success' => TRUE, 'data' => $this->documentGenerator->serializeDocument($doc), 'meta' => ['timestamp' => time()]]);
    }
    catch (\Exception $e) {
      $this->logger->error('Document detail retrieval failed: @msg', ['@msg' => $e->getMessage()]);
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.']], 500);
    }
  }

}
