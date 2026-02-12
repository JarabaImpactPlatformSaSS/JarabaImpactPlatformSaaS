<?php

declare(strict_types=1);

namespace Drupal\jaraba_pixels\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Gestiona el consentimiento de visitantes para tracking de pixels.
 *
 * Centraliza la lógica de registro, consulta y revocación de
 * consentimiento RGPD/ePrivacy por visitante, tenant y categoría.
 * Todas las operaciones se realizan sobre la entidad consent_record.
 */
class ConsentManagementService {

  /**
   * Constructor del servicio.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para acceder al storage de consent_record.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger para registrar operaciones de consentimiento.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Registra un consentimiento para un visitante.
   *
   * Crea una nueva entidad consent_record con el estado y contexto
   * proporcionados. Si ya existe un registro para el mismo visitante,
   * tenant y tipo, crea uno nuevo (historial auditable).
   *
   * @param string $visitorId
   *   Identificador único del visitante.
   * @param int $tenantId
   *   ID del tenant (grupo).
   * @param string $consentType
   *   Tipo de consentimiento (necessary, analytics, marketing, personalization).
   * @param string $status
   *   Estado del consentimiento (granted, denied, withdrawn).
   * @param array $context
   *   Contexto adicional: ip_address, user_agent, consent_version.
   *
   * @return bool
   *   TRUE si se registró correctamente, FALSE en caso de error.
   */
  public function recordConsent(string $visitorId, int $tenantId, string $consentType, string $status, array $context = []): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('consent_record');

      $values = [
        'visitor_id' => $visitorId,
        'tenant_id' => $tenantId,
        'consent_type' => $consentType,
        'status' => $status,
      ];

      // Añadir datos de contexto opcionales.
      if (!empty($context['ip_address'])) {
        $values['ip_address'] = $context['ip_address'];
      }
      if (!empty($context['user_agent'])) {
        $values['user_agent'] = $context['user_agent'];
      }
      if (!empty($context['consent_version'])) {
        $values['consent_version'] = $context['consent_version'];
      }

      $entity = $storage->create($values);
      $entity->save();

      $this->logger->info('Consentimiento registrado: visitor=@visitor, tenant=@tenant, tipo=@type, estado=@status', [
        '@visitor' => $visitorId,
        '@tenant' => $tenantId,
        '@type' => $consentType,
        '@status' => $status,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error registrando consentimiento para visitor @visitor: @error', [
        '@visitor' => $visitorId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Obtiene todos los registros de consentimiento de un visitante en un tenant.
   *
   * Devuelve el registro más reciente por cada tipo de consentimiento,
   * permitiendo conocer el estado actual consolidado del visitante.
   *
   * @param string $visitorId
   *   Identificador único del visitante.
   * @param int $tenantId
   *   ID del tenant (grupo).
   *
   * @return array
   *   Array asociativo con tipo de consentimiento como clave y array
   *   con 'status', 'consent_version' y 'created' como valor.
   *   Vacío si no hay registros.
   */
  public function getConsent(string $visitorId, int $tenantId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('consent_record');
      $entities = $storage->loadByProperties([
        'visitor_id' => $visitorId,
        'tenant_id' => $tenantId,
      ]);

      if (empty($entities)) {
        return [];
      }

      // Consolidar por tipo: mantener el registro más reciente de cada tipo.
      $consents = [];
      foreach ($entities as $entity) {
        $type = $entity->get('consent_type')->value;
        $created = (int) $entity->get('created')->value;

        if (!isset($consents[$type]) || $created > $consents[$type]['created']) {
          $consents[$type] = [
            'status' => $entity->get('status')->value,
            'consent_version' => $entity->get('consent_version')->value ?? '',
            'created' => $created,
          ];
        }
      }

      return $consents;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo consentimiento para visitor @visitor: @error', [
        '@visitor' => $visitorId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Revoca el consentimiento de un visitante para un tipo específico.
   *
   * Crea un nuevo registro con estado 'withdrawn' y marca la fecha
   * de revocación. No elimina registros anteriores para mantener
   * el historial auditable según RGPD.
   *
   * @param string $visitorId
   *   Identificador único del visitante.
   * @param int $tenantId
   *   ID del tenant (grupo).
   * @param string $consentType
   *   Tipo de consentimiento a revocar.
   *
   * @return bool
   *   TRUE si se revocó correctamente, FALSE en caso de error.
   */
  public function revokeConsent(string $visitorId, int $tenantId, string $consentType): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('consent_record');

      $entity = $storage->create([
        'visitor_id' => $visitorId,
        'tenant_id' => $tenantId,
        'consent_type' => $consentType,
        'status' => 'withdrawn',
        'revoked_at' => time(),
      ]);
      $entity->save();

      $this->logger->info('Consentimiento revocado: visitor=@visitor, tenant=@tenant, tipo=@type', [
        '@visitor' => $visitorId,
        '@tenant' => $tenantId,
        '@type' => $consentType,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error revocando consentimiento para visitor @visitor: @error', [
        '@visitor' => $visitorId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Comprueba si un visitante tiene consentimiento activo para un tipo.
   *
   * Evalúa el estado más reciente del consentimiento del visitante
   * para el tipo y tenant indicados. Devuelve TRUE solo si el
   * estado más reciente es 'granted'.
   *
   * @param string $visitorId
   *   Identificador único del visitante.
   * @param int $tenantId
   *   ID del tenant (grupo).
   * @param string $consentType
   *   Tipo de consentimiento a verificar.
   *
   * @return bool
   *   TRUE si el consentimiento está activo (granted), FALSE en otro caso.
   */
  public function hasConsent(string $visitorId, int $tenantId, string $consentType): bool {
    $consents = $this->getConsent($visitorId, $tenantId);

    if (!isset($consents[$consentType])) {
      return FALSE;
    }

    return $consents[$consentType]['status'] === 'granted';
  }

}
