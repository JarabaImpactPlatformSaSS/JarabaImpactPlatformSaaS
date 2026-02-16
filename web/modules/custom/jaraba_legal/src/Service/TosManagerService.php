<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestión de Terms of Service.
 *
 * ESTRUCTURA:
 * Gestiona el ciclo de vida de los ToS: creación de versiones, publicación,
 * control de aceptación por tenant y re-aceptación obligatoria.
 *
 * LÓGICA DE NEGOCIO:
 * - Crear nueva versión de ToS con hash SHA-256 del contenido.
 * - Publicar versión y marcar como activa (desactivando anteriores).
 * - Verificar si un tenant ha aceptado la versión activa.
 * - Forzar re-aceptación cuando cambia la versión (si está configurado).
 * - Enviar notificaciones de nuevas versiones a todos los tenants.
 *
 * RELACIONES:
 * - Depende de TenantContextService para aislamiento multi-tenant.
 * - Interactúa con ServiceAgreement entity para persistencia.
 *
 * Spec: Doc 184 §3.1. Plan: FASE 5, Stack Compliance Legal N1.
 */
class TosManagerService {

  /**
   * Constructor del servicio.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly object $tenantContext,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly MailManagerInterface $mailManager,
    protected readonly LoggerInterface $logger,
  ) {}

}
