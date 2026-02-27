<?php

declare(strict_types=1);

namespace Drupal\jaraba_success_cases\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for Success Cases frontend pages and API.
 *
 * Provides three endpoints:
 * - list(): Grid of published success cases with optional vertical filter.
 * - detail($slug): Individual success case detail page.
 * - apiList(): JSON API for GrapesJS Page Builder integration.
 *
 * Following the Content Hub Blog Pattern:
 * Controller → #theme render array → Template → page--success-cases.html.twig
 */
class SuccessCasesController extends ControllerBase
{

    /**
     * The renderer service.
     *
     * @var \Drupal\Core\Render\RendererInterface
     */
    protected RendererInterface $renderer;

    /**
     * Constructs a SuccessCasesController.
     *
     * Note: PHP 8.4 / DRUPAL11-002 — entityTypeManager and currentUser
     * are NOT promoted (no `protected` keyword) because they are inherited
     * from ControllerBase without explicit type declarations.
     *
     * @param \Drupal\Core\Render\RendererInterface $renderer
     *   The renderer service.
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     *   The entity type manager.
     */
    public function __construct(
        RendererInterface $renderer,
        EntityTypeManagerInterface $entityTypeManager,
    ) {
        $this->renderer = $renderer;
        // DRUPAL11-002: Assign manually, not via constructor promotion.
        $this->entityTypeManager = $entityTypeManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('renderer'),
            $container->get('entity_type.manager'),
        );
    }

    /**
     * Renders the success cases list page.
     *
     * Route: /casos-de-exito
     * Supports ?vertical=emprendimiento query parameter for filtering.
     * Pagination via ?page=N (Drupal's standard pager).
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The current request.
     *
     * @return array
     *   Render array with #theme = success_cases_list.
     */
    public function list(Request $request): array
    {
        $storage = $this->entityTypeManager->getStorage('success_case');

        $query = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('status', 1)
            ->sort('weight', 'ASC')
            ->sort('created', 'DESC');

        // Optional vertical filter.
        $vertical_filter = $request->query->get('vertical');
        if ($vertical_filter && is_string($vertical_filter)) {
            $query->condition('vertical', $vertical_filter);
        }

        // Pager: 12 items per page (4x3 grid).
        $query->pager(12);
        $ids = $query->execute();
        $cases = $storage->loadMultiple($ids);

        // Build card data for the template.
        $cards = [];
        foreach ($cases as $case) {
            $cards[] = $this->buildCardData($case);
        }

        // Get available verticals for filter.
        $verticals = $this->getAvailableVerticals();

        return [
            '#theme' => 'success_cases_list',
            '#cases' => $cards,
            '#verticals' => $verticals,
            '#current_vertical' => $vertical_filter,
            '#pager' => ['#type' => 'pager'],
            '#cache' => [
                'tags' => ['success_case_list'],
                'contexts' => ['url.query_args:vertical', 'url.query_args:page'],
            ],
            '#attached' => [
                'library' => ['jaraba_success_cases/success-cases'],
            ],
        ];
    }

    /**
     * Renders a single success case detail page.
     *
     * Route: /caso-de-exito/{slug}
     *
     * @param string $slug
     *   The URL slug of the success case.
     *
     * @return array
     *   Render array with #theme = success_case_detail.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *   If no published success case matches the slug.
     */
    public function detail(string $slug): array
    {
        $storage = $this->entityTypeManager->getStorage('success_case');

        $ids = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('slug', $slug)
            ->condition('status', 1)
            ->range(0, 1)
            ->execute();

        if (empty($ids)) {
            throw new NotFoundHttpException();
        }

        $case = $storage->load(reset($ids));
        $data = $this->buildDetailData($case);

        return [
            '#theme' => 'success_case_detail',
            '#case' => $data,
            '#cache' => [
                'tags' => ['success_case:' . $case->id()],
            ],
            '#attached' => [
                'library' => ['jaraba_success_cases/success-cases'],
            ],
        ];
    }

    /**
     * Returns JSON list of published success cases for PB integration.
     *
     * Route: /api/success-cases
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON array of success case objects.
     */
    public function apiList(): JsonResponse
    {
        $storage = $this->entityTypeManager->getStorage('success_case');

        $ids = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('status', 1)
            ->sort('weight', 'ASC')
            ->sort('created', 'DESC')
            ->execute();

        $cases = $storage->loadMultiple($ids);
        $data = [];

        foreach ($cases as $case) {
            $data[] = $this->buildCardData($case);
        }

        return new JsonResponse([
            'success' => TRUE,
            'data' => $data,
            'meta' => [
                'total' => count($data),
            ],
        ]);
    }

    /**
     * Builds card-level data from a SuccessCase entity.
     *
     * Used for list view and API response.
     *
     * @param \Drupal\jaraba_success_cases\Entity\SuccessCase $case
     *   The success case entity.
     *
     * @return array
     *   Flat array of primitive values for template rendering.
     */
    protected function buildCardData($case): array
    {
        $metrics = [];
        $metrics_raw = $case->get('metrics_json')->value;
        if ($metrics_raw) {
            $decoded = json_decode($metrics_raw, TRUE);
            if (is_array($decoded)) {
                $metrics = $decoded;
            }
        }

        return [
            'id' => (int) $case->id(),
            'name' => $case->get('name')->value,
            'slug' => $case->get('slug')->value,
            'profession' => $case->get('profession')->value,
            'company' => $case->get('company')->value,
            'sector' => $case->get('sector')->value,
            'location' => $case->get('location')->value,
            'vertical' => $case->get('vertical')->value,
            'quote_short' => $case->get('quote_short')->value,
            'rating' => (int) $case->get('rating')->value,
            'featured' => (bool) $case->get('featured')->value,
            'program_name' => $case->get('program_name')->value,
            'metrics' => $metrics,
            'url' => '/caso-de-exito/' . $case->get('slug')->value,
        ];
    }

    /**
     * Builds full detail data from a SuccessCase entity.
     *
     * Used for the detail page.
     *
     * @param \Drupal\jaraba_success_cases\Entity\SuccessCase $case
     *   The success case entity.
     *
     * @return array
     *   Full data array for the detail template.
     */
    protected function buildDetailData($case): array
    {
        $data = $this->buildCardData($case);

        // Add narrative fields.
        $data['challenge_before'] = $case->get('challenge_before')->value;
        $data['challenge_before_format'] = $case->get('challenge_before')->format ?? 'basic_html';
        $data['solution_during'] = $case->get('solution_during')->value;
        $data['solution_during_format'] = $case->get('solution_during')->format ?? 'basic_html';
        $data['result_after'] = $case->get('result_after')->value;
        $data['result_after_format'] = $case->get('result_after')->format ?? 'basic_html';

        // Add long quote.
        $data['quote_long'] = $case->get('quote_long')->value;

        // Add SEO.
        $data['meta_description'] = $case->get('meta_description')->value;

        // Add links.
        $data['website'] = $case->get('website')->value;
        $data['linkedin'] = $case->get('linkedin')->value;

        // Add program context.
        $data['program_funder'] = $case->get('program_funder')->value;
        $data['program_year'] = $case->get('program_year')->value;

        return $data;
    }

    /**
     * Retrieves the list of verticals that have published success cases.
     *
     * @return array
     *   Array of ['value' => 'machine_name', 'label' => 'Display Name'] items.
     */
    protected function getAvailableVerticals(): array
    {
        $storage = $this->entityTypeManager->getStorage('success_case');

        $ids = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('status', 1)
            ->execute();

        $verticals = [];
        if (!empty($ids)) {
            $cases = $storage->loadMultiple($ids);
            foreach ($cases as $case) {
                $v = $case->get('vertical')->value;
                if ($v && !isset($verticals[$v])) {
                    $verticals[$v] = [
                        'value' => $v,
                        'label' => ucfirst(str_replace('_', ' ', $v)),
                    ];
                }
            }
        }

        return array_values($verticals);
    }

}
