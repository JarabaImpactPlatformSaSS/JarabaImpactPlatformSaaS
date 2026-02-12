<?php

namespace Drupal\jaraba_interactive\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_interactive\Entity\InteractiveContent;
use Drupal\jaraba_interactive\Service\ContentGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * API Controller for Interactive Content.
 *
 * Provides REST endpoints for:
 * - GET content data by ID
 * - POST xAPI statements
 * - POST AI-powered content generation
 */
class ApiController extends ControllerBase
{

    /**
     * The content generator service.
     *
     * @var \Drupal\jaraba_interactive\Service\ContentGenerator
     */
    protected ContentGenerator $contentGenerator;

    /**
     * Constructs an ApiController.
     */
    public function __construct(ContentGenerator $content_generator)
    {
        $this->contentGenerator = $content_generator;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_interactive.content_generator')
        );
    }

    /**
     * Gets content data for a specific interactive content entity.
     *
     * @param \Drupal\jaraba_interactive\Entity\InteractiveContentInterface $interactive_content
     *   The interactive content entity.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON response with content data.
     */
    public function getContent(InteractiveContent $interactive_content): JsonResponse
    {
        $data = [
            'id' => $interactive_content->id(),
            'uuid' => $interactive_content->uuid(),
            'label' => $interactive_content->label(),
            'type' => $interactive_content->get('content_type')->value ?? 'general',
            'status' => $interactive_content->isPublished(),
            'created' => $interactive_content->getCreatedTime(),
            'changed' => $interactive_content->getChangedTime(),
        ];

        // Include content_data if available.
        if ($interactive_content->hasField('content_data') && !$interactive_content->get('content_data')->isEmpty()) {
            $data['content'] = json_decode($interactive_content->get('content_data')->value, TRUE);
        }

        return new JsonResponse($data);
    }

    /**
     * Receives xAPI statements for tracking user interactions.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The incoming request.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON response confirming receipt.
     */
    public function receiveXapi(Request $request): JsonResponse
    {
        $content = $request->getContent();
        $data = json_decode($content, TRUE);

        if (empty($data)) {
            throw new BadRequestHttpException('Invalid xAPI statement data');
        }

        // Log the xAPI statement.
        \Drupal::logger('jaraba_interactive')->info('xAPI statement received: @verb for content @id', [
            '@verb' => $data['verb'] ?? 'unknown',
            '@id' => $data['object']['id'] ?? 'unknown',
        ]);

        // TODO: Store xAPI statement in InteractiveResult entity.
        // TODO: Process learning analytics.

        return new JsonResponse([
            'status' => 'received',
            'timestamp' => \Drupal::time()->getRequestTime(),
        ]);
    }

    /**
     * Generates interactive content using AI.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The incoming request with generation parameters.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON response with generated content.
     */
    public function generate(Request $request): JsonResponse
    {
        $content = $request->getContent();
        $params = json_decode($content, TRUE);

        // Validate required fields.
        if (empty($params['source_text']) && empty($params['prompt'])) {
            throw new BadRequestHttpException('Missing source_text or prompt parameter');
        }

        $type = $params['type'] ?? 'quiz';
        $source = $params['source_text'] ?? $params['prompt'];
        $difficulty = $params['difficulty'] ?? 'intermediate';
        $count = (int) ($params['count'] ?? 5);

        $result = [];
        $content_data = [];

        try {
            switch ($type) {
                case 'quiz':
                case 'question_set':
                    $content_data = $this->contentGenerator->generateQuiz(
                        $source,
                        $difficulty,
                        $count,
                        $params['question_type'] ?? 'multiple_choice'
                    );
                    break;

                case 'scenario':
                    $content_data = $this->contentGenerator->generateScenario(
                        $source,
                        $params['learning_objective'] ?? '',
                        (int) ($params['depth'] ?? 3)
                    );
                    break;

                case 'flashcards':
                    $content_data = $this->contentGenerator->generateFlashcards(
                        $source,
                        $count
                    );
                    break;

                default:
                    throw new BadRequestHttpException('Unknown content type: ' . $type);
            }

            // Check if generation was successful.
            if (empty($content_data)) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'AI generation failed. Please try again.',
                ], 500);
            }

            // Optionally create the entity if requested.
            $entity_id = NULL;
            if (!empty($params['create_entity']) && $params['create_entity']) {
                $entity = $this->createInteractiveContent(
                    $params['title'] ?? 'Contenido generado con IA',
                    $type,
                    $content_data,
                    $difficulty
                );
                $entity_id = $entity->id();
            }

            $result = [
                'status' => 'success',
                'type' => $type,
                'content_data' => $content_data,
                'entity_id' => $entity_id,
            ];

        } catch (\Exception $e) {
            \Drupal::logger('jaraba_interactive')->error('AI generation error: @message', [
                '@message' => $e->getMessage(),
            ]);
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }

        return new JsonResponse($result);
    }

    /**
     * Creates an InteractiveContent entity from generated data.
     *
     * @param string $title
     *   The title.
     * @param string $type
     *   Content type (quiz, scenario, flashcards).
     * @param array $content_data
     *   The generated content data.
     * @param string $difficulty
     *   Difficulty level.
     *
     * @return \Drupal\jaraba_interactive\Entity\InteractiveContentInterface
     *   The created entity.
     */
    protected function createInteractiveContent(
        string $title,
        string $type,
        array $content_data,
        string $difficulty
    ): InteractiveContent {
        $storage = $this->entityTypeManager->getStorage('interactive_content');

        $entity = $storage->create([
            'title' => $title,
            'content_type' => $type === 'quiz' ? 'question_set' : $type,
            'content_data' => json_encode($content_data),
            'difficulty' => $difficulty,
            'status' => 0, // Draft by default.
        ]);

        $entity->save();

        \Drupal::logger('jaraba_interactive')->info('Created AI-generated content: @title (ID: @id)', [
            '@title' => $title,
            '@id' => $entity->id(),
        ]);

        return $entity;
    }

    // =========================================================================
    // SMART IMPORT ENDPOINTS (Sprint 5)
    // =========================================================================

    /**
     * Imports content from a URL and generates interactive content.
     *
     * Endpoint: POST /api/v1/interactive/import-url
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The incoming request with URL and generation options.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON response with extracted content and generated interactive data.
     */
    public function importFromUrl(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['url'])) {
            throw new BadRequestHttpException('URL is required');
        }

        $url = $data['url'];
        $content_type = $data['content_type'] ?? 'quiz';
        $create_entity = $data['create_entity'] ?? FALSE;
        $options = [
            'difficulty' => $data['difficulty'] ?? 'intermediate',
            'count' => $data['count'] ?? 5,
            'learning_objective' => $data['learning_objective'] ?? '',
            'slide_count' => $data['slide_count'] ?? 5,
        ];

        try {
            $result = $this->contentGenerator->importFromUrl($url, $content_type, $options);

            if (!$result['success']) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => $result['error'] ?? 'Import failed',
                ], 400);
            }

            // Create entity if requested
            if ($create_entity && !empty($result['content_data'])) {
                $title = $data['title'] ?? $result['source']['title'] ?? 'Imported from URL';
                $entity = $this->createInteractiveContent(
                    $title,
                    $content_type,
                    $result['content_data'],
                    $options['difficulty']
                );
                $result['entity_id'] = $entity->id();
            }

            return new JsonResponse($result);

        } catch (\Exception $e) {
            \Drupal::logger('jaraba_interactive')->error('URL import API error: @message', [
                '@message' => $e->getMessage(),
            ]);
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Imports content from a video URL and generates interactive content.
     *
     * Endpoint: POST /api/v1/interactive/import-video
     *
     * Uses YouTube captions or OpenAI Whisper for transcription,
     * then generates quizzes, checkpoints, or other content types.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The incoming request with video URL and generation options.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON response with transcript and generated interactive data.
     */
    public function importFromVideo(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['video_url'])) {
            throw new BadRequestHttpException('video_url is required');
        }

        $video_url = $data['video_url'];
        $content_type = $data['content_type'] ?? 'quiz';
        $create_entity = $data['create_entity'] ?? FALSE;
        $options = [
            'difficulty' => $data['difficulty'] ?? 'intermediate',
            'count' => $data['count'] ?? 5,
            'include_timestamps' => $data['include_timestamps'] ?? TRUE,
            'checkpoint_count' => $data['checkpoint_count'] ?? 3,
        ];

        try {
            $result = $this->contentGenerator->importFromVideo($video_url, $content_type, $options);

            if (!$result['success']) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => $result['error'] ?? 'Video import failed',
                ], 400);
            }

            // Create entity if requested
            if ($create_entity && !empty($result['content_data'])) {
                $title = $data['title'] ?? 'Imported from Video';
                $entity = $this->createInteractiveContent(
                    $title,
                    $content_type,
                    $result['content_data'],
                    $options['difficulty']
                );
                $result['entity_id'] = $entity->id();
            }

            return new JsonResponse($result);

        } catch (\Exception $e) {
            \Drupal::logger('jaraba_interactive')->error('Video import API error: @message', [
                '@message' => $e->getMessage(),
            ]);
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

}
