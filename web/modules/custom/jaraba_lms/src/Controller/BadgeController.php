<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_lms\Service\OpenBadgeService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for Open Badge verification and display.
 */
class BadgeController extends ControllerBase
{

    /**
     * Badge service.
     *
     * @var \Drupal\jaraba_lms\Service\OpenBadgeService
     */
    protected OpenBadgeService $badgeService;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        $instance = parent::create($container);
        $instance->badgeService = $container->get('jaraba_lms.open_badge');
        return $instance;
    }

    /**
     * Public verification page for a badge.
     *
     * @param string $badge_uuid
     *   UUID of the badge.
     *
     * @return array
     *   Render array.
     */
    public function verify(string $badge_uuid): array
    {
        $result = $this->badgeService->verifyBadge($badge_uuid);

        if (!$result) {
            return [
                '#theme' => 'badge_verification_error',
                '#message' => $this->t('This credential could not be verified. It may have been revoked or does not exist.'),
            ];
        }

        $badge = $result['badge'];
        $assertion = $badge['assertion'] ?? [];
        $badgeClass = $badge['badge_class'] ?? [];

        return [
            '#theme' => 'badge_verification',
            '#badge' => [
                'name' => $badgeClass['name'] ?? 'Unknown',
                'description' => $badgeClass['description'] ?? '',
                'issuer' => $badgeClass['issuer']['name'] ?? 'Jaraba Impact Platform',
                'issued_date' => date('d/m/Y', $badge['issued_at'] ?? time()),
                'verified_at' => $result['verified_at'],
                'uuid' => $badge_uuid,
            ],
            '#valid' => TRUE,
            '#attached' => [
                'library' => ['ecosistema_jaraba_core/premium-components'],
            ],
            '#cache' => [
                'max-age' => 3600,
            ],
        ];
    }

    /**
     * Returns badge data as JSON-LD for external consumers.
     */
    public function getJson(string $badge_uuid): Response
    {
        $json = $this->badgeService->getShareableJson($badge_uuid);

        $response = new Response($json, 200);
        $response->headers->set('Content-Type', 'application/ld+json');
        $response->headers->set('Access-Control-Allow-Origin', '*');

        return $response;
    }

    /**
     * Lists all badges for the current user.
     */
    public function myBadges(): array
    {
        $userId = (int) $this->currentUser()->id();
        $badges = $this->badgeService->getUserBadges($userId);

        $formatted = [];
        foreach ($badges as $badge) {
            $badgeClass = $badge['badge_class'] ?? [];
            $formatted[] = [
                'uuid' => $badge['uuid'],
                'name' => $badgeClass['name'] ?? 'Unknown',
                'description' => $badgeClass['description'] ?? '',
                'issued_date' => date('d/m/Y', $badge['issued_at'] ?? time()),
                'verify_url' => $badge['verification_url'] ?? '',
                'image' => $badgeClass['image']['id'] ?? NULL,
            ];
        }

        return [
            '#theme' => 'my_badges',
            '#badges' => $formatted,
            '#attached' => [
                'library' => ['ecosistema_jaraba_core/premium-components'],
            ],
        ];
    }

}
