<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Form for InsercionLaboral entity.
 *
 * PREMIUM-FORMS-PATTERN-001: Extends PremiumEntityFormBase.
 */
class InsercionLaboralForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'insercion' => [
        'label' => $this->t('Inserción'),
        'icon' => ['category' => 'ui', 'name' => 'briefcase'],
        'description' => $this->t('Datos principales de la inserción laboral.'),
        'fields' => [
          'participante_id',
          'tipo_insercion',
          'fecha_alta',
          'verificado',
        ],
      ],
      'cuenta_ajena' => [
        'label' => $this->t('Cuenta Ajena'),
        'icon' => ['category' => 'ui', 'name' => 'building'],
        'description' => $this->t('Datos específicos de contratación por cuenta ajena.'),
        'fields' => [
          'empresa_nombre',
          'empresa_cif',
          'tipo_contrato',
          'jornada',
          'horas_semanales',
          'codigo_cuenta_cotizacion',
          'sector_actividad',
        ],
      ],
      'cuenta_propia' => [
        'label' => $this->t('Cuenta Propia'),
        'icon' => ['category' => 'ui', 'name' => 'rocket'],
        'description' => $this->t('Datos específicos de alta como autónomo.'),
        'fields' => [
          'fecha_alta_reta',
          'cnae_actividad',
          'sector_emprendimiento',
          'modelo_fiscal',
        ],
      ],
      'agrario' => [
        'label' => $this->t('Agrario'),
        'icon' => ['category' => 'ui', 'name' => 'sprout'],
        'description' => $this->t('Datos específicos de inserción en el sector agrario.'),
        'fields' => [
          'empresa_agraria',
          'tipo_cultivo',
          'fecha_inicio_campana',
          'fecha_fin_campana',
        ],
      ],
      'documentacion' => [
        'label' => $this->t('Documentación'),
        'icon' => ['category' => 'ui', 'name' => 'file-text'],
        'description' => $this->t('Documento acreditativo y observaciones.'),
        'fields' => [
          'documento_acreditativo_id',
          'notas',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'briefcase'];
  }

}
