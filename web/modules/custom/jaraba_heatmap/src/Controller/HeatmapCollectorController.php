<?php

namespace Drupal\jaraba_heatmap\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\jaraba_heatmap\Service\HeatmapCollectorService;

/**
 * Controlador para la recolección de eventos de heatmap.
 *
 * Recibe eventos del tracker JavaScript via POST y los almacena
 * en la base de datos para posterior agregación.
 *
 * Optimizado para Beacon API:
 * - Respuesta 204 No Content para minimizar latencia
 * - Sin body de respuesta
 * - Procesamiento asíncrono cuando es posible
 *
 * Ref: Doc Técnico #180 - Native Heatmaps System
 */
class HeatmapCollectorController extends ControllerBase
{

    /**
     * Servicio de recolección de heatmaps.
     *
     * @var \Drupal\jaraba_heatmap\Service\HeatmapCollectorService
     */
    protected $collector;

    /**
     * Constructor.
     *
     * @param \Drupal\jaraba_heatmap\Service\HeatmapCollectorService $collector
     *   Servicio de recolección de eventos.
     */
    public function __construct(HeatmapCollectorService $collector)
    {
        $this->collector = $collector;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('jaraba_heatmap.collector')
        );
    }

    /**
     * Recibe y procesa eventos de heatmap del tracker.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP con payload JSON.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *   Respuesta 204 No Content para Beacon API.
     */
    public function collect(Request $request): Response
    {
        // Verificar método POST.
        if ($request->getMethod() !== 'POST') {
            return new Response('', 405);
        }

        // Decodificar payload JSON.
        $content = $request->getContent();
        $payload = json_decode($content, TRUE);

        // Validar payload mínimo.
        if (!$payload || empty($payload['events']) || !is_array($payload['events'])) {
            // 400 Bad Request pero sin body para Beacon API.
            return new Response('', 400);
        }

        // Validar campos requeridos del payload.
        $required = ['tenant_id', 'session_id', 'page'];
        foreach ($required as $field) {
            if (empty($payload[$field])) {
                return new Response('', 400);
            }
        }

        // Procesar eventos via servicio (patrón Thin Controller).
        try {
            $this->collector->processEvents($payload);
        } catch (\Exception $e) {
            // Loguear error pero retornar 204 para no bloquear cliente.
            $this->getLogger('jaraba_heatmap')->error('Error procesando eventos: @message', [
                '@message' => $e->getMessage(),
            ]);
        }

        // Respuesta 204 optimizada para Beacon API.
        $response = new Response('', 204);
        $response->headers->set('Cache-Control', 'no-store, no-cache');
        return $response;
    }

}
