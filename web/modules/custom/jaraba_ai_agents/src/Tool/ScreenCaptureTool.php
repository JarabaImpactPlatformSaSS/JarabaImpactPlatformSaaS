<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Tool;

use Drupal\jaraba_ai_agents\Service\BrowserAgentService;
use Psr\Log\LoggerInterface;

/**
 * Screen capture tool for headless browser screenshots.
 *
 * Captures screenshots of web pages for visual analysis or documentation.
 * URL must be in the vertical allowlist.
 */
class ScreenCaptureTool extends BaseTool {

  public function __construct(
    LoggerInterface $logger,
    protected readonly BrowserAgentService $browserAgent,
  ) {
    parent::__construct($logger);
  }

  public function getId(): string {
    return 'screen_capture';
  }

  public function getLabel(): string {
    return 'Screen Capture';
  }

  public function getDescription(): string {
    return 'Captures a screenshot of a web page using headless browser. URL must be in the vertical allowlist.';
  }

  public function getParameters(): array {
    return [
      'url' => [
        'type' => 'string',
        'required' => TRUE,
        'description' => 'URL of the page to capture.',
      ],
      'viewport_width' => [
        'type' => 'int',
        'required' => FALSE,
        'description' => 'Browser viewport width in pixels.',
        'default' => 1280,
      ],
      'viewport_height' => [
        'type' => 'int',
        'required' => FALSE,
        'description' => 'Browser viewport height in pixels.',
        'default' => 800,
      ],
      'full_page' => [
        'type' => 'bool',
        'required' => FALSE,
        'description' => 'Capture full page instead of viewport only.',
        'default' => FALSE,
      ],
    ];
  }

  public function execute(array $params, array $context = []): array {
    $url = $params['url'] ?? '';
    if (empty($url)) {
      return $this->error('URL is required.');
    }

    $result = $this->browserAgent->execute('screen_capture', $url, $params, $context);

    if (!$result['success']) {
      return $this->error($result['error'] ?? 'Screen capture failed.');
    }

    return $this->success($result['data']);
  }

}
