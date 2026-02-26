<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Provider Fallback Service with circuit breaker (FIX-031).
 *
 * If the primary IA provider fails (rate limit, outage), retries with
 * a fallback chain per tier. Circuit breaker: 3 failures in 5 minutes
 * = skip provider.
 */
class ProviderFallbackService
{

    /**
     * Circuit breaker state: provider_id => [failures => int, last_failure => float].
     *
     * @var array<string, array{failures: int, last_failure: float}>
     */
    protected array $circuitState = [];

    /**
     * Circuit breaker threshold: failures before skipping provider.
     */
    protected const CIRCUIT_THRESHOLD = 3;

    /**
     * Circuit breaker window in seconds.
     */
    protected const CIRCUIT_WINDOW = 300;

    /**
     * Constructor.
     */
    public function __construct(
        protected ConfigFactoryInterface $configFactory,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Executes an LLM call with fallback chain.
     *
     * @param string $tier
     *   The tier (fast, balanced, premium).
     * @param callable $executor
     *   Callable that takes (providerId, modelId) and returns text.
     * @param string $primaryProviderId
     *   Primary provider ID.
     * @param string $primaryModelId
     *   Primary model ID.
     *
     * @return string
     *   The response text.
     *
     * @throws \Exception
     *   If all providers in the chain fail.
     */
    public function executeWithFallback(
        string $tier,
        callable $executor,
        string $primaryProviderId,
        string $primaryModelId,
    ): string {
        $chain = $this->getFallbackChain($tier, $primaryProviderId, $primaryModelId);
        $lastException = NULL;

        foreach ($chain as $provider) {
            $pid = $provider['provider_id'];
            $mid = $provider['model_id'];

            // Check circuit breaker.
            if ($this->isCircuitOpen($pid)) {
                $this->logger->info('Circuit breaker open for provider @pid, skipping.', ['@pid' => $pid]);
                continue;
            }

            try {
                $result = $executor($pid, $mid);
                $this->recordSuccess($pid);
                return $result;
            } catch (\Exception $e) {
                $this->recordFailure($pid);
                $lastException = $e;
                $this->logger->warning('Provider @pid failed: @msg. Trying next in chain.', [
                    '@pid' => $pid,
                    '@msg' => $e->getMessage(),
                ]);
            }
        }

        throw $lastException ?? new \RuntimeException('All providers in fallback chain failed.');
    }

    /**
     * Gets the fallback chain for a tier.
     *
     * @param string $tier
     *   The tier.
     * @param string $primaryProviderId
     *   Primary provider ID.
     * @param string $primaryModelId
     *   Primary model ID.
     *
     * @return array
     *   Ordered list of [{provider_id, model_id}].
     */
    protected function getFallbackChain(string $tier, string $primaryProviderId, string $primaryModelId): array
    {
        // Primary always first.
        $chain = [
            ['provider_id' => $primaryProviderId, 'model_id' => $primaryModelId],
        ];

        // Load config fallbacks.
        $config = $this->configFactory->get('jaraba_ai_agents.provider_fallback');
        $tierChain = $config->get("chains.{$tier}") ?? [];

        foreach ($tierChain as $fallback) {
            if (($fallback['provider_id'] ?? '') !== $primaryProviderId
                || ($fallback['model_id'] ?? '') !== $primaryModelId) {
                $chain[] = $fallback;
            }
        }

        return $chain;
    }

    /**
     * Checks if the circuit breaker is open for a provider.
     */
    protected function isCircuitOpen(string $providerId): bool
    {
        if (!isset($this->circuitState[$providerId])) {
            return FALSE;
        }

        $state = $this->circuitState[$providerId];

        // Reset if outside window.
        if ((microtime(TRUE) - $state['last_failure']) > self::CIRCUIT_WINDOW) {
            unset($this->circuitState[$providerId]);
            return FALSE;
        }

        return $state['failures'] >= self::CIRCUIT_THRESHOLD;
    }

    /**
     * Records a failure for the circuit breaker.
     */
    protected function recordFailure(string $providerId): void
    {
        if (!isset($this->circuitState[$providerId])) {
            $this->circuitState[$providerId] = ['failures' => 0, 'last_failure' => 0.0];
        }

        $this->circuitState[$providerId]['failures']++;
        $this->circuitState[$providerId]['last_failure'] = microtime(TRUE);
    }

    /**
     * Records a success — resets the circuit breaker.
     */
    protected function recordSuccess(string $providerId): void
    {
        unset($this->circuitState[$providerId]);
    }

}
