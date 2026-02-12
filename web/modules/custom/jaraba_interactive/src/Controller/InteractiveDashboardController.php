<?php

declare(strict_types=1);

namespace Drupal\jaraba_interactive\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador del dashboard frontend de contenido interactivo.
 *
 * Renderiza un portal limpio sin regiones Drupal.
 */
class InteractiveDashboardController extends ControllerBase
{

    /**
     * Renderiza el dashboard de contenido interactivo.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return array|Response
     *   El render array.
     */
    public function render(Request $request): array|Response
    {
        // Obtener contenidos del usuario/tenant actual.
        $contents = $this->loadUserContents();
        $stats = $this->calculateStats($contents);

        return [
            '#theme' => 'interactive_dashboard',
            '#contents' => $contents,
            '#stats' => $stats,
            '#attached' => [
                'library' => ['jaraba_interactive/dashboard'],
            ],
        ];
    }

    /**
     * Carga los contenidos del usuario actual.
     */
    protected function loadUserContents(): array
    {
        $storage = $this->entityTypeManager()->getStorage('interactive_content');
        $query = $storage->getQuery()
            ->condition('uid', $this->currentUser()->id())
            ->sort('changed', 'DESC')
            ->accessCheck(TRUE)
            ->range(0, 50);

        $ids = $query->execute();
        return $storage->loadMultiple($ids);
    }

    /**
     * Calcula estadísticas para el dashboard.
     */
    protected function calculateStats(array $contents): array
    {
        $published = 0;
        $draft = 0;
        $totalResults = 0;

        foreach ($contents as $content) {
            if ($content->get('status')->value === 'published') {
                $published++;
            } else {
                $draft++;
            }
        }

        // Contar resultados totales.
        $resultStorage = $this->entityTypeManager()->getStorage('interactive_result');
        $totalResults = $resultStorage->getQuery()
            ->accessCheck(TRUE)
            ->count()
            ->execute();

        return [
            'total' => count($contents),
            'published' => $published,
            'draft' => $draft,
            'results' => $totalResults,
        ];
    }

}
