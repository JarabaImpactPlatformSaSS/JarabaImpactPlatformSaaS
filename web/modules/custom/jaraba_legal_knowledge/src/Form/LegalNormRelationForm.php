<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_knowledge\Form;

use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para la entidad Legal Norm Relation.
 *
 * PREMIUM-FORMS-PATTERN-001: Extiende PremiumEntityFormBase.
 */
class LegalNormRelationForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'relation' => [
        'label' => $this->t('Relacion Normativa'),
        'icon' => ['category' => 'legal', 'name' => 'balance'],
        'description' => $this->t('Definicion de la relacion entre normas.'),
        'fields' => [
          'source_norm_id',
          'target_norm_id',
          'relation_type',
          'effective_date',
        ],
      ],
      'details' => [
        'label' => $this->t('Detalles'),
        'icon' => ['category' => 'ui', 'name' => 'list'],
        'description' => $this->t('Articulos afectados y metadatos.'),
        'fields' => [
          'affected_articles',
          'metadata',
        ],
      ],
      'admin' => [
        'label' => $this->t('Administracion'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Campos administrativos.'),
        'fields' => [
          'tenant_id',
          'uid',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'legal', 'name' => 'balance'];
  }

}
