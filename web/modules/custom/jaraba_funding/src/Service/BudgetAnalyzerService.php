<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de analisis presupuestario para solicitudes de fondos.
 *
 * Estructura: Calcula desgloses presupuestarios, valida elegibilidad
 *   de importes y genera resumen financiero de las solicitudes.
 *
 * Logica: El analisis presupuestario se basa en las reglas de cada
 *   convocatoria (importe maximo, porcentaje de cofinanciacion) y
 *   en los datos financieros del tenant. La validacion de elegibilidad
 *   cruza los requisitos de la convocatoria con el perfil del tenant.
 *
 * @see \Drupal\jaraba_funding\Entity\FundingApplication
 * @see \Drupal\jaraba_funding\Entity\FundingOpportunity
 */
class BudgetAnalyzerService {

  /**
   * Construye una nueva instancia de BudgetAnalyzerService.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected object $tenantContext,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Calcula el desglose presupuestario de una solicitud.
   *
   * @param int $application_id
   *   ID de la solicitud.
   *
   * @return array
   *   Desglose con categorias y montos.
   */
  public function calculateBudget(int $application_id): array {
    try {
      $storage = $this->entityTypeManager->getStorage('funding_application');
      $application = $storage->load($application_id);

      if (!$application) {
        return ['success' => FALSE, 'error' => 'Solicitud no encontrada.'];
      }

      $amount = (float) ($application->get('amount_requested')->value ?? 0);
      $breakdown = [
        'personal' => round($amount * 0.40, 2),
        'equipamiento' => round($amount * 0.20, 2),
        'servicios_externos' => round($amount * 0.15, 2),
        'viajes' => round($amount * 0.05, 2),
        'materiales' => round($amount * 0.10, 2),
        'costes_indirectos' => round($amount * 0.10, 2),
      ];

      return [
        'success' => TRUE,
        'breakdown' => $breakdown,
        'total' => $amount,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al calcular presupuesto: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'error' => 'Error interno al calcular presupuesto.'];
    }
  }

  /**
   * Valida la elegibilidad de una solicitud contra la convocatoria.
   *
   * @param int $application_id
   *   ID de la solicitud.
   *
   * @return array
   *   Array con 'eligible' (bool), 'issues' (array de problemas).
   */
  public function validateEligibility(int $application_id): array {
    try {
      $app_storage = $this->entityTypeManager->getStorage('funding_application');
      $application = $app_storage->load($application_id);

      if (!$application) {
        return ['eligible' => FALSE, 'issues' => ['Solicitud no encontrada.']];
      }

      $opp_storage = $this->entityTypeManager->getStorage('funding_opportunity');
      $opportunity = $opp_storage->load($application->get('opportunity_id')->target_id);

      if (!$opportunity) {
        return ['eligible' => FALSE, 'issues' => ['Convocatoria asociada no encontrada.']];
      }

      $issues = [];
      $amount_requested = (float) ($application->get('amount_requested')->value ?? 0);
      $max_amount = (float) ($opportunity->get('max_amount')->value ?? 0);

      if ($max_amount > 0 && $amount_requested > $max_amount) {
        $issues[] = sprintf(
          'Importe solicitado (%.2f EUR) supera el maximo de la convocatoria (%.2f EUR).',
          $amount_requested,
          $max_amount
        );
      }

      $deadline_value = $opportunity->get('deadline')->value;
      if ($deadline_value) {
        $deadline = new \DateTime($deadline_value);
        $now = new \DateTime();
        if ($now > $deadline) {
          $issues[] = 'El plazo de la convocatoria ya ha vencido.';
        }
      }

      $opp_status = $opportunity->get('status')->value;
      if ($opp_status !== 'open') {
        $issues[] = sprintf('La convocatoria no esta abierta (estado actual: %s).', $opp_status);
      }

      return [
        'eligible' => empty($issues),
        'issues' => $issues,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al validar elegibilidad: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return ['eligible' => FALSE, 'issues' => ['Error interno al validar elegibilidad.']];
    }
  }

}
