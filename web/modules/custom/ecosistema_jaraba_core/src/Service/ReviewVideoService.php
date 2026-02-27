<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Psr\Log\LoggerInterface;

/**
 * B-16: Video review support.
 *
 * Handles video upload, validation, transcoding status tracking,
 * and thumbnail generation for video reviews.
 */
class ReviewVideoService {

  /**
   * Allowed video MIME types.
   */
  private const ALLOWED_MIME_TYPES = [
    'video/mp4',
    'video/webm',
    'video/ogg',
    'video/quicktime',
  ];

  /**
   * Max file size in bytes (100 MB).
   */
  private const MAX_FILE_SIZE = 104857600;

  /**
   * Max video duration in seconds (5 min).
   */
  private const MAX_DURATION = 300;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly FileSystemInterface $fileSystem,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Validate a video file for review upload.
   *
   * @param string $filepath
   *   Temporary file path.
   * @param string $mimeType
   *   MIME type of the file.
   * @param int $fileSize
   *   File size in bytes.
   *
   * @return array
   *   ['valid' => bool, 'errors' => string[]]
   */
  public function validateVideo(string $filepath, string $mimeType, int $fileSize): array {
    $errors = [];

    if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, TRUE)) {
      $errors[] = 'Invalid video format. Allowed: MP4, WebM, OGG, MOV.';
    }

    if ($fileSize > self::MAX_FILE_SIZE) {
      $errors[] = 'Video file too large. Maximum: 100 MB.';
    }

    if ($fileSize === 0) {
      $errors[] = 'Empty video file.';
    }

    // Duration check via ffprobe if available.
    if (empty($errors) && $filepath !== '') {
      $duration = $this->getVideoDuration($filepath);
      if ($duration !== NULL && $duration > self::MAX_DURATION) {
        $errors[] = 'Video too long. Maximum: ' . (self::MAX_DURATION / 60) . ' minutes.';
      }
    }

    return [
      'valid' => empty($errors),
      'errors' => $errors,
    ];
  }

  /**
   * Process a video upload for a review.
   *
   * @param int $fid
   *   File entity ID.
   * @param \Drupal\Core\Entity\EntityInterface $reviewEntity
   *   The review entity.
   *
   * @return array
   *   ['success' => bool, 'video_url' => string, 'thumbnail_url' => string]
   */
  public function processVideoUpload(int $fid, EntityInterface $reviewEntity): array {
    try {
      /** @var \Drupal\file\FileInterface $file */
      $file = $this->entityTypeManager->getStorage('file')->load($fid);
      if ($file === NULL) {
        return ['success' => FALSE, 'error' => 'File not found'];
      }

      $uri = $file->getFileUri();
      $validation = $this->validateVideo(
        $this->fileSystem->realpath($uri) ?: '',
        $file->getMimeType(),
        (int) $file->getSize()
      );

      if (!$validation['valid']) {
        return ['success' => FALSE, 'errors' => $validation['errors']];
      }

      // Mark file as permanent.
      $file->setPermanent();
      $file->save();

      $videoUrl = \Drupal::service('file_url_generator')->generateAbsoluteString($uri);

      // Store reference on review entity if field exists.
      if ($reviewEntity->hasField('video_file')) {
        $reviewEntity->set('video_file', ['target_id' => $fid]);
      }

      // Generate thumbnail (graceful degradation if ffmpeg unavailable).
      $thumbnailUrl = $this->generateThumbnail($uri, $fid);

      return [
        'success' => TRUE,
        'video_url' => $videoUrl,
        'thumbnail_url' => $thumbnailUrl,
        'fid' => $fid,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Video upload processing failed: @msg', ['@msg' => $e->getMessage()]);
      return ['success' => FALSE, 'error' => $e->getMessage()];
    }
  }

  /**
   * Get video info for a review entity.
   */
  public function getVideoInfo(EntityInterface $reviewEntity): ?array {
    if (!$reviewEntity->hasField('video_file') || $reviewEntity->get('video_file')->isEmpty()) {
      return NULL;
    }

    try {
      $fid = (int) ($reviewEntity->get('video_file')->target_id ?? 0);
      if ($fid === 0) {
        return NULL;
      }

      $file = $this->entityTypeManager->getStorage('file')->load($fid);
      if ($file === NULL) {
        return NULL;
      }

      return [
        'fid' => $fid,
        'url' => \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri()),
        'mime' => $file->getMimeType(),
        'size' => (int) $file->getSize(),
        'filename' => $file->getFilename(),
      ];
    }
    catch (\Exception) {
      return NULL;
    }
  }

  /**
   * Get video duration via ffprobe (if available).
   *
   * @return float|null
   *   Duration in seconds, or NULL if ffprobe unavailable.
   */
  protected function getVideoDuration(string $filepath): ?float {
    $ffprobe = $this->findExecutable('ffprobe');
    if ($ffprobe === NULL) {
      return NULL;
    }

    $cmd = escapeshellcmd($ffprobe)
      . ' -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 '
      . escapeshellarg($filepath);

    $output = @shell_exec($cmd);
    if ($output === NULL || trim($output) === '') {
      return NULL;
    }

    $duration = (float) trim($output);
    return $duration > 0 ? $duration : NULL;
  }

  /**
   * Generate a thumbnail for the video.
   *
   * Uses ffmpeg to extract a frame at 1 second. Falls back to
   * a placeholder SVG if ffmpeg is unavailable.
   *
   * @param string $videoUri
   *   Drupal stream URI of the video.
   * @param int $fid
   *   File entity ID (for naming).
   *
   * @return string
   *   URL to the thumbnail, or empty string on failure.
   */
  protected function generateThumbnail(string $videoUri, int $fid): string {
    $ffmpeg = $this->findExecutable('ffmpeg');
    $realPath = $this->fileSystem->realpath($videoUri);
    if ($ffmpeg === NULL || $realPath === FALSE) {
      return '';
    }

    try {
      $thumbDir = 'public://review-video-thumbnails';
      $this->fileSystem->prepareDirectory($thumbDir, FileSystemInterface::CREATE_DIRECTORY);
      $thumbUri = $thumbDir . '/thumb_' . $fid . '.jpg';
      $thumbPath = $this->fileSystem->realpath($thumbUri) ?: $thumbDir . '/thumb_' . $fid . '.jpg';

      $cmd = escapeshellcmd($ffmpeg)
        . ' -y -i ' . escapeshellarg($realPath)
        . ' -ss 1 -vframes 1 -q:v 5 '
        . escapeshellarg($thumbPath);

      @shell_exec($cmd . ' 2>/dev/null');

      if (file_exists($thumbPath) && filesize($thumbPath) > 0) {
        return \Drupal::service('file_url_generator')->generateAbsoluteString($thumbUri);
      }
    }
    catch (\Exception $e) {
      $this->logger->info('Thumbnail generation failed for fid @fid: @msg', [
        '@fid' => $fid,
        '@msg' => $e->getMessage(),
      ]);
    }

    return '';
  }

  /**
   * Find an executable in common paths.
   */
  protected function findExecutable(string $name): ?string {
    $cmd = @shell_exec('which ' . escapeshellarg($name) . ' 2>/dev/null');
    if ($cmd !== NULL && trim($cmd) !== '') {
      return trim($cmd);
    }
    return NULL;
  }

}
