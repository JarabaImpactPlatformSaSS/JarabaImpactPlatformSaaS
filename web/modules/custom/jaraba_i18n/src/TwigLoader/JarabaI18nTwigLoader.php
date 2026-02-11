<?php

declare(strict_types=1);

namespace Drupal\jaraba_i18n\TwigLoader;

use Twig\Loader\FilesystemLoader;

/**
 * Registra el namespace Twig @jaraba_i18n para el módulo.
 *
 * Permite usar:
 *   {% include '@jaraba_i18n/i18n-selector.html.twig' %}
 *
 * desde cualquier otro módulo o tema.
 */
class JarabaI18nTwigLoader extends FilesystemLoader
{

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // Obtener la ruta del módulo.
        $modulePath = \Drupal::service('extension.list.module')->getPath('jaraba_i18n');
        $templatesPath = DRUPAL_ROOT . '/' . $modulePath . '/templates';

        // Registrar el namespace.
        if (is_dir($templatesPath)) {
            $this->addPath($templatesPath, 'jaraba_i18n');
        }
    }

}
