<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Trait;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use Psr\Http\Message\ResponseInterface;

/**
 * AUDIT-PERF-N15: Trait para retry con exponential backoff en llamadas HTTP.
 *
 * Retry solo en errores transitorios (429 rate limit, 5xx server errors,
 * timeouts). No retry en 4xx (bad request).
 */
trait RetryableHttpClientTrait {

  protected function requestWithRetry(
    string $method,
    string $url,
    array $options = [],
    int $maxRetries = 3,
    float $baseDelay = 1.0,
  ): ResponseInterface {
    $attempt = 0;
    $lastException = null;

    while ($attempt <= $maxRetries) {
      try {
        $response = $this->httpClient->request($method, $url, $options);
        $statusCode = $response->getStatusCode();

        if ($statusCode === 429 && $attempt < $maxRetries) {
          $retryAfter = (int) ($response->getHeaderLine('Retry-After') ?: (int) ($baseDelay * pow(2, $attempt)));
          usleep((int) ($retryAfter * 1_000_000));
          $attempt++;
          continue;
        }

        return $response;
      } catch (ServerException $e) {
        $lastException = $e;
        if ($attempt >= $maxRetries) {
          throw $e;
        }
      } catch (ConnectException $e) {
        $lastException = $e;
        if ($attempt >= $maxRetries) {
          throw $e;
        }
      }

      $delay = $baseDelay * pow(2, $attempt);
      usleep((int) ($delay * 1_000_000));
      $attempt++;
    }

    throw $lastException;
  }

}
