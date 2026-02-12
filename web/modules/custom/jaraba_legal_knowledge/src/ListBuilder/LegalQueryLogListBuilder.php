<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_knowledge\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de registros de consultas legales en admin.
 *
 * ESTRUCTURA: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/legal-query-logs.
 *
 * LOGICA: Muestra columnas clave para inspeccion rapida: texto
 *   de consulta (truncado a 80 caracteres), puntuacion de confianza,
 *   tiempo de respuesta y fecha de creacion.
 *
 * RELACIONES:
 * - LegalQueryLogListBuilder -> LegalQueryLog entity (lista)
 * - LegalQueryLogListBuilder <- AdminHtmlRouteProvider (invocado por)
 */
class LegalQueryLogListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['query_text'] = $this->t('Consulta');
    $header['confidence_score'] = $this->t('Confianza');
    $header['response_time_ms'] = $this->t('Tiempo (ms)');
    $header['created'] = $this->t('Fecha');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $queryText = $entity->get('query_text')->value ?? '';
    $confidenceScore = $entity->get('confidence_score')->value;
    $responseTime = $entity->get('response_time_ms')->value;
    $created = $entity->get('created')->value;

    $row['query_text'] = mb_strlen($queryText) > 80
      ? mb_substr($queryText, 0, 80) . '...'
      : $queryText;
    $row['confidence_score'] = $confidenceScore !== NULL ? number_format((float) $confidenceScore, 2) : '-';
    $row['response_time_ms'] = $responseTime !== NULL ? number_format((int) $responseTime) . ' ms' : '-';
    $row['created'] = $created ? date('Y-m-d H:i:s', (int) $created) : '-';
    return $row + parent::buildRow($entity);
  }

}
