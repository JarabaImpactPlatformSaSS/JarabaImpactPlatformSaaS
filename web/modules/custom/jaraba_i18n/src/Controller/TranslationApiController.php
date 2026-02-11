<?php

declare(strict_types=1);

namespace Drupal\jaraba_i18n\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_i18n\Service\AITranslationService;
use Drupal\jaraba_i18n\Service\TranslationManagerService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador API para operaciones de traducción.
 *
 * PROPÓSITO:
 * Expone endpoints REST para el selector de idioma y la traducción con IA.
 * Usado por el componente Alpine.js i18n-selector.
 *
 * ENDPOINTS:
 * - GET /api/jaraba-i18n/status/{entity_type}/{entity_id}
 * - POST /api/jaraba-i18n/translate
 * - GET /api/jaraba-i18n/stats/{entity_type}
 */
class TranslationApiController extends ControllerBase
{

    /**
     * Servicio de gestión de traducciones.
     *
     * @var \Drupal\jaraba_i18n\Service\TranslationManagerService
     */
    protected TranslationManagerService $translationManager;

    /**
     * Servicio de traducción con IA.
     *
     * @var \Drupal\jaraba_i18n\Service\AITranslationService
     */
    protected AITranslationService $aiTranslation;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        $instance = new static();
        $instance->translationManager = $container->get('jaraba_i18n.translation_manager');
        $instance->aiTranslation = $container->get('jaraba_i18n.ai_translation');
        return $instance;
    }

    /**
     * Obtiene el estado de traducciones de una entidad.
     *
     * @param string $entity_type
     *   Tipo de entidad.
     * @param string $entity_id
     *   ID de la entidad.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Estado de traducciones en JSON.
     */
    public function getStatus(string $entity_type, string $entity_id): JsonResponse
    {
        try {
            $entity = $this->entityTypeManager()
                ->getStorage($entity_type)
                ->load($entity_id);

            if (!$entity) {
                return new JsonResponse([
                    'error' => 'Entidad no encontrada',
                ], 404);
            }

            $status = $this->translationManager->getTranslationStatus($entity);

            return new JsonResponse([
                'entity_type' => $entity_type,
                'entity_id' => $entity_id,
                'languages' => $status,
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Traduce una entidad con IA.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Resultado de la traducción.
     */
    public function translate(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), TRUE);

            // Validar campos requeridos.
            $required = ['entity_type', 'entity_id', 'source_lang', 'target_lang'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return new JsonResponse([
                        'error' => "Campo requerido: {$field}",
                    ], 400);
                }
            }

            // Cargar entidad.
            $entity = $this->entityTypeManager()
                ->getStorage($data['entity_type'])
                ->load($data['entity_id']);

            if (!$entity) {
                return new JsonResponse([
                    'error' => 'Entidad no encontrada',
                ], 404);
            }

            // Crear traducción si no existe.
            if (!$entity->hasTranslation($data['target_lang'])) {
                $translation = $this->translationManager->createTranslation(
                    $entity,
                    $data['target_lang']
                );
            } else {
                $translation = $entity->getTranslation($data['target_lang']);
            }

            // Traducir con IA.
            $translation = $this->aiTranslation->translateEntity(
                $translation,
                $data['source_lang'],
                $data['target_lang']
            );

            // Guardar.
            $translation->save();

            return new JsonResponse([
                'success' => TRUE,
                'message' => 'Traducción completada',
                'entity_type' => $data['entity_type'],
                'entity_id' => $data['entity_id'],
                'target_lang' => $data['target_lang'],
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtiene estadísticas de traducción para un tipo de entidad.
     *
     * @param string $entity_type
     *   Tipo de entidad.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Estadísticas en JSON.
     */
    public function getStats(string $entity_type): JsonResponse
    {
        try {
            $stats = $this->translationManager->getTranslationStats($entity_type);

            return new JsonResponse([
                'entity_type' => $entity_type,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
