<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\jaraba_business_tools\Entity\BusinessModelCanvas;

/**
 * View builder for Business Model Canvas entities.
 *
 * Renders the canvas using the canvas-editor template with all 9 blocks
 * in the Osterwalder visual grid layout.
 */
class BusinessModelCanvasViewBuilder extends EntityViewBuilder
{

    /**
     * {@inheritdoc}
     */
    public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL)
    {
        // For full view mode, render the visual canvas editor.
        if ($view_mode === 'full' || $view_mode === 'default') {
            return $this->buildCanvasEditorView($entity);
        }

        // For other view modes (teaser, etc.), use default rendering.
        return parent::view($entity, $view_mode, $langcode);
    }

    /**
     * Builds the canvas editor view with all 9 blocks.
     *
     * @param \Drupal\Core\Entity\EntityInterface $entity
     *   The Business Model Canvas entity.
     *
     * @return array
     *   Render array for the canvas editor.
     */
    protected function buildCanvasEditorView(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_business_tools\Entity\BusinessModelCanvas $canvas */
        $canvas = $entity;

        /** @var \Drupal\jaraba_business_tools\Service\CanvasService $canvasService */
        $canvasService = \Drupal::service('jaraba_business_tools.canvas_service');

        // Load all blocks for this canvas.
        $blocks = $canvasService->getBlocksForCanvas((int) $canvas->id());

        // Prepare blocks data for template.
        $blocksData = [];
        $blockTypes = [
            'key_partners',
            'key_activities',
            'key_resources',
            'value_propositions',
            'customer_relationships',
            'channels',
            'customer_segments',
            'cost_structure',
            'revenue_streams',
        ];

        foreach ($blockTypes as $type) {
            $block = $blocks[$type] ?? NULL;
            $blocksData[$type] = [
                'items' => $block ? $block->getItems() : [],
                'notes' => $block ? $block->getNotes() : NULL,
                'is_validated' => $block ? $block->isValidated() : FALSE,
                'item_count' => $block ? $block->getItemCount() : 0,
            ];
        }

        // Check if user can edit.
        $currentUser = \Drupal::currentUser();
        $isEditable = $canvas->access('update', $currentUser);

        // Prepare canvas data for template.
        $canvasData = [
            'id' => $canvas->id(),
            'uuid' => $canvas->uuid(),
            'title' => $canvas->getTitle(),
            'sector' => $canvas->getSector(),
            'business_stage' => $canvas->getBusinessStage(),
            'version' => $canvas->getVersion(),
            'completeness_score' => $canvas->getCompletenessScore(),
            'coherence_score' => $canvas->getCoherenceScore(),
            'status' => $canvas->getStatus(),
            'is_template' => $canvas->isTemplate(),
            'ai_suggestions' => [],
        ];

        return [
            '#theme' => 'canvas_editor',
            '#canvas' => $canvasData,
            '#blocks' => $blocksData,
            '#is_editable' => $isEditable,
            '#show_ai_panel' => $canvasData['coherence_score'] !== NULL,
            '#cache' => [
                'tags' => $canvas->getCacheTags(),
                'contexts' => ['user.permissions'],
                'max-age' => 0,
            ],
            '#attached' => [
                'library' => [
                    'jaraba_business_tools/canvas_editor',
                ],
                'drupalSettings' => [
                    'canvasEditor' => [
                        'canvasId' => $canvas->id(),
                        'canvasUuid' => $canvas->uuid(),
                        'apiBase' => '/api/v1/canvas/' . $canvas->id(),
                        'isEditable' => $isEditable,
                        // Canvas metadata for PDF export
                        'canvasTitle' => $canvas->getTitle(),
                        'ownerName' => $canvas->getOwner()->getDisplayName(),
                        'sector' => $canvas->getSector(),
                        'businessStage' => $canvas->getBusinessStage(),
                        'completenessScore' => $canvas->getCompletenessScore(),
                        'version' => $canvas->getVersion(),
                    ],
                ],
            ],
        ];
    }

}
