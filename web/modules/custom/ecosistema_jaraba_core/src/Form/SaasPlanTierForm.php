<?php

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar entidades SaasPlanTier.
 *
 * Permite configurar los tiers de planes SaaS con aliases,
 * Stripe Price IDs y jerarquia.
 */
class SaasPlanTierForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\ecosistema_jaraba_core\Entity\SaasPlanTierInterface $tier */
    $tier = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nombre del tier'),
      '#maxlength' => 255,
      '#default_value' => $tier->label(),
      '#description' => $this->t('Nombre legible del tier (ej: Starter, Professional, Enterprise).'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $tier->id(),
      '#machine_name' => [
        'exists' => '\Drupal\ecosistema_jaraba_core\Entity\SaasPlanTier::load',
      ],
      '#disabled' => !$tier->isNew(),
      '#description' => $this->t('ID unico del tier (ej: starter, professional, enterprise).'),
    ];

    // =====================================================================
    // IDENTIFICACION
    // =====================================================================
    $form['identification'] = [
      '#type' => 'details',
      '#title' => $this->t('Identificacion'),
      '#open' => TRUE,
    ];

    $form['identification']['tier_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Clave canonica del tier'),
      '#maxlength' => 64,
      '#default_value' => $tier->getTierKey(),
      '#required' => TRUE,
      '#description' => $this->t('Clave canonica normalizada (ej: starter, professional, enterprise).'),
    ];

    $form['identification']['aliases'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Aliases'),
      '#default_value' => implode("\n", $tier->getAliases()),
      '#description' => $this->t('Un alias por linea. Se usan para normalizar nombres de plan entrantes (ej: pro, profesional, professional).'),
      '#rows' => 4,
    ];

    $form['identification']['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Descripcion'),
      '#default_value' => $tier->getDescription(),
      '#description' => $this->t('Descripcion interna del tier para administradores.'),
      '#rows' => 2,
    ];

    // =====================================================================
    // STRIPE INTEGRATION
    // =====================================================================
    $form['stripe'] = [
      '#type' => 'details',
      '#title' => $this->t('Integracion Stripe'),
      '#open' => FALSE,
      '#description' => $this->t('IDs de precios de Stripe para este tier.'),
    ];

    $form['stripe']['stripe_price_monthly'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Stripe Price ID (Mensual)'),
      '#maxlength' => 255,
      '#default_value' => $tier->getStripePriceMonthly(),
      '#description' => $this->t('ID del precio mensual en Stripe (ej: price_1234abcd).'),
    ];

    $form['stripe']['stripe_price_yearly'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Stripe Price ID (Anual)'),
      '#maxlength' => 255,
      '#default_value' => $tier->getStripePriceYearly(),
      '#description' => $this->t('ID del precio anual en Stripe (ej: price_5678efgh).'),
    ];

    // =====================================================================
    // ORDEN Y ESTADO
    // =====================================================================
    $form['weight'] = [
      '#type' => 'number',
      '#title' => $this->t('Peso'),
      '#default_value' => $tier->getWeight(),
      '#description' => $this->t('Orden jerarquico (menor = tier inferior). Starter=0, Professional=10, Enterprise=20.'),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activo'),
      '#default_value' => $tier->status(),
      '#description' => $this->t('Si esta desactivado, este tier no se muestra en opciones de plan.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $tier = $this->entity;

    // Convertir aliases de textarea a array.
    $aliases_raw = $form_state->getValue('aliases') ?? '';
    $aliases = array_filter(array_map('trim', explode("\n", $aliases_raw)));
    $tier->set('aliases', array_values($aliases));

    $status = $tier->save();

    if ($status === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Tier %label creado.', [
        '%label' => $tier->label(),
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('Tier %label actualizado.', [
        '%label' => $tier->label(),
      ]));
    }

    $form_state->setRedirectUrl($tier->toUrl('collection'));
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    $actions['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancelar'),
      '#url' => $this->entity->toUrl('collection'),
      '#attributes' => [
        'class' => ['button'],
      ],
      '#weight' => 10,
    ];

    if (!$this->entity->isNew()) {
      $actions['delete'] = [
        '#type' => 'link',
        '#title' => $this->t('Eliminar'),
        '#url' => $this->entity->toUrl('delete-form'),
        '#attributes' => [
          'class' => ['button', 'button--danger'],
        ],
        '#weight' => 20,
      ];
    }

    return $actions;
  }

}
