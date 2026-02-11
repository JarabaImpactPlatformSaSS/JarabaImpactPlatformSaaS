<?php

declare(strict_types=1);

namespace Drupal\jaraba_i18n\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio central para gestión de traducciones de entidades.
 *
 * ¿QUÉ PROBLEMA RESUELVE?
 * =======================
 * La plataforma Jaraba tiene múltiples tipos de contenido que necesitan
 * soporte multilingüe:
 * - PageContent (Page Builder)
 * - BlogPost (AI Content Hub)
 * - Course, Lesson, Activity (LMS)
 * - HomepageContent (Site Builder)
 *
 * Este servicio proporciona una interfaz unificada para:
 * 1. Detectar el estado de traducción de cualquier entidad
 * 2. Determinar qué idiomas faltan por traducir
 * 3. Crear estructuras base para nuevas traducciones
 * 4. Detectar si una traducción está desactualizada
 *
 * ¿CÓMO FUNCIONA?
 * ===============
 * El servicio trabaja con entidades que implementan TranslatableInterface.
 * Para cada entidad, puede determinar:
 * - Qué idiomas están disponibles en el sitio
 * - Cuáles tienen traducción existente
 * - Si la traducción está sincronizada con el original
 *
 * Se integra con AITranslationService para traducción asistida y con
 * EntityTranslationAdapterManager para manejar diferentes tipos de entidad.
 *
 * MÉTRICAS OBJETIVO (Gap E del Plan de Elevación)
 * ================================================
 * - Cobertura de traducciones: >80% de contenido en idiomas configurados
 * - Tiempo de traducción: -70% con asistencia IA
 * - UX: Selector de idioma integrado en todos los editores
 *
 * @see docs/planificacion/20260129-Plan_Elevacion_Clase_Mundial_v1.md
 */
class TranslationManagerService
{

    /**
     * Constructor del servicio.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     *   Gestor de tipos de entidad para cargar/guardar traducciones.
     * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
     *   Gestor de idiomas para obtener idiomas configurados en el sitio.
     * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
     *   Fábrica de configuración para opciones del módulo.
     * @param \Psr\Log\LoggerInterface $logger
     *   Logger para registro de operaciones y errores.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected LanguageManagerInterface $languageManager,
        protected ConfigFactoryInterface $configFactory,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Obtiene el estado de traducción de una entidad.
     *
     * Analiza la entidad y retorna un array con el estado de cada idioma
     * disponible en el sitio, indicando si existe traducción y si está
     * actualizada respecto al contenido original.
     *
     * @param \Drupal\Core\Entity\ContentEntityInterface $entity
     *   La entidad a analizar.
     *
     * @return array<string, array{exists: bool, outdated: bool, label: string}>
     *   Estado por idioma con las claves:
     *   - exists: TRUE si hay traducción para ese idioma
     *   - outdated: TRUE si la traducción necesita actualización
     *   - label: Nombre del idioma para mostrar en UI
     */
    public function getTranslationStatus(ContentEntityInterface $entity): array
    {
        // Verificar que la entidad soporta traducciones.
        if (!$entity->isTranslatable()) {
            return [];
        }

        $status = [];
        $original = $entity->getUntranslated();
        $originalLangcode = $original->language()->getId();

        // Obtener timestamp del original si la entidad soporta EntityChangedInterface.
        $originalChanged = 0;
        if ($original instanceof EntityChangedInterface) {
            $originalChanged = $original->getChangedTime();
        }

        $languages = $this->getAvailableLanguages();

        foreach ($languages as $langcode => $language) {
            $hasTranslation = $entity->hasTranslation($langcode);

            // Determinar si la traducción está desactualizada.
            // Una traducción está desactualizada si el original se modificó después.
            $isOutdated = FALSE;
            if ($hasTranslation && $langcode !== $originalLangcode) {
                $translation = $entity->getTranslation($langcode);
                $translationChanged = 0;
                if ($translation instanceof EntityChangedInterface) {
                    $translationChanged = $translation->getChangedTime();
                }
                $isOutdated = $originalChanged > $translationChanged;
            }

            $status[$langcode] = [
                'exists' => $hasTranslation,
                'outdated' => $isOutdated,
                'label' => $language->getName(),
                'is_original' => $langcode === $originalLangcode,
            ];
        }

        return $status;
    }

    /**
     * Obtiene idiomas con traducciones pendientes para una entidad.
     *
     * @param \Drupal\Core\Entity\ContentEntityInterface $entity
     *   La entidad a analizar.
     *
     * @return array<string, string>
     *   Array de [langcode => nombre] para idiomas sin traducción.
     */
    public function getMissingTranslations(ContentEntityInterface $entity): array
    {
        $status = $this->getTranslationStatus($entity);
        $missing = [];

        foreach ($status as $langcode => $info) {
            if (!$info['exists'] && !$info['is_original']) {
                $missing[$langcode] = $info['label'];
            }
        }

        return $missing;
    }

    /**
     * Obtiene idiomas con traducciones desactualizadas para una entidad.
     *
     * @param \Drupal\Core\Entity\ContentEntityInterface $entity
     *   La entidad a analizar.
     *
     * @return array<string, string>
     *   Array de [langcode => nombre] para idiomas desactualizados.
     */
    public function getOutdatedTranslations(ContentEntityInterface $entity): array
    {
        $status = $this->getTranslationStatus($entity);
        $outdated = [];

        foreach ($status as $langcode => $info) {
            if ($info['exists'] && $info['outdated']) {
                $outdated[$langcode] = $info['label'];
            }
        }

        return $outdated;
    }

    /**
     * Crea una traducción vacía de una entidad.
     *
     * Duplica la estructura de la entidad (campos, referencias) pero marca
     * los campos de texto como pendientes de traducción.
     *
     * @param \Drupal\Core\Entity\ContentEntityInterface $entity
     *   La entidad original.
     * @param string $langcode
     *   Código del idioma destino.
     *
     * @return \Drupal\Core\Entity\ContentEntityInterface
     *   La nueva traducción (sin guardar).
     *
     * @throws \InvalidArgumentException
     *   Si la entidad no soporta traducciones o ya existe la traducción.
     */
    public function createTranslation(
        ContentEntityInterface $entity,
        string $langcode
    ): ContentEntityInterface {
        // Validaciones.
        if (!$entity->isTranslatable()) {
            throw new \InvalidArgumentException(
                sprintf('La entidad %s no soporta traducciones.', $entity->getEntityTypeId())
            );
        }

        if ($entity->hasTranslation($langcode)) {
            throw new \InvalidArgumentException(
                sprintf('Ya existe traducción para el idioma %s.', $langcode)
            );
        }

        // Crear la traducción base.
        $translation = $entity->addTranslation($langcode, $entity->toArray());

        $this->logger->info('Traducción creada para @type:@id en idioma @lang', [
            '@type' => $entity->getEntityTypeId(),
            '@id' => $entity->id(),
            '@lang' => $langcode,
        ]);

        return $translation;
    }

    /**
     * Obtiene idiomas disponibles en el sitio.
     *
     * @return \Drupal\Core\Language\LanguageInterface[]
     *   Array de idiomas configurados.
     */
    public function getAvailableLanguages(): array
    {
        return $this->languageManager->getLanguages();
    }

    /**
     * Obtiene estadísticas de traducción para un tipo de entidad.
     *
     * Útil para el dashboard de traducciones.
     *
     * @param string $entityTypeId
     *   ID del tipo de entidad (ej: 'page_content', 'blog_post').
     *
     * @return array{total: int, translated: array<string, int>, missing: array<string, int>}
     *   Estadísticas de traducción.
     */
    public function getTranslationStats(string $entityTypeId): array
    {
        $storage = $this->entityTypeManager->getStorage($entityTypeId);
        $entities = $storage->loadMultiple();

        $stats = [
            'total' => count($entities),
            'translated' => [],
            'missing' => [],
        ];

        $languages = $this->getAvailableLanguages();
        foreach ($languages as $langcode => $language) {
            $stats['translated'][$langcode] = 0;
            $stats['missing'][$langcode] = 0;
        }

        foreach ($entities as $entity) {
            if (!$entity instanceof ContentEntityInterface || !$entity->isTranslatable()) {
                continue;
            }

            $originalLangcode = $entity->getUntranslated()->language()->getId();

            foreach ($languages as $langcode => $language) {
                if ($entity->hasTranslation($langcode)) {
                    $stats['translated'][$langcode]++;
                } elseif ($langcode !== $originalLangcode) {
                    $stats['missing'][$langcode]++;
                }
            }
        }

        return $stats;
    }

}
