<?php

declare(strict_types=1);

namespace Drupal\jaraba_email\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio para gestionar listas de email.
 *
 * PROPÓSITO:
 * Gestiona las entidades EmailList que agrupan suscriptores.
 * Las listas son la base para segmentación de campañas y
 * gestión de audiencias multi-tenant.
 *
 * CARACTERÍSTICAS:
 * - Creación y consulta de listas por tenant
 * - Soporte para listas estáticas y dinámicas
 * - Configuración de double opt-in por lista
 * - Actualización automática de conteos de suscriptores
 *
 * ESPECIFICACIÓN: Doc 139 - Marketing_AI_Stack_Native
 */
class EmailListService
{

    /**
     * El gestor de tipos de entidad.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * El logger para registrar eventos.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Construye un EmailListService.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     *   El gestor de tipos de entidad.
     * @param \Psr\Log\LoggerInterface $logger
     *   El servicio de logging.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        LoggerInterface $logger,
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->logger = $logger;
    }

    /**
     * Obtiene todas las listas de un tenant.
     *
     * Solo retorna listas activas. Si no se especifica tenant,
     * retorna todas las listas activas del sistema.
     *
     * @param int|null $tenantId
     *   El ID del tenant, o NULL para todas.
     *
     * @return array
     *   Array de entidades EmailList.
     */
    public function getListsForTenant(?int $tenantId = NULL): array
    {
        $storage = $this->entityTypeManager->getStorage('email_list');
        $query = $storage->getQuery()
            ->condition('is_active', TRUE)
            ->accessCheck(FALSE);

        if ($tenantId !== NULL) {
            $query->condition('tenant_id', $tenantId);
        }

        $ids = $query->execute();
        return $storage->loadMultiple($ids);
    }

    /**
     * Crea una nueva lista de email.
     *
     * La lista se crea siempre como activa. Por defecto,
     * el double opt-in está habilitado para cumplir con GDPR.
     *
     * @param string $name
     *   El nombre de la lista.
     * @param array $options
     *   Opciones adicionales:
     *   - 'type': Tipo de lista (static|dynamic). Por defecto 'static'.
     *   - 'double_optin': Activar double opt-in. Por defecto TRUE.
     *   - 'tenant_id': ID del tenant propietario.
     *
     * @return \Drupal\jaraba_email\Entity\EmailList
     *   La lista creada.
     */
    public function createList(string $name, array $options = [])
    {
        $storage = $this->entityTypeManager->getStorage('email_list');

        $list = $storage->create([
            'name' => $name,
            'type' => $options['type'] ?? 'static',
            'double_optin' => $options['double_optin'] ?? TRUE,
            'is_active' => TRUE,
            'tenant_id' => $options['tenant_id'] ?? NULL,
        ]);

        $list->save();

        $this->logger->info('Lista de email creada: @name', ['@name' => $name]);

        return $list;
    }

    /**
     * Actualiza el conteo de suscriptores de una lista.
     *
     * Recuenta los suscriptores activos y actualiza el campo
     * subscriber_count de la lista. Útil después de operaciones
     * batch de suscripción/baja.
     *
     * @param int $listId
     *   El ID de la lista a actualizar.
     */
    public function updateSubscriberCount(int $listId): void
    {
        $listStorage = $this->entityTypeManager->getStorage('email_list');
        $subscriberStorage = $this->entityTypeManager->getStorage('email_subscriber');

        $list = $listStorage->load($listId);
        if (!$list) {
            return;
        }

        $count = $subscriberStorage->getQuery()
            ->condition('lists', $listId)
            ->condition('status', 'subscribed')
            ->accessCheck(FALSE)
            ->count()
            ->execute();

        $list->set('subscriber_count', $count);
        $list->save();
    }

}
