<?php

declare(strict_types=1);

namespace Drupal\jaraba_addons\Service;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Compatibility matrix service for add-ons per vertical.
 *
 * Implements the compatibility matrix from Doc 158 §4, determining
 * which add-ons are compatible/recommended for each vertical.
 */
class AddonCompatibilityService {

  use StringTranslationTrait;

  /**
   * Compatibility levels: 'recommended', 'available', 'not_applicable'.
   *
   * Maps addon machine_name => vertical => compatibility level.
   */
  protected const COMPATIBILITY_MATRIX = [
    'jaraba_crm' => [
      'empleabilidad' => 'available',
      'emprendimiento' => 'recommended',
      'agroconecta' => 'available',
      'comercioconecta' => 'available',
      'serviciosconecta' => 'recommended',
      'jarabalex' => 'available',
      'andalucia_ei' => 'not_applicable',
      'formacion' => 'available',
      'jaraba_content_hub' => 'available',
    ],
    'jaraba_email' => [
      'empleabilidad' => 'recommended',
      'emprendimiento' => 'recommended',
      'agroconecta' => 'recommended',
      'comercioconecta' => 'recommended',
      'serviciosconecta' => 'recommended',
      'jarabalex' => 'available',
      'andalucia_ei' => 'available',
      'formacion' => 'recommended',
      'jaraba_content_hub' => 'recommended',
    ],
    'jaraba_email_plus' => [
      'empleabilidad' => 'available',
      'emprendimiento' => 'recommended',
      'agroconecta' => 'recommended',
      'comercioconecta' => 'recommended',
      'serviciosconecta' => 'available',
      'jarabalex' => 'not_applicable',
      'andalucia_ei' => 'not_applicable',
      'formacion' => 'available',
      'jaraba_content_hub' => 'available',
    ],
    'jaraba_social' => [
      'empleabilidad' => 'available',
      'emprendimiento' => 'recommended',
      'agroconecta' => 'recommended',
      'comercioconecta' => 'recommended',
      'serviciosconecta' => 'available',
      'jarabalex' => 'not_applicable',
      'andalucia_ei' => 'not_applicable',
      'formacion' => 'available',
      'jaraba_content_hub' => 'recommended',
    ],
    'paid_ads_sync' => [
      'empleabilidad' => 'available',
      'emprendimiento' => 'available',
      'agroconecta' => 'recommended',
      'comercioconecta' => 'recommended',
      'serviciosconecta' => 'available',
      'jarabalex' => 'not_applicable',
      'andalucia_ei' => 'not_applicable',
      'formacion' => 'available',
      'jaraba_content_hub' => 'available',
    ],
    'retargeting_pixels' => [
      'empleabilidad' => 'available',
      'emprendimiento' => 'available',
      'agroconecta' => 'recommended',
      'comercioconecta' => 'recommended',
      'serviciosconecta' => 'available',
      'jarabalex' => 'not_applicable',
      'andalucia_ei' => 'not_applicable',
      'formacion' => 'available',
      'jaraba_content_hub' => 'available',
    ],
    'events_webinars' => [
      'empleabilidad' => 'recommended',
      'emprendimiento' => 'recommended',
      'agroconecta' => 'available',
      'comercioconecta' => 'available',
      'serviciosconecta' => 'recommended',
      'jarabalex' => 'available',
      'andalucia_ei' => 'recommended',
      'formacion' => 'recommended',
      'jaraba_content_hub' => 'available',
    ],
    'ab_testing' => [
      'empleabilidad' => 'available',
      'emprendimiento' => 'available',
      'agroconecta' => 'available',
      'comercioconecta' => 'recommended',
      'serviciosconecta' => 'available',
      'jarabalex' => 'not_applicable',
      'andalucia_ei' => 'not_applicable',
      'formacion' => 'available',
      'jaraba_content_hub' => 'available',
    ],
    'referral_program' => [
      'empleabilidad' => 'recommended',
      'emprendimiento' => 'recommended',
      'agroconecta' => 'recommended',
      'comercioconecta' => 'recommended',
      'serviciosconecta' => 'available',
      'jarabalex' => 'not_applicable',
      'andalucia_ei' => 'not_applicable',
      'formacion' => 'available',
      'jaraba_content_hub' => 'available',
    ],
  ];

  /**
   * Gets the recommendation level for an addon in a given vertical.
   *
   * @param string $addonMachineName
   *   The addon machine name.
   * @param string $vertical
   *   The vertical machine name.
   *
   * @return string
   *   One of 'recommended', 'available', or 'not_applicable'.
   */
  public function getRecommendationLevel(string $addonMachineName, string $vertical): string {
    return self::COMPATIBILITY_MATRIX[$addonMachineName][$vertical] ?? 'not_applicable';
  }

  /**
   * Gets all compatible addons for a vertical (excludes 'not_applicable').
   *
   * @param string $vertical
   *   The vertical machine name.
   *
   * @return array<string, string>
   *   Associative array of machine_name => level.
   */
  public function getCompatibleAddons(string $vertical): array {
    $compatible = [];
    foreach (self::COMPATIBILITY_MATRIX as $addonMachineName => $verticals) {
      $level = $verticals[$vertical] ?? 'not_applicable';
      if ($level !== 'not_applicable') {
        $compatible[$addonMachineName] = $level;
      }
    }
    return $compatible;
  }

  /**
   * Gets machine names of recommended addons for a vertical.
   *
   * @param string $vertical
   *   The vertical machine name.
   *
   * @return string[]
   *   Array of addon machine names where level is 'recommended'.
   */
  public function getRecommendedAddons(string $vertical): array {
    $recommended = [];
    foreach (self::COMPATIBILITY_MATRIX as $addonMachineName => $verticals) {
      $level = $verticals[$vertical] ?? 'not_applicable';
      if ($level === 'recommended') {
        $recommended[] = $addonMachineName;
      }
    }
    return $recommended;
  }

  /**
   * Checks whether an addon is compatible with a vertical.
   *
   * @param string $addonMachineName
   *   The addon machine name.
   * @param string $vertical
   *   The vertical machine name.
   *
   * @return bool
   *   TRUE if the addon is compatible (not 'not_applicable').
   */
  public function isCompatible(string $addonMachineName, string $vertical): bool {
    return $this->getRecommendationLevel($addonMachineName, $vertical) !== 'not_applicable';
  }

}
