<?php

namespace Drupal\jaraba_social_commerce\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador para webhooks de Social Commerce / Make.com.
 *
 * PROPÓSITO:
 * Este controlador proporciona endpoints para:
 * - Recibir datos de Make.com (sincronización inversa)
 * - Exportar productos en formatos específicos (Meta, Google)
 * - Triggear sincronizaciones manuales
 */
class SocialWebhookController extends ControllerBase
{

    /**
     * Recibe webhooks desde Make.com.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     * @param string $channel
     *   Canal de la integración (facebook, instagram, tiktok, pinterest, google).
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON.
     */
    public function receive(Request $request, string $channel): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        \Drupal::logger('jaraba_social_commerce')->info(
            '📥 Webhook recibido desde @channel: @data',
            [
                '@channel' => $channel,
                '@data' => json_encode($data),
            ]
        );

        // Procesar según el canal
        switch ($channel) {
            case 'facebook':
            case 'instagram':
                return $this->processMetaWebhook($data);

            case 'tiktok':
                return $this->processTikTokWebhook($data);

            case 'pinterest':
                return $this->processPinterestWebhook($data);

            case 'google':
                return $this->processGoogleWebhook($data);

            default:
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => 'Canal no soportado: ' . $channel,
                ], 400);
        }
    }

    /**
     * Obtiene productos en formato Meta Catalog (Facebook/Instagram).
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Lista de productos en formato Meta.
     */
    public function getProductsForMeta(Request $request): JsonResponse
    {
        $products = $this->loadPublishedProducts();
        $formatted = [];

        foreach ($products as $product) {
            $variations = $product->getVariations();
            $firstVariation = !empty($variations) ? reset($variations) : NULL;
            $price = $firstVariation?->getPrice();

            $formatted[] = [
                'id' => (string) $product->id(),
                'title' => $product->getTitle(),
                'description' => strip_tags($product->get('body')->value ?? ''),
                'availability' => $product->isPublished() ? 'in stock' : 'out of stock',
                'condition' => 'new',
                'price' => ($price?->getNumber() ?? 0) . ' ' . ($price?->getCurrencyCode() ?? 'EUR'),
                'link' => $product->toUrl('canonical', ['absolute' => TRUE])->toString(),
                'image_link' => $this->getProductImageUrl($product),
                'brand' => 'Jaraba Impact',
            ];
        }

        return new JsonResponse([
            'success' => TRUE,
            'count' => count($formatted),
            'products' => $formatted,
        ]);
    }

    /**
     * Obtiene productos en formato Google Merchant Center.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Lista de productos en formato Google.
     */
    public function getProductsForGoogle(Request $request): JsonResponse
    {
        $products = $this->loadPublishedProducts();
        $formatted = [];

        foreach ($products as $product) {
            $variations = $product->getVariations();
            $firstVariation = !empty($variations) ? reset($variations) : NULL;
            $price = $firstVariation?->getPrice();

            $formatted[] = [
                'offerId' => (string) $product->id(),
                'title' => $product->getTitle(),
                'description' => strip_tags($product->get('body')->value ?? ''),
                'link' => $product->toUrl('canonical', ['absolute' => TRUE])->toString(),
                'imageLink' => $this->getProductImageUrl($product),
                'contentLanguage' => 'es',
                'targetCountry' => 'ES',
                'channel' => 'online',
                'availability' => $product->isPublished() ? 'in_stock' : 'out_of_stock',
                'condition' => 'new',
                'price' => [
                    'value' => $price?->getNumber() ?? 0,
                    'currency' => $price?->getCurrencyCode() ?? 'EUR',
                ],
                'brand' => 'Jaraba Impact',
            ];
        }

        return new JsonResponse([
            'success' => TRUE,
            'count' => count($formatted),
            'items' => $formatted,
        ]);
    }

    /**
     * Triggera sincronización manual con un canal.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     * @param string $channel
     *   Canal a sincronizar.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Resultado de la sincronización.
     */
    public function triggerSync(Request $request, string $channel): JsonResponse
    {
        try {
            /** @var \Drupal\jaraba_social_commerce\Service\WebhookDispatcher $dispatcher */
            $dispatcher = \Drupal::service('jaraba_social_commerce.webhook_dispatcher');

            $products = $this->loadPublishedProducts();
            $synced = 0;

            foreach ($products as $product) {
                $dispatcher->dispatchProductUpdated($product);
                $synced++;
            }

            return new JsonResponse([
                'success' => TRUE,
                'channel' => $channel,
                'products_synced' => $synced,
                'message' => "Sincronización iniciada para $synced productos",
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Carga todos los productos publicados.
     *
     * @return \Drupal\commerce_product\Entity\ProductInterface[]
     *   Array de productos.
     */
    protected function loadPublishedProducts(): array
    {
        try {
            $productStorage = $this->entityTypeManager()->getStorage('commerce_product');
            return $productStorage->loadByProperties(['status' => 1]);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Obtiene la URL de imagen de un producto.
     */
    protected function getProductImageUrl($product): string
    {
        if ($product->hasField('field_images') && !$product->get('field_images')->isEmpty()) {
            $file = $product->get('field_images')->entity;
            if ($file) {
                return \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
            }
        }
        return '';
    }

    /**
     * Procesa webhook de Meta (Facebook/Instagram).
     */
    protected function processMetaWebhook(array $data): JsonResponse
    {
        // Implementar lógica específica de Meta
        return new JsonResponse(['success' => TRUE, 'processed' => 'meta']);
    }

    /**
     * Procesa webhook de TikTok.
     */
    protected function processTikTokWebhook(array $data): JsonResponse
    {
        return new JsonResponse(['success' => TRUE, 'processed' => 'tiktok']);
    }

    /**
     * Procesa webhook de Pinterest.
     */
    protected function processPinterestWebhook(array $data): JsonResponse
    {
        return new JsonResponse(['success' => TRUE, 'processed' => 'pinterest']);
    }

    /**
     * Procesa webhook de Google.
     */
    protected function processGoogleWebhook(array $data): JsonResponse
    {
        return new JsonResponse(['success' => TRUE, 'processed' => 'google']);
    }

}
