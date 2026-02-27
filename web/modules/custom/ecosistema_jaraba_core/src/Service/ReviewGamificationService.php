<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * B-13: Review gamification system.
 *
 * Awards points, badges, and tiers to users based on their
 * review activity. Encourages quality reviews through incentives.
 */
class ReviewGamificationService {

  /**
   * Points per action.
   */
  private const POINTS = [
    'review_submitted' => 10,
    'review_approved' => 5,
    'review_with_photo' => 3,
    'review_helpful_vote' => 2,
    'review_long_text' => 5,
    'first_review' => 20,
  ];

  /**
   * Tier thresholds.
   */
  private const TIERS = [
    'bronze' => 0,
    'silver' => 50,
    'gold' => 150,
    'platinum' => 500,
    'diamond' => 1000,
  ];

  /**
   * Badge definitions.
   */
  private const BADGES = [
    'first_review' => ['label' => 'First Review', 'icon' => 'star', 'condition' => 'reviews >= 1'],
    'prolific_reviewer' => ['label' => 'Prolific Reviewer', 'icon' => 'pencil', 'condition' => 'reviews >= 10'],
    'top_reviewer' => ['label' => 'Top Reviewer', 'icon' => 'trophy', 'condition' => 'reviews >= 50'],
    'helpful_reviewer' => ['label' => 'Helpful', 'icon' => 'thumbs-up', 'condition' => 'helpful_votes >= 20'],
    'photographer' => ['label' => 'Photographer', 'icon' => 'camera', 'condition' => 'photo_reviews >= 5'],
    'verified_buyer' => ['label' => 'Verified Buyer', 'icon' => 'check-circle', 'condition' => 'verified_purchases >= 3'],
  ];

  public function __construct(
    protected readonly Connection $database,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Award points to a user.
   *
   * @param int $uid
   *   User ID.
   * @param string $action
   *   Action key (e.g., 'review_submitted').
   * @param array $context
   *   Additional context (review_id, entity_type, etc.).
   *
   * @return int
   *   Total points after awarding.
   */
  public function awardPoints(int $uid, string $action, array $context = []): int {
    $points = self::POINTS[$action] ?? 0;
    if ($points === 0) {
      return $this->getUserPoints($uid);
    }

    $this->ensureTable();

    try {
      $this->database->insert('review_gamification_points')
        ->fields([
          'uid' => $uid,
          'action' => $action,
          'points' => $points,
          'context_data' => json_encode($context),
          'created' => time(),
        ])
        ->execute();
    }
    catch (\Exception $e) {
      $this->logger->warning('Gamification award failed: @msg', ['@msg' => $e->getMessage()]);
    }

    $total = $this->getUserPoints($uid);

    // Check for new badges.
    $this->checkBadges($uid);

    return $total;
  }

  /**
   * Get total points for a user.
   */
  public function getUserPoints(int $uid): int {
    $this->ensureTable();

    try {
      return (int) $this->database->select('review_gamification_points', 'p')
        ->condition('uid', $uid)
        ->addExpression('SUM(points)', 'total')
        ->execute()
        ->fetchField();
    }
    catch (\Exception) {
      return 0;
    }
  }

  /**
   * Get user tier based on points.
   */
  public function getUserTier(int $uid): string {
    $points = $this->getUserPoints($uid);

    $tier = 'bronze';
    foreach (self::TIERS as $name => $threshold) {
      if ($points >= $threshold) {
        $tier = $name;
      }
    }

    return $tier;
  }

  /**
   * Get user badges.
   *
   * @return array
   *   Array of badge keys.
   */
  public function getUserBadges(int $uid): array {
    $this->ensureTable();

    try {
      $result = $this->database->select('review_gamification_badges', 'b')
        ->fields('b', ['badge'])
        ->condition('uid', $uid)
        ->execute();
      return $result ? ($result->fetchCol() ?: []) : [];
    }
    catch (\Exception) {
      return [];
    }
  }

  /**
   * Get user stats summary.
   */
  public function getUserStats(int $uid): array {
    return [
      'points' => $this->getUserPoints($uid),
      'tier' => $this->getUserTier($uid),
      'badges' => $this->getUserBadges($uid),
      'tier_thresholds' => self::TIERS,
    ];
  }

  /**
   * Get leaderboard (top reviewers).
   *
   * @param int $limit
   *   Number of entries.
   *
   * @return array
   *   Array of ['uid' => int, 'points' => int, 'tier' => string].
   */
  public function getLeaderboard(int $limit = 10): array {
    $this->ensureTable();

    try {
      $query = $this->database->select('review_gamification_points', 'p');
      $query->addField('p', 'uid');
      $query->addExpression('SUM(points)', 'total_points');
      $query->groupBy('p.uid');
      $query->orderBy('total_points', 'DESC');
      $query->range(0, $limit);

      $results = $query->execute()->fetchAll();
      $leaderboard = [];

      foreach ($results as $row) {
        $leaderboard[] = [
          'uid' => (int) $row->uid,
          'points' => (int) $row->total_points,
          'tier' => $this->getUserTier((int) $row->uid),
        ];
      }

      return $leaderboard;
    }
    catch (\Exception) {
      return [];
    }
  }

  /**
   * Check and award badges.
   */
  protected function checkBadges(int $uid): void {
    $this->ensureTable();
    $existing = $this->getUserBadges($uid);
    $stats = $this->getReviewStatsForUser($uid);

    foreach (self::BADGES as $badge => $def) {
      if (in_array($badge, $existing, TRUE)) {
        continue;
      }

      if ($this->evaluateBadgeCondition($def['condition'], $stats)) {
        try {
          $this->database->insert('review_gamification_badges')
            ->fields([
              'uid' => $uid,
              'badge' => $badge,
              'created' => time(),
            ])
            ->execute();
        }
        catch (\Exception) {
          // Duplicate or error — skip.
        }
      }
    }
  }

  /**
   * Get review-related stats for a user.
   */
  protected function getReviewStatsForUser(int $uid): array {
    $reviewTypes = ['comercio_review', 'review_agro', 'review_servicios', 'session_review', 'course_review'];
    $totalReviews = 0;

    foreach ($reviewTypes as $type) {
      try {
        $count = $this->entityTypeManager->getStorage($type)
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('uid', $uid)
          ->count()
          ->execute();
        $totalReviews += (int) $count;
      }
      catch (\Exception) {
        // Entity type may not exist.
      }
    }

    // Helpful votes received on user's reviews.
    $helpfulVotes = 0;
    try {
      if ($this->database->schema()->tableExists('review_helpful_votes')) {
        $helpfulVotes = (int) $this->database->select('review_helpful_votes', 'v')
          ->condition('vote_type', 'helpful')
          ->addExpression('COUNT(*)', 'cnt')
          ->execute()
          ->fetchField();
      }
    }
    catch (\Exception) {}

    // Photo reviews and verified purchases.
    $photoReviews = 0;
    $verifiedPurchases = 0;
    foreach ($reviewTypes as $type) {
      try {
        $storage = $this->entityTypeManager->getStorage($type);
        $sampleEntity = $storage->create([]);

        // Count photo reviews.
        if ($sampleEntity->hasField('photos')) {
          $count = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('uid', $uid)
            ->condition('photos', '', '<>')
            ->count()
            ->execute();
          $photoReviews += (int) $count;
        }

        // Count verified purchases.
        if ($sampleEntity->hasField('verified_purchase')) {
          $count = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('uid', $uid)
            ->condition('verified_purchase', 1)
            ->count()
            ->execute();
          $verifiedPurchases += (int) $count;
        }
      }
      catch (\Exception) {
        // Entity type or field may not exist.
      }
    }

    return [
      'reviews' => $totalReviews,
      'helpful_votes' => $helpfulVotes,
      'photo_reviews' => $photoReviews,
      'verified_purchases' => $verifiedPurchases,
    ];
  }

  /**
   * Evaluate badge condition.
   */
  protected function evaluateBadgeCondition(string $condition, array $stats): bool {
    if (preg_match('/(\w+)\s*>=\s*(\d+)/', $condition, $m)) {
      return ($stats[$m[1]] ?? 0) >= (int) $m[2];
    }
    return FALSE;
  }

  /**
   * Ensure gamification tables exist.
   */
  protected function ensureTable(): void {
    $schema = $this->database->schema();

    if (!$schema->tableExists('review_gamification_points')) {
      $schema->createTable('review_gamification_points', [
        'fields' => [
          'id' => ['type' => 'serial', 'unsigned' => TRUE, 'not null' => TRUE],
          'uid' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE, 'default' => 0],
          'action' => ['type' => 'varchar', 'length' => 64, 'not null' => TRUE, 'default' => ''],
          'points' => ['type' => 'int', 'not null' => TRUE, 'default' => 0],
          'context_data' => ['type' => 'text', 'size' => 'normal'],
          'created' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE, 'default' => 0],
        ],
        'primary key' => ['id'],
        'indexes' => [
          'uid' => ['uid'],
          'action' => ['action'],
        ],
      ]);
    }

    if (!$schema->tableExists('review_gamification_badges')) {
      $schema->createTable('review_gamification_badges', [
        'fields' => [
          'id' => ['type' => 'serial', 'unsigned' => TRUE, 'not null' => TRUE],
          'uid' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE, 'default' => 0],
          'badge' => ['type' => 'varchar', 'length' => 64, 'not null' => TRUE, 'default' => ''],
          'created' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE, 'default' => 0],
        ],
        'primary key' => ['id'],
        'unique keys' => [
          'uid_badge' => ['uid', 'badge'],
        ],
      ]);
    }
  }

}
