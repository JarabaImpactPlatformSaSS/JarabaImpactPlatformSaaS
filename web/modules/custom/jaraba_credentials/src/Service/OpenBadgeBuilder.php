<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Service;

use Drupal\Component\Datetime\TimeInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Constructor de credenciales Open Badge 3.0 JSON-LD.
 *
 * Genera estructuras JSON-LD conformes al estándar Open Badges 3.0.
 *
 * @see https://www.imsglobal.org/spec/ob/v3p0/
 */
class OpenBadgeBuilder
{

    /**
     * Contextos JSON-LD para OB3.
     */
    protected const CONTEXTS = [
        'https://www.w3.org/2018/credentials/v1',
        'https://purl.imsglobal.org/spec/ob/v3p0/context-3.0.2.json',
    ];

    /**
     * Request stack para obtener el host.
     */
    protected RequestStack $requestStack;

    /**
     * Servicio de tiempo.
     */
    protected TimeInterface $time;

    /**
     * Constructor del servicio.
     *
     * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
     *   Stack de requests.
     * @param \Drupal\Component\Datetime\TimeInterface $time
     *   Servicio de tiempo.
     */
    public function __construct(RequestStack $requestStack, TimeInterface $time)
    {
        $this->requestStack = $requestStack;
        $this->time = $time;
    }

    /**
     * Construye una credencial OB3 completa.
     *
     * @param array $params
     *   Parámetros de la credencial:
     *   - uuid: UUID de la credencial
     *   - issuer: Array con datos del emisor
     *   - template: Array con datos del template
     *   - recipient: Array con datos del receptor
     *   - issued_on: Timestamp de emisión
     *   - expires_on: Timestamp de expiración (opcional)
     *   - evidence: Array de evidencias (opcional)
     *
     * @return array
     *   Estructura JSON-LD OB3.
     */
    public function buildCredential(array $params): array
    {
        $uuid = $params['uuid'];
        $baseUrl = $this->getBaseUrl();

        $credential = [
            '@context' => self::CONTEXTS,
            'id' => "{$baseUrl}/verify/{$uuid}",
            'type' => ['VerifiableCredential', 'OpenBadgeCredential'],
            'issuer' => $this->buildIssuer($params['issuer']),
            'issuanceDate' => $this->formatDate($params['issued_on']),
            'credentialSubject' => $this->buildCredentialSubject($params),
        ];

        // Agregar expiración si existe
        if (!empty($params['expires_on'])) {
            $credential['expirationDate'] = $this->formatDate($params['expires_on']);
        }

        // Agregar evidencia si existe
        if (!empty($params['evidence'])) {
            $credential['evidence'] = $this->buildEvidence($params['evidence']);
        }

        return $credential;
    }

    /**
     * Construye el objeto issuer para OB3.
     *
     * @param array $issuer
     *   Datos del emisor.
     *
     * @return array
     *   Objeto issuer OB3.
     */
    protected function buildIssuer(array $issuer): array
    {
        return [
            'id' => $issuer['url'] ?? $this->getBaseUrl(),
            'type' => 'Profile',
            'name' => $issuer['name'] ?? 'Jaraba Impact Platform',
            'email' => $issuer['email'] ?? NULL,
            'url' => $issuer['url'] ?? NULL,
            'image' => $issuer['image'] ?? NULL,
        ];
    }

    /**
     * Construye el objeto credentialSubject para OB3.
     *
     * @param array $params
     *   Parámetros de la credencial.
     *
     * @return array
     *   Objeto credentialSubject OB3.
     */
    protected function buildCredentialSubject(array $params): array
    {
        $recipient = $params['recipient'];
        $template = $params['template'];

        return [
            'id' => "did:email:{$recipient['email']}",
            'type' => 'AchievementSubject',
            'name' => $recipient['name'] ?? NULL,
            'achievement' => [
                'id' => "{$this->getBaseUrl()}/badges/{$template['machine_name']}",
                'type' => 'Achievement',
                'name' => $template['name'],
                'description' => $template['description'] ?? NULL,
                'criteria' => [
                    'type' => 'Criteria',
                    'narrative' => $template['criteria'] ?? NULL,
                ],
                'image' => $template['image'] ?? NULL,
                'achievementType' => $this->mapCredentialType($template['credential_type'] ?? 'badge'),
            ],
        ];
    }

    /**
     * Construye el array de evidencias OB3.
     *
     * @param array $evidence
     *   Datos de evidencia.
     *
     * @return array
     *   Array de objetos Evidence OB3.
     */
    protected function buildEvidence(array $evidence): array
    {
        $result = [];

        foreach ($evidence as $item) {
            $result[] = [
                'type' => 'Evidence',
                'id' => $item['url'] ?? NULL,
                'name' => $item['name'] ?? NULL,
                'description' => $item['description'] ?? NULL,
                'genre' => $item['genre'] ?? 'Activity',
            ];
        }

        return $result;
    }

    /**
     * Agrega la prueba criptográfica a la credencial.
     *
     * @param array $credential
     *   La credencial sin firmar.
     * @param string $signature
     *   La firma en Base64.
     * @param string $publicKeyId
     *   ID de la clave pública.
     *
     * @return array
     *   Credencial con proof.
     */
    public function addProof(array $credential, string $signature, string $publicKeyId): array
    {
        $credential['proof'] = [
            'type' => 'Ed25519Signature2020',
            'created' => $this->formatDate($this->time->getRequestTime()),
            'proofPurpose' => 'assertionMethod',
            'verificationMethod' => $publicKeyId,
            'proofValue' => $signature,
        ];

        return $credential;
    }

    /**
     * Serializa la credencial para firma.
     *
     * @param array $credential
     *   La credencial a serializar.
     *
     * @return string
     *   JSON normalizado para firma.
     */
    public function serializeForSigning(array $credential): string
    {
        // Remover proof existente si hay
        unset($credential['proof']);

        // Ordenar claves recursivamente para normalización
        $this->sortKeysRecursive($credential);

        return json_encode($credential, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Ordena las claves del array recursivamente.
     *
     * @param array &$array
     *   Array a ordenar.
     */
    protected function sortKeysRecursive(array &$array): void
    {
        ksort($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->sortKeysRecursive($value);
            }
        }
    }

    /**
     * Mapea el tipo de credencial interno a OB3 achievementType.
     *
     * @param string $type
     *   Tipo interno.
     *
     * @return string
     *   Tipo OB3.
     */
    protected function mapCredentialType(string $type): string
    {
        return match ($type) {
            'course_badge' => 'CourseBadge',
            'path_certificate' => 'Certificate',
            'skill_endorsement' => 'Endorsement',
            'achievement' => 'Achievement',
            'diploma' => 'Diploma',
            default => 'Badge',
        };
    }

    /**
     * Formatea un timestamp como ISO 8601.
     *
     * @param int $timestamp
     *   El timestamp.
     *
     * @return string
     *   Fecha formateada.
     */
    protected function formatDate(int $timestamp): string
    {
        return gmdate('Y-m-d\TH:i:s\Z', $timestamp);
    }

    /**
     * Obtiene la URL base del sitio.
     *
     * @return string
     *   URL base.
     */
    protected function getBaseUrl(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            return $request->getSchemeAndHttpHost();
        }
        return 'https://jaraba.es';
    }

}
