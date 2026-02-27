<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Tool;

use Drupal\jaraba_ai_agents\Service\BrowserAgentService;
use Psr\Log\LoggerInterface;

/**
 * Web scraping tool for browser-based data extraction.
 *
 * Delegates to BrowserAgentService for URL allowlist enforcement
 * and headless browser execution. Returns structured extracted data.
 */
class WebScrapingTool extends BaseTool {

  public function __construct(
    LoggerInterface $logger,
    protected readonly BrowserAgentService $browserAgent,
  ) {
    parent::__construct($logger);
  }

  public function getId(): string {
    return 'web_scraping';
  }

  public function getLabel(): string {
    return 'Web Scraping';
  }

  public function getDescription(): string {
    return 'Extracts structured data from web pages using headless browser. URL must be in the vertical allowlist.';
  }

  public function getParameters(): array {
    return [
      'url' => [
        'type' => 'string',
        'required' => TRUE,
        'description' => 'Target URL to scrape.',
      ],
      'selectors' => [
        'type' => 'array',
        'required' => FALSE,
        'description' => 'CSS selectors for targeted extraction.',
      ],
      'wait_for' => [
        'type' => 'string',
        'required' => FALSE,
        'description' => 'CSS selector to wait for before extracting.',
      ],
    ];
  }

  public function execute(array $params, array $context = []): array {
    $url = $params['url'] ?? '';
    if (empty($url)) {
      return $this->error('URL is required.');
    }

    $result = $this->browserAgent->execute('web_scraping', $url, $params, $context);

    if (!$result['success']) {
      return $this->error($result['error'] ?? 'Scraping failed.');
    }

    return $this->success($result['data']);
  }

}
