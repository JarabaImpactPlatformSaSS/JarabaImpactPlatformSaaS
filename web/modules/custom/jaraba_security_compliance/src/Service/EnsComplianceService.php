<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de compliance para el Esquema Nacional de Seguridad (ENS).
 *
 * Gestiona las medidas de seguridad del ENS (RD 311/2022) a nivel MEDIO,
 * proporcionando el catalogo de medidas predefinido, evaluacion de estado
 * por categoria, seed de medidas por defecto y resumen de compliance.
 */
class EnsComplianceService {

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
   * Returns all MEDIA-level ENS measures.
   *
   * Predefined catalog of ENS security measures organized by category:
   * organizational (org.*), operational (op.*), and protection (mp.*).
   *
   * @return array
   *   Array keyed by measure_id with:
   *   - measure_id (string): Unique measure identifier.
   *   - category (string): organizational, operational, or protection.
   *   - measure_name (string): Descriptive name.
   *   - required_level (string): basic, medium, or high.
   *   - description (string): Detailed description.
   */
  public function getMediaMeasures(): array {
    return [
      // Marco Organizativo (org.*).
      'org.1' => [
        'measure_id' => 'org.1',
        'category' => 'organizational',
        'measure_name' => 'Politica de Seguridad',
        'required_level' => 'basic',
        'description' => 'La organizacion debe disponer de una politica de seguridad aprobada por la direccion.',
      ],
      'org.2' => [
        'measure_id' => 'org.2',
        'category' => 'organizational',
        'measure_name' => 'Normativa de Seguridad',
        'required_level' => 'basic',
        'description' => 'Conjunto de normas que regulan el uso correcto de los medios tecnologicos.',
      ],
      'org.3' => [
        'measure_id' => 'org.3',
        'category' => 'organizational',
        'measure_name' => 'Procedimientos de Seguridad',
        'required_level' => 'basic',
        'description' => 'Procedimientos operativos que desarrollan la normativa de seguridad.',
      ],
      'org.4' => [
        'measure_id' => 'org.4',
        'category' => 'organizational',
        'measure_name' => 'Proceso de Autorizacion',
        'required_level' => 'basic',
        'description' => 'Proceso formal de autorizacion para el acceso a los sistemas de informacion.',
      ],

      // Marco Operacional (op.*).
      'op.pl.1' => [
        'measure_id' => 'op.pl.1',
        'category' => 'operational',
        'measure_name' => 'Analisis de Riesgos',
        'required_level' => 'basic',
        'description' => 'Realizacion de un analisis de riesgos que permita identificar amenazas y vulnerabilidades.',
      ],
      'op.pl.2' => [
        'measure_id' => 'op.pl.2',
        'category' => 'operational',
        'measure_name' => 'Arquitectura de Seguridad',
        'required_level' => 'medium',
        'description' => 'Definicion de la arquitectura de seguridad del sistema de informacion.',
      ],
      'op.acc.1' => [
        'measure_id' => 'op.acc.1',
        'category' => 'operational',
        'measure_name' => 'Identificacion',
        'required_level' => 'basic',
        'description' => 'Identificacion unica de cada usuario con acceso al sistema.',
      ],
      'op.acc.2' => [
        'measure_id' => 'op.acc.2',
        'category' => 'operational',
        'measure_name' => 'Requisitos de Acceso',
        'required_level' => 'basic',
        'description' => 'Definicion y control de los requisitos de acceso a los recursos.',
      ],
      'op.acc.5' => [
        'measure_id' => 'op.acc.5',
        'category' => 'operational',
        'measure_name' => 'Mecanismo de Autenticacion (MFA)',
        'required_level' => 'medium',
        'description' => 'Autenticacion multifactor para acceso a sistemas de categoria media o superior.',
      ],
      'op.exp.1' => [
        'measure_id' => 'op.exp.1',
        'category' => 'operational',
        'measure_name' => 'Registro de Actividad',
        'required_level' => 'basic',
        'description' => 'Registro de la actividad de los usuarios para detectar y responder a incidentes.',
      ],
      'op.exp.2' => [
        'measure_id' => 'op.exp.2',
        'category' => 'operational',
        'measure_name' => 'Gestion de Incidentes',
        'required_level' => 'medium',
        'description' => 'Procedimiento de gestion de incidentes de seguridad con notificacion al CCN-CERT.',
      ],

      // Medidas de Proteccion (mp.*).
      'mp.if.1' => [
        'measure_id' => 'mp.if.1',
        'category' => 'protection',
        'measure_name' => 'Areas Separadas y Control de Acceso Fisico',
        'required_level' => 'medium',
        'description' => 'Control de acceso fisico a las instalaciones que albergan los sistemas.',
      ],
      'mp.per.1' => [
        'measure_id' => 'mp.per.1',
        'category' => 'protection',
        'measure_name' => 'Caracterizacion del Puesto de Trabajo',
        'required_level' => 'basic',
        'description' => 'Definicion de responsabilidades y requisitos de seguridad por puesto.',
      ],
      'mp.eq.1' => [
        'measure_id' => 'mp.eq.1',
        'category' => 'protection',
        'measure_name' => 'Puesto de Trabajo Despejado',
        'required_level' => 'basic',
        'description' => 'Politica de puesto de trabajo despejado y bloqueo de pantalla.',
      ],
      'mp.com.1' => [
        'measure_id' => 'mp.com.1',
        'category' => 'protection',
        'measure_name' => 'Perimetro Seguro',
        'required_level' => 'basic',
        'description' => 'Proteccion del perimetro de comunicaciones mediante firewall y segmentacion.',
      ],
      'mp.si.1' => [
        'measure_id' => 'mp.si.1',
        'category' => 'protection',
        'measure_name' => 'Clasificacion de la Informacion',
        'required_level' => 'medium',
        'description' => 'Clasificacion de la informacion segun su nivel de confidencialidad.',
      ],
      'mp.s.1' => [
        'measure_id' => 'mp.s.1',
        'category' => 'protection',
        'measure_name' => 'Proteccion de Servicios Electronicos',
        'required_level' => 'medium',
        'description' => 'Medidas de proteccion para los servicios electronicos ofrecidos.',
      ],
    ];
  }

  /**
   * Assesses the compliance status of a specific ENS measure.
   *
   * @param string $measureId
   *   The ENS measure ID (e.g. 'org.1', 'op.acc.5').
   *
   * @return array
   *   Assessment result with:
   *   - measure_id (string): The measure identifier.
   *   - found (bool): Whether the measure is defined.
   *   - measure_name (string): Descriptive name.
   *   - category (string): Measure category.
   *   - required_level (string): Required ENS level.
   *   - platform_mapping (string): How the platform addresses this measure.
   *   - status (string): implemented, partial, not_implemented, or not_applicable.
   */
  public function assessMeasure(string $measureId): array {
    $measures = $this->getMediaMeasures();

    if (!isset($measures[$measureId])) {
      return [
        'measure_id' => $measureId,
        'found' => FALSE,
        'measure_name' => 'Unknown measure',
        'category' => '',
        'required_level' => '',
        'platform_mapping' => '',
        'status' => 'not_applicable',
      ];
    }

    $measure = $measures[$measureId];
    $mapping = $this->getPlatformMappings();
    $platformMapping = $mapping[$measureId] ?? ['mapping' => 'Not yet mapped', 'status' => 'not_implemented'];

    return [
      'measure_id' => $measureId,
      'found' => TRUE,
      'measure_name' => $measure['measure_name'],
      'category' => $measure['category'],
      'required_level' => $measure['required_level'],
      'platform_mapping' => $platformMapping['mapping'],
      'status' => $platformMapping['status'],
    ];
  }

  /**
   * Returns a compliance summary by category.
   *
   * @return array
   *   Summary keyed by category with:
   *   - category (string): Category code.
   *   - label (string): Human-readable category label.
   *   - total (int): Total measures.
   *   - implemented (int): Fully implemented measures.
   *   - partial (int): Partially implemented measures.
   *   - not_implemented (int): Not implemented measures.
   *   - score (int): Compliance percentage (0-100).
   */
  public function getComplianceSummary(): array {
    $measures = $this->getMediaMeasures();
    $mappings = $this->getPlatformMappings();

    $categories = [
      'organizational' => ['label' => 'Marco Organizativo', 'total' => 0, 'implemented' => 0, 'partial' => 0, 'not_implemented' => 0],
      'operational' => ['label' => 'Marco Operacional', 'total' => 0, 'implemented' => 0, 'partial' => 0, 'not_implemented' => 0],
      'protection' => ['label' => 'Medidas de Proteccion', 'total' => 0, 'implemented' => 0, 'partial' => 0, 'not_implemented' => 0],
    ];

    foreach ($measures as $measureId => $measure) {
      $cat = $measure['category'];
      if (!isset($categories[$cat])) {
        continue;
      }

      $categories[$cat]['total']++;
      $status = $mappings[$measureId]['status'] ?? 'not_implemented';

      match ($status) {
        'implemented' => $categories[$cat]['implemented']++,
        'partial' => $categories[$cat]['partial']++,
        default => $categories[$cat]['not_implemented']++,
      };
    }

    $result = [];
    foreach ($categories as $catCode => $catData) {
      $score = $catData['total'] > 0
        ? (int) round(($catData['implemented'] / $catData['total']) * 100)
        : 0;

      $result[$catCode] = [
        'category' => $catCode,
        'label' => $catData['label'],
        'total' => $catData['total'],
        'implemented' => $catData['implemented'],
        'partial' => $catData['partial'],
        'not_implemented' => $catData['not_implemented'],
        'score' => $score,
      ];
    }

    return $result;
  }

  /**
   * Seeds default ENS measures for a tenant.
   *
   * Creates EnsCompliance entities for all MEDIA-level measures
   * with initial status 'not_implemented'.
   *
   * @param int $tenantId
   *   The tenant ID to seed measures for.
   *
   * @return int
   *   Number of measures created.
   */
  public function seedDefaultMeasures(int $tenantId): int {
    $measures = $this->getMediaMeasures();
    $created = 0;

    try {
      $storage = $this->entityTypeManager->getStorage('ens_compliance');

      foreach ($measures as $measureId => $measure) {
        // Check if measure already exists for this tenant.
        $existing = $storage->getQuery()
          ->accessCheck(FALSE)
          ->condition('tenant_id', $tenantId)
          ->condition('measure_id', $measureId)
          ->count()
          ->execute();

        if ((int) $existing > 0) {
          continue;
        }

        $mapping = $this->getPlatformMappings();
        $initialStatus = $mapping[$measureId]['status'] ?? 'not_implemented';

        $entity = $storage->create([
          'tenant_id' => $tenantId,
          'measure_id' => $measureId,
          'category' => $measure['category'],
          'measure_name' => $measure['measure_name'],
          'required_level' => $measure['required_level'],
          'current_status' => $initialStatus,
          'evidence_type' => 'manual',
          'responsible' => '',
          'notes' => $measure['description'],
        ]);
        $entity->save();
        $created++;
      }

      $this->logger->info('Seeded @count ENS measures for tenant @tenant.', [
        '@count' => $created,
        '@tenant' => $tenantId,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to seed ENS measures for tenant @tenant: @message', [
        '@tenant' => $tenantId,
        '@message' => $e->getMessage(),
      ]);
    }

    return $created;
  }

  /**
   * Returns platform feature mappings to ENS measures.
   *
   * @return array
   *   Array keyed by measure_id with mapping and status.
   */
  protected function getPlatformMappings(): array {
    return [
      'org.1' => [
        'mapping' => 'SecurityPolicy entity with policy_type, versioning, and active/draft/archived lifecycle.',
        'status' => 'implemented',
      ],
      'org.2' => [
        'mapping' => 'SecurityPolicy with data_protection, encryption, retention types covering security standards.',
        'status' => 'implemented',
      ],
      'org.3' => [
        'mapping' => 'SecurityPolicy content field with detailed operational procedures.',
        'status' => 'partial',
      ],
      'org.4' => [
        'mapping' => 'Drupal role/permission system with per-entity AccessControlHandler and Group-based authorization.',
        'status' => 'implemented',
      ],
      'op.pl.1' => [
        'mapping' => 'RiskAssessment entity with full risk register: asset, threat, vulnerability, likelihood, impact, treatment.',
        'status' => 'implemented',
      ],
      'op.pl.2' => [
        'mapping' => 'architecture.yaml defines system architecture; SecurityHeadersSubscriber enforces security perimeter.',
        'status' => 'implemented',
      ],
      'op.acc.1' => [
        'mapping' => 'Drupal user system with unique username/email, UUID-based entity identification.',
        'status' => 'implemented',
      ],
      'op.acc.2' => [
        'mapping' => 'Role-based access with 30+ granular permissions, FeatureGateService per vertical, tenant isolation.',
        'status' => 'implemented',
      ],
      'op.acc.5' => [
        'mapping' => 'MFA integration available, CSRF token verification on all mutations.',
        'status' => 'partial',
      ],
      'op.exp.1' => [
        'mapping' => 'SecurityAuditLog immutable entity with event_type, actor, IP, severity, details JSON.',
        'status' => 'implemented',
      ],
      'op.exp.2' => [
        'mapping' => 'SecurityPolicy incident_response type, AuditLogService event capture with severity escalation.',
        'status' => 'partial',
      ],
      'mp.if.1' => [
        'mapping' => 'Cloud-hosted infrastructure; physical security managed by cloud provider (OVH/AWS).',
        'status' => 'partial',
      ],
      'mp.per.1' => [
        'mapping' => 'Role definitions in permissions.yml per module; tenant-based user assignment.',
        'status' => 'implemented',
      ],
      'mp.eq.1' => [
        'mapping' => 'Organizational policy measure; documented in SecurityPolicy entity.',
        'status' => 'partial',
      ],
      'mp.com.1' => [
        'mapping' => 'SecurityHeadersSubscriber (CSP, HSTS), nginx hardening, .htaccess security rules.',
        'status' => 'implemented',
      ],
      'mp.si.1' => [
        'mapping' => 'Tenant-based data isolation via Group module; SecurityPolicy data_protection type.',
        'status' => 'partial',
      ],
      'mp.s.1' => [
        'mapping' => 'HTTPS enforced via HSTS, SecurityHeadersSubscriber, TLS configuration in nginx.',
        'status' => 'implemented',
      ],
    ];
  }

}
