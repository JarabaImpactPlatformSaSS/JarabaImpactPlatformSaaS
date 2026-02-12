<?php

declare(strict_types=1);

namespace Drupal\jaraba_interactive\Service;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_interactive\Entity\InteractiveContentInterface;
use Drupal\jaraba_interactive\Entity\InteractiveResultInterface;
use Drupal\jaraba_lms\Service\XApiService;
use Psr\Log\LoggerInterface;

/**
 * Emits xAPI statements for interactive content events.
 *
 * Integrates with the LMS xAPI service to track:
 * - Content launched (attempted)
 * - Content completed
 * - Content passed/failed
 * - Answer submitted
 */
class XApiEmitter
{

    /**
     * The xAPI service from jaraba_lms.
     *
     * @var \Drupal\jaraba_lms\Service\XApiService
     */
    protected XApiService $xapiService;

    /**
     * The current user.
     *
     * @var \Drupal\Core\Session\AccountProxyInterface
     */
    protected AccountProxyInterface $currentUser;

    /**
     * The logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Constructs a new XApiEmitter.
     */
    public function __construct(
        XApiService $xapi_service,
        AccountProxyInterface $current_user,
        LoggerInterface $logger
    ) {
        $this->xapiService = $xapi_service;
        $this->currentUser = $current_user;
        $this->logger = $logger;
    }

    /**
     * Emits an "attempted" statement when content is launched.
     *
     * @param \Drupal\jaraba_interactive\Entity\InteractiveContentInterface $content
     *   The interactive content entity.
     */
    public function emitAttempted(InteractiveContentInterface $content): void
    {
        $this->emit('attempted', $content);
    }

    /**
     * Emits a "completed" statement when content is finished.
     *
     * @param \Drupal\jaraba_interactive\Entity\InteractiveContentInterface $content
     *   The interactive content entity.
     * @param \Drupal\jaraba_interactive\Entity\InteractiveResultInterface $result
     *   The result entity with score data.
     */
    public function emitCompleted(InteractiveContentInterface $content, InteractiveResultInterface $result): void
    {
        $score = $result->getScore();
        $passed = $score >= $result->getPassingScore();

        $this->emit($passed ? 'passed' : 'failed', $content, [
            'score' => [
                'scaled' => $score / 100,
                'raw' => $score,
                'min' => 0,
                'max' => 100,
            ],
            'success' => $passed,
            'completion' => TRUE,
            'duration' => $this->formatDuration($result->getDuration()),
        ]);
    }

    /**
     * Emits an "answered" statement for individual questions.
     *
     * @param \Drupal\jaraba_interactive\Entity\InteractiveContentInterface $content
     *   The interactive content entity.
     * @param array $answer_data
     *   Data about the answer: question_id, response, correct.
     */
    public function emitAnswered(InteractiveContentInterface $content, array $answer_data): void
    {
        $this->emit('answered', $content, [
            'response' => $answer_data['response'] ?? '',
            'success' => $answer_data['correct'] ?? FALSE,
            'extensions' => [
                'question_id' => $answer_data['question_id'] ?? NULL,
            ],
        ]);
    }

    /**
     * Emit an xAPI statement.
     *
     * @param string $verb
     *   The xAPI verb (attempted, completed, passed, failed, answered).
     * @param \Drupal\jaraba_interactive\Entity\InteractiveContentInterface $content
     *   The content entity.
     * @param array $result_data
     *   Optional result data for the statement.
     */
    protected function emit(string $verb, InteractiveContentInterface $content, array $result_data = []): void
    {
        try {
            $statement = [
                'actor' => [
                    'account' => [
                        'name' => $this->currentUser->getAccountName(),
                        'homePage' => \Drupal::request()->getSchemeAndHttpHost(),
                    ],
                ],
                'verb' => [
                    'id' => $this->getVerbIri($verb),
                    'display' => ['en-US' => $verb],
                ],
                'object' => [
                    'id' => $content->toUrl('canonical', ['absolute' => TRUE])->toString(),
                    'definition' => [
                        'name' => ['es' => $content->label()],
                        'type' => 'http://adlnet.gov/expapi/activities/assessment',
                    ],
                ],
                'context' => [
                    'extensions' => [
                        'content_type' => $content->getContentType(),
                        'difficulty' => $content->getDifficulty(),
                    ],
                ],
            ];

            if (!empty($result_data)) {
                $statement['result'] = $result_data;
            }

            $this->xapiService->sendStatement($statement);

            $this->logger->info('xAPI statement emitted: @verb for content @id', [
                '@verb' => $verb,
                '@id' => $content->id(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to emit xAPI statement: @message', [
                '@message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the IRI for an xAPI verb.
     *
     * @param string $verb
     *   The verb name.
     *
     * @return string
     *   The verb IRI.
     */
    protected function getVerbIri(string $verb): string
    {
        $verbs = [
            'attempted' => 'http://adlnet.gov/expapi/verbs/attempted',
            'completed' => 'http://adlnet.gov/expapi/verbs/completed',
            'passed' => 'http://adlnet.gov/expapi/verbs/passed',
            'failed' => 'http://adlnet.gov/expapi/verbs/failed',
            'answered' => 'http://adlnet.gov/expapi/verbs/answered',
        ];

        return $verbs[$verb] ?? 'http://adlnet.gov/expapi/verbs/' . $verb;
    }

    /**
     * Format duration as ISO 8601.
     *
     * @param int $seconds
     *   Duration in seconds.
     *
     * @return string
     *   ISO 8601 duration string.
     */
    protected function formatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        return sprintf('PT%dH%dM%dS', $hours, $minutes, $secs);
    }

}
