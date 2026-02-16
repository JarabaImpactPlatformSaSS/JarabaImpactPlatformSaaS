<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestión del proceso de offboarding.
 *
 * ESTRUCTURA:
 * Gestiona el workflow completo de baja de un tenant: solicitud, periodo
 * de gracia, exportación de datos, facturación final y eliminación certificada.
 *
 * LÓGICA DE NEGOCIO:
 * - Iniciar solicitud de offboarding con periodo de gracia configurable.
 * - Permitir cancelación durante el periodo de gracia.
 * - Exportar todos los datos del tenant en los formatos configurados.
 * - Generar factura final prorrateada.
 * - Eliminar datos del tenant y generar certificado con hash SHA-256.
 * - Enviar notificaciones en cada paso del workflow.
 *
 * RELACIONES:
 * - Depende de TenantContextService para aislamiento multi-tenant.
 * - Genera OffboardingRequest entities.
 * - Interactúa con FileSystem para exportación.
 *
 * Spec: Doc 184 §3.4. Plan: FASE 5, Stack Compliance Legal N1.
 */
class OffboardingManagerService {

  /**
   * Constructor del servicio.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly object $tenantContext,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly FileSystemInterface $fileSystem,
    protected readonly MailManagerInterface $mailManager,
    protected readonly LoggerInterface $logger,
  ) {}

}
