<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides a 'Hero Landing' Block for the homepage.
 *
 * @Block(
 *   id = "hero_landing_block",
 *   admin_label = @Translation("Hero Landing - Homepage"),
 *   category = @Translation("Jaraba")
 * )
 */
class HeroLandingBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#theme' => 'hero_landing',
      '#eyebrow' => $this->t('Jaraba Impact Platform'),
      '#title' => $this->t('Impulsa tu ecosistema digital'),
      '#subtitle' => $this->t('La plataforma que conecta talento, negocios y productores con inteligencia artificial'),
      '#dark' => FALSE,
      '#attached' => [
        'library' => [
          'ecosistema_jaraba_theme/hero-landing',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return Cache::mergeContexts(parent::getCacheContexts(), ['user.roles']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    return Cache::mergeTags(parent::getCacheTags(), ['config:system.site']);
  }

}
