<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\ValueObject;

/**
 * Value Object inmutable para resultado de deteccion de avatar.
 *
 * PROPOSITO:
 * Encapsula el resultado de la cascada de deteccion de avatar
 * (Domain -> Path/UTM -> Group -> Rol) con metadatos de confianza.
 *
 * ESTRUCTURA:
 * - avatarType: Tipo de avatar detectado (jobseeker, recruiter, etc.)
 * - vertical: Vertical asociada (empleabilidad, emprendimiento, etc.)
 * - detectionSource: Metodo que resolvio el avatar (domain, path, utm, group, role, default)
 * - programaOrigen: Programa o campana de origen si aplica
 * - confidence: Nivel de confianza 0.0-1.0 (domain=1.0, default=0.1)
 *
 * SINTAXIS:
 * Value Object inmutable - todos los campos son readonly.
 * Se instancia via constructor y metodos estaticos de fabrica.
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\AvatarDetectionService
 */
final class AvatarDetectionResult {

  /**
   * Construye un AvatarDetectionResult.
   *
   * @param string $avatarType
   *   Tipo de avatar detectado.
   * @param string|null $vertical
   *   Vertical asociada al avatar.
   * @param string $detectionSource
   *   Metodo de deteccion usado (domain, path, utm, group, role, default).
   * @param string|null $programaOrigen
   *   Programa o campana de origen.
   * @param float $confidence
   *   Nivel de confianza de la deteccion (0.0-1.0).
   */
  public function __construct(
    public readonly string $avatarType,
    public readonly ?string $vertical,
    public readonly string $detectionSource,
    public readonly ?string $programaOrigen,
    public readonly float $confidence,
  ) {}

  /**
   * Crea un resultado por defecto cuando no se detecta avatar.
   *
   * @return self
   *   Resultado con avatar 'general' y confianza minima.
   */
  public static function createDefault(): self {
    return new self(
      avatarType: 'general',
      vertical: NULL,
      detectionSource: 'default',
      programaOrigen: NULL,
      confidence: 0.1,
    );
  }

  /**
   * Indica si la deteccion fue exitosa (no es el valor por defecto).
   *
   * @return bool
   *   TRUE si se detecto un avatar especifico.
   */
  public function isDetected(): bool {
    return $this->avatarType !== 'general' && $this->confidence > 0.1;
  }

  /**
   * Convierte el resultado a array para serializar o cachear.
   *
   * @return array
   *   Representacion en array del resultado.
   */
  public function toArray(): array {
    return [
      'avatar_type' => $this->avatarType,
      'vertical' => $this->vertical,
      'detection_source' => $this->detectionSource,
      'programa_origen' => $this->programaOrigen,
      'confidence' => $this->confidence,
      'is_detected' => $this->isDetected(),
    ];
  }

}
