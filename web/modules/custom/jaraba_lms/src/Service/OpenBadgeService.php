<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

/**
 * Servicio para emisión y verificación de Open Badges 3.0.
 *
 * PROPÓSITO:
 * Implementa el estándar Open Badges 3.0 (basado en Verifiable Credentials)
 * para emitir badges digitales verificables al completar cursos.
 *
 * FLUJO:
 * 1. Usuario completa curso con éxito
 * 2. Sistema genera badge JSON-LD con BadgeClass y Assertion
 * 3. Badge se almacena y se genera URL pública de verificación
 * 4. Usuario puede compartir badge en LinkedIn, CV, etc.
 *
 * @see https://www.imsglobal.org/spec/ob/v3p0
 */
class OpenBadgeService
{

    /**
     * Entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * File system.
     *
     * @var \Drupal\Core\File\FileSystemInterface
     */
    protected FileSystemInterface $fileSystem;

    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        FileSystemInterface $fileSystem,
        LoggerChannelFactoryInterface $loggerFactory
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->fileSystem = $fileSystem;
        $this->logger = $loggerFactory->get('jaraba_lms');
    }

    /**
     * Emite un badge al completar un curso.
     *
     * @param int $courseId
     *   ID del curso completado.
     * @param int $userId
     *   ID del usuario que completó el curso.
     * @param array $metadata
     *   Metadatos adicionales (score, fecha, etc).
     *
     * @return array|null
     *   Datos del badge emitido o null si falla.
     */
    public function issueBadge(int $courseId, int $userId, array $metadata = []): ?array
    {
        try {
            $courseStorage = $this->entityTypeManager->getStorage('lms_course');
            $userStorage = $this->entityTypeManager->getStorage('user');

            $course = $courseStorage->load($courseId);
            $user = $userStorage->load($userId);

            if (!$course || !$user) {
                $this->logger->error('Cannot issue badge: course or user not found');
                return NULL;
            }

            // Generar UUID único para el badge
            $badgeUuid = Uuid::uuid4()->toString();

            // Construir BadgeClass (la definición del badge)
            $badgeClass = $this->buildBadgeClass($course);

            // Construir Assertion (la emisión específica)
            $assertion = $this->buildAssertion($badgeUuid, $user, $course, $badgeClass, $metadata);

            // Guardar badge en entidad
            $badgeData = [
                'uuid' => $badgeUuid,
                'course_id' => $courseId,
                'user_id' => $userId,
                'badge_class' => $badgeClass,
                'assertion' => $assertion,
                'verification_url' => $this->getVerificationUrl($badgeUuid),
                'issued_at' => time(),
                'metadata' => $metadata,
            ];

            // Guardar en entidad o archivo
            $this->saveBadge($badgeData);

            $this->logger->info('Badge issued: @uuid for user @user on course @course', [
                '@uuid' => $badgeUuid,
                '@user' => $userId,
                '@course' => $courseId,
            ]);

            return $badgeData;
        } catch (\Exception $e) {
            $this->logger->error('Error issuing badge: @error', ['@error' => $e->getMessage()]);
            return NULL;
        }
    }

    /**
     * Construye el BadgeClass según Open Badges 3.0.
     */
    protected function buildBadgeClass(object $course): array
    {
        $baseUrl = \Drupal::request()->getSchemeAndHttpHost();

        return [
            '@context' => [
                'https://www.w3.org/2018/credentials/v1',
                'https://purl.imsglobal.org/spec/ob/v3p0/context.json',
            ],
            'id' => $baseUrl . '/badge-class/' . $course->id(),
            'type' => ['VerifiableCredential', 'OpenBadgeCredential'],
            'name' => $course->label(),
            'description' => strip_tags($course->get('description')->value ?? ''),
            'criteria' => [
                'narrative' => 'Complete all lessons and pass the final assessment with 70% or higher.',
            ],
            'image' => [
                'type' => 'Image',
                'id' => $baseUrl . '/badge-images/course-' . $course->id() . '.png',
            ],
            'issuer' => [
                'type' => 'Profile',
                'id' => $baseUrl,
                'name' => 'Jaraba Impact Platform',
                'url' => $baseUrl,
            ],
        ];
    }

    /**
     * Construye la Assertion (credencial emitida) según Open Badges 3.0.
     */
    protected function buildAssertion(
        string $badgeUuid,
        object $user,
        object $course,
        array $badgeClass,
        array $metadata
    ): array {
        $baseUrl = \Drupal::request()->getSchemeAndHttpHost();
        $issuedOn = date('c'); // ISO 8601

        return [
            '@context' => [
                'https://www.w3.org/2018/credentials/v1',
                'https://purl.imsglobal.org/spec/ob/v3p0/context.json',
            ],
            'id' => $baseUrl . '/verify/' . $badgeUuid,
            'type' => ['VerifiableCredential', 'OpenBadgeCredential'],
            'issuer' => $badgeClass['issuer'],
            'issuanceDate' => $issuedOn,
            'credentialSubject' => [
                'type' => 'AchievementSubject',
                'identifier' => [
                    [
                        'type' => 'IdentityHash',
                        'identityHash' => 'sha256$' . hash('sha256', $user->getEmail()),
                        'hashed' => TRUE,
                    ],
                ],
                'achievement' => [
                    'type' => 'Achievement',
                    'id' => $badgeClass['id'],
                    'name' => $badgeClass['name'],
                    'description' => $badgeClass['description'],
                    'criteria' => $badgeClass['criteria'],
                    'image' => $badgeClass['image'],
                ],
                'result' => [
                    [
                        'type' => 'Result',
                        'achievedLevel' => $metadata['level'] ?? 'passed',
                        'score' => $metadata['score'] ?? NULL,
                    ],
                ],
            ],
            'evidence' => [
                [
                    'type' => 'Evidence',
                    'id' => $baseUrl . '/enrollment/' . ($metadata['enrollment_id'] ?? ''),
                    'name' => 'Course Completion Record',
                    'description' => 'Verified completion of all course modules',
                ],
            ],
        ];
    }

    /**
     * Genera la URL pública de verificación del badge.
     */
    public function getVerificationUrl(string $badgeUuid): string
    {
        return Url::fromRoute('jaraba_lms.badge.verify', ['badge_uuid' => $badgeUuid], ['absolute' => TRUE])->toString();
    }

    /**
     * Verifica un badge dado su UUID.
     *
     * @param string $badgeUuid
     *   UUID del badge a verificar.
     *
     * @return array|null
     *   Datos del badge si es válido, null si no existe.
     */
    public function verifyBadge(string $badgeUuid): ?array
    {
        // Buscar en archivos de badges
        $badgePath = 'public://badges/' . $badgeUuid . '.json';

        if (!file_exists($badgePath)) {
            $badgePath = $this->fileSystem->realpath("public://badges/{$badgeUuid}.json");
        }

        if ($badgePath && file_exists($badgePath)) {
            $content = file_get_contents($badgePath);
            $badge = json_decode($content, TRUE);

            if ($badge) {
                return [
                    'valid' => TRUE,
                    'badge' => $badge,
                    'verified_at' => date('c'),
                ];
            }
        }

        return NULL;
    }

    /**
     * Guarda el badge en formato JSON.
     */
    protected function saveBadge(array $badgeData): void
    {
        // Asegurar que existe el directorio
        $directory = 'public://badges';
        $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

        // Guardar como JSON
        $filepath = $directory . '/' . $badgeData['uuid'] . '.json';
        file_put_contents($filepath, json_encode($badgeData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Obtiene todos los badges de un usuario.
     *
     * @param int $userId
     *   ID del usuario.
     *
     * @return array
     *   Array de badges del usuario.
     */
    public function getUserBadges(int $userId): array
    {
        $badges = [];
        $directory = 'public://badges';

        $files = glob($this->fileSystem->realpath($directory) . '/*.json');
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $badge = json_decode($content, TRUE);

            if ($badge && ($badge['user_id'] ?? 0) == $userId) {
                $badges[] = $badge;
            }
        }

        // Ordenar por fecha de emisión (más reciente primero)
        usort($badges, fn($a, $b) => ($b['issued_at'] ?? 0) <=> ($a['issued_at'] ?? 0));

        return $badges;
    }

    /**
     * Genera JSON-LD para LinkedIn u otros consumidores.
     *
     * @param string $badgeUuid
     *   UUID del badge.
     *
     * @return string
     *   JSON-LD listo para compartir.
     */
    public function getShareableJson(string $badgeUuid): string
    {
        $badge = $this->verifyBadge($badgeUuid);

        if (!$badge) {
            return json_encode(['error' => 'Badge not found']);
        }

        return json_encode($badge['badge']['assertion'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

}
