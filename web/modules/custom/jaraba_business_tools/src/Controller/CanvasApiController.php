<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_business_tools\Service\CanvasService;
use Drupal\jaraba_business_tools\Service\CanvasAiService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
            $this->getLogger('jaraba_business_tools')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.'], 500);
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

            $this->getLogger('jaraba_business_tools')->info('Cloned canvas @source to @new for user @user', [
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
            $this->getLogger('jaraba_business_tools')->error('Clone canvas error: @msg', [
                '@msg' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.'], 500);
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

            $this->getLogger('jaraba_business_tools')->info('Generated canvas @id for user @user', [
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
            $this->getLogger('jaraba_business_tools')->error('Generate canvas error: @msg', [
                '@msg' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.'], 500);
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
            $this->getLogger('canvas_api')->debug('addItem START: canvas=@id, type=@type', [
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

            $this->getLogger('canvas_api')->debug('addItem: calling addItemToBlock');

            $block = $this->canvasService->addItemToBlock(
                $id,
                $type,
                $data['text'],
                $data['color'] ?? '#FFE082'
            );

            if (!$block) {
                return new JsonResponse(['error' => 'Block not found'], 404);
            }

            $this->getLogger('canvas_api')->debug('addItem SUCCESS');

            return new JsonResponse([
                'data' => ['items' => $block->getItems()],
                'message' => 'Item added',
            ], 201);
        } catch (\Throwable $e) {
            $this->getLogger('canvas_api')->error('addItem ERROR: @msg at @file:@line', [
                '@msg' => $e->getMessage(),
                '@file' => $e->getFile(),
                '@line' => $e->getLine(),
            ]);
            return new JsonResponse(['error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.'], 500);
        }
    }

    /**
     * DELETE /api/v1/canvas/{id}/blocks/{type}/items/{itemId} - Remove item.
     */
    public function removeItem(int $id, string $type, string $itemId): JsonResponse
    {
        try {
            $this->getLogger('canvas_api')->debug('removeItem START: canvas=@id, type=@type, item=@itemId', [
                '@id' => $id,
                '@type' => $type,
                '@itemId' => $itemId,
            ]);

            $canvasData = $this->canvasService->getCanvasWithBlocks($id);

            if (!$canvasData || !$this->checkAccess($canvasData['canvas'], 'edit')) {
                return new JsonResponse(['error' => 'Access denied'], 403);
            }

            $this->getLogger('canvas_api')->debug('removeItem: calling removeItemFromBlock');

            $success = $this->canvasService->removeItemFromBlock($id, $type, $itemId);

            if (!$success) {
                return new JsonResponse(['error' => 'Item not found'], 404);
            }

            $this->getLogger('canvas_api')->debug('removeItem SUCCESS');

            return new JsonResponse(['message' => 'Item removed']);
        } catch (\Throwable $e) {
            $this->getLogger('canvas_api')->error('removeItem ERROR: @msg at @file:@line', [
                '@msg' => $e->getMessage(),
                '@file' => $e->getFile(),
                '@line' => $e->getLine(),
            ]);
            return new JsonResponse(['error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.'], 500);
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
            $this->getLogger('canvas_api')->error('updateItem ERROR: @msg', [
                '@msg' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.'], 500);
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
            $this->getLogger('jaraba_business_tools')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.'], 500);
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
    public function exportPdf(int $id): Response
    {
        // AUDIT-TODO-RESOLVED: PDF generation implemented.
        $canvasData = $this->canvasService->getCanvasWithBlocks($id);

        if (!$canvasData || !$this->checkAccess($canvasData['canvas'])) {
            return new JsonResponse(['error' => 'Canvas not found or access denied'], 404);
        }

        try {
            $canvas = $canvasData['canvas'];
            $blocks = $canvasData['blocks'];
            $html = $this->renderCanvasPdfHtml($canvas, $blocks);

            // Use DOMPDF if available, otherwise return HTML with print-friendly headers.
            if (class_exists('\Dompdf\Dompdf')) {
                $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => FALSE]);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();
                $pdfContent = $dompdf->output();

                $safeTitle = preg_replace('/[^a-zA-Z0-9_-]/', '_', $canvas->getTitle());
                return new Response($pdfContent, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="canvas-' . $safeTitle . '.pdf"',
                    'Content-Length' => strlen($pdfContent),
                ]);
            }

            // Fallback: return HTML response suitable for browser print-to-PDF.
            $safeTitle = preg_replace('/[^a-zA-Z0-9_-]/', '_', $canvas->getTitle());
            return new Response($html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Content-Disposition' => 'inline; filename="canvas-' . $safeTitle . '.html"',
            ]);
        } catch (\Throwable $e) {
            $this->getLogger('jaraba_business_tools')->error('Canvas PDF export error: @msg', [
                '@msg' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.'], 500);
        }
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

        $this->getLogger('jaraba_business_tools')->info('Agent rating: @rating from user @user (session: @session)', [
            '@rating' => $data['rating'],
            '@user' => $userId,
            '@session' => $data['session_id'] ?? 'unknown',
        ]);

        return new JsonResponse(['status' => 'ok']);
    }

    /**
     * Renders the HTML content for a Business Model Canvas PDF export.
     *
     * Produces a landscape A4 layout with the standard 9-block BMC grid.
     *
     * @param object $canvas
     *   The BusinessModelCanvas entity.
     * @param array $blocks
     *   Associative array of block_type => CanvasBlock entity.
     *
     * @return string
     *   The rendered HTML string with print-friendly CSS.
     */
    protected function renderCanvasPdfHtml(object $canvas, array $blocks): string
    {
        $title = htmlspecialchars($canvas->getTitle(), ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars($canvas->get('description')->value ?? '', ENT_QUOTES, 'UTF-8');
        $sector = htmlspecialchars($canvas->get('sector')->value ?? '', ENT_QUOTES, 'UTF-8');
        $stage = htmlspecialchars($canvas->get('business_stage')->value ?? '', ENT_QUOTES, 'UTF-8');
        $version = (int) ($canvas->getVersion() ?? 1);
        $completeness = (int) ($canvas->getCompletenessScore() ?? 0);
        $dateGenerated = date('d/m/Y H:i');

        // Block type labels in Spanish following the standard BMC layout.
        $blockLabels = [
            'key_partners' => 'Socios Clave',
            'key_activities' => 'Actividades Clave',
            'key_resources' => 'Recursos Clave',
            'value_propositions' => 'Propuesta de Valor',
            'customer_relationships' => 'Relaciones con Clientes',
            'channels' => 'Canales',
            'customer_segments' => 'Segmentos de Clientes',
            'cost_structure' => 'Estructura de Costes',
            'revenue_streams' => 'Fuentes de Ingresos',
        ];

        // Block colors for visual distinction.
        $blockColors = [
            'key_partners' => '#e0f2fe',
            'key_activities' => '#fef3c7',
            'key_resources' => '#fce7f3',
            'value_propositions' => '#d1fae5',
            'customer_relationships' => '#ede9fe',
            'channels' => '#fed7aa',
            'customer_segments' => '#e0e7ff',
            'cost_structure' => '#fecaca',
            'revenue_streams' => '#bbf7d0',
        ];

        // Render block items.
        $renderBlock = function (string $type) use ($blocks, $blockLabels, $blockColors): string {
            $label = $blockLabels[$type] ?? $type;
            $bgColor = $blockColors[$type] ?? '#f8fafc';
            $items = [];

            if (isset($blocks[$type])) {
                $blockItems = $blocks[$type]->getItems();
                if (is_array($blockItems)) {
                    foreach ($blockItems as $item) {
                        $text = is_array($item) ? ($item['text'] ?? '') : (string) $item;
                        if (!empty($text)) {
                            $items[] = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
                        }
                    }
                }
            }

            $itemsHtml = empty($items)
                ? '<p class="empty-block">Sin elementos</p>'
                : '<ul>' . implode('', array_map(fn($i) => "<li>{$i}</li>", $items)) . '</ul>';

            return <<<BLOCK
            <div class="bmc-block" style="background:{$bgColor};">
              <h3>{$label}</h3>
              {$itemsHtml}
            </div>
BLOCK;
        };

        $keyPartnersHtml = $renderBlock('key_partners');
        $keyActivitiesHtml = $renderBlock('key_activities');
        $keyResourcesHtml = $renderBlock('key_resources');
        $valuePropsHtml = $renderBlock('value_propositions');
        $customerRelsHtml = $renderBlock('customer_relationships');
        $channelsHtml = $renderBlock('channels');
        $customerSegHtml = $renderBlock('customer_segments');
        $costStructHtml = $renderBlock('cost_structure');
        $revenueHtml = $renderBlock('revenue_streams');

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Business Model Canvas - {$title}</title>
  <style>
    @page { margin: 10mm; size: A4 landscape; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Helvetica Neue', Arial, sans-serif; font-size: 10px; color: #333; line-height: 1.4; }
    .pdf-header { display: flex; justify-content: space-between; align-items: center; padding: 8px 0 10px; border-bottom: 3px solid #6366f1; margin-bottom: 10px; }
    .pdf-header h1 { font-size: 18px; color: #6366f1; }
    .pdf-header .meta { text-align: right; font-size: 9px; color: #666; }
    .pdf-header .meta span { display: inline-block; margin-left: 10px; padding: 2px 8px; background: #eef2ff; border-radius: 10px; font-size: 9px; }

    .bmc-grid {
      display: grid;
      grid-template-columns: 1fr 0.5fr 0.5fr 1fr 1fr;
      grid-template-rows: 1fr 1fr 1fr;
      gap: 4px;
      height: calc(100vh - 80px);
      min-height: 450px;
    }

    /* BMC layout positions */
    .bmc-grid > :nth-child(1) { grid-column: 1; grid-row: 1 / 4; }         /* Key Partners */
    .bmc-grid > :nth-child(2) { grid-column: 2; grid-row: 1 / 3; }         /* Key Activities */
    .bmc-grid > :nth-child(3) { grid-column: 2; grid-row: 3; }              /* Key Resources */
    .bmc-grid > :nth-child(4) { grid-column: 3; grid-row: 1 / 4; }         /* Value Propositions */
    .bmc-grid > :nth-child(5) { grid-column: 4; grid-row: 1 / 3; }         /* Customer Relationships */
    .bmc-grid > :nth-child(6) { grid-column: 4; grid-row: 3; }              /* Channels */
    .bmc-grid > :nth-child(7) { grid-column: 5; grid-row: 1 / 4; }         /* Customer Segments */
    .bmc-grid > :nth-child(8) { grid-column: 1 / 4; grid-row: 4; }         /* Cost Structure */
    .bmc-grid > :nth-child(9) { grid-column: 4 / 6; grid-row: 4; }         /* Revenue Streams */

    /* Adjust grid to 4 rows for bottom sections */
    .bmc-grid {
      grid-template-rows: 1fr 1fr 1fr 0.8fr;
    }

    .bmc-block { border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px; overflow: hidden; }
    .bmc-block h3 { font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; color: #475569; margin-bottom: 6px; padding-bottom: 4px; border-bottom: 1px solid rgba(0,0,0,0.1); }
    .bmc-block ul { list-style: none; padding: 0; }
    .bmc-block li { padding: 3px 0; font-size: 9px; border-bottom: 1px dotted #e2e8f0; }
    .bmc-block li:last-child { border-bottom: none; }
    .empty-block { color: #94a3b8; font-style: italic; font-size: 9px; }

    .pdf-footer { margin-top: 8px; text-align: center; font-size: 8px; color: #94a3b8; }
    @media print { body { margin: 0; } }
  </style>
</head>
<body>
  <div class="pdf-header">
    <div>
      <h1>{$title}</h1>
      <p style="font-size:10px; color:#64748b; margin-top:2px;">{$description}</p>
    </div>
    <div class="meta">
      <span>Sector: {$sector}</span>
      <span>Fase: {$stage}</span>
      <span>v{$version}</span>
      <span>Completitud: {$completeness}%</span>
      <br><span style="margin-top:4px;">{$dateGenerated}</span>
    </div>
  </div>

  <div class="bmc-grid">
    {$keyPartnersHtml}
    {$keyActivitiesHtml}
    {$keyResourcesHtml}
    {$valuePropsHtml}
    {$customerRelsHtml}
    {$channelsHtml}
    {$customerSegHtml}
    {$costStructHtml}
    {$revenueHtml}
  </div>

  <div class="pdf-footer">
    Business Model Canvas &mdash; Generado por Jaraba Impact Platform
  </div>
</body>
</html>
HTML;
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
