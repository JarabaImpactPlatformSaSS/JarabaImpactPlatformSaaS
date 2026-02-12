<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_content_hub\Agent\ContentWriterAgent;
use Drupal\jaraba_content_hub\Entity\ContentArticle;
use Psr\Log\LoggerInterface;

/**
 * Servicio de alto nivel para funciones de escritura asistida por IA.
 *
 * PROPÓSITO:
 * Orquesta el ContentWriterAgent con entidades de artículos para proporcionar
 * una interfaz simplificada de generación de contenido. Maneja la creación
 * de borradores, optimización SEO y expansión de secciones.
 *
 * CARACTERÍSTICAS:
 * - Generación de esquemas/outlines para artículos
 * - Expansión de secciones individuales
 * - Optimización automática de headlines
 * - Mejoras SEO (Answer Capsule, meta tags)
 * - Creación completa de borradores desde un tema
 *
 * ESPECIFICACIÓN: Doc 128 - Platform_AI_Content_Hub_v2
 */
class WritingAssistantService
{

    /**
     * El agente de escritura de contenido IA.
     *
     * @var \Drupal\jaraba_content_hub\Agent\ContentWriterAgent
     */
    protected ContentWriterAgent $agent;

    /**
     * El gestor de tipos de entidad.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * El logger para registrar eventos.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Construye un WritingAssistantService.
     *
     * @param \Drupal\jaraba_content_hub\Agent\ContentWriterAgent $agent
     *   Agente IA especializado en redacción de contenido.
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     *   El gestor de tipos de entidad.
     * @param \Psr\Log\LoggerInterface $logger
     *   El servicio de logging.
     */
    public function __construct(
        ContentWriterAgent $agent,
        EntityTypeManagerInterface $entityTypeManager,
        LoggerInterface $logger,
    ) {
        $this->agent = $agent;
        $this->entityTypeManager = $entityTypeManager;
        $this->logger = $logger;
    }

    /**
     * Genera un esquema/outline para un nuevo artículo.
     *
     * Crea una estructura de secciones y puntos clave basándose
     * en el tema proporcionado. Útil como primer paso antes de
     * escribir el contenido completo.
     *
     * @param string $topic
     *   El tema sobre el que escribir.
     * @param array $options
     *   Configuración opcional:
     *   - 'audience': Audiencia objetivo (general/técnico/empresarial).
     *   - 'length': Longitud deseada (short/medium/long).
     *
     * @return array
     *   Resultado con 'success' y 'data' o 'error'.
     */
    public function generateOutline(string $topic, array $options = []): array
    {
        return $this->agent->execute('generate_outline', [
            'topic' => $topic,
            'audience' => $options['audience'] ?? 'general',
            'length' => $options['length'] ?? 'medium',
        ]);
    }

    /**
     * Expande una sección en contenido completo.
     *
     * Toma un encabezado de sección y puntos clave para generar
     * párrafos desarrollados. Mantiene coherencia con el contexto
     * del artículo completo.
     *
     * @param string $heading
     *   El encabezado de la sección.
     * @param array $keyPoints
     *   Puntos clave a desarrollar en la sección.
     * @param string $articleContext
     *   Contexto general del artículo para coherencia.
     *
     * @return array
     *   Resultado con 'success' y 'data' o 'error'.
     */
    public function expandSection(string $heading, array $keyPoints = [], string $articleContext = ''): array
    {
        return $this->agent->execute('expand_section', [
            'heading' => $heading,
            'key_points' => $keyPoints,
            'article_context' => $articleContext,
        ]);
    }

    /**
     * Genera variantes optimizadas de titulares.
     *
     * Propone múltiples títulos optimizados para engagement y SEO
     * basándose en el tema y opcionalmente mejorando un título existente.
     *
     * @param string $topic
     *   El tema del artículo.
     * @param string $currentTitle
     *   Título actual a mejorar (opcional).
     *
     * @return array
     *   Resultado con 'success' y 'data' conteniendo variantes de títulos.
     */
    public function optimizeHeadline(string $topic, string $currentTitle = ''): array
    {
        return $this->agent->execute('optimize_headline', [
            'topic' => $topic,
            'current_title' => $currentTitle,
        ]);
    }

    /**
     * Mejora elementos SEO de un artículo.
     *
     * Genera Answer Capsule (respuesta directa para featured snippets),
     * meta título optimizado y meta descripción basándose en el contenido.
     *
     * @param string $title
     *   El título del artículo.
     * @param string $body
     *   El cuerpo del artículo (contenido HTML).
     *
     * @return array
     *   Resultado con 'success' y 'data' conteniendo:
     *   - 'answer_capsule': Respuesta directa para SEO.
     *   - 'seo_title': Título optimizado para buscadores.
     *   - 'seo_description': Meta descripción.
     */
    public function improveSeo(string $title, string $body): array
    {
        return $this->agent->execute('improve_seo', [
            'title' => $title,
            'body' => $body,
        ]);
    }

    /**
     * Genera un artículo completo desde un tema.
     *
     * Crea todos los elementos del artículo: título, cuerpo HTML,
     * extracto, Answer Capsule y metadatos SEO. Es la operación
     * más completa de generación de contenido.
     *
     * @param string $topic
     *   El tema sobre el que escribir.
     * @param array $options
     *   Configuración opcional:
     *   - 'audience': Audiencia objetivo.
     *   - 'length': Longitud deseada.
     *   - 'style': Estilo de redacción (informative/persuasive/tutorial).
     *
     * @return array
     *   Resultado con 'success' y 'data' conteniendo todos los campos.
     */
    public function generateFullArticle(string $topic, array $options = []): array
    {
        return $this->agent->execute('full_article', [
            'topic' => $topic,
            'audience' => $options['audience'] ?? 'general',
            'length' => $options['length'] ?? 'medium',
            'style' => $options['style'] ?? 'informative',
        ]);
    }

    /**
     * Genera y crea un borrador de artículo.
     *
     * Combina la generación completa con la creación de la entidad
     * ContentArticle en estado draft. Marca el artículo como
     * generado por IA para trazabilidad.
     *
     * @param string $topic
     *   El tema sobre el que escribir.
     * @param array $options
     *   Configuración opcional (ver generateFullArticle).
     *
     * @return array
     *   Resultado con 'success', 'article' (entidad) y 'data'.
     */
    public function createDraftFromTopic(string $topic, array $options = []): array
    {
        $result = $this->generateFullArticle($topic, $options);

        if (!$result['success']) {
            return $result;
        }

        $data = $result['data'];

        try {
            $storage = $this->entityTypeManager->getStorage('content_article');
            $article = $storage->create([
                'title' => $data['title'] ?? $topic,
                'body' => $data['body_html'] ?? '',
                'excerpt' => $data['excerpt'] ?? '',
                'answer_capsule' => $data['answer_capsule'] ?? '',
                'seo_title' => $data['seo_title'] ?? '',
                'seo_description' => $data['seo_description'] ?? '',
                'reading_time' => $data['reading_time'] ?? 5,
                'status' => 'draft',
                'ai_generated' => TRUE,
            ]);
            $article->save();

            $this->logger->info('Borrador IA creado: @title', ['@title' => $article->label()]);

            return [
                'success' => TRUE,
                'article' => $article,
                'data' => $data,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error al crear borrador IA: @error', ['@error' => $e->getMessage()]);
            return [
                'success' => FALSE,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Aplica mejoras SEO a un artículo existente.
     *
     * Carga el artículo, genera mejoras SEO via IA y actualiza
     * los campos answer_capsule, seo_title y seo_description.
     *
     * @param int $articleId
     *   El ID del artículo a mejorar.
     *
     * @return array
     *   Resultado con 'success', 'data' y 'article_id'.
     */
    public function applySeoToArticle(int $articleId): array
    {
        $storage = $this->entityTypeManager->getStorage('content_article');
        $article = $storage->load($articleId);

        if (!$article) {
            return ['success' => FALSE, 'error' => 'Artículo no encontrado.'];
        }

        $result = $this->improveSeo(
            $article->getTitle(),
            $article->get('body')->value ?? ''
        );

        if (!$result['success']) {
            return $result;
        }

        $data = $result['data'];

        // Actualizar artículo con sugerencias de IA.
        if (!empty($data['answer_capsule'])) {
            $article->set('answer_capsule', $data['answer_capsule']);
        }
        if (!empty($data['seo_title'])) {
            $article->set('seo_title', $data['seo_title']);
        }
        if (!empty($data['seo_description'])) {
            $article->set('seo_description', $data['seo_description']);
        }

        $article->save();

        return [
            'success' => TRUE,
            'data' => $data,
            'article_id' => $articleId,
        ];
    }

}
