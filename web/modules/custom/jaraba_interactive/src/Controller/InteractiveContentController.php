<?php

declare(strict_types=1);

namespace Drupal\jaraba_interactive\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_interactive\Entity\InteractiveContent;

/**
 * Controlador para operaciones canónicas de contenido interactivo.
 */
class InteractiveContentController extends ControllerBase
{

    /**
     * Obtiene el título del contenido para la vista canónica.
     *
     * @param \Drupal\jaraba_interactive\Entity\InteractiveContent $interactive_content
     *   El contenido interactivo.
     *
     * @return string
     *   El título.
     */
    public function getTitle(InteractiveContent $interactive_content): string
    {
        return $interactive_content->label();
    }

}
