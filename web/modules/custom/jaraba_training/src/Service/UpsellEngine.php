<?php

declare(strict_types=1);

namespace Drupal\jaraba_training\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Queue\QueueFactoryInterface;
use Drupal\jaraba_training\Entity\TrainingProductInterface;
use Drupal\user\UserInterface;

/**
 * Motor de Upsell automático.
 *
 * Gestiona la conversión entre peldaños de la escalera mediante:
 * - Emails secuenciales post-compra
 * - Recomendaciones personalizadas
 * - Ofertas de tiempo limitado
 *
 * @todo INTEGRACIÓN COMMERCE (Fase Futura):
 *   - Escuchar evento `commerce_order.place.post_transition` para triggear upsell automático.
 *   - Usar servicio `jaraba_commerce.stripe_connect` para split payments (Destination Charges).
 *   - Crear Commerce Product Type 'training_course' que referencie TrainingProduct.
 *   - Integrar con `jaraba_email` para secuencias automatizadas post-compra.
 * @see docs/tecnicos/20260110e-Documento_Tecnico_Maestro_v2_Claude.md (Sección 5.2)
 */
class UpsellEngine
{

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected MailManagerInterface $mailManager,
        protected QueueFactoryInterface $queueFactory,
    ) {
    }

    /**
     * Programa secuencia de upsell después de una compra.
     *
     * @param \Drupal\jaraba_training\Entity\TrainingProductInterface $product
     *   Producto comprado.
     * @param \Drupal\user\UserInterface $user
     *   Usuario que compró.
     */
    public function scheduleUpsell(TrainingProductInterface $product, UserInterface $user): void
    {
        $nextProduct = $product->getNextProduct();

        if (!$nextProduct) {
            return;
        }

        // Encolar email de upsell.
        $queue = $this->queueFactory->get('jaraba_training_upsell');
        $queue->createItem([
            'user_id' => $user->id(),
            'purchased_product_id' => $product->id(),
            'upsell_product_id' => $nextProduct->id(),
            'send_after' => time() + (7 * 86400), // 7 días después.
        ]);

        \Drupal::logger('jaraba_training')->info(
            'Upsell programado para usuario @user: @from -> @to',
            [
                '@user' => $user->getDisplayName(),
                '@from' => $product->getTitle(),
                '@to' => $nextProduct->getTitle(),
            ]
        );
    }

    /**
     * Procesa un ítem de la cola de upsell.
     *
     * @param array $data
     *   Datos del ítem de cola.
     */
    public function processUpsellQueue(array $data): void
    {
        if (time() < ($data['send_after'] ?? 0)) {
            // Aún no es tiempo de enviar.
            return;
        }

        $userStorage = $this->entityTypeManager->getStorage('user');
        $productStorage = $this->entityTypeManager->getStorage('training_product');

        $user = $userStorage->load($data['user_id']);
        $upsellProduct = $productStorage->load($data['upsell_product_id']);

        if (!$user || !$upsellProduct) {
            return;
        }

        $this->sendUpsellEmail($user, $upsellProduct);
    }

    /**
     * Envía email de upsell.
     *
     * @param \Drupal\user\UserInterface $user
     *   Usuario destinatario.
     * @param \Drupal\jaraba_training\Entity\TrainingProductInterface $product
     *   Producto recomendado.
     */
    protected function sendUpsellEmail(UserInterface $user, TrainingProductInterface $product): void
    {
        $params = [
            'user_name' => $user->getDisplayName(),
            'product_title' => $product->getTitle(),
            'product_price' => $product->getPrice(),
            'upsell_message' => $product->get('upsell_message')->value ?? '',
        ];

        $this->mailManager->mail(
            'jaraba_training',
            'upsell_offer',
            $user->getEmail(),
            $user->getPreferredLangcode(),
            $params,
            NULL,
            TRUE
        );
    }

    /**
     * Recomienda siguiente producto basado en historial del usuario.
     *
     * @param \Drupal\user\UserInterface $user
     *   Usuario para recomendar.
     *
     * @return \Drupal\jaraba_training\Entity\TrainingProductInterface|null
     *   Producto recomendado.
     */
    public function recommendNext(UserInterface $user): ?TrainingProductInterface
    {
        // Delegar al LadderService.
        /** @var \Drupal\jaraba_training\Service\LadderService $ladderService */
        $ladderService = \Drupal::service('jaraba_training.ladder_service');
        return $ladderService->getRecommendedProduct((int) $user->id());
    }

}
