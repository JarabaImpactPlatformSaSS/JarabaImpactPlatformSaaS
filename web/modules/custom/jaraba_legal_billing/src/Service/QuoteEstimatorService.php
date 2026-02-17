<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_billing\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de estimacion automatica de presupuestos via IA.
 *
 * Estructura: Genera presupuestos automaticos usando Gemini 2.0 Flash.
 * Logica: Toma el triage de una consulta (InquiryTriage) y el perfil del
 *   proveedor, selecciona items del catalogo de servicios aplicables y
 *   asigna multiplicadores de complejidad basandose en los factores.
 *   Solo usa items del catalogo real del proveedor (no inventa precios).
 */
class QuoteEstimatorService {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Genera una estimacion de presupuesto desde triage + catalogo.
   *
   * @param array $triageData
   *   Datos del triage: area_legal, complexity, description, urgency, etc.
   * @param int $providerId
   *   ID del proveedor cuyo catalogo se usara.
   * @param int|null $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Lineas estimadas con catalog_item_id, multiplier, precio.
   */
  public function generateEstimate(array $triageData, int $providerId, ?int $tenantId = NULL): array {
    try {
      $config = $this->configFactory->get('jaraba_legal_billing.settings');
      if (!$config->get('ai_estimator_enabled')) {
        return ['error' => 'AI estimator is disabled.'];
      }

      // Cargar catalogo del proveedor.
      $catalog = $this->loadProviderCatalog($providerId, $tenantId);
      if (empty($catalog)) {
        return ['error' => 'No catalog items found for provider.'];
      }

      // Construir prompt para Gemini.
      $prompt = $this->buildPrompt($triageData, $catalog);

      // Llamar a Gemini 2.0 Flash API.
      // TODO: Integrar con el GeminiApiClient del kernel.
      // $response = $geminiClient->generate($prompt, [
      //   'model' => 'gemini-2.0-flash-001',
      //   'temperature' => 0.2,
      //   'response_mime_type' => 'application/json',
      //   'response_schema' => $this->getResponseSchema(),
      // ]);

      $this->logger->info('Quote estimation requested for provider @pid (placeholder).', [
        '@pid' => $providerId,
      ]);

      // Placeholder response structure.
      return [
        'status' => 'pending_integration',
        'catalog_count' => count($catalog),
        'triage_area' => $triageData['area_legal'] ?? 'unknown',
        'lines' => [],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Quote estimation error: @msg', ['@msg' => $e->getMessage()]);
      return ['error' => $e->getMessage()];
    }
  }

  /**
   * Carga el catalogo de servicios activos de un proveedor.
   */
  protected function loadProviderCatalog(int $providerId, ?int $tenantId): array {
    $storage = $this->entityTypeManager->getStorage('service_catalog_item');
    $query = $storage->getQuery()
      ->condition('provider_id', $providerId)
      ->condition('is_active', TRUE)
      ->accessCheck(FALSE)
      ->sort('display_order', 'ASC');

    if ($tenantId) {
      $query->condition('tenant_id', $tenantId);
    }

    $ids = $query->execute();
    $items = $storage->loadMultiple($ids);

    return array_map(function ($item) {
      return [
        'id' => (int) $item->id(),
        'name' => $item->get('name')->value,
        'short_description' => $item->get('short_description')->value ?? '',
        'pricing_model' => $item->get('pricing_model')->value,
        'base_price' => (float) ($item->get('base_price')->value ?? 0),
        'price_min' => (float) ($item->get('price_min')->value ?? 0),
        'price_max' => (float) ($item->get('price_max')->value ?? 0),
        'hourly_rate' => (float) ($item->get('hourly_rate')->value ?? 0),
        'estimated_hours_min' => (int) ($item->get('estimated_hours_min')->value ?? 0),
        'estimated_hours_max' => (int) ($item->get('estimated_hours_max')->value ?? 0),
        'complexity_factors' => $item->get('complexity_factors')->getValue()[0] ?? [],
      ];
    }, $items);
  }

  /**
   * Construye el prompt para el modelo de IA.
   */
  protected function buildPrompt(array $triageData, array $catalog): string {
    $catalogJson = json_encode($catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    return <<<PROMPT
Eres un asistente de presupuestos juridicos. Basandote UNICAMENTE en el catalogo
de servicios proporcionado, selecciona los items aplicables para la consulta del
cliente y asigna multiplicadores de complejidad apropiados.

REGLAS:
- Solo usa items del catalogo proporcionado. NO inventes servicios ni precios.
- Asigna complexity_multiplier entre 0.8 y 2.5 segun la complejidad.
- Responde en JSON con el schema: {lines: [{catalog_item_id, complexity_multiplier, notes}]}

CONSULTA DEL CLIENTE:
- Area legal: {$triageData['area_legal']}
- Descripcion: {$triageData['description']}
- Complejidad estimada: {$triageData['complexity']}
- Urgencia: {$triageData['urgency']}

CATALOGO DE SERVICIOS:
{$catalogJson}
PROMPT;
  }

}
