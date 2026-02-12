<?php

declare(strict_types=1);

namespace Drupal\jaraba_interactive\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin manager para tipos de contenido interactivo.
 *
 * Gestiona los plugins que definen diferentes tipos de contenido
 * (QuestionSet, InteractiveVideo, BranchingScenario, etc).
 *
 * @see \Drupal\jaraba_interactive\Annotation\InteractiveType
 * @see \Drupal\jaraba_interactive\Plugin\InteractiveTypeInterface
 * @see plugin_api
 */
class InteractiveTypeManager extends DefaultPluginManager
{

    /**
     * Constructor.
     *
     * @param \Traversable $namespaces
     *   Los namespaces de clases PSR-4.
     * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
     *   El backend de caché.
     * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
     *   El module handler.
     */
    public function __construct(
        \Traversable $namespaces,
        CacheBackendInterface $cache_backend,
        ModuleHandlerInterface $module_handler,
    ) {
        parent::__construct(
            'Plugin/InteractiveType',
            $namespaces,
            $module_handler,
            'Drupal\jaraba_interactive\Plugin\InteractiveTypeInterface',
            'Drupal\jaraba_interactive\Annotation\InteractiveType'
        );

        $this->alterInfo('jaraba_interactive_type_info');
        $this->setCacheBackend($cache_backend, 'jaraba_interactive_type_plugins');
    }

    /**
     * Obtiene todos los tipos ordenados por peso.
     *
     * @return array
     *   Array de definiciones de plugins.
     */
    public function getTypeOptions(): array
    {
        $options = [];
        $definitions = $this->getDefinitions();

        // Ordenar por peso.
        uasort($definitions, function ($a, $b) {
            return ($a['weight'] ?? 0) <=> ($b['weight'] ?? 0);
        });

        foreach ($definitions as $id => $definition) {
            $options[$id] = $definition['label'];
        }

        return $options;
    }

    /**
     * Obtiene las opciones agrupadas por categoría.
     *
     * @return array
     *   Array ['category' => ['plugin_id' => 'label']].
     */
    public function getGroupedOptions(): array
    {
        $grouped = [];
        $definitions = $this->getDefinitions();

        foreach ($definitions as $id => $definition) {
            $category = $definition['category'] ?? 'other';
            $grouped[$category][$id] = $definition['label'];
        }

        return $grouped;
    }

}
