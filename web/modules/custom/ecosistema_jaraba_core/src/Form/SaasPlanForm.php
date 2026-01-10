<?php

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar entidades SaasPlan.
 */
class SaasPlanForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildForm($form, $form_state);

        // Información básica.
        $form['basic'] = [
            '#type' => 'details',
            '#title' => $this->t('Información del Plan'),
            '#open' => TRUE,
            '#weight' => -10,
        ];

        $form['basic']['name'] = $form['name'];
        $form['basic']['vertical'] = $form['vertical'];
        $form['basic']['status'] = $form['status'];
        $form['basic']['weight'] = $form['weight'];
        unset($form['name'], $form['vertical'], $form['status'], $form['weight']);

        // Precios.
        $form['pricing'] = [
            '#type' => 'details',
            '#title' => $this->t('Precios'),
            '#open' => TRUE,
            '#weight' => 0,
        ];

        $form['pricing']['price_monthly'] = $form['price_monthly'];
        $form['pricing']['price_yearly'] = $form['price_yearly'];
        $form['pricing']['stripe_price_id'] = $form['stripe_price_id'];
        unset($form['price_monthly'], $form['price_yearly'], $form['stripe_price_id']);

        // Features.
        $form['features_section'] = [
            '#type' => 'details',
            '#title' => $this->t('Features Incluidas'),
            '#open' => TRUE,
            '#weight' => 10,
        ];

        if (isset($form['features'])) {
            $form['features_section']['features'] = $form['features'];
            unset($form['features']);
        }

        // Límites.
        $form['limits_section'] = [
            '#type' => 'details',
            '#title' => $this->t('Límites del Plan'),
            '#open' => TRUE,
            '#weight' => 20,
            '#description' => $this->t('Define los límites en formato JSON. Usa -1 para ilimitado.'),
        ];

        if (isset($form['limits'])) {
            $form['limits_section']['limits'] = $form['limits'];
            $form['limits_section']['limits']['widget'][0]['value']['#description'] = $this->t('Ejemplo: {"productores": 50, "storage_gb": 25, "ai_queries": 100}');
            unset($form['limits']);
        }

        // Añadir ayuda contextual.
        $form['limits_section']['help'] = [
            '#type' => 'markup',
            '#markup' => '<div class="description">' .
                '<strong>' . $this->t('Claves disponibles:') . '</strong><br>' .
                '<code>productores</code> - ' . $this->t('Máximo de productores') . '<br>' .
                '<code>storage_gb</code> - ' . $this->t('Almacenamiento en GB') . '<br>' .
                '<code>ai_queries</code> - ' . $this->t('Queries de IA por mes (0 = no incluido)') . '<br>' .
                '<code>webhooks</code> - ' . $this->t('Número de webhooks') . '<br>' .
                '</div>',
            '#weight' => 100,
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        parent::validateForm($form, $form_state);

        // Validar JSON de limits.
        $limits = $form_state->getValue(['limits', 0, 'value']);
        if ($limits) {
            $decoded = json_decode($limits, TRUE);
            if ($decoded === NULL) {
                $form_state->setErrorByName('limits', $this->t('Los límites deben ser un JSON válido.'));
            }
        }

        // Validar que precio anual sea menor que mensual * 12.
        $monthly = (float) $form_state->getValue(['price_monthly', 0, 'value']);
        $yearly = (float) $form_state->getValue(['price_yearly', 0, 'value']);

        if ($yearly > 0 && $yearly > ($monthly * 12)) {
            $form_state->setErrorByName('price_yearly', $this->t('El precio anual debería ser menor o igual que el mensual × 12 (actualmente @expected €).', [
                '@expected' => number_format($monthly * 12, 2),
            ]));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state)
    {
        $result = parent::save($form, $form_state);

        $entity = $this->entity;
        $message_arguments = ['%label' => $entity->label()];

        if ($result == SAVED_NEW) {
            $this->messenger()->addStatus($this->t('Plan SaaS %label creado.', $message_arguments));
        } else {
            $this->messenger()->addStatus($this->t('Plan SaaS %label actualizado.', $message_arguments));
        }

        $form_state->setRedirectUrl($entity->toUrl('collection'));
        return $result;
    }

}
