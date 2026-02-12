<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador principal del módulo Andalucía +ei.
 */
class AndaluciaEiController extends ControllerBase
{

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static();
    }

    /**
     * Dashboard frontend del programa Andalucía +ei.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   Request HTTP.
     *
     * @return array
     *   Render array.
     */
    public function dashboard(Request $request): array
    {
        $user = $this->currentUser();

        // Verificar si el usuario es participante.
        $participante = $this->entityTypeManager()
            ->getStorage('programa_participante_ei')
            ->loadByProperties(['user_id' => $user->id()]);

        $participante = reset($participante) ?: NULL;

        return [
            '#theme' => 'andalucia_ei_dashboard',
            '#participante' => $participante,
            '#attached' => [
                'library' => [
                    'jaraba_andalucia_ei/dashboard',
                ],
            ],
            '#cache' => [
                'contexts' => ['user'],
                'tags' => ['programa_participante_ei_list'],
            ],
        ];
    }

}
