<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interfaz para la entidad PromotionConfig.
 */
interface PromotionConfigInterface extends ConfigEntityInterface {

  /**
   * Devuelve la descripcion extendida.
   */
  public function getDescription(): string;

  /**
   * Establece la descripcion.
   */
  public function setDescription(string $description): static;

  /**
   * Devuelve la clave canonica del vertical o 'global'.
   */
  public function getVertical(): string;

  /**
   * Establece el vertical.
   */
  public function setVertical(string $vertical): static;

  /**
   * Devuelve el tipo de promocion.
   */
  public function getType(): string;

  /**
   * Establece el tipo.
   */
  public function setType(string $type): static;

  /**
   * Devuelve datos destacados como array asociativo.
   *
   * @return array<string, string>
   */
  public function getHighlightValues(): array;

  /**
   * Establece datos destacados.
   *
   * @param array<string, string> $values
   */
  public function setHighlightValues(array $values): static;

  /**
   * Devuelve la URL del CTA principal.
   */
  public function getCtaUrl(): string;

  /**
   * Establece la URL del CTA.
   */
  public function setCtaUrl(string $url): static;

  /**
   * Devuelve el texto del CTA principal.
   */
  public function getCtaLabel(): string;

  /**
   * Establece el texto del CTA.
   */
  public function setCtaLabel(string $label): static;

  /**
   * Devuelve la URL del CTA secundario.
   */
  public function getSecondaryCtaUrl(): string;

  /**
   * Devuelve el texto del CTA secundario.
   */
  public function getSecondaryCtaLabel(): string;

  /**
   * Devuelve la fecha de inicio (Y-m-d) o vacio.
   */
  public function getDateStart(): string;

  /**
   * Devuelve la fecha de fin (Y-m-d) o vacio.
   */
  public function getDateEnd(): string;

  /**
   * Devuelve la prioridad (mayor = mas importante).
   */
  public function getPriority(): int;

  /**
   * Devuelve la instruccion especial para el copilot.
   */
  public function getCopilotInstruction(): string;

  /**
   * Establece la instruccion para el copilot.
   */
  public function setCopilotInstruction(string $instruction): static;

  /**
   * Determina si la promocion esta activa ahora (status + fechas).
   */
  public function isCurrentlyActive(): bool;

}
