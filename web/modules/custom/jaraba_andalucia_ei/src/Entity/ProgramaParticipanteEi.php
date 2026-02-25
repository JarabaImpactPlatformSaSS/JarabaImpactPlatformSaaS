<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad ProgramaParticipanteEi.
 *
 * Participante en el Programa Andalucía +ei con datos de seguimiento,
 * tracking de horas de mentoría IA y transiciones de fase PIIL.
 *
 * @ContentEntityType(
 *   id = "programa_participante_ei",
 *   label = @Translation("Participante Andalucía +ei"),
 *   label_collection = @Translation("Participantes Andalucía +ei"),
 *   label_singular = @Translation("participante Andalucía +ei"),
 *   label_plural = @Translation("participantes Andalucía +ei"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_andalucia_ei\ProgramaParticipanteEiListBuilder",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_andalucia_ei\ProgramaParticipanteEiAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "programa_participante_ei",
 *   admin_permission = "administer andalucia ei",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "dni_nie",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/andalucia-ei/{programa_participante_ei}",
 *     "add-form" = "/admin/content/andalucia-ei/add",
 *     "edit-form" = "/admin/content/andalucia-ei/{programa_participante_ei}/edit",
 *     "delete-form" = "/admin/content/andalucia-ei/{programa_participante_ei}/delete",
 *     "collection" = "/admin/content/andalucia-ei",
 *   },
 *   field_ui_base_route = "entity.programa_participante_ei.settings",
 * )
 */
class ProgramaParticipanteEi extends ContentEntityBase implements ProgramaParticipanteEiInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * {@inheritdoc}
     */
    public function getDniNie(): string
    {
        return $this->get('dni_nie')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setDniNie(string $dni_nie): self
    {
        $this->set('dni_nie', $dni_nie);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getColectivo(): string
    {
        return $this->get('colectivo')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getFaseActual(): string
    {
        return $this->get('fase_actual')->value ?? 'atencion';
    }

    /**
     * {@inheritdoc}
     */
    public function setFaseActual(string $fase): self
    {
        $this->set('fase_actual', $fase);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getHorasMentoriaIa(): float
    {
        return (float) ($this->get('horas_mentoria_ia')->value ?? 0);
    }

    /**
     * {@inheritdoc}
     */
    public function getHorasMentoriaHumana(): float
    {
        return (float) ($this->get('horas_mentoria_humana')->value ?? 0);
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalHorasOrientacion(): float
    {
        $individual = (float) ($this->get('horas_orientacion_ind')->value ?? 0);
        $grupal = (float) ($this->get('horas_orientacion_grup')->value ?? 0);
        return $individual + $grupal + $this->getHorasMentoriaIa() + $this->getHorasMentoriaHumana();
    }

    /**
     * {@inheritdoc}
     */
    public function canTransitToInsercion(): bool
    {
        // Según Doc 45 § 4.3: mínimo 10h orientación + 50h formación.
        $horasOrientacion = $this->getTotalHorasOrientacion();
        $horasFormacion = (float) ($this->get('horas_formacion')->value ?? 0);

        return $horasOrientacion >= 10 && $horasFormacion >= 50;
    }

    /**
     * {@inheritdoc}
     */
    public function hasReceivedIncentivo(): bool
    {
        return (bool) ($this->get('incentivo_recibido')->value ?? FALSE);
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        // === DATOS DE IDENTIFICACIÓN ===

        // Owner field (uid) provided by EntityOwnerTrait — configure display.
        $fields['uid']
            ->setLabel(t('Usuario Drupal'))
            ->setDescription(t('Usuario vinculado al participante.'))
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('Tenant al que pertenece este participante.'))
            ->setSetting('target_type', 'group')
            ->setRequired(FALSE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['group_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Grupo Andalucía +ei'))
            ->setDescription(t('Grupo del programa al que pertenece.'))
            ->setSetting('target_type', 'group')
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['dni_nie'] = BaseFieldDefinition::create('string')
            ->setLabel(t('DNI/NIE'))
            ->setDescription(t('Documento identificativo del participante.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 12)
            ->addConstraint('UniqueField')
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -9,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['colectivo'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Colectivo'))
            ->setDescription(t('Colectivo destino del participante.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'jovenes' => t('Jóvenes 18-29 Garantía Juvenil'),
                'mayores_45' => t('Mayores de 45 años'),
                'larga_duracion' => t('Desempleados larga duración'),
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['provincia_participacion'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Provincia'))
            ->setDescription(t('Provincia de inscripción en el STO.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'cadiz' => t('Cádiz'),
                'granada' => t('Granada'),
                'malaga' => t('Málaga'),
                'sevilla' => t('Sevilla'),
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -7,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['fecha_alta_sto'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha Alta STO'))
            ->setDescription(t('Fecha de registro en el STO (inmutable).'))
            ->setRequired(TRUE)
            ->setSetting('datetime_type', 'date')
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => -6,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === FASE PIIL ===

        $fields['fase_actual'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Fase PIIL'))
            ->setDescription(t('Fase actual del participante en el programa.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'atencion' => t('Atención'),
                'insercion' => t('Inserción'),
                'baja' => t('Baja'),
            ])
            ->setDefaultValue('atencion')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === TRACKING DE HORAS ===

        $fields['horas_orientacion_ind'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Horas Orientación Individual'))
            ->setDescription(t('Horas de orientación individual acumuladas.'))
            ->setDefaultValue(0)
            ->setSetting('precision', 8)
            ->setSetting('scale', 2)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['horas_orientacion_grup'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Horas Orientación Grupal'))
            ->setDescription(t('Horas de orientación grupal acumuladas.'))
            ->setDefaultValue(0)
            ->setSetting('precision', 8)
            ->setSetting('scale', 2)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['horas_formacion'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Horas Formación'))
            ->setDescription(t('Horas de formación acumuladas (LMS + talleres).'))
            ->setDefaultValue(0)
            ->setSetting('precision', 8)
            ->setSetting('scale', 2)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['horas_mentoria_ia'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Horas Mentoría IA'))
            ->setDescription(t('Horas acumuladas con el Tutor IA (Copilot).'))
            ->setDefaultValue(0)
            ->setSetting('precision', 8)
            ->setSetting('scale', 2)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['horas_mentoria_humana'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Horas Mentoría Humana'))
            ->setDescription(t('Horas acumuladas con mentor humano.'))
            ->setDefaultValue(0)
            ->setSetting('precision', 8)
            ->setSetting('scale', 2)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === CARRIL Y PROGRAMA ===

        $fields['carril'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Carril'))
            ->setDescription(t('Carril del programa seleccionado.'))
            ->setSetting('allowed_values', [
                'impulso_digital' => t('Impulso Digital (Empleabilidad)'),
                'acelera_pro' => t('Acelera Pro (Emprendimiento)'),
                'hibrido' => t('Híbrido'),
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === INCENTIVO ECONÓMICO ===

        $fields['incentivo_recibido'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Incentivo €528 Recibido'))
            ->setDescription(t('Indica si el participante ha recibido el incentivo económico.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === INSERCIÓN LABORAL ===

        $fields['tipo_insercion'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Inserción'))
            ->setDescription(t('Tipo de inserción laboral conseguida.'))
            ->setSetting('allowed_values', [
                'cuenta_ajena' => t('Cuenta Ajena'),
                'cuenta_propia' => t('Cuenta Propia'),
                'agrario' => t('Especial Agrario'),
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['fecha_insercion'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha Inserción'))
            ->setDescription(t('Fecha de inserción laboral verificada.'))
            ->setSetting('datetime_type', 'date')
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === SINCRONIZACIÓN STO ===

        $fields['sto_sync_status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado Sincronización STO'))
            ->setDescription(t('Estado de sincronización con el STO.'))
            ->setSetting('allowed_values', [
                'pending' => t('Pendiente'),
                'synced' => t('Sincronizado'),
                'error' => t('Error'),
            ])
            ->setDefaultValue('pending')
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === CAMPOS DE SISTEMA ===

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

}
