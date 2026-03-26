<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_foc\Service\StripeConnectService;
use Psr\Log\LoggerInterface;

/**
 * Activa la facturación Stripe para packs de participantes publicados.
 *
 * Cuando un participante publica su pack en el catálogo, este servicio
 * crea el Stripe Product + Price y almacena los IDs en la entidad
 * PackServicioEi para habilitar pagos recurrentes.
 *
 * PRESAVE-RESILIENCE-001: Todos los métodos usan try-catch \Throwable.
 * STRIPE-URL-PREFIX-001: Endpoints sin /v1/ (base URL ya incluye /v1).
 */
class PackBillingActivationService {

  /**
   * Constructs a PackBillingActivationService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   El gestor de tipos de entidad.
   * @param \Psr\Log\LoggerInterface $logger
   *   El canal de log para andalucia_ei.
   * @param \Drupal\jaraba_foc\Service\StripeConnectService|null $stripeConnect
   *   El servicio de Stripe Connect (opcional, @?).
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
    protected readonly ?StripeConnectService $stripeConnect = NULL,
  ) {}

  /**
   * Activa la facturación Stripe para un pack publicado.
   *
   * Crea un Stripe Product y un Stripe Price (recurring monthly) y almacena
   * los IDs en la entidad PackServicioEi. Idempotente: si stripe_product_id
   * ya existe, retorna TRUE sin crear duplicados.
   *
   * @param int $packId
   *   ID de la entidad PackServicioEi.
   *
   * @return bool
   *   TRUE si la activación fue exitosa o ya estaba activa, FALSE en error.
   */
  public function activarBillingPack(int $packId): bool {
    if ($this->stripeConnect === NULL) {
      $this->logger->warning('StripeConnectService no disponible. No se puede activar billing para pack @id.', [
        '@id' => $packId,
      ]);
      return FALSE;
    }

    try {
      $storage = $this->entityTypeManager->getStorage('pack_servicio_ei');
      /** @var \Drupal\jaraba_andalucia_ei\Entity\PackServicioEi|null $pack */
      $pack = $storage->load($packId);

      if ($pack === NULL) {
        $this->logger->warning('Pack @id no encontrado para activar billing.', [
          '@id' => $packId,
        ]);
        return FALSE;
      }

      // Validar campos requeridos.
      $titulo = (string) ($pack->get('titulo_personalizado')->value ?? '');
      $packTipo = $pack->getPackTipo();
      $precioMensual = $pack->getPrecioMensual();

      if ($titulo === '' || $packTipo === '' || $precioMensual === NULL || (float) $precioMensual <= 0) {
        $this->logger->warning('Pack @id no tiene los campos requeridos para billing (titulo, pack_tipo, precio_mensual > 0).', [
          '@id' => $packId,
        ]);
        return FALSE;
      }

      // Idempotente: si ya tiene stripe_product_id, no recrear.
      $existingProductId = (string) ($pack->get('stripe_product_id')->value ?? '');
      if ($existingProductId !== '') {
        return TRUE;
      }

      // Crear Stripe Product.
      $modalidad = $pack->getModalidad();
      $productResponse = $this->stripeConnect->stripeRequest('POST', '/products', [
        'name' => $titulo,
        'metadata' => [
          'pack_tipo' => $packTipo,
          'modalidad' => $modalidad,
          'pack_id' => (string) $packId,
        ],
      ]);

      $stripeProductId = (string) ($productResponse['id'] ?? '');
      if ($stripeProductId === '') {
        $this->logger->error('Stripe no devolvió product ID para pack @id.', [
          '@id' => $packId,
        ]);
        return FALSE;
      }

      // Crear Stripe Price (recurring monthly).
      $unitAmount = (int) round((float) $precioMensual * 100);
      $priceResponse = $this->stripeConnect->stripeRequest('POST', '/prices', [
        'product' => $stripeProductId,
        'unit_amount' => (string) $unitAmount,
        'currency' => 'eur',
        'recurring' => [
          'interval' => 'month',
        ],
      ]);

      $stripePriceId = (string) ($priceResponse['id'] ?? '');
      if ($stripePriceId === '') {
        $this->logger->error('Stripe no devolvió price ID para pack @id (product: @product).', [
          '@id' => $packId,
          '@product' => $stripeProductId,
        ]);
        return FALSE;
      }

      // Actualizar entidad con IDs de Stripe.
      $pack->set('stripe_product_id', $stripeProductId);
      $pack->set('stripe_price_id', $stripePriceId);
      $pack->save();

      $this->logger->info('Billing activado para pack @id: product=@product, price=@price.', [
        '@id' => $packId,
        '@product' => $stripeProductId,
        '@price' => $stripePriceId,
      ]);

      return TRUE;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error activando billing para pack @id: @message', [
        '@id' => $packId,
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Genera un enlace de pago (Checkout Session) para un pack.
   *
   * Crea una Stripe Checkout Session con el price_id del pack para
   * configurar una suscripción mensual recurrente.
   *
   * @param int $packId
   *   ID de la entidad PackServicioEi.
   * @param string $clienteEmail
   *   Email del cliente que va a pagar.
   *
   * @return string|null
   *   URL de la Checkout Session o NULL en caso de error.
   */
  public function generarEnlacePago(int $packId, string $clienteEmail): ?string {
    if ($this->stripeConnect === NULL) {
      $this->logger->warning('StripeConnectService no disponible. No se puede generar enlace de pago para pack @id.', [
        '@id' => $packId,
      ]);
      return NULL;
    }

    try {
      $storage = $this->entityTypeManager->getStorage('pack_servicio_ei');
      /** @var \Drupal\jaraba_andalucia_ei\Entity\PackServicioEi|null $pack */
      $pack = $storage->load($packId);

      if ($pack === NULL) {
        $this->logger->warning('Pack @id no encontrado para generar enlace de pago.', [
          '@id' => $packId,
        ]);
        return NULL;
      }

      $stripePriceId = (string) ($pack->get('stripe_price_id')->value ?? '');
      if ($stripePriceId === '') {
        $this->logger->warning('Pack @id no tiene stripe_price_id configurado.', [
          '@id' => $packId,
        ]);
        return NULL;
      }

      // Crear Checkout Session para suscripción recurrente.
      $sessionResponse = $this->stripeConnect->stripeRequest('POST', '/checkout/sessions', [
        'mode' => 'subscription',
        'customer_email' => $clienteEmail,
        'line_items' => [
          [
            'price' => $stripePriceId,
            'quantity' => '1',
          ],
        ],
        'success_url' => $this->buildSuccessUrl($packId),
        'cancel_url' => $this->buildCancelUrl($packId),
        'metadata' => [
          'pack_id' => (string) $packId,
        ],
      ]);

      $checkoutUrl = (string) ($sessionResponse['url'] ?? '');
      if ($checkoutUrl === '') {
        $this->logger->error('Stripe no devolvió URL de checkout para pack @id.', [
          '@id' => $packId,
        ]);
        return NULL;
      }

      $this->logger->info('Enlace de pago generado para pack @id, cliente @email.', [
        '@id' => $packId,
        '@email' => $clienteEmail,
      ]);

      return $checkoutUrl;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error generando enlace de pago para pack @id: @message', [
        '@id' => $packId,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Desactiva la facturación Stripe para un pack.
   *
   * Archiva el Stripe Product (no lo elimina, best practice de Stripe)
   * y limpia los IDs de Stripe de la entidad.
   *
   * @param int $packId
   *   ID de la entidad PackServicioEi.
   *
   * @return bool
   *   TRUE si la desactivación fue exitosa, FALSE en error.
   */
  public function desactivarBillingPack(int $packId): bool {
    if ($this->stripeConnect === NULL) {
      $this->logger->warning('StripeConnectService no disponible. No se puede desactivar billing para pack @id.', [
        '@id' => $packId,
      ]);
      return FALSE;
    }

    try {
      $storage = $this->entityTypeManager->getStorage('pack_servicio_ei');
      /** @var \Drupal\jaraba_andalucia_ei\Entity\PackServicioEi|null $pack */
      $pack = $storage->load($packId);

      if ($pack === NULL) {
        $this->logger->warning('Pack @id no encontrado para desactivar billing.', [
          '@id' => $packId,
        ]);
        return FALSE;
      }

      $stripeProductId = (string) ($pack->get('stripe_product_id')->value ?? '');

      // Archivar producto en Stripe si existe.
      if ($stripeProductId !== '') {
        $this->stripeConnect->stripeRequest('POST', '/products/' . $stripeProductId, [
          'active' => 'false',
        ]);
      }

      // Limpiar IDs de Stripe de la entidad.
      $pack->set('stripe_product_id', NULL);
      $pack->set('stripe_price_id', NULL);
      $pack->save();

      $this->logger->info('Billing desactivado para pack @id (producto archivado: @product).', [
        '@id' => $packId,
        '@product' => $stripeProductId,
      ]);

      return TRUE;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error desactivando billing para pack @id: @message', [
        '@id' => $packId,
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Construye la URL de éxito para Checkout Session.
   *
   * @param int $packId
   *   ID del pack.
   *
   * @return string
   *   URL de éxito con session_id placeholder.
   */
  protected function buildSuccessUrl(int $packId): string {
    $baseUrl = $GLOBALS['base_url'] ?? 'https://plataformadeecosistemas.com';
    return $baseUrl . '/es/andalucia-ei/pago-exitoso?pack_id=' . $packId . '&session_id={CHECKOUT_SESSION_ID}';
  }

  /**
   * Construye la URL de cancelación para Checkout Session.
   *
   * @param int $packId
   *   ID del pack.
   *
   * @return string
   *   URL de cancelación.
   */
  protected function buildCancelUrl(int $packId): string {
    $baseUrl = $GLOBALS['base_url'] ?? 'https://plataformadeecosistemas.com';
    return $baseUrl . '/es/andalucia-ei/catalogo-packs?cancelled=1&pack_id=' . $packId;
  }

}
