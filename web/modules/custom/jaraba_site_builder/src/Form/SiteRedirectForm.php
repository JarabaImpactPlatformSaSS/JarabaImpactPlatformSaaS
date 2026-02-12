<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear y editar redirects.
 */
class SiteRedirectForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function form(array $form, FormStateInterface $form_state): array
    {
        $form = parent::form($form, $form_state);

        // URLs.
        $form['urls'] = [
            '#type' => 'details',
            '#title' => $this->t('URLs'),
            '#open' => TRUE,
            '#weight' => 0,
        ];
        $form['urls']['source_path'] = $form['source_path'];
        $form['urls']['source_path']['widget'][0]['value']['#description'] = $this->t('Ruta de origen sin dominio. Ejemplo: /old-page');
        $form['urls']['destination_path'] = $form['destination_path'];
        $form['urls']['destination_path']['widget'][0]['value']['#description'] = $this->t('Ruta de destino o URL completa. Ejemplo: /new-page o https://example.com');
        $form['urls']['redirect_type'] = $form['redirect_type'];
        unset($form['source_path'], $form['destination_path'], $form['redirect_type']);

        // Opciones.
        $form['options'] = [
            '#type' => 'details',
            '#title' => $this->t('Opciones'),
            '#open' => FALSE,
            '#weight' => 10,
        ];
        $form['options']['reason'] = $form['reason'];
        $form['options']['is_active'] = $form['is_active'];
        $form['options']['expires_at'] = $form['expires_at'];
        unset($form['reason'], $form['is_active'], $form['expires_at']);

        // Ocultar campos técnicos.
        if (isset($form['hit_count'])) {
            $form['hit_count']['#access'] = FALSE;
        }
        if (isset($form['last_hit'])) {
            $form['last_hit']['#access'] = FALSE;
        }
        if (isset($form['is_auto_generated'])) {
            $form['is_auto_generated']['#access'] = FALSE;
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state): void
    {
        parent::validateForm($form, $form_state);

        // Validar que source_path empiece con /.
        $sourcePath = $form_state->getValue(['source_path', 0, 'value']) ?? '';
        if (!empty($sourcePath) && !str_starts_with($sourcePath, '/')) {
            $form_state->setErrorByName('source_path', $this->t('La URL de origen debe empezar con /'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $entity = $this->entity;
        $result = parent::save($form, $form_state);

        $source = $entity->get('source_path')->value;
        $destination = $entity->get('destination_path')->value;

        $this->messenger()->addStatus($this->t('Redirect de %source a %destination guardado.', [
            '%source' => $source,
            '%destination' => $destination,
        ]));

        $form_state->setRedirect('jaraba_site_builder.redirects');

        return $result;
    }

}
