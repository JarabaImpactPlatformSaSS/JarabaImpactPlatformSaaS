<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\Role;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller para la Matriz RBAC visual.
 *
 * Proporciona una vista de todos los permisos por rol
 * en formato de matrix (grid) para facilitar la gestión.
 */
class RbacMatrixController extends ControllerBase
{

    /**
     * Página principal de la matriz RBAC.
     */
    public function matrix(): array
    {
        $matrixData = $this->buildMatrix();

        return [
            '#theme' => 'rbac_matrix',
            '#roles' => $matrixData['roles'],
            '#permissions' => $matrixData['permissions'],
            '#matrix' => $matrixData['matrix'],
            '#attached' => [
                'library' => ['ecosistema_jaraba_core/rbac-matrix'],
            ],
        ];
    }

    /**
     * API: Obtiene la matriz de permisos completa.
     */
    public function getMatrix(Request $request): JsonResponse
    {
        $module = $request->query->get('module', '');
        $matrixData = $this->buildMatrix($module ?: NULL);

        return new JsonResponse($matrixData);
    }

    /**
     * API: Toggle un permiso para un rol.
     */
    public function togglePermission(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['role']) || empty($data['permission'])) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $this->t('role y permission son requeridos'),
            ], 400);
        }

        $roleId = $data['role'];
        $permission = $data['permission'];
        $enabled = (bool) ($data['enabled'] ?? FALSE);

        $role = Role::load($roleId);
        if (!$role) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $this->t('Rol no encontrado'),
            ], 404);
        }

        if ($enabled) {
            $role->grantPermission($permission);
        } else {
            $role->revokePermission($permission);
        }

        $role->save();

        return new JsonResponse([
            'success' => TRUE,
            'role' => $roleId,
            'permission' => $permission,
            'enabled' => $enabled,
        ]);
    }

    /**
     * Export de la matriz en formato CSV.
     */
    public function exportCsv(): Response
    {
        $matrixData = $this->buildMatrix();
        $csv = [];

        // Header row
        $header = ['Permission'];
        foreach ($matrixData['roles'] as $role) {
            $header[] = $role['label'];
        }
        $csv[] = implode(',', $header);

        // Data rows
        foreach ($matrixData['permissions'] as $perm) {
            $row = ['"' . str_replace('"', '""', $perm['title']) . '"'];
            foreach ($matrixData['roles'] as $role) {
                $hasPermission = $matrixData['matrix'][$perm['id']][$role['id']] ?? FALSE;
                $row[] = $hasPermission ? '1' : '0';
            }
            $csv[] = implode(',', $row);
        }

        $content = implode("\n", $csv);

        return new Response($content, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="rbac-matrix-' . date('Y-m-d') . '.csv"',
        ]);
    }

    /**
     * Construye la matriz de permisos.
     *
     * @param string|null $moduleFilter
     *   Filtrar por módulo (opcional).
     *
     * @return array
     *   Array con roles, permissions y matrix.
     */
    protected function buildMatrix(?string $moduleFilter = NULL): array
    {
        // Obtener todos los roles (excepto anonymous y authenticated).
        $roleStorage = $this->entityTypeManager()->getStorage('user_role');
        $roles = $roleStorage->loadMultiple();

        $rolesData = [];
        foreach ($roles as $role) {
            if (!in_array($role->id(), ['anonymous', 'authenticated'])) {
                $rolesData[] = [
                    'id' => $role->id(),
                    'label' => $role->label(),
                ];
            }
        }

        // Obtener permisos agrupados por módulo.
        $permissionHandler = \Drupal::service('user.permissions');
        $allPermissions = $permissionHandler->getPermissions();

        $permissionsData = [];
        $moduleGroups = [];

        foreach ($allPermissions as $permissionId => $permission) {
            $module = $permission['provider'];

            // Filtrar por módulo si se especifica.
            if ($moduleFilter && $module !== $moduleFilter) {
                continue;
            }

            if (!isset($moduleGroups[$module])) {
                $moduleGroups[$module] = [];
            }

            $permInfo = [
                'id' => $permissionId,
                'title' => (string) $permission['title'],
                'module' => $module,
                'description' => (string) ($permission['description'] ?? ''),
                'restrict_access' => $permission['restrict access'] ?? FALSE,
            ];

            $moduleGroups[$module][] = $permInfo;
            $permissionsData[] = $permInfo;
        }

        // Construir la matriz.
        $matrix = [];
        foreach ($permissionsData as $perm) {
            $matrix[$perm['id']] = [];
            foreach ($roles as $role) {
                if (in_array($role->id(), ['anonymous', 'authenticated'])) {
                    continue;
                }
                $matrix[$perm['id']][$role->id()] = $role->hasPermission($perm['id']);
            }
        }

        // Obtener lista de módulos para filtro.
        $modules = array_keys($moduleGroups);
        sort($modules);

        return [
            'roles' => $rolesData,
            'permissions' => $permissionsData,
            'matrix' => $matrix,
            'modules' => $modules,
            'module_groups' => $moduleGroups,
        ];
    }

}
