<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_andalucia_ei\Service\StoExportService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador para exportación de datos al STO.
 */
class StoExportController extends ControllerBase
{

    /**
     * Constructor del controlador.
     *
     * @param \Drupal\jaraba_andalucia_ei\Service\StoExportService $stoExportService
     *   Servicio de exportación STO.
     */
    public function __construct(
        protected StoExportService $stoExportService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_andalucia_ei.sto_export')
        );
    }

    /**
     * Formulario de exportación para STO.
     *
     * @return array
     *   Render array del formulario.
     */
    public function exportForm(): array
    {
        return $this->formBuilder()->getForm('Drupal\jaraba_andalucia_ei\Form\StoExportForm');
    }

    /**
     * Descarga el paquete XML de exportación.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *   Respuesta con el XML.
     */
    public function downloadXml(): Response
    {
        // Obtener todos los participantes pendientes de sincronización.
        $participantes = $this->entityTypeManager()
            ->getStorage('programa_participante_ei')
            ->loadByProperties(['sto_sync_status' => 'pending']);

        $ids = array_keys($participantes);

        $resultado = $this->stoExportService->generarPaqueteExportacion($ids);

        if (!$resultado['success']) {
            throw new \RuntimeException($resultado['message']);
        }

        return new Response($resultado['data'], 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="andalucia_ei_' . date('Ymd_His') . '.xml"',
        ]);
    }

}
