<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials_emprendimiento\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\jaraba_credentials\Entity\IssuedCredential;
use Drupal\jaraba_credentials\Service\CredentialIssuer;
use Psr\Log\LoggerInterface;

/**
 * Servicio de emisión de credenciales para emprendimiento.
 */
class EmprendimientoCredentialService {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected CredentialIssuer $credentialIssuer;
  protected LoggerInterface $logger;

  /**
   * Machine names de los templates de emprendimiento.
   */
  public const TEMPLATES = [
    'diagnostico_completado',
    'madurez_digital_basica',
    'madurez_digital_intermedia',
    'madurez_digital_avanzada',
    'business_canvas_creator',
    'business_canvas_validated',
    'financial_architect',
    'pitch_ready',
    'mvp_launched',
    'mvp_validated',
    'first_sale',
    'first_mentoring_session',
    'emprendedor_digital_basico',
    'emprendedor_digital_avanzado',
    'transformador_digital_expert',
  ];

  /**
   * Umbrales de madurez digital.
   */
  public const MATURITY_THRESHOLDS = [
    'madurez_digital_basica' => 30,
    'madurez_digital_intermedia' => 60,
    'madurez_digital_avanzada' => 85,
  ];

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    CredentialIssuer $credentialIssuer,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->credentialIssuer = $credentialIssuer;
    $this->logger = $loggerFactory->get('jaraba_credentials_emprendimiento');
  }

  /**
   * Emite badge por diagnóstico empresarial completado.
   */
  public function issueBadgeForDiagnostic(int $uid, object $diagnostic): ?IssuedCredential {
    return $this->issueByMachineName('diagnostico_completado', $uid, [
      'diagnostic_id' => $diagnostic->id(),
    ]);
  }

  /**
   * Emite badges de madurez digital según score.
   */
  public function issueMaturityBadges(int $uid, int $score): array {
    $issued = [];
    foreach (self::MATURITY_THRESHOLDS as $machineName => $threshold) {
      if ($score >= $threshold) {
        $credential = $this->issueByMachineName($machineName, $uid, [
          'maturity_score' => $score,
        ]);
        if ($credential) {
          $issued[] = $credential;
        }
      }
    }
    return $issued;
  }

  /**
   * Emite badge por Business Canvas.
   */
  public function issueBadgeForCanvas(int $uid, object $canvas): ?IssuedCredential {
    $status = $canvas->get('status')->value ?? '';
    $machineName = $status === 'validated' ? 'business_canvas_validated' : 'business_canvas_creator';
    return $this->issueByMachineName($machineName, $uid, [
      'canvas_id' => $canvas->id(),
    ]);
  }

  /**
   * Emite badge por MVP.
   */
  public function issueBadgeForMvp(int $uid, object $mvp): ?IssuedCredential {
    return $this->issueByMachineName('mvp_launched', $uid, [
      'mvp_id' => $mvp->id(),
    ]);
  }

  /**
   * Emite badge por primera venta.
   */
  public function issueBadgeForSale(int $uid, object $order): ?IssuedCredential {
    return $this->issueByMachineName('first_sale', $uid, [
      'order_id' => $order->id(),
    ]);
  }

  /**
   * Emite badge por sesión de mentoría.
   */
  public function issueBadgeForMentoring(int $uid, object $session): ?IssuedCredential {
    return $this->issueByMachineName('first_mentoring_session', $uid, [
      'session_id' => $session->id(),
    ]);
  }

  /**
   * Evalúa si el usuario califica para diplomas compuestos.
   */
  public function evaluateDiplomas(int $uid): array {
    $issued = [];
    $userTemplates = $this->getUserEmprendimientoTemplates($uid);

    // Emprendedor Digital Básico: diagnóstico + canvas + madurez básica.
    $basicReqs = ['diagnostico_completado', 'business_canvas_creator', 'madurez_digital_basica'];
    if ($this->hasAllTemplates($userTemplates, $basicReqs)) {
      $credential = $this->issueByMachineName('emprendedor_digital_basico', $uid, [
        'diploma_type' => 'basic',
      ]);
      if ($credential) {
        $issued[] = $credential;
      }
    }

    // Emprendedor Digital Avanzado: básico + MVP + financial + madurez intermedia.
    $advancedReqs = ['emprendedor_digital_basico', 'mvp_launched', 'financial_architect', 'madurez_digital_intermedia'];
    if ($this->hasAllTemplates($userTemplates, $advancedReqs)) {
      $credential = $this->issueByMachineName('emprendedor_digital_avanzado', $uid, [
        'diploma_type' => 'advanced',
      ]);
      if ($credential) {
        $issued[] = $credential;
      }
    }

    // Transformador Digital Expert: avanzado + validaciones + madurez avanzada.
    $expertReqs = ['emprendedor_digital_avanzado', 'business_canvas_validated', 'mvp_validated', 'madurez_digital_avanzada'];
    if ($this->hasAllTemplates($userTemplates, $expertReqs)) {
      $credential = $this->issueByMachineName('transformador_digital_expert', $uid, [
        'diploma_type' => 'expert',
      ]);
      if ($credential) {
        $issued[] = $credential;
      }
    }

    return $issued;
  }

  /**
   * Emite credencial por machine_name del template.
   */
  protected function issueByMachineName(string $machineName, int $uid, array $context = []): ?IssuedCredential {
    $template = $this->findTemplateByMachineName($machineName);
    if (!$template) {
      $this->logger->warning('Template @name no encontrado.', ['@name' => $machineName]);
      return NULL;
    }

    // Verificar duplicado.
    if ($this->hasCredentialForTemplate($uid, (int) $template->id())) {
      return NULL;
    }

    try {
      $credential = $this->credentialIssuer->issueCredential($template, $uid, $context);
      $this->logger->info('Badge @name emitido para usuario #@uid', [
        '@name' => $machineName,
        '@uid' => $uid,
      ]);
      return $credential;
    }
    catch (\Exception $e) {
      $this->logger->error('Error emitiendo @name: @msg', [
        '@name' => $machineName,
        '@msg' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Busca template por machine_name.
   */
  protected function findTemplateByMachineName(string $machineName): ?object {
    $templates = $this->entityTypeManager->getStorage('credential_template')
      ->loadByProperties(['machine_name' => $machineName]);
    return $templates ? reset($templates) : NULL;
  }

  /**
   * Verifica si el usuario ya tiene credencial para un template.
   */
  protected function hasCredentialForTemplate(int $uid, int $templateId): bool {
    $count = $this->entityTypeManager->getStorage('issued_credential')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('recipient_id', $uid)
      ->condition('template_id', $templateId)
      ->condition('status', IssuedCredential::STATUS_ACTIVE)
      ->count()
      ->execute();
    return $count > 0;
  }

  /**
   * Obtiene machine_names de templates de emprendimiento del usuario.
   */
  public function getUserEmprendimientoTemplates(int $uid): array {
    $ids = $this->entityTypeManager->getStorage('issued_credential')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('recipient_id', $uid)
      ->condition('status', IssuedCredential::STATUS_ACTIVE)
      ->execute();

    if (empty($ids)) {
      return [];
    }

    $credentials = $this->entityTypeManager->getStorage('issued_credential')->loadMultiple($ids);
    $machineNames = [];
    foreach ($credentials as $credential) {
      $templateId = $credential->get('template_id')->target_id ?? NULL;
      if ($templateId) {
        $template = $this->entityTypeManager->getStorage('credential_template')->load($templateId);
        if ($template) {
          $mn = $template->get('machine_name')->value ?? '';
          if (in_array($mn, self::TEMPLATES, TRUE)) {
            $machineNames[] = $mn;
          }
        }
      }
    }

    return array_unique($machineNames);
  }

  /**
   * Verifica si el usuario tiene todos los templates requeridos.
   */
  protected function hasAllTemplates(array $userTemplates, array $required): bool {
    return empty(array_diff($required, $userTemplates));
  }

}
