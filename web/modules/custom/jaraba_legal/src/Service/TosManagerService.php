<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_legal\Entity\ServiceAgreement;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestion de Terms of Service.
 *
 * ESTRUCTURA:
 * Gestiona el ciclo de vida de los ToS: creacion de versiones, publicacion,
 * control de aceptacion por tenant y re-aceptacion obligatoria.
 *
 * LOGICA DE NEGOCIO:
 * - Crear nueva version de ToS con hash SHA-256 del contenido.
 * - Publicar version y marcar como activa (desactivando anteriores).
 * - Verificar si un tenant ha aceptado la version activa.
 * - Forzar re-aceptacion cuando cambia la version (si esta configurado).
 * - Enviar notificaciones de nuevas versiones a todos los tenants.
 *
 * RELACIONES:
 * - Depende de TenantContextService para aislamiento multi-tenant.
 * - Interactua con ServiceAgreement entity para persistencia.
 *
 * Spec: Doc 184 §3.1. Plan: FASE 5, Stack Compliance Legal N1.
 */
class TosManagerService {

  /**
   * Nombre de la configuracion del modulo.
   */
  const CONFIG_NAME = 'jaraba_legal.settings';

  /**
   * Constructor del servicio.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected TenantContextService $tenantContext,
    protected ConfigFactoryInterface $configFactory,
    protected MailManagerInterface $mailManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Crea una nueva version de ToS con hash SHA-256 del contenido.
   *
   * Genera un ServiceAgreement de tipo 'tos' en estado borrador
   * con el contenido HTML proporcionado y su hash de integridad.
   *
   * @param int $tenant_id
   *   ID del tenant (Group entity).
   * @param string $content
   *   Contenido HTML del Terms of Service.
   * @param string $version
   *   Identificador de version (ej: '1.0', '2.0').
   *
   * @return \Drupal\jaraba_legal\Entity\ServiceAgreement
   *   Entidad ServiceAgreement creada.
   *
   * @throws \InvalidArgumentException
   *   Si el tenant no existe.
   */
  public function createVersion(int $tenant_id, string $content, string $version): ServiceAgreement {
    // Verificar que el tenant existe.
    $tenant = $this->entityTypeManager->getStorage('group')->load($tenant_id);
    if (!$tenant) {
      throw new \InvalidArgumentException(
        (string) new TranslatableMarkup('El tenant con ID @id no existe.', ['@id' => $tenant_id])
      );
    }

    // Calcular hash SHA-256 del contenido para integridad.
    $contentHash = hash('sha256', $content);

    $storage = $this->entityTypeManager->getStorage('service_agreement');

    /** @var \Drupal\jaraba_legal\Entity\ServiceAgreement $agreement */
    $agreement = $storage->create([
      'tenant_id' => $tenant_id,
      'title' => 'Terms of Service v' . $version,
      'agreement_type' => 'tos',
      'version' => $version,
      'content_html' => [
        'value' => $content,
        'format' => 'full_html',
      ],
      'content_hash' => $contentHash,
      'is_active' => FALSE,
      'requires_acceptance' => TRUE,
      'accepted_count' => 0,
    ]);

    $agreement->save();

    $this->logger->info('ToS v@version creado para tenant @tenant (hash: @hash).', [
      '@version' => $version,
      '@tenant' => $tenant->label(),
      '@hash' => substr($contentHash, 0, 16) . '...',
    ]);

    return $agreement;
  }

  /**
   * Publica una version de ToS, desactivando las versiones anteriores.
   *
   * Marca la version especificada como activa y establece la fecha
   * de publicacion. Todas las versiones anteriores del mismo tipo
   * y tenant se desactivan automaticamente.
   *
   * @param int $tos_id
   *   ID de la entidad ServiceAgreement a publicar.
   *
   * @return \Drupal\jaraba_legal\Entity\ServiceAgreement
   *   Entidad ServiceAgreement publicada.
   *
   * @throws \RuntimeException
   *   Si el acuerdo no existe o no es de tipo ToS.
   */
  public function publishVersion(int $tos_id): ServiceAgreement {
    $storage = $this->entityTypeManager->getStorage('service_agreement');

    /** @var \Drupal\jaraba_legal\Entity\ServiceAgreement|null $agreement */
    $agreement = $storage->load($tos_id);

    if (!$agreement) {
      throw new \RuntimeException(
        (string) new TranslatableMarkup('El acuerdo con ID @id no existe.', ['@id' => $tos_id])
      );
    }

    if ($agreement->get('agreement_type')->value !== 'tos') {
      throw new \RuntimeException(
        (string) new TranslatableMarkup('El acuerdo con ID @id no es de tipo ToS.', ['@id' => $tos_id])
      );
    }

    $tenantId = (int) $agreement->get('tenant_id')->target_id;

    // Desactivar versiones anteriores del mismo tenant y tipo.
    $previousIds = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenantId)
      ->condition('agreement_type', 'tos')
      ->condition('is_active', TRUE)
      ->condition('id', $tos_id, '!=')
      ->execute();

    foreach ($storage->loadMultiple($previousIds) as $previous) {
      $previous->set('is_active', FALSE);
      $previous->save();
    }

    // Publicar la nueva version.
    $agreement->set('is_active', TRUE);
    $agreement->set('published_at', time());
    $agreement->set('effective_date', time());
    $agreement->set('accepted_count', 0);
    $agreement->save();

    $this->logger->info('ToS v@version publicado para tenant @tenant.', [
      '@version' => $agreement->get('version')->value,
      '@tenant' => $tenantId,
    ]);

    return $agreement;
  }

  /**
   * Registra la aceptacion de ToS por un tenant.
   *
   * Almacena en el state de Drupal la aceptacion con timestamp, IP,
   * user ID y version del ToS aceptado. Incrementa el contador de
   * aceptaciones en la entidad.
   *
   * @param int $tenant_id
   *   ID del tenant (Group entity).
   * @param int $user_id
   *   ID del usuario que acepta.
   * @param string $ip_address
   *   Direccion IP desde la que se acepta.
   *
   * @return array
   *   Datos de la aceptacion registrada.
   *
   * @throws \RuntimeException
   *   Si no hay una version activa de ToS.
   */
  public function acceptToS(int $tenant_id, int $user_id, string $ip_address): array {
    $activeToS = $this->getActiveVersion($tenant_id);
    if (!$activeToS) {
      throw new \RuntimeException(
        (string) new TranslatableMarkup('No hay una version activa de ToS para aceptar.')
      );
    }

    $tosId = (int) $activeToS->id();
    $tosVersion = $activeToS->get('version')->value;
    $now = time();

    // Registrar la aceptacion en state (clave unica por tenant + tos).
    $acceptanceKey = "jaraba_legal.tos_acceptance.{$tenant_id}.{$tosId}";
    $acceptanceData = [
      'tenant_id' => $tenant_id,
      'user_id' => $user_id,
      'tos_id' => $tosId,
      'tos_version' => $tosVersion,
      'content_hash' => $activeToS->get('content_hash')->value,
      'ip_address' => $ip_address,
      'accepted_at' => $now,
    ];

    \Drupal::state()->set($acceptanceKey, $acceptanceData);

    // Incrementar contador de aceptaciones.
    $currentCount = (int) $activeToS->get('accepted_count')->value;
    $activeToS->set('accepted_count', $currentCount + 1);
    $activeToS->save();

    $this->logger->info('ToS v@version aceptado por usuario @user (tenant @tenant, IP @ip).', [
      '@version' => $tosVersion,
      '@user' => $user_id,
      '@tenant' => $tenant_id,
      '@ip' => $ip_address,
    ]);

    return $acceptanceData;
  }

  /**
   * Verifica si un tenant ha aceptado la version activa de ToS.
   *
   * Compara la version activa actual con el registro de aceptacion
   * almacenado en state para este tenant.
   *
   * @param int $tenant_id
   *   ID del tenant (Group entity).
   *
   * @return array
   *   Array con claves 'accepted' (bool), 'active_version', 'accepted_version'.
   */
  public function checkAcceptance(int $tenant_id): array {
    $activeToS = $this->getActiveVersion($tenant_id);

    if (!$activeToS) {
      return [
        'accepted' => FALSE,
        'active_version' => NULL,
        'accepted_version' => NULL,
        'message' => 'No hay version activa de ToS.',
      ];
    }

    $tosId = (int) $activeToS->id();
    $acceptanceKey = "jaraba_legal.tos_acceptance.{$tenant_id}.{$tosId}";
    $acceptance = \Drupal::state()->get($acceptanceKey);

    if (!$acceptance) {
      return [
        'accepted' => FALSE,
        'active_version' => $activeToS->get('version')->value,
        'accepted_version' => NULL,
        'message' => 'El tenant no ha aceptado la version activa de ToS.',
      ];
    }

    return [
      'accepted' => TRUE,
      'active_version' => $activeToS->get('version')->value,
      'accepted_version' => $acceptance['tos_version'],
      'accepted_at' => $acceptance['accepted_at'],
      'accepted_by' => $acceptance['user_id'],
    ];
  }

  /**
   * Obtiene la version activa de ToS para un tenant.
   *
   * Busca el ServiceAgreement de tipo 'tos' marcado como activo
   * para el tenant especificado.
   *
   * @param int|null $tenant_id
   *   ID del tenant. Si es NULL, se usa el tenant del contexto actual.
   *
   * @return \Drupal\jaraba_legal\Entity\ServiceAgreement|null
   *   La version activa o NULL si no existe.
   */
  public function getActiveVersion(?int $tenant_id = NULL): ?ServiceAgreement {
    // Si no se proporciona tenant_id, intentar resolverlo del contexto.
    if ($tenant_id === NULL) {
      $tenant_id = $this->tenantContext->getCurrentTenantId();
    }

    if (!$tenant_id) {
      return NULL;
    }

    $storage = $this->entityTypeManager->getStorage('service_agreement');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenant_id)
      ->condition('agreement_type', 'tos')
      ->condition('is_active', TRUE)
      ->sort('published_at', 'DESC')
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    /** @var \Drupal\jaraba_legal\Entity\ServiceAgreement $agreement */
    $agreement = $storage->load(reset($ids));
    return $agreement;
  }

  /**
   * Notifica a todos los tenants de una nueva version de ToS.
   *
   * Envia un email a los administradores de todos los tenants activos
   * informando que hay una nueva version de ToS que requiere aceptacion.
   *
   * @return int
   *   Numero de notificaciones enviadas.
   */
  public function notifyNewVersion(): int {
    $config = $this->configFactory->get(self::CONFIG_NAME);
    $notificationsEnabled = $config->get('tos_notifications_enabled') ?? TRUE;

    if (!$notificationsEnabled) {
      $this->logger->info('Notificaciones de ToS deshabilitadas por configuracion.');
      return 0;
    }

    // Obtener todos los grupos (tenants).
    $groupStorage = $this->entityTypeManager->getStorage('group');
    $groupIds = $groupStorage->getQuery()
      ->accessCheck(FALSE)
      ->execute();

    $notified = 0;
    $langcode = 'es';

    foreach ($groupStorage->loadMultiple($groupIds) as $group) {
      $tenantId = (int) $group->id();
      $activeToS = $this->getActiveVersion($tenantId);

      if (!$activeToS) {
        continue;
      }

      // Buscar el administrador del tenant para enviar la notificacion.
      try {
        $membershipLoader = \Drupal::service('group.membership_loader');
        $memberships = $membershipLoader->loadByGroup($group);

        foreach ($memberships as $membership) {
          $member = $membership->getUser();
          if ($member && $member->getEmail()) {
            $params = [
              'subject' => (string) new TranslatableMarkup('Nueva version de Terms of Service disponible'),
              'tos_version' => $activeToS->get('version')->value,
              'tenant_name' => $group->label(),
            ];

            $this->mailManager->mail(
              'jaraba_legal',
              'tos_new_version',
              $member->getEmail(),
              $langcode,
              $params,
            );
            $notified++;
            // Solo notificar al primer miembro (admin) del grupo.
            break;
          }
        }
      }
      catch (\Exception $e) {
        $this->logger->warning('Error notificando a tenant @tenant: @error', [
          '@tenant' => $tenantId,
          '@error' => $e->getMessage(),
        ]);
      }
    }

    $this->logger->info('Notificaciones de nueva version ToS enviadas: @count.', [
      '@count' => $notified,
    ]);

    return $notified;
  }

}
