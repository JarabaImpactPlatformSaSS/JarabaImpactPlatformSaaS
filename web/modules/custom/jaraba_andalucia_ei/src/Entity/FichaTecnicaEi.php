<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Ficha Técnica PIIL ICV 2025.
 *
 * Documento fundacional que recoge datos de la entidad gestora, sedes,
 * personal técnico y ratio normativo (1 técnico : 60 proyectos).
 * Debe validarse por SSCC del SAE antes de iniciar el programa.
 *
 * Normativa: §3.2 Pautas Gestión Técnica ICV 2025.
 *
 * @ContentEntityType(
 *   id = "ficha_tecnica_ei",
 *   label = @Translation("Ficha Técnica PIIL"),
 *   label_collection = @Translation("Fichas Técnicas PIIL"),
 *   label_singular = @Translation("ficha técnica PIIL"),
 *   label_plural = @Translation("fichas técnicas PIIL"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_andalucia_ei\ListBuilder\FichaTecnicaEiListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_andalucia_ei\Form\FichaTecnicaEiForm",
 *       "add" = "Drupal\jaraba_andalucia_ei\Form\FichaTecnicaEiForm",
 *       "edit" = "Drupal\jaraba_andalucia_ei\Form\FichaTecnicaEiForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_andalucia_ei\Access\FichaTecnicaEiAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "ficha_tecnica_ei",
 *   admin_permission = "administer andalucia ei",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "expediente_ref",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/fichas-tecnicas-ei/{ficha_tecnica_ei}",
 *     "add-form" = "/admin/content/fichas-tecnicas-ei/add",
 *     "edit-form" = "/admin/content/fichas-tecnicas-ei/{ficha_tecnica_ei}/edit",
 *     "delete-form" = "/admin/content/fichas-tecnicas-ei/{ficha_tecnica_ei}/delete",
 *     "collection" = "/admin/content/fichas-tecnicas-ei",
 *   },
 *   field_ui_base_route = "entity.ficha_tecnica_ei.settings",
 * )
 */
class FichaTecnicaEi extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * Estados de validación de la ficha técnica por SSCC del SAE.
   */
  public const ESTADOS_VALIDACION = [
    'borrador' => 'Borrador',
    'enviada' => 'Enviada al SAE',
    'validada' => 'Validada por SSCC',
    'rechazada' => 'Rechazada — pendiente subsanación',
  ];

  /**
   * Provincias del programa PIIL ICV 2025.
   */
  public const PROVINCIAS = [
    'malaga' => 'Málaga',
    'sevilla' => 'Sevilla',
  ];

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // ENTITY-FK-001: tenant_id como entity_reference.
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['expediente_ref'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Referencia expediente'))
      ->setDescription(t('Formato: SC/ICV/NNNN/2025'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 50)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['provincia'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Provincia'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', self::PROVINCIAS)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sede_direccion'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Dirección sede operativa'))
      ->setDescription(t('Dirección donde se realizan las acciones del programa.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sede_operativa'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Sede operativa'))
      ->setDescription(t('Confirma que la sede está operativa durante la ejecución del proyecto.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['representante_nombre'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Representante legal'))
      ->setDescription(t('Nombre completo del representante legal de la entidad.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['representante_nif'] = BaseFieldDefinition::create('string')
      ->setLabel(t('NIF representante'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['coordinador_nombre'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Coordinador/a del programa'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['coordinador_nif'] = BaseFieldDefinition::create('string')
      ->setLabel(t('NIF coordinador/a'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // JSON array: [{nombre, nif, titulacion, provincia, email, telefono}]
    $fields['personal_tecnico'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Personal técnico'))
      ->setDescription(t('JSON con datos del equipo técnico: nombre, NIF, titulación, provincia, contacto.'))
      ->setDisplayOptions('form', ['weight' => 9])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['proyectos_concedidos'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Proyectos concedidos'))
      ->setDescription(t('Número de proyectos PIIL concedidos en esta provincia.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['estado_validacion'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado validación SAE'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', self::ESTADOS_VALIDACION)
      ->setDefaultValue('borrador')
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fecha_envio_sae'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha envío al SAE'))
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fecha_validacion_sae'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha validación SSCC'))
      ->setDisplayOptions('form', ['weight' => 13])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['observaciones'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Observaciones'))
      ->setDescription(t('Notas del SAE sobre subsanaciones o requisitos pendientes.'))
      ->setDisplayOptions('form', ['weight' => 14])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

  /**
   * Calcula el ratio de técnicos requerido (1 por cada 60 proyectos).
   *
   * §3.4 Pautas ICV 2025: "al menos un miembro técnico por cada
   * sesenta proyectos resueltos por provincia".
   */
  public function getRatioRequerido(): int {
    $proyectos = (int) ($this->get('proyectos_concedidos')->value ?? 0);
    return $proyectos > 0 ? (int) ceil($proyectos / 60) : 1;
  }

  /**
   * Cuenta el personal técnico registrado.
   */
  public function getPersonalTecnicoCount(): int {
    $json = $this->get('personal_tecnico')->value;
    if (!$json) {
      return 0;
    }
    $data = json_decode($json, TRUE);
    return is_array($data) ? count($data) : 0;
  }

  /**
   * Comprueba si el ratio de personal técnico se cumple.
   */
  public function cumpleRatio(): bool {
    return $this->getPersonalTecnicoCount() >= $this->getRatioRequerido();
  }

  /**
   * Comprueba si la ficha está validada por el SAE.
   */
  public function isValidada(): bool {
    return $this->get('estado_validacion')->value === 'validada';
  }

}
