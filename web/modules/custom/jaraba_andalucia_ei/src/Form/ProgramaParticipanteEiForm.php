<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Form para ProgramaParticipanteEi.
 *
 * PREMIUM-FORMS-PATTERN-001: Extiende PremiumEntityFormBase.
 * 7 secciones cubriendo todo el ciclo de vida PIIL CV 2025.
 */
class ProgramaParticipanteEiForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identificacion' => [
        'label' => $this->t('Identificación'),
        'icon' => ['category' => 'ui', 'name' => 'user'],
        'description' => $this->t('Datos personales del participante.'),
        'fields' => ['dni_nie', 'uid', 'colectivo', 'provincia_participacion'],
      ],
      'programa' => [
        'label' => $this->t('Programa'),
        'icon' => ['category' => 'ui', 'name' => 'calendar'],
        'description' => $this->t('Estado y temporalización del itinerario PIIL.'),
        'fields' => [
          'fase_actual', 'carril', 'fecha_inicio_programa',
          'fecha_fin_programa', 'semana_actual', 'motivo_baja',
        ],
      ],
      'documentacion' => [
        'label' => $this->t('Documentación'),
        'icon' => ['category' => 'ui', 'name' => 'file'],
        'description' => $this->t('Acuerdo de Participación, DACI e indicadores FSE+.'),
        'fields' => [
          'acuerdo_participacion_firmado', 'acuerdo_participacion_fecha',
          'daci_firmado', 'daci_fecha_firma',
          'fse_entrada_completado', 'fse_salida_completado',
        ],
      ],
      'horas' => [
        'label' => $this->t('Horas'),
        'icon' => ['category' => 'ui', 'name' => 'clock'],
        'description' => $this->t('Registro de horas de orientación, formación y mentoría.'),
        'fields' => [
          'horas_orientacion_ind', 'horas_orientacion_grup',
          'horas_mentoria_ia', 'horas_mentoria_humana',
          'horas_formacion', 'horas_orientacion_insercion',
          'asistencia_porcentaje',
        ],
      ],
      'diagnostico' => [
        'label' => $this->t('Diagnóstico'),
        'icon' => ['category' => 'ui', 'name' => 'chart'],
        'description' => $this->t('Resultados del diagnóstico DIME y asignación de itinerario.'),
        'fields' => ['dime_score', 'dime_fecha'],
      ],
      'insercion' => [
        'label' => $this->t('Inserción'),
        'icon' => ['category' => 'ui', 'name' => 'briefcase'],
        'description' => $this->t('Datos de inserción laboral y módulo económico.'),
        'fields' => [
          'tipo_insercion', 'fecha_insercion',
          'es_persona_atendida', 'es_persona_insertada',
          'incentivo_recibido', 'fecha_incentivo',
        ],
      ],
      'segunda_edicion' => [
        'label' => $this->t('2ª Edición'),
        'icon' => ['category' => 'ai', 'name' => 'sparkles'],
        'description' => $this->t('Campos de la 2ª Edición: ruta, pack, competencia IA, packs de servicios.'),
        'fields' => [
          'ruta_programa', 'nivel_digital', 'pack_preseleccionado',
          'pack_confirmado', 'objetivos_smart', 'perfil_riasec',
          'compromiso_firmado', 'compromiso_fecha',
          'estado_programa_2e', 'meses_ss_acumulados',
          'negocio_piloto_id', 'pack_servicio_id',
        ],
      ],
      'integraciones' => [
        'label' => $this->t('Integraciones'),
        'icon' => ['category' => 'ui', 'name' => 'link'],
        'description' => $this->t('Cross-vertical y Club Alumni.'),
        'fields' => [
          'candidate_profile_id', 'canvas_id',
          'is_alumni', 'alumni_fecha', 'alumni_disponible_mentoria',
          'tenant_id', 'status',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'user'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->getEntity();
    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
