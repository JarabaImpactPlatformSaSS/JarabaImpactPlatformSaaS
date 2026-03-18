<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\jaraba_billing\Entity\KitDigitalAgreement;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Dashboard de administración Kit Digital.
 *
 * Muestra KPIs, acuerdos recientes, alertas de justificación
 * y métricas de conversión Kit Digital vs orgánico.
 *
 * CONTROLLER-READONLY-001: No readonly en propiedades heredadas.
 * ZERO-REGION-001: Retorna render array con #theme.
 */
class KitDigitalAdminController extends ControllerBase {

  /**
   * Conexión a base de datos.
   */
  protected Connection $database;

  /**
   * Servicio de tiempo.
   */
  protected TimeInterface $time;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = new static();
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->database = $container->get('database');
    $instance->time = $container->get('datetime.time');
    return $instance;
  }

  /**
   * Dashboard principal Kit Digital.
   */
  public function dashboard(): array {
    $snapshot = $this->buildSnapshot();

    return [
      '#theme' => 'kit_digital_admin_dashboard',
      '#snapshot' => $snapshot,
      '#attached' => [
        'library' => ['jaraba_billing/kit-digital-admin'],
        'drupalSettings' => [
          'kitDigitalDashboard' => $snapshot,
        ],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * API endpoint para snapshot JSON.
   */
  public function apiSnapshot(): JsonResponse {
    return new JsonResponse(['data' => $this->buildSnapshot()]);
  }

  /**
   * Construye el snapshot de datos del dashboard.
   */
  private function buildSnapshot(): array {
    $storage = $this->entityTypeManager()->getStorage('kit_digital_agreement');
    $now = $this->time->getCurrentTime();
    $sixtyDays = date('Y-m-d\TH:i:s', $now + (60 * 86400));

    // KPIs.
    $totalAgreements = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 'draft', '<>')
      ->count()
      ->execute();

    $activeAgreements = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 'active')
      ->count()
      ->execute();

    $pendingJustification = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 'justification_pending')
      ->count()
      ->execute();

    // Bono total via DB API (no raw query — PHPStan security rule).
    $bonoTotal = 0.0;
    try {
      $selectBono = $this->database->select('kit_digital_agreement', 'kda');
      $selectBono->addExpression('COALESCE(SUM(kda.bono_digital_amount), 0)', 'total');
      $selectBono->condition('kda.status', 'draft', '<>');
      $result = $selectBono->execute()->fetchField();
      $bonoTotal = (float) ($result ?? 0);
    }
    catch (\Throwable) {
    }

    // Acuerdos próximos a expirar (< 60 días).
    $expiringSoon = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 'active')
      ->condition('end_date', date('Y-m-d\TH:i:s'), '>')
      ->condition('end_date', $sixtyDays, '<')
      ->count()
      ->execute();

    // Acuerdos recientes (últimos 10).
    $recentIds = $storage->getQuery()
      ->accessCheck(FALSE)
      ->sort('created', 'DESC')
      ->range(0, 10)
      ->execute();

    $recentAgreements = [];
    if (!empty($recentIds)) {
      /** @var \Drupal\jaraba_billing\Entity\KitDigitalAgreement[] $entities */
      $entities = $storage->loadMultiple($recentIds);
      foreach ($entities as $entity) {
        $recentAgreements[] = [
          'id' => $entity->id(),
          'agreement_number' => $entity->get('agreement_number')->value,
          'beneficiary_name' => $entity->get('beneficiary_name')->value,
          'paquete' => KitDigitalAgreement::PAQUETES[$entity->get('paquete')->value] ?? $entity->get('paquete')->value,
          'segmento' => $entity->get('segmento')->value,
          'bono_amount' => $entity->get('bono_digital_amount')->value,
          'status' => $entity->get('status')->value,
          'status_label' => KitDigitalAgreement::STATUSES[$entity->get('status')->value] ?? $entity->get('status')->value,
          'start_date' => $entity->get('start_date')->value,
        ];
      }
    }

    // Distribución por paquete via DB API.
    $distributionByPaquete = [];
    try {
      $selectDist = $this->database->select('kit_digital_agreement', 'kda');
      $selectDist->addField('kda', 'paquete');
      $selectDist->addExpression('COUNT(*)', 'total');
      $selectDist->addExpression('SUM(kda.bono_digital_amount)', 'bono_sum');
      $selectDist->condition('kda.status', 'draft', '<>');
      $selectDist->groupBy('kda.paquete');
      $results = $selectDist->execute()->fetchAll();
      foreach ($results as $row) {
        $distributionByPaquete[] = [
          'paquete' => KitDigitalAgreement::PAQUETES[$row->paquete] ?? $row->paquete,
          'key' => $row->paquete,
          'count' => (int) $row->total,
          'bono_sum' => (float) $row->bono_sum,
        ];
      }
    }
    catch (\Throwable) {
    }

    return [
      'kpis' => [
        'total_agreements' => $totalAgreements,
        'active_agreements' => $activeAgreements,
        'pending_justification' => $pendingJustification,
        'bono_total_eur' => $bonoTotal,
        'expiring_soon' => $expiringSoon,
      ],
      'recent_agreements' => $recentAgreements,
      'distribution_by_paquete' => $distributionByPaquete,
    ];
  }

}
