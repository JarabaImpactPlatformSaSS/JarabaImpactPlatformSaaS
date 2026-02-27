<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio transversal de votos de utilidad para resenas.
 *
 * Gestiona votos "util/no util" con deduplicacion por usuario,
 * calculo Wilson Lower Bound Score para ranking, y tracking
 * de votos en tabla custom.
 *
 * B-01: Helpfulness Voting UI.
 */
class ReviewHelpfulnessService {

  /**
   * Tabla custom para tracking de votos.
   */
  private const VOTE_TABLE = 'review_helpful_votes';

  public function __construct(
    protected readonly Connection $database,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountInterface $currentUser,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Registra un voto de utilidad.
   *
   * @param string $reviewEntityTypeId
   *   Tipo de entidad de resena.
   * @param int $reviewEntityId
   *   ID de la resena.
   * @param bool $helpful
   *   TRUE = util, FALSE = no util.
   *
   * @return array
   *   ['success' => bool, 'helpful_count' => int, 'not_helpful_count' => int,
   *    'wilson_score' => float, 'user_vote' => string|null]
   */
  public function vote(string $reviewEntityTypeId, int $reviewEntityId, bool $helpful): array {
    $uid = (int) $this->currentUser->id();
    if ($uid === 0) {
      return ['success' => FALSE, 'error' => 'authentication_required'];
    }

    // Verificar voto previo.
    $existingVote = $this->getUserVote($reviewEntityTypeId, $reviewEntityId, $uid);

    try {
      if ($existingVote !== NULL) {
        if ($existingVote === ($helpful ? 'helpful' : 'not_helpful')) {
          // Mismo voto: eliminar (toggle off).
          $this->removeVote($reviewEntityTypeId, $reviewEntityId, $uid);
        }
        else {
          // Voto diferente: cambiar.
          $this->updateVote($reviewEntityTypeId, $reviewEntityId, $uid, $helpful);
        }
      }
      else {
        // Nuevo voto.
        $this->insertVote($reviewEntityTypeId, $reviewEntityId, $uid, $helpful);
      }

      // Recalcular conteos y actualizar entidad.
      $counts = $this->recalculateCounts($reviewEntityTypeId, $reviewEntityId);
      $this->updateEntityHelpfulCount($reviewEntityTypeId, $reviewEntityId, $counts['helpful']);

      $userVote = $this->getUserVote($reviewEntityTypeId, $reviewEntityId, $uid);

      return [
        'success' => TRUE,
        'helpful_count' => $counts['helpful'],
        'not_helpful_count' => $counts['not_helpful'],
        'wilson_score' => $counts['wilson_score'],
        'user_vote' => $userVote,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error voting on @type @id: @msg', [
        '@type' => $reviewEntityTypeId,
        '@id' => $reviewEntityId,
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'error' => 'internal_error'];
    }
  }

  /**
   * Obtiene el voto del usuario actual para una resena.
   */
  public function getUserVote(string $reviewEntityTypeId, int $reviewEntityId, int $uid): ?string {
    if (!$this->database->schema()->tableExists(self::VOTE_TABLE)) {
      return NULL;
    }

    $vote = $this->database->select(self::VOTE_TABLE, 'v')
      ->fields('v', ['vote_type'])
      ->condition('review_entity_type', $reviewEntityTypeId)
      ->condition('review_entity_id', $reviewEntityId)
      ->condition('uid', $uid)
      ->execute()
      ->fetchField();

    return $vote !== FALSE ? (string) $vote : NULL;
  }

  /**
   * Obtiene conteos de utilidad para una resena.
   */
  public function getCounts(string $reviewEntityTypeId, int $reviewEntityId): array {
    $counts = ['helpful' => 0, 'not_helpful' => 0, 'wilson_score' => 0.0];

    if (!$this->database->schema()->tableExists(self::VOTE_TABLE)) {
      return $counts;
    }

    $result = $this->database->query(
      "SELECT vote_type, COUNT(*) as cnt FROM {" . self::VOTE_TABLE . "} WHERE review_entity_type = :type AND review_entity_id = :id GROUP BY vote_type",
      [':type' => $reviewEntityTypeId, ':id' => $reviewEntityId]
    );

    foreach ($result as $row) {
      if ($row->vote_type === 'helpful') {
        $counts['helpful'] = (int) $row->cnt;
      }
      elseif ($row->vote_type === 'not_helpful') {
        $counts['not_helpful'] = (int) $row->cnt;
      }
    }

    $counts['wilson_score'] = $this->calculateWilsonScore($counts['helpful'], $counts['not_helpful']);

    return $counts;
  }

  /**
   * Calcula Wilson Lower Bound Score.
   *
   * Algoritmo usado por Amazon/Reddit para ranking de utilidad.
   * z = 1.96 para 95% confidence interval.
   *
   * @param int $positive
   *   Votos positivos.
   * @param int $negative
   *   Votos negativos.
   *
   * @return float
   *   Score entre 0 y 1.
   */
  public function calculateWilsonScore(int $positive, int $negative): float {
    $n = $positive + $negative;
    if ($n === 0) {
      return 0.0;
    }

    $z = 1.96;
    $phat = $positive / $n;
    $denominator = 1 + ($z * $z / $n);
    $center = $phat + ($z * $z) / (2 * $n);
    $spread = $z * sqrt(($phat * (1 - $phat) + ($z * $z) / (4 * $n)) / $n);

    return round(($center - $spread) / $denominator, 4);
  }

  /**
   * Crea la tabla de votos si no existe.
   */
  public function ensureTable(): void {
    if ($this->database->schema()->tableExists(self::VOTE_TABLE)) {
      return;
    }

    $this->database->schema()->createTable(self::VOTE_TABLE, [
      'description' => 'Tracking de votos de utilidad de resenas.',
      'fields' => [
        'id' => [
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ],
        'review_entity_type' => [
          'type' => 'varchar',
          'length' => 64,
          'not null' => TRUE,
        ],
        'review_entity_id' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ],
        'uid' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ],
        'vote_type' => [
          'type' => 'varchar',
          'length' => 16,
          'not null' => TRUE,
          'description' => 'helpful or not_helpful',
        ],
        'created' => [
          'type' => 'int',
          'not null' => TRUE,
        ],
      ],
      'primary key' => ['id'],
      'unique keys' => [
        'user_review' => ['review_entity_type', 'review_entity_id', 'uid'],
      ],
      'indexes' => [
        'review_lookup' => ['review_entity_type', 'review_entity_id'],
        'user_votes' => ['uid'],
      ],
    ]);
  }

  /**
   * Inserta un voto nuevo.
   */
  protected function insertVote(string $type, int $id, int $uid, bool $helpful): void {
    $this->ensureTable();
    $this->database->insert(self::VOTE_TABLE)
      ->fields([
        'review_entity_type' => $type,
        'review_entity_id' => $id,
        'uid' => $uid,
        'vote_type' => $helpful ? 'helpful' : 'not_helpful',
        'created' => \Drupal::time()->getRequestTime(),
      ])
      ->execute();
  }

  /**
   * Actualiza un voto existente.
   */
  protected function updateVote(string $type, int $id, int $uid, bool $helpful): void {
    $this->database->update(self::VOTE_TABLE)
      ->fields(['vote_type' => $helpful ? 'helpful' : 'not_helpful'])
      ->condition('review_entity_type', $type)
      ->condition('review_entity_id', $id)
      ->condition('uid', $uid)
      ->execute();
  }

  /**
   * Elimina un voto (toggle off).
   */
  protected function removeVote(string $type, int $id, int $uid): void {
    $this->database->delete(self::VOTE_TABLE)
      ->condition('review_entity_type', $type)
      ->condition('review_entity_id', $id)
      ->condition('uid', $uid)
      ->execute();
  }

  /**
   * Recalcula conteos desde la tabla de votos.
   */
  protected function recalculateCounts(string $type, int $id): array {
    return $this->getCounts($type, $id);
  }

  /**
   * Actualiza campos helpful_count, not_helpful_count y wilson_score en la entidad.
   */
  protected function updateEntityHelpfulCount(string $type, int $id, int $count): void {
    try {
      $storage = $this->entityTypeManager->getStorage($type);
      $entity = $storage->load($id);
      if ($entity === NULL) {
        return;
      }

      // Obtener conteos completos.
      $counts = $this->getCounts($type, $id);

      if ($entity->hasField('helpful_count')) {
        $entity->set('helpful_count', $counts['helpful']);
      }
      if ($entity->hasField('not_helpful_count')) {
        $entity->set('not_helpful_count', $counts['not_helpful']);
      }
      if ($entity->hasField('wilson_score')) {
        $entity->set('wilson_score', $counts['wilson_score']);
      }

      $entity->save();
    }
    catch (\Exception $e) {
      $this->logger->warning('Could not update helpfulness fields for @type @id: @msg', [
        '@type' => $type,
        '@id' => $id,
        '@msg' => $e->getMessage(),
      ]);
    }
  }

}
