<?php

declare(strict_types=1);

namespace Drupal\jaraba_interactive\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_interactive\Entity\InteractiveContent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador del Player de contenido interactivo.
 *
 * Renderiza el player en frontend limpio (sin regiones Drupal).
 */
class PlayerController extends ControllerBase
{

    /**
     * Renderiza el player para un contenido interactivo.
     *
     * @param \Drupal\jaraba_interactive\Entity\InteractiveContent $interactive_content
     *   El contenido a reproducir.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return array|Response
     *   El render array o respuesta AJAX.
     */
    public function render(InteractiveContent $interactive_content, Request $request): array|Response
    {
        // Extraer valores escalares de la entidad para evitar FieldItemList en Twig.
        $content_data = $this->extractContentData($interactive_content);

        // Si es AJAX (slide-panel), devolver solo el player.
        if ($request->isXmlHttpRequest()) {
            $build = $this->buildPlayerRenderArray($interactive_content, $content_data);
            $html = \Drupal::service('renderer')->renderRoot($build);
            return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        }

        // Renderizado completo para frontend limpio.
        $tenant_data = $this->getTenantBranding($interactive_content);

        return [
            '#theme' => 'interactive_player',
            '#content' => $content_data,
            '#xapi_endpoint' => '/api/v1/interactive/xapi',
            '#enrollment_id' => $this->getEnrollmentId($interactive_content),
            '#settings' => $content_data['settings'],
            '#tenant' => $tenant_data,
            '#attached' => [
                'library' => ['jaraba_interactive/player'],
                'drupalSettings' => [
                    'jarabaInteractive' => [
                        'contentId' => $content_data['id'],
                        'contentType' => $content_data['content_type'],
                        'contentData' => $content_data['content_data'],
                        'settings' => $content_data['settings'],
                        'xapiEndpoint' => '/api/v1/interactive/xapi',
                    ],
                ],
            ],
            '#cache' => [
                'tags' => [
                    'interactive_content:' . $interactive_content->id(),
                    'tenant:' . ($tenant_data['id'] ?? 0),
                ],
                'contexts' => ['user', 'session'],
                'max-age' => 3600,
            ],
        ];
    }

    /**
     * Obtiene el título del contenido.
     */
    public function getTitle(InteractiveContent $interactive_content): string
    {
        return $interactive_content->label();
    }

    /**
     * Extrae datos escalares de la entidad para uso en Twig.
     *
     * CRÍTICO: No pasar objetos FieldItemList a Twig - causan error
     * "Object of type FieldItemList cannot be printed".
     *
     * @param \Drupal\jaraba_interactive\Entity\InteractiveContent $content
     *   La entidad de contenido.
     *
     * @return array
     *   Datos procesados como valores escalares.
     */
    protected function extractContentData(InteractiveContent $content): array
    {
        // Obtener content_data como array desde el campo JSON.
        $content_data = [];
        if ($content->hasField('content_data') && !$content->get('content_data')->isEmpty()) {
            $json_value = $content->get('content_data')->value;
            if ($json_value) {
                $content_data = json_decode($json_value, TRUE) ?? [];
            }
        }

        // Obtener settings como array.
        $settings = [];
        if ($content->hasField('settings') && !$content->get('settings')->isEmpty()) {
            $json_value = $content->get('settings')->value;
            if ($json_value) {
                $settings = json_decode($json_value, TRUE) ?? [];
            }
        }

        return [
            'id' => (int) $content->id(),
            'label' => $content->label(),
            'content_type' => $content->hasField('content_type') && !$content->get('content_type')->isEmpty()
                ? $content->get('content_type')->value
                : 'question_set',
            'difficulty' => $content->hasField('difficulty') && !$content->get('difficulty')->isEmpty()
                ? $content->get('difficulty')->value
                : NULL,
            'duration_minutes' => $content->hasField('duration_minutes') && !$content->get('duration_minutes')->isEmpty()
                ? (int) $content->get('duration_minutes')->value
                : NULL,
            'content_data' => $content_data,
            'settings' => $settings,
        ];
    }

    /**
     * Construye el render array del player.
     */
    protected function buildPlayerRenderArray(InteractiveContent $content, array $content_data): array
    {
        return [
            '#theme' => 'interactive_player',
            '#content' => $content_data,
            '#xapi_endpoint' => '/api/v1/interactive/xapi',
            '#enrollment_id' => $this->getEnrollmentId($content),
            '#settings' => $content_data['settings'],
            '#attached' => [
                'library' => ['jaraba_interactive/player'],
            ],
        ];
    }

    /**
     * Obtiene el ID de inscripción desde la sesión o parámetros.
     */
    protected function getEnrollmentId(InteractiveContent $content): ?int
    {
        // Integración con jaraba_lms.
        $session = \Drupal::request()->getSession();
        return $session->get('jaraba_lms_enrollment_id');
    }

    /**
     * Obtiene los datos de branding del tenant asociado al contenido.
     *
     * @param \Drupal\jaraba_interactive\Entity\InteractiveContent $content
     *   El contenido interactivo.
     *
     * @return array
     *   Array con logo y nombre del tenant, vacío si no hay tenant.
     */
    protected function getTenantBranding(InteractiveContent $content): array
    {
        // Verificar si el contenido tiene tenant_id definido.
        if (!$content->hasField('tenant_id') || $content->get('tenant_id')->isEmpty()) {
            return ['id' => 0, 'name' => '', 'logo' => ''];
        }

        $tenant = $content->get('tenant_id')->entity;
        if (!$tenant) {
            return ['id' => 0, 'name' => '', 'logo' => ''];
        }

        // Extraer datos del grupo (tenant).
        $logo_url = '';
        if ($tenant->hasField('field_logo') && !$tenant->get('field_logo')->isEmpty()) {
            $media = $tenant->get('field_logo')->entity;
            if ($media && $media->hasField('field_media_image') && !$media->get('field_media_image')->isEmpty()) {
                $file = $media->get('field_media_image')->entity;
                if ($file) {
                    $logo_url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
                }
            }
        }

        return [
            'id' => (int) $tenant->id(),
            'name' => $tenant->label() ?? '',
            'logo' => $logo_url,
        ];
    }

}
