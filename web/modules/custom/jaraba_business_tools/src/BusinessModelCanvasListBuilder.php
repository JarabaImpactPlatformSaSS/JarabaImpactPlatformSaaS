<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a premium listing of Business Model Canvas entities.
 *
 * Renders a card-based grid layout instead of a plain table.
 */
class BusinessModelCanvasListBuilder extends EntityListBuilder
{

    /**
     * The date formatter service.
     *
     * @var \Drupal\Core\Datetime\DateFormatterInterface
     */
    protected $dateFormatter;

    /**
     * {@inheritdoc}
     */
    public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type)
    {
        $instance = parent::createInstance($container, $entity_type);
        $instance->dateFormatter = $container->get('date.formatter');
        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function render(): array
    {
        // Get all entities for the card grid.
        $entities = $this->load();

        // Prepare cards data.
        $cards = [];
        foreach ($entities as $entity) {
            /** @var \Drupal\jaraba_business_tools\Entity\BusinessModelCanvas $entity */
            $cards[] = [
                'id' => $entity->id(),
                'title' => $entity->getTitle(),
                'url' => $entity->toUrl()->toString(),
                'edit_url' => $entity->toUrl('edit-form')->toString(),
                'delete_url' => $entity->toUrl('delete-form')->toString(),
                'sector' => $this->getSectorLabel($entity->getSector()),
                'sector_key' => $entity->getSector(),
                'stage' => $this->getStageLabel($entity->getBusinessStage()),
                'stage_key' => $entity->getBusinessStage(),
                'completeness' => (float) $entity->getCompletenessScore(),
                'coherence' => $entity->getCoherenceScore(),
                'status' => $entity->getStatus(),
                'version' => $entity->getVersion(),
                'is_template' => $entity->isTemplate(),
                'owner_name' => $entity->getOwner()?->getDisplayName() ?? $this->t('Anónimo'),
                'changed' => $this->dateFormatter->format($entity->getChangedTime(), 'short'),
                'created' => $this->dateFormatter->format($entity->get('created')->value, 'short'),
            ];
        }

        // Count statistics.
        $stats = [
            'total' => count($cards),
            'draft' => count(array_filter($cards, fn($c) => $c['status'] === 'draft')),
            'active' => count(array_filter($cards, fn($c) => $c['status'] === 'active')),
            'templates' => count(array_filter($cards, fn($c) => $c['is_template'])),
        ];

        return [
            '#theme' => 'canvas_list_premium',
            '#cards' => $cards,
            '#stats' => $stats,
            '#empty_message' => $this->t('No hay ningún Business Model Canvas creado todavía.'),
            '#add_url' => '/admin/content/business-canvas/add',
            '#cache' => [
                'tags' => $this->entityType->getListCacheTags(),
                'contexts' => $this->entityType->getListCacheContexts(),
            ],
            '#attached' => [
                'library' => [
                    'ecosistema_jaraba_core/global',
                ],
            ],
        ];
    }

    /**
     * Gets the translated sector label.
     */
    protected function getSectorLabel(string $sector): string
    {
        $labels = [
            'comercio' => $this->t('Comercio'),
            'servicios' => $this->t('Servicios'),
            'hosteleria' => $this->t('Hostelería'),
            'agro' => $this->t('Agroalimentario'),
            'tech' => $this->t('Tecnología'),
            'industria' => $this->t('Industria'),
            'otros' => $this->t('Otros'),
        ];
        return (string) ($labels[$sector] ?? ucfirst($sector));
    }

    /**
     * Gets the translated stage label.
     */
    protected function getStageLabel(string $stage): string
    {
        $labels = [
            'idea' => $this->t('Idea'),
            'validacion' => $this->t('Validación'),
            'crecimiento' => $this->t('Crecimiento'),
            'escalado' => $this->t('Escalado'),
        ];
        return (string) ($labels[$stage] ?? ucfirst($stage));
    }

}
