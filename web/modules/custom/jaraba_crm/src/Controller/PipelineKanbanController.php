<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller para la vista Kanban del Pipeline CRM.
 */
class PipelineKanbanController extends ControllerBase
{

    /**
     * Vista Kanban del pipeline de oportunidades.
     */
    public function kanban(): array
    {
        $stages = jaraba_crm_get_opportunity_stage_values();

        $columns = [];
        foreach ($stages as $key => $label) {
            $columns[$key] = [
                'key' => $key,
                'label' => $label,
                'opportunities' => $this->getOpportunitiesByStage($key),
            ];
        }

        return [
            '#theme' => 'crm_pipeline_kanban',
            '#columns' => $columns,
            '#attached' => [
                'library' => ['jaraba_crm/kanban'],
            ],
            '#cache' => ['max-age' => 0],
        ];
    }

    /**
     * Obtiene oportunidades por etapa.
     */
    protected function getOpportunitiesByStage(string $stage): array
    {
        $storage = $this->entityTypeManager->getStorage('crm_opportunity');

        $ids = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('stage', $stage)
            ->sort('changed', 'DESC')
            ->execute();

        if (empty($ids)) {
            return [];
        }

        $opportunities = $storage->loadMultiple($ids);
        $items = [];

        foreach ($opportunities as $opportunity) {
            $items[] = [
                'id' => $opportunity->id(),
                'title' => $opportunity->label(),
                'value' => $opportunity->get('value')->value ?? 0,
                'probability' => $opportunity->get('probability')->value ?? 0,
                'expected_close' => $opportunity->get('expected_close')->value ?? NULL,
                'url' => $opportunity->toUrl('edit-form')->toString(),
            ];
        }

        return $items;
    }

    /**
     * API: Mover oportunidad a otra etapa.
     */
    public function move(): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $request = \Drupal::request();
        $data = json_decode($request->getContent(), TRUE) ?? [];

        $opportunityId = $data['opportunity_id'] ?? NULL;
        $newStage = $data['stage'] ?? NULL;

        if (!$opportunityId || !$newStage) {
            return new \Symfony\Component\HttpFoundation\JsonResponse([
                'success' => FALSE,
                'error' => $this->t('Datos incompletos.'),
            ], 400);
        }

        $storage = $this->entityTypeManager->getStorage('crm_opportunity');
        $opportunity = $storage->load($opportunityId);

        if (!$opportunity) {
            return new \Symfony\Component\HttpFoundation\JsonResponse([
                'success' => FALSE,
                'error' => $this->t('Oportunidad no encontrada.'),
            ], 404);
        }

        $opportunity->set('stage', $newStage);
        $opportunity->save();

        return new \Symfony\Component\HttpFoundation\JsonResponse([
            'success' => TRUE,
            'message' => $this->t('Oportunidad movida a @stage.', ['@stage' => $newStage]),
        ]);
    }

}
