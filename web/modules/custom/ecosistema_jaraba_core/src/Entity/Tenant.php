<?php

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\domain\Entity\Domain;
use Drupal\group\Entity\GroupInterface;
use Drupal\user\UserInterface;

/**
 * Define la entidad de contenido Tenant.
 *
 * Un Tenant representa una organización cliente que utiliza la plataforma
 * (anteriormente llamada "Sede").
 *
 * @ContentEntityType(
 *   id = "tenant",
 *   label = @Translation("Tenant"),
 *   label_collection = @Translation("Tenants"),
 *   label_singular = @Translation("tenant"),
 *   label_plural = @Translation("tenants"),
 *   label_count = @PluralTranslation(
 *     singular = "@count tenant",
 *     plural = "@count tenants",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ecosistema_jaraba_core\TenantListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\ecosistema_jaraba_core\Form\TenantForm",
 *       "add" = "Drupal\ecosistema_jaraba_core\Form\TenantForm",
 *       "edit" = "Drupal\ecosistema_jaraba_core\Form\TenantForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\ecosistema_jaraba_core\TenantAccessControlHandler",
 *   },
 *   base_table = "tenant",
 *   data_table = "tenant_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer tenants",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/tenants",
 *     "add-form" = "/admin/structure/tenants/add",
 *     "canonical" = "/admin/structure/tenants/{tenant}",
 *     "edit-form" = "/admin/structure/tenants/{tenant}/edit",
 *     "delete-form" = "/admin/structure/tenants/{tenant}/delete",
 *   },
 *   field_ui_base_route = "entity.tenant.collection",
 * )
 */
class Tenant extends ContentEntityBase implements TenantInterface
{

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     *
     * Crea automáticamente Group y Domain cuando se guarda un nuevo Tenant.
     * Esto asegura que los Tenants creados desde admin (no via onboarding)
     * también tengan su infraestructura de aislamiento.
     */
    public function postSave(\Drupal\Core\Entity\EntityStorageInterface $storage, $update = TRUE): void
    {
        parent::postSave($storage, $update);

        // Solo para nuevas entidades (no updates)
        if (!$update) {
            $this->provisionGroupIfNeeded();
            $this->provisionDomainIfNeeded();
        }
    }

    /**
     * Crea un Group si el Tenant no tiene uno asociado.
     */
    protected function provisionGroupIfNeeded(): void
    {
        // Campo group_id es condicional — puede no existir sin módulo group
        if (!$this->hasField('group_id')) {
            return;
        }

        // Ya tiene Group? No hacer nada
        if ($this->get('group_id')->target_id) {
            return;
        }

        // Verificar que existe el tipo de grupo 'tenant'
        $groupTypeStorage = \Drupal::entityTypeManager()->getStorage('group_type');
        if (!$groupTypeStorage->load('tenant')) {
            \Drupal::logger('ecosistema_jaraba_core')->warning(
                'No se puede crear Group para Tenant @id: no existe el tipo "tenant"',
                ['@id' => $this->id()]
            );
            return;
        }

        // Crear el Group
        $groupStorage = \Drupal::entityTypeManager()->getStorage('group');
        $group = $groupStorage->create([
            'type' => 'tenant',
            'label' => $this->getName(),
        ]);
        $group->save();

        // Asignar el admin user como miembro del grupo
        $adminUser = $this->getAdminUser();
        if ($adminUser) {
            $group->addMember($adminUser);
        }

        // BE-07: Actualizar group_id via Entity API en vez de query directa.
        // Usar enforceIsNew(FALSE) + save() sobre la entidad recargada
        // para que los hooks de entidad se ejecuten correctamente.
        $this->set('group_id', $group->id());
        $this->setSyncing(TRUE);
        $this->save();
        $this->setSyncing(FALSE);

        \Drupal::logger('ecosistema_jaraba_core')->notice(
            'Group @gid creado para Tenant @tid (@name)',
            ['@gid' => $group->id(), '@tid' => $this->id(), '@name' => $this->getName()]
        );
    }

    /**
     * Crea un Domain si el Tenant no tiene uno asociado.
     */
    protected function provisionDomainIfNeeded(): void
    {
        // Campo domain_id es condicional — puede no existir sin módulo domain
        if (!$this->hasField('domain_id')) {
            return;
        }

        // Ya tiene Domain? No hacer nada
        if ($this->get('domain_id')->target_id) {
            return;
        }

        // Obtener hostname del campo domain
        $hostname = $this->getDomain();
        if (empty($hostname)) {
            return;
        }

        // BE-08: Normalizar hostname usando dominio base configurable.
        if (strpos($hostname, '.') === FALSE) {
            $baseDomain = \Drupal\Core\Site\Settings::get('jaraba_base_domain', 'jaraba-saas.lndo.site');
            $hostname = $hostname . '.' . $baseDomain;
        }

        // Crear el Domain
        $domainStorage = \Drupal::entityTypeManager()->getStorage('domain');
        $domainId = preg_replace('/[^a-z0-9_]/', '_', strtolower($hostname));

        $domain = $domainStorage->create([
            'id' => $domainId,
            'hostname' => $hostname,
            'name' => $this->getName(),
            'scheme' => 'https',
            'status' => TRUE,
            'weight' => 0,
            'is_default' => FALSE,
        ]);
        $domain->save();

        // Actualizar el Tenant con el domain_id (sin trigger recursivo)
        \Drupal::database()->update('tenant_field_data')
            ->fields(['domain_id' => $domain->id()])
            ->condition('id', $this->id())
            ->execute();

        \Drupal::logger('ecosistema_jaraba_core')->notice(
            'Domain @did (@host) creado para Tenant @tid',
            ['@did' => $domain->id(), '@host' => $hostname, '@tid' => $this->id()]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->get('name')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setName(string $name): TenantInterface
    {
        $this->set('name', $name);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getVertical(): ?VerticalInterface
    {
        $vertical = $this->get('vertical')->entity;
        return $vertical instanceof VerticalInterface ? $vertical : NULL;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscriptionPlan(): ?SaasPlanInterface
    {
        $plan = $this->get('subscription_plan')->entity;
        return $plan instanceof SaasPlanInterface ? $plan : NULL;
    }

    /**
     * {@inheritdoc}
     */
    public function setSubscriptionPlan(SaasPlanInterface $plan): TenantInterface
    {
        $this->set('subscription_plan', $plan->id());
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDomain(): string
    {
        return $this->get('domain')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminUser(): ?UserInterface
    {
        $user = $this->get('admin_user')->entity;
        return $user instanceof UserInterface ? $user : NULL;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscriptionStatus(): string
    {
        return $this->get('subscription_status')->value ?? self::STATUS_PENDING;
    }

    /**
     * {@inheritdoc}
     */
    public function setSubscriptionStatus(string $status): TenantInterface
    {
        $this->set('subscription_status', $status);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isActive(): bool
    {
        $status = $this->getSubscriptionStatus();
        return in_array($status, [self::STATUS_ACTIVE, self::STATUS_TRIAL], TRUE);
    }

    /**
     * {@inheritdoc}
     */
    public function isOnTrial(): bool
    {
        return $this->getSubscriptionStatus() === self::STATUS_TRIAL;
    }

    /**
     * {@inheritdoc}
     */
    public function getTrialEndsAt(): ?\DateTimeInterface
    {
        $value = $this->get('trial_ends')->value;
        if ($value) {
            return new \DateTime($value);
        }
        return NULL;
    }

    /**
     * {@inheritdoc}
     */
    public function getThemeOverrides(): array
    {
        $overrides = $this->get('theme_overrides')->value;
        if (is_string($overrides)) {
            return json_decode($overrides, TRUE) ?? [];
        }
        return $overrides ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getStripeCustomerId(): ?string
    {
        return $this->get('stripe_customer_id')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getStripeConnectId(): ?string
    {
        return $this->get('stripe_connect_id')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function hasStripeConnect(): bool
    {
        return !empty($this->getStripeConnectId());
    }

    /**
     * {@inheritdoc}
     */
    public function getGroup(): ?GroupInterface
    {
        $group = $this->get('group_id')->entity;
        return $group instanceof GroupInterface ? $group : NULL;
    }

    /**
     * Establece el Group de aislamiento.
     *
     * @param \Drupal\group\Entity\GroupInterface $group
     *   El grupo a asociar.
     *
     * @return $this
     */
    public function setGroup(GroupInterface $group): TenantInterface
    {
        $this->set('group_id', $group->id());
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * LÓGICA:
     * Obtiene la entidad Domain asociada a través del campo domain_id.
     * Este campo es un entity_reference al módulo Domain Access.
     * Retorna NULL si no hay dominio asignado (tenants legacy o en creación).
     */
    public function getDomainEntity(): ?Domain
    {
        $domain = $this->get('domain_id')->entity;
        return $domain instanceof Domain ? $domain : NULL;
    }

    /**
     * {@inheritdoc}
     *
     * FLUJO DE EJECUCIÓN:
     * 1. Recibe la entidad Domain creada por TenantOnboardingService
     * 2. Almacena la referencia en el campo domain_id
     * 3. Retorna $this para permitir encadenamiento fluido
     *
     * NOTA: Este método NO guarda la entidad automáticamente.
     * El llamador debe invocar $tenant->save() explícitamente.
     */
    public function setDomainEntity(Domain $domain): TenantInterface
    {
        $this->set('domain_id', $domain->id());
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type)
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Nombre comercial del Tenant.
        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre'))
            ->setDescription(t('Nombre comercial del tenant (ej: Cooperativa Aceites Jaén).'))
            ->setRequired(TRUE)
            ->setTranslatable(TRUE)
            ->setSetting('max_length', 200)
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'string',
                'weight' => -5,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Vertical asociada.
        $fields['vertical'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Vertical'))
            ->setDescription(t('Vertical a la que pertenece el tenant.'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'vertical')
            ->setSetting('handler', 'default')
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'entity_reference_label',
                'weight' => 0,
            ])
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Plan de suscripción.
        $fields['subscription_plan'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Plan de Suscripción'))
            ->setDescription(t('Plan SaaS contratado actualmente.'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'saas_plan')
            ->setSetting('handler', 'default')
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'entity_reference_label',
                'weight' => 1,
            ])
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Dominio.
        $fields['domain'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Dominio'))
            ->setDescription(t('Subdominio o dominio personalizado (ej: aceites-jaen.jaraba.io).'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->addConstraint('UniqueField')
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Usuario administrador.
        $fields['admin_user'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Usuario Administrador'))
            ->setDescription(t('Usuario principal del tenant.'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'user')
            ->setSetting('handler', 'default')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Estado de suscripción.
        $fields['subscription_status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado de Suscripción'))
            ->setDescription(t('Estado actual de la suscripción.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                TenantInterface::STATUS_PENDING => 'Pendiente',
                TenantInterface::STATUS_TRIAL => 'Período de prueba',
                TenantInterface::STATUS_ACTIVE => 'Activo',
                TenantInterface::STATUS_PAST_DUE => 'Pago pendiente',
                TenantInterface::STATUS_SUSPENDED => 'Suspendido',
                TenantInterface::STATUS_CANCELLED => 'Cancelado',
            ])
            ->setDefaultValue(TenantInterface::STATUS_PENDING)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'list_default',
                'weight' => 15,
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 15,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Fecha fin de trial.
        $fields['trial_ends'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fin de Período de Prueba'))
            ->setDescription(t('Fecha en que termina el trial.'))
            ->setSetting('datetime_type', 'datetime')
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => 20,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Fecha de próxima renovación.
        $fields['current_period_end'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Próxima Renovación'))
            ->setDescription(t('Fecha de la próxima renovación de suscripción.'))
            ->setSetting('datetime_type', 'datetime')
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => 21,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // BIZ-002: Fecha fin periodo de gracia por impago.
        // Migrado desde State API (subscription_grace_ends_{id}) para
        // persistencia auditable y tolerancia a rebuilds.
        $fields['grace_period_ends'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fin Periodo de Gracia'))
            ->setDescription(t('Fecha de expiración del periodo de gracia tras fallo de pago.'))
            ->setSetting('datetime_type', 'datetime')
            ->setDisplayOptions('view', ['region' => 'hidden'])
            ->setDisplayOptions('form', ['region' => 'hidden'])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // BIZ-002: Fecha cancelación diferida al fin del periodo de facturación.
        // Migrado desde State API (subscription_cancel_at_{id}) para
        // persistencia auditable y tolerancia a rebuilds.
        $fields['cancel_at'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Cancelación Programada'))
            ->setDescription(t('Fecha de cancelación diferida al final del periodo de facturación.'))
            ->setSetting('datetime_type', 'datetime')
            ->setDisplayOptions('view', ['region' => 'hidden'])
            ->setDisplayOptions('form', ['region' => 'hidden'])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Stripe Customer ID.
        $fields['stripe_customer_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Stripe Customer ID'))
            ->setDescription(t('ID del cliente en Stripe (ej: cus_XXXX).'))
            ->setSetting('max_length', 100)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 25,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Stripe Subscription ID.
        $fields['stripe_subscription_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Stripe Subscription ID'))
            ->setDescription(t('ID de la suscripción en Stripe (ej: sub_XXXX).'))
            ->setSetting('max_length', 100)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 26,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Stripe Connect ID (para franquicias).
        $fields['stripe_connect_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Stripe Connect ID'))
            ->setDescription(t('ID de cuenta conectada para split payments (ej: acct_XXXX).'))
            ->setSetting('max_length', 100)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 27,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Personalizaciones de tema (JSON).
        $fields['theme_overrides'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Personalizaciones de Tema'))
            ->setDescription(t('JSON con colores, logo y tipografía personalizados.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 30,
                'settings' => [
                    'rows' => 8,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE);

        // =====================================================================
        // INTEGRACIÓN CON GROUP MODULE
        // Este campo vincula el Tenant con su Group de aislamiento.
        // Cuando se crea un Tenant, se debe crear automáticamente un Group
        // del tipo 'tenant' y asociarlo aquí.
        //
        // CONDICIONAL: Solo se define si el entity type 'group' existe.
        // Esto permite que Tenant funcione en entornos de test (Kernel)
        // sin requerir el módulo contrib 'group'.
        // =====================================================================
        if (\Drupal::entityTypeManager()->hasDefinition('group')) {
            $fields['group_id'] = BaseFieldDefinition::create('entity_reference')
                ->setLabel(t('Grupo de Aislamiento'))
                ->setDescription(t('Grupo asociado para aislamiento de contenido (Group Module).'))
                ->setSetting('target_type', 'group')
                ->setSetting('handler', 'default')
                ->setSetting('handler_settings', [
                    'target_bundles' => ['tenant' => 'tenant'],
                ])
                ->setDisplayOptions('view', [
                    'label' => 'above',
                    'type' => 'entity_reference_label',
                    'weight' => 35,
                ])
                ->setDisplayOptions('form', [
                    'type' => 'entity_reference_autocomplete',
                    'weight' => 35,
                ])
                ->setDisplayConfigurable('form', TRUE)
                ->setDisplayConfigurable('view', TRUE);
        }

        // =====================================================================
        // INTEGRACIÓN CON DOMAIN ACCESS MODULE
        // Este campo vincula el Tenant con su Domain personalizado.
        // Cuando se crea un Tenant, se debe crear automáticamente un Domain
        // con el subdominio correspondiente y asociarlo aquí.
        //
        // RELACIÓN CON OTROS CAMPOS:
        // - 'domain' (string): Es el hostname como texto simple (legacy/manual)
        // - 'domain_id' (entity_reference): Es la referencia a Domain Access
        //
        // PRIORIDAD: Si domain_id existe, usar getDomainEntity() para obtener
        // la configuración completa (scheme, is_default, etc.).
        //
        // CONDICIONAL: Solo se define si el entity type 'domain' existe.
        // =====================================================================
        if (\Drupal::entityTypeManager()->hasDefinition('domain')) {
            $fields['domain_id'] = BaseFieldDefinition::create('entity_reference')
                ->setLabel(t('Dominio Asignado'))
                ->setDescription(t('Dominio de Domain Access asociado a este tenant.'))
                ->setSetting('target_type', 'domain')
                ->setSetting('handler', 'default')
                ->setDisplayOptions('view', [
                    'label' => 'above',
                    'type' => 'entity_reference_label',
                    'weight' => 36,
                ])
                ->setDisplayOptions('form', [
                    'type' => 'entity_reference_autocomplete',
                    'weight' => 36,
                    'settings' => [
                        'match_operator' => 'CONTAINS',
                        'size' => 60,
                        'placeholder' => t('Buscar dominio...'),
                    ],
                ])
                ->setDisplayConfigurable('form', TRUE)
                ->setDisplayConfigurable('view', TRUE);
        }

        // =====================================================================
        // REVERSE TRIAL MODEL (Q1 2027)
        // Campos para soportar el modelo de trial inverso donde el usuario
        // obtiene acceso Pro completo y luego hace downgrade automático.
        // =====================================================================

        // Flag para indicar si el trial es "reverse" (acceso Pro completo).
        $fields['is_reverse_trial'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Reverse Trial'))
            ->setDescription(t('Indica si el tenant está en modo Reverse Trial (acceso Pro completo).'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 22,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Plan al que hacer downgrade después del trial.
        $fields['downgrade_plan'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Plan Post-Trial'))
            ->setDescription(t('Plan al que cambiar cuando expire el Reverse Trial.'))
            ->setSetting('target_type', 'saas_plan')
            ->setSetting('handler', 'default')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 23,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Flag para evitar spam en recordatorios de trial.
        $fields['trial_reminder_sent'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Recordatorio de Trial Enviado'))
            ->setDescription(t('Indica si ya se envió el recordatorio de fin de trial.'))
            ->setDefaultValue(FALSE);

        // Timestamps.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Fecha de creación'))
            ->setDescription(t('Fecha en que se creó el tenant.'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Fecha de modificación'))
            ->setDescription(t('Fecha de la última modificación.'));

        return $fields;
    }

}
