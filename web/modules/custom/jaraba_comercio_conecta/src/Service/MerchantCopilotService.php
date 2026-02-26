<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_ai_agents\Agent\MerchantCopilotAgent;
use Psr\Log\LoggerInterface;

/**
 * Servicio de orquestacion para el copiloto de comerciantes.
 *
 * FIX-017: Capa de servicio entre el controller API y el MerchantCopilotAgent.
 * Gestiona contexto de entidades, validacion y delegacion al agente IA.
 *
 * RESPONSABILIDADES:
 * - Orquesta la interaccion entre el controller y el MerchantCopilotAgent.
 * - Enriquece el contexto con datos reales de entidades Drupal (productos,
 *   variaciones de Commerce, resenas).
 * - Valida existencia de entidades antes de delegar al agente.
 * - Maneja gracefully la ausencia del agente IA (servicio opcional).
 *
 * ACCIONES SOPORTADAS:
 * - generate_description: Descripcion atractiva para productos.
 * - suggest_price: Precio competitivo basado en mercado local.
 * - social_post: Post para Instagram/Facebook con hashtags locales.
 * - flash_offer: Oferta flash para stock lento.
 * - respond_review: Respuesta profesional a resenas.
 * - email_promo: Email promocional para campana.
 *
 * @see \Drupal\jaraba_ai_agents\Agent\MerchantCopilotAgent
 */
class MerchantCopilotService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected ?MerchantCopilotAgent $copilotAgent = NULL,
  ) {}

  /**
   * Genera una descripcion atractiva para un producto.
   *
   * @param int $productId
   *   ID del producto de Commerce.
   * @param string|null $tenantId
   *   (Opcional) ID del tenant para contexto multi-tenant.
   *
   * @return array
   *   Resultado del agente con descripcion generada o error.
   */
  public function generateDescription(int $productId, ?string $tenantId = NULL): array {
    if (!$this->copilotAgent) {
      return ['success' => FALSE, 'error' => 'Copilot agent not available.'];
    }

    $product = $this->loadProduct($productId);
    if (!$product) {
      return ['success' => FALSE, 'error' => 'Product not found.'];
    }

    $context = [
      'product_name' => $product->getTitle(),
      'product_id' => $productId,
      'current_description' => $product->get('body')->value ?? '',
      'price' => $this->getProductPrice($product),
      'tenant_id' => $tenantId,
    ];

    return $this->copilotAgent->execute('generate_description', $context);
  }

  /**
   * Sugiere un precio competitivo para un producto.
   *
   * @param int $productId
   *   ID del producto de Commerce.
   * @param string|null $tenantId
   *   (Opcional) ID del tenant para contexto multi-tenant.
   *
   * @return array
   *   Resultado del agente con sugerencia de precio o error.
   */
  public function suggestPrice(int $productId, ?string $tenantId = NULL): array {
    if (!$this->copilotAgent) {
      return ['success' => FALSE, 'error' => 'Copilot agent not available.'];
    }

    $product = $this->loadProduct($productId);
    if (!$product) {
      return ['success' => FALSE, 'error' => 'Product not found.'];
    }

    $context = [
      'product_name' => $product->getTitle(),
      'product_id' => $productId,
      'current_price' => $this->getProductPrice($product),
      'tenant_id' => $tenantId,
    ];

    return $this->copilotAgent->execute('suggest_price', $context);
  }

  /**
   * Genera un post para redes sociales.
   *
   * @param array $params
   *   Parametros: product_name, message, platform, tone.
   *
   * @return array
   *   Resultado del agente con post generado o error.
   */
  public function generateSocialPost(array $params): array {
    if (!$this->copilotAgent) {
      return ['success' => FALSE, 'error' => 'Copilot agent not available.'];
    }

    $context = [
      'product_name' => $params['product_name'] ?? '',
      'message' => $params['message'] ?? '',
      'platform' => $params['platform'] ?? 'instagram',
      'tone' => $params['tone'] ?? 'casual',
    ];

    return $this->copilotAgent->execute('social_post', $context);
  }

  /**
   * Genera una oferta flash para un producto.
   *
   * @param int $productId
   *   ID del producto de Commerce.
   * @param array $offerParams
   *   Parametros opcionales: discount, duration.
   *
   * @return array
   *   Resultado del agente con oferta flash generada o error.
   */
  public function generateFlashOffer(int $productId, array $offerParams = []): array {
    if (!$this->copilotAgent) {
      return ['success' => FALSE, 'error' => 'Copilot agent not available.'];
    }

    $product = $this->loadProduct($productId);
    if (!$product) {
      return ['success' => FALSE, 'error' => 'Product not found.'];
    }

    $context = [
      'product_name' => $product->getTitle(),
      'product_id' => $productId,
      'current_price' => $this->getProductPrice($product),
      'discount_percentage' => $offerParams['discount'] ?? 20,
      'duration_hours' => $offerParams['duration'] ?? 24,
    ];

    return $this->copilotAgent->execute('flash_offer', $context);
  }

  /**
   * Genera respuesta a una resena de cliente.
   *
   * @param string $reviewText
   *   Texto de la resena a la que responder.
   * @param string|null $tenantId
   *   (Opcional) ID del tenant para contexto multi-tenant.
   *
   * @return array
   *   Resultado del agente con respuesta generada o error.
   */
  public function respondReview(string $reviewText, ?string $tenantId = NULL): array {
    if (!$this->copilotAgent) {
      return ['success' => FALSE, 'error' => 'Copilot agent not available.'];
    }

    $context = [
      'review_text' => $reviewText,
      'tenant_id' => $tenantId,
    ];

    return $this->copilotAgent->execute('respond_review', $context);
  }

  /**
   * Genera un email promocional.
   *
   * @param array $params
   *   Parametros: product_name, offer_details, audience, tone.
   *
   * @return array
   *   Resultado del agente con email generado o error.
   */
  public function generateEmailPromo(array $params): array {
    if (!$this->copilotAgent) {
      return ['success' => FALSE, 'error' => 'Copilot agent not available.'];
    }

    $context = [
      'product_name' => $params['product_name'] ?? '',
      'offer_details' => $params['offer_details'] ?? '',
      'audience' => $params['audience'] ?? 'general',
      'tone' => $params['tone'] ?? 'profesional',
    ];

    return $this->copilotAgent->execute('email_promo', $context);
  }

  /**
   * Carga un producto de Commerce.
   *
   * @param int $productId
   *   ID del producto.
   *
   * @return object|null
   *   La entidad producto o NULL si no se encuentra.
   */
  protected function loadProduct(int $productId): ?object {
    try {
      return $this->entityTypeManager->getStorage('commerce_product')->load($productId);
    }
    catch (\Exception $e) {
      $this->logger->error('MerchantCopilotService: Error loading product @id: @error', [
        '@id' => $productId,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Obtiene el precio de un producto desde sus variaciones.
   *
   * @param object $product
   *   La entidad producto de Commerce.
   *
   * @return string
   *   Precio formateado como "0.00 EUR".
   */
  protected function getProductPrice(object $product): string {
    try {
      $variations = $product->getVariations();
      if (!empty($variations)) {
        $variation = reset($variations);
        $price = $variation->getPrice();
        return $price ? $price->getNumber() . ' ' . $price->getCurrencyCode() : '0.00 EUR';
      }
    }
    catch (\Exception $e) {
      // Product may not have variations.
    }
    return '0.00 EUR';
  }

}
