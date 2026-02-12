<?php

declare(strict_types=1);

namespace Drupal\jaraba_skills\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_skills\Entity\AiSkill;
use Drupal\jaraba_skills\Entity\AiSkillRevision;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestión de revisiones de habilidades IA.
 *
 * Permite crear snapshots, restaurar versiones anteriores y comparar cambios.
 */
class SkillRevisionService
{

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected AccountProxyInterface $currentUser,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Crea una revisión (snapshot) de un skill antes de modificarlo.
     *
     * @param \Drupal\jaraba_skills\Entity\AiSkill $skill
     *   El skill del que crear la revisión.
     * @param string $changeSummary
     *   Resumen opcional del cambio.
     *
     * @return \Drupal\jaraba_skills\Entity\AiSkillRevision|null
     *   La revisión creada o NULL si no se creó.
     */
    public function createRevision(AiSkill $skill, string $changeSummary = ''): ?AiSkillRevision
    {
        if (!$skill->id()) {
            // No crear revisiones para skills nuevos.
            return NULL;
        }

        try {
            $storage = $this->entityTypeManager->getStorage('ai_skill_revision');

            // Obtener el siguiente número de revisión.
            $nextRevisionNumber = $this->getNextRevisionNumber((int) $skill->id());

            /** @var \Drupal\jaraba_skills\Entity\AiSkillRevision $revision */
            $revision = $storage->create([
                'skill_id' => $skill->id(),
                'revision_number' => $nextRevisionNumber,
                'name' => $skill->label(),
                'content' => $skill->getContent(),
                'skill_type' => $skill->getSkillType(),
                'priority' => $skill->get('priority')->value ?? 0,
                'is_active' => $skill->isActive(),
                'changed_by' => $this->currentUser->id(),
                'change_summary' => $changeSummary ?: $this->t('Modificación automática'),
            ]);

            $revision->save();

            $this->logger->info('Created revision #@num for skill @id "@name"', [
                '@num' => $nextRevisionNumber,
                '@id' => $skill->id(),
                '@name' => $skill->label(),
            ]);

            return $revision;
        } catch (\Exception $e) {
            $this->logger->error('Error creating revision for skill @id: @message', [
                '@id' => $skill->id(),
                '@message' => $e->getMessage(),
            ]);
            return NULL;
        }
    }

    /**
     * Obtiene el siguiente número de revisión para un skill.
     */
    protected function getNextRevisionNumber(int $skillId): int
    {
        $query = $this->entityTypeManager->getStorage('ai_skill_revision')
            ->getQuery()
            ->accessCheck(FALSE)
            ->condition('skill_id', $skillId)
            ->sort('revision_number', 'DESC')
            ->range(0, 1);

        $ids = $query->execute();

        if (empty($ids)) {
            return 1;
        }

        $lastRevision = $this->entityTypeManager
            ->getStorage('ai_skill_revision')
            ->load(reset($ids));

        return $lastRevision ? $lastRevision->getRevisionNumber() + 1 : 1;
    }

    /**
     * Obtiene todas las revisiones de un skill.
     *
     * @param int $skillId
     *   ID del skill.
     *
     * @return \Drupal\jaraba_skills\Entity\AiSkillRevision[]
     *   Array de revisiones ordenadas por número (más reciente primero).
     */
    public function getRevisions(int $skillId): array
    {
        $query = $this->entityTypeManager->getStorage('ai_skill_revision')
            ->getQuery()
            ->accessCheck(TRUE)
            ->condition('skill_id', $skillId)
            ->sort('revision_number', 'DESC');

        $ids = $query->execute();

        if (empty($ids)) {
            return [];
        }

        return $this->entityTypeManager
            ->getStorage('ai_skill_revision')
            ->loadMultiple($ids);
    }

    /**
     * Restaura un skill a una versión anterior.
     *
     * @param int $revisionId
     *   ID de la revisión a restaurar.
     *
     * @return bool
     *   TRUE si se restauró correctamente.
     */
    public function restoreRevision(int $revisionId): bool
    {
        try {
            /** @var \Drupal\jaraba_skills\Entity\AiSkillRevision $revision */
            $revision = $this->entityTypeManager
                ->getStorage('ai_skill_revision')
                ->load($revisionId);

            if (!$revision) {
                $this->logger->warning('Revision @id not found for restore', ['@id' => $revisionId]);
                return FALSE;
            }

            $skillId = $revision->getSkillId();
            if (!$skillId) {
                $this->logger->warning('Revision @id has no skill_id', ['@id' => $revisionId]);
                return FALSE;
            }

            /** @var \Drupal\jaraba_skills\Entity\AiSkill $skill */
            $skill = $this->entityTypeManager
                ->getStorage('ai_skill')
                ->load($skillId);

            if (!$skill) {
                $this->logger->warning('Skill @id not found for restore', ['@id' => $skillId]);
                return FALSE;
            }

            // Crear una revisión del estado actual antes de restaurar.
            $this->createRevision($skill, $this->t('Antes de restaurar a revisión #@num', [
                '@num' => $revision->getRevisionNumber(),
            ]));

            // Restaurar los valores de la revisión.
            $skill->set('name', $revision->getName());
            $skill->set('content', $revision->getContent());
            $skill->set('skill_type', $revision->getSkillType());
            $skill->set('priority', $revision->getPriority());
            $skill->set('is_active', $revision->wasActive());

            // Guardar sin crear otra revisión (ya la creamos arriba).
            $skill->__jaraba_skip_revision = TRUE;
            $skill->save();
            unset($skill->__jaraba_skip_revision);

            $this->logger->info('Restored skill @id to revision #@num', [
                '@id' => $skillId,
                '@num' => $revision->getRevisionNumber(),
            ]);

            return TRUE;
        } catch (\Exception $e) {
            $this->logger->error('Error restoring revision @id: @message', [
                '@id' => $revisionId,
                '@message' => $e->getMessage(),
            ]);
            return FALSE;
        }
    }

    /**
     * Compara dos revisiones (para generar diff).
     *
     * @param int $revId1
     *   ID de la primera revisión.
     * @param int $revId2
     *   ID de la segunda revisión.
     *
     * @return array
     *   Array con los cambios entre las dos revisiones.
     */
    public function compareRevisions(int $revId1, int $revId2): array
    {
        $storage = $this->entityTypeManager->getStorage('ai_skill_revision');

        /** @var \Drupal\jaraba_skills\Entity\AiSkillRevision $rev1 */
        $rev1 = $storage->load($revId1);
        /** @var \Drupal\jaraba_skills\Entity\AiSkillRevision $rev2 */
        $rev2 = $storage->load($revId2);

        if (!$rev1 || !$rev2) {
            return ['error' => 'One or both revisions not found'];
        }

        return [
            'revision_1' => [
                'id' => $revId1,
                'number' => $rev1->getRevisionNumber(),
                'name' => $rev1->getName(),
                'content' => $rev1->getContent(),
                'created' => $rev1->getCreatedTime(),
            ],
            'revision_2' => [
                'id' => $revId2,
                'number' => $rev2->getRevisionNumber(),
                'name' => $rev2->getName(),
                'content' => $rev2->getContent(),
                'created' => $rev2->getCreatedTime(),
            ],
            'changes' => [
                'name_changed' => $rev1->getName() !== $rev2->getName(),
                'content_changed' => $rev1->getContent() !== $rev2->getContent(),
                'type_changed' => $rev1->getSkillType() !== $rev2->getSkillType(),
                'priority_changed' => $rev1->getPriority() !== $rev2->getPriority(),
                'active_changed' => $rev1->wasActive() !== $rev2->wasActive(),
            ],
        ];
    }

    /**
     * Obtiene estadísticas de revisiones.
     *
     * @return array
     *   Estadísticas globales de revisiones.
     */
    public function getStatistics(): array
    {
        $storage = $this->entityTypeManager->getStorage('ai_skill_revision');

        $query = $storage->getQuery()
            ->accessCheck(FALSE);
        $totalRevisions = $query->count()->execute();

        $skillsQuery = $storage->getQuery()
            ->accessCheck(FALSE);
        $skillIds = $skillsQuery->execute();
        $uniqueSkillRevisions = count(array_unique(array_map(function ($id) use ($storage) {
            $rev = $storage->load($id);
            return $rev ? $rev->getSkillId() : 0;
        }, $skillIds)));

        return [
            'total_revisions' => (int) $totalRevisions,
            'skills_with_revisions' => $uniqueSkillRevisions,
        ];
    }

    /**
     * Helper para traducción.
     */
    protected function t(string $string, array $args = []): string
    {
        return (string) \Drupal::translation()->translate($string, $args);
    }

}
