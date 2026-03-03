SPEC-002: API Pública Documentada (OpenAPI 3.0)
Estado: Especificación para Implementación | Versión: 1.0.0
Relacionado con: jaraba_integrations, jaraba_auth
1. Objetivo Estratégico
Permitir el escalado B2B mediante la integración de terceros. El sistema debe permitir a un ayuntamiento o una gran cooperativa agrícola inyectar datos de sus sistemas locales hacia el Ecosistema Jaraba automáticamente.
2. Arquitectura de Autenticación (OAuth2 + HMAC)
●	Proveedor: Extensión del sistema actual de Auth Social.
●	Mecanismo: Client Credentials Flow para integraciones servidor-servidor.
●	Seguridad: Rotación de API_KEY via getenv() y validación obligatoria de X-Jaraba-Signature (HMAC SHA256) para webhooks salientes.
3. Endpoints Canónicos (REST)
Recurso	Método	Ruta	Descripción
Tenants	GET	/api/v1/tenants	Listado de tenants del vertical autorizado.
Agro	POST	/api/v1/agro/batch	Ingesta masiva de lotes de producción.
Empleo	GET	/api/v1/jobs/matching	Endpoint de matching de talento via IA.
AI	POST	/api/v1/mcp/proxy	Acceso al servidor MCP para herramientas externas.
4. Generación de Documentación (OpenAPI)
Utilizaremos un servicio dinámico que lea las definiciones de ContentEntity para generar el esquema JSON.
declare(strict_types=1);

namespace Drupal\jaraba_integrations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Generador dinámico de especificación OpenAPI.
 */
final class OpenApiController extends ControllerBase {

  public function getSpec(): JsonResponse {
    $spec = [
      'openapi' => '3.0.3',
      'info' => [
        'title' => 'Jaraba Impact API',
        'version' => '2.0.0',
        'description' => 'API Pública para la integración de ecosistemas digitales.',
      ],
      'paths' => $this->getDiscoveryPaths(),
    ];

    return new JsonResponse($spec);
  }
}

5. Directrices P0 de Implementación
●	TENANT-001: El middleware de la API DEBE detectar el client_id y setear el tenant_context global antes de procesar cualquier recurso.
●	ROUTE-LANGPREFIX-001: Los recursos devueltos deben incluir el prefijo de idioma /es/ en todos los enlaces canónicos.
●	AUDIT-SEC-001: Todo intento de acceso con API KEY inválida debe ser logueado en AiAuditEntry como un riesgo de seguridad de nivel alto.
6. Verificación de Despliegue
●	Test Kernel para validar la inyección de tenant_id via Header X-Tenant-ID.
●	Verificación de esquema JSON contra el validador oficial de Swagger.
