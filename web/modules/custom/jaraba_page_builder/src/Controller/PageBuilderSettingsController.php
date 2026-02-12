<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador para la configuración del Page Builder en slide-panel.
 *
 * Implementa el patrón AJAX sniffing para servir el formulario de configuración
 * como fragmento HTML cuando se carga en un slide-panel, o como página completa
 * para acceso directo.
 *
 * @see docs/architecture/standards/slide_panel_pattern.md
 */
class PageBuilderSettingsController extends ControllerBase
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
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('form_builder'),
            $container->get('renderer'),
        );
    }

    /**
     * Renderiza el formulario de configuración.
     *
     * Detecta si la petición es AJAX (slide-panel) y devuelve solo el fragmento
     * HTML del formulario. Para acceso directo, renderiza la página completa.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP actual.
     *
     * @return array|\Symfony\Component\HttpFoundation\Response
     *   Render array o Response con el fragmento HTML.
     */
    public function settings(Request $request): array|Response
    {
        // Construir el formulario de configuración.
        $form = $this->formBuilderService->getForm('\Drupal\jaraba_page_builder\Form\PageBuilderSettingsForm');

        // AJAX Sniffing: Si es petición AJAX, devolver solo el fragmento.
        if ($request->isXmlHttpRequest()) {
            // Añadir clases premium al formulario.
            $form['#attributes']['class'][] = 'slide-panel__form';
            $form['#attributes']['class'][] = 'jaraba-premium-form';

            // Usar render() en vez de renderRoot() para formularios con tokens.
            $html = \Drupal::service('renderer')->render($form);

            return new Response((string) $html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        }


        // Acceso directo: renderizar formulario con wrapper básico.
        return [
            '#type' => 'container',
            '#attributes' => ['class' => ['page-builder-settings-wrapper', 'jaraba-premium-form']],
            'form' => $form,
            '#attached' => [
                'library' => [
                    'ecosistema_jaraba_theme/slide-panel',
                ],
            ],
        ];
    }

}


