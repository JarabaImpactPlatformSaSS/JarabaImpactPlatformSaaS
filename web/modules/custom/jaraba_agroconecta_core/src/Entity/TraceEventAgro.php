<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad TraceEventAgro.
 *
 * Evento inmutable de la cadena de trazabilidad de un lote.
 * Cada evento se enlaza al anterior via hash encadenado (blockchain-like),
 * garantizando la integridad de la secuencia.
 *
 * TIPOS: siembra, riego, tratamiento, cosecha, procesado, envasado,
 *        almacenado, analisis, certificacion, envio, entregado.
 *
 * @ContentEntityType(
 *   id = "trace_event_agro",
 *   label = @Translation("Evento Trazabilidad Agro"),
 *   label_collection = @Translation("Eventos Trazabilidad Agro"),
 *   label_singular = @Translation("evento de trazabilidad agro"),
 *   label_plural = @Translation("eventos de trazabilidad agro"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_agroconecta_core\Entity\TraceEventAgroListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_agroconecta_core\Form\TraceEventAgroForm",
 *       "add" = "Drupal\jaraba_agroconecta_core\Form\TraceEventAgroForm",
 *       "edit" = "Drupal\jaraba_agroconecta_core\Form\TraceEventAgroForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_agroconecta_core\Entity\TraceEventAgroAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "trace_event_agro",
 *   admin_permission = "administer agroconecta",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.trace_event_agro.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "event_type",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/agro-trace-events/{trace_event_agro}",
 *     "add-form" = "/admin/content/agro-trace-events/add",
 *     "edit-form" = "/admin/content/agro-trace-events/{trace_event_agro}/edit",
 *     "delete-form" = "/admin/content/agro-trace-events/{trace_event_agro}/delete",
 *     "collection" = "/admin/content/agro-trace-events",
 *   },
 * )
 */
class TraceEventAgro extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        $fields['batch_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Lote'))
            ->setSetting('target_type', 'agro_batch')
            ->setRequired(TRUE)
            ->setDisplayOptions('form', ['type' => 'entity_reference_autocomplete', 'weight' => -10])
            ->setDisplayConfigurable('form', TRUE);

        $fields['event_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de evento'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'siembra' => t('Siembra'),
                'riego' => t('Riego'),
                'tratamiento' => t('Tratamiento fitosanitario'),
                'cosecha' => t('Cosecha'),
                'procesado' => t('Procesado'),
                'envasado' => t('Envasado'),
                'almacenado' => t('Almacenamiento'),
                'analisis' => t('Análisis de calidad'),
                'certificacion' => t('Certificación'),
                'envio' => t('Envío'),
                'entregado' => t('Entregado'),
                'custom' => t('Personalizado'),
            ])
            ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => -9])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['description'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Descripción'))
            ->setDescription(t('Detalle del evento (ej: Cosecha manual de aceitunas Picual, parcela 3).'))
            ->setDisplayOptions('form', ['type' => 'string_textarea', 'weight' => -8, 'settings' => ['rows' => 3]])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['location'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Ubicación'))
            ->setDescription(t('GPS o nombre del lugar.'))
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => -7])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['event_timestamp'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha/hora del evento'))
            ->setSetting('datetime_type', 'datetime')
            ->setRequired(TRUE)
            ->setDisplayOptions('form', ['type' => 'datetime_default', 'weight' => -6])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['actor'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Actor responsable'))
            ->setDescription(t('Persona o entidad responsable del evento.'))
            ->setSetting('max_length', 128)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => -5])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['metadata'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Metadatos'))
            ->setDescription(t('JSON con datos específicos del evento (temperatura, humedad, lote analítica, etc).'))
            ->setDisplayOptions('form', ['type' => 'string_textarea', 'weight' => -4, 'settings' => ['rows' => 3]])
            ->setDisplayConfigurable('form', TRUE);

        $fields['evidence_url'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Evidencia'))
            ->setDescription(t('URL a foto, PDF o documento de soporte.'))
            ->setSetting('max_length', 512)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => -3])
            ->setDisplayConfigurable('form', TRUE);

        // Hash encadenado para inmutabilidad.
        $fields['event_hash'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Hash del evento'))
            ->setDescription(t('SHA-256 de los datos del evento.'))
            ->setSetting('max_length', 64)
            ->setDisplayConfigurable('view', TRUE);

        $fields['previous_hash'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Hash anterior'))
            ->setDescription(t('SHA-256 del evento anterior en la cadena.'))
            ->setSetting('max_length', 64)
            ->setDisplayConfigurable('view', TRUE);

        $fields['sequence'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Secuencia'))
            ->setDescription(t('Número de orden en la cadena del lote.'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('view', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')->setLabel(t('Creado'));
        $fields['changed'] = BaseFieldDefinition::create('changed')->setLabel(t('Modificado'));

        return $fields;
    }

    public function getEventType(): string
    {
        return $this->get('event_type')->value ?? '';
    }
    public function getEventHash(): string
    {
        return $this->get('event_hash')->value ?? '';
    }
    public function getPreviousHash(): string
    {
        return $this->get('previous_hash')->value ?? '';
    }
    public function getSequence(): int
    {
        return (int) ($this->get('sequence')->value ?? 0);
    }
}
