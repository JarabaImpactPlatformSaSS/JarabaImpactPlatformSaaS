<?php

declare(strict_types=1);

namespace Drupal\jaraba_interactive\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Define la anotación InteractiveType.
 *
 * Los plugins de tipo interactivo definen esquemas y comportamientos
 * para diferentes tipos de contenido (quiz, video, escenarios, etc).
 *
 * @Annotation
 */
class InteractiveType extends Plugin
{

    /**
     * El ID del plugin.
     *
     * @var string
     */
    public string $id;

    /**
     * El label traducible del plugin.
     *
     * @var \Drupal\Core\Annotation\Translation
     *
     * @ingroup plugin_translatable
     */
    public $label;

    /**
     * Descripción del tipo de contenido.
     *
     * @var \Drupal\Core\Annotation\Translation
     *
     * @ingroup plugin_translatable
     */
    public $description;

    /**
     * Categoría del contenido (assessment, media, interactive).
     *
     * @var string
     */
    public string $category = 'interactive';

    /**
     * Icono del tipo (categoría/nombre para jaraba_icon).
     *
     * @var string
     */
    public string $icon = 'ui/question';

    /**
     * El peso para ordenamiento.
     *
     * @var int
     */
    public int $weight = 0;

}
