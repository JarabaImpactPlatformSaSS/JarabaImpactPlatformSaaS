<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de alertas legales en admin.
 *
 * ESTRUCTURA: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/legal-alerts.
 *
 * LOGICA: Muestra columnas clave para inspeccion rapida: etiqueta,
 *   tipo de alerta, severidad, estado activo, conteo de disparos
 *   y ultima vez disparada.
 *
 * RELACIONES:
 * - LegalAlertListBuilder -> LegalAlert entity (lista)
 * - LegalAlertListBuilder <- AdminHtmlRouteProvider (invocado por)
 */
class LegalAlertListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Etiqueta');
    $header['alert_type'] = $this->t('Tipo Alerta');
    $header['severity'] = $this->t('Severidad');
    $header['active'] = $this->t('Activo');
    $header['trigger_count'] = $this->t('Disparos');
    $header['last_triggered'] = $this->t('Ultimo Disparo');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $alert_type_labels = [
      'resolution_annulled' => $this->t('Resolucion anulada'),
      'criteria_change' => $this->t('Cambio de criterio'),
      'new_relevant_doctrine' => $this->t('Nueva doctrina relevante'),
      'legislation_modified' => $this->t('Legislacion modificada'),
      'procedural_deadline' => $this->t('Plazo procesal'),
      'tjue_spain_impact' => $this->t('Impacto TJUE en Espana'),
      'tedh_spain' => $this->t('TEDH contra Espana'),
      'edpb_guideline' => $this->t('Directriz EDPB'),
      'transposition_deadline' => $this->t('Plazo de transposicion'),
      'ag_conclusions' => $this->t('Conclusiones Abogado General'),
    ];

    $severity_labels = [
      'low' => $this->t('Baja'),
      'medium' => $this->t('Media'),
      'high' => $this->t('Alta'),
      'critical' => $this->t('Critica'),
    ];

    $alertType = $entity->get('alert_type')->value;
    $severity = $entity->get('severity')->value;
    $active = $entity->get('is_active')->value;
    $lastTriggered = $entity->get('last_triggered')->value;

    $row['label'] = $entity->get('label')->value ?? '-';
    $row['alert_type'] = $alert_type_labels[$alertType] ?? $alertType;
    $row['severity'] = $severity_labels[$severity] ?? $severity;
    $row['active'] = $active ? $this->t('Si') : $this->t('No');
    $row['trigger_count'] = (string) ($entity->get('trigger_count')->value ?? 0);
    $row['last_triggered'] = $lastTriggered ? date('Y-m-d H:i', (int) $lastTriggered) : '-';
    return $row + parent::buildRow($entity);
  }

}
