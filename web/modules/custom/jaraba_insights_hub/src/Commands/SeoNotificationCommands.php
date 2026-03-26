<?php

declare(strict_types=1);

namespace Drupal\jaraba_insights_hub\Commands;

use Drupal\jaraba_insights_hub\Service\GoogleSeoNotificationService;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;

/**
 * SEO-DEPLOY-NOTIFY-001: Comandos Drush para notificaciones SEO a Google.
 */
class SeoNotificationCommands extends DrushCommands {

  public function __construct(
    protected GoogleSeoNotificationService $notificationService,
  ) {
    parent::__construct();
  }

  /**
   * Envia sitemaps a Google Search Console para dominios de produccion.
   *
   * @param array<string, mixed> $options
   *   Opciones del comando.
   */
  #[CLI\Command(name: 'jaraba:seo:notify-google', aliases: ['seo:notify'])]
  #[CLI\Option(name: 'domain', description: 'Dominio especifico (default: todos).')]
  #[CLI\Option(name: 'dry-run', description: 'Muestra que haria sin ejecutar.')]
  #[CLI\Usage(name: 'drush jaraba:seo:notify-google', description: 'Envia sitemaps para los 4 dominios.')]
  #[CLI\Usage(name: 'drush seo:notify --domain=jarabaimpact.com', description: 'Solo un dominio.')]
  public function notifyGoogle(
    array $options = [
      'domain' => NULL,
      'dry-run' => FALSE,
    ],
  ): void {
    $dryRun = (bool) $options['dry-run'];
    $domainFilter = [];

    if ($options['domain'] !== NULL && $options['domain'] !== '') {
      $domainFilter = [(string) $options['domain']];
    }

    if ($dryRun) {
      $config = \Drupal::config('jaraba_insights_hub.settings');
      /** @var string[] $domains */
      $domains = (array) ($config->get('seo_notification_domains') ?? []);
      if ($domainFilter !== []) {
        $domains = array_intersect($domains, $domainFilter);
      }
      $this->io()->note('Modo dry-run: no se enviaran sitemaps.');
      foreach ($domains as $domain) {
        $this->io()->text("  Enviaria 4 sitemaps para {$domain}");
      }
      return;
    }

    $this->io()->text('Enviando sitemaps a Google Search Console...');
    $result = $this->notificationService->submitAllSitemaps($domainFilter);

    foreach ($result['details'] as $domain => $detail) {
      $status = $detail['status'] ?? 'unknown';
      if ($status === 'success') {
        $this->io()->success("{$domain}: " . ($detail['submitted'] ?? 0) . ' sitemaps enviados');
      }
      elseif ($status === 'skipped') {
        $this->io()->warning("{$domain}: omitido — " . ($detail['reason'] ?? ''));
      }
      else {
        $this->io()->error("{$domain}: " . ($detail['reason'] ?? 'error'));
      }
    }

    $submitted = $result['submitted'];
    $errors = $result['errors'];
    if ($errors === 0 && $submitted > 0) {
      $this->io()->success("Total: {$submitted} sitemaps enviados, 0 errores.");
    }
    elseif ($submitted > 0) {
      $this->io()->warning("Total: {$submitted} enviados, {$errors} errores.");
    }
    else {
      $this->io()->error("No se pudo enviar ningun sitemap. {$errors} errores.");
    }
  }

  /**
   * Envia notificacion de URL a Google Indexing API.
   *
   * @param string $url
   *   URL absoluta a notificar.
   * @param array<string, mixed> $options
   *   Opciones del comando.
   */
  #[CLI\Command(name: 'jaraba:seo:notify-url', aliases: ['seo:url'])]
  #[CLI\Argument(name: 'url', description: 'URL absoluta a notificar.')]
  #[CLI\Option(name: 'type', description: 'URL_UPDATED o URL_DELETED (default: URL_UPDATED).')]
  #[CLI\Usage(name: 'drush seo:url https://jarabaimpact.com/es/planes', description: 'Notifica URL actualizada.')]
  public function notifyUrl(
    string $url,
    array $options = ['type' => 'URL_UPDATED'],
  ): void {
    $type = (string) $options['type'];
    if (!in_array($type, ['URL_UPDATED', 'URL_DELETED'], TRUE)) {
      $this->io()->error("Tipo invalido: {$type}. Usar URL_UPDATED o URL_DELETED.");
      return;
    }

    $this->io()->text("Notificando {$type}: {$url}");
    $result = $this->notificationService->notifyUrlChange($url, $type);

    if ($result['status'] === 'success') {
      $this->io()->success("Notificacion enviada (HTTP {$result['code']}).");
    }
    elseif ($result['status'] === 'disabled') {
      $this->io()->warning('SEO notifications deshabilitadas en config.');
    }
    else {
      $this->io()->error("Error: {$result['error']}");
    }
  }

}
