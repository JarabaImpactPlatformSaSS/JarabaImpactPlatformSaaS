<?php

namespace Drupal\jaraba_commerce\EventSubscriber;

use Drupal\commerce_store\Entity\Store;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ecosistema_jaraba_core\Event\TenantEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber para auto-crear Commerce Store cuando se crea un Tenant.
 *
 * PROPÓSITO:
 * Cuando un nuevo Tenant es creado (via onboarding o admin), este subscriber
 * crea automáticamente una Commerce Store asociada a ese Tenant, permitiendo
 * que cada Tenant tenga su propia tienda con configuración independiente.
 *
 * ARQUITECTURA:
 * - Escucha el evento TenantEvents::TENANT_CREATED
 * - Crea una Store del tipo 'jaraba_store' (o 'online' por defecto)
 * - Vincula la Store al Tenant para aislamiento de datos
 *
 * @see \Drupal\ecosistema_jaraba_core\Event\TenantCreatedEvent
 */
class TenantStoreSubscriber implements EventSubscriberInterface
{

    /**
     * Entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Logger channel.
     *
     * @var \Drupal\Core\Logger\LoggerChannelInterface
     */
    protected LoggerChannelInterface $logger;

    /**
     * Constructor.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     *   The entity type manager.
     * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
     *   The logger channel.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        LoggerChannelInterface $logger
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        // Escuchar evento de creación de tenant si existe
        // Por ahora usamos hook_entity_insert como fallback
        return [];
    }

    /**
     * Crea una Commerce Store para un Tenant.
     *
     * Este método puede ser llamado desde:
     * - El evento TenantEvents::TENANT_CREATED
     * - Hook entity_insert en jaraba_commerce.module
     * - Manualmente desde código
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant para el que crear la store.
     *
     * @return \Drupal\commerce_store\Entity\StoreInterface|null
     *   La store creada, o NULL si falló.
     */
    public function createStoreForTenant($tenant): ?Store
    {
        try {
            // Verificar que Commerce Store está disponible
            if (!$this->entityTypeManager->hasDefinition('commerce_store')) {
                $this->logger->warning('Commerce Store no está instalado.');
                return NULL;
            }

            // Determinar el tipo de store a usar
            $storeTypeStorage = $this->entityTypeManager->getStorage('commerce_store_type');
            $storeType = $storeTypeStorage->load('jaraba_store') ?? $storeTypeStorage->load('online');

            if (!$storeType) {
                $this->logger->warning('No hay tipos de Commerce Store disponibles.');
                return NULL;
            }

            // Obtener información del tenant
            $tenantName = $tenant->getName();
            $adminUser = $tenant->getAdminUser();
            $domain = $tenant->getDomain();

            // Crear la Store
            $storeStorage = $this->entityTypeManager->getStorage('commerce_store');
            $store = $storeStorage->create([
                'type' => $storeType->id(),
                'name' => $tenantName,
                'mail' => $adminUser ? $adminUser->getEmail() : 'info@jaraba-impact.com',
                'default_currency' => 'EUR',
                'timezone' => 'Europe/Madrid',
                'address' => [
                    'country_code' => 'ES',
                    'locality' => 'Madrid',
                    'organization' => $tenantName,
                ],
                // Metadata para vincular con el Tenant
                'field_tenant_id' => $tenant->id(),
            ]);

            $store->save();

            $this->logger->info(
                '🏪 Commerce Store creada para Tenant @tenant: Store ID @store_id',
                [
                    '@tenant' => $tenantName,
                    '@store_id' => $store->id(),
                ]
            );

            return $store;

        } catch (\Exception $e) {
            $this->logger->error(
                '🚫 Error creando Commerce Store para Tenant @tenant: @error',
                [
                    '@tenant' => $tenant->getName(),
                    '@error' => $e->getMessage(),
                ]
            );
            return NULL;
        }
    }

    /**
     * Obtiene la Commerce Store asociada a un Tenant.
     *
     * @param int $tenantId
     *   El ID del tenant.
     *
     * @return \Drupal\commerce_store\Entity\StoreInterface|null
     *   La store asociada, o NULL si no existe.
     */
    public function getStoreForTenant(int $tenantId): ?Store
    {
        try {
            $storeStorage = $this->entityTypeManager->getStorage('commerce_store');

            // Buscar store por field_tenant_id
            $stores = $storeStorage->loadByProperties([
                'field_tenant_id' => $tenantId,
            ]);

            return !empty($stores) ? reset($stores) : NULL;
        } catch (\Exception $e) {
            return NULL;
        }
    }

}
