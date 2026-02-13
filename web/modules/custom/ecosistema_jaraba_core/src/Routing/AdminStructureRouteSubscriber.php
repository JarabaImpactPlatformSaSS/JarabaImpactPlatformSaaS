<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Overrides admin overview routes with enhanced categorized controllers.
 *
 * Replaces the default flat lists on /admin/structure and /admin/content
 * with categorized, searchable, tabbed overviews.
 *
 * Uses priority -200 to run AFTER Views' route subscriber (-175),
 * which overrides system.admin_content with a Views page display.
 */
class AdminStructureRouteSubscriber extends RouteSubscriberBase
{

    /**
     * {@inheritdoc}
     */
    protected function alterRoutes(RouteCollection $collection): void
    {
        // Override /admin/structure.
        $route = $collection->get('system.admin_structure');
        if ($route) {
            $route->setDefault(
                '_controller',
                '\Drupal\ecosistema_jaraba_core\Controller\AdminStructureController::overview'
            );
        }

        // Override /admin/content.
        // Views sets this to ViewPageController::handle with view_id/display_id.
        // We replace it entirely with our categorized controller.
        $route = $collection->get('system.admin_content');
        if ($route) {
            $route->setDefault(
                '_controller',
                '\Drupal\ecosistema_jaraba_core\Controller\AdminContentController::overview'
            );
            // Remove Views-specific defaults so Drupal doesn't try to resolve them.
            $route->setDefault('view_id', NULL);
            $route->setDefault('display_id', NULL);
            $route->setDefault('_view_display_show_admin_links', NULL);
            $route->setDefault('_title_callback', NULL);
        }
    }

    /**
     * {@inheritdoc}
     *
     * Run at priority -200, after Views' route subscriber (-175).
     */
    public static function getSubscribedEvents(): array
    {
        $events = parent::getSubscribedEvents();
        // Override the priority to ensure we run after Views.
        $events['routing.route_alter'] = ['onAlterRoutes', -200];
        return $events;
    }

}
