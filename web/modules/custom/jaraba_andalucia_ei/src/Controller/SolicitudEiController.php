<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller para la página pública de solicitud Andalucía +ei.
 */
class SolicitudEiController extends ControllerBase
{

    /**
     * Página pública del formulario de solicitud.
     */
    public function solicitar(): array
    {
        $form = $this->formBuilder()->getForm('Drupal\jaraba_andalucia_ei\Form\SolicitudEiPublicForm');

        return [
            '#theme' => 'solicitud_ei_page',
            '#form' => $form,
            '#attached' => [
                'library' => [
                    'jaraba_andalucia_ei/solicitud-form',
                ],
            ],
            '#cache' => [
                'contexts' => ['url.path'],
                'max-age' => 0,
            ],
        ];
    }

}
