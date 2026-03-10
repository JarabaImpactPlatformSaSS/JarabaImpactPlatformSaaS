<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\jaraba_andalucia_ei\Entity\ExpedienteDocumentoInterface;
use Drupal\jaraba_andalucia_ei\Service\FirmaWorkflowService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller para el slide-panel de firma táctil.
 *
 * Sprint 2 — Plan Maestro Andalucía +ei Clase Mundial.
 *
 * SLIDE-PANEL-RENDER-001: Usa renderPlain() para evitar BigPipe placeholders.
 * CONTROLLER-READONLY-001: No usar readonly en propiedades heredadas.
 * ZERO-REGION-001: drupalSettings inyectados via render array.
 */
class FirmaTactilController extends ControllerBase {

  public function __construct(
    protected FirmaWorkflowService $firmaWorkflow,
    protected RendererInterface $renderer,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_andalucia_ei.firma_workflow'),
      $container->get('renderer'),
    );
  }

  /**
   * Renderiza el slide-panel de firma para un ExpedienteDocumento.
   *
   * Si la petición es AJAX (slide-panel), devuelve HTML plano.
   * Si no, devuelve render array normal.
   *
   * @param int $expediente_documento
   *   ID del ExpedienteDocumento.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   El request HTTP.
   *
   * @return array|\Symfony\Component\HttpFoundation\Response
   *   Render array o Response para slide-panel.
   */
  public function firmaPanel(int $expediente_documento, Request $request): array|Response {
    $storage = $this->entityTypeManager()->getStorage('expediente_documento');
    $documento = $storage->load($expediente_documento);

    if (!$documento instanceof ExpedienteDocumentoInterface) {
      if ($this->isSlidePanelRequest($request)) {
        return new Response('<p>' . $this->t('Documento no encontrado.') . '</p>', 404);
      }
      return [
        '#markup' => '<p>' . $this->t('Documento no encontrado.') . '</p>',
      ];
    }

    $estado = $this->firmaWorkflow->getEstadoFirma($expediente_documento);

    $build = [
      '#theme' => 'andalucia_ei_firma_pad',
      '#documento' => [
        'id' => (int) $documento->id(),
        'titulo' => $documento->label() ?? '',
        'categoria' => $documento->getCategoria(),
        'estado_firma' => $estado['estado'],
        'firmado' => $estado['firmado'],
        'firmantes' => $estado['firmantes'],
      ],
      '#attached' => [
        'library' => ['jaraba_andalucia_ei/firma-electronica'],
        'drupalSettings' => [
          'jarabaFirma' => [
            'firmarTactilUrl' => Url::fromRoute('jaraba_andalucia_ei.firma.firmar_tactil')->toString(),
            'firmarAutofirmaUrl' => Url::fromRoute('jaraba_andalucia_ei.firma.firmar_autofirma')->toString(),
            'estadoUrl' => Url::fromRoute('jaraba_andalucia_ei.firma.estado', [
              'expediente_documento' => $expediente_documento,
            ])->toString(),
            'documentoId' => (int) $documento->id(),
            'documentoTitulo' => $documento->label() ?? '',
          ],
        ],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    // SLIDE-PANEL-RENDER-001: renderPlain() para peticiones AJAX.
    if ($this->isSlidePanelRequest($request)) {
      $html = $this->renderer->renderPlain($build);
      return new Response((string) $html, 200, [
        'Content-Type' => 'text/html; charset=UTF-8',
      ]);
    }

    return $build;
  }

  /**
   * Detecta si la petición viene del slide-panel.
   *
   * SLIDE-PANEL-RENDER-001: isXmlHttpRequest() && !_wrapper_format.
   */
  protected function isSlidePanelRequest(Request $request): bool {
    return $request->isXmlHttpRequest() && !$request->query->has('_wrapper_format');
  }

}
