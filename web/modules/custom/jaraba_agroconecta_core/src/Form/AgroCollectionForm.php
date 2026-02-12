<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar colecciones AgroConecta.
 *
 * Incluye campos agrupados para datos principales y configuración.
 * Valida JSON de product_ids y rules según el tipo de colección.
 */
class AgroCollectionForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function form(array $form, FormStateInterface $form_state): array
    {
        $form = parent::form($form, $form_state);

        // Agrupar campos principales.
        $form['collection_details'] = [
            '#type' => 'details',
            '#title' => $this->t('Datos de la colección'),
            '#open' => TRUE,
            '#weight' => -10,
        ];

        $fields_in_group = ['name', 'slug', 'description', 'image', 'type'];
        foreach ($fields_in_group as $field_name) {
            if (isset($form[$field_name])) {
                $form['collection_details'][$field_name] = $form[$field_name];
                unset($form[$field_name]);
            }
        }

        // Grupo de contenido (productos/reglas).
        $form['content'] = [
            '#type' => 'details',
            '#title' => $this->t('Contenido'),
            '#open' => TRUE,
            '#weight' => 0,
        ];

        $content_fields = ['product_ids', 'rules'];
        foreach ($content_fields as $field_name) {
            if (isset($form[$field_name])) {
                $form['content'][$field_name] = $form[$field_name];
                unset($form[$field_name]);
            }
        }

        // Grupo de configuración.
        $form['configuration'] = [
            '#type' => 'details',
            '#title' => $this->t('Configuración'),
            '#open' => TRUE,
            '#weight' => 5,
        ];

        $config_fields = ['position', 'is_featured', 'is_active'];
        foreach ($config_fields as $field_name) {
            if (isset($form[$field_name])) {
                $form['configuration'][$field_name] = $form[$field_name];
                unset($form[$field_name]);
            }
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state): void
    {
        parent::validateForm($form, $form_state);

        $type = $form_state->getValue(['type', 0, 'value']);

        // Validar JSON de product_ids si es colección manual.
        if ($type === 'manual') {
            $product_ids = $form_state->getValue(['product_ids', 0, 'value']);
            if ($product_ids) {
                $decoded = json_decode($product_ids, TRUE);
                if (!is_array($decoded)) {
                    $form_state->setErrorByName('product_ids', $this->t('Los IDs de productos deben ser un JSON array válido. Ej: [1, 5, 12]'));
                }
            }
        }

        // Validar JSON de rules si es colección smart.
        if ($type === 'smart') {
            $rules = $form_state->getValue(['rules', 0, 'value']);
            if ($rules) {
                $decoded = json_decode($rules, TRUE);
                if (!is_array($decoded)) {
                    $form_state->setErrorByName('rules', $this->t('Las reglas deben ser un JSON object válido. Ej: {"category_id": 5, "min_rating": 4}'));
                }
            }
        }

        // Validar tipo válido.
        $valid_types = ['manual', 'smart'];
        if ($type && !in_array($type, $valid_types)) {
            $form_state->setErrorByName('type', $this->t('Tipo de colección no válido. Tipos permitidos: @types.', [
                '@types' => implode(', ', $valid_types),
            ]));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $result = parent::save($form, $form_state);

        $entity = $this->getEntity();
        $message_args = ['%name' => $entity->label()];

        if ($result === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('Colección %name creada correctamente.', $message_args));
        } else {
            $this->messenger()->addStatus($this->t('Colección %name actualizada correctamente.', $message_args));
        }

        $form_state->setRedirectUrl($entity->toUrl('collection'));

        return $result;
    }

}
