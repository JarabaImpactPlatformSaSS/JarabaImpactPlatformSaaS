<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_crm\Entity\Activity;

/**
 * Servicio para gestión de actividades y timeline.
 */
class ActivityService
{

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
    ) {
    }

    /**
     * Obtiene el storage de actividades.
     */
    protected function getStorage()
    {
        return $this->entityTypeManager->getStorage('crm_activity');
    }

    /**
     * Crea una nueva actividad.
     */
    public function create(array $values): Activity
    {
        $activity = $this->getStorage()->create($values);
        $activity->save();
        return $activity;
    }

    /**
     * Carga una actividad por ID.
     */
    public function load(int $id): ?Activity
    {
        return $this->getStorage()->load($id);
    }

    /**
     * Obtiene el timeline de actividades de un contacto.
     *
     * @param int $contactId
     *   ID del contacto.
     * @param int $limit
     *   Límite de actividades.
     *
     * @return \Drupal\jaraba_crm\Entity\Activity[]
     *   Actividades ordenadas por fecha descendente.
     */
    public function getContactTimeline(int $contactId, int $limit = 50): array
    {
        $ids = $this->getStorage()->getQuery()
            ->accessCheck(TRUE)
            ->condition('contact_id', $contactId)
            ->sort('activity_date', 'DESC')
            ->range(0, $limit)
            ->execute();

        return $ids ? $this->getStorage()->loadMultiple($ids) : [];
    }

    /**
     * Obtiene actividades de una oportunidad.
     *
     * @param int $opportunityId
     *   ID de la oportunidad.
     *
     * @return \Drupal\jaraba_crm\Entity\Activity[]
     *   Actividades de la oportunidad.
     */
    public function getOpportunityActivities(int $opportunityId): array
    {
        $ids = $this->getStorage()->getQuery()
            ->accessCheck(TRUE)
            ->condition('opportunity_id', $opportunityId)
            ->sort('activity_date', 'DESC')
            ->execute();

        return $ids ? $this->getStorage()->loadMultiple($ids) : [];
    }

    /**
     * Obtiene actividades recientes del tenant.
     *
     * @param int|null $tenantId
     *   ID del tenant (opcional).
     * @param int $limit
     *   Límite de resultados.
     *
     * @return \Drupal\jaraba_crm\Entity\Activity[]
     *   Actividades más recientes.
     */
    public function getRecent(?int $tenantId = NULL, int $limit = 10): array
    {
        $query = $this->getStorage()->getQuery()
            ->accessCheck(TRUE)
            ->sort('activity_date', 'DESC')
            ->range(0, $limit);

        if ($tenantId) {
            $query->condition('tenant_id', $tenantId);
        }

        $ids = $query->execute();
        return $ids ? $this->getStorage()->loadMultiple($ids) : [];
    }

    /**
     * Obtiene actividades de hoy.
     *
     * @param int|null $tenantId
     *   ID del tenant (opcional).
     *
     * @return \Drupal\jaraba_crm\Entity\Activity[]
     *   Actividades programadas para hoy.
     */
    public function getToday(?int $tenantId = NULL): array
    {
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));

        $query = $this->getStorage()->getQuery()
            ->accessCheck(TRUE)
            ->condition('activity_date', $today, '>=')
            ->condition('activity_date', $tomorrow, '<')
            ->sort('activity_date', 'ASC');

        if ($tenantId) {
            $query->condition('tenant_id', $tenantId);
        }

        $ids = $query->execute();
        return $ids ? $this->getStorage()->loadMultiple($ids) : [];
    }

    /**
     * Obtiene actividades por tipo.
     *
     * @param string $type
     *   Tipo de actividad.
     * @param int|null $tenantId
     *   ID del tenant.
     * @param int $limit
     *   Límite.
     *
     * @return \Drupal\jaraba_crm\Entity\Activity[]
     *   Actividades del tipo especificado.
     */
    public function getByType(string $type, ?int $tenantId = NULL, int $limit = 50): array
    {
        $query = $this->getStorage()->getQuery()
            ->accessCheck(TRUE)
            ->condition('type', $type)
            ->sort('activity_date', 'DESC')
            ->range(0, $limit);

        if ($tenantId) {
            $query->condition('tenant_id', $tenantId);
        }

        $ids = $query->execute();
        return $ids ? $this->getStorage()->loadMultiple($ids) : [];
    }

    /**
     * Registra una llamada rápida.
     *
     * @param int $contactId
     *   ID del contacto.
     * @param string $subject
     *   Asunto de la llamada.
     * @param int $duration
     *   Duración en minutos.
     * @param string|null $notes
     *   Notas opcionales.
     *
     * @return \Drupal\jaraba_crm\Entity\Activity
     *   La actividad creada.
     */
    public function logCall(int $contactId, string $subject, int $duration, ?string $notes = NULL): Activity
    {
        return $this->create([
            'contact_id' => $contactId,
            'subject' => $subject,
            'type' => 'call',
            'duration' => $duration,
            'notes' => $notes ? ['value' => $notes, 'format' => 'basic_html'] : NULL,
            'activity_date' => date('Y-m-d\TH:i:s'),
        ]);
    }

    /**
     * Registra un email enviado.
     *
     * @param int $contactId
     *   ID del contacto.
     * @param string $subject
     *   Asunto del email.
     * @param string|null $notes
     *   Notas opcionales.
     *
     * @return \Drupal\jaraba_crm\Entity\Activity
     *   La actividad creada.
     */
    public function logEmail(int $contactId, string $subject, ?string $notes = NULL): Activity
    {
        return $this->create([
            'contact_id' => $contactId,
            'subject' => $subject,
            'type' => 'email',
            'notes' => $notes ? ['value' => $notes, 'format' => 'basic_html'] : NULL,
            'activity_date' => date('Y-m-d\TH:i:s'),
        ]);
    }

    /**
     * Registra una actividad genérica desde módulos externos.
     *
     * Acepta un array flexible con type, contact_email, stage y data,
     * y crea una Activity CRM si encuentra un contacto asociado.
     */
    public function logActivity(array $params): ?Activity
    {
        $type = $params['type'] ?? 'note';
        $contactEmail = $params['contact_email'] ?? NULL;
        $data = $params['data'] ?? [];
        $stage = $params['stage'] ?? '';

        // Buscar contacto CRM por email.
        $contactId = NULL;
        if ($contactEmail) {
            $contactIds = $this->getStorage()
                ->getEntityType()
                ->getClass();
            // Use entity query on crm_contact.
            $ids = \Drupal::entityTypeManager()
                ->getStorage('crm_contact')
                ->getQuery()
                ->accessCheck(FALSE)
                ->condition('email', $contactEmail)
                ->range(0, 1)
                ->execute();
            $contactId = $ids ? (int) reset($ids) : NULL;
        }

        if (!$contactId) {
            \Drupal::logger('jaraba_crm')->notice(
                'logActivity: No CRM contact found for email @email, skipping.',
                ['@email' => $contactEmail ?? 'N/A']
            );
            return NULL;
        }

        $subject = ucfirst(str_replace('_', ' ', $type));
        if (!empty($data['fase'])) {
            $subject .= ': ' . $data['fase'];
        }

        return $this->create([
            'contact_id' => $contactId,
            'subject' => $subject,
            'type' => 'note',
            'notes' => [
                'value' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                'format' => 'basic_html',
            ],
            'activity_date' => date('Y-m-d\TH:i:s'),
        ]);
    }

    /**
     * Cuenta actividades.
     */
    public function count(?int $tenantId = NULL, ?string $type = NULL): int
    {
        $query = $this->getStorage()->getQuery()
            ->accessCheck(TRUE)
            ->count();

        if ($tenantId) {
            $query->condition('tenant_id', $tenantId);
        }

        if ($type) {
            $query->condition('type', $type);
        }

        return (int) $query->execute();
    }

}
