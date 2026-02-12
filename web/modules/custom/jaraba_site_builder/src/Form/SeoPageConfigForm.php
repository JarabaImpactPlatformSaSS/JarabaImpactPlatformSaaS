<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para la entidad SeoPageConfig.
 *
 * Organiza los campos SEO en fieldsets lógicos para
 * facilitar la edición en slide-panel o página completa.
 */
class SeoPageConfigForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function form(array $form, FormStateInterface $form_state): array
    {
        $form = parent::form($form, $form_state);

        // --- Meta Tags Básicos ---
        $form['meta_tags'] = [
            '#type' => 'details',
            '#title' => $this->t('Meta Tags'),
            '#open' => TRUE,
            '#weight' => 0,
        ];
        foreach (['meta_title', 'meta_description', 'canonical_url', 'robots', 'keywords'] as $field) {
            if (isset($form[$field])) {
                $form['meta_tags'][$field] = $form[$field];
                unset($form[$field]);
            }
        }

        // --- Open Graph ---
        $form['open_graph'] = [
            '#type' => 'details',
            '#title' => $this->t('Open Graph'),
            '#open' => FALSE,
            '#weight' => 10,
        ];
        foreach (['og_title', 'og_description', 'og_image', 'twitter_card'] as $field) {
            if (isset($form[$field])) {
                $form['open_graph'][$field] = $form[$field];
                unset($form[$field]);
            }
        }

        // --- Schema.org ---
        $form['schema'] = [
            '#type' => 'details',
            '#title' => $this->t('Schema.org / JSON-LD'),
            '#open' => FALSE,
            '#weight' => 20,
        ];
        foreach (['schema_type', 'schema_custom_json'] as $field) {
            if (isset($form[$field])) {
                $form['schema'][$field] = $form[$field];
                unset($form[$field]);
            }
        }

        // --- Hreflang ---
        $form['hreflang'] = [
            '#type' => 'details',
            '#title' => $this->t('Hreflang / Multi-idioma'),
            '#open' => FALSE,
            '#weight' => 25,
        ];
        if (isset($form['hreflang_config'])) {
            $form['hreflang']['hreflang_config'] = $form['hreflang_config'];
            unset($form['hreflang_config']);
        }

        // --- Geo-Targeting ---
        $form['geo'] = [
            '#type' => 'details',
            '#title' => $this->t('Geo-Targeting'),
            '#open' => FALSE,
            '#weight' => 30,
        ];
        foreach (['geo_region', 'geo_position'] as $field) {
            if (isset($form[$field])) {
                $form['geo'][$field] = $form[$field];
                unset($form[$field]);
            }
        }

        // --- Auditoría (solo lectura) ---
        $form['audit_info'] = [
            '#type' => 'details',
            '#title' => $this->t('Auditoría SEO'),
            '#open' => FALSE,
            '#weight' => 40,
        ];
        foreach (['last_audit_score', 'last_audit_date'] as $field) {
            if (isset($form[$field])) {
                $form['audit_info'][$field] = $form[$field];
                unset($form[$field]);
            }
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $result = parent::save($form, $form_state);

        $entity = $this->getEntity();
        $message = $result === SAVED_NEW
            ? $this->t('Configuración SEO creada para la página @id.', ['@id' => $entity->getPageId()])
            : $this->t('Configuración SEO actualizada para la página @id.', ['@id' => $entity->getPageId()]);

        $this->messenger()->addStatus($message);
        $form_state->setRedirectUrl($entity->toUrl('collection'));

        return $result;
    }

}
