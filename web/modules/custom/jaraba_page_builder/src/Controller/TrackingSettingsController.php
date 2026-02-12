<?php

namespace Drupal\jaraba_page_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador para la configuración de tracking vía slide-panel.
 *
 * Implementa AJAX sniffing para servir el formulario TrackingSettingsForm
 * como fragmento HTML (slide-panel) o página completa (acceso directo).
 */
class TrackingSettingsController extends ControllerBase
{

    /**
     * El servicio form builder.
     *
     * @var \Drupal\Core\Form\FormBuilderInterface
     */
    protected FormBuilderInterface $formBuilderService;

    /**
     * El servicio renderer.
     *
     * @var \Drupal\Core\Render\RendererInterface
     */
    protected RendererInterface $rendererService;

    /**
     * Constructor.
     *
     * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
     *   El servicio form builder.
     * @param \Drupal\Core\Render\RendererInterface $renderer
     *   El servicio renderer.
     */
    public function __construct(
        FormBuilderInterface $form_builder,
        RendererInterface $renderer,
    ) {
        $this->formBuilderService = $form_builder;
        $this->rendererService = $renderer;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('form_builder'),
            $container->get('renderer'),
        );
    }

    /**
     * Muestra el formulario de configuración de tracking.
     *
     * Detecta si es una petición AJAX (slide-panel) o directa,
     * y sirve el contenido apropiado.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP actual.
     *
     * @return array|\Symfony\Component\HttpFoundation\Response
     *   Render array o Response según el tipo de petición.
     */
    public function settings(Request $request)
    {
        // Construir el formulario de tracking.
        $form = $this->formBuilderService->getForm(
            'Drupal\jaraba_page_builder\Form\TrackingSettingsForm'
        );

        // Añadir clases premium para estilos del slide-panel.
        $form['#attributes']['class'][] = 'slide-panel__form';
        $form['#attributes']['class'][] = 'jaraba-premium-form';

        // AJAX sniffing: detectar si es petición AJAX.
        $isAjax = $request->isXmlHttpRequest()
            || $request->headers->get('X-Requested-With') === 'XMLHttpRequest'
            || $request->query->get('ajax') === '1';

        if ($isAjax) {
            // Renderizar solo el formulario para slide-panel.
            $html = $this->rendererService->render($form);

            return new Response((string) $html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        }

        // Acceso directo: envolver en estructura de página completa.
        return [
            '#theme' => 'container',
            '#children' => $form,
            '#attributes' => [
                'class' => ['jaraba-admin-form-wrapper'],
            ],
        ];
    }

}
