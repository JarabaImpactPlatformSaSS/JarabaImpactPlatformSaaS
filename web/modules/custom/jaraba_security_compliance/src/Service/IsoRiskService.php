<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestion de riesgos ISO 27001.
 *
 * Proporciona metodos para calcular puntuaciones de riesgo,
 * consultar el registro de riesgos por tenant, filtrar riesgos
 * altos/criticos y seed de riesgos por defecto.
 */
class IsoRiskService {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   El gestor de tipos de entidad.
   * @param \Psr\Log\LoggerInterface $logger
   *   El canal de log del modulo.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Calculates the risk score and determines the risk level.
   *
   * Risk matrix:
   * - 1-4: Low
   * - 5-9: Medium
   * - 10-14: High
   * - 15-25: Critical
   *
   * @param int $likelihood
   *   Likelihood score (1-5).
   * @param int $impact
   *   Impact score (1-5).
   *
   * @return array
   *   Array with:
   *   - score (int): The computed risk score (likelihood * impact).
   *   - level (string): The risk level: low, medium, high, or critical.
   *   - likelihood (int): The input likelihood.
   *   - impact (int): The input impact.
   */
  public function calculateRiskScore(int $likelihood, int $impact): array {
    $likelihood = max(1, min(5, $likelihood));
    $impact = max(1, min(5, $impact));
    $score = $likelihood * $impact;

    $level = match (TRUE) {
      $score >= 15 => 'critical',
      $score >= 10 => 'high',
      $score >= 5 => 'medium',
      default => 'low',
    };

    return [
      'score' => $score,
      'level' => $level,
      'likelihood' => $likelihood,
      'impact' => $impact,
    ];
  }

  /**
   * Returns the complete risk register for a tenant.
   *
   * @param int $tenantId
   *   The tenant ID.
   *
   * @return array
   *   Array of risk data, each with:
   *   - id (int): Entity ID.
   *   - asset (string): Asset name.
   *   - threat (string): Threat description.
   *   - vulnerability (string): Vulnerability description.
   *   - likelihood (int): Likelihood score.
   *   - impact (int): Impact score.
   *   - risk_score (int): Computed score.
   *   - risk_level (string): Risk level.
   *   - treatment (string): Treatment strategy.
   *   - residual_risk (int): Residual risk score.
   *   - status (string): Risk status.
   */
  public function getRiskRegister(int $tenantId): array {
    $risks = [];

    try {
      $storage = $this->entityTypeManager->getStorage('risk_assessment');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->sort('risk_score', 'DESC');
      $ids = $query->execute();

      if (!empty($ids)) {
        $entities = $storage->loadMultiple($ids);
        foreach ($entities as $entity) {
          /** @var \Drupal\jaraba_security_compliance\Entity\RiskAssessment $entity */
          $risks[] = [
            'id' => (int) $entity->id(),
            'asset' => $entity->getAsset(),
            'threat' => $entity->getThreat(),
            'vulnerability' => $entity->getVulnerability(),
            'likelihood' => $entity->getLikelihood(),
            'impact' => $entity->getImpact(),
            'risk_score' => $entity->getRiskScore(),
            'risk_level' => $entity->getRiskLevel(),
            'treatment' => $entity->getTreatment(),
            'residual_risk' => $entity->getResidualRisk(),
            'status' => $entity->getStatus(),
          ];
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to load risk register for tenant @tenant: @message', [
        '@tenant' => $tenantId,
        '@message' => $e->getMessage(),
      ]);
    }

    return $risks;
  }

  /**
   * Returns high-risk items (score >= 15) for a tenant.
   *
   * @param int $tenantId
   *   The tenant ID.
   *
   * @return array
   *   Array of high-risk items (same structure as getRiskRegister).
   */
  public function getHighRisks(int $tenantId): array {
    $risks = [];

    try {
      $storage = $this->entityTypeManager->getStorage('risk_assessment');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('risk_score', 15, '>=')
        ->sort('risk_score', 'DESC');
      $ids = $query->execute();

      if (!empty($ids)) {
        $entities = $storage->loadMultiple($ids);
        foreach ($entities as $entity) {
          /** @var \Drupal\jaraba_security_compliance\Entity\RiskAssessment $entity */
          $risks[] = [
            'id' => (int) $entity->id(),
            'asset' => $entity->getAsset(),
            'threat' => $entity->getThreat(),
            'vulnerability' => $entity->getVulnerability(),
            'likelihood' => $entity->getLikelihood(),
            'impact' => $entity->getImpact(),
            'risk_score' => $entity->getRiskScore(),
            'risk_level' => $entity->getRiskLevel(),
            'treatment' => $entity->getTreatment(),
            'residual_risk' => $entity->getResidualRisk(),
            'status' => $entity->getStatus(),
          ];
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to load high risks for tenant @tenant: @message', [
        '@tenant' => $tenantId,
        '@message' => $e->getMessage(),
      ]);
    }

    return $risks;
  }

  /**
   * Seeds the 7 default risks from ISO 27001 risk register template.
   *
   * @param int $tenantId
   *   The tenant ID.
   *
   * @return int
   *   Number of risks created.
   */
  public function seedDefaultRisks(int $tenantId): int {
    $defaultRisks = [
      [
        'asset' => 'Datos de clientes y usuarios',
        'threat' => 'Data breach - Brecha de datos',
        'vulnerability' => 'Exposicion de datos por configuracion incorrecta o ataque dirigido',
        'likelihood' => 3,
        'impact' => 5,
        'treatment' => 'mitigate',
        'mitigation_plan' => 'Cifrado en reposo y transito, control de acceso granular, auditorias periodicas, DLP.',
      ],
      [
        'asset' => 'Infraestructura de servidores',
        'threat' => 'Ransomware',
        'vulnerability' => 'Vulnerabilidad en software no parcheado o phishing a empleados',
        'likelihood' => 2,
        'impact' => 5,
        'treatment' => 'mitigate',
        'mitigation_plan' => 'Backups automatizados verificados, segmentacion de red, EDR, formacion anti-phishing.',
      ],
      [
        'asset' => 'Plataforma SaaS',
        'threat' => 'Cloud provider failure - Fallo del proveedor cloud',
        'vulnerability' => 'Dependencia de un unico proveedor de infraestructura cloud',
        'likelihood' => 2,
        'impact' => 4,
        'treatment' => 'transfer',
        'mitigation_plan' => 'SLA con proveedor, backups multi-region, plan DR con RTO/RPO definidos.',
      ],
      [
        'asset' => 'Configuracion de sistemas',
        'threat' => 'Human error - Error humano',
        'vulnerability' => 'Procedimientos manuales sin verificacion automatizada',
        'likelihood' => 4,
        'impact' => 3,
        'treatment' => 'mitigate',
        'mitigation_plan' => 'CI/CD con validacion automatica, preflight checks, rollback automatico, revision por pares.',
      ],
      [
        'asset' => 'Aplicacion web',
        'threat' => 'Zero-day exploit',
        'vulnerability' => 'Vulnerabilidad desconocida en framework o dependencias',
        'likelihood' => 2,
        'impact' => 4,
        'treatment' => 'mitigate',
        'mitigation_plan' => 'WAF, actualizaciones automaticas, OWASP ZAP scanning, bug bounty program.',
      ],
      [
        'asset' => 'Cuentas de usuario',
        'threat' => 'Unauthorized access - Acceso no autorizado',
        'vulnerability' => 'Credenciales debiles o robadas, ausencia de MFA',
        'likelihood' => 3,
        'impact' => 4,
        'treatment' => 'mitigate',
        'mitigation_plan' => 'MFA obligatorio, politica de contrasenas fuertes, monitorizacion de logins sospechosos.',
      ],
      [
        'asset' => 'Base de datos',
        'threat' => 'Data loss - Perdida de datos',
        'vulnerability' => 'Fallo de hardware, corrupcion de datos, eliminacion accidental',
        'likelihood' => 2,
        'impact' => 5,
        'treatment' => 'mitigate',
        'mitigation_plan' => 'Backups incrementales diarios, snapshots, replicacion, pruebas de restauracion mensuales.',
      ],
    ];

    $created = 0;

    try {
      $storage = $this->entityTypeManager->getStorage('risk_assessment');

      foreach ($defaultRisks as $risk) {
        // Check if a similar risk already exists for this tenant.
        $existing = $storage->getQuery()
          ->accessCheck(FALSE)
          ->condition('tenant_id', $tenantId)
          ->condition('threat', $risk['threat'])
          ->count()
          ->execute();

        if ((int) $existing > 0) {
          continue;
        }

        $riskCalc = $this->calculateRiskScore($risk['likelihood'], $risk['impact']);

        $entity = $storage->create([
          'tenant_id' => $tenantId,
          'asset' => $risk['asset'],
          'threat' => $risk['threat'],
          'vulnerability' => $risk['vulnerability'],
          'likelihood' => $risk['likelihood'],
          'impact' => $risk['impact'],
          'risk_score' => $riskCalc['score'],
          'risk_level' => $riskCalc['level'],
          'treatment' => $risk['treatment'],
          'residual_risk' => (int) round($riskCalc['score'] * 0.4),
          'mitigation_plan' => $risk['mitigation_plan'],
          'status' => 'open',
        ]);
        $entity->save();
        $created++;
      }

      $this->logger->info('Seeded @count default risks for tenant @tenant.', [
        '@count' => $created,
        '@tenant' => $tenantId,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to seed default risks for tenant @tenant: @message', [
        '@tenant' => $tenantId,
        '@message' => $e->getMessage(),
      ]);
    }

    return $created;
  }

}
