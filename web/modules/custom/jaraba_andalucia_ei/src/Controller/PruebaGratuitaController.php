<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller para la landing page pública de prueba gratuita Andalucía +ei.
 *
 * Captura leads de negocios piloto interesados en el programa PIIL.
 * Ruta pública sin autenticación. Honeypot anti-spam en campo oculto.
 *
 * ZERO-REGION-001: landing() devuelve render array con #theme.
 * CONTROLLER-READONLY-001: No redeclara $entityTypeManager con readonly.
 * TENANT-001: Establece tenant_id al crear la entidad.
 */
class PruebaGratuitaController extends ControllerBase {

  /**
   * Servicio de contexto de tenant (opcional).
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService|null
   */
  protected ?TenantContextService $tenantContext;

  /**
   * El logger del módulo.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Construye el controller.
   *
   * CONTROLLER-READONLY-001: $entityTypeManager se asigna en el body,
   * NO como constructor promotion con readonly.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   El gestor de tipos de entidad.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService|null $tenantContext
   *   Servicio de contexto tenant (opcional via @?).
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de logger del módulo.
   */
  public function __construct(
    $entityTypeManager,
    ?TenantContextService $tenantContext,
    LoggerInterface $logger,
  ) {
    // DRUPAL11-001: Asignar manualmente en constructor body.
    $this->entityTypeManager = $entityTypeManager;
    $this->tenantContext = $tenantContext;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('ecosistema_jaraba_core.tenant_context', ContainerInterface::NULL_ON_INVALID_REFERENCE),
      $container->get('logger.factory')->get('jaraba_andalucia_ei'),
    );
  }

  /**
   * Renderiza la landing page pública de prueba gratuita.
   *
   * ZERO-REGION-001: Devuelve render array con #theme, sin lógica de region.
   * ROUTE-LANGPREFIX-001: URL de acción del formulario via Url::fromRoute().
   *
   * @return array<string, mixed>
   *   Render array con tema 'prueba_gratuita_landing'.
   */
  public function landing(): array {
    return [
      '#theme' => 'prueba_gratuita_landing',
      '#form_action' => Url::fromRoute('jaraba_andalucia_ei.prueba_gratuita.submit')->toString(),
      '#attached' => [
        'library' => [
          'jaraba_andalucia_ei/prueba-gratuita',
        ],
      ],
      '#cache' => [
        'contexts' => ['url.query_args:ok'],
      ],
    ];
  }

  /**
   * Procesa el envío del formulario de lead de prueba gratuita.
   *
   * Crea una entidad NegocioProspectadoEi con los datos del formulario.
   * Incluye protección honeypot anti-spam y validación de provincia.
   * TENANT-001: Asigna tenant_id desde TenantContextService o default grupo 5.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La solicitud HTTP con los datos POST del formulario.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Redirección a la landing con parámetro ?ok=1 en caso de éxito,
   *   o 403 si se detecta un bot.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Si el campo honeypot no está vacío (bot detectado).
   */
  public function submit(Request $request): Response {
    // Honeypot anti-spam: campo oculto 'website' debe estar vacío.
    $honeypot = $request->request->get('website', '');
    if ($honeypot !== '') {
      $this->logger->warning('Bot detectado en prueba gratuita. Honeypot: @value, IP: @ip', [
        '@value' => $honeypot,
        '@ip' => $request->getClientIp(),
      ]);
      throw new AccessDeniedHttpException('Acceso denegado.');
    }

    // Validar campos requeridos.
    $nombre_negocio = trim((string) $request->request->get('nombre_negocio', ''));
    $persona_contacto = trim((string) $request->request->get('persona_contacto', ''));
    $telefono = trim((string) $request->request->get('telefono', ''));
    $email = trim((string) $request->request->get('email', ''));
    $provincia = trim((string) $request->request->get('provincia', ''));
    $sector = trim((string) $request->request->get('sector', ''));

    if ($nombre_negocio === '' || $persona_contacto === '' || $telefono === '' || $email === '' || $provincia === '' || $sector === '') {
      $this->logger->warning('Envío de prueba gratuita con campos requeridos vacíos desde IP @ip.', [
        '@ip' => $request->getClientIp(),
      ]);
      return $this->buildRedirect();
    }

    // Validar provincia (solo sevilla o malaga).
    $provincias_validas = ['sevilla', 'malaga'];
    if (!in_array($provincia, $provincias_validas, TRUE)) {
      $this->logger->warning('Provincia inválida en prueba gratuita: @prov', [
        '@prov' => $provincia,
      ]);
      return $this->buildRedirect();
    }

    // Campos opcionales.
    $municipio = trim((string) $request->request->get('municipio', ''));
    $web = trim((string) $request->request->get('web', ''));
    $problema = trim((string) $request->request->get('problema', ''));
    $rrss = trim((string) $request->request->get('rrss', ''));

    // Servicios seleccionados (array de checkboxes).
    $servicios = $request->request->all('servicio');
    $pack_compatible = count($servicios) > 0 ? json_encode(array_values($servicios), JSON_UNESCAPED_UNICODE) : '';

    // Clasificación de urgencia: rojo si no tiene web NI RRSS, amarillo por defecto.
    $clasificacion_urgencia = ($web === '' && $rrss === '') ? 'rojo' : 'amarillo';

    // TENANT-001: Resolver tenant_id.
    $tenant_id = NULL;
    if ($this->tenantContext !== NULL) {
      try {
        $tenant_id = $this->tenantContext->getCurrentTenantId();
      }
      catch (\Throwable $e) {
        $this->logger->notice('No se pudo resolver tenant para lead público: @msg', [
          '@msg' => $e->getMessage(),
        ]);
      }
    }
    // Default: grupo 5 (Andalucía +ei) si no se resuelve tenant.
    if ($tenant_id === NULL) {
      $tenant_id = 5;
    }

    try {
      /** @var \Drupal\jaraba_andalucia_ei\Entity\NegocioProspectadoEi $entity */
      $entity = $this->entityTypeManager
        ->getStorage('negocio_prospectado_ei')
        ->create([
          'nombre_negocio' => $nombre_negocio,
          'persona_contacto' => $persona_contacto,
          'telefono' => $telefono,
          'email' => $email,
          'provincia' => $provincia,
          'direccion' => $municipio,
          'sector' => $sector,
          'url_web' => $web,
          'pack_compatible' => $pack_compatible,
          'notas' => $problema,
          'clasificacion_urgencia' => $clasificacion_urgencia,
          'estado_embudo' => 'identificado',
          'tenant_id' => $tenant_id,
        ]);
      $entity->save();

      $this->logger->info('Lead prueba gratuita creado: @nombre (ID: @id, provincia: @prov, sector: @sector).', [
        '@nombre' => $nombre_negocio,
        '@id' => $entity->id(),
        '@prov' => $provincia,
        '@sector' => $sector,
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->error('Error al crear lead prueba gratuita: @msg', [
        '@msg' => $e->getMessage(),
      ]);
    }

    return $this->buildRedirect(['ok' => '1']);
  }

  /**
   * Construye la redirección a la landing de prueba gratuita.
   *
   * ROUTE-LANGPREFIX-001: URL construida via Url::fromRoute().
   *
   * @param array<string, string> $query
   *   Parámetros de query opcionales.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Respuesta de redirección 302.
   */
  protected function buildRedirect(array $query = []): RedirectResponse {
    $url = Url::fromRoute('jaraba_andalucia_ei.prueba_gratuita.landing', [], [
      'query' => $query,
    ])->toString();

    return new RedirectResponse($url);
  }

}
