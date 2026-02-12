<?php

declare(strict_types=1);

namespace Drupal\jaraba_interactive\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_interactive\Entity\InteractiveContent;
use Drupal\jaraba_interactive\Plugin\InteractiveTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador de la pagina del editor frontend (zero-region).
 *
 * Estructura: Renderiza el editor visual de contenido interactivo
 * en una pagina sin regiones de Drupal (layout limpio). Carga
 * el sub-editor correspondiente al content_type dinamicamente.
 *
 * Logica: Recibe un InteractiveContent por parametro de ruta,
 * extrae sus datos y los inyecta como drupalSettings para que
 * el JS del editor pueda trabajar con ellos. La pagina usa
 * un template zero-region para maximo espacio de edicion.
 *
 * Sintaxis: Extiende ControllerBase con inyeccion de dependencias.
 */
class EditorController extends ControllerBase
{

    /**
     * El plugin manager de tipos interactivos.
     *
     * @var \Drupal\jaraba_interactive\Plugin\InteractiveTypeManager
     */
    protected InteractiveTypeManager $typeManager;

    /**
     * Constructor.
     *
     * @param \Drupal\jaraba_interactive\Plugin\InteractiveTypeManager $type_manager
     *   El plugin manager de tipos interactivos.
     */
    public function __construct(InteractiveTypeManager $type_manager)
    {
        $this->typeManager = $type_manager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('plugin.manager.interactive_type')
        );
    }

    /**
     * Renderiza el editor de contenido interactivo.
     *
     * @param \Drupal\jaraba_interactive\Entity\InteractiveContent $interactive_content
     *   La entidad de contenido interactivo a editar.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La peticion HTTP actual.
     *
     * @return array
     *   Render array con el editor y sus dependencias JS.
     */
    public function render(InteractiveContent $interactive_content, Request $request): array
    {
        $contentType = $interactive_content->get('content_type')->value ?? 'question_set';
        $contentData = [];

        // Extraer content_data del campo JSON.
        if ($interactive_content->hasField('content_data') && !$interactive_content->get('content_data')->isEmpty()) {
            $contentData = json_decode($interactive_content->get('content_data')->value, TRUE) ?? [];
        }

        $settings = [];
        if ($interactive_content->hasField('settings') && !$interactive_content->get('settings')->isEmpty()) {
            $settings = json_decode($interactive_content->get('settings')->value, TRUE) ?? [];
        }

        // Obtener opciones de tipos disponibles.
        $typeOptions = $this->typeManager->getTypeOptions();

        // Obtener esquema del tipo actual.
        try {
            /** @var \Drupal\jaraba_interactive\Plugin\InteractiveTypeInterface $plugin */
            $plugin = $this->typeManager->createInstance($contentType);
            $schema = $plugin->getSchema();
            $icon = $plugin->getIcon();
        }
        catch (\Exception $e) {
            $schema = [];
            $icon = ['category' => 'ui', 'name' => 'question'];
        }

        return [
            '#theme' => 'interactive_editor',
            '#content' => [
                'id' => $interactive_content->id(),
                'uuid' => $interactive_content->uuid(),
                'title' => $interactive_content->label(),
                'content_type' => $contentType,
                'status' => $interactive_content->isPublished(),
                'difficulty' => $interactive_content->get('difficulty')->value ?? 'intermediate',
            ],
            '#attached' => [
                'library' => [
                    'jaraba_interactive/editor',
                ],
                'drupalSettings' => [
                    'jarabaInteractiveEditor' => [
                        'contentId' => (int) $interactive_content->id(),
                        'contentType' => $contentType,
                        'contentData' => $contentData,
                        'settings' => $settings,
                        'schema' => $schema,
                        'icon' => $icon,
                        'title' => $interactive_content->label(),
                        'status' => $interactive_content->isPublished(),
                        'difficulty' => $interactive_content->get('difficulty')->value ?? 'intermediate',
                        'typeOptions' => $typeOptions,
                        'apiBaseUrl' => '/api/v1/interactive/content',
                        'previewUrl' => '/interactive/play/' . $interactive_content->id(),
                    ],
                ],
            ],
            '#cache' => [
                'contexts' => ['user', 'url.path'],
                'tags' => $interactive_content->getCacheTags(),
            ],
        ];
    }

    /**
     * Obtiene el titulo de la pagina del editor.
     *
     * @param \Drupal\jaraba_interactive\Entity\InteractiveContent $interactive_content
     *   La entidad de contenido interactivo.
     *
     * @return string
     *   El titulo.
     */
    public function getTitle(InteractiveContent $interactive_content): string
    {
        return (string) $this->t('Editar: @title', ['@title' => $interactive_content->label()]);
    }

}
