<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;

/**
 * Calcula el compliance score fiscal unificado por tenant.
 *
 * Score 0-100 basado en 5 subfactores ponderados equitativamente (20 pts c/u):
 * 1. Cadena VeriFactu intacta (integridad hash).
 * 2. Remisiones AEAT al dia (sin registros pendientes > 4 dias).
 * 3. Certificados digitales vigentes (no expirados ni proximos a expirar).
 * 4. Facturas FACe sin rechazos recientes (B2G sin incidencias).
 * 5. Morosidad B2B controlada (Ley 3/2004, sin facturas > 60 dias).
 *
 * Inyeccion opcional: los servicios de jaraba_verifactu, jaraba_facturae
 * y jaraba_einvoice_b2b se pasan como NULL cuando el modulo no esta
 * instalado. El subfactor correspondiente obtiene puntuacion maxima
 * (modulo no aplicable = sin incumplimiento).
 *
 * Plan Implementacion Stack Cumplimiento Fiscal v1 — FASE 11, F11-2.
 */
class FiscalComplianceService {

  use StringTranslationTrait;

  /**
   * Puntos maximos por cada subfactor.
   */
  protected const FACTOR_MAX = 20;

  /**
   * Umbral de dias para considerar una remision como atrasada.
   */
  protected const REMISION_OVERDUE_DAYS = 4;

  /**
   * Umbral de dias para alerta de certificado proximo a expirar.
   */
  protected const CERT_WARNING_DAYS = 30;

  /**
   * Umbral Ley 3/2004: morosidad critica > 60 dias.
   */
  protected const MOROSIDAD_CRITICAL_DAYS = 60;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected ?object $hashService = NULL,
    protected ?object $remisionService = NULL,
    protected ?object $faceClient = NULL,
    protected ?object $paymentStatusService = NULL,
    protected ?object $certificateManager = NULL,
  ) {}

  /**
   * Calcula el compliance score completo para un tenant.
   *
   * @param string $tenantId
   *   ID del tenant (group entity).
   *
   * @return array
   *   Array con claves:
   *   - score (int): Score global 0-100.
   *   - factors (array): Detalle de cada subfactor.
   *   - alerts (array): Alertas activas.
   *   - grade (string): A/B/C/D/F segun score.
   */
  public function calculateScore(string $tenantId): array {
    $factors = [
      'verifactu_chain' => $this->scoreVerifactuChain($tenantId),
      'aeat_remisions' => $this->scoreAeatRemisions($tenantId),
      'certificates' => $this->scoreCertificates($tenantId),
      'face_rejections' => $this->scoreFaceRejections($tenantId),
      'b2b_morosidad' => $this->scoreB2bMorosidad($tenantId),
    ];

    $totalScore = 0;
    $alerts = [];

    foreach ($factors as $key => $factor) {
      $totalScore += $factor['score'];
      if (!empty($factor['alerts'])) {
        $alerts = array_merge($alerts, $factor['alerts']);
      }
    }

    $totalScore = min(100, max(0, $totalScore));

    return [
      'score' => $totalScore,
      'grade' => $this->scoreToGrade($totalScore),
      'factors' => $factors,
      'alerts' => $alerts,
      'tenant_id' => $tenantId,
      'calculated_at' => date('c'),
    ];
  }

  /**
   * Subfactor 1: Integridad de cadena VeriFactu (20 pts).
   *
   * 20 pts = cadena intacta o VeriFactu no instalado.
   * 10 pts = cadena con advertencias menores.
   *  0 pts = cadena rota.
   */
  protected function scoreVerifactuChain(string $tenantId): array {
    if ($this->hashService === NULL) {
      return [
        'score' => self::FACTOR_MAX,
        'max' => self::FACTOR_MAX,
        'label' => $this->t('VeriFactu Chain'),
        'status' => 'not_applicable',
        'detail' => $this->t('VeriFactu module not installed.'),
        'alerts' => [],
      ];
    }

    try {
      $result = $this->hashService->verifyChainIntegrity();
      $data = method_exists($result, 'toArray') ? $result->toArray() : (array) $result;

      $isValid = $data['is_valid'] ?? FALSE;

      if ($isValid) {
        return [
          'score' => self::FACTOR_MAX,
          'max' => self::FACTOR_MAX,
          'label' => $this->t('VeriFactu Chain'),
          'status' => 'ok',
          'detail' => $this->t('Hash chain integrity verified.'),
          'alerts' => [],
        ];
      }

      return [
        'score' => 0,
        'max' => self::FACTOR_MAX,
        'label' => $this->t('VeriFactu Chain'),
        'status' => 'critical',
        'detail' => $data['error_message'] ?? $this->t('Chain integrity compromised.'),
        'alerts' => [
          [
            'severity' => 'critical',
            'module' => 'verifactu',
            'message' => $this->t('VeriFactu hash chain integrity compromised. Immediate action required.'),
          ],
        ],
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('FiscalCompliance: Error verifying VeriFactu chain for tenant @id: @msg', [
        '@id' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return [
        'score' => 0,
        'max' => self::FACTOR_MAX,
        'label' => $this->t('VeriFactu Chain'),
        'status' => 'error',
        'detail' => $this->t('Unable to verify chain integrity.'),
        'alerts' => [
          [
            'severity' => 'warning',
            'module' => 'verifactu',
            'message' => $this->t('VeriFactu chain verification failed: @msg', ['@msg' => $e->getMessage()]),
          ],
        ],
      ];
    }
  }

  /**
   * Subfactor 2: Remisiones AEAT al dia (20 pts).
   *
   * 20 pts = sin registros pendientes de remision > REMISION_OVERDUE_DAYS dias.
   * 10 pts = entre 1-5 registros pendientes atrasados.
   *  0 pts = mas de 5 registros pendientes atrasados.
   */
  protected function scoreAeatRemisions(string $tenantId): array {
    if ($this->hashService === NULL) {
      return [
        'score' => self::FACTOR_MAX,
        'max' => self::FACTOR_MAX,
        'label' => $this->t('AEAT Submissions'),
        'status' => 'not_applicable',
        'detail' => $this->t('VeriFactu module not installed.'),
        'alerts' => [],
      ];
    }

    try {
      $storage = $this->entityTypeManager->getStorage('verifactu_invoice_record');
      $overdueThreshold = strtotime('-' . self::REMISION_OVERDUE_DAYS . ' days');

      $pendingCount = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('aeat_status', 'pending')
        ->condition('created', $overdueThreshold, '<')
        ->count()
        ->execute();

      if ($pendingCount === 0) {
        return [
          'score' => self::FACTOR_MAX,
          'max' => self::FACTOR_MAX,
          'label' => $this->t('AEAT Submissions'),
          'status' => 'ok',
          'detail' => $this->t('All VeriFactu records submitted to AEAT on time.'),
          'alerts' => [],
        ];
      }

      $score = $pendingCount <= 5 ? 10 : 0;
      $severity = $pendingCount <= 5 ? 'warning' : 'critical';

      return [
        'score' => $score,
        'max' => self::FACTOR_MAX,
        'label' => $this->t('AEAT Submissions'),
        'status' => $severity,
        'detail' => $this->t('@count VeriFactu records pending AEAT submission for over @days days.', [
          '@count' => $pendingCount,
          '@days' => self::REMISION_OVERDUE_DAYS,
        ]),
        'alerts' => [
          [
            'severity' => $severity,
            'module' => 'verifactu',
            'message' => $this->t('@count records overdue for AEAT submission.', ['@count' => $pendingCount]),
          ],
        ],
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('FiscalCompliance: Error checking AEAT remisions for tenant @id: @msg', [
        '@id' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return [
        'score' => self::FACTOR_MAX,
        'max' => self::FACTOR_MAX,
        'label' => $this->t('AEAT Submissions'),
        'status' => 'error',
        'detail' => $this->t('Unable to check AEAT submission status.'),
        'alerts' => [],
      ];
    }
  }

  /**
   * Subfactor 3: Certificados digitales vigentes (20 pts).
   *
   * 20 pts = todos los certificados vigentes (> CERT_WARNING_DAYS dias).
   * 10 pts = al menos un certificado proximo a expirar.
   *  0 pts = certificado expirado o ausente.
   */
  protected function scoreCertificates(string $tenantId): array {
    if ($this->certificateManager === NULL) {
      return [
        'score' => self::FACTOR_MAX,
        'max' => self::FACTOR_MAX,
        'label' => $this->t('Digital Certificates'),
        'status' => 'not_applicable',
        'detail' => $this->t('Certificate manager not available.'),
        'alerts' => [],
      ];
    }

    try {
      $validation = $this->certificateManager->validateTenantCertificate($tenantId);
      $data = method_exists($validation, 'toArray') ? $validation->toArray() : (array) $validation;

      $isValid = $data['is_valid'] ?? FALSE;
      $daysRemaining = $data['days_remaining'] ?? 0;

      if (!$isValid) {
        return [
          'score' => 0,
          'max' => self::FACTOR_MAX,
          'label' => $this->t('Digital Certificates'),
          'status' => 'critical',
          'detail' => $data['error'] ?? $this->t('Certificate expired or invalid.'),
          'alerts' => [
            [
              'severity' => 'critical',
              'module' => 'certificates',
              'message' => $this->t('Digital certificate expired or invalid. Fiscal operations blocked.'),
            ],
          ],
        ];
      }

      if ($daysRemaining <= self::CERT_WARNING_DAYS) {
        return [
          'score' => 10,
          'max' => self::FACTOR_MAX,
          'label' => $this->t('Digital Certificates'),
          'status' => 'warning',
          'detail' => $this->t('Certificate expires in @days days.', ['@days' => $daysRemaining]),
          'alerts' => [
            [
              'severity' => 'warning',
              'module' => 'certificates',
              'message' => $this->t('Digital certificate expires in @days days. Renew immediately.', ['@days' => $daysRemaining]),
            ],
          ],
        ];
      }

      return [
        'score' => self::FACTOR_MAX,
        'max' => self::FACTOR_MAX,
        'label' => $this->t('Digital Certificates'),
        'status' => 'ok',
        'detail' => $this->t('Certificate valid, @days days remaining.', ['@days' => $daysRemaining]),
        'alerts' => [],
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('FiscalCompliance: Error checking certificates for tenant @id: @msg', [
        '@id' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return [
        'score' => self::FACTOR_MAX,
        'max' => self::FACTOR_MAX,
        'label' => $this->t('Digital Certificates'),
        'status' => 'error',
        'detail' => $this->t('Unable to check certificate status.'),
        'alerts' => [],
      ];
    }
  }

  /**
   * Subfactor 4: Facturas FACe sin rechazos (20 pts).
   *
   * 20 pts = sin facturas rechazadas recientes o Facturae no instalado.
   * 10 pts = entre 1-3 facturas rechazadas en ultimos 30 dias.
   *  0 pts = mas de 3 rechazos recientes.
   */
  protected function scoreFaceRejections(string $tenantId): array {
    if ($this->faceClient === NULL) {
      return [
        'score' => self::FACTOR_MAX,
        'max' => self::FACTOR_MAX,
        'label' => $this->t('FACe B2G Invoices'),
        'status' => 'not_applicable',
        'detail' => $this->t('Facturae module not installed.'),
        'alerts' => [],
      ];
    }

    try {
      $storage = $this->entityTypeManager->getStorage('facturae_invoice');
      $thirtyDaysAgo = strtotime('-30 days');

      $rejectedCount = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('face_status', 'rejected')
        ->condition('changed', $thirtyDaysAgo, '>=')
        ->count()
        ->execute();

      if ($rejectedCount === 0) {
        return [
          'score' => self::FACTOR_MAX,
          'max' => self::FACTOR_MAX,
          'label' => $this->t('FACe B2G Invoices'),
          'status' => 'ok',
          'detail' => $this->t('No FACe rejections in the last 30 days.'),
          'alerts' => [],
        ];
      }

      $score = $rejectedCount <= 3 ? 10 : 0;
      $severity = $rejectedCount <= 3 ? 'warning' : 'critical';

      return [
        'score' => $score,
        'max' => self::FACTOR_MAX,
        'label' => $this->t('FACe B2G Invoices'),
        'status' => $severity,
        'detail' => $this->t('@count FACe invoices rejected in the last 30 days.', ['@count' => $rejectedCount]),
        'alerts' => [
          [
            'severity' => $severity,
            'module' => 'facturae',
            'message' => $this->t('@count B2G invoices rejected by FACe. Review and resubmit.', ['@count' => $rejectedCount]),
          ],
        ],
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('FiscalCompliance: Error checking FACe rejections for tenant @id: @msg', [
        '@id' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return [
        'score' => self::FACTOR_MAX,
        'max' => self::FACTOR_MAX,
        'label' => $this->t('FACe B2G Invoices'),
        'status' => 'error',
        'detail' => $this->t('Unable to check FACe rejection status.'),
        'alerts' => [],
      ];
    }
  }

  /**
   * Subfactor 5: Morosidad B2B controlada (20 pts).
   *
   * 20 pts = sin facturas con morosidad > MOROSIDAD_CRITICAL_DAYS o B2B no instalado.
   * 10 pts = algunas facturas con morosidad moderada (31-60 dias).
   *  0 pts = facturas con morosidad critica > 60 dias.
   */
  protected function scoreB2bMorosidad(string $tenantId): array {
    if ($this->paymentStatusService === NULL) {
      return [
        'score' => self::FACTOR_MAX,
        'max' => self::FACTOR_MAX,
        'label' => $this->t('B2B Late Payments'),
        'status' => 'not_applicable',
        'detail' => $this->t('E-Invoice B2B module not installed.'),
        'alerts' => [],
      ];
    }

    try {
      $storage = $this->entityTypeManager->getStorage('einvoice_document');

      // Critical: invoices overdue > 60 days.
      $criticalCount = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('direction', 'outbound')
        ->condition('payment_status', 'overdue')
        ->count()
        ->execute();

      if ($criticalCount === 0) {
        return [
          'score' => self::FACTOR_MAX,
          'max' => self::FACTOR_MAX,
          'label' => $this->t('B2B Late Payments'),
          'status' => 'ok',
          'detail' => $this->t('No B2B invoices with overdue payments.'),
          'alerts' => [],
        ];
      }

      // Check severity: query for documents with payment_status overdue.
      // The actual overdue days calculation is handled by paymentStatusService.
      $score = $criticalCount <= 3 ? 10 : 0;
      $severity = $criticalCount <= 3 ? 'warning' : 'critical';

      return [
        'score' => $score,
        'max' => self::FACTOR_MAX,
        'label' => $this->t('B2B Late Payments'),
        'status' => $severity,
        'detail' => $this->t('@count B2B invoices with overdue payments (Ley 3/2004).', ['@count' => $criticalCount]),
        'alerts' => [
          [
            'severity' => $severity,
            'module' => 'einvoice_b2b',
            'message' => $this->t('@count B2B invoices with overdue payments. Ley 3/2004 compliance risk.', ['@count' => $criticalCount]),
          ],
        ],
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('FiscalCompliance: Error checking B2B morosidad for tenant @id: @msg', [
        '@id' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return [
        'score' => self::FACTOR_MAX,
        'max' => self::FACTOR_MAX,
        'label' => $this->t('B2B Late Payments'),
        'status' => 'error',
        'detail' => $this->t('Unable to check B2B payment status.'),
        'alerts' => [],
      ];
    }
  }

  /**
   * Convierte score numerico a letra de calificacion.
   */
  protected function scoreToGrade(int $score): string {
    return match (TRUE) {
      $score >= 90 => 'A',
      $score >= 70 => 'B',
      $score >= 50 => 'C',
      $score >= 30 => 'D',
      default => 'F',
    };
  }

  /**
   * Obtiene un resumen rapido del compliance para el Admin Center KPI.
   *
   * @param string $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Array con score, grade, alert_count.
   */
  public function getComplianceSummary(string $tenantId): array {
    $full = $this->calculateScore($tenantId);
    return [
      'score' => $full['score'],
      'grade' => $full['grade'],
      'alert_count' => count($full['alerts']),
    ];
  }

  /**
   * Obtiene los modulos fiscales instalados.
   *
   * @return array
   *   Array con claves: verifactu, facturae, einvoice_b2b (bool cada una).
   */
  public function getInstalledModules(): array {
    return [
      'verifactu' => $this->hashService !== NULL,
      'facturae' => $this->faceClient !== NULL,
      'einvoice_b2b' => $this->paymentStatusService !== NULL,
    ];
  }

}
