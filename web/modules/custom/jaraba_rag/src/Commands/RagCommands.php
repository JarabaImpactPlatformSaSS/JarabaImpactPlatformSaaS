<?php

declare(strict_types=1);

namespace Drupal\jaraba_rag\Commands;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\jaraba_rag\Client\QdrantDirectClient;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for Jaraba RAG / Qdrant management.
 */
class RagCommands extends DrushCommands
{

    /**
     * Collections used by the platform with their vector dimensions.
     */
    protected const COLLECTIONS = [
        'jaraba_kb' => 1536,
        'matching_jobs' => 1536,
        'matching_candidates' => 1536,
        'content_hub_articles' => 1536,
    ];

    public function __construct(
        protected QdrantDirectClient $qdrantClient,
        protected ConfigFactoryInterface $configFactory,
    ) {
        parent::__construct();
    }

    /**
     * Show RAG/Qdrant configuration and connection status.
     */
    #[CLI\Command(name: 'rag:status', aliases: ['rags'])]
    #[CLI\Usage(name: 'drush rag:status', description: 'Show Qdrant connection status')]
    public function status(): void
    {
        $config = $this->configFactory->get('jaraba_rag.settings');

        $host = $config->get('vector_db.host') ?: '(not set)';
        $apiKey = $config->get('vector_db.api_key');
        $disabled = $config->get('disabled');
        $environment = $config->get('environment') ?: '(not set)';
        $collection = $config->get('vector_db.collection') ?: 'jaraba_kb';

        $this->io()->title('Jaraba RAG - Qdrant Status');
        $this->io()->definitionList(
            ['Environment' => $environment],
            ['Host' => $host],
            ['API Key' => $apiKey ? substr($apiKey, 0, 8) . '...' : '(not set)'],
            ['Default Collection' => $collection],
            ['Disabled' => $disabled ? 'YES' : 'NO'],
        );

        if ($disabled) {
            $this->io()->warning('RAG/Qdrant is DISABLED by configuration.');
            $this->io()->note('To enable: set $config[\'jaraba_rag.settings\'][\'disabled\'] = FALSE in settings.local.php');
            return;
        }

        if (empty($config->get('vector_db.host'))) {
            $this->io()->error('Qdrant host is not configured. Cannot ping.');
            return;
        }

        $this->io()->write('Pinging Qdrant... ');
        $start = microtime(TRUE);
        $ok = $this->qdrantClient->ping();
        $latency = round((microtime(TRUE) - $start) * 1000, 1);

        if ($ok) {
            $this->io()->success("Connected! Latency: {$latency}ms");
        }
        else {
            $this->io()->error("Ping FAILED ({$latency}ms). Check host/api_key.");
        }
    }

    /**
     * Create all platform collections in Qdrant.
     */
    #[CLI\Command(name: 'rag:create-collections', aliases: ['ragcc'])]
    #[CLI\Usage(name: 'drush rag:create-collections', description: 'Create all Qdrant collections')]
    public function createCollections(): void
    {
        $config = $this->configFactory->get('jaraba_rag.settings');
        if ($config->get('disabled')) {
            $this->io()->error('RAG/Qdrant is disabled. Enable it first.');
            return;
        }

        $this->io()->title('Creating Qdrant Collections');

        foreach (self::COLLECTIONS as $name => $dimensions) {
            $this->io()->write("  {$name} ({$dimensions}d)... ");
            if ($this->qdrantClient->collectionExists($name)) {
                $this->io()->writeln('<info>already exists</info>');
                continue;
            }
            $ok = $this->qdrantClient->ensureCollection($name, $dimensions);
            if ($ok) {
                $this->io()->writeln('<info>created</info>');
            }
            else {
                $this->io()->writeln('<error>FAILED</error>');
            }
        }

        $this->io()->success('Done.');
    }

    /**
     * Check if a specific collection exists and show its info.
     */
    #[CLI\Command(name: 'rag:collection-info', aliases: ['ragci'])]
    #[CLI\Argument(name: 'collection', description: 'Collection name (default: jaraba_kb)')]
    #[CLI\Usage(name: 'drush rag:collection-info jaraba_kb', description: 'Check if jaraba_kb collection exists')]
    public function collectionInfo(?string $collection = NULL): void
    {
        $collection = $collection ?: $this->configFactory->get('jaraba_rag.settings')->get('vector_db.collection') ?: 'jaraba_kb';

        $config = $this->configFactory->get('jaraba_rag.settings');
        if ($config->get('disabled')) {
            $this->io()->error('RAG/Qdrant is disabled.');
            return;
        }

        $exists = $this->qdrantClient->collectionExists($collection);

        if ($exists) {
            $this->io()->success("Collection '{$collection}' exists.");
        }
        else {
            $this->io()->warning("Collection '{$collection}' does NOT exist. Run: drush rag:create-collections");
        }
    }

}
