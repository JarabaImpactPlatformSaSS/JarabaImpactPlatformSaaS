<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\media\Entity\Media;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador API para el browser de medios del Canvas Editor.
 *
 * Endpoints para gestión de assets del tenant:
 * - GET /api/v1/page-builder/assets - Lista assets del tenant
 * - POST /api/v1/page-builder/assets - Sube nuevo asset
 * - GET /api/v1/page-builder/assets/{mid} - Detalle de un asset
 *
 * @see docs/tecnicos/20260204-Media_Browser_Integration.md
 */
class MediaBrowserController extends ControllerBase
{

    /**
     * File system service.
     *
     * @var \Drupal\Core\File\FileSystemInterface
     */
    protected FileSystemInterface $fileSystem;

    /**
     * File repository service.
     *
     * @var \Drupal\file\FileRepositoryInterface
     */
    protected FileRepositoryInterface $fileRepository;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        $instance = new static();
        $instance->entityTypeManager = $container->get('entity_type.manager');
        $instance->currentUser = $container->get('current_user');
        $instance->fileSystem = $container->get('file_system');
        $instance->fileRepository = $container->get('file.repository');
        return $instance;
    }

    /**
     * GET /api/v1/page-builder/assets
     *
     * Lista assets de medios del tenant actual.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   Request HTTP con query params.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON con array de assets.
     */
    public function listAssets(Request $request): JsonResponse
    {
        $type = $request->query->get('type', 'all');
        $search = $request->query->get('search', '');
        $limit = (int) $request->query->get('limit', 24);
        $offset = (int) $request->query->get('offset', 0);

        // Limitar para evitar abuse.
        $limit = min($limit, 100);

        try {
            $mediaStorage = $this->entityTypeManager->getStorage('media');
            $query = $mediaStorage->getQuery()
                ->accessCheck(TRUE)
                ->sort('created', 'DESC')
                ->range($offset, $limit);

            // Filtrar por tipo de media.
            if ($type !== 'all') {
                $bundleMapping = [
                    'image' => 'image',
                    'video' => 'remote_video',
                    'document' => 'document',
                ];
                if (isset($bundleMapping[$type])) {
                    $query->condition('bundle', $bundleMapping[$type]);
                }
            } else {
                // Por defecto, mostrar solo imágenes y vídeos (más útiles para el Page Builder).
                $query->condition('bundle', ['image', 'remote_video'], 'IN');
            }

            // Filtrar por búsqueda en el nombre.
            if (!empty($search)) {
                $query->condition('name', '%' . $search . '%', 'LIKE');
            }

            $mediaIds = $query->execute();

            // Contar total para paginación.
            $countQuery = $mediaStorage->getQuery()
                ->accessCheck(TRUE);
            if ($type !== 'all' && isset($bundleMapping[$type])) {
                $countQuery->condition('bundle', $bundleMapping[$type]);
            } else {
                $countQuery->condition('bundle', ['image', 'remote_video'], 'IN');
            }
            if (!empty($search)) {
                $countQuery->condition('name', '%' . $search . '%', 'LIKE');
            }
            $total = $countQuery->count()->execute();

            $items = [];
            if (!empty($mediaIds)) {
                $mediaEntities = $mediaStorage->loadMultiple($mediaIds);

                foreach ($mediaEntities as $media) {
                    $items[] = $this->mediaToAsset($media);
                }
            }

            return new JsonResponse([
                'items' => $items,
                'total' => (int) $total,
                'limit' => $limit,
                'offset' => $offset,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $this->t('Error al cargar assets: @message', ['@message' => $e->getMessage()]),
            ], 500);
        }
    }

    /**
     * POST /api/v1/page-builder/assets
     *
     * Sube un nuevo asset al Media Library del tenant.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   Request HTTP con el archivo a subir.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON con el asset creado.
     */
    public function uploadAsset(Request $request): JsonResponse
    {
        try {
            // Obtener archivo del request.
            $file = $request->files->get('file');
            if (!$file) {
                return new JsonResponse([
                    'error' => $this->t('No se recibió ningún archivo.'),
                ], 400);
            }

            // Validar tipo de archivo.
            $allowedMimes = [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
                'image/svg+xml',
                'video/mp4',
                'video/webm',
                'application/pdf',
            ];

            if (!in_array($file->getMimeType(), $allowedMimes)) {
                return new JsonResponse([
                    'error' => $this->t('Tipo de archivo no permitido: @type', ['@type' => $file->getMimeType()]),
                ], 400);
            }

            // Validar tamaño (máximo 10MB).
            $maxSize = 10 * 1024 * 1024;
            if ($file->getSize() > $maxSize) {
                return new JsonResponse([
                    'error' => $this->t('El archivo excede el tamaño máximo de 10MB.'),
                ], 400);
            }

            // Determinar directorio de destino.
            $directory = 'public://page-builder/assets/' . date('Y-m');
            $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

            // Generar nombre único.
            $filename = $this->fileSystem->createFilename($file->getClientOriginalName(), $directory);

            // Guardar archivo.
            $destination = $directory . '/' . $filename;
            $fileEntity = $this->fileRepository->writeData(
                file_get_contents($file->getPathname()),
                $destination,
                FileSystemInterface::EXISTS_RENAME
            );

            if (!$fileEntity) {
                return new JsonResponse([
                    'error' => $this->t('Error al guardar el archivo.'),
                ], 500);
            }

            // Determinar bundle de media según mime type.
            $bundle = 'image';
            $sourceField = 'field_media_image';
            if (str_starts_with($file->getMimeType(), 'video/')) {
                $bundle = 'video';
                $sourceField = 'field_media_video_file';
            } elseif ($file->getMimeType() === 'application/pdf') {
                $bundle = 'document';
                $sourceField = 'field_media_document';
            }

            // Crear entidad Media.
            $media = Media::create([
                'bundle' => $bundle,
                'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'uid' => $this->currentUser->id(),
                $sourceField => [
                    'target_id' => $fileEntity->id(),
                    'alt' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                ],
            ]);
            $media->save();

            return new JsonResponse([
                'success' => TRUE,
                'asset' => $this->mediaToAsset($media),
            ], 201);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $this->t('Error al subir asset: @message', ['@message' => $e->getMessage()]),
            ], 500);
        }
    }

    /**
     * GET /api/v1/page-builder/assets/{media}
     *
     * Obtiene detalle de un asset específico.
     *
     * @param \Drupal\media\Entity\Media $media
     *   Entidad Media.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON con datos del asset.
     */
    public function getAsset(Media $media): JsonResponse
    {
        return new JsonResponse($this->mediaToAsset($media));
    }

    /**
     * Convierte una entidad Media a formato de asset para el frontend.
     *
     * @param \Drupal\media\Entity\Media $media
     *   Entidad Media.
     *
     * @return array
     *   Array con datos del asset.
     */
    protected function mediaToAsset(Media $media): array
    {
        $bundle = $media->bundle();
        $sourceFieldName = $media->getSource()->getConfiguration()['source_field'] ?? NULL;

        $url = '';
        $thumbnail = '';
        $width = 0;
        $height = 0;
        $size = 0;
        $type = 'unknown';

        // Obtener URL según el bundle.
        if ($bundle === 'image' && $media->hasField('field_media_image')) {
            $imageField = $media->get('field_media_image');
            if (!$imageField->isEmpty()) {
                $file = $imageField->entity;
                if ($file) {
                    $url = $file->createFileUrl(FALSE);
                    $size = $file->getSize();
                    $type = 'image';

                    // Obtener dimensiones.
                    $width = $imageField->width ?? 0;
                    $height = $imageField->height ?? 0;

                    // Generar thumbnail.
                    $imageStyle = $this->entityTypeManager->getStorage('image_style')->load('medium');
                    if ($imageStyle) {
                        $thumbnail = $imageStyle->buildUrl($file->getFileUri());
                    } else {
                        $thumbnail = $url;
                    }
                }
            }
        } elseif ($bundle === 'remote_video' && $media->hasField('field_media_oembed_video')) {
            $videoField = $media->get('field_media_oembed_video');
            if (!$videoField->isEmpty()) {
                $url = $videoField->value;
                $type = 'video';
                // Usar thumbnail generado por Media.
                $thumbnail = $media->getSource()->getMetadata($media, 'thumbnail_uri');
                if ($thumbnail) {
                    $thumbnail = \Drupal::service('file_url_generator')->generateAbsoluteString($thumbnail);
                }
            }
        } elseif ($bundle === 'document' && $media->hasField('field_media_document')) {
            $docField = $media->get('field_media_document');
            if (!$docField->isEmpty()) {
                $file = $docField->entity;
                if ($file) {
                    $url = $file->createFileUrl(FALSE);
                    $size = $file->getSize();
                    $type = 'document';
                    // Icono genérico para documentos.
                    $thumbnail = '/core/misc/icons/787878/document.svg';
                }
            }
        }

        return [
            'id' => (int) $media->id(),
            'name' => $media->label(),
            'url' => $url,
            'thumbnail' => $thumbnail,
            'type' => $type,
            'width' => $width,
            'height' => $height,
            'size' => $size,
            'created' => date('c', $media->getCreatedTime()),
        ];
    }

}
