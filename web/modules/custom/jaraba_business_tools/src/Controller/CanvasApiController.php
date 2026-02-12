<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_business_tools\Service\CanvasService;
use Drupal\jaraba_business_tools\Service\CanvasAiService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Controller for Business Model Canvas.
 */
class CanvasApiController extends ControllerBase
{

    /**
     * The canvas service.
     */
    protected CanvasService $canvasService;

    /**
     * The canvas AI service.
     */
    protected CanvasAiService $aiService;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        $instance = new static();
        $instance->canvasService = $container->get('jaraba_business_tools.canvas_service');
        $instance->aiService = $container->get('jaraba_business_tools.canvas_ai_service');
        return $instance;
    }

    /**
     * GET /api/v1/canvas - List user's canvases.
     */
    public function list(): JsonResponse
    {
        $canvases = $this->canvasService->getUserCanvases();

        $data = [];
        foreach ($canvases as $canvas) {
            $data[] = $this->canvasService->serializeCanvas($canvas, FALSE);
        }

        return new JsonResponse([
            'data' => $data,
            'count' => count($data),
        ]);
    }

    /**
     * GET /api/v1/canvas/{id} - Get canvas with blocks.
     */
    public function get(int $id): JsonResponse
    {
        $canvasData = $this->canvasService->getCanvasWithBlocks($id);

        if (!$canvasData) {
            return new JsonResponse(['error' => 'Canvas not found'], 404);
        }

        // Check access.
        $canvas = $canvasData['canvas'];
        if (!$this->checkAccess($canvas)) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        return new JsonResponse([
            'data' => $this->canvasService->serializeCanvas($canvas, TRUE),
        ]);
    }

    /**
     * POST /api/v1/canvas - Create new canvas.
     */
    public function createCanvas(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['title'])) {
            return new JsonResponse(['error' => 'Title is required'], 400);
        }

        try {
            $templateId = $data['template_id'] ?? NULL;
            $canvas = $this->canvasService->createCanvas($data, $templateId);

            return new JsonResponse([
                'data' => $this->canvasService->serializeCanvas($canvas, TRUE),
                'message' => 'Canvas created successfully',
            ], 201);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/v1/canvas/{id}/clone - Clone a canvas (typically a template).
     */
    public function cloneCanvas(int $id, Request $request): JsonResponse
    {
        try {
            $sourceCanvas = $this->entityTypeManager()->getStorage('business_model_canvas')->load($id);

            if (!$sourceCanvas) {
                return new JsonResponse(['error' => 'Canvas not found'], 404);
            }

            // Templates are clonable by anyone, own canvases by owner
            if (!$sourceCanvas->isTemplate() && !$this->checkAccess($sourceCanvas, 'view')) {
                return new JsonResponse(['error' => 'Access denied'], 403);
            }

            $data = json_decode($request->getContent(), TRUE) ?? [];
            $newTitle = $data['title'] ?? $this->t('Copia de @title', ['@title' => $sourceCanvas->getTitle()]);

            // Create new canvas with template_source_id to prevent hook auto-creating blocks
            $canvasStorage = $this->entityTypeManager()->getStorage('business_model_canvas');
            $newCanvas = $canvasStorage->create([
                'title' => $newTitle,
                'description' => $sourceCanvas->get('description')->value,
                'sector' => $sourceCanvas->get('sector')->value,
                'business_stage' => $sourceCanvas->get('business_stage')->value,
                'user_id' => $this->currentUser()->id(),
                'is_template' => FALSE,
                'template_source_id' => $id, // Prevents hook from auto-creating blocks
                'status' => 'draft',
                'version' => 1,
            ]);
            $newCanvas->save();

            // Clone blocks
            $blockStorage = $this->entityTypeManager()->getStorage('canvas_block');
            $sourceBlocks = $blockStorage->loadByProperties(['canvas_id' => $id]);

            foreach ($sourceBlocks as $sourceBlock) {
                $newBlock = $blockStorage->create([
                    'canvas_id' => $newCanvas->id(),
                    'block_type' => $sourceBlock->getBlockType(),
                    'items' => $sourceBlock->get('items')->value,
                    'notes' => $sourceBlock->get('notes')->value ?? '[]',
                ]);
                $newBlock->save();
            }

            // Update completeness score
            $this->canvasService->updateCompletenessScore($newCanvas->id());

            \Drupal::logger('jaraba_business_tools')->info('Cloned canvas @source to @new for user @user', [
                '@source' => $id,
                '@new' => $newCanvas->id(),
                '@user' => $this->currentUser()->id(),
            ]);

            return new JsonResponse([
                'data' => [
                    'id' => $newCanvas->id(),
                    'title' => $newCanvas->getTitle(),
                    'url' => '/admin/content/business-canvas/' . $newCanvas->id(),
                ],
                'message' => $this->t('Canvas creado desde plantilla'),
            ], 201);

        } catch (\Throwable $e) {
            \Drupal::logger('jaraba_business_tools')->error('Clone canvas error: @msg', [
                '@msg' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/v1/canvas/generate - Generate a full canvas using AI.
     */
    public function generateCanvas(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), TRUE) ?? [];
            $description = $data['description'] ?? '';
            $sector = $data['sector'] ?? 'general';

            if (empty($description)) {
                return new JsonResponse(['error' => 'Description is required'], 400);
            }

            // Generate canvas content using AI
            $generatedData = $this->aiService->generateFullCanvas($description, $sector);

            // Create the canvas entity
            $canvasStorage = $this->entityTypeManager()->getStorage('business_model_canvas');
            $canvas = $canvasStorage->create([
                'title' => $generatedData['title'] ?? $this->t('Mi Nuevo Canvas'),
                'description' => $description,
                'sector' => $sector,
                'business_stage' => 'idea',
                'user_id' => $this->currentUser()->id(),
                'is_template' => FALSE,
                'status' => 'draft',
                'version' => 1,
            ]);
            $canvas->save();

            // Create blocks with generated content
            $blockStorage = $this->entityTypeManager()->getStorage('canvas_block');
            $blocks = $generatedData['blocks'] ?? [];

            foreach ($blocks as $blockType => $items) {
                // Format items as expected by the block entity
                $formattedItems = [];
                foreach ($items as $index => $text) {
                    $formattedItems[] = [
                        'id' => uniqid(),
                        'text' => $text,
                        'order' => $index,
                    ];
                }

                $block = $blockStorage->create([
                    'canvas_id' => $canvas->id(),
                    'block_type' => $blockType,
                    'items' => json_encode($formattedItems),
                    'notes' => '[]',
                ]);
                $block->save();
            }

            // Update completeness score
            $this->canvasService->updateCompletenessScore($canvas->id());

            \Drupal::logger('jaraba_business_tools')->info('Generated canvas @id for user @user', [
                '@id' => $canvas->id(),
                '@user' => $this->currentUser()->id(),
            ]);

            return new JsonResponse([
                'success' => TRUE,
                'data' => [
                    'canvas_id' => $canvas->id(),
                    'title' => $canvas->getTitle(),
                    'blocks' => $blocks,
                    'url' => '/admin/content/business-canvas/' . $canvas->id(),
                    'fallback' => $generatedData['fallback'] ?? FALSE,
                ],
                'message' => $this->t('Canvas generado con éxito'),
            ], 201);

        } catch (\Throwable $e) {
            \Drupal::logger('jaraba_business_tools')->error('Generate canvas error: @msg', [
                '@msg' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * PATCH /api/v1/canvas/{id} - Update canvas metadata.
     */
    public function update(int $id, Request $request): JsonResponse
    {
        $storage = $this->entityTypeManager()->getStorage('business_model_canvas');
        $canvas = $storage->load($id);

        if (!$canvas) {
            return new JsonResponse(['error' => 'Canvas not found'], 404);
        }

        if (!$this->checkAccess($canvas, 'edit')) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), TRUE);

        $allowedFields = ['title', 'description', 'sector', 'business_stage', 'status'];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $canvas->set($field, $data[$field]);
            }
        }

        $canvas->save();

        return new JsonResponse([
            'data' => $this->canvasService->serializeCanvas($canvas, FALSE),
            'message' => 'Canvas updated',
        ]);
    }

    /**
     * DELETE /api/v1/canvas/{id} - Delete canvas (soft).
     */
    public function delete(int $id): JsonResponse
    {
        $storage = $this->entityTypeManager()->getStorage('business_model_canvas');
        $canvas = $storage->load($id);

        if (!$canvas) {
            return new JsonResponse(['error' => 'Canvas not found'], 404);
        }

        if (!$this->checkAccess($canvas, 'delete')) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        // Soft delete.
        $canvas->setStatus('archived');
        $canvas->save();

        return new JsonResponse(['message' => 'Canvas archived']);
    }

    /**
     * PATCH /api/v1/canvas/{id}/status - Update canvas status.
     */
    public function updateStatus(int $id, Request $request): JsonResponse
    {
        $storage = $this->entityTypeManager()->getStorage('business_model_canvas');
        $canvas = $storage->load($id);

        if (!$canvas) {
            return new JsonResponse(['error' => 'Canvas not found'], 404);
        }

        if (!$this->checkAccess($canvas, 'edit')) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), TRUE);
        $status = $data['status'] ?? NULL;

        // Validate status
        $validStatuses = ['draft', 'active', 'archived'];
        if (!$status || !in_array($status, $validStatuses, TRUE)) {
            return new JsonResponse(['error' => $this->t('Invalid status. Must be: draft, active, or archived')], 400);
        }

        $canvas->setStatus($status);
        $canvas->save();

        return new JsonResponse([
            'data' => ['status' => $status],
            'message' => $this->t('Status updated'),
        ]);
    }

    /**
     * GET /api/v1/canvas/{id}/blocks - Get all blocks.
     */
    public function getBlocks(int $id): JsonResponse
    {
        $canvasData = $this->canvasService->getCanvasWithBlocks($id);

        if (!$canvasData || !$this->checkAccess($canvasData['canvas'])) {
            return new JsonResponse(['error' => 'Canvas not found or access denied'], 404);
        }

        $blocks = [];
        foreach ($canvasData['blocks'] as $type => $block) {
            $blocks[$type] = [
                'items' => $block->getItems(),
                'notes' => $block->getNotes(),
                'is_validated' => $block->isValidated(),
            ];
        }

        return new JsonResponse(['data' => $blocks]);
    }

    /**
     * PATCH /api/v1/canvas/{id}/blocks/{type} - Update block items.
     */
    public function updateBlock(int $id, string $type, Request $request): JsonResponse
    {
        $canvasData = $this->canvasService->getCanvasWithBlocks($id);

        if (!$canvasData || !$this->checkAccess($canvasData['canvas'], 'edit')) {
            return new JsonResponse(['error' => 'Canvas not found or access denied'], 404);
        }

        $data = json_decode($request->getContent(), TRUE);

        // Handle reorder operation
        if (isset($data['reorder']) && is_array($data['reorder'])) {
            $block = $this->canvasService->reorderBlockItems($id, $type, $data['reorder']);
            if (!$block) {
                return new JsonResponse(['error' => 'Block not found'], 404);
            }
            return new JsonResponse([
                'data' => [
                    'items' => $block->getItems(),
                    'item_count' => $block->getItemCount(),
                ],
                'message' => $this->t('Orden actualizado'),
            ]);
        }

        // Handle items update
        if (!isset($data['items']) || !is_array($data['items'])) {
            return new JsonResponse(['error' => 'Items array or reorder array required'], 400);
        }

        $block = $this->canvasService->updateBlockItems($id, $type, $data['items']);

        if (!$block) {
            return new JsonResponse(['error' => 'Block not found'], 404);
        }

        return new JsonResponse([
            'data' => [
                'items' => $block->getItems(),
                'item_count' => $block->getItemCount(),
            ],
            'completeness_score' => $canvasData['canvas']->getCompletenessScore(),
        ]);
    }

    /**
     * POST /api/v1/canvas/{id}/blocks/{type}/items - Add item to block.
     */
    public function addItem(int $id, string $type, Request $request): JsonResponse
    {
        try {
            \Drupal::logger('canvas_api')->debug('addItem START: canvas=@id, type=@type', [
                '@id' => $id,
                '@type' => $type,
            ]);

            $canvasData = $this->canvasService->getCanvasWithBlocks($id);

            if (!$canvasData || !$this->checkAccess($canvasData['canvas'], 'edit')) {
                return new JsonResponse(['error' => 'Access denied'], 403);
            }

            $data = json_decode($request->getContent(), TRUE);

            if (empty($data['text'])) {
                return new JsonResponse(['error' => 'Text is required'], 400);
            }

            \Drupal::logger('canvas_api')->debug('addItem: calling addItemToBlock');

            $block = $this->canvasService->addItemToBlock(
                $id,
                $type,
                $data['text'],
                $data['color'] ?? '#FFE082'
            );

            if (!$block) {
                return new JsonResponse(['error' => 'Block not found'], 404);
            }

            \Drupal::logger('canvas_api')->debug('addItem SUCCESS');

            return new JsonResponse([
                'data' => ['items' => $block->getItems()],
                'message' => 'Item added',
            ], 201);
        } catch (\Throwable $e) {
            \Drupal::logger('canvas_api')->error('addItem ERROR: @msg at @file:@line', [
                '@msg' => $e->getMessage(),
                '@file' => $e->getFile(),
                '@line' => $e->getLine(),
            ]);
            return new JsonResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/v1/canvas/{id}/blocks/{type}/items/{itemId} - Remove item.
     */
    public function removeItem(int $id, string $type, string $itemId): JsonResponse
    {
        try {
            \Drupal::logger('canvas_api')->debug('removeItem START: canvas=@id, type=@type, item=@itemId', [
                '@id' => $id,
                '@type' => $type,
                '@itemId' => $itemId,
            ]);

            $canvasData = $this->canvasService->getCanvasWithBlocks($id);

            if (!$canvasData || !$this->checkAccess($canvasData['canvas'], 'edit')) {
                return new JsonResponse(['error' => 'Access denied'], 403);
            }

            \Drupal::logger('canvas_api')->debug('removeItem: calling removeItemFromBlock');

            $success = $this->canvasService->removeItemFromBlock($id, $type, $itemId);

            if (!$success) {
                return new JsonResponse(['error' => 'Item not found'], 404);
            }

            \Drupal::logger('canvas_api')->debug('removeItem SUCCESS');

            return new JsonResponse(['message' => 'Item removed']);
        } catch (\Throwable $e) {
            \Drupal::logger('canvas_api')->error('removeItem ERROR: @msg at @file:@line', [
                '@msg' => $e->getMessage(),
                '@file' => $e->getFile(),
                '@line' => $e->getLine(),
            ]);
            return new JsonResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * PATCH /api/v1/canvas/{id}/blocks/{type}/items/{itemId} - Update item.
     */
    public function updateItem(int $id, string $type, string $itemId, Request $request): JsonResponse
    {
        try {
            $canvasData = $this->canvasService->getCanvasWithBlocks($id);

            if (!$canvasData || !$this->checkAccess($canvasData['canvas'], 'edit')) {
                return new JsonResponse(['error' => 'Access denied'], 403);
            }

            $data = json_decode($request->getContent(), TRUE);

            if (empty($data)) {
                return new JsonResponse(['error' => 'No update data provided'], 400);
            }

            // Get the block
            $storage = $this->entityTypeManager()->getStorage('canvas_block');
            $blocks = $storage->loadByProperties([
                'canvas_id' => $id,
                'block_type' => $type,
            ]);

            if (empty($blocks)) {
                return new JsonResponse(['error' => 'Block not found'], 404);
            }

            $block = reset($blocks);

            // Build updates array (only allow safe fields)
            $updates = [];
            if (isset($data['text'])) {
                $updates['text'] = $data['text'];
            }
            if (isset($data['color'])) {
                $updates['color'] = $data['color'];
            }
            if (isset($data['priority'])) {
                $updates['priority'] = (int) $data['priority'];
            }
            if (isset($data['validated'])) {
                $updates['validated'] = (bool) $data['validated'];
            }

            if (empty($updates)) {
                return new JsonResponse(['error' => 'No valid fields to update'], 400);
            }

            $block->updateItem($itemId, $updates);
            $block->save();

            return new JsonResponse([
                'data' => ['items' => $block->getItems()],
                'message' => 'Item updated',
            ]);

        } catch (\Throwable $e) {
            \Drupal::logger('canvas_api')->error('updateItem ERROR: @msg', [
                '@msg' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/v1/canvas/{id}/analyze - Request AI analysis.
     */
    public function analyze(int $id): JsonResponse
    {
        $canvasData = $this->canvasService->getCanvasWithBlocks($id);

        if (!$canvasData || !$this->checkAccess($canvasData['canvas'])) {
            return new JsonResponse(['error' => 'Canvas not found'], 404);
        }

        // Prepare data for AI.
        $serialized = $this->canvasService->serializeCanvas($canvasData['canvas'], TRUE);

        $analysis = $this->aiService->analyzeCanvas([
            'canvas' => $serialized,
            'blocks' => $serialized['blocks'],
        ]);

        // Update coherence score.
        if (isset($analysis['coherence_score'])) {
            $canvasData['canvas']->setCoherenceScore($analysis['coherence_score']);
            $canvasData['canvas']->save();
        }

        // Store suggestions in blocks.
        if (!empty($analysis['suggestions'])) {
            foreach ($analysis['suggestions'] as $type => $suggestions) {
                if (isset($canvasData['blocks'][$type])) {
                    $canvasData['blocks'][$type]->setAiSuggestions($suggestions);
                    $canvasData['blocks'][$type]->save();
                }
            }
        }

        return new JsonResponse([
            'data' => $analysis,
            'message' => 'Analysis complete',
        ]);
    }

    /**
     * GET /api/v1/canvas/{id}/suggestions - Get AI suggestions.
     */
    public function getSuggestions(int $id): JsonResponse
    {
        $canvasData = $this->canvasService->getCanvasWithBlocks($id);

        if (!$canvasData || !$this->checkAccess($canvasData['canvas'])) {
            return new JsonResponse(['error' => 'Canvas not found'], 404);
        }

        $suggestions = [];
        foreach ($canvasData['blocks'] as $type => $block) {
            $blockSuggestions = $block->getAiSuggestions();
            if (!empty($blockSuggestions)) {
                $suggestions[$type] = $blockSuggestions;
            }
        }

        return new JsonResponse(['data' => $suggestions]);
    }

    /**
     * GET /api/v1/canvas/{id}/versions - List versions.
     */
    public function listVersions(int $id): JsonResponse
    {
        $canvasData = $this->canvasService->getCanvasWithBlocks($id);

        if (!$canvasData || !$this->checkAccess($canvasData['canvas'])) {
            return new JsonResponse(['error' => 'Canvas not found'], 404);
        }

        $storage = $this->entityTypeManager()->getStorage('canvas_version');
        $versions = $storage->loadByProperties(['canvas_id' => $id]);

        $data = [];
        foreach ($versions as $version) {
            $data[] = [
                'version_number' => $version->getVersionNumber(),
                'change_summary' => $version->getChangeSummary(),
                'created' => $version->getCreatedTime(),
            ];
        }

        // Sort by version number desc.
        usort($data, fn($a, $b) => $b['version_number'] <=> $a['version_number']);

        return new JsonResponse(['data' => $data]);
    }

    /**
     * POST /api/v1/canvas/{id}/version - Create a new version snapshot.
     */
    public function createVersion(int $id): JsonResponse
    {
        $canvasData = $this->canvasService->getCanvasWithBlocks($id);

        if (!$canvasData || !$this->checkAccess($canvasData['canvas'], 'edit')) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        try {
            // Increment version and save.
            $canvas = $canvasData['canvas'];
            $canvas->incrementVersion();
            $canvas->save();

            return new JsonResponse([
                'data' => ['version' => $canvas->getVersion()],
                'message' => 'Versión guardada correctamente',
            ], 201);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/v1/canvas/{id}/versions/{version} - Get version snapshot.
     */
    public function getVersion(int $id, int $version): JsonResponse
    {
        $storage = $this->entityTypeManager()->getStorage('canvas_version');
        $versions = $storage->loadByProperties([
            'canvas_id' => $id,
            'version_number' => $version,
        ]);

        if (empty($versions)) {
            return new JsonResponse(['error' => 'Version not found'], 404);
        }

        $versionEntity = reset($versions);

        return new JsonResponse([
            'data' => [
                'version_number' => $versionEntity->getVersionNumber(),
                'snapshot' => $versionEntity->getSnapshot(),
                'change_summary' => $versionEntity->getChangeSummary(),
                'created' => $versionEntity->getCreatedTime(),
            ],
        ]);
    }

    /**
     * POST /api/v1/canvas/{id}/versions/{version}/restore - Restore version.
     */
    public function restoreVersion(int $id, int $version): JsonResponse
    {
        $canvasData = $this->canvasService->getCanvasWithBlocks($id);

        if (!$canvasData || !$this->checkAccess($canvasData['canvas'], 'edit')) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $success = $this->canvasService->restoreVersion($id, $version);

        if (!$success) {
            return new JsonResponse(['error' => 'Version not found'], 404);
        }

        return new JsonResponse(['message' => 'Version restored']);
    }

    /**
     * GET /api/v1/canvas/{id}/export/pdf - Export as PDF.
     */
    public function exportPdf(int $id): JsonResponse
    {
        // TODO: Implement PDF export with Puppeteer.
        return new JsonResponse([
            'message' => 'PDF export not yet implemented',
            'url' => '/canvas/' . $id . '/pdf',
        ], 501);
    }

    /**
     * POST /api/v1/canvas/{id}/share - Share with collaborators.
     */
    public function share(int $id, Request $request): JsonResponse
    {
        $storage = $this->entityTypeManager()->getStorage('business_model_canvas');
        $canvas = $storage->load($id);

        if (!$canvas || !$this->checkAccess($canvas, 'edit')) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['user_id'])) {
            return new JsonResponse(['error' => 'User ID required'], 400);
        }

        $canvas->addCollaborator((int) $data['user_id']);
        $canvas->save();

        return new JsonResponse([
            'message' => 'Collaborator added',
            'shared_with' => $canvas->getSharedWith(),
        ]);
    }

    /**
     * GET /api/v1/canvas/templates - List available templates.
     */
    public function listTemplates(Request $request): JsonResponse
    {
        $sector = $request->query->get('sector');
        $templates = $this->canvasService->getTemplates($sector);

        $data = [];
        foreach ($templates as $template) {
            $data[] = $this->canvasService->serializeCanvas($template, FALSE);
        }

        return new JsonResponse(['data' => $data]);
    }

    /**
     * POST /api/v1/business-tools/agent-rating - Receives agent rating feedback.
     */
    public function submitAgentRating(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['rating'])) {
            return new JsonResponse(['error' => 'Rating is required'], 400);
        }

        $userId = (int) $this->currentUser()->id();

        \Drupal::logger('jaraba_business_tools')->info('Agent rating: @rating from user @user (session: @session)', [
            '@rating' => $data['rating'],
            '@user' => $userId,
            '@session' => $data['session_id'] ?? 'unknown',
        ]);

        return new JsonResponse(['status' => 'ok']);
    }

    /**
     * Checks access to a canvas.
     */
    protected function checkAccess($canvas, string $operation = 'view'): bool
    {
        $currentUser = $this->currentUser();

        // Owner has full access.
        if ($canvas->getOwnerId() == $currentUser->id()) {
            return TRUE;
        }

        // Collaborators can view and edit.
        if (in_array($currentUser->id(), $canvas->getSharedWith())) {
            return $operation !== 'delete';
        }

        // Templates are viewable by all authenticated users.
        if ($canvas->isTemplate() && $operation === 'view') {
            return TRUE;
        }

        // Admins have full access.
        if ($currentUser->hasPermission('administer business model canvas')) {
            return TRUE;
        }

        return FALSE;
    }

}
