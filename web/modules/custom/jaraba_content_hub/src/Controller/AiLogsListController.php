<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for frontend AI generation logs listing (tenant editors).
 */
class AiLogsListController extends ControllerBase
{

    /**
     * Items per page.
     */
    protected const ITEMS_PER_PAGE = 25;

    /**
     * The pager manager.
     */
    protected PagerManagerInterface $pagerManager;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        $instance = parent::create($container);
        $instance->pagerManager = $container->get('pager.manager');
        return $instance;
    }

    /**
     * Lists AI generation logs for tenant editors (frontend theme).
     *
     * @return array
     *   Render array for the logs list.
     */
    public function list(): array
    {
        $storage = $this->entityTypeManager()->getStorage('ai_generation_log');
        $query = $storage->getQuery()
            ->accessCheck(TRUE)
            ->sort('created', 'DESC');

        // Paginación.
        $count_query = clone $query;
        $total = $count_query->count()->execute();
        $page = $this->pagerManager->createPager($total, self::ITEMS_PER_PAGE)->getCurrentPage();
        $query->range($page * self::ITEMS_PER_PAGE, self::ITEMS_PER_PAGE);

        $ids = $query->execute();
        $logs = $storage->loadMultiple($ids);

        $rows = [];
        foreach ($logs as $log) {
            /** @var \Drupal\jaraba_content_hub\Entity\AiGenerationLog $log */
            $rows[] = [
                'id' => $log->id(),
                'action' => $log->get('action')->value ?? 'generate',
                'model' => $log->get('model')->value ?? '-',
                'status' => $log->get('status')->value ?? 'success',
                'tokens_used' => $log->get('tokens_used')->value ?? 0,
                'created' => $log->get('created')->value,
                'article_title' => $log->get('article_id')->entity?->label() ?? '-',
            ];
        }

        return [
            '#theme' => 'content_hub_ai_logs_list',
            '#logs' => $rows,
            '#total_count' => $total,
            '#pager' => [
                '#type' => 'pager',
            ],
            '#back_url' => Url::fromRoute('jaraba_content_hub.dashboard.frontend')->toString(),
            '#cache' => [
                'tags' => ['ai_generation_log_list'],
            ],
            '#attached' => [
                'library' => ['ecosistema_jaraba_theme/content-hub'],
            ],
        ];
    }

}
