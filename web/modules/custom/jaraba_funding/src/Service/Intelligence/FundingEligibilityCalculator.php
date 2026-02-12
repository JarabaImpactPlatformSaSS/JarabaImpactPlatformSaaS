<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Service\Intelligence;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Calculadora de elegibilidad para subvenciones.
 *
 * Evalua si un perfil de suscripcion cumple los requisitos de una
 * convocatoria, ejecutando checks individuales por criterio
 * (empleados, facturacion, tipo de beneficiario, plazo).
 *
 * ARQUITECTURA:
 * - Checks individuales que retornan {pass, reason}.
 * - Resultado agregado con elegibilidad, checks y warnings.
 * - Resumen legible para humanos.
 *
 * RELACIONES:
 * - FundingEligibilityCalculator -> FundingSubscription entity (perfil)
 * - FundingEligibilityCalculator -> FundingCall entity (requisitos)
 */
class FundingEligibilityCalculator {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del modulo.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Verifica la elegibilidad completa de una suscripcion para una convocatoria.
   *
   * @param \Drupal\Core\Entity\EntityInterface $subscription
   *   Entidad FundingSubscription con el perfil del tenant.
   * @param \Drupal\Core\Entity\EntityInterface $call
   *   Entidad FundingCall con los requisitos de la convocatoria.
   *
   * @return array
   *   Resultado de elegibilidad con claves:
   *   - eligible: (bool) TRUE si cumple todos los requisitos obligatorios.
   *   - checks: (array) Resultado de cada check individual.
   *   - warnings: (array) Advertencias no bloqueantes.
   */
  public function checkEligibility(EntityInterface $subscription, EntityInterface $call): array {
    $checks = [];
    $warnings = [];

    // Ejecutar todos los checks.
    $checks['employee_range'] = $this->checkEmployeeRange($subscription, $call);
    $checks['revenue_range'] = $this->checkRevenueRange($subscription, $call);
    $checks['beneficiary_type'] = $this->checkBeneficiaryType($subscription, $call);
    $checks['deadline'] = $this->checkDeadline($call);

    // Determinar elegibilidad global.
    $eligible = TRUE;
    foreach ($checks as $check) {
      if (!$check['pass']) {
        $eligible = FALSE;
      }
    }

    // Generar warnings.
    $deadlineCheck = $checks['deadline'];
    if ($deadlineCheck['pass'] && !empty($deadlineCheck['days_remaining'])) {
      $daysRemaining = $deadlineCheck['days_remaining'];
      if ($daysRemaining <= 7) {
        $warnings[] = "El plazo de solicitud vence en {$daysRemaining} dias.";
      }
      elseif ($daysRemaining <= 14) {
        $warnings[] = "Quedan {$daysRemaining} dias para el cierre del plazo.";
      }
    }

    // Warning si datos del perfil estan incompletos.
    $subEmployees = (int) ($subscription->get('employees')->value ?? 0);
    $subRevenue = (float) ($subscription->get('annual_revenue')->value ?? 0);
    if ($subEmployees === 0 && $subRevenue == 0) {
      $warnings[] = 'El perfil de la empresa tiene datos incompletos (empleados y facturacion). Complete su perfil para mejorar la precision del analisis.';
    }

    return [
      'eligible' => $eligible,
      'checks' => $checks,
      'warnings' => $warnings,
    ];
  }

  /**
   * Verifica si el numero de empleados esta dentro del rango requerido.
   *
   * @param \Drupal\Core\Entity\EntityInterface $sub
   *   Entidad FundingSubscription.
   * @param \Drupal\Core\Entity\EntityInterface $call
   *   Entidad FundingCall.
   *
   * @return array
   *   Resultado del check:
   *   - pass: (bool) TRUE si cumple.
   *   - reason: (string) Explicacion del resultado.
   */
  public function checkEmployeeRange(EntityInterface $sub, EntityInterface $call): array {
    $subEmployees = (int) ($sub->get('employees')->value ?? 0);
    $callMinEmployees = (int) ($call->get('min_employees')->value ?? 0);
    $callMaxEmployees = (int) ($call->get('max_employees')->value ?? 0);

    // Sin requisito de empleados.
    if ($callMinEmployees === 0 && $callMaxEmployees === 0) {
      return [
        'pass' => TRUE,
        'reason' => 'La convocatoria no establece requisitos de numero de empleados.',
      ];
    }

    // Datos del perfil no disponibles.
    if ($subEmployees === 0) {
      return [
        'pass' => TRUE,
        'reason' => 'No se ha indicado el numero de empleados en el perfil. Verifique manualmente.',
      ];
    }

    // Verificar rango.
    if ($callMinEmployees > 0 && $subEmployees < $callMinEmployees) {
      return [
        'pass' => FALSE,
        'reason' => "Se requieren al menos {$callMinEmployees} empleados. Su empresa tiene {$subEmployees}.",
      ];
    }

    if ($callMaxEmployees > 0 && $subEmployees > $callMaxEmployees) {
      return [
        'pass' => FALSE,
        'reason' => "Se permite un maximo de {$callMaxEmployees} empleados. Su empresa tiene {$subEmployees}.",
      ];
    }

    return [
      'pass' => TRUE,
      'reason' => "Numero de empleados ({$subEmployees}) dentro del rango requerido.",
    ];
  }

  /**
   * Verifica si la facturacion anual esta dentro del rango requerido.
   *
   * @param \Drupal\Core\Entity\EntityInterface $sub
   *   Entidad FundingSubscription.
   * @param \Drupal\Core\Entity\EntityInterface $call
   *   Entidad FundingCall.
   *
   * @return array
   *   Resultado del check:
   *   - pass: (bool) TRUE si cumple.
   *   - reason: (string) Explicacion del resultado.
   */
  public function checkRevenueRange(EntityInterface $sub, EntityInterface $call): array {
    $subRevenue = (float) ($sub->get('annual_revenue')->value ?? 0);
    $callMinRevenue = (float) ($call->get('min_revenue')->value ?? 0);
    $callMaxRevenue = (float) ($call->get('max_revenue')->value ?? 0);

    // Sin requisito de facturacion.
    if ($callMinRevenue == 0 && $callMaxRevenue == 0) {
      return [
        'pass' => TRUE,
        'reason' => 'La convocatoria no establece requisitos de facturacion.',
      ];
    }

    // Datos del perfil no disponibles.
    if ($subRevenue == 0) {
      return [
        'pass' => TRUE,
        'reason' => 'No se ha indicado la facturacion anual en el perfil. Verifique manualmente.',
      ];
    }

    $formattedRevenue = number_format($subRevenue, 2, ',', '.');
    $formattedMin = number_format($callMinRevenue, 2, ',', '.');
    $formattedMax = number_format($callMaxRevenue, 2, ',', '.');

    // Verificar rango.
    if ($callMinRevenue > 0 && $subRevenue < $callMinRevenue) {
      return [
        'pass' => FALSE,
        'reason' => "Se requiere una facturacion minima de {$formattedMin} EUR. Su facturacion es {$formattedRevenue} EUR.",
      ];
    }

    if ($callMaxRevenue > 0 && $subRevenue > $callMaxRevenue) {
      return [
        'pass' => FALSE,
        'reason' => "Se permite una facturacion maxima de {$formattedMax} EUR. Su facturacion es {$formattedRevenue} EUR.",
      ];
    }

    return [
      'pass' => TRUE,
      'reason' => "Facturacion ({$formattedRevenue} EUR) dentro del rango requerido.",
    ];
  }

  /**
   * Verifica si el tipo de beneficiario es compatible con la convocatoria.
   *
   * @param \Drupal\Core\Entity\EntityInterface $sub
   *   Entidad FundingSubscription.
   * @param \Drupal\Core\Entity\EntityInterface $call
   *   Entidad FundingCall.
   *
   * @return array
   *   Resultado del check:
   *   - pass: (bool) TRUE si cumple.
   *   - reason: (string) Explicacion del resultado.
   */
  public function checkBeneficiaryType(EntityInterface $sub, EntityInterface $call): array {
    $callTypes = $call->get('beneficiary_types')->value ?? [];
    $subType = $sub->get('beneficiary_type')->value ?? '';

    if (!is_array($callTypes)) {
      $callTypes = [$callTypes];
    }
    $callTypes = array_filter($callTypes);

    // Sin restriccion de beneficiarios.
    if (empty($callTypes)) {
      return [
        'pass' => TRUE,
        'reason' => 'La convocatoria no restringe el tipo de beneficiario.',
      ];
    }

    // Sin tipo de beneficiario en el perfil.
    if (empty($subType)) {
      return [
        'pass' => TRUE,
        'reason' => 'No se ha indicado el tipo de beneficiario en el perfil. Verifique manualmente.',
      ];
    }

    // Coincidencia directa.
    if (in_array($subType, $callTypes, TRUE)) {
      return [
        'pass' => TRUE,
        'reason' => "Su tipo de beneficiario ({$subType}) coincide con los requeridos.",
      ];
    }

    // Verificar inclusiones jerarquicas.
    $inclusions = [
      'pyme' => ['autonomo', 'micropyme', 'cooperativa'],
      'empresa' => ['pyme', 'autonomo', 'micropyme', 'gran_empresa'],
      'sin_animo_lucro' => ['asociacion', 'fundacion'],
    ];

    foreach ($callTypes as $callType) {
      if (isset($inclusions[$callType]) && in_array($subType, $inclusions[$callType], TRUE)) {
        return [
          'pass' => TRUE,
          'reason' => "Su tipo de beneficiario ({$subType}) esta incluido en la categoria '{$callType}'.",
        ];
      }
    }

    $typesStr = implode(', ', $callTypes);
    return [
      'pass' => FALSE,
      'reason' => "Su tipo de beneficiario ({$subType}) no coincide con los requeridos: {$typesStr}.",
    ];
  }

  /**
   * Verifica si el plazo de solicitud sigue abierto.
   *
   * @param \Drupal\Core\Entity\EntityInterface $call
   *   Entidad FundingCall.
   *
   * @return array
   *   Resultado del check:
   *   - pass: (bool) TRUE si el plazo sigue abierto.
   *   - reason: (string) Explicacion del resultado.
   *   - days_remaining: (int|null) Dias restantes hasta el cierre.
   */
  public function checkDeadline(EntityInterface $call): array {
    $deadline = $call->get('deadline')->value ?? NULL;

    // Sin fecha limite.
    if (empty($deadline)) {
      return [
        'pass' => TRUE,
        'reason' => 'La convocatoria no especifica fecha limite de solicitud.',
        'days_remaining' => NULL,
      ];
    }

    $deadlineDate = \DateTime::createFromFormat('Y-m-d', $deadline);
    if (!$deadlineDate) {
      return [
        'pass' => TRUE,
        'reason' => 'No se pudo interpretar la fecha limite. Verifique manualmente.',
        'days_remaining' => NULL,
      ];
    }

    $now = new \DateTime();
    $now->setTime(0, 0, 0);
    $deadlineDate->setTime(23, 59, 59);

    if ($now > $deadlineDate) {
      $deadlineFormatted = $deadlineDate->format('d/m/Y');
      return [
        'pass' => FALSE,
        'reason' => "El plazo de solicitud finalizo el {$deadlineFormatted}.",
        'days_remaining' => 0,
      ];
    }

    $diff = $now->diff($deadlineDate);
    $daysRemaining = (int) $diff->days;
    $deadlineFormatted = $deadlineDate->format('d/m/Y');

    return [
      'pass' => TRUE,
      'reason' => "Plazo abierto hasta el {$deadlineFormatted} ({$daysRemaining} dias restantes).",
      'days_remaining' => $daysRemaining,
    ];
  }

  /**
   * Genera un resumen legible de los checks de elegibilidad.
   *
   * @param array $checks
   *   Array de resultados de checks individuales.
   *
   * @return string
   *   Resumen en texto plano.
   */
  public function getEligibilitySummary(array $checks): string {
    $lines = [];
    $allPass = TRUE;

    foreach ($checks as $name => $check) {
      $icon = $check['pass'] ? 'OK' : 'NO CUMPLE';
      $label = match ($name) {
        'employee_range' => 'Empleados',
        'revenue_range' => 'Facturacion',
        'beneficiary_type' => 'Tipo beneficiario',
        'deadline' => 'Plazo',
        default => ucfirst(str_replace('_', ' ', $name)),
      };

      $lines[] = "[{$icon}] {$label}: {$check['reason']}";

      if (!$check['pass']) {
        $allPass = FALSE;
      }
    }

    $header = $allPass
      ? 'RESULTADO: ELEGIBLE - Cumple todos los requisitos verificados.'
      : 'RESULTADO: NO ELEGIBLE - No cumple uno o mas requisitos.';

    return $header . "\n\n" . implode("\n", $lines);
  }

}
