<?php

declare(strict_types=1);

namespace Drupal\jaraba_interactive\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_interactive\Plugin\InteractiveTypeManager;
use Drupal\jaraba_interactive\Service\XApiEmitter;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Suscriptor de eventos xAPI para contenido interactivo.
 *
 * Estructura: Escucha eventos de visualizacion de entidades
 * interactive_content para emitir sentencias xAPI de tipo
 * "attempted" cuando un usuario accede al contenido.
 *
 * Logica: Cuando un usuario visualiza un InteractiveContent en modo
 * player (frontend), se emite automaticamente una sentencia xAPI
 * "attempted" con los metadatos del tipo de contenido. Los verbos
 * emitidos dependen del tipo de plugin (cada tipo define sus propios
 * verbos xAPI).
 *
 * Sintaxis: Implementa EventSubscriberInterface con tag event_subscriber.
 */
class XapiSubscriber implements EventSubscriberInterface
{

    /**
     * Constructor.
     *
     * @param \Drupal\jaraba_interactive\Service\XApiEmitter $xapiEmitter
     *   El emisor de sentencias xAPI.
     * @param \Drupal\jaraba_interactive\Plugin\InteractiveTypeManager $typeManager
     *   El plugin manager de tipos interactivos.
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     *   El gestor de tipos de entidad.
     * @param \Psr\Log\LoggerInterface $logger
     *   El logger del modulo.
     */
    public function __construct(
        protected XApiEmitter $xapiEmitter,
        protected InteractiveTypeManager $typeManager,
        protected EntityTypeManagerInterface $entityTypeManager,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * Returns an empty array since the module dependency (core_event_dispatcher)
     * is not installed. The entity view logic should be migrated to Drupal 11
     * Hooks system or implemented via hook_entity_view() in the .module file.
     *
     * @todo Install hook_event_dispatcher or migrate to Drupal 11 Hooks system.
     */
    public static function getSubscribedEvents(): array
    {
        return [];
    }

    /**
     * Reacciona a la visualizacion de un contenido interactivo.
     *
     * Emite una sentencia xAPI "attempted" cuando se visualiza
     * un contenido interactivo en modo player.
     *
     * @param \Drupal\Core\Entity\EntityInterface $entity
     *   La entidad visualizada.
     * @param string $viewMode
     *   El modo de visualización.
     */
    public function onEntityView(EntityInterface $entity, string $viewMode = 'full'): void
    {
        if ($entity->getEntityTypeId() !== 'interactive_content') {
            return;
        }

        // Solo emitir en modo de visualizacion completa (player).
        if ($viewMode !== 'full' && $viewMode !== 'default') {
            return;
        }

        try {
            /** @var \Drupal\jaraba_interactive\Entity\InteractiveContentInterface $content */
            $content = $entity;
            $this->xapiEmitter->emitAttempted($content);

            $this->logger->info('xAPI attempted emitido: contenido @id, tipo @type.', [
                '@id' => $content->id(),
                '@type' => $content->getContentType(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error al emitir xAPI attempted: @message', [
                '@message' => $e->getMessage(),
            ]);
        }
    }

}
