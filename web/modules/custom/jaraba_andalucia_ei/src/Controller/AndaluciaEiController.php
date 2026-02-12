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
     * Estructura: Renderiza el dashboard con datos del participante y
     * controles de administracion (slide-panel) si el usuario tiene permisos.
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

        // Determinar si el usuario tiene permisos de gestion (AND-001).
        $isAdmin = $user->hasPermission('create programa participante ei')
            || $user->hasPermission('administer andalucia ei');

        return [
            '#theme' => 'andalucia_ei_dashboard',
            '#participante' => $participante,
            '#is_admin' => $isAdmin,
            '#attached' => [
                'library' => [
                    'jaraba_andalucia_ei/dashboard',
                    'ecosistema_jaraba_theme/slide-panel',
                ],
            ],
            '#cache' => [
                'contexts' => ['user'],
                'tags' => ['programa_participante_ei_list'],
            ],
        ];
    }

}
