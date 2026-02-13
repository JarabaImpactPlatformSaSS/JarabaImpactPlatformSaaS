<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Overrides the system.admin_structure route with an enhanced controller.
 *
 * Replaces the default flat list with a categorized, searchable overview.
 */
class AdminStructureRouteSubscriber extends RouteSubscriberBase
{

    /**
     * {@inheritdoc}
     */
    protected function alterRoutes(RouteCollection $collection): void
    {
        $route = $collection->get('system.admin_structure');
        if ($route) {
            $route->setDefault(
                '_controller',
                '\Drupal\ecosistema_jaraba_core\Controller\AdminStructureController::overview'
            );
            // Remove _title so our controller can set it dynamically if needed.
            // Actually keep _title so breadcrumbs still work.
        }
    }

}
