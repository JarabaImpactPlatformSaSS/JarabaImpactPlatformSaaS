<?php

declare(strict_types=1);

namespace Drupal\jaraba_diagnostic\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Employability Diagnostic.
 *
 * PROPOSITO:
 * Representa un diagnostico express de empleabilidad con 3 preguntas
 * (LinkedIn, CV ATS, Estrategia) que genera un score 0-10 y un perfil
 * (Invisible, Desconectado, Construccion, Competitivo, Magnetico).
 *
 * ESTRUCTURA:
 * - q_linkedin: Respuesta pregunta LinkedIn (1-5)
 * - q_cv_ats: Respuesta pregunta CV optimizado para ATS (1-5)
 * - q_estrategia: Respuesta pregunta estrategia de busqueda (1-5)
 * - score: Puntuacion calculada 0-10
 * - profile_type: Tipo de perfil (invisible|desconectado|construccion|competitivo|magnetico)
 * - primary_gap: Area principal de mejora
 * - anonymous_token: Token para acceso anonimo a resultados
 * - email_remarketing: Email para remarketing (opcional)
 * - avatar_confirmed: Avatar confirmado por el usuario
 *
 * SPEC: 20260120b S3 - Diagnostico Express de Empleabilidad
 *
 * @ContentEntityType(
 *   id = "employability_diagnostic",
 *   label = @Translation("Diagnostico de Empleabilidad"),
 *   label_collection = @Translation("Diagnosticos de Empleabilidad"),
 *   label_singular = @Translation("diagnostico de empleabilidad"),
 *   label_plural = @Translation("diagnosticos de empleabilidad"),
 *   label_count = @PluralTranslation(
 *     singular = "@count diagnostico",
 *     plural = "@count diagnosticos",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_diagnostic\EmployabilityDiagnosticListBuilder",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_diagnostic\EmployabilityDiagnosticAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "employability_diagnostic",
 *   admin_permission = "administer employability diagnostics",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "id",
 *     "owner" = "user_id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/employability-diagnostics",
 *     "add-form" = "/admin/content/employability-diagnostics/add",
 *     "canonical" = "/admin/content/employability-diagnostic/{employability_diagnostic}",
 *     "edit-form" = "/admin/content/employability-diagnostic/{employability_diagnostic}/edit",
 *     "delete-form" = "/admin/content/employability-diagnostic/{employability_diagnostic}/delete",
 *   },
 *   field_ui_base_route = "entity.employability_diagnostic.settings",
 * )
 */
class EmployabilityDiagnostic extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * Constantes de tipos de perfil.
   */
  const PROFILE_INVISIBLE = 'invisible';
  const PROFILE_DESCONECTADO = 'desconectado';
  const PROFILE_CONSTRUCCION = 'construccion';
  const PROFILE_COMPETITIVO = 'competitivo';
  const PROFILE_MAGNETICO = 'magnetico';

  /**
   * Etiquetas legibles de cada perfil.
   */
  const PROFILE_LABELS = [
    self::PROFILE_INVISIBLE => 'Invisible',
    self::PROFILE_DESCONECTADO => 'Desconectado',
    self::PROFILE_CONSTRUCCION => 'En Construccion',
    self::PROFILE_COMPETITIVO => 'Competitivo',
    self::PROFILE_MAGNETICO => 'Magnetico',
  ];

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // Pregunta 1: Presencia en LinkedIn (1-5).
    $fields['q_linkedin'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('LinkedIn'))
      ->setDescription(t('Valoracion de presencia en LinkedIn (1-5).'))
      ->setRequired(TRUE)
      ->setSetting('min', 1)
      ->setSetting('max', 5)
      ->setDisplayOptions('view', ['type' => 'number_integer'])
      ->setDisplayOptions('form', ['type' => 'number']);

    // Pregunta 2: CV optimizado para ATS (1-5).
    $fields['q_cv_ats'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('CV ATS'))
      ->setDescription(t('Valoracion de CV optimizado para ATS (1-5).'))
      ->setRequired(TRUE)
      ->setSetting('min', 1)
      ->setSetting('max', 5)
      ->setDisplayOptions('view', ['type' => 'number_integer'])
      ->setDisplayOptions('form', ['type' => 'number']);

    // Pregunta 3: Estrategia de busqueda (1-5).
    $fields['q_estrategia'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Estrategia'))
      ->setDescription(t('Valoracion de estrategia de busqueda de empleo (1-5).'))
      ->setRequired(TRUE)
      ->setSetting('min', 1)
      ->setSetting('max', 5)
      ->setDisplayOptions('view', ['type' => 'number_integer'])
      ->setDisplayOptions('form', ['type' => 'number']);

    // Score calculado (0-10).
    $fields['score'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Score'))
      ->setDescription(t('Puntuacion calculada 0-10.'))
      ->setSetting('precision', 4)
      ->setSetting('scale', 2)
      ->setDisplayOptions('view', ['type' => 'number_decimal']);

    // Tipo de perfil detectado.
    $fields['profile_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Perfil'))
      ->setDescription(t('Tipo de perfil de empleabilidad.'))
      ->setSetting('max_length', 32)
      ->setDisplayOptions('view', ['type' => 'string']);

    // Gap principal identificado.
    $fields['primary_gap'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Gap principal'))
      ->setDescription(t('Area principal de mejora detectada.'))
      ->setSetting('max_length', 64)
      ->setDisplayOptions('view', ['type' => 'string']);

    // Token anonimo para acceso a resultados.
    $fields['anonymous_token'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Token anonimo'))
      ->setDescription(t('Token para acceso anonimo a resultados.'))
      ->setSetting('max_length', 64);

    // Email para remarketing (opcional).
    $fields['email_remarketing'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email remarketing'))
      ->setDescription(t('Email para comunicaciones de seguimiento.'));

    // Avatar confirmado por el usuario.
    $fields['avatar_confirmed'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Avatar confirmado'))
      ->setDescription(t('Avatar seleccionado o confirmado por el usuario.'))
      ->setSetting('max_length', 32);

    // Timestamps.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

  /**
   * Obtiene el score calculado.
   */
  public function getScore(): float {
    return (float) ($this->get('score')->value ?? 0);
  }

  /**
   * Obtiene el tipo de perfil.
   */
  public function getProfileType(): string {
    return $this->get('profile_type')->value ?? self::PROFILE_INVISIBLE;
  }

  /**
   * Obtiene la etiqueta legible del perfil.
   */
  public function getProfileLabel(): string {
    return self::PROFILE_LABELS[$this->getProfileType()] ?? 'Desconocido';
  }

  /**
   * Obtiene el gap principal.
   */
  public function getPrimaryGap(): string {
    return $this->get('primary_gap')->value ?? '';
  }

}
