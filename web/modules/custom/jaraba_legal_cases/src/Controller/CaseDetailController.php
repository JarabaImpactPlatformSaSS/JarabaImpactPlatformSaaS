<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_cases\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller para el detalle de un expediente juridico.
 *
 * Estructura: Controller zero-region para la pagina de detalle.
 *   Las variables se inyectan via hook_preprocess_page().
 *
 * Logica: ZERO-REGION-001 — Retorna render array minimo.
 *   Los datos del expediente, actividades y plazos se inyectan
 *   en jaraba_legal_cases_preprocess_page().
 */
class CaseDetailController extends ControllerBase {

  /**
   * Detalle de un expediente juridico.
   *
   * @param int $client_case
   *   ID del expediente.
   *
   * @return array
   *   Render array minimo (ZERO-REGION-001).
   */
  public function detail(int $client_case): array {
    return [
      '#type' => 'markup',
      '#markup' => '',
    ];
  }

  /**
   * Titulo dinamico para la pagina de detalle.
   *
   * @param int $client_case
   *   ID del expediente.
   *
   * @return string
   *   Titulo con referencia del expediente.
   */
  public function title(int $client_case): string {
    $storage = $this->entityTypeManager()->getStorage('client_case');
    $entity = $storage->load($client_case);
    if ($entity) {
      $case_number = $entity->get('case_number')->value ?? '';
      $title = $entity->get('title')->value ?? '';
      return $case_number . ' — ' . $title;
    }
    return (string) $this->t('Expediente');
  }

}
