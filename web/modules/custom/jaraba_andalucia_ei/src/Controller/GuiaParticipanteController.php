<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Lead magnet — Guía del Participante Andalucía +ei.
 *
 * Renderiza una página de descarga de la guía del participante
 * que captura email para enrolamiento en secuencia SEQ_AEI_006.
 */
class GuiaParticipanteController extends ControllerBase {

  /**
   * Renders the guide download page.
   *
   * @return array
   *   Render array.
   */
  public function guia(): array {
    $solicitarUrl = Url::fromRoute('jaraba_andalucia_ei.solicitar')->toString();

    return [
      '#theme' => 'andalucia_ei_guia_participante',
      '#solicitar_url' => $solicitarUrl,
      '#attached' => [
        'library' => [
          'jaraba_andalucia_ei/dashboard',
        ],
      ],
      '#cache' => [
        'contexts' => ['url.path'],
        'tags' => ['config:jaraba_andalucia_ei.settings'],
        'max-age' => 3600,
      ],
    ];
  }

}
