<?php

declare(strict_types=1);

namespace Drupal\jaraba_dr\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * ENTIDAD BACKUP VERIFICATION -- Verificacion de integridad de backup.
 *
 * ESTRUCTURA:
 * Content Entity que almacena los resultados de verificacion de
 * integridad de backups. Cada registro compara el checksum esperado
 * con el actual para detectar corrupcion.
 *
 * LOGICA DE NEGOCIO:
 * - Los registros se generan automaticamente por BackupVerifierService.
 * - Son de solo lectura tras su creacion (el ACH restringe update).
 * - Se verifican backups de tipo: database, files, config o full.
 * - La comparacion de checksums detecta corrupcion de datos.
 * - DR es a nivel de plataforma, no multi-tenant.
 *
 * RELACIONES:
 * - Sin relaciones externas. Entidad autocontenida.
 *
 * Spec: Doc 185 s4.3. Plan: FASE 9, Stack Compliance Legal N1.
 *
 * @ContentEntityType(
 *   id = "backup_verification",
 *   label = @Translation("Backup Verification"),
 *   label_collection = @Translation("Backup Verifications"),
 *   label_singular = @Translation("backup verification"),
 *   label_plural = @Translation("backup verifications"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_dr\ListBuilder\BackupVerificationListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_dr\Form\BackupVerificationForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_dr\Access\BackupVerificationAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "backup_verification",
 *   admin_permission = "administer dr",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/backup-verification/{backup_verification}",
 *     "add-form" = "/admin/content/backup-verification/add",
 *     "edit-form" = "/admin/content/backup-verification/{backup_verification}/edit",
 *     "delete-form" = "/admin/content/backup-verification/{backup_verification}/delete",
 *     "collection" = "/admin/content/backup-verifications",
 *   },
 *   field_ui_base_route = "jaraba_dr.backup_verification.settings",
 * )
 */
class BackupVerification extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- TIPO DE BACKUP ---

    $fields['backup_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Tipo de backup'))
      ->setDescription(new TranslatableMarkup('Tipo de backup verificado.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'database' => new TranslatableMarkup('Base de datos'),
        'files' => new TranslatableMarkup('Ficheros'),
        'config' => new TranslatableMarkup('Configuracion'),
        'full' => new TranslatableMarkup('Completo'),
      ])
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- RUTA Y CHECKSUMS ---

    $fields['backup_path'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Ruta del backup'))
      ->setDescription(new TranslatableMarkup('Ruta completa al archivo de backup verificado.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 500)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['checksum_expected'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Checksum esperado'))
      ->setDescription(new TranslatableMarkup('Hash SHA-256 esperado del backup.'))
      ->setSetting('max_length', 128)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['checksum_actual'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Checksum actual'))
      ->setDescription(new TranslatableMarkup('Hash SHA-256 calculado del backup.'))
      ->setSetting('max_length', 128)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- ESTADO ---

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Estado'))
      ->setDescription(new TranslatableMarkup('Resultado de la verificacion del backup.'))
      ->setRequired(TRUE)
      ->setDefaultValue('pending')
      ->setSetting('allowed_values', [
        'pending' => new TranslatableMarkup('Pendiente'),
        'verified' => new TranslatableMarkup('Verificado'),
        'failed' => new TranslatableMarkup('Fallido'),
        'corrupted' => new TranslatableMarkup('Corrupto'),
      ])
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- VERIFICACION ---

    $fields['verified_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Verificado en'))
      ->setDescription(new TranslatableMarkup('Timestamp UTC de la verificacion.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['file_size_bytes'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Tamano del archivo (bytes)'))
      ->setDescription(new TranslatableMarkup('Tamano del archivo de backup en bytes.'))
      ->setSetting('unsigned', TRUE)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['error_message'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Mensaje de error'))
      ->setDescription(new TranslatableMarkup('Detalle del error si la verificacion fallo.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['verification_duration_ms'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Duracion de verificacion (ms)'))
      ->setDescription(new TranslatableMarkup('Tiempo de la verificacion en milisegundos.'))
      ->setSetting('unsigned', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- TIMESTAMPS ---

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Creado'))
      ->setDescription(new TranslatableMarkup('Fecha de creacion del registro.'));

    return $fields;
  }

  /**
   * Comprueba si el backup ha sido verificado correctamente.
   */
  public function isVerified(): bool {
    return $this->get('status')->value === 'verified';
  }

  /**
   * Comprueba si el backup esta corrupto.
   */
  public function isCorrupted(): bool {
    return $this->get('status')->value === 'corrupted';
  }

  /**
   * Comprueba si la verificacion ha fallado.
   */
  public function isFailed(): bool {
    return $this->get('status')->value === 'failed';
  }

  /**
   * Comprueba si los checksums coinciden.
   */
  public function checksumsMatch(): bool {
    $expected = $this->get('checksum_expected')->value;
    $actual = $this->get('checksum_actual')->value;
    if (empty($expected) || empty($actual)) {
      return FALSE;
    }
    return hash_equals($expected, $actual);
  }

  /**
   * Devuelve el tamano del backup en formato legible.
   */
  public function getFormattedFileSize(): string {
    $bytes = (int) $this->get('file_size_bytes')->value;
    if ($bytes <= 0) {
      return '-';
    }
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $index = 0;
    $size = (float) $bytes;
    while ($size >= 1024 && $index < count($units) - 1) {
      $size /= 1024;
      $index++;
    }
    return sprintf('%.2f %s', $size, $units[$index]);
  }

}
