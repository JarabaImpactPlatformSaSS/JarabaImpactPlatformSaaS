<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_crm\Entity\Contact;

/**
 * Servicio para gestión de contactos con engagement scoring.
 */
class ContactService
{

    /**
     * Pesos para cálculo de engagement.
     */
    protected const ENGAGEMENT_WEIGHTS = [
        'email' => 5,
        'call' => 10,
        'meeting' => 20,
        'proposal' => 15,
        'note' => 2,
    ];

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
    ) {
    }

    /**
     * Obtiene el storage de contactos.
     */
    protected function getStorage()
    {
        return $this->entityTypeManager->getStorage('crm_contact');
    }

    /**
     * Crea un nuevo contacto.
     *
     * @param array $values
     *   Valores del contacto.
     *
     * @return \Drupal\jaraba_crm\Entity\Contact
     *   El contacto creado.
     */
    public function create(array $values): Contact
    {
        $contact = $this->getStorage()->create($values);
        $contact->save();
        return $contact;
    }

    /**
     * Carga un contacto por ID.
     */
    public function load(int $id): ?Contact
    {
        return $this->getStorage()->load($id);
    }

    /**
     * Lista contactos con filtros.
     *
     * @param array $filters
     *   Filtros (company_id, source, tenant_id).
     * @param int $limit
     *   Límite de resultados.
     *
     * @return \Drupal\jaraba_crm\Entity\Contact[]
     *   Array de contactos.
     */
    public function list(array $filters = [], int $limit = 50): array
    {
        $query = $this->getStorage()->getQuery()
            ->accessCheck(TRUE)
            ->range(0, $limit)
            ->sort('created', 'DESC');

        foreach ($filters as $field => $value) {
            $query->condition($field, $value);
        }

        $ids = $query->execute();
        return $ids ? $this->getStorage()->loadMultiple($ids) : [];
    }

    /**
     * Obtiene contactos por empresa.
     *
     * @param int $companyId
     *   ID de la empresa.
     *
     * @return \Drupal\jaraba_crm\Entity\Contact[]
     *   Contactos de la empresa.
     */
    public function getByCompany(int $companyId): array
    {
        return $this->list(['company_id' => $companyId], 100);
    }

    /**
     * Calcula y actualiza el engagement score de un contacto.
     *
     * El score se basa en las actividades asociadas al contacto.
     *
     * @param int $contactId
     *   ID del contacto.
     *
     * @return int
     *   El nuevo score calculado (0-100).
     */
    public function calculateEngagementScore(int $contactId): int
    {
        $contact = $this->load($contactId);
        if (!$contact) {
            return 0;
        }

        // Obtener actividades del contacto.
        $activityStorage = $this->entityTypeManager->getStorage('crm_activity');
        $activityIds = $activityStorage->getQuery()
            ->accessCheck(TRUE)
            ->condition('contact_id', $contactId)
            ->condition('activity_date', strtotime('-90 days'), '>=')
            ->execute();

        if (empty($activityIds)) {
            $score = 0;
        } else {
            $activities = $activityStorage->loadMultiple($activityIds);
            $rawScore = 0;

            foreach ($activities as $activity) {
                $type = $activity->get('type')->value;
                $weight = self::ENGAGEMENT_WEIGHTS[$type] ?? 1;
                $rawScore += $weight;
            }

            // Normalizar a 0-100 (máximo 100 puntos de actividades).
            $score = min(100, $rawScore);
        }

        // Actualizar el contacto.
        $contact->set('engagement_score', $score);
        $contact->save();

        return $score;
    }

    /**
     * Obtiene contactos ordenados por engagement.
     *
     * @param int $limit
     *   Número máximo de resultados.
     * @param int|null $tenantId
     *   ID del tenant (opcional).
     *
     * @return \Drupal\jaraba_crm\Entity\Contact[]
     *   Contactos ordenados por engagement descendente.
     */
    public function getTopEngaged(int $limit = 10, ?int $tenantId = NULL): array
    {
        $query = $this->getStorage()->getQuery()
            ->accessCheck(TRUE)
            ->sort('engagement_score', 'DESC')
            ->range(0, $limit);

        if ($tenantId) {
            $query->condition('tenant_id', $tenantId);
        }

        $ids = $query->execute();
        return $ids ? $this->getStorage()->loadMultiple($ids) : [];
    }

    /**
     * Busca contactos por nombre o email.
     *
     * @param string $search
     *   Término de búsqueda.
     * @param int $limit
     *   Límite de resultados.
     *
     * @return \Drupal\jaraba_crm\Entity\Contact[]
     *   Contactos encontrados.
     */
    public function search(string $search, int $limit = 20): array
    {
        $query = $this->getStorage()->getQuery()
            ->accessCheck(TRUE)
            ->range(0, $limit);

        $orGroup = $query->orConditionGroup()
            ->condition('first_name', $search, 'CONTAINS')
            ->condition('last_name', $search, 'CONTAINS')
            ->condition('email', $search, 'CONTAINS');

        $ids = $query->condition($orGroup)->execute();
        return $ids ? $this->getStorage()->loadMultiple($ids) : [];
    }

    /**
     * Cuenta contactos.
     */
    public function count(?int $tenantId = NULL): int
    {
        $query = $this->getStorage()->getQuery()
            ->accessCheck(TRUE)
            ->count();

        if ($tenantId) {
            $query->condition('tenant_id', $tenantId);
        }

        return (int) $query->execute();
    }

}
