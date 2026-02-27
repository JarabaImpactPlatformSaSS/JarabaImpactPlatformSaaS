<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Proporciona campos compartidos para entidades de review.
 *
 * Define 5 campos comunes al 90% de las entidades de review del ecosistema
 * (review_status, helpful_count, photos, ai_summary, ai_summary_generated_at).
 * Cada entidad vertical usa este trait en su baseFieldDefinitions() para evitar
 * duplicacion. Los campos como rating, title, body, tenant_id, uid, created y
 * changed son responsabilidad de cada entidad.
 *
 * Uso:
 *   use ReviewableEntityTrait;
 *   public static function baseFieldDefinitions(...) {
 *     $fields = parent::baseFieldDefinitions($entity_type);
 *     $fields += static::reviewableBaseFieldDefinitions();
 *     return $fields;
 *   }
 *
 * @see \Drupal\jaraba_comercio_conecta\Entity\ReviewRetail
 * @see \Drupal\jaraba_agroconecta_core\Entity\ReviewAgro
 * @see \Drupal\jaraba_servicios_conecta\Entity\ReviewServicios
 * @see \Drupal\jaraba_mentoring\Entity\SessionReview
 */
trait ReviewableEntityTrait {

  /**
   * Constantes de estado de moderacion.
   */
  public const STATUS_PENDING = 'pending';
  public const STATUS_APPROVED = 'approved';
  public const STATUS_REJECTED = 'rejected';
  public const STATUS_FLAGGED = 'flagged';

  /**
   * Devuelve los campos compartidos para entidades de review.
   *
   * @return \Drupal\Core\Field\BaseFieldDefinition[]
   *   Array indexado por nombre de campo.
   */
  public static function reviewableBaseFieldDefinitions(): array {
    $fields = [];

    $fields['review_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Estado de moderacion'))
      ->setDescription(new TranslatableMarkup('Estado en el flujo de moderacion.'))
      ->setRequired(TRUE)
      ->setDefaultValue(self::STATUS_PENDING)
      ->setSetting('allowed_values', [
        self::STATUS_PENDING => new TranslatableMarkup('Pendiente'),
        self::STATUS_APPROVED => new TranslatableMarkup('Aprobada'),
        self::STATUS_REJECTED => new TranslatableMarkup('Rechazada'),
        self::STATUS_FLAGGED => new TranslatableMarkup('Marcada'),
      ])
      ->setDisplayOptions('form', ['weight' => 50])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['helpful_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Votos de utilidad'))
      ->setDescription(new TranslatableMarkup('Numero de usuarios que marcaron esta resena como util.'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    $fields['photos'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Fotos'))
      ->setDescription(new TranslatableMarkup('JSON array de file entity IDs adjuntos a la resena.'))
      ->setDisplayOptions('form', ['weight' => 40])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ai_summary'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Resumen IA'))
      ->setDescription(new TranslatableMarkup('Resumen generado automaticamente por IA a partir del conjunto de resenas.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['ai_summary_generated_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Fecha de resumen IA'))
      ->setDescription(new TranslatableMarkup('Timestamp de la ultima generacion del resumen IA.'))
      ->setDisplayConfigurable('view', TRUE);

    // B-09: AI Sentiment Overlay.
    $fields['sentiment'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Sentimiento'))
      ->setDescription(new TranslatableMarkup('Sentimiento detectado automaticamente.'))
      ->setSetting('allowed_values', [
        'positive' => new TranslatableMarkup('Positivo'),
        'neutral' => new TranslatableMarkup('Neutro'),
        'negative' => new TranslatableMarkup('Negativo'),
      ])
      ->setDisplayConfigurable('view', TRUE);

    // B-07: Fake Review Detection score.
    $fields['authenticity_score'] = BaseFieldDefinition::create('float')
      ->setLabel(new TranslatableMarkup('Score de autenticidad'))
      ->setDescription(new TranslatableMarkup('Score de deteccion de resena falsa (0=genuina, 1=sospechosa).'))
      ->setDefaultValue(0.0)
      ->setDisplayConfigurable('view', TRUE);

    // B-01: Not-helpful count (complement of helpful_count).
    $fields['not_helpful_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Votos de no utilidad'))
      ->setDescription(new TranslatableMarkup('Numero de usuarios que marcaron esta resena como no util.'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    // B-01: Wilson Lower Bound score for ranking.
    $fields['wilson_score'] = BaseFieldDefinition::create('float')
      ->setLabel(new TranslatableMarkup('Wilson Score'))
      ->setDescription(new TranslatableMarkup('Wilson Lower Bound score para ranking de utilidad.'))
      ->setDefaultValue(0.0)
      ->setDisplayConfigurable('view', TRUE);

    // Item 14: Flag/report fields.
    $fields['flag_reason'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Razon de reporte'))
      ->setDescription(new TranslatableMarkup('Razon proporcionada al reportar la resena.'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('view', TRUE);

    $fields['flag_reporter_uid'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('UID del reportante'))
      ->setDescription(new TranslatableMarkup('ID del ultimo usuario que reporto la resena.'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    $fields['flag_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Numero de reportes'))
      ->setDescription(new TranslatableMarkup('Veces que esta resena ha sido reportada.'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    // Item 20: Owner response field.
    $fields['owner_response'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Respuesta del propietario'))
      ->setDescription(new TranslatableMarkup('Respuesta del propietario de la entidad target a la resena.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['owner_response_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Fecha de respuesta'))
      ->setDescription(new TranslatableMarkup('Timestamp de la respuesta del propietario.'))
      ->setDisplayConfigurable('view', TRUE);

    // Item 20: Verified purchase flag.
    $fields['verified_purchase'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Compra verificada'))
      ->setDescription(new TranslatableMarkup('Indica si la resena proviene de una compra/transaccion verificada.'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Devuelve la etiqueta legible del estado actual.
   */
  public function getReviewStatusLabel(): string {
    $labels = [
      self::STATUS_PENDING => (string) new TranslatableMarkup('Pendiente de moderacion'),
      self::STATUS_APPROVED => (string) new TranslatableMarkup('Aprobada'),
      self::STATUS_REJECTED => (string) new TranslatableMarkup('Rechazada'),
      self::STATUS_FLAGGED => (string) new TranslatableMarkup('Marcada para revision'),
    ];
    $status = $this->getReviewStatus();
    return $labels[$status] ?? $status;
  }

  /**
   * Devuelve el estado de moderacion.
   *
   * Busca primero en 'review_status' (nombre canonico del trait). Si no existe,
   * busca en 'status' o 'state' (nombres legacy) para retrocompatibilidad.
   */
  public function getReviewStatus(): string {
    if ($this->hasField('review_status') && !$this->get('review_status')->isEmpty()) {
      return $this->get('review_status')->value;
    }
    if ($this->hasField('status') && !$this->get('status')->isEmpty()) {
      return $this->get('status')->value;
    }
    if ($this->hasField('state') && !$this->get('state')->isEmpty()) {
      return $this->get('state')->value;
    }
    return self::STATUS_PENDING;
  }

  /**
   * Indica si la review esta aprobada.
   */
  public function isApprovedReview(): bool {
    return $this->getReviewStatus() === self::STATUS_APPROVED;
  }

  /**
   * Devuelve el rating numerico (1-5).
   */
  public function getReviewRating(): int {
    if ($this->hasField('rating')) {
      return max(0, min(5, (int) ($this->get('rating')->value ?? 0)));
    }
    if ($this->hasField('overall_rating')) {
      return max(0, min(5, (int) ($this->get('overall_rating')->value ?? 0)));
    }
    return 0;
  }

  /**
   * Devuelve el rating como cadena de estrellas Unicode.
   */
  public function getRatingStarsDisplay(): string {
    $rating = $this->getReviewRating();
    return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
  }

  /**
   * Devuelve las fotos como array de file entity IDs.
   */
  public function getReviewPhotos(): array {
    if (!$this->hasField('photos') || $this->get('photos')->isEmpty()) {
      return [];
    }
    $decoded = json_decode($this->get('photos')->value, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

}
