<?php

declare(strict_types=1);

namespace Drupal\jaraba_training\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Servicio de tracking de royalties.
 *
 * Gestiona el cálculo y registro de royalties para certificados.
 *
 * @todo INTEGRACIÓN STRIPE CONNECT (Fase Futura):
 *   - Conectar con `jaraba_commerce.stripe_connect` para pagos automáticos a franquiciados.
 *   - Implementar Stripe Connect Express Accounts para consultores certificados.
 *   - Escuchar webhook `payment_intent.succeeded` para registrar royalties automáticamente.
 *   - Crear endpoint de liquidación periódica (semanal/mensual) via Stripe Transfers.
 * @see docs/tecnicos/20260110e-Documento_Tecnico_Maestro_v2_Claude.md (Sección 5.2.2)
 */
class RoyaltyTracker
{

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected Connection $database,
    ) {
    }

    /**
     * Registra un royalty por una venta.
     *
     * @param int $certificationId
     *   ID de la certificación del consultor/entidad.
     * @param float $saleAmount
     *   Monto de la venta.
     * @param float $royaltyPercent
     *   Porcentaje de royalty aplicable.
     *
     * @return float
     *   Monto del royalty calculado.
     */
    public function recordRoyalty(int $certificationId, float $saleAmount, float $royaltyPercent): float
    {
        $royaltyAmount = $saleAmount * ($royaltyPercent / 100);

        // Registrar en tabla de transacciones.
        $this->database->insert('jaraba_training_royalty_transactions')
            ->fields([
                'certification_id' => $certificationId,
                'sale_amount' => $saleAmount,
                'royalty_percent' => $royaltyPercent,
                'royalty_amount' => $royaltyAmount,
                'created' => time(),
            ])
            ->execute();

        // Actualizar total en la certificación.
        $this->updateCertificationTotal($certificationId, $royaltyAmount);

        return $royaltyAmount;
    }

    /**
     * Actualiza el total de royalties en una certificación.
     */
    protected function updateCertificationTotal(int $certificationId, float $amount): void
    {
        $storage = $this->entityTypeManager->getStorage('user_certification');
        $certification = $storage->load($certificationId);

        if ($certification) {
            $currentTotal = (float) ($certification->get('total_royalties')->value ?? 0);
            $certification->set('total_royalties', $currentTotal + $amount);
            $certification->save();
        }
    }

    /**
     * Obtiene el total de royalties de una certificación.
     *
     * @param int $certificationId
     *   ID de la certificación.
     *
     * @return float
     *   Total acumulado.
     */
    public function getTotalRoyalties(int $certificationId): float
    {
        $storage = $this->entityTypeManager->getStorage('user_certification');
        $certification = $storage->load($certificationId);

        if ($certification) {
            return (float) ($certification->get('total_royalties')->value ?? 0);
        }

        return 0;
    }

    /**
     * Obtiene historial de royalties de una certificación.
     *
     * @param int $certificationId
     *   ID de la certificación.
     * @param int $limit
     *   Número máximo de registros.
     *
     * @return array
     *   Lista de transacciones.
     */
    public function getRoyaltyHistory(int $certificationId, int $limit = 50): array
    {
        // Verificar si la tabla existe antes de consultar.
        if (!$this->database->schema()->tableExists('jaraba_training_royalty_transactions')) {
            return [];
        }

        return $this->database->select('jaraba_training_royalty_transactions', 'r')
            ->fields('r')
            ->condition('certification_id', $certificationId)
            ->orderBy('created', 'DESC')
            ->range(0, $limit)
            ->execute()
            ->fetchAll();
    }

}
