<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar categorías AgroConecta.
 *
 * Incluye campos agrupados para datos principales, SEO y configuración visual.
 */
class AgroCategoryForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function form(array $form, FormStateInterface $form_state): array
    {
        $form = parent::form($form, $form_state);

        // Agrupar campos principales.
        $form['category_details'] = [
            '#type' => 'details',
            '#title' => $this->t('Datos de la categoría'),
            '#open' => TRUE,
            '#weight' => -10,
        ];

        $fields_in_group = ['name', 'slug', 'description', 'parent_id', 'icon', 'image'];
        foreach ($fields_in_group as $field_name) {
            if (isset($form[$field_name])) {
                $form['category_details'][$field_name] = $form[$field_name];
                unset($form[$field_name]);
            }
        }

        // Grupo de configuración.
        $form['configuration'] = [
            '#type' => 'details',
            '#title' => $this->t('Configuración'),
            '#open' => TRUE,
            '#weight' => 0,
        ];

        $config_fields = ['position', 'is_featured', 'is_active'];
        foreach ($config_fields as $field_name) {
            if (isset($form[$field_name])) {
                $form['configuration'][$field_name] = $form[$field_name];
                unset($form[$field_name]);
            }
        }

        // Grupo SEO.
        $form['seo'] = [
            '#type' => 'details',
            '#title' => $this->t('SEO'),
            '#open' => FALSE,
            '#weight' => 5,
        ];

        $seo_fields = ['meta_title', 'meta_description'];
        foreach ($seo_fields as $field_name) {
            if (isset($form[$field_name])) {
                $form['seo'][$field_name] = $form[$field_name];
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

        // Validar slug único (si se proporciona).
        $slug = $form_state->getValue(['slug', 0, 'value']);
        if ($slug) {
            $entity = $this->getEntity();
            $storage = $this->entityTypeManager->getStorage('agro_category');
            $query = $storage->getQuery()
                ->condition('slug', $slug)
                ->accessCheck(FALSE);

            // Excluir la entidad actual si estamos editando.
            if ($entity->id()) {
                $query->condition('id', $entity->id(), '<>');
            }

            $existing = $query->count()->execute();
            if ($existing > 0) {
                $form_state->setErrorByName('slug', $this->t('El slug "@slug" ya está en uso por otra categoría.', [
                    '@slug' => $slug,
                ]));
            }
        }

        // Validar que no se seleccione a sí misma como padre.
        $parent_id = $form_state->getValue(['parent_id', 0, 'target_id']);
        $entity = $this->getEntity();
        if ($parent_id && $entity->id() && (int) $parent_id === (int) $entity->id()) {
            $form_state->setErrorByName('parent_id', $this->t('Una categoría no puede ser su propio padre.'));
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
            $this->messenger()->addStatus($this->t('Categoría %name creada correctamente.', $message_args));
        } else {
            $this->messenger()->addStatus($this->t('Categoría %name actualizada correctamente.', $message_args));
        }

        $form_state->setRedirectUrl($entity->toUrl('collection'));

        return $result;
    }

}
