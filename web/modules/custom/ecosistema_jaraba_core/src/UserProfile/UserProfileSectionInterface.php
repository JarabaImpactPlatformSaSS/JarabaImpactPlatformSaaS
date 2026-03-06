<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\UserProfile;

/**
 * Define una seccion del perfil de usuario.
 *
 * Las secciones se registran como tagged services y se agregan
 * automaticamente al registry via UserProfileSectionPass.
 *
 * Cada modulo del ecosistema puede contribuir secciones sin tocar
 * el theme ni el core, simplemente registrando un servicio con el tag
 * 'ecosistema_jaraba_core.user_profile_section'.
 *
 * Patron identico a TenantSettingsSectionInterface.
 *
 * @see \Drupal\ecosistema_jaraba_core\TenantSettings\TenantSettingsSectionInterface
 */
interface UserProfileSectionInterface {

  /**
   * Identificador unico de la seccion (ej: 'professional_profile').
   */
  public function getId(): string;

  /**
   * Titulo visible traducido, contextual al usuario.
   *
   * @param int $uid
   *   ID del usuario cuyo perfil se visualiza.
   */
  public function getTitle(int $uid): string;

  /**
   * Subtitulo breve traducido.
   *
   * @param int $uid
   *   ID del usuario cuyo perfil se visualiza.
   */
  public function getSubtitle(int $uid): string;

  /**
   * Icono para jaraba_icon() — ICON-CONVENTION-001.
   *
   * @return array{category: string, name: string}
   */
  public function getIcon(): array;

  /**
   * Color semantico de la seccion.
   *
   * Valores permitidos: 'innovation', 'impulse', 'corporate', 'neutral',
   * 'primary', 'danger', 'servicios', 'andalucia' u otro token del tema.
   */
  public function getColor(): string;

  /**
   * Peso para ordenar (menor = primero). Rango recomendado: 0-100.
   */
  public function getWeight(): int;

  /**
   * Array de links contextuales para el usuario dado.
   *
   * Cada link debe contener:
   * - 'label' (string|\Drupal\Core\StringTranslation\TranslatableMarkup)
   * - 'url' (string) — Resuelta via Url::fromRoute() (ROUTE-LANGPREFIX-001)
   * - 'icon_category' (string)
   * - 'icon_name' (string)
   * - 'color' (string)
   * - 'description' (string) — Opcional
   * - 'slide_panel' (bool) — Opcional, default FALSE
   * - 'slide_panel_title' (string) — Opcional
   * - 'cross_vertical' (bool) — Opcional, default FALSE
   *
   * @param int $uid
   *   ID del usuario cuyo perfil se visualiza.
   *
   * @return array<int, array<string, mixed>>
   */
  public function getLinks(int $uid): array;

  /**
   * Determina si esta seccion es aplicable/visible para el usuario dado.
   *
   * Evaluacion lazy: solo se llama getLinks() si isApplicable() retorna TRUE.
   *
   * @param int $uid
   *   ID del usuario cuyo perfil se visualiza.
   */
  public function isApplicable(int $uid): bool;

  /**
   * Datos extra para widgets custom (completitud, badges, contadores).
   *
   * El template page--user.html.twig los consume como keys del array
   * de seccion (ej: $section['profile_completeness']).
   *
   * @param int $uid
   *   ID del usuario cuyo perfil se visualiza.
   *
   * @return array<string, mixed>
   */
  public function getExtraData(int $uid): array;

}
