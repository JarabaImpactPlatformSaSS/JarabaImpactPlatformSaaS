<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

/**
 * Interface for ProgramaVerticalAccessService.
 *
 * Determina qué verticales del ecosistema están disponibles para
 * participantes activas del programa Andalucía +ei según su carril.
 */
interface ProgramaVerticalAccessInterface {

  /**
   * Verifica si un usuario tiene acceso a un vertical por programa.
   *
   * @param int $uid
   *   ID del usuario.
   * @param string $vertical
   *   ID del vertical (empleabilidad, emprendimiento, etc.).
   *
   * @return bool
   *   TRUE si tiene acceso activo.
   */
  public function hasAccess(int $uid, string $vertical): bool;

  /**
   * Obtiene los verticales activos para un usuario del programa.
   *
   * @param int $uid
   *   ID del usuario.
   *
   * @return string[]
   *   IDs de verticales accesibles.
   */
  public function getActiveVerticals(int $uid): array;

  /**
   * Verifica si el acceso del usuario ha expirado.
   *
   * @param int $uid
   *   ID del usuario.
   *
   * @return bool
   *   TRUE si el acceso ha expirado (fase baja/alumni sin extensión).
   */
  public function isExpired(int $uid): bool;

  /**
   * Obtiene los días restantes de acceso.
   *
   * @param int $uid
   *   ID del usuario.
   *
   * @return int|null
   *   Días restantes, NULL si indefinido (fase activa), -1 si expirado.
   */
  public function getDiasRestantes(int $uid): ?int;

}
