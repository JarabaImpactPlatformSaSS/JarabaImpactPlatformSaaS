<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controlador API para credenciales.
 */
class CredentialsApiController extends ControllerBase
{

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static();
    }

    /**
     * Lista las credenciales del usuario actual.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Lista de credenciales en JSON.
     */
    public function list(): JsonResponse
    {
        $user = $this->currentUser();

        $credentials = $this->entityTypeManager()->getStorage('issued_credential')
            ->loadByProperties([
                'recipient_id' => $user->id(),
            ]);

        $data = [];
        foreach ($credentials as $credential) {
            /** @var \Drupal\jaraba_credentials\Entity\IssuedCredential $credential */
            $data[] = [
                'uuid' => $credential->uuid(),
                'status' => $credential->get('status')->value,
                'issued_on' => $credential->get('issued_on')->value,
                'expires_on' => $credential->get('expires_on')->value,
                'verification_url' => $credential->get('verification_url')->value,
            ];
        }

        return new JsonResponse(['credentials' => $data]);
    }

    /**
     * Obtiene una credencial específica.
     *
     * @param string $uuid
     *   UUID de la credencial.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Datos de la credencial.
     */
    public function get(string $uuid): JsonResponse
    {
        $credentials = $this->entityTypeManager()->getStorage('issued_credential')
            ->loadByProperties(['uuid' => $uuid]);

        if (empty($credentials)) {
            return new JsonResponse(['error' => 'Credential not found'], 404);
        }

        /** @var \Drupal\jaraba_credentials\Entity\IssuedCredential $credential */
        $credential = reset($credentials);

        $ob3Json = $credential->get('ob3_json')->value ?? '';
        $ob3Data = $ob3Json ? json_decode($ob3Json, TRUE) : NULL;

        return new JsonResponse([
            'uuid' => $credential->uuid(),
            'status' => $credential->get('status')->value,
            'issued_on' => $credential->get('issued_on')->value,
            'expires_on' => $credential->get('expires_on')->value,
            'verification_url' => $credential->get('verification_url')->value,
            'ob3' => $ob3Data,
        ]);
    }

}
