<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\jaraba_content_hub\Entity\ContentArticle;
use Drupal\jaraba_ai_agents\Attribute\AgentTool;
use Psr\Log\LoggerInterface;

/**
 * Puente entre Content Hub y Email Marketing.
 *
 * Transforma artículos en borradores de campañas de Newsletter.
 */
class NewsletterBridgeService {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Crea un borrador de campaña de email a partir de un artículo.
   */
  #[AgentTool(
    name: 'hub_create_newsletter',
    description: 'Crea un borrador de newsletter a partir de un artículo del Content Hub.',
    parameters: [
      'articleId' => ['type' => 'integer', 'description' => 'ID del artículo de origen']
    ]
  )]
  public function createArticleNewsletter(ContentArticle $article): ?int {
    try {
      $storage = $this->entityTypeManager->getStorage('email_campaign');
      if (!$storage) {
        return NULL;
      }

      // Buscar template de newsletter por defecto.
      $template = $this->entityTypeManager->getStorage('email_template')
        ->loadByProperties(['machine_name' => 'newsletter_default']);
      $template = !empty($template) ? reset($template) : NULL;

      $campaign = $storage->create([
        'name' => $this->t('Newsletter: @title', ['@title' => $article->label()]),
        'subject' => $article->get('seo_title')->value ?: $article->label(),
        'template_id' => $template ? $template->id() : NULL,
        'status' => 'draft',
        'tenant_id' => $article->get('tenant_id')->target_id,
        'metadata' => json_encode([
          'source_article_id' => $article->id(),
          'auto_generated' => TRUE,
        ]),
      ]);

      // Aquí se podría inyectar el cuerpo del artículo en un campo body_html 
      // si la entidad EmailCampaign lo soporta.
      $campaign->save();

      $this->logger->info('Borrador de newsletter creado para el artículo @id', ['@id' => $article->id()]);
      return (int) $campaign->id();
    }
    catch (\Exception $e) {
      $this->logger->error('Error creando newsletter desde artículo: @error', ['@error' => $e->getMessage()]);
      return NULL;
    }
  }

}
