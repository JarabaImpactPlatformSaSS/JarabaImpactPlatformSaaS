<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Acuerdo Kit Digital.
 *
 * Gestiona los Acuerdos de Prestacion de Soluciones de Digitalizacion
 * para el programa Kit Digital (Red.es). Cada acuerdo vincula un tenant
 * beneficiario con un paquete de digitalizacion y un bono digital.
 *
 * @ContentEntityType(
 *   id = "kit_digital_agreement",
 *   label = @Translation("Acuerdo Kit Digital"),
 *   label_collection = @Translation("Acuerdos Kit Digital"),
 *   label_singular = @Translation("acuerdo Kit Digital"),
 *   label_plural = @Translation("acuerdos Kit Digital"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_billing\ListBuilder\KitDigitalAgreementListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_billing\Form\KitDigitalAgreementForm",
 *       "add" = "Drupal\jaraba_billing\Form\KitDigitalAgreementForm",
 *       "edit" = "Drupal\jaraba_billing\Form\KitDigitalAgreementForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_billing\Access\KitDigitalAgreementAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "kit_digital_agreement",
 *   admin_permission = "administer kit digital",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "agreement_number",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/kit-digital-agreement/{kit_digital_agreement}",
 *     "add-form" = "/admin/content/kit-digital-agreement/add",
 *     "edit-form" = "/admin/content/kit-digital-agreement/{kit_digital_agreement}/edit",
 *     "delete-form" = "/admin/content/kit-digital-agreement/{kit_digital_agreement}/delete",
 *     "collection" = "/admin/content/kit-digital-agreements",
 *   },
 *   field_ui_base_route = "jaraba_billing.kit_digital_agreement.settings",
 * )
 */
class KitDigitalAgreement extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * Paquetes Kit Digital disponibles.
   */
  public const PAQUETES = [
    'comercio_digital' => 'Comercio Digital (ComercioConecta)',
    'productor_digital' => 'Productor Digital (AgroConecta)',
    'profesional_digital' => 'Profesional Digital (ServiciosConecta)',
    'despacho_digital' => 'Despacho Digital (JarabaLex)',
    'emprendedor_digital' => 'Emprendedor Digital (Emprendimiento)',
  ];

  /**
   * Segmentos de beneficiarios.
   */
  public const SEGMENTOS = [
    'I' => 'Segmento I (10-49 empleados)',
    'II' => 'Segmento II (3-9 empleados)',
    'III' => 'Segmento III (0-2 empleados)',
    'IV' => 'Segmento IV (50-99 empleados)',
    'V' => 'Segmento V (100-249 empleados)',
  ];

  /**
   * Estados del ciclo de vida del acuerdo.
   */
  public const STATUSES = [
    'draft' => 'Borrador',
    'signed' => 'Firmado',
    'active' => 'Activo',
    'justification_pending' => 'Justificación pendiente',
    'justified' => 'Justificado',
    'paid' => 'Bono cobrado',
    'expired' => 'Expirado',
  ];

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // ENTITY-FK-001: tenant_id como entity_reference.
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Grupo/tenant beneficiario del acuerdo.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['agreement_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Número de acuerdo'))
      ->setDescription(t('Referencia única del acuerdo (formato KD-YYYY-NNNN).'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['beneficiary_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Razón social del beneficiario'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['beneficiary_nif'] = BaseFieldDefinition::create('string')
      ->setLabel(t('NIF/CIF del beneficiario'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['segmento'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Segmento'))
      ->setDescription(t('Segmento del beneficiario según número de empleados.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', self::SEGMENTOS)
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['bono_digital_amount'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Importe del bono (EUR)'))
      ->setDescription(t('Cuantía del bono digital concedido.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['bono_digital_ref'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Referencia del bono digital'))
      ->setDescription(t('Código de referencia del bono en el portal Red.es.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['paquete'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Paquete Kit Digital'))
      ->setDescription(t('Solución de digitalización contratada.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', self::PAQUETES)
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['categorias_kit_digital'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Categorías Kit Digital'))
      ->setDescription(t('Categorías cubiertas (JSON array: C1, C2, C3...).'))
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['plan_tier'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tier del plan'))
      ->setSetting('allowed_values', [
        'starter' => 'Starter',
        'pro' => 'Pro',
        'enterprise' => 'Enterprise',
      ])
      ->setDisplayOptions('form', ['weight' => 9])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['stripe_subscription_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Stripe Subscription ID'))
      ->setDescription(t('ID de la suscripción Stripe vinculada al bono.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['start_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de inicio'))
      ->setDescription(t('Inicio del periodo de prestación del servicio.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['end_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de fin'))
      ->setDescription(t('Fin del periodo (mínimo 12 meses desde inicio).'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', self::STATUSES)
      ->setDefaultValue('draft')
      ->setDisplayOptions('form', ['weight' => 13])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['justification_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de justificación'))
      ->setDescription(t('Fecha en que se presentó la justificación del bono.'))
      ->setDisplayOptions('form', ['weight' => 14])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['justification_memory'] = BaseFieldDefinition::create('file')
      ->setLabel(t('Memoria técnica de actuación'))
      ->setDescription(t('PDF de la Memoria Técnica para justificación ante Red.es.'))
      ->setSetting('file_extensions', 'pdf')
      ->setSetting('file_directory', 'kit-digital/justificaciones')
      ->setDisplayOptions('form', ['weight' => 15])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

  /**
   * Comprueba si el acuerdo está activo.
   */
  public function isActive(): bool {
    return $this->get('status')->value === 'active';
  }

  /**
   * Comprueba si el acuerdo ha expirado.
   */
  public function isExpired(): bool {
    $endDate = $this->get('end_date')->value;
    if (!$endDate) {
      return FALSE;
    }
    return strtotime($endDate) < time();
  }

  /**
   * Comprueba si está pendiente de justificación.
   */
  public function isPendingJustification(): bool {
    return $this->get('status')->value === 'justification_pending';
  }

}
