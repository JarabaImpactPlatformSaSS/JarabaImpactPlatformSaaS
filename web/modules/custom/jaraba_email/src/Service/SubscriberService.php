<?php

declare(strict_types=1);

namespace Drupal\jaraba_email\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_email\Entity\EmailSubscriber;
use Psr\Log\LoggerInterface;

/**
 * Servicio para gestionar suscriptores de email.
 *
 * PROPÓSITO:
 * Gestiona el ciclo de vida completo de suscriptores de email,
 * incluyendo suscripción, baja, y consultas por lista.
 *
 * CARACTERÍSTICAS:
 * - Alta/baja de suscriptores con detección de duplicados
 * - Soporte multi-lista (un suscriptor puede estar en varias listas)
 * - Registro de consentimiento GDPR
 * - Tracking de fuente de suscripción
 * - Integración multi-tenant
 *
 * ESPECIFICACIÓN: Doc 139 - Marketing_AI_Stack_Native
 */
class SubscriberService
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
     * Construye un SubscriberService.
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
     * Suscribe un email a una lista.
     *
     * Si el email ya existe, añade la lista sin crear duplicado.
     * Si es nuevo, crea la entidad con los datos proporcionados.
     *
     * @param string $email
     *   La dirección de email.
     * @param int $listId
     *   El ID de la lista.
     * @param array $data
     *   Datos adicionales del suscriptor:
     *   - 'first_name': Nombre.
     *   - 'last_name': Apellido.
     *   - 'confirmed': Si ya está confirmado (double opt-in).
     *   - 'source': Fuente de suscripción (form, import, api).
     *   - 'gdpr_consent': Si aceptó términos GDPR.
     *   - 'ip_address': IP del usuario.
     *   - 'tenant_id': ID del tenant.
     *
     * @return \Drupal\jaraba_email\Entity\EmailSubscriber
     *   La entidad suscriptor (nueva o existente).
     */
    public function subscribe(string $email, int $listId, array $data = []): EmailSubscriber
    {
        $storage = $this->entityTypeManager->getStorage('email_subscriber');

        // Verificar si ya existe.
        $existing = $this->findByEmail($email);

        if ($existing) {
            // Añadir a lista si no está ya.
            $lists = [];
            foreach ($existing->get('lists') as $item) {
                $lists[] = $item->target_id;
            }

            if (!in_array($listId, $lists)) {
                $lists[] = $listId;
                $existing->set('lists', array_map(fn($id) => ['target_id' => $id], $lists));
                $existing->save();
            }

            return $existing;
        }

        // Crear nuevo suscriptor.
        $subscriber = $storage->create([
            'email' => $email,
            'first_name' => $data['first_name'] ?? '',
            'last_name' => $data['last_name'] ?? '',
            'status' => $data['confirmed'] ?? FALSE ? 'subscribed' : 'pending',
            'source' => $data['source'] ?? 'form',
            'source_detail' => $data['source_detail'] ?? '',
            'gdpr_consent' => $data['gdpr_consent'] ?? FALSE,
            'gdpr_consent_at' => $data['gdpr_consent'] ? date('Y-m-d\TH:i:s') : NULL,
            'ip_address' => $data['ip_address'] ?? '',
            'lists' => [['target_id' => $listId]],
            'tenant_id' => $data['tenant_id'] ?? NULL,
        ]);

        $subscriber->save();

        $this->logger->info('Nuevo suscriptor: @email en lista @list', [
            '@email' => $email,
            '@list' => $listId,
        ]);

        return $subscriber;
    }

    /**
     * Busca un suscriptor por email.
     *
     * @param string $email
     *   La dirección de email a buscar.
     *
     * @return \Drupal\jaraba_email\Entity\EmailSubscriber|null
     *   El suscriptor encontrado o NULL si no existe.
     */
    public function findByEmail(string $email): ?EmailSubscriber
    {
        $storage = $this->entityTypeManager->getStorage('email_subscriber');

        $ids = $storage->getQuery()
            ->condition('email', $email)
            ->accessCheck(FALSE)
            ->range(0, 1)
            ->execute();

        if (empty($ids)) {
            return NULL;
        }

        return $storage->load(reset($ids));
    }

    /**
     * Da de baja un email de una lista o de todas.
     *
     * Si se especifica listId, elimina solo de esa lista.
     * Si listId es NULL, marca el suscriptor como dado de baja
     * de todas las listas.
     *
     * @param string $email
     *   La dirección de email.
     * @param int|null $listId
     *   El ID de la lista, o NULL para baja total.
     * @param string $reason
     *   Razón opcional de la baja (feedback).
     *
     * @return bool
     *   TRUE si se procesó correctamente la baja.
     */
    public function unsubscribe(string $email, ?int $listId = NULL, string $reason = ''): bool
    {
        $subscriber = $this->findByEmail($email);

        if (!$subscriber) {
            return FALSE;
        }

        if ($listId === NULL) {
            // Baja total de todas las listas.
            $subscriber->set('status', 'unsubscribed');
            $subscriber->set('unsubscribed_at', date('Y-m-d\TH:i:s'));
            $subscriber->set('unsubscribe_reason', $reason);
        } else {
            // Eliminar de lista específica.
            $lists = [];
            foreach ($subscriber->get('lists') as $item) {
                if ($item->target_id != $listId) {
                    $lists[] = ['target_id' => $item->target_id];
                }
            }
            $subscriber->set('lists', $lists);

            // Si no quedan listas, marcar como dado de baja.
            if (empty($lists)) {
                $subscriber->set('status', 'unsubscribed');
                $subscriber->set('unsubscribed_at', date('Y-m-d\TH:i:s'));
            }
        }

        $subscriber->save();

        $this->logger->info('Baja de suscripción: @email', ['@email' => $email]);

        return TRUE;
    }

    /**
     * Obtiene suscriptores activos de una lista.
     *
     * Solo retorna suscriptores con status 'subscribed'.
     *
     * @param int $listId
     *   El ID de la lista.
     * @param int $limit
     *   Número máximo a retornar.
     * @param int $offset
     *   Offset para paginación.
     *
     * @return array
     *   Array de entidades EmailSubscriber.
     */
    public function getActiveSubscribers(int $listId, int $limit = 100, int $offset = 0): array
    {
        $storage = $this->entityTypeManager->getStorage('email_subscriber');

        $ids = $storage->getQuery()
            ->condition('lists', $listId)
            ->condition('status', 'subscribed')
            ->range($offset, $limit)
            ->accessCheck(FALSE)
            ->execute();

        return $storage->loadMultiple($ids);
    }

    /**
     * Cuenta suscriptores activos de una lista.
     *
     * @param int $listId
     *   El ID de la lista.
     *
     * @return int
     *   El conteo de suscriptores.
     */
    public function countActiveSubscribers(int $listId): int
    {
        $storage = $this->entityTypeManager->getStorage('email_subscriber');

        return (int) $storage->getQuery()
            ->condition('lists', $listId)
            ->condition('status', 'subscribed')
            ->accessCheck(FALSE)
            ->count()
            ->execute();
    }

}
