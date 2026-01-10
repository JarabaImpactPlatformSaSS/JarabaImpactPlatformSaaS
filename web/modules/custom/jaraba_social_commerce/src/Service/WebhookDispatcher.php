<?php

namespace Drupal\jaraba_social_commerce\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use GuzzleHttp\ClientInterface;

/**
 * Servicio para enviar webhooks a Make.com y otras plataformas.
 *
 * PROPÓSITO:
 * Este servicio envía eventos de Commerce (producto creado, actualizado,
 * orden completada, etc.) a Make.com para sincronización con redes sociales.
 *
 * CANALES SOPORTADOS:
 * - Facebook/Instagram Shop (Meta Catalog API)
 * - TikTok Shop
 * - Pinterest Shopping
 * - Google Shopping (Merchant Center)
 *
 * @see https://www.make.com/en/integrations/webhooks
 */
class WebhookDispatcher
{

    /**
     * HTTP client for making requests.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected ClientInterface $httpClient;

    /**
     * Config factory.
     *
     * @var \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected ConfigFactoryInterface $configFactory;

    /**
     * Logger channel.
     *
     * @var \Drupal\Core\Logger\LoggerChannelInterface
     */
    protected LoggerChannelInterface $logger;

    /**
     * Constructor.
     */
    public function __construct(
        ClientInterface $httpClient,
        ConfigFactoryInterface $configFactory,
        LoggerChannelInterface $logger
    ) {
        $this->httpClient = $httpClient;
        $this->configFactory = $configFactory;
        $this->logger = $logger;
    }

    /**
     * Envía un evento a Make.com.
     *
     * @param string $eventType
     *   Tipo de evento: product.created, product.updated, order.completed, etc.
     * @param array $data
     *   Datos del evento.
     * @param int|null $tenantId
     *   ID del tenant (opcional, para webhooks específicos por tenant).
     *
     * @return bool
     *   TRUE si se envió correctamente.
     */
    public function dispatch(string $eventType, array $data, ?int $tenantId = NULL): bool
    {
        $config = $this->configFactory->get('jaraba_social_commerce.settings');
        $webhookUrl = $config->get('make_webhook_url');

        if (empty($webhookUrl)) {
            $this->logger->warning('Make.com webhook URL no configurada.');
            return FALSE;
        }

        $payload = [
            'event_type' => $eventType,
            'timestamp' => date('c'),
            'tenant_id' => $tenantId,
            'data' => $data,
        ];

        try {
            $response = $this->httpClient->request('POST', $webhookUrl, [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Jaraba-Event' => $eventType,
                ],
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode >= 200 && $statusCode < 300) {
                $this->logger->info(
                    '📤 Webhook enviado a Make.com: @event',
                    ['@event' => $eventType]
                );
                return TRUE;
            }

            $this->logger->warning(
                '⚠️ Make.com respondió con código @code para evento @event',
                ['@code' => $statusCode, '@event' => $eventType]
            );
            return FALSE;

        } catch (\Exception $e) {
            $this->logger->error(
                '🚫 Error enviando webhook a Make.com: @error',
                ['@error' => $e->getMessage()]
            );
            return FALSE;
        }
    }

    /**
     * Envía evento de producto creado.
     *
     * @param \Drupal\commerce_product\Entity\ProductInterface $product
     *   El producto creado.
     */
    public function dispatchProductCreated($product): void
    {
        $data = $this->formatProductForSync($product);
        $this->dispatch('product.created', $data);
    }

    /**
     * Envía evento de producto actualizado.
     *
     * @param \Drupal\commerce_product\Entity\ProductInterface $product
     *   El producto actualizado.
     */
    public function dispatchProductUpdated($product): void
    {
        $data = $this->formatProductForSync($product);
        $this->dispatch('product.updated', $data);
    }

    /**
     * Envía evento de orden completada.
     *
     * @param \Drupal\commerce_order\Entity\OrderInterface $order
     *   La orden completada.
     */
    public function dispatchOrderCompleted($order): void
    {
        $data = [
            'order_id' => $order->id(),
            'order_number' => $order->getOrderNumber(),
            'total' => $order->getTotalPrice()?->getNumber(),
            'currency' => $order->getTotalPrice()?->getCurrencyCode(),
            'customer_email' => $order->getEmail(),
            'items_count' => count($order->getItems()),
        ];
        $this->dispatch('order.completed', $data);
    }

    /**
     * Formatea un producto para sincronización con redes sociales.
     *
     * @param \Drupal\commerce_product\Entity\ProductInterface $product
     *   El producto.
     *
     * @return array
     *   Datos formateados.
     */
    protected function formatProductForSync($product): array
    {
        // Obtener primera variación para precio
        $variations = $product->getVariations();
        $firstVariation = !empty($variations) ? reset($variations) : NULL;
        $price = $firstVariation?->getPrice();

        return [
            'id' => $product->id(),
            'sku' => $firstVariation?->getSku() ?? 'SKU-' . $product->id(),
            'title' => $product->getTitle(),
            'description' => $product->get('body')->value ?? '',
            'ai_summary' => $product->get('field_ai_summary')->value ?? '',
            'price' => $price?->getNumber() ?? 0,
            'currency' => $price?->getCurrencyCode() ?? 'EUR',
            'availability' => $product->isPublished() ? 'in_stock' : 'out_of_stock',
            'link' => $product->toUrl('canonical', ['absolute' => TRUE])->toString(),
            'image_link' => $this->getProductImageUrl($product),
            'brand' => 'Jaraba Impact', // Puede personalizarse por tenant
            'condition' => 'new',
        ];
    }

    /**
     * Obtiene la URL de la imagen principal del producto.
     *
     * @param \Drupal\commerce_product\Entity\ProductInterface $product
     *   El producto.
     *
     * @return string
     *   URL de la imagen, o cadena vacía si no hay.
     */
    protected function getProductImageUrl($product): string
    {
        // Intentar obtener de field_images o similar
        if ($product->hasField('field_images') && !$product->get('field_images')->isEmpty()) {
            $file = $product->get('field_images')->entity;
            if ($file) {
                return \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
            }
        }
        return '';
    }

}
