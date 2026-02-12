<?php

declare(strict_types=1);

namespace Drupal\jaraba_sepe_teleformacion\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_sepe_teleformacion\Service\SepeSoapService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador para el Web Service SOAP del SEPE.
 *
 * Expone el endpoint /sepe/ws/seguimiento que implementa las operaciones
 * SOAP requeridas por la Orden TMS/369/2019.
 */
class SepeSoapController extends ControllerBase
{

    /**
     * El servicio SOAP.
     */
    protected SepeSoapService $soapService;

    /**
     * El logger.
     */
    protected LoggerInterface $logger;

    /**
     * Constructor.
     */
    public function __construct(SepeSoapService $soap_service, LoggerInterface $logger)
    {
        $this->soapService = $soap_service;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_sepe_teleformacion.soap_service'),
            $container->get('logger.channel.sepe_teleformacion')
        );
    }

    /**
     * Maneja las peticiones SOAP entrantes.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *   Respuesta SOAP XML.
     */
    public function handle(Request $request): Response
    {
        $soapRequest = $request->getContent();

        if (empty($soapRequest)) {
            return $this->wsdl();
        }

        $this->logger->info('Petición SOAP recibida: @bytes bytes', [
            '@bytes' => strlen($soapRequest),
        ]);

        try {
            // Parsear la petición SOAP.
            $xml = simplexml_load_string($soapRequest);
            if ($xml === FALSE) {
                throw new \Exception('XML inválido');
            }

            // Registrar namespaces SOAP.
            $xml->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
            $xml->registerXPathNamespace('sepe', 'http://sepe.es/ws/seguimiento');

            // Obtener el body de la petición.
            $body = $xml->xpath('//soap:Body/*');
            if (empty($body)) {
                throw new \Exception('SOAP Body vacío');
            }

            $operation = $body[0]->getName();
            $response = $this->executeOperation($operation, $body[0]);

            return $this->createSoapResponse($operation, $response);

        } catch (\Exception $e) {
            $this->logger->error('Error SOAP: @message', ['@message' => $e->getMessage()]);
            return $this->createSoapFault('Server', $e->getMessage());
        }
    }

    /**
     * Ejecuta una operación SOAP.
     *
     * @param string $operation
     *   Nombre de la operación.
     * @param \SimpleXMLElement $params
     *   Parámetros de la operación.
     *
     * @return array
     *   Resultado de la operación.
     */
    protected function executeOperation(string $operation, \SimpleXMLElement $params): array
    {
        return match ($operation) {
            'ObtenerDatosCentro' => $this->soapService->obtenerDatosCentro(),
            'CrearAccion' => $this->soapService->crearAccion((string) $params->idAccion),
            'ObtenerListaAcciones' => ['acciones' => $this->soapService->obtenerListaAcciones()],
            'ObtenerDatosAccion' => $this->soapService->obtenerDatosAccion((string) $params->idAccion),
            'ObtenerParticipantes' => ['participantes' => $this->soapService->obtenerParticipantes((string) $params->idAccion)],
            'ObtenerSeguimiento' => $this->soapService->obtenerSeguimiento(
                (string) $params->idAccion,
                (string) $params->dni
            ),
            default => throw new \Exception("Operación no soportada: $operation"),
        };
    }

    /**
     * Crea una respuesta SOAP válida.
     *
     * @param string $operation
     *   Nombre de la operación.
     * @param array $data
     *   Datos de respuesta.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *   Respuesta HTTP con XML SOAP.
     */
    protected function createSoapResponse(string $operation, array $data): Response
    {
        $dataXml = $this->arrayToXml($data);

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" 
               xmlns:sepe="http://sepe.es/ws/seguimiento">
  <soap:Body>
    <sepe:{$operation}Response>
      {$dataXml}
    </sepe:{$operation}Response>
  </soap:Body>
</soap:Envelope>
XML;

        return new Response($xml, 200, [
            'Content-Type' => 'text/xml; charset=utf-8',
        ]);
    }

    /**
     * Crea una respuesta SOAP Fault.
     *
     * @param string $code
     *   Código de error.
     * @param string $message
     *   Mensaje de error.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *   Respuesta HTTP con SOAP Fault.
     */
    protected function createSoapFault(string $code, string $message): Response
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <soap:Fault>
      <faultcode>soap:{$code}</faultcode>
      <faultstring>{$message}</faultstring>
    </soap:Fault>
  </soap:Body>
</soap:Envelope>
XML;

        return new Response($xml, 500, [
            'Content-Type' => 'text/xml; charset=utf-8',
        ]);
    }

    /**
     * Devuelve el fichero WSDL.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *   Respuesta con el WSDL.
     */
    public function wsdl(): Response
    {
        $wsdlPath = \Drupal::service('extension.list.module')
            ->getPath('jaraba_sepe_teleformacion') . '/wsdl/seguimiento-teleformacion.wsdl';

        if (!file_exists($wsdlPath)) {
            return new Response('WSDL no encontrado', 404);
        }

        $wsdl = file_get_contents($wsdlPath);

        return new Response($wsdl, 200, [
            'Content-Type' => 'text/xml; charset=utf-8',
        ]);
    }

    /**
     * Convierte un array a XML.
     *
     * @param array $data
     *   Datos a convertir.
     * @param string $parentKey
     *   Clave padre para elementos de array.
     *
     * @return string
     *   XML string.
     */
    protected function arrayToXml(array $data, string $parentKey = 'item'): string
    {
        $xml = '';
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $key = $parentKey;
            }
            if (is_array($value)) {
                $xml .= "<{$key}>" . $this->arrayToXml($value, $key) . "</{$key}>";
            } else {
                $xml .= "<{$key}>" . htmlspecialchars((string) $value) . "</{$key}>";
            }
        }
        return $xml;
    }

}
