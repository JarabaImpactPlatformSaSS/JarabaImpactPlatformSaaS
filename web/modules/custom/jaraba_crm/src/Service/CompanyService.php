<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_crm\Entity\Company;

/**
 * Servicio para gestión de empresas del CRM.
 */
class CompanyService
{

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
    ) {
    }

    /**
     * Obtiene el storage de empresas.
     */
    protected function getStorage()
    {
        return $this->entityTypeManager->getStorage('crm_company');
    }

    /**
     * Crea una nueva empresa.
     *
     * @param array $values
     *   Valores de la empresa.
     *
     * @return \Drupal\jaraba_crm\Entity\Company
     *   La empresa creada.
     */
    public function create(array $values): Company
    {
        $company = $this->getStorage()->create($values);
        $company->save();
        return $company;
    }

    /**
     * Carga una empresa por ID.
     *
     * @param int $id
     *   ID de la empresa.
     *
     * @return \Drupal\jaraba_crm\Entity\Company|null
     *   La empresa o NULL si no existe.
     */
    public function load(int $id): ?Company
    {
        return $this->getStorage()->load($id);
    }

    /**
     * Lista empresas con filtros opcionales.
     *
     * @param array $filters
     *   Filtros opcionales (industry, size, tenant_id).
     * @param int $limit
     *   Límite de resultados.
     *
     * @return \Drupal\jaraba_crm\Entity\Company[]
     *   Array de empresas.
     */
    public function list(array $filters = [], int $limit = 50): array
    {
        $query = $this->getStorage()->getQuery()
            ->accessCheck(TRUE)
            ->range(0, $limit)
            ->sort('name', 'ASC');

        foreach ($filters as $field => $value) {
            $query->condition($field, $value);
        }

        $ids = $query->execute();
        return $ids ? $this->getStorage()->loadMultiple($ids) : [];
    }

    /**
     * Busca empresas por nombre.
     *
     * @param string $search
     *   Término de búsqueda.
     * @param int $limit
     *   Límite de resultados.
     *
     * @return \Drupal\jaraba_crm\Entity\Company[]
     *   Empresas encontradas.
     */
    public function search(string $search, int $limit = 20): array
    {
        $ids = $this->getStorage()->getQuery()
            ->accessCheck(TRUE)
            ->condition('name', $search, 'CONTAINS')
            ->range(0, $limit)
            ->sort('name', 'ASC')
            ->execute();

        return $ids ? $this->getStorage()->loadMultiple($ids) : [];
    }

    /**
     * Cuenta empresas por tenant.
     *
     * @param int|null $tenantId
     *   ID del tenant (opcional).
     *
     * @return int
     *   Número de empresas.
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

    /**
     * Elimina una empresa.
     *
     * @param int $id
     *   ID de la empresa.
     *
     * @return bool
     *   TRUE si se eliminó correctamente.
     */
    public function delete(int $id): bool
    {
        $company = $this->load($id);
        if ($company) {
            $company->delete();
            return TRUE;
        }
        return FALSE;
    }

}
