
MÓDULO jaraba_sepe_teleformacion
Especificación Técnica de Implementación
Web Service SOAP para Seguimiento SEPE

JARABA IMPACT PLATFORM
Proyecto EDI Google Antigravity

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica Detallada
Código:	106_Modulo_SEPE_Teleformacion_Implementacion
Dependencias:	105_Homologacion, 08_LMS_Core
Framework:	Drupal 11 + PHP 8.2+
 
1. Estructura del Módulo Drupal
El módulo jaraba_sepe_teleformacion se organiza siguiendo las convenciones de Drupal 11:
modules/custom/jaraba_sepe_teleformacion/
├── jaraba_sepe_teleformacion.info.yml
├── jaraba_sepe_teleformacion.module
├── jaraba_sepe_teleformacion.install
├── jaraba_sepe_teleformacion.routing.yml
├── jaraba_sepe_teleformacion.services.yml
├── jaraba_sepe_teleformacion.permissions.yml
├── config/
│   ├── install/
│   │   └── jaraba_sepe_teleformacion.settings.yml
│   └── schema/
│       └── jaraba_sepe_teleformacion.schema.yml
├── src/
│   ├── Controller/
│   │   └── SepeSoapController.php
│   ├── Entity/
│   │   ├── SepeCentro.php
│   │   ├── SepeAccionFormativa.php
│   │   └── SepeParticipante.php
│   ├── Form/
│   │   ├── SepeCentroForm.php
│   │   └── SepeSettingsForm.php
│   ├── Service/
│   │   ├── SepeSoapService.php
│   │   ├── SepeDataMapper.php
│   │   └── SepeSeguimientoCalculator.php
│   └── Plugin/
│       └── rest/
│           └── resource/
│               ├── SepeAccionesResource.php
│               └── SepeParticipantesResource.php
├── templates/
│   └── sepe-informe-seguimiento.html.twig
└── wsdl/
    └── seguimiento-teleformacion.wsdl
 
2. Configuración del Módulo
2.1 jaraba_sepe_teleformacion.info.yml
name: 'Jaraba SEPE Teleformación'
type: module
description: 'Web Service SOAP para seguimiento SEPE de acciones formativas'
package: 'Jaraba Impact Platform'
core_version_requirement: ^10.3 || ^11
php: 8.2
dependencies:
  - drupal:rest
  - drupal:serialization
  - jaraba_lms:jaraba_lms
  - jaraba_core:jaraba_core
configure: jaraba_sepe_teleformacion.settings
2.2 jaraba_sepe_teleformacion.routing.yml
# SOAP Web Service endpoint
jaraba_sepe_teleformacion.soap:
  path: '/sepe/ws/seguimiento'
  defaults:
    _controller: '\Drupal\jaraba_sepe_teleformacion\Controller\SepeSoapController::handle'
  methods: [POST, GET]
  requirements:
    _permission: 'access content'

# WSDL endpoint
jaraba_sepe_teleformacion.wsdl:
  path: '/sepe/ws/seguimiento/wsdl'
  defaults:
    _controller: '\Drupal\jaraba_sepe_teleformacion\Controller\SepeSoapController::wsdl'
  methods: [GET]
  requirements:
    _permission: 'access content'

# Admin settings
jaraba_sepe_teleformacion.settings:
  path: '/admin/config/jaraba/sepe'
  defaults:
    _form: '\Drupal\jaraba_sepe_teleformacion\Form\SepeSettingsForm'
    _title: 'Configuración SEPE Teleformación'
  requirements:
    _permission: 'administer sepe teleformacion'
2.3 jaraba_sepe_teleformacion.services.yml
services:
  jaraba_sepe_teleformacion.soap_service:
    class: Drupal\jaraba_sepe_teleformacion\Service\SepeSoapService
    arguments:
      - '@entity_type.manager'
      - '@jaraba_sepe_teleformacion.data_mapper'
      - '@config.factory'
      - '@logger.factory'

  jaraba_sepe_teleformacion.data_mapper:
    class: Drupal\jaraba_sepe_teleformacion\Service\SepeDataMapper
    arguments:
      - '@entity_type.manager'
      - '@jaraba_sepe_teleformacion.seguimiento_calculator'

  jaraba_sepe_teleformacion.seguimiento_calculator:
    class: Drupal\jaraba_sepe_teleformacion\Service\SepeSeguimientoCalculator
    arguments:
      - '@entity_type.manager'
      - '@database'
 
3. Implementación de Entidades
3.1 Entidad SepeCentro (src/Entity/SepeCentro.php)
Extracto de la definición de entidad:
<?php
namespace Drupal\jaraba_sepe_teleformacion\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * @ContentEntityType(
 *   id = "sepe_centro",
 *   label = @Translation("SEPE Centro de Formación"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_sepe_teleformacion\SepeCentroListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_sepe_teleformacion\Form\SepeCentroForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *   },
 *   base_table = "sepe_centro",
 *   admin_permission = "administer sepe teleformacion",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "razon_social",
 *   },
 * )
 */
class SepeCentro extends ContentEntityBase {

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['cif'] = BaseFieldDefinition::create('string')
      ->setLabel(t('CIF/NIF'))
      ->setRequired(TRUE)
      ->addConstraint('UniqueField')
      ->setSettings(['max_length' => 9]);

    $fields['razon_social'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Razón Social'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 100]);

    $fields['codigo_sepe'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Código SEPE'))
      ->setSettings(['max_length' => 20]);

    // ... resto de campos según esquema doc 105 ...

    $fields['url_seguimiento'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('URL Servicio SOAP'))
      ->setRequired(TRUE);

    return $fields;
  }
}
 
4. Controlador SOAP Principal
4.1 SepeSoapController.php
<?php
namespace Drupal\jaraba_sepe_teleformacion\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SepeSoapController extends ControllerBase {

  protected $soapService;
  protected $logger;

  public function __construct($soap_service, $logger_factory) {
    $this->soapService = $soap_service;
    $this->logger = $logger_factory->get('sepe_teleformacion');
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('jaraba_sepe_teleformacion.soap_service'),
      $container->get('logger.factory')
    );
  }

  /**
   * Handle SOAP requests from SEPE.
   */
  public function handle(Request $request): Response {
    // Log incoming request for audit
    $this->logger->info('SEPE SOAP request from @ip', [
      '@ip' => $request->getClientIp(),
    ]);

    try {
      // Initialize SOAP server with WSDL
      $wsdlPath = $this->getWsdlPath();
      
      $server = new \SoapServer($wsdlPath, [
        'encoding' => 'UTF-8',
        'soap_version' => SOAP_1_1,
      ]);

      // Set the handler class
      $server->setObject($this->soapService);

      // Capture output
      ob_start();
      $server->handle($request->getContent());
      $soapResponse = ob_get_clean();

      // Log successful response
      $this->logger->info('SEPE SOAP response sent successfully');

      return new Response($soapResponse, 200, [
        'Content-Type' => 'text/xml; charset=utf-8',
      ]);

    } catch (\Exception $e) {
      $this->logger->error('SEPE SOAP error: @message', [
        '@message' => $e->getMessage(),
      ]);
      
      return $this->createSoapFault($e);
    }
  }

  /**
   * Serve WSDL file.
   */
  public function wsdl(): Response {
    $wsdlContent = file_get_contents($this->getWsdlPath());
    
    // Replace placeholder with actual endpoint URL
    $baseUrl = \Drupal::request()->getSchemeAndHttpHost();
    $wsdlContent = str_replace(
      '{{ENDPOINT_URL}}',
      $baseUrl . '/sepe/ws/seguimiento',
      $wsdlContent
    );

    return new Response($wsdlContent, 200, [
      'Content-Type' => 'text/xml; charset=utf-8',
    ]);
  }

  protected function getWsdlPath(): string {
    return \Drupal::service('extension.list.module')
      ->getPath('jaraba_sepe_teleformacion') . '/wsdl/seguimiento-teleformacion.wsdl';
  }
}
 
5. Servicio SOAP - Operaciones
5.1 SepeSoapService.php - Operaciones SEPE
<?php
namespace Drupal\jaraba_sepe_teleformacion\Service;

class SepeSoapService {

  protected $entityTypeManager;
  protected $dataMapper;
  protected $config;
  protected $logger;

  public function __construct($entity_type_manager, $data_mapper, $config_factory, $logger_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->dataMapper = $data_mapper;
    $this->config = $config_factory->get('jaraba_sepe_teleformacion.settings');
    $this->logger = $logger_factory->get('sepe_soap');
  }

  /**
   * OPERACIÓN: ObtenerDatosCentro
   * Devuelve datos identificativos del centro de formación.
   */
  public function ObtenerDatosCentro(): object {
    $centroId = $this->config->get('centro_activo_id');
    $centro = $this->entityTypeManager
      ->getStorage('sepe_centro')
      ->load($centroId);

    if (!$centro) {
      throw new \SoapFault('Server', 'Centro no configurado');
    }

    return $this->dataMapper->centroToSepe($centro);
  }

  /**
   * OPERACIÓN: ObtenerListaAcciones
   * Devuelve lista de IDs de acciones formativas del centro.
   */
  public function ObtenerListaAcciones(): array {
    $centroId = $this->config->get('centro_activo_id');
    
    $acciones = $this->entityTypeManager
      ->getStorage('sepe_accion_formativa')
      ->loadByProperties(['centro_id' => $centroId]);

    $ids = [];
    foreach ($acciones as $accion) {
      $ids[] = $accion->get('id_accion_sepe')->value;
    }

    return $ids;
  }

  /**
   * OPERACIÓN: ObtenerDatosAccion
   * Devuelve datos completos de una acción formativa.
   */
  public function ObtenerDatosAccion(string $idAccion): object {
    $acciones = $this->entityTypeManager
      ->getStorage('sepe_accion_formativa')
      ->loadByProperties(['id_accion_sepe' => $idAccion]);

    if (empty($acciones)) {
      throw new \SoapFault('Server', 'Acción no encontrada: ' . $idAccion);
    }

    $accion = reset($acciones);
    return $this->dataMapper->accionToSepe($accion);
  }

  /**
   * OPERACIÓN: ObtenerParticipantes
   * Devuelve lista de participantes con datos de seguimiento.
   */
  public function ObtenerParticipantes(string $idAccion): array {
    $acciones = $this->entityTypeManager
      ->getStorage('sepe_accion_formativa')
      ->loadByProperties(['id_accion_sepe' => $idAccion]);

    if (empty($acciones)) {
      throw new \SoapFault('Server', 'Acción no encontrada');
    }

    $accion = reset($acciones);
    $participantes = $this->entityTypeManager
      ->getStorage('sepe_participante')
      ->loadByProperties(['accion_id' => $accion->id()]);

    $result = [];
    foreach ($participantes as $participante) {
      $result[] = $this->dataMapper->participanteToSepe($participante);
    }

    return $result;
  }

  /**
   * OPERACIÓN: ObtenerSeguimiento
   * Devuelve seguimiento detallado de un participante específico.
   */
  public function ObtenerSeguimiento(string $idAccion, string $dni): object {
    // Buscar participante por acción y DNI
    $query = $this->entityTypeManager
      ->getStorage('sepe_participante')
      ->getQuery()
      ->condition('dni', $dni)
      ->condition('accion_id.entity.id_accion_sepe', $idAccion);

    $ids = $query->execute();

    if (empty($ids)) {
      throw new \SoapFault('Server', 'Participante no encontrado');
    }

    $participante = $this->entityTypeManager
      ->getStorage('sepe_participante')
      ->load(reset($ids));

    return $this->dataMapper->seguimientoDetalladoToSepe($participante);
  }

  /**
   * OPERACIÓN: CrearAccion
   * Crea/registra una acción formativa con el ID proporcionado por SEPE.
   */
  public function CrearAccion(string $idAccion, object $datosAccion): bool {
    // Esta operación se usa cuando SEPE asigna un ID a la acción
    $acciones = $this->entityTypeManager
      ->getStorage('sepe_accion_formativa')
      ->loadByProperties(['id_accion_sepe' => $idAccion]);

    if (!empty($acciones)) {
      // Ya existe, actualizar
      $accion = reset($acciones);
      $this->dataMapper->updateAccionFromSepe($accion, $datosAccion);
    } else {
      // Crear nueva
      $accion = $this->dataMapper->createAccionFromSepe($idAccion, $datosAccion);
    }

    $accion->save();
    return TRUE;
  }
}
 
6. Servicio de Mapeo de Datos
6.1 SepeDataMapper.php
Transforma entidades Drupal al formato esperado por el SEPE:
<?php
namespace Drupal\jaraba_sepe_teleformacion\Service;

class SepeDataMapper {

  protected $seguimientoCalculator;

  public function __construct($entity_type_manager, $seguimiento_calculator) {
    $this->entityTypeManager = $entity_type_manager;
    $this->seguimientoCalculator = $seguimiento_calculator;
  }

  /**
   * Mapea entidad SepeCentro a estructura SEPE DatosCentro.
   */
  public function centroToSepe($centro): object {
    return (object) [
      'CIF' => $centro->get('cif')->value,
      'RazonSocial' => $centro->get('razon_social')->value,
      'CodigoCentro' => $centro->get('codigo_sepe')->value ?? '',
      'Direccion' => $centro->get('direccion')->value,
      'CodigoPostal' => $centro->get('codigo_postal')->value,
      'Municipio' => $centro->get('municipio')->value,
      'Provincia' => $centro->get('provincia')->value,
      'Telefono' => $centro->get('telefono')->value,
      'Email' => $centro->get('email')->value,
      'URLPlataforma' => $centro->get('url_plataforma')->value,
    ];
  }

  /**
   * Mapea entidad SepeAccionFormativa a estructura SEPE DatosAccion.
   */
  public function accionToSepe($accion): object {
    // Contar participantes activos
    $numParticipantes = $this->entityTypeManager
      ->getStorage('sepe_participante')
      ->getQuery()
      ->condition('accion_id', $accion->id())
      ->condition('estado', 'baja', '<>')
      ->count()
      ->execute();

    return (object) [
      'IdAccion' => $accion->get('id_accion_sepe')->value,
      'CodigoEspecialidad' => $accion->get('codigo_especialidad')->value,
      'Denominacion' => $accion->get('denominacion')->value,
      'Modalidad' => $accion->get('modalidad')->value,
      'NumeroHoras' => (int) $accion->get('numero_horas')->value,
      'FechaInicio' => $accion->get('fecha_inicio')->value,
      'FechaFin' => $accion->get('fecha_fin')->value,
      'NumParticipantes' => $numParticipantes,
      'Estado' => $this->mapEstadoAccion($accion->get('estado')->value),
    ];
  }

  /**
   * Mapea entidad SepeParticipante a estructura SEPE DatosSeguimiento.
   */
  public function participanteToSepe($participante): object {
    // Recalcular datos de seguimiento desde progress_record
    $seguimiento = $this->seguimientoCalculator->calculate(
      $participante->get('enrollment_id')->target_id
    );

    return (object) [
      'DNI' => $participante->get('dni')->value,
      'Nombre' => $participante->get('nombre')->value,
      'Apellidos' => $participante->get('apellidos')->value,
      'FechaAlta' => $participante->get('fecha_alta')->value,
      'FechaBaja' => $participante->get('fecha_baja')->value ?? '',
      'HorasConectado' => round($seguimiento['horas_conectado'], 2),
      'PorcentajeProgreso' => $seguimiento['porcentaje_progreso'],
      'NumActividadesRealizadas' => $seguimiento['num_actividades'],
      'NotaMedia' => $seguimiento['nota_media'],
      'Estado' => $this->mapEstadoParticipante($participante->get('estado')->value),
      'UltimaConexion' => $seguimiento['ultima_conexion'] ?? '',
    ];
  }

  protected function mapEstadoAccion(string $estado): string {
    return match($estado) {
      'pendiente', 'autorizada' => 'P',
      'en_curso' => 'E',
      'finalizada', 'cancelada' => 'F',
      default => 'P',
    };
  }

  protected function mapEstadoParticipante(string $estado): string {
    return match($estado) {
      'activo' => 'A',
      'baja' => 'B',
      'finalizado' => 'F',
      'certificado' => 'C',
      default => 'A',
    };
  }
}
 
7. Calculador de Seguimiento
7.1 SepeSeguimientoCalculator.php
Agrega datos de seguimiento desde el LMS (progress_record):
<?php
namespace Drupal\jaraba_sepe_teleformacion\Service;

use Drupal\Core\Database\Connection;

class SepeSeguimientoCalculator {

  protected $entityTypeManager;
  protected $database;

  public function __construct($entity_type_manager, Connection $database) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
  }

  /**
   * Calcula datos de seguimiento agregados para un enrollment.
   */
  public function calculate(int $enrollmentId): array {

    // Obtener suma de tiempo conectado (duration_seconds)
    $horasQuery = $this->database->select('progress_record', 'pr')
      ->condition('enrollment_id', $enrollmentId)
      ->fields('pr', []);
    $horasQuery->addExpression('SUM(duration_seconds) / 3600', 'horas');
    $horas = $horasQuery->execute()->fetchField() ?? 0;

    // Obtener enrollment para % progreso
    $enrollment = $this->entityTypeManager
      ->getStorage('enrollment')
      ->load($enrollmentId);

    $porcentaje = $enrollment ? (int) $enrollment->get('progress_percent')->value : 0;

    // Contar actividades completadas
    $actividadesQuery = $this->database->select('progress_record', 'pr')
      ->condition('enrollment_id', $enrollmentId)
      ->condition('status', 'completed')
      ->countQuery();
    $actividades = $actividadesQuery->execute()->fetchField();

    // Calcular nota media de evaluaciones
    $notaQuery = $this->database->select('progress_record', 'pr')
      ->condition('enrollment_id', $enrollmentId)
      ->condition('score', NULL, 'IS NOT NULL')
      ->fields('pr', []);
    $notaQuery->addExpression('AVG(score)', 'nota_media');
    $notaMedia = $notaQuery->execute()->fetchField();

    // Obtener última conexión
    $ultimaQuery = $this->database->select('progress_record', 'pr')
      ->condition('enrollment_id', $enrollmentId)
      ->fields('pr', ['changed'])
      ->orderBy('changed', 'DESC')
      ->range(0, 1);
    $ultima = $ultimaQuery->execute()->fetchField();

    return [
      'horas_conectado' => (float) $horas,
      'porcentaje_progreso' => $porcentaje,
      'num_actividades' => (int) $actividades,
      'nota_media' => $notaMedia ? round((float) $notaMedia, 2) : NULL,
      'ultima_conexion' => $ultima ? date('Y-m-d\TH:i:s', $ultima) : NULL,
    ];
  }
}
 
8. Definición WSDL
8.1 wsdl/seguimiento-teleformacion.wsdl
Estructura basada en el fichero WSDL oficial del SEPE (simplificado):
<?xml version="1.0" encoding="UTF-8"?>
<definitions xmlns="http://schemas.xmlsoap.org/wsdl/"
             xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
             xmlns:tns="http://jaraba.es/sepe/seguimiento"
             xmlns:xsd="http://www.w3.org/2001/XMLSchema"
             name="SeguimientoTeleformacion"
             targetNamespace="http://jaraba.es/sepe/seguimiento">

  <types>
    <xsd:schema targetNamespace="http://jaraba.es/sepe/seguimiento">

      <!-- Tipo DatosCentro -->
      <xsd:complexType name="DatosCentro">
        <xsd:sequence>
          <xsd:element name="CIF" type="xsd:string"/>
          <xsd:element name="RazonSocial" type="xsd:string"/>
          <xsd:element name="CodigoCentro" type="xsd:string"/>
          <xsd:element name="Direccion" type="xsd:string"/>
          <xsd:element name="CodigoPostal" type="xsd:string"/>
          <xsd:element name="Municipio" type="xsd:string"/>
          <xsd:element name="Provincia" type="xsd:string"/>
          <xsd:element name="Telefono" type="xsd:string"/>
          <xsd:element name="Email" type="xsd:string"/>
          <xsd:element name="URLPlataforma" type="xsd:string"/>
        </xsd:sequence>
      </xsd:complexType>

      <!-- Tipo DatosAccion -->
      <xsd:complexType name="DatosAccion">
        <xsd:sequence>
          <xsd:element name="IdAccion" type="xsd:string"/>
          <xsd:element name="CodigoEspecialidad" type="xsd:string"/>
          <xsd:element name="Denominacion" type="xsd:string"/>
          <xsd:element name="Modalidad" type="xsd:string"/>
          <xsd:element name="NumeroHoras" type="xsd:int"/>
          <xsd:element name="FechaInicio" type="xsd:date"/>
          <xsd:element name="FechaFin" type="xsd:date"/>
          <xsd:element name="NumParticipantes" type="xsd:int"/>
          <xsd:element name="Estado" type="xsd:string"/>
        </xsd:sequence>
      </xsd:complexType>

      <!-- Tipo DatosSeguimiento -->
      <xsd:complexType name="DatosSeguimiento">
        <xsd:sequence>
          <xsd:element name="DNI" type="xsd:string"/>
          <xsd:element name="Nombre" type="xsd:string"/>
          <xsd:element name="Apellidos" type="xsd:string"/>
          <xsd:element name="FechaAlta" type="xsd:date"/>
          <xsd:element name="FechaBaja" type="xsd:date" minOccurs="0"/>
          <xsd:element name="HorasConectado" type="xsd:decimal"/>
          <xsd:element name="PorcentajeProgreso" type="xsd:int"/>
          <xsd:element name="NumActividadesRealizadas" type="xsd:int"/>
          <xsd:element name="NotaMedia" type="xsd:decimal" minOccurs="0"/>
          <xsd:element name="Estado" type="xsd:string"/>
          <xsd:element name="UltimaConexion" type="xsd:dateTime" minOccurs="0"/>
        </xsd:sequence>
      </xsd:complexType>

    </xsd:schema>
  </types>

  <!-- Port Type con operaciones -->
  <portType name="SeguimientoPortType">
    <operation name="ObtenerDatosCentro">
      <input message="tns:ObtenerDatosCentroRequest"/>
      <output message="tns:ObtenerDatosCentroResponse"/>
    </operation>
    <!-- ... demás operaciones ... -->
  </portType>

  <service name="SeguimientoService">
    <port name="SeguimientoPort" binding="tns:SeguimientoBinding">
      <soap:address location="{{ENDPOINT_URL}}"/>
    </port>
  </service>

</definitions>
 
9. Pruebas y Validación
9.1 Validación con Kit de Autoevaluación SEPE
El SEPE proporciona un kit de autoevaluación (ZIP) que permite validar el servicio web antes de presentar la solicitud.
1.	Descargar kit desde https://www.sepe.es (sección Teleformación)
2.	Configurar URL del servicio en el fichero de propiedades
3.	Ejecutar tests automatizados (requiere Java)
4.	Verificar que todas las operaciones devuelven respuestas válidas
5.	Generar informe de validación para adjuntar a la solicitud
9.2 Tests Unitarios PHPUnit
# Ejecutar tests del módulo
./vendor/bin/phpunit modules/custom/jaraba_sepe_teleformacion/tests

# Test específico del servicio SOAP
./vendor/bin/phpunit --filter SepeSoapServiceTest
9.3 Test Manual con SoapUI
Pasos para probar el servicio con SoapUI:
6.	Importar WSDL: https://plataforma.jarabaimpact.es/sepe/ws/seguimiento?wsdl
7.	Crear request para cada operación
8.	Verificar respuestas XML válidas
9.	Comprobar tiempos de respuesta < 5 segundos
10. Checklist de Implementación
#	Tarea	Estado
1	Crear módulo jaraba_sepe_teleformacion con estructura base	Pendiente
2	Implementar entidades SepeCentro, SepeAccionFormativa, SepeParticipante	Pendiente
3	Crear migraciones de base de datos	Pendiente
4	Implementar SepeSoapController con endpoint SOAP	Pendiente
5	Implementar SepeSoapService con 6 operaciones SEPE	Pendiente
6	Implementar SepeDataMapper para transformación de datos	Pendiente
7	Implementar SepeSeguimientoCalculator para agregación LMS	Pendiente
8	Crear fichero WSDL conforme a especificación SEPE	Pendiente
9	Crear flujos ECA para sincronización automática	Pendiente
10	Implementar APIs REST de gestión interna	Pendiente
11	Crear UI de administración (formularios, listados)	Pendiente
12	Escribir tests unitarios (cobertura > 80%)	Pendiente
13	Validar con kit de autoevaluación SEPE	Pendiente
14	Auditar accesibilidad WCAG 2.1 AA	Pendiente
15	Documentar APIs en OpenAPI 3.0	Pendiente
16	Preparar documentación pedagógica	Pendiente
17	Presentar Declaración Responsable en sede SEPE	Pendiente

--- Fin del Documento ---

106_Modulo_SEPE_Teleformacion_Implementacion_v1.docx | Jaraba Impact Platform | Enero 2026
