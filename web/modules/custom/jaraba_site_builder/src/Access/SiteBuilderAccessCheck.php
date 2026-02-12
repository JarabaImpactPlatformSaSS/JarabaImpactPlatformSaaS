<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Access check para las rutas del Site Builder.
 */
class SiteBuilderAccessCheck implements AccessCheckInterface
{

    /**
     * Constructor.
     */
    public function __construct(
        protected AccountInterface $currentUser,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function applies(Route $route): bool
    {
        return $route->hasRequirement('_site_builder_access');
    }

    /**
     * {@inheritdoc}
     */
    public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account): AccessResultInterface
    {
        $requirement = $route->getRequirement('_site_builder_access');

        // Verificar permiso requerido.
        $has_permission = match ($requirement) {
            'view' => $account->hasPermission('view site structure'),
            'edit' => $account->hasPermission('administer site structure'),
            'config' => $account->hasPermission('edit site config'),
            'redirects' => $account->hasPermission('manage site redirects'),
            default => $account->hasPermission('administer site structure'),
        };

        return AccessResult::allowedIf($has_permission)->cachePerPermissions();
    }

}
