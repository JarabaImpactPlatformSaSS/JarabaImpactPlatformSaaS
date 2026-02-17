<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_templates\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de generacion de documentos desde plantillas.
 *
 * Estructura: Genera documentos mediante merge-fields o con asistencia IA.
 * Logica: generateFromTemplate() = merge puro. generateWithAi() delega
 *   en AiDraftingService para generacion completa con jurisprudencia.
 */
class DocumentGeneratorService {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
    protected readonly TemplateManagerService $templateManager,
    protected readonly AiDraftingService $aiDrafter,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Genera un documento desde plantilla con merge-fields.
   */
  public function generateFromTemplate(int $templateId, ?int $caseId, array $mergeValues): array {
    try {
      $template = $this->entityTypeManager->getStorage('legal_template')->load($templateId);
      if (!$template) {
        return ['error' => 'Template not found.'];
      }

      $body = $template->get('template_body')->value ?? '';
      $rendered = $this->templateManager->renderTemplate($body, $mergeValues);

      // Crear GeneratedDocument.
      $docStorage = $this->entityTypeManager->getStorage('generated_document');
      $doc = $docStorage->create([
        'uid' => $this->currentUser->id(),
        'tenant_id' => $template->get('tenant_id')->target_id,
        'case_id' => $caseId,
        'template_id' => $templateId,
        'generated_by' => $this->currentUser->id(),
        'title' => sprintf('%s — %s', $template->get('name')->value, date('d/m/Y')),
        'content_html' => $rendered,
        'merge_data' => [$mergeValues],
        'generation_mode' => 'template_only',
        'status' => 'draft',
      ]);
      $doc->save();

      // Incrementar usage_count.
      $count = (int) ($template->get('usage_count')->value ?? 0);
      $template->set('usage_count', $count + 1);
      $template->save();

      return $this->serializeDocument($doc);
    }
    catch (\Exception $e) {
      $this->logger->error('Generate from template error: @msg', ['@msg' => $e->getMessage()]);
      return ['error' => $e->getMessage()];
    }
  }

  /**
   * Genera un documento con asistencia de IA.
   */
  public function generateWithAi(int $templateId, int $caseId): array {
    try {
      $result = $this->aiDrafter->draftDocument($templateId, $caseId);

      if (isset($result['error'])) {
        return $result;
      }

      // Incrementar usage_count.
      $template = $this->entityTypeManager->getStorage('legal_template')->load($templateId);
      if ($template) {
        $count = (int) ($template->get('usage_count')->value ?? 0);
        $template->set('usage_count', $count + 1);
        $template->save();
      }

      return $result;
    }
    catch (\Exception $e) {
      $this->logger->error('Generate with AI error: @msg', ['@msg' => $e->getMessage()]);
      return ['error' => $e->getMessage()];
    }
  }

  /**
   * Serializa un documento generado.
   */
  public function serializeDocument($doc): array {
    return [
      'id' => (int) $doc->id(),
      'uuid' => $doc->uuid(),
      'title' => $doc->get('title')->value ?? '',
      'template_id' => (int) ($doc->get('template_id')->target_id ?? 0),
      'case_id' => $doc->get('case_id')->target_id ? (int) $doc->get('case_id')->target_id : NULL,
      'generation_mode' => $doc->get('generation_mode')->value ?? 'template_only',
      'ai_model_version' => $doc->get('ai_model_version')->value ?? NULL,
      'status' => $doc->get('status')->value ?? 'draft',
      'vault_document_id' => $doc->get('vault_document_id')->target_id ? (int) $doc->get('vault_document_id')->target_id : NULL,
      'created' => $doc->get('created')->value ?? '',
    ];
  }

}
