<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_billing\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use GuzzleHttp\ClientInterface;
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
    protected readonly ClientInterface $httpClient,
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

      // AUDIT-TODO-RESOLVED: Real Gemini API integration for quote estimation.
      $geminiApiKey = $config->get('gemini_api_key')
        ?: getenv('GEMINI_API_KEY');

      if (empty($geminiApiKey)) {
        $this->logger->warning('Gemini API key not configured for quote estimation.');
        return ['error' => 'AI estimator API key not configured.'];
      }

      $geminiModel = $config->get('gemini_model') ?: 'gemini-2.0-flash-001';
      $apiUrl = sprintf(
        'https://generativelanguage.googleapis.com/v1/models/%s:generateContent?key=%s',
        $geminiModel,
        $geminiApiKey
      );

      $requestPayload = [
        'contents' => [
          [
            'parts' => [
              ['text' => $prompt],
            ],
          ],
        ],
        'generationConfig' => [
          'temperature' => 0.2,
          'maxOutputTokens' => 2048,
          'responseMimeType' => 'application/json',
        ],
      ];

      $geminiResponse = $this->httpClient->request('POST', $apiUrl, [
        'headers' => [
          'Content-Type' => 'application/json',
        ],
        'json' => $requestPayload,
        'timeout' => 60,
      ]);

      $geminiData = json_decode((string) $geminiResponse->getBody(), TRUE);
      $generatedText = $geminiData['candidates'][0]['content']['parts'][0]['text'] ?? '';

      if (empty($generatedText)) {
        $this->logger->warning('Empty response from Gemini for provider @pid.', [
          '@pid' => $providerId,
        ]);
        return ['error' => 'AI returned empty estimation.'];
      }

      // Parse the JSON response from the LLM.
      $estimationData = json_decode($generatedText, TRUE);
      if (json_last_error() !== JSON_ERROR_NONE || empty($estimationData['lines'])) {
        $this->logger->warning('Invalid JSON from Gemini for provider @pid: @raw', [
          '@pid' => $providerId,
          '@raw' => mb_substr($generatedText, 0, 500),
        ]);
        return ['error' => 'AI returned invalid estimation format.'];
      }

      // Build quote lines from catalog data and AI multipliers.
      $quoteLines = [];
      $catalogById = array_column($catalog, NULL, 'id');

      foreach ($estimationData['lines'] as $line) {
        $catalogItemId = $line['catalog_item_id'] ?? NULL;
        $multiplier = (float) ($line['complexity_multiplier'] ?? 1.0);
        $multiplier = max(0.8, min(2.5, $multiplier));

        if ($catalogItemId && isset($catalogById[$catalogItemId])) {
          $item = $catalogById[$catalogItemId];
          $basePrice = $item['base_price'] > 0 ? $item['base_price'] : $item['price_min'];
          $estimatedPrice = round($basePrice * $multiplier, 2);

          $quoteLines[] = [
            'catalog_item_id' => $catalogItemId,
            'name' => $item['name'],
            'complexity_multiplier' => $multiplier,
            'base_price' => $basePrice,
            'estimated_price' => $estimatedPrice,
            'pricing_model' => $item['pricing_model'],
            'notes' => $line['notes'] ?? '',
          ];
        }
      }

      $this->logger->info('Quote estimation completed for provider @pid: @count lines.', [
        '@pid' => $providerId,
        '@count' => count($quoteLines),
      ]);

      return [
        'status' => 'estimated',
        'catalog_count' => count($catalog),
        'triage_area' => $triageData['area_legal'] ?? 'unknown',
        'lines' => $quoteLines,
        'total_estimated' => array_sum(array_column($quoteLines, 'estimated_price')),
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
