<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_business_tools\Entity\BusinessModelCanvas;
use Drupal\jaraba_business_tools\Entity\CanvasBlock;
use Drupal\jaraba_business_tools\Entity\CanvasVersion;
use Psr\Log\LoggerInterface;

/**
 * Service for managing Business Model Canvas operations.
 */
class CanvasService
{

    /**
     * The entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * The current user.
     */
    protected AccountProxyInterface $currentUser;

    /**
     * The logger.
     */
    protected LoggerInterface $logger;

    /**
     * Constructs a new CanvasService.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        AccountProxyInterface $currentUser,
        $loggerFactory
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->currentUser = $currentUser;
        $this->logger = $loggerFactory->get('jaraba_business_tools');
    }

    /**
     * Creates a new canvas with all 9 blocks.
     */
    public function createCanvas(array $data, ?int $templateId = NULL): BusinessModelCanvas
    {
        $canvas = BusinessModelCanvas::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? NULL,
            'sector' => $data['sector'] ?? 'otros',
            'business_stage' => $data['business_stage'] ?? 'idea',
            'user_id' => $this->currentUser->id(),
            'tenant_id' => $data['tenant_id'] ?? NULL,
            'business_diagnostic_id' => $data['business_diagnostic_id'] ?? NULL,
            'template_source_id' => $templateId,
            'status' => 'draft',
        ]);
        $canvas->save();

        // Create all 9 blocks.
        $blockTypes = CanvasBlock::getBlockTypes();
        foreach (array_keys($blockTypes) as $blockType) {
            $block = CanvasBlock::create([
                'canvas_id' => $canvas->id(),
                'block_type' => $blockType,
                'items' => '[]',
            ]);
            $block->save();
        }

        // If template provided, copy its items.
        if ($templateId) {
            $this->copyFromTemplate($canvas, $templateId);
        }

        $this->logger->info('Created canvas @id for user @uid', [
            '@id' => $canvas->id(),
            '@uid' => $this->currentUser->id(),
        ]);

        return $canvas;
    }

    /**
     * Gets a canvas with all its blocks.
     */
    public function getCanvasWithBlocks(int|string $canvasId): ?array
    {
        $canvasId = (int) $canvasId;
        $storage = $this->entityTypeManager->getStorage('business_model_canvas');
        $canvas = $storage->load($canvasId);

        if (!$canvas) {
            return NULL;
        }

        $blocks = $this->getBlocksForCanvas($canvasId);

        return [
            'canvas' => $canvas,
            'blocks' => $blocks,
        ];
    }

    /**
     * Gets all blocks for a canvas.
     */
    public function getBlocksForCanvas(int|string $canvasId): array
    {
        $canvasId = (int) $canvasId;
        $storage = $this->entityTypeManager->getStorage('canvas_block');
        $blocks = $storage->loadByProperties(['canvas_id' => $canvasId]);

        $result = [];
        foreach ($blocks as $block) {
            $result[$block->getBlockType()] = $block;
        }

        return $result;
    }

    /**
     * Updates items in a specific block.
     */
    public function updateBlockItems(int $canvasId, string $blockType, array $items): ?CanvasBlock
    {
        $storage = $this->entityTypeManager->getStorage('canvas_block');
        $blocks = $storage->loadByProperties([
            'canvas_id' => $canvasId,
            'block_type' => $blockType,
        ]);

        if (empty($blocks)) {
            return NULL;
        }

        $block = reset($blocks);
        $block->setItems($items);
        $block->save();

        // Recalculate completeness score.
        $this->updateCompletenessScore($canvasId);

        return $block;
    }

    /**
     * Adds an item to a block.
     */
    public function addItemToBlock(int|string $canvasId, string $blockType, string $text, string $color = '#FFE082'): ?CanvasBlock
    {
        $canvasId = (int) $canvasId;
        $storage = $this->entityTypeManager->getStorage('canvas_block');
        $blocks = $storage->loadByProperties([
            'canvas_id' => $canvasId,
            'block_type' => $blockType,
        ]);

        if (empty($blocks)) {
            return NULL;
        }

        $block = reset($blocks);
        $block->addItem($text, $color);
        $block->save();

        $this->updateCompletenessScore($canvasId);

        return $block;
    }

    /**
     * Removes an item from a block.
     */
    public function removeItemFromBlock(int|string $canvasId, string $blockType, string $itemId): bool
    {
        $canvasId = (int) $canvasId;
        $storage = $this->entityTypeManager->getStorage('canvas_block');
        $blocks = $storage->loadByProperties([
            'canvas_id' => $canvasId,
            'block_type' => $blockType,
        ]);

        if (empty($blocks)) {
            return FALSE;
        }

        $block = reset($blocks);
        $block->removeItem($itemId);
        $block->save();

        $this->updateCompletenessScore($canvasId);

        return TRUE;
    }

    /**
     * Reorders items in a block.
     */
    public function reorderBlockItems(int $canvasId, string $blockType, array $newOrder): ?CanvasBlock
    {
        $storage = $this->entityTypeManager->getStorage('canvas_block');
        $blocks = $storage->loadByProperties([
            'canvas_id' => $canvasId,
            'block_type' => $blockType,
        ]);

        if (empty($blocks)) {
            return NULL;
        }

        $block = reset($blocks);
        $currentItems = $block->getItems();

        // Build order map: id => new order
        $orderMap = [];
        foreach ($newOrder as $orderItem) {
            $orderMap[$orderItem['id']] = $orderItem['order'];
        }

        // Reorder items
        usort($currentItems, function ($a, $b) use ($orderMap) {
            $orderA = $orderMap[$a['id']] ?? PHP_INT_MAX;
            $orderB = $orderMap[$b['id']] ?? PHP_INT_MAX;
            return $orderA <=> $orderB;
        });

        // Update order values
        foreach ($currentItems as $index => &$item) {
            $item['order'] = $index;
        }

        $block->setItems($currentItems);
        $block->save();

        return $block;
    }

    /**
     * Updates the completeness score of a canvas.
     */
    public function updateCompletenessScore(int|string $canvasId): float
    {
        $canvasId = (int) $canvasId;
        $blocks = $this->getBlocksForCanvas($canvasId);

        $totalItems = 0;
        $filledBlocks = 0;

        foreach ($blocks as $block) {
            $itemCount = $block->getItemCount();
            if ($itemCount > 0) {
                $filledBlocks++;
                $totalItems += $itemCount;
            }
        }

        // Score: 50% for having items in blocks, 50% for minimum items per block.
        $blockScore = ($filledBlocks / 9) * 50;
        $itemScore = min(50, ($totalItems / 18) * 50); // Target: ~2 items per block.

        $score = $blockScore + $itemScore;

        $canvas = $this->entityTypeManager->getStorage('business_model_canvas')->load($canvasId);
        if ($canvas) {
            $canvas->set('completeness_score', $score);
            $canvas->save();
        }

        return $score;
    }

    /**
     * Creates a version snapshot.
     */
    public function createVersion(int|string $canvasId, \Stringable|string|null $summary = NULL): CanvasVersion
    {
        $canvasId = (int) $canvasId;
        // Cast Stringable (TranslatableMarkup) to string
        if ($summary instanceof \Stringable) {
            $summary = (string) $summary;
        }
        $canvasData = $this->getCanvasWithBlocks($canvasId);
        $canvas = $canvasData['canvas'];
        $blocks = $canvasData['blocks'];

        // Build snapshot.
        $snapshot = [];
        foreach ($blocks as $type => $block) {
            $snapshot[$type] = [
                'items' => $block->getItems(),
                'notes' => $block->getNotes(),
            ];
        }

        // Increment canvas version.
        $newVersion = $canvas->getVersion() + 1;
        $canvas->set('version', $newVersion);
        $canvas->save();

        // Create version record.
        $version = CanvasVersion::create([
            'canvas_id' => $canvasId,
            'version_number' => $newVersion,
            'snapshot' => json_encode($snapshot),
            'change_summary' => $summary ?? t('Version @v', ['@v' => $newVersion]),
            'created_by' => $this->currentUser->id(),
        ]);
        $version->save();

        $this->logger->info('Created version @v for canvas @id', [
            '@v' => $newVersion,
            '@id' => $canvasId,
        ]);

        return $version;
    }

    /**
     * Restores a previous version.
     */
    public function restoreVersion(int $canvasId, int $versionNumber): bool
    {
        $storage = $this->entityTypeManager->getStorage('canvas_version');
        $versions = $storage->loadByProperties([
            'canvas_id' => $canvasId,
            'version_number' => $versionNumber,
        ]);

        if (empty($versions)) {
            return FALSE;
        }

        $version = reset($versions);
        $snapshot = $version->getSnapshot();

        // Restore each block.
        foreach ($snapshot as $type => $data) {
            $this->updateBlockItems($canvasId, $type, $data['items']);

            $blocks = $this->entityTypeManager->getStorage('canvas_block')
                ->loadByProperties([
                    'canvas_id' => $canvasId,
                    'block_type' => $type,
                ]);

            if (!empty($blocks)) {
                $block = reset($blocks);
                $block->setNotes($data['notes'] ?? NULL);
                $block->save();
            }
        }

        // Create a new version to mark the restore.
        $this->createVersion($canvasId, t('Restored from version @v', ['@v' => $versionNumber]));

        return TRUE;
    }

    /**
     * Copies content from a template canvas.
     */
    protected function copyFromTemplate(BusinessModelCanvas $canvas, int $templateId): void
    {
        $templateBlocks = $this->getBlocksForCanvas($templateId);
        $canvasBlocks = $this->getBlocksForCanvas($canvas->id());

        foreach ($templateBlocks as $type => $templateBlock) {
            if (isset($canvasBlocks[$type])) {
                $canvasBlocks[$type]->setItems($templateBlock->getItems());
                $canvasBlocks[$type]->setNotes($templateBlock->getNotes());
                $canvasBlocks[$type]->save();
            }
        }

        $this->updateCompletenessScore($canvas->id());
    }

    /**
     * Gets all canvases for the current user.
     */
    public function getUserCanvases(): array
    {
        $storage = $this->entityTypeManager->getStorage('business_model_canvas');
        return $storage->loadByProperties([
            'user_id' => $this->currentUser->id(),
        ]);
    }

    /**
     * Gets all template canvases.
     */
    public function getTemplates(?string $sector = NULL): array
    {
        $storage = $this->entityTypeManager->getStorage('business_model_canvas');
        $query = [
            'is_template' => TRUE,
            'status' => 'active',
        ];

        if ($sector) {
            $query['sector'] = $sector;
        }

        return $storage->loadByProperties($query);
    }

    /**
     * Serializes a canvas to array for API response.
     */
    public function serializeCanvas(BusinessModelCanvas $canvas, bool $includeBlocks = TRUE): array
    {
        $data = [
            'id' => $canvas->id(),
            'uuid' => $canvas->uuid(),
            'title' => $canvas->getTitle(),
            'description' => $canvas->getDescription(),
            'sector' => $canvas->getSector(),
            'business_stage' => $canvas->getBusinessStage(),
            'version' => $canvas->getVersion(),
            'completeness_score' => $canvas->getCompletenessScore(),
            'coherence_score' => $canvas->getCoherenceScore(),
            'status' => $canvas->getStatus(),
            'is_template' => $canvas->isTemplate(),
            'created' => $canvas->get('created')->value,
            'changed' => $canvas->getChangedTime(),
        ];

        if ($includeBlocks) {
            $blocks = $this->getBlocksForCanvas($canvas->id());
            $data['blocks'] = [];
            foreach ($blocks as $type => $block) {
                $data['blocks'][$type] = [
                    'items' => $block->getItems(),
                    'notes' => $block->getNotes(),
                    'ai_suggestions' => $block->getAiSuggestions(),
                    'is_validated' => $block->isValidated(),
                    'item_count' => $block->getItemCount(),
                ];
            }
        }

        return $data;
    }

}
