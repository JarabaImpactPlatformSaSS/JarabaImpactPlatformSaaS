
ESPECIFICACIÓN TÉCNICA
Módulo jaraba_sepe_teleformacion
Web Service SOAP para Seguimiento SEPE
Incluye: Kit de Autoevaluación y Procedimiento de Validación
Documento:	107_SEPE_Kit_Validacion_Procedimiento_v1
Versión:	1.0
Fecha:	Enero 2026
Dependencias:	105_Homologacion, 106_Implementacion, 08_LMS_Core
Framework:	Drupal 11 + PHP 8.2+ + SOAP
 
1. Descarga del Kit de Autoevaluación SEPE
El SEPE proporciona un kit de autoevaluación oficial que permite validar el Web Service SOAP antes de presentar la solicitud de inscripción/acreditación. Este kit es obligatorio para verificar que la implementación cumple con las especificaciones técnicas.
1.1 URLs Oficiales de Descarga
📥 DESCARGA DIRECTA - Kit de Autoevaluación
https://www.sepe.es/SiteSepe/contenidos/personas/formacion/centros_formacion/pdf/ProvCentTFVal_20161118.zip

Documentación Complementaria Obligatoria
Documento	URL de Descarga	Tamaño
Kit de Autoevaluación (ZIP)	ProvCentTFVal_20161118.zip	22.9 MB
Definición WSDL Oficial	ProveedorCentroTFWS_20140619.wsdl	42 KB
Modelo de Datos Seguimiento	Modelo_Datos_Seguimiento_Teleformacion.pdf	1.02 MB
Guía Declaración Responsable	guia_declaracion_responsable_inscripcion.pdf	3 MB
Guía Solicitud Acreditación	guia_solicitud_acreditacion_teleformacion.pdf	5 MB
Página Oficial del SEPE
Todos los documentos están disponibles en la sección "Centros y Entidades de Teleformación":
https://www.sepe.es/HomeSepe/es/formacion-trabajo/centros-entidades-teleformacion.html
 
2. Requisitos Previos para la Validación
2.1 Requisitos del Entorno de Desarrollo
Componente	Requisito	Versión Mínima
Java Runtime	Necesario para ejecutar el kit de autoevaluación	JRE 8+
PHP	Con extensión SOAP habilitada (php-soap)	8.2+
Drupal	Core con módulos REST y Serialization	11.x
Certificado SSL	Certificado válido para HTTPS (Let's Encrypt válido)	TLS 1.2+
Certificado Digital	Certificado de persona jurídica o representante	X.509
2.2 Verificación de Extensión PHP SOAP
# Verificar que SOAP está habilitado
php -m | grep soap
 
# Si no aparece, instalar:
sudo apt-get install php8.2-soap
sudo systemctl restart php8.2-fpm
 
# Verificar en PHP:
<?php
if (extension_loaded('soap')) {
    echo "SOAP extension is loaded";
} else {
    echo "ERROR: SOAP extension is NOT loaded";
}
?>
2.3 Estructura del Kit Descomprimido
# Descomprimir el kit
unzip ProvCentTFVal_20161118.zip
 
# Estructura resultante:
KITVal_20161118/
└── ProvCentTFVal_20161118/
    ├── lib/                    # Dependencias Java
    ├── ProvCentTFVal.jar       # Ejecutable principal
    ├── config.properties       # Configuración (EDITAR)
    ├── pruebaDatosDelCentro_20140619.xml
    ├── pruebaCrearAccion_20140619.xml
    ├── pruebaObtenerListaAcciones_20140619.xml
    ├── pruebaObtenerDatosAccion_20140619.xml
    ├── pruebaObtenerParticipantes_20140619.xml
    ├── pruebaObtenerSeguimiento_20140619.xml
    ├── ejecutar.bat            # Script Windows
    └── ejecutar.sh             # Script Linux/Mac
 
3. Configuración del Kit de Autoevaluación
3.1 Archivo config.properties
El archivo config.properties debe editarse con los datos de tu plataforma antes de ejecutar las pruebas:
# ============================================
# CONFIGURACIÓN KIT AUTOEVALUACIÓN SEPE
# Jaraba Impact Platform
# ============================================
 
# Tipo de almacén de certificados
TRUST_STORE_TYPE=jks
 
# Ruta al keystore con el certificado SSL de la plataforma
TRUST_STORE_PATH=/path/to/jaraba_platform.jks
 
# Contraseña del keystore
TRUST_STORE_PASSWORD=tu_password_keystore
 
# URL del Web Service SOAP de tu plataforma
WEB_SERVICE=https://plataforma.jarabaimpact.es/sepe/ws/seguimiento
 
# Contraseña de autenticación del Web Service (si aplica)
PASSWORD=sepe_ws_password
 
# Ruta a los archivos XML de prueba
XML_PATH=./pruebaDatosDelCentro_20140619.xml
 
# Timeout de conexión (milisegundos)
CONNECTION_TIMEOUT=30000
 
# Timeout de lectura (milisegundos)
READ_TIMEOUT=60000
3.2 Creación del Keystore JKS
Si tu certificado SSL está en formato PEM (Let's Encrypt), convértelo a JKS:
# 1. Convertir certificados PEM a PKCS12
openssl pkcs12 -export \
  -in /etc/letsencrypt/live/plataforma.jarabaimpact.es/fullchain.pem \
  -inkey /etc/letsencrypt/live/plataforma.jarabaimpact.es/privkey.pem \
  -out jaraba_platform.p12 \
  -name jaraba \
  -password pass:tu_password
 
# 2. Convertir PKCS12 a JKS
keytool -importkeystore \
  -srckeystore jaraba_platform.p12 \
  -srcstoretype PKCS12 \
  -srcstorepass tu_password \
  -destkeystore jaraba_platform.jks \
  -deststoretype JKS \
  -deststorepass tu_password
 
# 3. Verificar contenido del keystore
keytool -list -keystore jaraba_platform.jks -storepass tu_password
 
4. Especificación de Operaciones SOAP
El Web Service debe implementar las 6 operaciones definidas en el Anexo V de la Orden TMS/369/2019. A continuación se detalla cada operación con su firma, parámetros y respuesta esperada.
Operación	Descripción	Test
ObtenerDatosCentro()	Devuelve datos identificativos del centro de formación	T-01
CrearAccion(idAccion)	Crea/registra una acción formativa con el ID indicado	T-02
ObtenerListaAcciones()	Lista todos los IDs de acciones formativas del centro	T-03
ObtenerDatosAccion(id)	Devuelve datos completos de una acción formativa	T-04
ObtenerParticipantes(id)	Lista participantes de una acción con seguimiento básico	T-05
ObtenerSeguimiento(id,dni)	Seguimiento detallado de un participante específico	T-06
4.1 ObtenerDatosCentro()
Respuesta XML esperada:
<DatosCentro>
  <CIF>B12345678</CIF>
  <RazonSocial>Jaraba Impact Platform SL</RazonSocial>
  <CodigoCentro>SEPE-TF-2026-0001</CodigoCentro>
  <Direccion>Calle Ejemplo 123, Planta 2</Direccion>
  <CodigoPostal>14940</CodigoPostal>
  <Municipio>Santaella</Municipio>
  <Provincia>Córdoba</Provincia>
  <Telefono>957123456</Telefono>
  <Email>formacion@jarabaimpact.es</Email>
  <URLPlataforma>https://plataforma.jarabaimpact.es</URLPlataforma>
</DatosCentro>
4.2 ObtenerDatosAccion(idAccion)
Respuesta XML esperada:
<DatosAccion>
  <IdAccion>AF-2026-0001</IdAccion>
  <CodigoEspecialidad>SSCE0110</CodigoEspecialidad>
  <Denominacion>Docencia de la formación profesional para el empleo</Denominacion>
  <Modalidad>T</Modalidad>  <!-- T=Teleformación, M=Mixta -->
  <NumeroHoras>380</NumeroHoras>
  <FechaInicio>2026-02-01</FechaInicio>
  <FechaFin>2026-06-30</FechaFin>
  <NumParticipantes>25</NumParticipantes>
  <Estado>E</Estado>  <!-- P=Pendiente, E=En curso, F=Finalizada -->
</DatosAccion>
4.3 ObtenerSeguimiento(idAccion, dni)
Respuesta XML esperada:
<DatosSeguimiento>
  <DNI>12345678A</DNI>
  <Nombre>María</Nombre>
  <Apellidos>García López</Apellidos>
  <FechaAlta>2026-02-01</FechaAlta>
  <FechaBaja/>  <!-- Vacío si no hay baja -->
  <HorasConectado>45.5</HorasConectado>
  <PorcentajeProgreso>65</PorcentajeProgreso>
  <NumActividadesRealizadas>12</NumActividadesRealizadas>
  <NotaMedia>7.8</NotaMedia>
  <Estado>A</Estado>  <!-- A=Activo, B=Baja, F=Finalizado, C=Certificado -->
  <UltimaConexion>2026-03-15T14:30:00</UltimaConexion>
</DatosSeguimiento>
 
5. Implementación PHP/Drupal del Servicio SOAP
5.1 SepeSoapService.php - Clase Principal
<?php
namespace Drupal\jaraba_sepe_teleformacion\Service;
 
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;
 
/**
 * Servicio SOAP para comunicación con SEPE.
 * Implementa las 6 operaciones requeridas por Anexo V TMS/369/2019.
 */
class SepeSoapService {
 
  protected EntityTypeManagerInterface $entityTypeManager;
  protected SepeDataMapper $dataMapper;
  protected ConfigFactoryInterface $configFactory;
  protected LoggerInterface $logger;
 
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    SepeDataMapper $data_mapper,
    ConfigFactoryInterface $config_factory,
    LoggerInterface $logger
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->dataMapper = $data_mapper;
    $this->configFactory = $config_factory;
    $this->logger = $logger;
  }
 
  /**
   * OPERACIÓN 1: ObtenerDatosCentro
   * @return object DatosCentro
   */
  public function ObtenerDatosCentro(): object {
    $this->logger->info('SEPE: ObtenerDatosCentro called');
    
    $centro = $this->entityTypeManager
      ->getStorage('sepe_centro')
      ->loadByProperties(['is_active' => TRUE]);
    
    $centro = reset($centro);
    if (!$centro) {
      throw new \SoapFault('Server', 'Centro no configurado');
    }
    
    return $this->dataMapper->mapCentro($centro);
  }
 
  /**
   * OPERACIÓN 2: CrearAccion
   * @param string $idAccion Identificador de la acción
   * @return int Código resultado (0=OK, -1=Error, -2=Ya existe)
   */
  public function CrearAccion(string $idAccion): int {
    $this->logger->info('SEPE: CrearAccion called: @id', ['@id' => $idAccion]);
    
    // Verificar si ya existe
    $existing = $this->entityTypeManager
      ->getStorage('sepe_accion_formativa')
      ->loadByProperties(['id_accion_sepe' => $idAccion]);
    
    if (!empty($existing)) {
      return -2; // Ya existe
    }
    
    try {
      $accion = $this->entityTypeManager
        ->getStorage('sepe_accion_formativa')
        ->create(['id_accion_sepe' => $idAccion, 'estado' => 'P']);
      $accion->save();
      return 0; // OK
    } catch (\Exception $e) {
      $this->logger->error('Error creando acción: @msg', ['@msg' => $e->getMessage()]);
      return -1; // Error
    }
  }
 
  /**
   * OPERACIÓN 3: ObtenerListaAcciones
   * @return array Lista de IDs de acciones
   */
  public function ObtenerListaAcciones(): array {
    $this->logger->info('SEPE: ObtenerListaAcciones called');
    
    $acciones = $this->entityTypeManager
      ->getStorage('sepe_accion_formativa')
      ->loadMultiple();
    
    $lista = [];
    foreach ($acciones as $accion) {
      $lista[] = $accion->get('id_accion_sepe')->value;
    }
    
    return $lista;
  }
 
  /**
   * OPERACIÓN 4: ObtenerDatosAccion
   * @param string $idAccion
   * @return object DatosAccion
   */
  public function ObtenerDatosAccion(string $idAccion): object {
    $this->logger->info('SEPE: ObtenerDatosAccion: @id', ['@id' => $idAccion]);
    
    $acciones = $this->entityTypeManager
      ->getStorage('sepe_accion_formativa')
      ->loadByProperties(['id_accion_sepe' => $idAccion]);
    
    $accion = reset($acciones);
    if (!$accion) {
      throw new \SoapFault('Server', 'Acción no encontrada: ' . $idAccion);
    }
    
    return $this->dataMapper->mapAccion($accion);
  }
 
  /**
   * OPERACIÓN 5: ObtenerParticipantes
   * @param string $idAccion
   * @return array Lista de DatosSeguimiento básicos
   */
  public function ObtenerParticipantes(string $idAccion): array {
    $this->logger->info('SEPE: ObtenerParticipantes: @id', ['@id' => $idAccion]);
    
    $participantes = $this->entityTypeManager
      ->getStorage('sepe_participante')
      ->loadByProperties(['accion_id' => $this->getAccionId($idAccion)]);
    
    $lista = [];
    foreach ($participantes as $participante) {
      $lista[] = $this->dataMapper->mapParticipanteBasico($participante);
    }
    
    return $lista;
  }
 
  /**
   * OPERACIÓN 6: ObtenerSeguimiento
   * @param string $idAccion
   * @param string $dni
   * @return object DatosSeguimiento completo
   */
  public function ObtenerSeguimiento(string $idAccion, string $dni): object {
    $this->logger->info('SEPE: ObtenerSeguimiento: @id, @dni', [
      '@id' => $idAccion,
      '@dni' => $dni,
    ]);
    
    $participantes = $this->entityTypeManager
      ->getStorage('sepe_participante')
      ->loadByProperties([
        'accion_id' => $this->getAccionId($idAccion),
        'dni' => $dni,
      ]);
    
    $participante = reset($participantes);
    if (!$participante) {
      throw new \SoapFault('Server', 'Participante no encontrado');
    }
    
    return $this->dataMapper->mapSeguimientoCompleto($participante);
  }
 
  protected function getAccionId(string $idAccionSepe): int {
    $acciones = $this->entityTypeManager
      ->getStorage('sepe_accion_formativa')
      ->loadByProperties(['id_accion_sepe' => $idAccionSepe]);
    $accion = reset($acciones);
    return $accion ? $accion->id() : 0;
  }
}
 
6. Procedimiento de Validación Paso a Paso
6.1 Ejecución del Kit de Autoevaluación
⚠️ IMPORTANTE
Antes de ejecutar el kit, asegúrate de que el Web Service SOAP está operativo y accesible públicamente. El kit realiza llamadas reales a tu endpoint.

Paso 1:	 Navegar a la carpeta del kit descomprimido
cd KITVal_20161118/ProvCentTFVal_20161118/
Paso 2:	 Editar config.properties con datos de tu plataforma
# Editar con tu editor preferido
nano config.properties
 
# Configurar al menos:
WEB_SERVICE=https://plataforma.jarabaimpact.es/sepe/ws/seguimiento
TRUST_STORE_PATH=./jaraba_platform.jks
TRUST_STORE_PASSWORD=tu_password
Paso 3:	 Ejecutar el kit de pruebas
# Linux/Mac
chmod +x ejecutar.sh
./ejecutar.sh
 
# Windows
ejecutar.bat
 
# O directamente con Java
java -jar ProvCentTFVal.jar config.properties
Paso 4:	 Interpretar los resultados
El kit ejecuta cada operación y muestra el resultado:
Resultado	Código	Significado
OK	0	La operación cumple con la especificación SEPE
KO	-1	Error general - revisar respuesta XML
KO	-2	Error de autenticación o conexión
KO	-3	Error de formato XML en respuesta
TIMEOUT	-	Tiempo de respuesta > 5 segundos (optimizar)
6.2 Salida Esperada del Kit
=====================================================
KIT DE AUTOEVALUACIÓN SEPE - TELEFORMACIÓN
Versión: 20161118
=====================================================
 
Conectando a: https://plataforma.jarabaimpact.es/sepe/ws/seguimiento
 
Test 1: ObtenerDatosCentro
  Enviando request...
  Respuesta recibida (245ms)
  Validando estructura XML... OK
  Validando campos obligatorios... OK
  RESULTADO: OK
 
Test 2: CrearAccion (TEST-2026-0001)
  Enviando request...
  Respuesta recibida (156ms)
  Código retornado: 0
  RESULTADO: OK
 
Test 3: ObtenerListaAcciones
  Enviando request...
  Respuesta recibida (89ms)
  Acciones encontradas: 1
  RESULTADO: OK
 
Test 4: ObtenerDatosAccion (TEST-2026-0001)
  Enviando request...
  Respuesta recibida (112ms)
  Validando estructura XML... OK
  RESULTADO: OK
 
Test 5: ObtenerParticipantes (TEST-2026-0001)
  Enviando request...
  Respuesta recibida (203ms)
  Participantes encontrados: 0
  RESULTADO: OK
 
Test 6: ObtenerSeguimiento (TEST-2026-0001, 00000000T)
  Enviando request...
  Respuesta recibida (178ms)
  RESULTADO: OK (participante no encontrado esperado)
 
=====================================================
RESUMEN: 6/6 Tests OK
VALIDACIÓN: APROBADA
=====================================================
 
Informe generado: informe_validacion_20260115.pdf
 
7. Errores Comunes y Soluciones
7.1 Tabla de Errores y Soluciones
Error	Solución
SSL Handshake Failed	Verificar que el certificado SSL es válido y el keystore JKS está correctamente generado. Comprobar que TLS 1.2+ está habilitado.
Connection Refused	El endpoint SOAP no está accesible. Verificar URL, firewall, y que el servidor web está corriendo.
Invalid XML Response	La respuesta XML no cumple el esquema esperado. Revisar estructura según WSDL oficial. Validar con SoapUI primero.
Authentication Error (-1 vs -2)	El kit espera códigos específicos. Descompilar el JAR para ver la lógica exacta de validación. Contraseña incorrecta debe devolver -1.
Timeout (> 5 seg)	Optimizar consultas a BD. Añadir índices en sepe_participante(dni, accion_id). Cachear datos del centro.
WSDL Not Found	El endpoint WSDL debe ser accesible en /sepe/ws/seguimiento?wsdl o /sepe/ws/seguimiento/wsdl
7.2 Depuración con SoapUI
Antes de ejecutar el kit oficial, se recomienda probar cada operación manualmente con SoapUI:
1.	Descargar e instalar SoapUI (versión gratuita suficiente)
2.	File → New SOAP Project
3.	Initial WSDL: https://plataforma.jarabaimpact.es/sepe/ws/seguimiento?wsdl
4.	Expandir el proyecto y hacer doble clic en cada operación
5.	Completar los parámetros y ejecutar (botón verde Play)
6.	Verificar que la respuesta XML es válida y los tiempos < 5 segundos
 
8. Checklist de Validación Final
Antes de presentar la Declaración Responsable ante el SEPE, verificar que se cumplen todos los requisitos:
✓	Requisito	Estado
□	Kit de autoevaluación descargado y descomprimido	Pendiente
□	Keystore JKS generado con certificado SSL válido	Pendiente
□	config.properties configurado con URL correcta	Pendiente
□	ObtenerDatosCentro() → OK	Pendiente
□	CrearAccion() → OK (código 0)	Pendiente
□	ObtenerListaAcciones() → OK	Pendiente
□	ObtenerDatosAccion() → OK	Pendiente
□	ObtenerParticipantes() → OK	Pendiente
□	ObtenerSeguimiento() → OK	Pendiente
□	Tiempos de respuesta < 5 segundos en todas las operaciones	Pendiente
□	Informe de validación generado (PDF)	Pendiente
□	Certificado digital de representante legal disponible	Pendiente
□	Documentación pedagógica preparada (mín. 3 especialidades)	Pendiente

📋 SIGUIENTE PASO
Una vez completado el checklist con todos los tests OK, acceder a la Sede Electrónica del SEPE para presentar la Declaración Responsable de Inscripción: https://sede.sepe.gob.es/portalSede/procedimientos-y-servicios/empresas/formacion-para-el-empleo/acreditacion-e-inscripcion-en-teleformacion

— Fin del Documento —
