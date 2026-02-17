<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_templates\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de redaccion de documentos con IA.
 *
 * Estructura: Genera documentos juridicos completos usando Gemini 2.0 Flash.
 * Logica: 8 pasos — carga expediente, citas, jurisprudencia, plantilla,
 *   construye prompt, llama a IA, post-procesa, guarda como draft.
 */
class AiDraftingService {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Genera un documento juridico completo con IA.
   *
   * @param int $templateId
   *   ID de la plantilla a usar como base.
   * @param int $caseId
   *   ID del expediente con los datos del caso.
   *
   * @return array
   *   Documento generado serializado o error.
   */
  public function draftDocument(int $templateId, int $caseId): array {
    try {
      $config = $this->configFactory->get('jaraba_legal_templates.settings');
      if (!$config->get('ai_drafting_enabled')) {
        return ['error' => 'AI drafting is disabled.'];
      }

      // 1. Cargar plantilla.
      $template = $this->entityTypeManager->getStorage('legal_template')->load($templateId);
      if (!$template) {
        return ['error' => 'Template not found.'];
      }

      // 2. Cargar expediente.
      $case = $this->entityTypeManager->getStorage('client_case')->load($caseId);
      if (!$case) {
        return ['error' => 'Case not found.'];
      }

      // 3. Construir contexto del caso.
      $caseContext = $this->buildCaseContext($case);

      // 4. Buscar jurisprudencia relevante.
      $citations = $this->loadCitations($caseId);

      // 5. Construir prompt.
      $prompt = $this->buildPrompt($template, $caseContext, $citations);

      // 6. TODO: Llamar a Gemini 2.0 Flash via @ai.provider.
      // $aiModel = $config->get('default_ai_model') ?? 'gemini-2.0-flash';
      // $response = $aiProvider->generate($prompt, ['model' => $aiModel, 'temperature' => 0.3]);
      // $generatedHtml = $response['content'];

      $generatedHtml = sprintf(
        '<!-- AI Draft Placeholder --><p>Documento generado por IA para el expediente #%d usando plantilla "%s".</p><p>Pendiente de integracion con AI Provider.</p>',
        $caseId,
        $template->get('name')->value,
      );

      // 7. Crear GeneratedDocument.
      $docStorage = $this->entityTypeManager->getStorage('generated_document');
      $doc = $docStorage->create([
        'uid' => \Drupal::currentUser()->id(),
        'tenant_id' => $template->get('tenant_id')->target_id,
        'case_id' => $caseId,
        'template_id' => $templateId,
        'generated_by' => \Drupal::currentUser()->id(),
        'title' => sprintf('%s — IA — %s', $template->get('name')->value, date('d/m/Y')),
        'content_html' => $generatedHtml,
        'merge_data' => [$caseContext],
        'citations_used' => [$citations],
        'ai_model_version' => $config->get('default_ai_model') ?? 'gemini-2.0-flash',
        'generation_mode' => 'ai_full',
        'status' => 'draft',
      ]);
      $doc->save();

      $this->logger->info('AI document drafted for case @cid with template @tid.', [
        '@cid' => $caseId,
        '@tid' => $templateId,
      ]);

      return [
        'id' => (int) $doc->id(),
        'uuid' => $doc->uuid(),
        'title' => $doc->get('title')->value,
        'generation_mode' => 'ai_full',
        'status' => 'draft',
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('AI drafting error: @msg', ['@msg' => $e->getMessage()]);
      return ['error' => $e->getMessage()];
    }
  }

  /**
   * Construye contexto estructurado del expediente.
   */
  protected function buildCaseContext($case): array {
    return [
      'case_id' => (int) $case->id(),
      'title' => $case->get('title')->value ?? '',
      'status' => $case->get('status')->value ?? '',
    ];
  }

  /**
   * Carga citas juridicas vinculadas al expediente.
   */
  protected function loadCitations(int $caseId): array {
    try {
      if (!$this->entityTypeManager->hasDefinition('legal_citation')) {
        return [];
      }
      $storage = $this->entityTypeManager->getStorage('legal_citation');
      $ids = $storage->getQuery()
        ->condition('case_id', $caseId)
        ->accessCheck(FALSE)
        ->range(0, 20)
        ->execute();

      $citations = [];
      foreach ($storage->loadMultiple($ids) as $citation) {
        $citations[] = [
          'id' => (int) $citation->id(),
          'text' => $citation->get('citation_text')->value ?? '',
        ];
      }
      return $citations;
    }
    catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Construye el prompt para el modelo de IA.
   */
  protected function buildPrompt($template, array $caseContext, array $citations): string {
    $templateBody = $template->get('template_body')->value ?? '';
    $aiInstructions = $template->get('ai_instructions')->value ?? '';
    $citationsText = '';
    foreach ($citations as $c) {
      $citationsText .= "- " . $c['text'] . "\n";
    }

    return <<<PROMPT
Eres un abogado redactor experto. Genera un documento juridico profesional.

PLANTILLA BASE:
{$templateBody}

DATOS DEL CASO:
Expediente: {$caseContext['title']}
Estado: {$caseContext['status']}

JURISPRUDENCIA RELEVANTE:
{$citationsText}

INSTRUCCIONES ADICIONALES:
{$aiInstructions}

Genera el documento completo en HTML, manteniendo formato legal profesional.
Cita la jurisprudencia proporcionada donde sea relevante.
PROMPT;
  }

}
