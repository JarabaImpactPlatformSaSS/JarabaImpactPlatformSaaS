<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestión del canal de denuncias.
 *
 * ESTRUCTURA:
 * Gestiona el canal de denuncias conforme a la Directiva EU 2019/1937:
 * recepción de reportes (anónimos o identificados), cifrado de datos
 * sensibles, asignación de investigadores y seguimiento anónimo.
 *
 * LÓGICA DE NEGOCIO:
 * - Recibir reportes con cifrado de descripción y datos de contacto.
 * - Generar código de seguimiento único (tracking_code).
 * - Permitir seguimiento anónimo por código sin identificar al denunciante.
 * - Asignar investigador y gestionar el workflow de investigación.
 * - Registrar resolución y notificar al denunciante (si tiene contacto).
 * - Los reportes son inmutables una vez creados (excepto estado/resolución).
 *
 * RELACIONES:
 * - Depende de TenantContextService para aislamiento multi-tenant.
 * - Genera WhistleblowerReport entities.
 *
 * Spec: Doc 184 §3.5. Plan: FASE 5, Stack Compliance Legal N1.
 */
class WhistleblowerChannelService {

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
