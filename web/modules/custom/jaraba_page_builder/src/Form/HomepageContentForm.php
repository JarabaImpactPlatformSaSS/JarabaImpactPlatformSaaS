<?php

namespace Drupal\jaraba_page_builder\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para HomepageContent.
 */
class HomepageContentForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function form(array $form, FormStateInterface $form_state): array
    {
        $form = parent::form($form, $form_state);

        // Organizar en tabs para mejor UX.
        $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

        // Tab: Hero.
        $form['hero_group'] = [
            '#type' => 'details',
            '#title' => $this->t('Sección Hero'),
            '#open' => TRUE,
            '#weight' => 0,
        ];

        // Mover campos del hero al grupo.
        $hero_fields = [
            'hero_eyebrow',
            'hero_title',
            'hero_subtitle',
            'hero_cta_primary_text',
            'hero_cta_primary_url',
            'hero_cta_secondary_text',
            'hero_cta_secondary_url',
            'hero_scroll_text',
        ];

        foreach ($hero_fields as $field_name) {
            if (isset($form[$field_name])) {
                $form['hero_group'][$field_name] = $form[$field_name];
                unset($form[$field_name]);
            }
        }

        // Tab: Features.
        $form['features_group'] = [
            '#type' => 'details',
            '#title' => $this->t('Características'),
            '#open' => FALSE,
            '#weight' => 10,
        ];

        if (isset($form['features'])) {
            $form['features_group']['features'] = $form['features'];
            unset($form['features']);
        }

        // Tab: Stats.
        $form['stats_group'] = [
            '#type' => 'details',
            '#title' => $this->t('Estadísticas'),
            '#open' => FALSE,
            '#weight' => 20,
        ];

        if (isset($form['stats'])) {
            $form['stats_group']['stats'] = $form['stats'];
            unset($form['stats']);
        }

        // Tab: Intentions.
        $form['intentions_group'] = [
            '#type' => 'details',
            '#title' => $this->t('Intenciones'),
            '#open' => FALSE,
            '#weight' => 30,
        ];

        if (isset($form['intentions'])) {
            $form['intentions_group']['intentions'] = $form['intentions'];
            unset($form['intentions']);
        }

        // Tab: SEO.
        $form['seo_group'] = [
            '#type' => 'details',
            '#title' => $this->t('🔍 SEO / Open Graph'),
            '#description' => $this->t('Configura los meta tags para motores de búsqueda y redes sociales.'),
            '#open' => FALSE,
            '#weight' => 40,
        ];

        $seo_fields = ['meta_title', 'meta_description', 'og_image'];
        foreach ($seo_fields as $field_name) {
            if (isset($form[$field_name])) {
                $form['seo_group'][$field_name] = $form[$field_name];
                unset($form[$field_name]);
            }
        }

        // Tab: Tenant (si existe).
        if (isset($form['tenant_id'])) {
            $form['tenant_group'] = [
                '#type' => 'details',
                '#title' => $this->t('⚙️ Configuración'),
                '#open' => FALSE,
                '#weight' => 50,
            ];
            $form['tenant_group']['tenant_id'] = $form['tenant_id'];
            unset($form['tenant_id']);
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
        $message_args = ['%label' => $entity->label()];

        $this->messenger()->addStatus(
            $result === SAVED_NEW
            ? $this->t('Contenido de homepage %label creado.', $message_args)
            : $this->t('Contenido de homepage %label actualizado.', $message_args)
        );

        $form_state->setRedirectUrl($entity->toUrl('collection'));

        return $result;
    }

}
