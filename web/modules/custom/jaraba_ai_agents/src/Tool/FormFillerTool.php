<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Tool;

use Drupal\jaraba_ai_agents\Service\BrowserAgentService;
use Psr\Log\LoggerInterface;

/**
 * Form filling tool for automated form submission via headless browser.
 *
 * Requires approval (side effects). Delegates to BrowserAgentService.
 */
class FormFillerTool extends BaseTool {

  public function __construct(
    LoggerInterface $logger,
    protected readonly BrowserAgentService $browserAgent,
  ) {
    parent::__construct($logger);
  }

  public function getId(): string {
    return 'form_filler';
  }

  public function getLabel(): string {
    return 'Form Filler';
  }

  public function getDescription(): string {
    return 'Fills and submits web forms using headless browser. Requires approval. URL must be in the vertical allowlist.';
  }

  public function getParameters(): array {
    return [
      'url' => [
        'type' => 'string',
        'required' => TRUE,
        'description' => 'URL of the page containing the form.',
      ],
      'form_data' => [
        'type' => 'array',
        'required' => TRUE,
        'description' => 'Key-value pairs: CSS selector => value to fill.',
      ],
      'submit_selector' => [
        'type' => 'string',
        'required' => FALSE,
        'description' => 'CSS selector for the submit button.',
      ],
    ];
  }

  public function requiresApproval(): bool {
    return TRUE;
  }

  public function execute(array $params, array $context = []): array {
    $url = $params['url'] ?? '';
    if (empty($url)) {
      return $this->error('URL is required.');
    }

    if (empty($params['form_data'])) {
      return $this->error('form_data is required.');
    }

    $result = $this->browserAgent->execute('form_filling', $url, $params, $context);

    if (!$result['success']) {
      return $this->error($result['error'] ?? 'Form filling failed.');
    }

    return $this->success($result['data']);
  }

}
