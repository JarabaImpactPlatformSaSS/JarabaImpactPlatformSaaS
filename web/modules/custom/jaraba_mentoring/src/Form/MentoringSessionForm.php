<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Form;

use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario premium para la entidad MentoringSession.
 *
 * PREMIUM-FORMS-PATTERN-001: Extiende PremiumEntityFormBase con secciones
 * glassmorphism. Patron A (Simple) — sin DI extra ni logica custom.
 */
class MentoringSessionForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'relaciones' => [
        'label' => $this->t('Relaciones'),
        'icon' => ['category' => 'general', 'name' => 'users'],
        'description' => $this->t('Participantes y engagement de la sesion.'),
        'fields' => [
          'engagement_id',
          'mentor_id',
          'mentee_id',
          'session_number',
          'tenant_id',
        ],
      ],
      'programacion' => [
        'label' => $this->t('Programacion'),
        'icon' => ['category' => 'general', 'name' => 'calendar'],
        'description' => $this->t('Fechas y horarios de la sesion.'),
        'fields' => [
          'scheduled_start',
          'scheduled_end',
          'timezone',
          'actual_start',
          'actual_end',
          'session_type',
          'status',
        ],
      ],
      'videollamada' => [
        'label' => $this->t('Videollamada'),
        'icon' => ['category' => 'general', 'name' => 'video'],
        'description' => $this->t('Configuracion de la videollamada.'),
        'fields' => [
          'meeting_provider',
          'meeting_room_id',
          'meeting_url',
        ],
      ],
      'contenido' => [
        'label' => $this->t('Contenido de la sesion'),
        'icon' => ['category' => 'general', 'name' => 'file-text'],
        'description' => $this->t('Notas, objetivos, acuerdos y proximos pasos.'),
        'fields' => [
          'agenda',
          'session_notes',
          'objectives_worked',
          'agreements',
          'next_steps',
        ],
      ],
      'valoraciones' => [
        'label' => $this->t('Valoraciones y firma'),
        'icon' => ['category' => 'ui', 'name' => 'star'],
        'description' => $this->t('Puntuaciones y estado de firma digital.'),
        'fields' => [
          'participant_rating',
          'mentor_rating',
          'service_sheet_doc',
          'firma_participante_status',
          'firma_orientador_status',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'general', 'name' => 'calendar'];
  }

}
