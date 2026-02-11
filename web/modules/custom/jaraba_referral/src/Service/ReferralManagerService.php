<?php

namespace Drupal\jaraba_referral\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_referral\Entity\Referral;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestión del programa de referidos.
 *
 * ESTRUCTURA:
 * Servicio central que orquesta la generación de códigos de referido,
 * procesamiento de referidos entrantes, consulta de referidos por usuario
 * y estadísticas agregadas por tenant. Depende de EntityTypeManager
 * para CRUD de entidades, TenantContextService para aislamiento
 * multi-tenant, y del canal de log dedicado.
 *
 * LÓGICA:
 * El flujo de referidos sigue estas reglas de negocio:
 * 1. Cada usuario puede generar UN código único de 8 caracteres alfanuméricos.
 * 2. El código se genera con random_bytes para seguridad criptográfica.
 * 3. Cuando un referido usa el código, se crea una entidad Referral en estado
 *    'pending' que pasa a 'confirmed' cuando completa el registro.
 * 4. Las recompensas se otorgan cuando el admin marca el referido como 'rewarded'.
 * 5. Los referidos expiran tras un periodo configurable.
 *
 * RELACIONES:
 * - ReferralManagerService -> EntityTypeManager (dependencia)
 * - ReferralManagerService -> TenantContextService (dependencia)
 * - ReferralManagerService -> Referral entity (gestiona)
 * - ReferralManagerService <- ReferralFrontendController (consumido por)
 * - ReferralManagerService <- ReferralApiController (consumido por)
 *
 * @package Drupal\jaraba_referral\Service
 */
class ReferralManagerService {

  /**
   * Gestor de tipos de entidad de Drupal.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Servicio de contexto de tenant para aislamiento multi-tenant.
   *
   * @var object
   */
  protected $tenantContext;

  /**
   * Canal de log dedicado para el módulo de referidos.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructor del servicio de gestión de referidos.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Gestor de tipos de entidad para acceso a storage de entidades.
   * @param object $tenant_context
   *   Servicio de contexto de tenant para filtrado multi-tenant.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de log dedicado para trazar operaciones del módulo.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    $tenant_context,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->tenantContext = $tenant_context;
    $this->logger = $logger;
  }

  /**
   * Genera un código de referido único para un usuario.
   *
   * ESTRUCTURA: Método público principal para generar códigos.
   *
   * LÓGICA: Genera una cadena alfanumérica de 8 caracteres usando
   *   random_bytes para seguridad criptográfica. Verifica unicidad
   *   contra la base de datos antes de devolver el código.
   *   Si el usuario ya tiene un código activo, lo devuelve.
   *
   * RELACIONES: Consume Referral storage para verificar unicidad.
   *
   * @param int $uid
   *   ID del usuario que genera el código.
   *
   * @return \Drupal\jaraba_referral\Entity\Referral
   *   La entidad Referral creada con el código generado.
   */
  public function generateCode(int $uid): Referral {
    $storage = $this->entityTypeManager->getStorage('referral');

    // Verificar si ya tiene un código activo.
    $existing = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('referrer_uid', $uid)
      ->condition('status', ['pending', 'confirmed'], 'IN')
      ->condition('referred_uid', NULL, 'IS NULL')
      ->range(0, 1)
      ->execute();

    if (!empty($existing)) {
      $existing_id = reset($existing);
      /** @var \Drupal\jaraba_referral\Entity\Referral $existing_referral */
      $existing_referral = $storage->load($existing_id);
      if ($existing_referral) {
        return $existing_referral;
      }
    }

    // Generar código único de 8 caracteres alfanuméricos.
    $code = $this->generateUniqueCode();

    // Obtener tenant_id del contexto actual.
    $tenant_id = NULL;
    if (method_exists($this->tenantContext, 'getCurrentTenantId')) {
      $tenant_id = $this->tenantContext->getCurrentTenantId();
    }

    /** @var \Drupal\jaraba_referral\Entity\Referral $referral */
    $referral = $storage->create([
      'referrer_uid' => $uid,
      'referral_code' => $code,
      'status' => 'pending',
      'reward_type' => 'discount',
      'reward_value' => 0,
      'tenant_id' => $tenant_id,
    ]);

    $referral->save();

    $this->logger->info('Código de referido generado: @code para usuario #@uid', [
      '@code' => $code,
      '@uid' => $uid,
    ]);

    return $referral;
  }

  /**
   * Procesa un referido cuando un nuevo usuario usa un código.
   *
   * ESTRUCTURA: Método público para registrar un referido entrante.
   *
   * LÓGICA: Busca el código de referido, valida que no se haya referido
   *   a sí mismo, crea la vinculación con el usuario referido y
   *   actualiza el estado a 'confirmed'.
   *
   * RELACIONES: Consume Referral storage, actualiza entidad Referral.
   *
   * @param string $code
   *   Código de referido de 8 caracteres.
   * @param int $referred_uid
   *   ID del usuario referido que se registró.
   *
   * @return \Drupal\jaraba_referral\Entity\Referral|null
   *   La entidad Referral actualizada, o NULL si el código no existe.
   *
   * @throws \RuntimeException
   *   Si el código no es válido o el usuario intenta referirse a sí mismo.
   */
  public function processReferral(string $code, int $referred_uid): ?Referral {
    $storage = $this->entityTypeManager->getStorage('referral');

    $referrals = $storage->loadByProperties([
      'referral_code' => $code,
      'status' => 'pending',
    ]);

    if (empty($referrals)) {
      throw new \RuntimeException('Código de referido no válido o ya utilizado.');
    }

    /** @var \Drupal\jaraba_referral\Entity\Referral $referral */
    $referral = reset($referrals);

    // Validar que no se refiere a sí mismo.
    if ((int) $referral->get('referrer_uid')->target_id === $referred_uid) {
      throw new \RuntimeException('No puedes usar tu propio código de referido.');
    }

    // Verificar que este usuario no fue ya referido.
    $already_referred = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('referred_uid', $referred_uid)
      ->condition('status', ['confirmed', 'rewarded'], 'IN')
      ->count()
      ->execute();

    if ((int) $already_referred > 0) {
      throw new \RuntimeException('Este usuario ya fue referido anteriormente.');
    }

    $referral->set('referred_uid', $referred_uid);
    $referral->set('status', 'confirmed');
    $referral->save();

    $this->logger->info('Referido procesado: código @code, referido usuario #@referred por usuario #@referrer', [
      '@code' => $code,
      '@referred' => $referred_uid,
      '@referrer' => $referral->get('referrer_uid')->target_id,
    ]);

    return $referral;
  }

  /**
   * Obtiene los referidos de un usuario.
   *
   * ESTRUCTURA: Método público de consulta de referidos personales.
   *
   * LÓGICA: Consulta todos los referidos donde el usuario es el referidor,
   *   ordenados por fecha de creación descendente.
   *
   * RELACIONES: Consume Referral storage.
   *
   * @param int $uid
   *   ID del usuario referidor.
   *
   * @return array
   *   Array de entidades Referral del usuario.
   */
  public function getMyReferrals(int $uid): array {
    $storage = $this->entityTypeManager->getStorage('referral');

    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('referrer_uid', $uid)
      ->sort('created', 'DESC')
      ->execute();

    if (empty($ids)) {
      return [];
    }

    return array_values($storage->loadMultiple($ids));
  }

  /**
   * Obtiene estadísticas del programa de referidos por tenant.
   *
   * ESTRUCTURA: Método público de analítica agregada.
   *
   * LÓGICA: Consulta todos los referidos del tenant y calcula métricas:
   *   total de códigos generados, referidos confirmados, recompensas
   *   otorgadas, tasa de conversión y valor total de recompensas.
   *
   * RELACIONES: Consume Referral storage, filtra por tenant_id.
   *
   * @param int $tenant_id
   *   ID del tenant para filtrar estadísticas.
   *
   * @return array
   *   Estructura de métricas:
   *   - 'total_codes' (int): Total de códigos generados.
   *   - 'total_confirmed' (int): Referidos confirmados.
   *   - 'total_rewarded' (int): Referidos recompensados.
   *   - 'total_expired' (int): Referidos expirados.
   *   - 'conversion_rate' (float): Porcentaje de conversión.
   *   - 'total_reward_value' (float): Valor total de recompensas en EUR.
   */
  public function getReferralStats(int $tenant_id): array {
    $storage = $this->entityTypeManager->getStorage('referral');

    $all_ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenant_id)
      ->execute();

    $referrals = !empty($all_ids) ? $storage->loadMultiple($all_ids) : [];

    $stats = [
      'total_codes' => 0,
      'total_confirmed' => 0,
      'total_rewarded' => 0,
      'total_expired' => 0,
      'conversion_rate' => 0.0,
      'total_reward_value' => 0.0,
    ];

    /** @var \Drupal\jaraba_referral\Entity\Referral $referral */
    foreach ($referrals as $referral) {
      $stats['total_codes']++;
      $status = $referral->get('status')->value;

      switch ($status) {
        case 'confirmed':
          $stats['total_confirmed']++;
          break;

        case 'rewarded':
          $stats['total_rewarded']++;
          $stats['total_reward_value'] += (float) ($referral->get('reward_value')->value ?? 0);
          break;

        case 'expired':
          $stats['total_expired']++;
          break;
      }
    }

    // Calcular tasa de conversión.
    if ($stats['total_codes'] > 0) {
      $converted = $stats['total_confirmed'] + $stats['total_rewarded'];
      $stats['conversion_rate'] = round(($converted / $stats['total_codes']) * 100, 1);
    }

    return $stats;
  }

  /**
   * Genera un código alfanumérico único de 8 caracteres.
   *
   * ESTRUCTURA: Método protegido auxiliar.
   *
   * LÓGICA: Usa random_bytes para generación criptográficamente segura,
   *   filtra a caracteres alfanuméricos (A-Z, 0-9) y verifica unicidad
   *   contra la base de datos. Reintentos hasta encontrar código libre.
   *
   * RELACIONES: Consume Referral storage para verificar unicidad.
   *
   * @return string
   *   Código alfanumérico único de 8 caracteres en mayúsculas.
   */
  protected function generateUniqueCode(): string {
    $storage = $this->entityTypeManager->getStorage('referral');
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $max_attempts = 100;

    for ($attempt = 0; $attempt < $max_attempts; $attempt++) {
      $code = '';
      $bytes = random_bytes(8);
      for ($i = 0; $i < 8; $i++) {
        $code .= $characters[ord($bytes[$i]) % strlen($characters)];
      }

      // Verificar unicidad.
      $exists = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('referral_code', $code)
        ->count()
        ->execute();

      if ((int) $exists === 0) {
        return $code;
      }
    }

    // Fallback extremadamente improbable.
    return strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
  }

}
