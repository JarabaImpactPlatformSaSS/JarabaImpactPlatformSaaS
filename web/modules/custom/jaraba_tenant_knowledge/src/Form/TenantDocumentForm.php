<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Premium form for Tenant Document entities.
 *
 * On save, enqueues document processing in the background.
 */
class TenantDocumentForm extends PremiumEntityFormBase {

  /**
   * Tenant context service.
   */
  protected ?TenantContextService $tenantContext = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->tenantContext = $container->get('ecosistema_jaraba_core.tenant_context');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'document'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormSubtitle() {
    return $this->t('Supported formats: PDF, DOC, DOCX, TXT, MD. Max size: 10 MB.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'content' => [
        'label' => $this->t('Document'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'description' => $this->t('Upload a file and provide descriptive metadata.'),
        'fields' => ['file', 'title', 'description', 'category'],
      ],
      'settings' => [
        'label' => $this->t('Settings'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Publishing and visibility options.'),
        'fields' => ['is_published'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Assign tenant automatically for new entities.
    $entity = $this->getEntity();
    if ($entity->isNew()) {
      $tenantId = $this->getCurrentTenantId();
      if ($tenantId) {
        $entity->set('tenant_id', $tenantId);
      }
    }

    $form = parent::buildForm($form, $form_state);

    // Show processing status for existing documents.
    if (!$entity->isNew()) {
      /** @var \Drupal\jaraba_tenant_knowledge\Entity\TenantDocument $entity */
      $status = $entity->getProcessingStatus();
      $statusLabels = [
        'pending' => $this->t('Pending processing'),
        'processing' => $this->t('Processing...'),
        'completed' => $this->t('Processed (@chunks chunks)', ['@chunks' => $entity->getChunkCount()]),
        'failed' => $this->t('Error: @error', ['@error' => $entity->get('error_message')->value ?? $this->t('Unknown')]),
      ];

      $form['status_info'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['premium-form__section', 'glass-card'],
        ],
        '#weight' => 50,
      ];
      $form['status_info']['markup'] = [
        '#type' => 'markup',
        '#markup' => '<strong>' . $this->t('Status:') . '</strong> ' . ($statusLabels[$status] ?? $status),
      ];

      // Reprocess button if processing failed.
      if ($entity->hasFailed()) {
        $form['actions']['reprocess'] = [
          '#type' => 'submit',
          '#value' => $this->t('Retry Processing'),
          '#submit' => ['::reprocessDocument'],
          '#weight' => 5,
        ];
      }
    }

    // Hide system fields.
    foreach (['tenant_id', 'processing_status', 'extracted_text', 'chunk_count', 'error_message', 'processed_at', 'content_hash'] as $field) {
      if (isset($form[$field])) {
        $form[$field]['#access'] = FALSE;
      }
    }

    return $form;
  }

  /**
   * Submit handler to reprocess a document.
   */
  public function reprocessDocument(array &$form, FormStateInterface $form_state): void {
    $entity = $this->getEntity();

    // Reset status.
    $entity->set('processing_status', 'pending');
    $entity->set('error_message', NULL);
    $entity->save();

    // Enqueue processing.
    $this->enqueueProcessing($entity);

    $this->messenger()->addStatus($this->t('Document enqueued for reprocessing.'));
    $form_state->setRedirectUrl(Url::fromRoute('jaraba_tenant_knowledge.documents'));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->getEntity();
    $isNew = $entity->isNew();

    $result = parent::save($form, $form_state);

    // Enqueue processing for new documents.
    if ($isNew) {
      $this->enqueueProcessing($entity);
    }

    $form_state->setRedirectUrl(Url::fromRoute('jaraba_tenant_knowledge.documents'));

    return $result;
  }

  /**
   * Enqueues the document for background processing.
   *
   * @param \Drupal\jaraba_tenant_knowledge\Entity\TenantDocument $document
   *   The document to process.
   */
  protected function enqueueProcessing($document): void {
    drupal_register_shutdown_function(function () use ($document) {
      if (\Drupal::hasService('jaraba_tenant_knowledge.document_processor')) {
        try {
          $processor = \Drupal::service('jaraba_tenant_knowledge.document_processor');
          $processor->processDocument($document);
        }
        catch (\Exception $e) {
          \Drupal::logger('jaraba_tenant_knowledge')
            ->error('Error processing document @id: @error', [
              '@id' => $document->id(),
              '@error' => $e->getMessage(),
            ]);
        }
      }
    });
  }

  /**
   * Gets the current tenant ID.
   */
  protected function getCurrentTenantId(): ?int {
    if ($this->tenantContext !== NULL) {
      $tenant = $this->tenantContext->getCurrentTenant();
      return $tenant ? (int) $tenant->id() : NULL;
    }
    return NULL;
  }

}
