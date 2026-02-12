<?php

namespace Drupal\jaraba_page_builder\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Servicio para obtener datos de la homepage.
 *
 * PROPÓSITO:
 * Proporciona acceso a los datos configurados en HomepageContent
 * para ser consumidos por los templates Twig de la homepage.
 */
class HomepageDataService
{

    /**
     * El entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Constructor.
     */
    public function __construct(EntityTypeManagerInterface $entity_type_manager)
    {
        $this->entityTypeManager = $entity_type_manager;
    }

    /**
     * Obtiene los datos de la homepage.
     *
     * @param int|null $tenant_id
     *   ID del tenant (opcional, por defecto carga la primera configuración).
     *
     * @return array
     *   Array con datos estructurados para los templates.
     */
    public function getHomepageData(?int $tenant_id = NULL): array
    {
        $storage = $this->entityTypeManager->getStorage('homepage_content');

        // Buscar configuración de homepage.
        $query = $storage->getQuery()
            ->accessCheck(TRUE)
            ->range(0, 1);

        if ($tenant_id) {
            $query->condition('tenant_id', $tenant_id);
        }

        $ids = $query->execute();

        if (empty($ids)) {
            return $this->getDefaultData();
        }

        $homepage = $storage->load(reset($ids));
        if (!$homepage) {
            return $this->getDefaultData();
        }

        /** @var \Drupal\jaraba_page_builder\Entity\HomepageContent $homepage */
        return [
            'hero' => $homepage->getHeroData(),
            'features' => $this->formatFeatures($homepage->getFeatures()),
            'stats' => $this->formatStats($homepage->getStats()),
            'intentions' => $this->formatIntentions($homepage->getIntentions()),
            'seo' => $homepage->getSeoData(),
        ];
    }

    /**
     * Formatea las features para el template.
     *
     * @param \Drupal\jaraba_page_builder\Entity\FeatureCard[] $features
     *   Array de entidades FeatureCard.
     *
     * @return array
     *   Datos formateados para Twig.
     */
    protected function formatFeatures(array $features): array
    {
        $result = [];
        foreach ($features as $feature) {
            $result[] = [
                'title' => $feature->getTitle(),
                'description' => $feature->getDescription(),
                'badge' => $feature->getBadge(),
                'icon' => $feature->getIcon(),
            ];
        }
        return $result;
    }

    /**
     * Formatea las stats para el template.
     *
     * @param \Drupal\jaraba_page_builder\Entity\StatItem[] $stats
     *   Array de entidades StatItem.
     *
     * @return array
     *   Datos formateados para Twig.
     */
    protected function formatStats(array $stats): array
    {
        $result = [];
        foreach ($stats as $stat) {
            $result[] = [
                'value' => $stat->getValue(),
                'suffix' => $stat->getSuffix(),
                'label' => $stat->getLabel(),
            ];
        }
        return $result;
    }

    /**
     * Formatea las intentions para el template.
     *
     * @param \Drupal\jaraba_page_builder\Entity\IntentionCard[] $intentions
     *   Array de entidades IntentionCard.
     *
     * @return array
     *   Datos formateados para Twig.
     */
    protected function formatIntentions(array $intentions): array
    {
        $result = [];
        foreach ($intentions as $intention) {
            $result[] = [
                'title' => $intention->getTitle(),
                'description' => $intention->getDescription(),
                'icon' => $intention->getIcon(),
                'url' => $intention->getUrl(),
                'color_class' => $intention->getColorClass(),
            ];
        }
        return $result;
    }

    /**
     * Obtiene datos por defecto si no hay configuración.
     *
     * NOTA: Devuelve arrays vacíos para que los templates usen sus
     * propios fallbacks hardcodeados con SVGs inline.
     *
     * @return array
     *   Arrays vacíos - los templates tienen sus propios fallbacks.
     */
    public function getDefaultData(): array
    {
        return [
            'hero' => [],
            'features' => [],
            'stats' => [],
            'intentions' => [],
            'seo' => [],
        ];
    }

}
