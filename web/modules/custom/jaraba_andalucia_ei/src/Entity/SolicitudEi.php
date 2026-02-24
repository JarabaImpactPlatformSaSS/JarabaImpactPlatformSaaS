<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad SolicitudEi.
 *
 * Solicitud de participación en el Programa Andalucía +ei.
 * Almacena datos del candidato para triaje y gestión administrativa.
 *
 * @ContentEntityType(
 *   id = "solicitud_ei",
 *   label = @Translation("Solicitud Andalucía +ei"),
 *   label_collection = @Translation("Solicitudes Andalucía +ei"),
 *   label_singular = @Translation("solicitud"),
 *   label_plural = @Translation("solicitudes"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_andalucia_ei\SolicitudEiListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_andalucia_ei\Form\SolicitudEiAdminForm",
 *       "add" = "Drupal\jaraba_andalucia_ei\Form\SolicitudEiAdminForm",
 *       "edit" = "Drupal\jaraba_andalucia_ei\Form\SolicitudEiAdminForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_andalucia_ei\SolicitudEiAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "solicitud_ei",
 *   admin_permission = "administer andalucia ei",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "nombre",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/andalucia-ei/solicitudes/{solicitud_ei}",
 *     "add-form" = "/admin/content/andalucia-ei/solicitudes/add",
 *     "edit-form" = "/admin/content/andalucia-ei/solicitudes/{solicitud_ei}/edit",
 *     "delete-form" = "/admin/content/andalucia-ei/solicitudes/{solicitud_ei}/delete",
 *     "collection" = "/admin/content/andalucia-ei/solicitudes",
 *   },
 *   field_ui_base_route = "entity.solicitud_ei.settings",
 * )
 */
class SolicitudEi extends ContentEntityBase implements SolicitudEiInterface
{

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     */
    public function getNombre(): string
    {
        return (string) $this->get('nombre')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmail(): string
    {
        return (string) $this->get('email')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getTelefono(): string
    {
        return (string) $this->get('telefono')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getProvincia(): string
    {
        return (string) $this->get('provincia')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getEstado(): string
    {
        return (string) $this->get('estado')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function setEstado(string $estado): static
    {
        $this->set('estado', $estado);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getColectivoInferido(): ?string
    {
        return $this->get('colectivo_inferido')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function setColectivoInferido(string $colectivo): static
    {
        $this->set('colectivo_inferido', $colectivo);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function inferirColectivo(): string
    {
        $fecha_nacimiento = $this->get('fecha_nacimiento')->value;
        $situacion = $this->get('situacion_laboral')->value;
        $tiempo_desempleo = $this->get('tiempo_desempleo')->value;

        // Personas migrantes (campo explícito).
        $es_migrante = (bool) $this->get('es_migrante')->value;
        if ($es_migrante) {
            return 'migrantes';
        }

        // Personas perceptoras de prestaciones/subsidio/RAI.
        $percibe_prestacion = (bool) $this->get('percibe_prestacion')->value;
        if ($percibe_prestacion && $situacion === 'desempleado') {
            return 'perceptores_prestaciones';
        }

        // Mayores de 45 años.
        if ($fecha_nacimiento) {
            $birth = new \DateTime($fecha_nacimiento);
            $now = new \DateTime();
            $age = (int) $now->diff($birth)->y;

            if ($age >= 45 && $situacion === 'desempleado') {
                return 'mayores_45';
            }
        }

        // Desempleados de larga duración (>12 meses).
        if ($situacion === 'desempleado' && $tiempo_desempleo === 'mas_12_meses') {
            return 'larga_duracion';
        }

        // No encaja claramente en un colectivo prioritario.
        return 'otros';
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // === DATOS PERSONALES ===

        $fields['nombre'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre completo'))
            ->setDescription(t('Nombre y apellidos del candidato.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -15,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['email'] = BaseFieldDefinition::create('email')
            ->setLabel(t('Correo electrónico'))
            ->setDescription(t('Email de contacto del candidato.'))
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'email_default',
                'weight' => -14,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['telefono'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Teléfono'))
            ->setDescription(t('Número de teléfono de contacto.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 20)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -13,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['fecha_nacimiento'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha de nacimiento'))
            ->setDescription(t('Para determinar el colectivo (Jóvenes 18-29, Mayores 45+).'))
            ->setRequired(TRUE)
            ->setSetting('datetime_type', 'date')
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => -12,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['dni_nie'] = BaseFieldDefinition::create('string')
            ->setLabel(t('DNI/NIE'))
            ->setDescription(t('Documento identificativo (opcional en solicitud).'))
            ->setSetting('max_length', 12)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -11,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === DATOS TERRITORIALES ===

        $fields['provincia'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Provincia'))
            ->setDescription(t('Provincia de residencia (elegibilidad territorial).'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'almeria' => t('Almería'),
                'cadiz' => t('Cádiz'),
                'cordoba' => t('Córdoba'),
                'granada' => t('Granada'),
                'huelva' => t('Huelva'),
                'jaen' => t('Jaén'),
                'malaga' => t('Málaga'),
                'sevilla' => t('Sevilla'),
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['municipio'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Municipio'))
            ->setDescription(t('Municipio de residencia.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 100)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -9,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === DATOS PARA TRIAJE ===

        $fields['situacion_laboral'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Situación laboral actual'))
            ->setDescription(t('Estado laboral del candidato.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'desempleado' => t('Desempleado/a'),
                'empleado' => t('Empleado/a por cuenta ajena'),
                'autonomo' => t('Autónomo/a'),
                'estudiante' => t('Estudiante'),
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['tiempo_desempleo'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tiempo en desempleo'))
            ->setDescription(t('Solo si está desempleado/a. Determina colectivo larga duración.'))
            ->setSetting('allowed_values', [
                'menos_6_meses' => t('Menos de 6 meses'),
                '6_12_meses' => t('Entre 6 y 12 meses'),
                'mas_12_meses' => t('Más de 12 meses'),
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -7,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['nivel_estudios'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Nivel de estudios'))
            ->setDescription(t('Nivel máximo de estudios completado.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'sin_estudios' => t('Sin estudios'),
                'eso' => t('ESO / Graduado Escolar'),
                'bachillerato' => t('Bachillerato'),
                'fp_medio' => t('FP Grado Medio'),
                'fp_superior' => t('FP Grado Superior'),
                'grado' => t('Grado universitario'),
                'master' => t('Máster / Postgrado'),
                'doctorado' => t('Doctorado'),
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -6,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['es_migrante'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Persona migrante'))
            ->setDescription(t('Marcar si el candidato es persona migrante.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => -5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['percibe_prestacion'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Percibe prestación/subsidio/RAI'))
            ->setDescription(t('Marcar si percibe prestación, subsidio por desempleo o Renta Activa de Inserción.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => -4,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['experiencia_sector'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Experiencia profesional'))
            ->setDescription(t('Breve descripción de experiencia laboral relevante.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => -3,
                'settings' => ['rows' => 3],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['motivacion'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Motivación'))
            ->setDescription(t('¿Por qué quieres participar en el programa Andalucía +ei?'))
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => -2,
                'settings' => ['rows' => 4],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === CAMPOS ADMINISTRATIVOS ===

        $fields['estado'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado de la solicitud'))
            ->setDescription(t('Estado actual en el proceso de triaje.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'pendiente' => t('Pendiente'),
                'contactado' => t('Contactado'),
                'admitido' => t('Admitido'),
                'rechazado' => t('Rechazado'),
                'lista_espera' => t('Lista de espera'),
            ])
            ->setDefaultValue('pendiente')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['colectivo_inferido'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Colectivo inferido'))
            ->setDescription(t('Colectivo asignado automáticamente según datos del candidato.'))
            ->setSetting('allowed_values', [
                'larga_duracion' => t('Desempleados larga duración'),
                'mayores_45' => t('Mayores de 45 años'),
                'migrantes' => t('Personas migrantes'),
                'perceptores_prestaciones' => t('Perceptores prestaciones/subsidio/RAI'),
                'otros' => t('Otros'),
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 11,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['notas_admin'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Notas del administrador'))
            ->setDescription(t('Notas internas sobre esta solicitud.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 12,
                'settings' => ['rows' => 3],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['ip_address'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Dirección IP'))
            ->setDescription(t('IP desde la que se envió la solicitud.'))
            ->setSetting('max_length', 45)
            ->setDisplayConfigurable('view', TRUE);

        // === TENANT ISOLATION ===

        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('Tenant al que pertenece esta solicitud.'))
            ->setSetting('target_type', 'tenant')
            ->setRequired(FALSE)
            ->setDisplayConfigurable('view', TRUE);

        // === TIMESTAMPS ===

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Fecha de solicitud'))
            ->setDescription(t('Fecha y hora en que se envió la solicitud.'))
            ->setDisplayConfigurable('view', TRUE);

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Última modificación'))
            ->setDescription(t('Fecha de última actualización.'))
            ->setDisplayConfigurable('view', TRUE);

        // === TRIAJE IA ===

        $fields['ai_score'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Puntuación IA'))
            ->setDescription(t('Puntuación de idoneidad asignada por IA (0-100).'))
            ->setDefaultValue(NULL)
            ->setDisplayConfigurable('view', TRUE);

        $fields['ai_justificacion'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Justificación IA'))
            ->setDescription(t('Análisis textual generado por IA sobre la solicitud.'))
            ->setDisplayConfigurable('view', TRUE);

        $fields['ai_recomendacion'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Recomendación IA'))
            ->setDescription(t('Recomendación automática del sistema de triaje IA.'))
            ->setSetting('allowed_values', [
                'admitir' => t('Admitir'),
                'revisar' => t('Revisar manualmente'),
                'rechazar' => t('Rechazar'),
            ])
            ->setDisplayConfigurable('view', TRUE);

        return $fields;
    }

}
