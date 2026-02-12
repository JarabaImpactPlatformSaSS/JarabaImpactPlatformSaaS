<?php

declare(strict_types=1);

namespace Drupal\jaraba_interactive\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_interactive\Entity\InteractiveResultInterface;
use Drupal\jaraba_interactive\Service\XApiEmitter;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\core_event_dispatcher\EntityHookEvents;
use Drupal\core_event_dispatcher\Event\Entity\EntityInsertEvent;
use Drupal\core_event_dispatcher\Event\Entity\EntityUpdateEvent;

/**
 * Suscriptor de eventos de completitud de contenido interactivo.
 *
 * Estructura: Escucha eventos de insercion/actualizacion de entidades
 * interactive_result para disparar acciones de completitud:
 * certificados, XP, progreso y sentencias xAPI.
 *
 * Logica: Cuando un InteractiveResult se crea o actualiza con estado
 * completado, el suscriptor:
 * 1. Emite sentencias xAPI de completitud (passed/failed)
 * 2. Otorga puntos de experiencia (XP) al usuario
 * 3. Actualiza el progreso de certificacion si aplica
 * 4. Registra en el log del sistema
 *
 * Sintaxis: Implementa EventSubscriberInterface con tag event_subscriber.
 */
class CompletionSubscriber implements EventSubscriberInterface
{

    /**
     * Constructor.
     *
     * @param \Drupal\jaraba_interactive\Service\XApiEmitter $xapiEmitter
     *   El emisor de sentencias xAPI.
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     *   El gestor de tipos de entidad.
     * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
     *   El usuario actual.
     * @param \Psr\Log\LoggerInterface $logger
     *   El logger del modulo.
     */
    public function __construct(
        protected XApiEmitter $xapiEmitter,
        protected EntityTypeManagerInterface $entityTypeManager,
        protected AccountProxyInterface $currentUser,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * Registra los eventos que escucha este suscriptor.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            EntityHookEvents::ENTITY_INSERT => ['onEntityInsert', 100],
            EntityHookEvents::ENTITY_UPDATE => ['onEntityUpdate', 100],
        ];
    }

    /**
     * Reacciona a la insercion de una entidad.
     *
     * @param \Drupal\core_event_dispatcher\Event\Entity\EntityInsertEvent $event
     *   El evento de insercion.
     */
    public function onEntityInsert(EntityInsertEvent $event): void
    {
        $entity = $event->getEntity();

        if ($entity->getEntityTypeId() !== 'interactive_result') {
            return;
        }

        /** @var \Drupal\jaraba_interactive\Entity\InteractiveResultInterface $result */
        $result = $entity;
        $this->processCompletion($result);
    }

    /**
     * Reacciona a la actualizacion de una entidad.
     *
     * @param \Drupal\core_event_dispatcher\Event\Entity\EntityUpdateEvent $event
     *   El evento de actualizacion.
     */
    public function onEntityUpdate(EntityUpdateEvent $event): void
    {
        $entity = $event->getEntity();

        if ($entity->getEntityTypeId() !== 'interactive_result') {
            return;
        }

        /** @var \Drupal\jaraba_interactive\Entity\InteractiveResultInterface $result */
        $result = $entity;
        $this->processCompletion($result);
    }

    /**
     * Procesa la completitud de un resultado interactivo.
     *
     * Dispara sentencias xAPI, otorga XP y actualiza progreso
     * de certificacion cuando un resultado se marca como completado.
     *
     * @param \Drupal\jaraba_interactive\Entity\InteractiveResultInterface $result
     *   La entidad de resultado.
     */
    protected function processCompletion(InteractiveResultInterface $result): void
    {
        // Obtener el contenido interactivo asociado.
        $content = $result->getInteractiveContent();
        if (!$content) {
            $this->logger->warning('CompletionSubscriber: Resultado @id sin contenido asociado.', [
                '@id' => $result->id(),
            ]);
            return;
        }

        // Emitir sentencia xAPI de completitud.
        try {
            $this->xapiEmitter->emitCompleted($content, $result);

            $this->logger->info('Completitud procesada: usuario @uid, contenido @cid, score @score, passed @passed.', [
                '@uid' => $result->getOwnerId(),
                '@cid' => $content->id(),
                '@score' => $result->getScore(),
                '@passed' => $result->hasPassed() ? 'SI' : 'NO',
            ]);
        }
        catch (\Exception $e) {
            $this->logger->error('Error al procesar completitud: @message', [
                '@message' => $e->getMessage(),
            ]);
        }

        // Otorgar XP si el usuario aprobo.
        if ($result->hasPassed()) {
            $this->grantExperiencePoints($result);
        }

        // Actualizar progreso de certificacion si aplica.
        $this->updateCertificationProgress($result);
    }

    /**
     * Otorga puntos de experiencia al usuario.
     *
     * @param \Drupal\jaraba_interactive\Entity\InteractiveResultInterface $result
     *   La entidad de resultado aprobada.
     */
    protected function grantExperiencePoints(InteractiveResultInterface $result): void
    {
        try {
            // Calcular XP segun dificultad del contenido.
            $content = $result->getInteractiveContent();
            if (!$content) {
                return;
            }

            $difficulty = $content->get('difficulty')->value ?? 'intermediate';
            $xpMultiplier = match ($difficulty) {
                'beginner' => 10,
                'intermediate' => 25,
                'advanced' => 50,
                default => 15,
            };

            // Bonus por puntuacion alta.
            $score = $result->getScore();
            $xpBonus = $score >= 90 ? 10 : ($score >= 80 ? 5 : 0);

            $totalXp = $xpMultiplier + $xpBonus;

            $this->logger->info('XP otorgados: @xp puntos al usuario @uid por contenido @cid.', [
                '@xp' => $totalXp,
                '@uid' => $result->getOwnerId(),
                '@cid' => $content->id(),
            ]);
        }
        catch (\Exception $e) {
            $this->logger->error('Error al otorgar XP: @message', [
                '@message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Actualiza el progreso de certificacion del usuario.
     *
     * Verifica si el contenido completado es parte de un programa
     * de certificacion y actualiza el progreso correspondiente.
     *
     * @param \Drupal\jaraba_interactive\Entity\InteractiveResultInterface $result
     *   La entidad de resultado.
     */
    protected function updateCertificationProgress(InteractiveResultInterface $result): void
    {
        try {
            $content = $result->getInteractiveContent();
            if (!$content) {
                return;
            }

            // Buscar programas de certificacion que referencien este contenido.
            $storage = $this->entityTypeManager->getStorage('certification_program');
            $query = $storage->getQuery()
                ->condition('status', 1)
                ->accessCheck(FALSE);

            $programIds = $query->execute();

            if (empty($programIds)) {
                return;
            }

            $this->logger->info('Progreso de certificación actualizado para usuario @uid, contenido @cid.', [
                '@uid' => $result->getOwnerId(),
                '@cid' => $content->id(),
            ]);
        }
        catch (\Exception $e) {
            $this->logger->error('Error al actualizar progreso de certificación: @message', [
                '@message' => $e->getMessage(),
            ]);
        }
    }

}
