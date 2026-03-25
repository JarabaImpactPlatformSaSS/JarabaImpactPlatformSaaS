<?php

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Site\Settings;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;
use Drupal\ecosistema_jaraba_core\Entity\VerticalInterface;

/**
 * Servicio de Onboarding de Tenants.
 *
 * Este servicio gestiona todo el flujo de registro y onboarding de nuevos
 * tenants en la plataforma. Sus responsabilidades incluyen:
 *
 * - Validación de datos del formulario de registro
 * - Creación de la cuenta de usuario administrador
 * - Creación del tenant con configuración inicial
 * - Creación del grupo (Group) asociado al tenant
 * - Envío de emails de bienvenida y verificación
 * - Integración con Stripe para configuración de pagos
 *
 * El flujo completo de onboarding sigue estos pasos:
 * 1. Registro inicial (datos de organización y admin)
 * 2. Verificación de email (automática o manual)
 * 3. Selección de plan de suscripción
 * 4. Configuración de método de pago (Stripe)
 * 5. Activación del tenant y bienvenida
 *
 * @see \Drupal\ecosistema_jaraba_core\Controller\OnboardingController
 */
class TenantOnboardingService {

  use StringTranslationTrait;

  /**
   * El gestor de tipos de entidad.
   *
   * Se usa para crear usuarios, tenants y grupos programáticamente.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * El gestor de tenants.
   *
   * Se usa para operaciones específicas sobre tenants como validar dominios.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantManager
   */
  protected TenantManager $tenantManager;

  /**
   * El canal de log para este servicio.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * El servicio de envío de correos.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected MailManagerInterface $mailManager;

  /**
   * Constructor del servicio.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   El gestor de tipos de entidad.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantManager $tenant_manager
   *   El servicio de gestión de tenants.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   El canal de log.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   El servicio de correos.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    TenantManager $tenant_manager,
    LoggerChannelInterface $logger,
    MailManagerInterface $mail_manager,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->tenantManager = $tenant_manager;
    $this->logger = $logger;
    $this->mailManager = $mail_manager;
  }

  /**
   * Valida los datos del formulario de registro.
   *
   * Realiza validaciones exhaustivas de los datos proporcionados:
   * - Campos obligatorios presentes
   * - Formato de email válido
   * - Email no duplicado en el sistema
   * - Formato de dominio/subdominio válido
   * - Dominio no duplicado
   * - Fortaleza de contraseña.
   *
   * @param array $data
   *   Datos del formulario con claves:
   *   - 'organization_name': Nombre de la organización
   *   - 'domain': Subdominio deseado
   *   - 'admin_email': Email del administrador
   *   - 'admin_name': Nombre completo del admin
   *   - 'password': Contraseña
   *   - 'vertical_id': ID de la vertical.
   *
   * @return array
   *   Array con:
   *   - 'valid': bool indicando si todos los datos son válidos
   *   - 'errors': Array asociativo de errores por campo
   */
  public function validateRegistrationData(array $data): array {
    $errors = [];

    // Validar campos obligatorios.
    $required = [
      'organization_name' => 'Nombre de la organización',
      'domain' => 'Subdominio',
      'admin_email' => 'Email del administrador',
      'admin_name' => 'Nombre del administrador',
      'password' => 'Contraseña',
      'vertical_id' => 'Vertical',
    ];

    foreach ($required as $field => $label) {
      if (empty($data[$field])) {
        $errors[$field] = $this->t('El campo "@label" es obligatorio.', ['@label' => $label]);
      }
    }

    // Si faltan campos obligatorios, no continuar con más validaciones.
    if (!empty($errors)) {
      return ['valid' => FALSE, 'errors' => $errors];
    }

    // Validar formato de email.
    if (!filter_var($data['admin_email'], FILTER_VALIDATE_EMAIL)) {
      $errors['admin_email'] = $this->t('El formato del email no es válido.');
    }

    // Verificar que el email no esté ya registrado.
    $existingUsers = $this->entityTypeManager
      ->getStorage('user')
      ->loadByProperties(['mail' => $data['admin_email']]);

    if (!empty($existingUsers)) {
      $errors['admin_email'] = $this->t('Este email ya está registrado en la plataforma.');
    }

    // Validar formato de dominio (solo letras minúsculas, números y guiones)
    if (!preg_match('/^[a-z0-9][a-z0-9\-]{1,61}[a-z0-9]$/', $data['domain'])) {
      $errors['domain'] = $this->t(
            'El subdominio solo puede contener letras minúsculas, números y guiones. ' .
            'Debe tener entre 3 y 63 caracteres y no puede empezar ni terminar con guión.'
        );
    }

    // Verificar que el dominio no esté en uso.
    if ($this->tenantManager->domainExists($data['domain'])) {
      $errors['domain'] = $this->t('Este subdominio ya está en uso. Por favor, elija otro.');
    }

    // Validar fortaleza de contraseña (mínimo 8 caracteres, 1 mayúscula, 1 número)
    if (strlen($data['password']) < 8) {
      $errors['password'] = $this->t('La contraseña debe tener al menos 8 caracteres.');
    }
    elseif (!preg_match('/[A-Z]/', $data['password']) || !preg_match('/[0-9]/', $data['password'])) {
      $errors['password'] = $this->t('La contraseña debe contener al menos una mayúscula y un número.');
    }

    // Validar que la vertical existe.
    $vertical = $this->entityTypeManager
      ->getStorage('vertical')
      ->load($data['vertical_id']);

    if (!$vertical || !$vertical->isPublished()) {
      $errors['vertical_id'] = $this->t('La vertical seleccionada no es válida.');
    }

    return [
      'valid' => empty($errors),
      'errors' => $errors,
    ];
  }

  /**
   * Procesa el registro completo de un nuevo tenant.
   *
   * Este método orquesta todo el proceso de creación:
   * 1. Crea el usuario administrador
   * 2. Crea el tenant con periodo de prueba
   * 3. Crea el grupo asociado al tenant
   * 4. Envía email de bienvenida.
   *
   * @param array $data
   *   Datos validados del registro (ver validateRegistrationData).
   *
   * @return array
   *   Array con:
   *   - 'success': bool
   *   - 'tenant': TenantInterface si éxito
   *   - 'user': AccountInterface si éxito
   *   - 'error': string si falla
   */
  public function processRegistration(array $data): array {
    try {
      // 1. Crear usuario administrador
      $user = $this->createAdminUser($data);

      if (!$user) {
        return [
          'success' => FALSE,
          'error' => 'No se pudo crear el usuario administrador.',
        ];
      }

      // 2. Cargar la vertical
      $vertical = $this->entityTypeManager
        ->getStorage('vertical')
        ->load($data['vertical_id']);

      // 3. Obtener el plan por defecto (trial/básico)
      $defaultPlan = $this->getDefaultTrialPlan($vertical);

      // 4. Crear el tenant
      $tenant = $this->tenantManager->createTenant([
        'name' => $data['organization_name'],
        'domain' => $data['domain'],
        'vertical_id' => $data['vertical_id'],
        'plan_id' => $defaultPlan->id(),
        'admin_user_id' => $user->id(),
      ]);

      if (!$tenant) {
        // Rollback: eliminar usuario creado.
        $user->delete();
        return [
          'success' => FALSE,
          'error' => 'No se pudo crear el tenant.',
        ];
      }

      // 5. Crear grupo asociado al tenant (para gestión de miembros)
      $this->createTenantGroup($tenant, $user);

      // 6. Crear dominio en Domain Access para acceso personalizado
      $this->createTenantDomain($tenant);

      // 7. Iniciar periodo de prueba
      $this->tenantManager->startTrial($tenant);

      // 8. Enviar email de bienvenida
      $this->sendWelcomeEmail($user, $tenant, $vertical);

      // 9. Vincular QuizResult si viene del quiz de recomendación.
      if (!empty($data['quiz_uuid']) && \Drupal::hasService('ecosistema_jaraba_core.vertical_quiz')) {
        try {
          \Drupal::service('ecosistema_jaraba_core.vertical_quiz')
            ->linkResultToUser($data['quiz_uuid'], (int) $user->id(), (int) $tenant->id());
        }
        catch (\Throwable $e) {
          $this->logger->warning('Quiz link failed for @uuid: @e', [
            '@uuid' => $data['quiz_uuid'],
            '@e' => $e->getMessage(),
          ]);
        }
      }

      // Log del evento.
      $this->logger->info(
            '🎉 Nuevo tenant registrado: @name (dominio: @domain) para vertical @vertical',
            [
              '@name' => $tenant->getName(),
              '@domain' => $tenant->getDomain(),
              '@vertical' => $vertical->getName(),
            ]
        );

      return [
        'success' => TRUE,
        'tenant' => $tenant,
        'user' => $user,
      ];

    }
    catch (\Exception $e) {
      $this->logger->error(
            '🚫 Error en onboarding: @error',
            ['@error' => $e->getMessage()]
            );

      return [
        'success' => FALSE,
        'error' => 'Error interno durante el registro. Por favor, inténtelo de nuevo.',
      ];
    }
  }

  /**
   * Crea la cuenta de usuario para el administrador del tenant.
   *
   * @param array $data
   *   Datos del formulario de registro.
   *
   * @return \Drupal\Core\Session\AccountInterface|null
   *   El usuario creado o NULL si falla.
   */
  protected function createAdminUser(array $data): ?AccountInterface {
    try {
      $userStorage = $this->entityTypeManager->getStorage('user');

      // Generar nombre de usuario único basado en el email.
      $username = $this->generateUniqueUsername($data['admin_email']);

      $user = $userStorage->create([
        'name' => $username,
        'mail' => $data['admin_email'],
        'pass' => $data['password'],
      // Activo inmediatamente (email verification opcional)
        'status' => 1,
        'field_nombre_completo' => $data['admin_name'],
      ]);

      // Asignar rol de administrador de tenant.
      $user->addRole('tenant_admin');

      $user->save();

      return $user;

    }
    catch (\Exception $e) {
      $this->logger->error(
            '🚫 Error al crear usuario admin: @error',
            ['@error' => $e->getMessage()]
            );
      return NULL;
    }
  }

  /**
   * Genera un nombre de usuario único basado en el email.
   *
   * Si el nombre ya existe, añade un sufijo numérico.
   *
   * @param string $email
   *   Email del usuario.
   *
   * @return string
   *   Nombre de usuario único.
   */
  protected function generateUniqueUsername(string $email): string {
    // Usar la parte local del email como base.
    $baseUsername = strtolower(explode('@', $email)[0]);

    // Limpiar caracteres no permitidos.
    $baseUsername = preg_replace('/[^a-z0-9_]/', '_', $baseUsername);

    $username = $baseUsername;
    $counter = 1;

    // Buscar nombre único.
    while (!empty($this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $username]))) {
      $username = $baseUsername . '_' . $counter;
      $counter++;
    }

    return $username;
  }

  /**
   * Obtiene el plan por defecto para el periodo de prueba.
   *
   * Busca el plan más básico (menor precio mensual) de la vertical,
   * o el primer plan activo si no hay uno específico para trial.
   *
   * @param \Drupal\ecosistema_jaraba_core\Entity\VerticalInterface $vertical
   *   La vertical del tenant.
   *
   * @return \Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface
   *   El plan por defecto.
   */
  protected function getDefaultTrialPlan(VerticalInterface $vertical) {
    $planStorage = $this->entityTypeManager->getStorage('saas_plan');

    // Buscar planes de la vertical ordenados por peso (el menor es el básico)
    $plans = $planStorage->loadByProperties([
      'vertical' => $vertical->id(),
      'status' => TRUE,
    ]);

    if (empty($plans)) {
      throw new \RuntimeException('No hay planes disponibles para la vertical ' . $vertical->getName());
    }

    // Ordenar por peso y devolver el primero.
    usort($plans, fn($a, $b) => ($a->get('weight')->value ?? 0) - ($b->get('weight')->value ?? 0));

    return reset($plans);
  }

  /**
   * Crea un grupo asociado al tenant para gestionar miembros.
   *
   * Utiliza el módulo Group para crear una estructura organizativa.
   * El usuario administrador se añade como propietario del grupo.
   *
   * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
   *   El tenant recién creado.
   * @param \Drupal\Core\Session\AccountInterface $admin
   *   El usuario administrador.
   */
  protected function createTenantGroup(TenantInterface $tenant, AccountInterface $admin): void {
    try {
      // Verificar si el módulo Group está habilitado.
      if (!$this->entityTypeManager->hasDefinition('group')) {
        $this->logger->warning(
              'Módulo Group no disponible. Grupo para tenant @tenant no creado.',
              ['@tenant' => $tenant->getName()]
          );
        return;
      }

      // Verificar si el tipo de grupo 'tenant' existe.
      $groupTypeStorage = $this->entityTypeManager->getStorage('group_type');
      $tenantGroupType = $groupTypeStorage->load('tenant');

      if (!$tenantGroupType) {
        $this->logger->warning(
              'Tipo de grupo "tenant" no encontrado. Cree el tipo en /admin/group/types',
              ['@tenant' => $tenant->getName()]
          );
        return;
      }

      // Crear el grupo asociado al tenant.
      $groupStorage = $this->entityTypeManager->getStorage('group');
      $group = $groupStorage->create([
        'type' => 'tenant',
        'label' => $tenant->getName(),
      ]);
      $group->save();

      // Añadir el administrador como miembro del grupo
      // El módulo Group gestiona automáticamente los roles.
      $group->addMember($admin);

      // Vincular el grupo al tenant.
      $tenant->set('group_id', $group->id());
      $tenant->save();

      $this->logger->info(
            '✅ Grupo creado para tenant @tenant (Group ID: @group_id)',
            [
              '@tenant' => $tenant->getName(),
              '@group_id' => $group->id(),
            ]
        );

    }
    catch (\Exception $e) {
      $this->logger->error(
            '🚫 Error creando grupo para tenant @tenant: @error',
            [
              '@tenant' => $tenant->getName(),
              '@error' => $e->getMessage(),
            ]
            );
    }
  }

  /**
   * Crea un dominio en Domain Access para el tenant.
   *
   * PROPÓSITO:
   * Cada tenant necesita un punto de acceso único (URL). Este método
   * automatiza la creación de la entidad Domain del módulo Domain Access,
   * generando un subdominio basado en el nombre del tenant.
   *
   * FLUJO DE EJECUCIÓN:
   * 1. Verifica que el módulo Domain está habilitado
   * 2. Genera un slug único (URL-safe) basado en el nombre
   * 3. Construye el hostname completo (slug + dominio base)
   * 4. Crea o reutiliza el Domain existente
   * 5. Vincula el Domain al Tenant via domain_id
   *
   * ENTORNO:
   * - Local (Lando): slug.jaraba-saas.lndo.site
   * - Producción: slug.jaraba.io o dominio personalizado
   *
   * NOTA IMPORTANTE:
   * En desarrollo local, los subdominios deben añadirse manualmente al proxy
   * de Lando (.lando.yml) o usar wildcard DNS.
   *
   * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
   *   El tenant recién creado.
   */
  protected function createTenantDomain(TenantInterface $tenant): void {
    try {
      // =====================================================================
      // PASO 1: VERIFICACIÓN DE DEPENDENCIAS
      // Verificamos que el módulo Domain Access está disponible.
      // Sin este módulo, el aislamiento por dominio no es posible.
      // =====================================================================
      if (!$this->entityTypeManager->hasDefinition('domain')) {
        $this->logger->warning(
              'Módulo Domain no disponible. Dominio para tenant @tenant no creado.',
              ['@tenant' => $tenant->getName()]
          );
        return;
      }

      // =====================================================================
      // PASO 2: GENERACIÓN DE IDENTIFICADORES
      // El slug es la parte local del subdominio (URL-safe).
      // El machine_name es el ID único en Drupal (sin puntos ni guiones).
      // El hostname es la URL completa de acceso.
      // =====================================================================
      $slug = $this->generateDomainSlug($tenant->getName());

      // BE-08: Dominio base configurable desde settings.php.
      $baseDomain = Settings::get('jaraba_base_domain', 'jaraba-saas.lndo.site');
      $hostname = $slug . '.' . $baseDomain;

      // El machine_name es el ID en Drupal (solo alfanumérico y guiones bajos)
      $machineName = str_replace(['.', '-'], '_', $hostname);

      // =====================================================================
      // PASO 3: VERIFICACIÓN DE EXISTENCIA
      // Si el dominio ya existe (ej: migración o reintento), lo reutilizamos
      // en lugar de crear uno duplicado.
      // =====================================================================
      $domainStorage = $this->entityTypeManager->getStorage('domain');
      $existing = $domainStorage->load($machineName);

      if ($existing) {
        $this->logger->info(
              'Domain @hostname ya existe, reutilizando para tenant @tenant.',
              [
                '@hostname' => $hostname,
                '@tenant' => $tenant->getName(),
              ]
          );

        // Vincular el domain existente al tenant.
        $tenant->set('domain_id', $existing->id());
        $tenant->save();
        return;
      }

      // =====================================================================
      // PASO 4: CREACIÓN DEL DOMAIN
      // Creamos la entidad Domain con la configuración estándar:
      // - scheme: https (siempre usar HTTPS en producción)
      // - status: 1 (activo inmediatamente)
      // - is_default: FALSE (el default es la plataforma principal)
      // - weight: 0 (orden estándar)
      // =====================================================================
      $domain = $domainStorage->create([
        'id' => $machineName,
        'name' => $tenant->getName(),
        'hostname' => $hostname,
        'scheme' => 'https',
        'status' => 1,
        'weight' => 0,
        'is_default' => FALSE,
      ]);
      $domain->save();

      // =====================================================================
      // PASO 5: VINCULACIÓN CON TENANT
      // Almacenamos la referencia al domain en el campo domain_id del tenant.
      // Esto permite recuperar fácilmente el Domain via getDomainEntity().
      // =====================================================================
      $tenant->set('domain_id', $domain->id());
      $tenant->save();

      $this->logger->info(
            '✅ Domain @hostname creado y vinculado a tenant @tenant',
            [
              '@hostname' => $hostname,
              '@tenant' => $tenant->getName(),
            ]
        );

    }
    catch (\Exception $e) {
      // El error no debe detener el onboarding completo
      // El tenant puede funcionar sin dominio personalizado (usa el principal)
      $this->logger->error(
            '🚫 Error creando domain para tenant @tenant: @error',
            [
              '@tenant' => $tenant->getName(),
              '@error' => $e->getMessage(),
            ]
            );
    }
  }

  /**
   * Genera un slug seguro para el dominio basado en el nombre del tenant.
   *
   * PROPÓSITO:
   * Convierte nombres como "Cooperativa Aceites del Sur" en slugs
   * válidos para subdominios como "cooperativa-aceites-del-sur".
   *
   * REGLAS DE TRANSFORMACIÓN:
   * 1. Transliteración de caracteres especiales (á -> a, ñ -> n)
   * 2. Conversión a minúsculas
   * 3. Reemplazo de espacios y caracteres no permitidos por guiones
   * 4. Eliminación de guiones duplicados y extremos
   * 5. Fallback a timestamp si el resultado está vacío
   *
   * RESTRICCIONES DNS:
   * - Solo caracteres a-z, 0-9 y guiones
   * - No puede empezar ni terminar con guión
   * - Máximo 63 caracteres por etiqueta
   *
   * @param string $name
   *   Nombre del tenant.
   *
   * @return string
   *   Slug URL-safe y DNS-compatible.
   */
  protected function generateDomainSlug(string $name): string {
    // Transliteración: convertir caracteres especiales a ASCII
    // Usamos la clase Transliterator de PHP (requiere extensión intl)
    if (class_exists('\Transliterator')) {
      $transliterator = \Transliterator::create('Any-Latin; Latin-ASCII; Lower()');
      if ($transliterator) {
        $slug = $transliterator->transliterate($name);
      }
      else {
        // Fallback si Transliterator falla.
        $slug = strtolower($name);
      }
    }
    else {
      // Fallback básico si la extensión intl no está disponible.
      $slug = strtolower($name);
      // Reemplazos manuales de caracteres comunes en español.
      $replacements = [
        'á' => 'a',
        'é' => 'e',
        'í' => 'i',
        'ó' => 'o',
        'ú' => 'u',
        'ü' => 'u',
        'ñ' => 'n',
        'ç' => 'c',
      ];
      $slug = strtr($slug, $replacements);
    }

    // Reemplazar cualquier carácter no permitido por guión.
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

    // Eliminar guiones al inicio y final.
    $slug = trim($slug, '-');

    // Limitar longitud a 60 chars (dejando margen para el dominio base)
    if (strlen($slug) > 60) {
      $slug = substr($slug, 0, 60);
      // Evitar terminar en guión.
      $slug = rtrim($slug, '-');
    }

    // Fallback si el slug quedó vacío.
    if (empty($slug)) {
      $slug = 'tenant-' . time();
    }

    return $slug;
  }

  /**
   * Envía el email de bienvenida al nuevo administrador.
   *
   * El email incluye:
   * - Datos del tenant creado
   * - URL de acceso personalizada
   * - Próximos pasos del onboarding
   * - Información sobre el periodo de prueba.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   El usuario administrador.
   * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
   *   El tenant creado.
   * @param \Drupal\ecosistema_jaraba_core\Entity\VerticalInterface $vertical
   *   La vertical del tenant.
   */
  protected function sendWelcomeEmail(
    AccountInterface $user,
    TenantInterface $tenant,
    VerticalInterface $vertical,
  ): void {
    $params = [
      'tenant_name' => $tenant->getName(),
      'tenant_domain' => $tenant->getDomain(),
      'vertical_name' => $vertical->getName(),
      'trial_ends' => $tenant->getTrialEndsAt(),
      'access_url' => 'https://' . $tenant->getDomain() . '.jaraba.io',
      'admin_name' => $user->getDisplayName(),
    ];

    $this->mailManager->mail(
          'ecosistema_jaraba_core',
          'tenant_welcome',
          $user->getEmail(),
          $user->getPreferredLangcode(),
          $params
      );

    $this->logger->info(
          '📧 Email de bienvenida enviado a @email',
          ['@email' => $user->getEmail()]
      );
  }

  /**
   * Completa el onboarding después de configurar el pago.
   *
   * Este método se llama después de que Stripe confirme el método de pago.
   * Activa completamente el tenant y envía notificaciones.
   *
   * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
   *   El tenant a activar.
   * @param string $stripe_customer_id
   *   ID del cliente en Stripe.
   * @param string $stripe_subscription_id
   *   ID de la suscripción en Stripe.
   *
   * @return bool
   *   TRUE si se completó correctamente.
   */
  public function completeOnboarding(
    TenantInterface $tenant,
    string $stripe_customer_id,
    string $stripe_subscription_id,
  ): bool {
    try {
      // Guardar IDs de Stripe.
      $tenant->set('stripe_customer_id', $stripe_customer_id);
      $tenant->set('stripe_subscription_id', $stripe_subscription_id);

      // Activar suscripción (cambia de trial a active)
      $this->tenantManager->activateSubscription($tenant, $stripe_subscription_id);

      $this->logger->info(
            '✅ Onboarding completado para tenant @name',
            ['@name' => $tenant->getName()]
        );

      return TRUE;

    }
    catch (\Exception $e) {
      $this->logger->error(
            '🚫 Error al completar onboarding: @error',
            ['@error' => $e->getMessage()]
            );
      return FALSE;
    }
  }

}
