<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * FORMULARIO ENRIQUECIMIENTO DE PRODUCTO.
 *
 * Slide-panel con campos para descripción extendida, especificaciones,
 * beneficios y FAQs específicas del producto.
 */
class TenantProductEnrichmentForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form = parent::buildForm($form, $form_state);

        $form['#attributes']['class'][] = 'jaraba-premium-form';
        $form['#attributes']['class'][] = 'slide-panel__form';
        $form['#attributes']['class'][] = 'product-enrichment-form';

        $entity = $this->getEntity();

        // Asignar tenant automáticamente.
        if ($entity->isNew()) {
            $tenantId = $this->getCurrentTenantId();
            if ($tenantId) {
                $entity->set('tenant_id', $tenantId);
            }
        }

        // Grupo: Identificación.
        $form['identification'] = [
            '#type' => 'details',
            '#title' => $this->t('Identificación'),
            '#open' => TRUE,
            '#weight' => -10,
        ];

        foreach (['product_sku', 'product_name', 'category'] as $field) {
            if (isset($form[$field])) {
                $form['identification'][$field] = $form[$field];
                unset($form[$field]);
            }
        }

        // Grupo: Descripción.
        $form['description_group'] = [
            '#type' => 'details',
            '#title' => $this->t('Descripción y Especificaciones'),
            '#open' => TRUE,
            '#weight' => 0,
        ];

        foreach (['description', 'specifications', 'benefits', 'use_cases'] as $field) {
            if (isset($form[$field])) {
                $form['description_group'][$field] = $form[$field];
                unset($form[$field]);
            }
        }

        // Grupo: Pricing y FAQs.
        $form['details_group'] = [
            '#type' => 'details',
            '#title' => $this->t('Precios y FAQs'),
            '#open' => FALSE,
            '#weight' => 5,
        ];

        foreach (['price_info', 'product_faqs'] as $field) {
            if (isset($form[$field])) {
                $form['details_group'][$field] = $form[$field];
                unset($form[$field]);
            }
        }

        // Grupo: Configuración.
        $form['settings'] = [
            '#type' => 'details',
            '#title' => $this->t('Configuración'),
            '#open' => FALSE,
            '#weight' => 10,
        ];

        if (isset($form['is_published'])) {
            $form['settings']['is_published'] = $form['is_published'];
            unset($form['is_published']);
        }

        // Ocultar campos de sistema.
        $hiddenFields = ['tenant_id', 'content_hash', 'qdrant_point_id'];
        foreach ($hiddenFields as $field) {
            if (isset($form[$field])) {
                $form[$field]['#access'] = FALSE;
            }
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $entity = $this->getEntity();

        // Actualizar hash antes de guardar.
        $entity->updateContentHash();

        $status = parent::save($form, $form_state);

        if ($entity->isNew()) {
            \Drupal::messenger()->addStatus($this->t('Producto "@name" creado.', [
                '@name' => $entity->getProductName(),
            ]));
        } else {
            \Drupal::messenger()->addStatus($this->t('Producto "@name" actualizado.', [
                '@name' => $entity->getProductName(),
            ]));
        }

        $form_state->setRedirectUrl(Url::fromRoute('jaraba_tenant_knowledge.products'));

        return $status;
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
