<?php

declare(strict_types=1);

namespace Drupal\jaraba_i18n\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_i18n\Service\TranslationManagerService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controlador para el dashboard de traducciones.
 *
 * PROPÓSITO:
 * Proporciona una vista general del estado de traducciones de todas las
 * entidades, siguiendo el patrón Operational Tower para dashboards premium.
 *
 * CARACTERÍSTICAS:
 * - Vista panorámica de cobertura de traducciones
 * - Filtrado por tipo de entidad e idioma
 * - Acciones rápidas de traducción con IA
 */
class TranslationDashboardController extends ControllerBase
{

    /**
     * Servicio de gestión de traducciones.
     *
     * @var \Drupal\jaraba_i18n\Service\TranslationManagerService
     */
    protected TranslationManagerService $translationManager;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        $instance = new static();
        $instance->translationManager = $container->get('jaraba_i18n.translation_manager');
        return $instance;
    }

    /**
     * Página principal del dashboard de traducciones.
     *
     * @return array
     *   Render array con el dashboard.
     */
    public function dashboard(): array
    {
        // Tipos de entidad soportados para traducción.
        $entityTypes = [
            'page_content' => $this->t('Páginas'),
            'blog_post' => $this->t('Blog Posts'),
        ];

        // Obtener estadísticas por tipo.
        $stats = [];
        foreach ($entityTypes as $typeId => $label) {
            try {
                $stats[$typeId] = $this->translationManager->getTranslationStats($typeId);
                $stats[$typeId]['label'] = $label;
            } catch (\Exception $e) {
                // Tipo de entidad no existe, ignorar.
                continue;
            }
        }

        // Idiomas disponibles.
        $languages = $this->translationManager->getAvailableLanguages();
        $languageData = [];
        foreach ($languages as $langcode => $language) {
            $languageData[$langcode] = $language->getName();
        }

        return [
            '#theme' => 'i18n_dashboard',
            '#entity_types' => $entityTypes,
            '#stats' => $stats,
            '#languages' => $languageData,
            '#attached' => [
                'library' => ['jaraba_i18n/i18n-dashboard'],
            ],
        ];
    }

}
