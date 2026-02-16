<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de cálculo de métricas SLA.
 *
 * ESTRUCTURA:
 * Calcula las métricas de disponibilidad (uptime) por periodo y tenant,
 * determina incumplimientos y genera los créditos correspondientes.
 *
 * LÓGICA DE NEGOCIO:
 * - Calcular uptime real vs target comprometido por periodo.
 * - Registrar downtime en minutos y número de incidentes.
 * - Determinar porcentaje de crédito según el nivel de incumplimiento.
 * - Generar SlaRecord automáticamente vía cron al cierre de cada periodo.
 * - Integrar con el módulo de billing para aplicar créditos.
 *
 * RELACIONES:
 * - Depende de TenantContextService para aislamiento multi-tenant.
 * - Genera SlaRecord entities.
 *
 * Spec: Doc 184 §3.2. Plan: FASE 5, Stack Compliance Legal N1.
 */
class SlaCalculatorService {

  /**
   * Constructor del servicio.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly object $tenantContext,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly LoggerInterface $logger,
  ) {}

}
