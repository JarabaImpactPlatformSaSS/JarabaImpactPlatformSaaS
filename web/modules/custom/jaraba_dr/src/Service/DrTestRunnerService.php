<?php

declare(strict_types=1);

namespace Drupal\jaraba_dr\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de ejecucion y registro de tests DR.
 *
 * ESTRUCTURA:
 * Framework para ejecutar pruebas de Disaster Recovery de forma
 * automatizada o manual, registrando resultados como entidades
 * DrTestResult.
 *
 * LOGICA:
 * - Ejecuta pruebas de tipo: backup_restore, failover, network, database, full_dr.
 * - Mide RTO y RPO alcanzados durante la prueba.
 * - Registra resultados tecnicos detallados en JSON.
 * - Programa pruebas automaticas segun la periodicidad configurada.
 *
 * RELACIONES:
 * - DrTestResult (entidad de resultados)
 * - BackupVerifierService (para pruebas de backup_restore)
 * - FailoverOrchestratorService (para pruebas de failover)
 * - jaraba_dr.settings (periodicidad de tests)
 *
 * Spec: Doc 185 s4.3. Plan: FASE 9, Stack Compliance Legal N1.
 */
class DrTestRunnerService {

  /**
   * Construye el servicio de ejecucion de tests DR.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Factoria de configuracion.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de logging.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Ejecuta un test DR del tipo especificado.
   *
   * @param string $testType
   *   Tipo de test: backup_restore, failover, network, database, full_dr.
   * @param string $testName
   *   Nombre descriptivo del test.
   * @param int|null $userId
   *   ID del usuario que ejecuta el test, o NULL para automatico.
   *
   * @return int
   *   ID de la entidad DrTestResult creada.
   */
  public function executeTest(string $testType, string $testName, ?int $userId = NULL): int {
    // Stub: implementacion completa en fases posteriores.
    $this->logger->info('Test DR ejecutado: @name (tipo: @type)', [
      '@name' => $testName,
      '@type' => $testType,
    ]);
    return 0;
  }

  /**
   * Ejecuta los tests DR programados.
   *
   * @return int
   *   Numero de tests ejecutados.
   */
  public function runScheduledTests(): int {
    // Stub: implementacion completa en fases posteriores.
    $this->logger->info('Tests DR programados ejecutados.');
    return 0;
  }

}
