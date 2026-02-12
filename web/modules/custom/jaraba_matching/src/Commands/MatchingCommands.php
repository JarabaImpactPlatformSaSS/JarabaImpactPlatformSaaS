<?php

declare(strict_types=1);

namespace Drupal\jaraba_matching\Commands;

use Drupal\jaraba_matching\Service\IndexingService;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for Matching Engine.
 */
class MatchingCommands extends DrushCommands
{

    /**
     * The indexing service.
     *
     * @var \Drupal\jaraba_matching\Service\IndexingService
     */
    protected $indexingService;

    /**
     * Constructor.
     */
    public function __construct(IndexingService $indexing_service)
    {
        parent::__construct();
        $this->indexingService = $indexing_service;
    }

    /**
     * Index all published jobs in Qdrant.
     *
     * @command matching:index-jobs
     * @aliases mij
     * @option limit Limit the number of jobs to index (0 = all)
     * @usage matching:index-jobs
     *   Index all published jobs.
     * @usage matching:index-jobs --limit=100
     *   Index first 100 jobs.
     */
    public function indexJobs(array $options = ['limit' => 0]): void
    {
        $limit = (int) $options['limit'];

        $this->io()->title('Indexing Jobs in Qdrant');

        $result = $this->indexingService->indexAllJobs($limit, function ($current, $total) {
            // Progress callback
        });

        $this->io()->success(sprintf(
            'Indexed %d jobs (%d errors) of %d total',
            $result['indexed'],
            $result['errors'],
            $result['total']
        ));
    }

    /**
     * Index all active candidates in Qdrant.
     *
     * @command matching:index-candidates
     * @aliases mic
     * @option limit Limit the number of candidates to index (0 = all)
     * @usage matching:index-candidates
     *   Index all active candidates.
     * @usage matching:index-candidates --limit=50
     *   Index first 50 candidates.
     */
    public function indexCandidates(array $options = ['limit' => 0]): void
    {
        $limit = (int) $options['limit'];

        $this->io()->title('Indexing Candidates in Qdrant');

        $result = $this->indexingService->indexAllCandidates($limit, function ($current, $total) {
            // Progress callback
        });

        $this->io()->success(sprintf(
            'Indexed %d candidates (%d errors) of %d total',
            $result['indexed'],
            $result['errors'],
            $result['total']
        ));
    }

    /**
     * Index all jobs and candidates.
     *
     * @command matching:index-all
     * @aliases mia
     * @usage matching:index-all
     *   Index all jobs and candidates.
     */
    public function indexAll(): void
    {
        $this->io()->title('Full Matching Index Rebuild');

        $this->indexJobs();
        $this->indexCandidates();

        $this->io()->success('Full index rebuild complete');
    }

}
