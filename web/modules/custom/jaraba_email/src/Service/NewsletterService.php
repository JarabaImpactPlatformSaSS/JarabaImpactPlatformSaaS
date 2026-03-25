<?php

declare(strict_types=1);

namespace Drupal\jaraba_email\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_email\Entity\EmailCampaign;
use Psr\Log\LoggerInterface;

/**
 * Servicio para gestionar campañas de newsletter integradas con Content Hub.
 *
 * PROPÓSITO:
 * Proporciona funcionalidad de alto nivel para creación y gestión de
 * newsletters basados en artículos del Content Hub. Automatiza la
 * generación de campañas semanales y mensuales.
 *
 * CARACTERÍSTICAS:
 * - Creación de newsletters desde artículos seleccionados
 * - Generación automática de digest semanal
 * - Construcción de HTML responsive para emails
 * - Estadísticas de rendimiento de campañas
 *
 * ESPECIFICACIÓN: Doc 139 - Marketing_AI_Stack_Native
 */
class NewsletterService {

  /**
   * El gestor de tipos de entidad.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * El servicio de campañas.
   *
   * @var \Drupal\jaraba_email\Service\CampaignService
   */
  protected CampaignService $campaignService;

  /**
   * El servicio de suscriptores.
   *
   * @var \Drupal\jaraba_email\Service\SubscriberService
   */
  protected SubscriberService $subscriberService;

  /**
   * El logger para registrar eventos.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Construye un NewsletterService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   El gestor de tipos de entidad.
   * @param \Drupal\jaraba_email\Service\CampaignService $campaignService
   *   Servicio para gestión de campañas.
   * @param \Drupal\jaraba_email\Service\SubscriberService $subscriberService
   *   Servicio para gestión de suscriptores.
   * @param \Psr\Log\LoggerInterface $logger
   *   El servicio de logging.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    CampaignService $campaignService,
    SubscriberService $subscriberService,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->campaignService = $campaignService;
    $this->subscriberService = $subscriberService;
    $this->logger = $logger;
  }

  /**
   * Crea una campaña de newsletter desde artículos destacados.
   *
   * Genera una entidad EmailCampaign de tipo 'newsletter' con los
   * artículos y listas especificados. La campaña se crea en estado
   * 'draft' para revisión antes del envío.
   *
   * @param string $name
   *   El nombre del newsletter.
   * @param array $articleIds
   *   Array de IDs de entidades content_article.
   * @param array $listIds
   *   Array de IDs de entidades email_list (listas de destinatarios).
   * @param array $options
   *   Opciones adicionales:
   *   - 'subject': Línea de asunto del email.
   *   - 'from_name': Nombre del remitente.
   *   - 'from_email': Email del remitente.
   *   - 'scheduled_at': Fecha/hora de envío programado.
   *
   * @return \Drupal\jaraba_email\Entity\EmailCampaign
   *   La campaña creada.
   */
  public function createNewsletter(string $name, array $articleIds, array $listIds, array $options = []): EmailCampaign {
    $storage = $this->entityTypeManager->getStorage('email_campaign');

    $campaign = $storage->create([
      'name' => $name,
      'type' => 'newsletter',
      'status' => 'draft',
      'subject_line' => $options['subject'] ?? $name,
      'preview_text' => $options['preview_text'] ?? '',
      'from_name' => $options['from_name'] ?? 'Jaraba Newsletter',
      'from_email' => $options['from_email'] ?? 'newsletter@jaraba.io',
      'reply_to' => $options['reply_to'] ?? '',
      'list_ids' => array_map(fn($id) => ['target_id' => $id], $listIds),
      'article_ids' => array_map(fn($id) => ['target_id' => $id], $articleIds),
      'scheduled_at' => $options['scheduled_at'] ?? NULL,
    ]);

    $campaign->save();

    $this->logger->info('Newsletter creado: @name con @count artículos', [
      '@name' => $name,
      '@count' => count($articleIds),
    ]);

    return $campaign;
  }

  /**
   * Crea automáticamente un newsletter de resumen semanal.
   *
   * Obtiene los artículos publicados en los últimos 7 días y genera
   * una campaña de newsletter. Útil para automatización via cron.
   *
   * @param int $limit
   *   Número máximo de artículos a incluir.
   * @param array $listIds
   *   IDs de las listas destino.
   *
   * @return \Drupal\jaraba_email\Entity\EmailCampaign|null
   *   La campaña creada o NULL si no hay artículos nuevos.
   */
  public function createWeeklyDigest(int $limit = 5, array $listIds = []): ?EmailCampaign {
    // Obtener artículos publicados en los últimos 7 días.
    $articleStorage = $this->entityTypeManager->getStorage('content_article');

    $weekAgo = strtotime('-7 days');
    $query = $articleStorage->getQuery()
      ->condition('status', 'published')
      ->condition('published_at', date('Y-m-d\TH:i:s', $weekAgo), '>')
      ->sort('published_at', 'DESC')
      ->range(0, $limit)
      ->accessCheck(FALSE);

    $articleIds = $query->execute();

    if (empty($articleIds)) {
      $this->logger->info('No hay artículos nuevos para el resumen semanal.');
      return NULL;
    }

    $weekNumber = date('W');
    $year = date('Y');

    return $this->createNewsletter(
          "Resumen Semanal - Semana {$weekNumber}, {$year}",
          array_values($articleIds),
          $listIds,
          [
            'subject' => "📰 Tu resumen semanal - Semana {$weekNumber}",
            'preview_text' => 'Los mejores artículos de esta semana',
          ]
      );
  }

  /**
   * Genera el HTML del newsletter desde los artículos de la campaña.
   *
   * Construye un email HTML responsive con los artículos incluidos,
   * incluyendo título, extracto y enlace a cada uno. El footer
   * incluye placeholder para enlace de baja.
   *
   * @param \Drupal\jaraba_email\Entity\EmailCampaign $campaign
   *   La entidad campaña.
   *
   * @return string
   *   El HTML generado listo para envío.
   */
  public function generateNewsletterHtml(EmailCampaign $campaign): string {
    $articleIds = [];
    foreach ($campaign->get('article_ids') as $item) {
      if ($item->target_id) {
        $articleIds[] = $item->target_id;
      }
    }

    if (empty($articleIds)) {
      return '';
    }

    $articleStorage = $this->entityTypeManager->getStorage('content_article');
    $articles = $articleStorage->loadMultiple($articleIds);

    // Construir HTML para cada artículo.
    $articlesHtml = '';
    foreach ($articles as $article) {
      $title = $article->label();
      $excerpt = $article->get('excerpt')->value ?? '';
      $url = $article->toUrl('canonical', ['absolute' => TRUE])->toString();

      $articlesHtml .= <<<HTML
      <div style="margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid #e5e5e5;">
        <h2 style="margin: 0 0 8px; font-size: 18px;">
          <a href="{$url}" style="color: #2563eb; text-decoration: none;">{$title}</a>
        </h2>
        <p style="margin: 0; color: #666; font-size: 14px; line-height: 1.5;">{$excerpt}</p>
        <a href="{$url}" style="display: inline-block; margin-top: 12px; color: #2563eb; font-size: 14px;">Leer más →</a>
      </div>
      HTML;
    }

    return <<<HTML
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
    </head>
    <body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
      <header style="text-align: center; margin-bottom: 32px;">
        <h1 style="margin: 0; font-size: 24px; color: #1a1a1a;">{$campaign->getName()}</h1>
      </header>
      
      <main>
        {$articlesHtml}
      </main>
      
      <footer style="margin-top: 32px; padding-top: 24px; border-top: 1px solid #e5e5e5; text-align: center; font-size: 12px; color: #999;">
        <p>Recibiste este email porque estás suscrito a nuestro newsletter.</p>
        <p><a href="{{unsubscribe_url}}" style="color: #999;">Cancelar suscripción</a></p>
      </footer>
    </body>
    </html>
    HTML;
  }

  /**
   * Obtiene estadísticas de rendimiento de newsletters.
   *
   * Calcula métricas agregadas de campañas de newsletter enviadas
   * en el período especificado.
   *
   * @param int $days
   *   Número de días hacia atrás para analizar.
   *
   * @return array
   *   Estadísticas:
   *   - 'total_campaigns': Número de campañas.
   *   - 'total_sent': Total de emails enviados.
   *   - 'total_opens': Aperturas únicas.
   *   - 'total_clicks': Clics únicos.
   *   - 'avg_open_rate': Tasa de apertura promedio (%).
   *   - 'avg_click_rate': Tasa de clics promedio (%).
   */
  public function getNewsletterStats(int $days = 30): array {
    $storage = $this->entityTypeManager->getStorage('email_campaign');

    $fromDate = date('Y-m-d\TH:i:s', strtotime("-{$days} days"));

    $query = $storage->getQuery()
      ->condition('type', 'newsletter')
      ->condition('status', 'sent')
      ->condition('sent_at', $fromDate, '>')
      ->accessCheck(FALSE);

    $campaignIds = $query->execute();
    $campaigns = $storage->loadMultiple($campaignIds);

    $stats = [
      'total_campaigns' => count($campaigns),
      'total_sent' => 0,
      'total_opens' => 0,
      'total_clicks' => 0,
      'avg_open_rate' => 0,
      'avg_click_rate' => 0,
    ];

    foreach ($campaigns as $campaign) {
      $stats['total_sent'] += (int) $campaign->get('total_sent')->value;
      $stats['total_opens'] += (int) $campaign->get('unique_opens')->value;
      $stats['total_clicks'] += (int) $campaign->get('unique_clicks')->value;
    }

    if ($stats['total_sent'] > 0) {
      $stats['avg_open_rate'] = ($stats['total_opens'] / $stats['total_sent']) * 100;
      $stats['avg_click_rate'] = ($stats['total_clicks'] / $stats['total_sent']) * 100;
    }

    return $stats;
  }

}
