<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar pedidos agro.
 */
class OrderAgroForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $result = parent::save($form, $form_state);

        $entity = $this->getEntity();
        $this->messenger()->addStatus($this->t('Pedido @number guardado.', [
            '@number' => $entity->get('order_number')->value ?? $entity->id(),
        ]));

        $form_state->setRedirect('entity.order_agro.collection');

        return $result;
    }

}
