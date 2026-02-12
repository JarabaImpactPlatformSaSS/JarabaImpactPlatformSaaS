<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jaraba_content_hub\Service\WritingAssistantService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Controller for AI Writing Assistant.
 */
class WritingAssistantController extends ControllerBase implements ContainerInjectionInterface
{

    /**
     * The writing assistant service.
     */
    protected WritingAssistantService $writingAssistant;

    /**
     * Constructs a WritingAssistantController.
     */
    public function __construct(WritingAssistantService $writingAssistant)
    {
        $this->writingAssistant = $writingAssistant;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_content_hub.writing_assistant'),
        );
    }

    /**
     * Generates an article outline.
     */
    public function generateOutline(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['topic'])) {
            return new JsonResponse(['error' => 'Topic is required'], 400);
        }

        $result = $this->writingAssistant->generateOutline(
            $data['topic'],
            [
                'audience' => $data['audience'] ?? 'general',
                'length' => $data['length'] ?? 'medium',
            ]
        );

        return new JsonResponse($result, $result['success'] ? 200 : 500);
    }

    /**
     * Expands a section into content.
     */
    public function expandSection(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['heading'])) {
            return new JsonResponse(['error' => 'Heading is required'], 400);
        }

        $result = $this->writingAssistant->expandSection(
            $data['heading'],
            $data['key_points'] ?? [],
            $data['article_context'] ?? ''
        );

        return new JsonResponse($result, $result['success'] ? 200 : 500);
    }

    /**
     * Generates headline variants.
     */
    public function optimizeHeadline(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['topic']) && empty($data['current_title'])) {
            return new JsonResponse(['error' => 'Topic or current_title is required'], 400);
        }

        $result = $this->writingAssistant->optimizeHeadline(
            $data['topic'] ?? '',
            $data['current_title'] ?? ''
        );

        return new JsonResponse($result, $result['success'] ? 200 : 500);
    }

    /**
     * Improves SEO elements.
     */
    public function improveSeo(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['title']) || empty($data['body'])) {
            return new JsonResponse(['error' => 'Title and body are required'], 400);
        }

        $result = $this->writingAssistant->improveSeo($data['title'], $data['body']);

        return new JsonResponse($result, $result['success'] ? 200 : 500);
    }

    /**
     * Generates a full article.
     */
    public function generateFullArticle(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['topic'])) {
            return new JsonResponse(['error' => 'Topic is required'], 400);
        }

        $result = $this->writingAssistant->generateFullArticle(
            $data['topic'],
            [
                'audience' => $data['audience'] ?? 'general',
                'length' => $data['length'] ?? 'medium',
                'style' => $data['style'] ?? 'informative',
            ]
        );

        return new JsonResponse($result, $result['success'] ? 200 : 500);
    }

    /**
     * Creates a draft article from a topic.
     */
    public function createDraft(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['topic'])) {
            return new JsonResponse(['error' => 'Topic is required'], 400);
        }

        $result = $this->writingAssistant->createDraftFromTopic(
            $data['topic'],
            [
                'audience' => $data['audience'] ?? 'general',
                'length' => $data['length'] ?? 'medium',
                'style' => $data['style'] ?? 'informative',
            ]
        );

        if ($result['success'] && isset($result['article'])) {
            return new JsonResponse([
                'success' => TRUE,
                'article' => [
                    'id' => $result['article']->id(),
                    'uuid' => $result['article']->uuid(),
                    'title' => $result['article']->getTitle(),
                    'url' => $result['article']->toUrl('edit-form')->toString(),
                ],
                'data' => $result['data'] ?? [],
            ], 201);
        }

        return new JsonResponse($result, $result['success'] ? 200 : 500);
    }

    /**
     * Applies SEO improvements to an article.
     */
    public function applySeoToArticle(string $articleId): JsonResponse
    {
        $result = $this->writingAssistant->applySeoToArticle((int) $articleId);

        return new JsonResponse($result, $result['success'] ? 200 : 500);
    }

}
