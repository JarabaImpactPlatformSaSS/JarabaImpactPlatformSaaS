<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * FORMULARIO DOCUMENTO EN SLIDE-PANEL
 *
 * PROPÓSITO:
 * Formulario para subir documentos del tenant.
 * Al guardar, encola el procesamiento en background.
 *
 * PATRÓN:
 * Sigue el patrón slide-panel del proyecto.
 */
class TenantDocumentForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form = parent::buildForm($form, $form_state);

        $form['#attributes']['class'][] = 'jaraba-premium-form';
        $form['#attributes']['class'][] = 'slide-panel__form';
        $form['#attributes']['class'][] = 'document-form';

        $entity = $this->getEntity();

        // Asignar tenant automáticamente si es nueva entidad.
        if ($entity->isNew()) {
            $tenantId = $this->getCurrentTenantId();
            if ($tenantId) {
                $entity->set('tenant_id', $tenantId);
            }
        }

        // Contenedor principal.
        $form['content_wrapper'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['document-form__content']],
        ];

        // Mover campos al wrapper.
        foreach (['file', 'title', 'description', 'category'] as $field) {
            if (isset($form[$field])) {
                $form['content_wrapper'][$field] = $form[$field];
                unset($form[$field]);
            }
        }

        // Información de ayuda.
        $form['content_wrapper']['help'] = [
            '#type' => 'markup',
            '#markup' => '<div class="document-form__help">' .
                '<p>' . $this->t('Formatos soportados: PDF, DOC, DOCX, TXT, MD') . '</p>' .
                '<p>' . $this->t('Tamaño máximo: 10 MB') . '</p>' .
                '<p>' . $this->t('El documento se procesará automáticamente para extraer el texto.') . '</p>' .
                '</div>',
            '#weight' => -5,
        ];

        // Fieldset de configuración.
        $form['settings_wrapper'] = [
            '#type' => 'details',
            '#title' => $this->t('Configuración'),
            '#open' => FALSE,
        ];

        if (isset($form['is_published'])) {
            $form['settings_wrapper']['is_published'] = $form['is_published'];
            unset($form['is_published']);
        }

        // Mostrar estado si no es nuevo.
        if (!$entity->isNew()) {
            /** @var \Drupal\jaraba_tenant_knowledge\Entity\TenantDocument $entity */
            $status = $entity->getProcessingStatus();
            $statusLabels = [
                'pending' => $this->t('Pendiente de procesar'),
                'processing' => $this->t('Procesando...'),
                'completed' => $this->t('Procesado (@chunks chunks)', ['@chunks' => $entity->getChunkCount()]),
                'failed' => $this->t('Error: @error', ['@error' => $entity->get('error_message')->value ?? 'Desconocido']),
            ];

            $form['content_wrapper']['status_info'] = [
                '#type' => 'markup',
                '#markup' => '<div class="document-form__status"><strong>' .
                    $this->t('Estado:') . '</strong> ' . ($statusLabels[$status] ?? $status) .
                    '</div>',
                '#weight' => 10,
            ];

            // Botón de reprocesar si falló.
            if ($entity->hasFailed()) {
                $form['actions']['reprocess'] = [
                    '#type' => 'submit',
                    '#value' => $this->t('Reintentar Procesamiento'),
                    '#submit' => ['::reprocessDocument'],
                    '#weight' => 5,
                ];
            }
        }

        // Ocultar campos de sistema.
        $hiddenFields = [
            'tenant_id',
            'processing_status',
            'extracted_text',
            'chunk_count',
            'error_message',
            'processed_at',
            'content_hash',
        ];
        foreach ($hiddenFields as $field) {
            if (isset($form[$field])) {
                $form[$field]['#access'] = FALSE;
            }
        }

        return $form;
    }

    /**
     * Submit handler para reprocesar documento.
     */
    public function reprocessDocument(array &$form, FormStateInterface $form_state): void
    {
        $entity = $this->getEntity();

        // Resetear estado.
        $entity->set('processing_status', 'pending');
        $entity->set('error_message', NULL);
        $entity->save();

        // Encolar procesamiento.
        $this->enqueueProcessing($entity);

        \Drupal::messenger()->addStatus($this->t('Documento encolado para reprocesamiento.'));
        $form_state->setRedirectUrl(Url::fromRoute('jaraba_tenant_knowledge.documents'));
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $entity = $this->getEntity();
        $isNew = $entity->isNew();

        $status = parent::save($form, $form_state);

        // Si es nuevo, encolar procesamiento.
        if ($isNew) {
            $this->enqueueProcessing($entity);
            \Drupal::messenger()->addStatus($this->t('Documento "@title" subido. El procesamiento se iniciará en breve.', [
                '@title' => $entity->getTitle(),
            ]));
        } else {
            \Drupal::messenger()->addStatus($this->t('Documento "@title" actualizado.', [
                '@title' => $entity->getTitle(),
            ]));
        }

        $form_state->setRedirectUrl(Url::fromRoute('jaraba_tenant_knowledge.documents'));

        return $status;
    }

    /**
     * Encola el documento para procesamiento.
     *
     * @param \Drupal\jaraba_tenant_knowledge\Entity\TenantDocument $document
     *   El documento a procesar.
     */
    protected function enqueueProcessing($document): void
    {
        // En un entorno real, usaríamos colas de Drupal.
        // Por ahora, procesamos en shutdown function.
        drupal_register_shutdown_function(function () use ($document) {
            if (\Drupal::hasService('jaraba_tenant_knowledge.document_processor')) {
                try {
                    $processor = \Drupal::service('jaraba_tenant_knowledge.document_processor');
                    $processor->processDocument($document);
                } catch (\Exception $e) {
                    \Drupal::logger('jaraba_tenant_knowledge')
                        ->error('Error procesando documento @id: @error', [
                            '@id' => $document->id(),
                            '@error' => $e->getMessage(),
                        ]);
                }
            }
        });
    }

    /**
     * Obtiene el tenant ID actual.
     */
    protected function getCurrentTenantId(): ?int
    {
        if (\Drupal::hasService('jaraba_multitenancy.tenant_context')) {
            $tenantContext = \Drupal::service('jaraba_multitenancy.tenant_context');
            $tenant = $tenantContext->getCurrentTenant();
            return $tenant ? (int) $tenant->id() : NULL;
        }
        return NULL;
    }

}
