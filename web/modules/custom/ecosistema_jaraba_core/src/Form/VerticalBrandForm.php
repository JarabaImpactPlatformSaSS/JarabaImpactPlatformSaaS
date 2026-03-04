<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form para VerticalBrandConfig.
 */
class VerticalBrandForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\ecosistema_jaraba_core\Entity\VerticalBrandConfig $entity */
    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nombre interno'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\ecosistema_jaraba_core\Entity\VerticalBrandConfig::load',
      ],
      '#disabled' => !$entity->isNew(),
    ];

    $verticals = [
      'empleabilidad' => 'Empleabilidad',
      'emprendimiento' => 'Emprendimiento',
      'comercioconecta' => 'ComercioConecta',
      'agroconecta' => 'AgroConecta',
      'jarabalex' => 'JarabaLex',
      'serviciosconecta' => 'ServiciosConecta',
      'andalucia_ei' => 'Andalucia EI',
      'jaraba_content_hub' => 'Content Hub',
      'formacion' => 'Formacion',
      'demo' => 'Demo',
    ];

    $form['vertical'] = [
      '#type' => 'select',
      '#title' => $this->t('Vertical'),
      '#options' => $verticals,
      '#default_value' => $entity->getVertical(),
      '#required' => TRUE,
    ];

    $form['branding'] = [
      '#type' => 'details',
      '#title' => $this->t('Marca'),
      '#open' => TRUE,
    ];

    $form['branding']['public_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nombre Publico'),
      '#description' => $this->t('Nombre visible al usuario (ej: Jaraba Empleo).'),
      '#default_value' => $entity->getPublicName(),
      '#required' => TRUE,
    ];

    $form['branding']['tagline'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tagline'),
      '#default_value' => $entity->getTagline(),
    ];

    $form['branding']['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Descripcion'),
      '#default_value' => $entity->getDescription(),
    ];

    $form['visual'] = [
      '#type' => 'details',
      '#title' => $this->t('Visual'),
      '#open' => FALSE,
    ];

    $form['visual']['icon_category'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Categoria Icono'),
      '#default_value' => $entity->getIconCategory(),
    ];

    $form['visual']['icon_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nombre Icono'),
      '#default_value' => $entity->getIconName(),
    ];

    $form['visual']['primary_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Color Primario'),
      '#description' => $this->t('Codigo hex (ej: #00A9A5).'),
      '#default_value' => $entity->getPrimaryColor(),
    ];

    $form['visual']['secondary_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Color Secundario'),
      '#default_value' => $entity->getSecondaryColor(),
    ];

    $form['visual']['hero_image_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL Imagen Hero'),
      '#default_value' => $entity->getHeroImageUrl(),
    ];

    $form['seo'] = [
      '#type' => 'details',
      '#title' => $this->t('SEO'),
      '#open' => FALSE,
    ];

    $form['seo']['og_image_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL Imagen OpenGraph'),
      '#default_value' => $entity->getOgImageUrl(),
    ];

    $form['seo']['seo_title_template'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Template Titulo SEO'),
      '#description' => $this->t('Usar {page_title} como placeholder.'),
      '#default_value' => $entity->getSeoTitleTemplate(),
    ];

    $form['seo']['seo_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Meta Description'),
      '#default_value' => $entity->getSeoDescription(),
    ];

    $form['seo']['schema_org_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Schema.org Type'),
      '#default_value' => $entity->getSchemaOrgType(),
    ];

    $form['behavior'] = [
      '#type' => 'details',
      '#title' => $this->t('Comportamiento'),
      '#open' => TRUE,
    ];

    $form['behavior']['revelation_level'] = [
      '#type' => 'select',
      '#title' => $this->t('Nivel Revelacion'),
      '#options' => [
        'landing' => $this->t('Landing (anonimo)'),
        'trial' => $this->t('Trial (plan free)'),
        'expansion' => $this->t('Expansion (starter/profesional)'),
        'enterprise' => $this->t('Enterprise'),
      ],
      '#default_value' => $entity->getRevelationLevel(),
    ];

    $form['behavior']['landing_route'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ruta Landing'),
      '#description' => $this->t('Nombre de ruta Drupal de la landing del vertical.'),
      '#default_value' => $entity->getLandingRoute(),
    ];

    $form['behavior']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Vertical activo'),
      '#default_value' => $entity->isEnabled(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->entity;
    $status = $entity->save();

    if ($status === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Vertical brand %label creado.', [
        '%label' => $entity->label(),
      ]));
    }
    else {
      $this->messenger()->addStatus($this->t('Vertical brand %label actualizado.', [
        '%label' => $entity->label(),
      ]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $status;
  }

}
