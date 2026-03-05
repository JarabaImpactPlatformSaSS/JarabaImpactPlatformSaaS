<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\TenantSettings;

/**
 * Define una seccion del hub de configuracion de tenant.
 *
 * Las secciones se registran como tagged services y se agregan
 * automaticamente al registry via TenantSettingsSectionPass.
 */
interface TenantSettingsSectionInterface {

  /**
   * Identificador unico de la seccion.
   */
  public function getId(): string;

  /**
   * Etiqueta visible traducida.
   */
  public function getLabel(): string;

  /**
   * Nombre del icono para jaraba_icon().
   *
   * @return array{category: string, name: string}
   */
  public function getIcon(): array;

  /**
   * Descripcion breve traducida.
   */
  public function getDescription(): string;

  /**
   * Peso para ordenar (menor = primero).
   */
  public function getWeight(): int;

  /**
   * Nombre de ruta Drupal al que enlaza la tarjeta.
   */
  public function getRoute(): string;

  /**
   * Parametros de ruta adicionales.
   *
   * @return array<string, mixed>
   */
  public function getRouteParameters(): array;

  /**
   * Verifica si la seccion es accesible para el usuario actual.
   */
  public function isAccessible(): bool;

  /**
   * Texto de estado breve (ej. "Configurado", "Pendiente").
   */
  public function getStatus(): string;

}
