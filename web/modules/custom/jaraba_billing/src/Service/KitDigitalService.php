<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_billing\Entity\KitDigitalAgreement;
use Psr\Log\LoggerInterface;

/**
 * Gestiona el ciclo de vida de los acuerdos Kit Digital.
 *
 * Responsabilidades:
 * - Crear acuerdos con generación automática de número de referencia.
 * - Mapear paquetes a categorías Kit Digital del Anexo IV.
 * - Calcular bonos máximos por segmento.
 * - Generar Memoria Técnica de Actuación para justificación.
 * - Vincular acuerdos a suscripciones Stripe.
 * - Calcular meses de servicio cubiertos por el bono.
 */
class KitDigitalService {

  /**
   * Mapa paquete → categorías Kit Digital (Anexo IV).
   */
  private const PAQUETE_CATEGORIAS = [
    'comercio_digital' => ['C1', 'C2', 'C3', 'C6'],
    'productor_digital' => ['C1', 'C8', 'C6', 'C2'],
    'profesional_digital' => ['C4', 'C5', 'C3', 'C7'],
    'despacho_digital' => ['C4', 'C5', 'C7', 'C2'],
    'emprendedor_digital' => ['C4', 'C6', 'C2', 'C3'],
  ];

  /**
   * Tabla de bonos máximos: paquete × segmento → EUR.
   *
   * Fuente: Orden ETD/1498/2021 + Convocatoria 2024-2026.
   */
  private const BONO_MAXIMOS = [
    'comercio_digital' => ['I' => 12000, 'II' => 6000, 'III' => 3000, 'IV' => 12000, 'V' => 25000],
    'productor_digital' => ['I' => 10000, 'II' => 6000, 'III' => 3000, 'IV' => 10000, 'V' => 25000],
    'profesional_digital' => ['I' => 17000, 'II' => 6000, 'III' => 3000, 'IV' => 17000, 'V' => 29000],
    'despacho_digital' => ['I' => 15000, 'II' => 6000, 'III' => 3000, 'IV' => 15000, 'V' => 29000],
    'emprendedor_digital' => ['I' => 14000, 'II' => 6000, 'III' => 3000, 'IV' => 14000, 'V' => 25000],
  ];

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
    protected readonly AccountProxyInterface $currentUser,
    protected readonly TimeInterface $time,
  ) {}

  /**
   * Crea un nuevo acuerdo Kit Digital para un tenant.
   *
   * @param int $tenant_id
   *   ID del grupo/tenant beneficiario.
   * @param string $paquete
   *   Clave del paquete (comercio_digital, productor_digital, etc.).
   * @param string $segmento
   *   Segmento del beneficiario (I, II, III, IV, V).
   * @param float $bono_amount
   *   Importe del bono digital concedido.
   * @param string $plan_tier
   *   Tier del plan (starter, pro, enterprise).
   *
   * @return \Drupal\jaraba_billing\Entity\KitDigitalAgreement
   *   El acuerdo creado.
   */
  public function createAgreement(
    int $tenant_id,
    string $paquete,
    string $segmento,
    float $bono_amount,
    string $plan_tier = 'pro',
  ): KitDigitalAgreement {
    $storage = $this->entityTypeManager->getStorage('kit_digital_agreement');
    $year = date('Y');
    $sequence = $this->getNextSequence($year);

    /** @var \Drupal\jaraba_billing\Entity\KitDigitalAgreement $agreement */
    $agreement = $storage->create([
      'tenant_id' => $tenant_id,
      'agreement_number' => sprintf('KD-%s-%04d', $year, $sequence),
      'paquete' => $paquete,
      'segmento' => $segmento,
      'bono_digital_amount' => $bono_amount,
      'plan_tier' => $plan_tier,
      'categorias_kit_digital' => json_encode($this->getCategoriesForPaquete($paquete)),
      'start_date' => date('Y-m-d\TH:i:s'),
      'end_date' => date('Y-m-d\TH:i:s', strtotime('+12 months')),
      'status' => 'draft',
    ]);

    $agreement->save();

    $this->logger->info('Kit Digital agreement @num created for tenant @tid (paquete: @paq, segmento: @seg, bono: @bono EUR)', [
      '@num' => $agreement->get('agreement_number')->value,
      '@tid' => $tenant_id,
      '@paq' => $paquete,
      '@seg' => $segmento,
      '@bono' => $bono_amount,
    ]);

    return $agreement;
  }

  /**
   * Devuelve las categorías Kit Digital cubiertas por un paquete.
   *
   * @param string $paquete
   *   Clave del paquete.
   *
   * @return string[]
   *   Array de categorías (C1, C2, C3...).
   */
  public function getCategoriesForPaquete(string $paquete): array {
    return self::PAQUETE_CATEGORIAS[$paquete] ?? [];
  }

  /**
   * Calcula el bono máximo para un paquete y segmento.
   *
   * @param string $paquete
   *   Clave del paquete.
   * @param string $segmento
   *   Segmento del beneficiario (I-V).
   *
   * @return float
   *   Importe máximo del bono en EUR.
   */
  public function getMaxBonoAmount(string $paquete, string $segmento): float {
    return (float) (self::BONO_MAXIMOS[$paquete][$segmento] ?? 0);
  }

  /**
   * Vincula el acuerdo a una suscripción Stripe.
   *
   * @param int $agreement_id
   *   ID del acuerdo.
   * @param string $stripe_subscription_id
   *   ID de la suscripción Stripe.
   */
  public function linkToStripeSubscription(int $agreement_id, string $stripe_subscription_id): void {
    $storage = $this->entityTypeManager->getStorage('kit_digital_agreement');
    /** @var \Drupal\jaraba_billing\Entity\KitDigitalAgreement|null $agreement */
    $agreement = $storage->load($agreement_id);
    if (!$agreement) {
      $this->logger->error('Kit Digital agreement @id not found for Stripe link.', ['@id' => $agreement_id]);
      return;
    }

    $agreement->set('stripe_subscription_id', $stripe_subscription_id);
    $agreement->set('status', 'active');
    $agreement->save();

    $this->logger->info('Kit Digital agreement @num linked to Stripe subscription @sub.', [
      '@num' => $agreement->get('agreement_number')->value,
      '@sub' => $stripe_subscription_id,
    ]);
  }

  /**
   * Calcula los meses de servicio cubiertos por el bono.
   *
   * El Kit Digital requiere mínimo 12 meses de servicio.
   *
   * @param float $bono_amount
   *   Importe del bono en EUR.
   * @param float $monthly_price
   *   Precio mensual del plan en EUR.
   *
   * @return int
   *   Número de meses cubiertos (mínimo 12).
   */
  public function calculateCoveredMonths(float $bono_amount, float $monthly_price): int {
    if ($monthly_price <= 0) {
      return 12;
    }
    return max(12, (int) ceil($bono_amount / $monthly_price));
  }

  /**
   * Devuelve todos los acuerdos activos.
   *
   * @return \Drupal\jaraba_billing\Entity\KitDigitalAgreement[]
   *   Array de acuerdos con status = 'active'.
   */
  public function getActiveAgreements(): array {
    $storage = $this->entityTypeManager->getStorage('kit_digital_agreement');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 'active')
      ->sort('start_date', 'DESC')
      ->execute();

    return $ids ? $storage->loadMultiple($ids) : [];
  }

  /**
   * Comprueba si un tenant fue adquirido vía bono Kit Digital.
   *
   * @param int $tenant_id
   *   ID del tenant.
   *
   * @return bool
   *   TRUE si el tenant tiene al menos un acuerdo Kit Digital no borrador.
   */
  public function isKitDigitalTenant(int $tenant_id): bool {
    $storage = $this->entityTypeManager->getStorage('kit_digital_agreement');
    $count = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenant_id)
      ->condition('status', 'draft', '<>')
      ->count()
      ->execute();

    return (int) $count > 0;
  }

  /**
   * Obtiene los acuerdos que expiran en los próximos N días.
   *
   * Usado por cron y daily actions para alertas proactivas.
   *
   * @param int $days
   *   Número de días de anticipación.
   *
   * @return \Drupal\jaraba_billing\Entity\KitDigitalAgreement[]
   *   Array de acuerdos que expiran dentro del rango.
   */
  public function getExpiringAgreements(int $days = 60): array {
    $storage = $this->entityTypeManager->getStorage('kit_digital_agreement');
    $now = date('Y-m-d\TH:i:s');
    $limit = date('Y-m-d\TH:i:s', $this->time->getCurrentTime() + ($days * 86400));

    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 'active')
      ->condition('end_date', $now, '>')
      ->condition('end_date', $limit, '<')
      ->sort('end_date', 'ASC')
      ->execute();

    return $ids ? $storage->loadMultiple($ids) : [];
  }

  /**
   * Transiciona acuerdos expirados a estado 'justification_pending'.
   *
   * Se ejecuta vía cron. Marca acuerdos activos cuya end_date ha pasado.
   *
   * @return int
   *   Número de acuerdos transicionados.
   */
  public function processExpiredAgreements(): int {
    $storage = $this->entityTypeManager->getStorage('kit_digital_agreement');
    $now = date('Y-m-d\TH:i:s');

    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 'active')
      ->condition('end_date', $now, '<')
      ->execute();

    $count = 0;
    if ($ids) {
      /** @var \Drupal\jaraba_billing\Entity\KitDigitalAgreement[] $agreements */
      $agreements = $storage->loadMultiple($ids);
      foreach ($agreements as $agreement) {
        $agreement->set('status', 'justification_pending');
        $agreement->save();
        $count++;

        $this->logger->info('Kit Digital agreement @num transitioned to justification_pending (expired).', [
          '@num' => $agreement->get('agreement_number')->value,
        ]);
      }
    }

    return $count;
  }

  /**
   * Obtiene el siguiente número de secuencia para el año dado.
   */
  private function getNextSequence(string $year): int {
    $storage = $this->entityTypeManager->getStorage('kit_digital_agreement');
    $count = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('agreement_number', 'KD-' . $year . '-', 'STARTS_WITH')
      ->count()
      ->execute();

    return (int) $count + 1;
  }

}
