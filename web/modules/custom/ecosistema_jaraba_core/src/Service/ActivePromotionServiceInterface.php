<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

/**
 * Interfaz para el servicio de conciencia de promociones activas.
 *
 * Consultable por: copilot IA, templates Twig, emails, CRM, etc.
 * Forma parte del Nivel 1 (SIEMPRE) de la cascada de busqueda IA.
 */
interface ActivePromotionServiceInterface {

  /**
   * Devuelve todas las promociones/programas activos ahora.
   *
   * Filtra por: status=active AND date_start<=NOW AND date_end>=NOW.
   * Ordenadas por priority DESC.
   *
   * @return array<int, array{
   *   id: string,
   *   title: string,
   *   description: string,
   *   vertical: string,
   *   type: string,
   *   highlight_values: array<string, string>,
   *   cta_url: string,
   *   cta_label: string,
   *   secondary_cta_url: string,
   *   secondary_cta_label: string,
   *   priority: int,
   *   copilot_instruction: string,
   *   expires: ?string,
   *   }>
   */
  public function getActivePromotions(): array;

  /**
   * Devuelve promociones activas filtradas por vertical.
   *
   * Incluye tanto las de la vertical especificada como las 'global'.
   *
   * @param string $verticalKey
   *   Clave canonica del vertical (VERTICAL-CANONICAL-001).
   *
   * @return array<int, array<string, mixed>>
   *   Mismo formato que getActivePromotions().
   */
  public function getActivePromotionsByVertical(string $verticalKey): array;

  /**
   * Genera texto formateado para inyectar en system prompts del copilot.
   *
   * Cacheado con tag 'promotion_config_list', max-age 300s.
   *
   * @return string
   *   Bloque de texto con todas las promociones activas.
   *   Vacio si no hay promociones.
   */
  public function buildPromotionContextForCopilot(): string;

}
