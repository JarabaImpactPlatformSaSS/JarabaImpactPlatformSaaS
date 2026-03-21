<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Thank-you page after SolicitudEi submission.
 *
 * Provides:
 * - Confirmation message with next steps timeline.
 * - Guide download CTA.
 * - Social sharing buttons (WhatsApp, Facebook).
 * - Conversion tracking event for campaign attribution.
 */
class SolicitudConfirmadaController extends ControllerBase {

  /**
   * The extension path resolver.
   */
  protected ExtensionPathResolver $pathResolver;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->pathResolver = $container->get('extension.path.resolver');
    return $instance;
  }

  /**
   * Renders the confirmation page.
   *
   * @return array<string, mixed>
   *   Render array with theme 'solicitud_confirmada'.
   */
  public function page(): array {
    $config = $this->config('jaraba_andalucia_ei.settings');

    $guiaUrl = Url::fromRoute('jaraba_andalucia_ei.guia_participante')->toString();
    $dashboardUrl = Url::fromRoute('jaraba_andalucia_ei.dashboard')->toString();
    $modulePath = $this->pathResolver->getPath('module', 'jaraba_andalucia_ei');

    return [
      '#theme' => 'solicitud_confirmada',
      '#guia_url' => $guiaUrl,
      '#dashboard_url' => $dashboardUrl,
      '#module_path' => $modulePath,
      '#incentivo' => (int) ($config->get('incentivo_euros') ?? 528),
      '#cache' => [
        'contexts' => ['url.path'],
        'tags' => ['config:jaraba_andalucia_ei.settings'],
        'max-age' => 3600,
      ],
    ];
  }

}
