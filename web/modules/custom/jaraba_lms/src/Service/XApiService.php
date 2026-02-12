<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * xAPI (Experience API) integration service.
 *
 * Envía y consulta statements xAPI a un Learning Record Store (LRS)
 * para tracking granular de actividades de aprendizaje.
 */
class XApiService {

  /**
   * xAPI specification version.
   */
  private const XAPI_VERSION = '1.0.3';

  public function __construct(
    protected ClientInterface $httpClient,
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Sends an xAPI statement to the configured LRS.
   *
   * @param array $statement
   *   Complete xAPI statement array.
   *
   * @return bool
   *   TRUE if statement was accepted by the LRS.
   */
  public function sendStatement(array $statement): bool {
    $config = $this->configFactory->get('jaraba_lms.xapi');
    $endpoint = $config->get('lrs_endpoint');
    $key = $config->get('lrs_key');
    $secret = $config->get('lrs_secret');

    if (empty($endpoint) || empty($key)) {
      return FALSE;
    }

    // Ensure required fields.
    $statement['timestamp'] = $statement['timestamp'] ?? date('c');
    $statement['version'] = self::XAPI_VERSION;

    try {
      $response = $this->httpClient->request('POST', rtrim($endpoint, '/') . '/statements', [
        'headers' => [
          'Content-Type' => 'application/json',
          'X-Experience-API-Version' => self::XAPI_VERSION,
          'Authorization' => 'Basic ' . base64_encode($key . ':' . ($secret ?? '')),
        ],
        'json' => [$statement],
        'timeout' => 10,
      ]);

      return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
    }
    catch (\Exception) {
      return FALSE;
    }
  }

  /**
   * Builds an xAPI statement from components.
   *
   * @param string $verb
   *   Verb ID (e.g., 'completed', 'attempted', 'passed').
   * @param array $actor
   *   Actor data with 'name' and 'mbox' keys.
   * @param array $object
   *   Activity object with 'id', 'name', 'description' keys.
   * @param array|null $result
   *   Optional result data with 'score', 'success', 'completion', 'duration'.
   *
   * @return array
   *   Complete xAPI statement.
   */
  public function buildStatement(string $verb, array $actor, array $object, ?array $result = NULL): array {
    $verbMap = [
      'completed' => ['id' => 'http://adlnet.gov/expapi/verbs/completed', 'display' => 'completed'],
      'attempted' => ['id' => 'http://adlnet.gov/expapi/verbs/attempted', 'display' => 'attempted'],
      'passed' => ['id' => 'http://adlnet.gov/expapi/verbs/passed', 'display' => 'passed'],
      'failed' => ['id' => 'http://adlnet.gov/expapi/verbs/failed', 'display' => 'failed'],
      'experienced' => ['id' => 'http://adlnet.gov/expapi/verbs/experienced', 'display' => 'experienced'],
      'launched' => ['id' => 'http://adlnet.gov/expapi/verbs/launched', 'display' => 'launched'],
      'answered' => ['id' => 'http://adlnet.gov/expapi/verbs/answered', 'display' => 'answered'],
    ];

    $verbData = $verbMap[$verb] ?? ['id' => "http://adlnet.gov/expapi/verbs/{$verb}", 'display' => $verb];

    $statement = [
      'actor' => [
        'objectType' => 'Agent',
        'name' => $actor['name'] ?? 'Unknown',
        'mbox' => $actor['mbox'] ?? 'mailto:unknown@example.com',
      ],
      'verb' => [
        'id' => $verbData['id'],
        'display' => ['en-US' => $verbData['display']],
      ],
      'object' => [
        'objectType' => 'Activity',
        'id' => $object['id'] ?? '',
        'definition' => [
          'name' => ['es' => $object['name'] ?? ''],
          'description' => ['es' => $object['description'] ?? ''],
          'type' => $object['type'] ?? 'http://adlnet.gov/expapi/activities/course',
        ],
      ],
      'timestamp' => date('c'),
      'version' => self::XAPI_VERSION,
    ];

    if ($result !== NULL) {
      $statement['result'] = [];

      if (isset($result['score'])) {
        $statement['result']['score'] = [
          'scaled' => min(1.0, max(0.0, (float) $result['score'] / 100)),
          'raw' => (float) $result['score'],
          'min' => 0,
          'max' => 100,
        ];
      }

      if (isset($result['success'])) {
        $statement['result']['success'] = (bool) $result['success'];
      }

      if (isset($result['completion'])) {
        $statement['result']['completion'] = (bool) $result['completion'];
      }

      if (isset($result['duration'])) {
        $statement['result']['duration'] = $this->formatIsoDuration((int) $result['duration']);
      }
    }

    return $statement;
  }

  /**
   * Queries statements from the LRS.
   *
   * @param array $filters
   *   Filters: 'agent', 'verb', 'activity', 'since', 'until', 'limit'.
   *
   * @return array
   *   List of matching statements.
   */
  public function getStatements(array $filters = []): array {
    $config = $this->configFactory->get('jaraba_lms.xapi');
    $endpoint = $config->get('lrs_endpoint');
    $key = $config->get('lrs_key');
    $secret = $config->get('lrs_secret');

    if (empty($endpoint) || empty($key)) {
      return [];
    }

    $query = [];
    if (!empty($filters['agent'])) {
      $query['agent'] = json_encode(['mbox' => $filters['agent']]);
    }
    if (!empty($filters['verb'])) {
      $query['verb'] = $filters['verb'];
    }
    if (!empty($filters['activity'])) {
      $query['activity'] = $filters['activity'];
    }
    if (!empty($filters['since'])) {
      $query['since'] = $filters['since'];
    }
    if (!empty($filters['until'])) {
      $query['until'] = $filters['until'];
    }
    $query['limit'] = $filters['limit'] ?? 50;

    try {
      $response = $this->httpClient->request('GET', rtrim($endpoint, '/') . '/statements', [
        'headers' => [
          'X-Experience-API-Version' => self::XAPI_VERSION,
          'Authorization' => 'Basic ' . base64_encode($key . ':' . ($secret ?? '')),
        ],
        'query' => $query,
        'timeout' => 10,
      ]);

      $body = json_decode((string) $response->getBody(), TRUE);
      return $body['statements'] ?? [];
    }
    catch (\Exception) {
      return [];
    }
  }

  /**
   * Gets activity state from the LRS.
   *
   * @param string $activityId
   *   The activity IRI.
   * @param string $agentId
   *   The agent mbox.
   *
   * @return array|null
   *   State data or NULL.
   */
  public function getActivityState(string $activityId, string $agentId): ?array {
    $config = $this->configFactory->get('jaraba_lms.xapi');
    $endpoint = $config->get('lrs_endpoint');
    $key = $config->get('lrs_key');
    $secret = $config->get('lrs_secret');

    if (empty($endpoint) || empty($key)) {
      return NULL;
    }

    try {
      $response = $this->httpClient->request('GET', rtrim($endpoint, '/') . '/activities/state', [
        'headers' => [
          'X-Experience-API-Version' => self::XAPI_VERSION,
          'Authorization' => 'Basic ' . base64_encode($key . ':' . ($secret ?? '')),
        ],
        'query' => [
          'activityId' => $activityId,
          'agent' => json_encode(['mbox' => $agentId]),
          'stateId' => 'current',
        ],
        'timeout' => 10,
      ]);

      return json_decode((string) $response->getBody(), TRUE);
    }
    catch (\Exception) {
      return NULL;
    }
  }

  /**
   * Formats seconds as ISO 8601 duration.
   */
  protected function formatIsoDuration(int $seconds): string {
    $hours = intdiv($seconds, 3600);
    $minutes = intdiv($seconds % 3600, 60);
    $secs = $seconds % 60;

    $parts = ['PT'];
    if ($hours > 0) {
      $parts[] = $hours . 'H';
    }
    if ($minutes > 0) {
      $parts[] = $minutes . 'M';
    }
    $parts[] = $secs . 'S';

    return implode('', $parts);
  }

}
