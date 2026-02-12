<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para la configuración del sitio.
 *
 * Usa slide-panel para edición sin abandonar la página.
 */
class SiteConfigForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function form(array $form, FormStateInterface $form_state): array
    {
        $form = parent::form($form, $form_state);

        // Organizar en grupos verticales (fieldsets).
        $form['identity'] = [
            '#type' => 'details',
            '#title' => $this->t('Identidad del Sitio'),
            '#open' => TRUE,
            '#weight' => 0,
        ];
        $form['identity']['site_name'] = $form['site_name'];
        $form['identity']['site_tagline'] = $form['site_tagline'];
        $form['identity']['site_logo'] = $form['site_logo'];
        $form['identity']['site_favicon'] = $form['site_favicon'];
        unset($form['site_name'], $form['site_tagline'], $form['site_logo'], $form['site_favicon']);

        $form['pages'] = [
            '#type' => 'details',
            '#title' => $this->t('Páginas Especiales'),
            '#open' => FALSE,
            '#weight' => 10,
        ];
        $form['pages']['homepage_id'] = $form['homepage_id'];
        $form['pages']['blog_index_id'] = $form['blog_index_id'];
        $form['pages']['error_404_id'] = $form['error_404_id'];
        unset($form['homepage_id'], $form['blog_index_id'], $form['error_404_id']);

        $form['seo'] = [
            '#type' => 'details',
            '#title' => $this->t('SEO y Analytics'),
            '#open' => FALSE,
            '#weight' => 20,
        ];
        $form['seo']['meta_title_suffix'] = $form['meta_title_suffix'];
        $form['seo']['default_og_image'] = $form['default_og_image'];
        $form['seo']['google_analytics_id'] = $form['google_analytics_id'];
        $form['seo']['google_tag_manager_id'] = $form['google_tag_manager_id'];
        unset($form['meta_title_suffix'], $form['default_og_image'], $form['google_analytics_id'], $form['google_tag_manager_id']);

        $form['contact'] = [
            '#type' => 'details',
            '#title' => $this->t('Datos de Contacto'),
            '#open' => FALSE,
            '#weight' => 30,
        ];
        $form['contact']['contact_email'] = $form['contact_email'];
        $form['contact']['contact_phone'] = $form['contact_phone'];
        $form['contact']['contact_address'] = $form['contact_address'];
        $form['contact']['contact_coordinates'] = $form['contact_coordinates'];
        unset($form['contact_email'], $form['contact_phone'], $form['contact_address'], $form['contact_coordinates']);

        $form['social'] = [
            '#type' => 'details',
            '#title' => $this->t('Redes Sociales'),
            '#open' => FALSE,
            '#weight' => 40,
        ];
        $form['social']['social_links'] = $form['social_links'];
        unset($form['social_links']);

        $form['legal'] = [
            '#type' => 'details',
            '#title' => $this->t('Páginas Legales'),
            '#open' => FALSE,
            '#weight' => 50,
        ];
        $form['legal']['privacy_policy_id'] = $form['privacy_policy_id'];
        $form['legal']['terms_conditions_id'] = $form['terms_conditions_id'];
        $form['legal']['cookies_policy_id'] = $form['cookies_policy_id'];
        unset($form['privacy_policy_id'], $form['terms_conditions_id'], $form['cookies_policy_id']);

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $entity = $this->entity;
        $result = parent::save($form, $form_state);

        $this->messenger()->addStatus($this->t('Configuración del sitio guardada.'));

        $form_state->setRedirect('jaraba_site_builder.settings');

        return $result;
    }

}
