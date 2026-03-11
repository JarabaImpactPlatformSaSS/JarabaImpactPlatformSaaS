<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Gestiona el acceso cross-vertical para participantes del programa.
 *
 * Andalucía +ei actúa como "vertical temporal": las participantes
 * acceden a herramientas de empleabilidad y emprendimiento según
 * su carril durante la vigencia del programa, sin necesidad de
 * addons de pago.
 *
 * Post-programa: acceso en modo lectura + banner de conversión.
 *
 * @see \Drupal\jaraba_billing\Service\FeatureAccessService
 *   Se inyecta como @? paso 5 en la cadena de resolución.
 */
class ProgramaVerticalAccessService implements ProgramaVerticalAccessInterface {

  /**
   * Mapeo carril → verticales accesibles.
   *
   * @var array<string, string[]>
   */
  private const CARRIL_VERTICALS = [
    'impulso_digital' => [
      'empleabilidad',
      'emprendimiento',
    ],
    'acelera_pro' => [
      'emprendimiento',
      'empleabilidad',
    ],
    'hibrido' => [
      'empleabilidad',
      'emprendimiento',
    ],
  ];

  /**
   * Fases que otorgan acceso activo.
   *
   * @var string[]
   */
  private const FASES_ACTIVAS = [
    'acogida',
    'diagnostico',
    'atencion',
    'insercion',
    'seguimiento',
  ];

  /**
   * Meses de extensión post-programa para fase seguimiento.
   */
  private const MESES_EXTENSION_SEGUIMIENTO = 6;

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LoggerInterface $logger,
    private readonly StateInterface $state,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function hasAccess(int $uid, string $vertical): bool {
    $participante = $this->getParticipanteActivo($uid);
    if ($participante === NULL) {
      return FALSE;
    }

    $carril = $participante->get('carril')->value ?? '';
    $verticalesPermitidos = self::CARRIL_VERTICALS[$carril] ?? [];

    if (!in_array($vertical, $verticalesPermitidos, TRUE)) {
      return FALSE;
    }

    // Verificar que no ha expirado.
    if ($this->isExpiredForParticipante($participante)) {
      return FALSE;
    }

    // Registrar métrica de acceso.
    $this->registrarAcceso($uid, $vertical);

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveVerticals(int $uid): array {
    $participante = $this->getParticipanteActivo($uid);
    if ($participante === NULL) {
      return [];
    }

    if ($this->isExpiredForParticipante($participante)) {
      return [];
    }

    $carril = $participante->get('carril')->value ?? '';
    return self::CARRIL_VERTICALS[$carril] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function isExpired(int $uid): bool {
    $participante = $this->getParticipanteActivo($uid);
    if ($participante === NULL) {
      return TRUE;
    }
    return $this->isExpiredForParticipante($participante);
  }

  /**
   * {@inheritdoc}
   */
  public function getDiasRestantes(int $uid): ?int {
    $participante = $this->getParticipanteActivo($uid);
    if ($participante === NULL) {
      return -1;
    }

    $fase = $participante->get('fase_actual')->value ?? '';

    // Fases activas: acceso indefinido.
    if (in_array($fase, ['acogida', 'diagnostico', 'atencion', 'insercion'], TRUE)) {
      return NULL;
    }

    // Fase seguimiento: +6 meses desde fecha de transición.
    if ($fase === 'seguimiento') {
      return $this->calcularDiasRestantesSeguimiento($participante);
    }

    // Alumni/baja: expirado.
    return -1;
  }

  /**
   * Obtiene el participante activo de un usuario.
   *
   * @param int $uid
   *   ID del usuario.
   *
   * @return object|null
   *   Entidad ProgramaParticipanteEi o NULL.
   */
  private function getParticipanteActivo(int $uid): ?object {
    $storage = $this->entityTypeManager->getStorage('programa_participante_ei');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('uid', $uid)
      ->condition('fase_actual', ['alumni', 'baja'], 'NOT IN')
      ->sort('created', 'DESC')
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    return $storage->load(reset($ids));
  }

  /**
   * Verifica si el acceso ha expirado para un participante.
   *
   * @param object $participante
   *   Entidad ProgramaParticipanteEi.
   *
   * @return bool
   *   TRUE si expirado.
   */
  private function isExpiredForParticipante(object $participante): bool {
    $fase = $participante->get('fase_actual')->value ?? '';

    if (in_array($fase, self::FASES_ACTIVAS, TRUE)) {
      if ($fase === 'seguimiento') {
        $diasRestantes = $this->calcularDiasRestantesSeguimiento($participante);
        return $diasRestantes !== NULL && $diasRestantes < 0;
      }
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Calcula días restantes de acceso en fase seguimiento.
   *
   * @param object $participante
   *   Entidad ProgramaParticipanteEi.
   *
   * @return int|null
   *   Días restantes, NULL si no se puede calcular.
   */
  private function calcularDiasRestantesSeguimiento(object $participante): ?int {
    // Intentar obtener fecha de transición a seguimiento.
    $fechaSeguimiento = $participante->hasField('fecha_fase_seguimiento')
      ? $participante->get('fecha_fase_seguimiento')->value
      : NULL;

    if ($fechaSeguimiento === NULL) {
      // Si no hay fecha registrada, usar changed como aproximación.
      $changed = $participante->get('changed')->value;
      if ($changed === NULL) {
        return NULL;
      }
      try {
        $fecha = (new \DateTimeImmutable())->setTimestamp((int) $changed);
      }
      catch (\Throwable) {
        return NULL;
      }
    }
    else {
      try {
        $fecha = new \DateTimeImmutable($fechaSeguimiento);
      }
      catch (\Throwable) {
        return NULL;
      }
    }

    $expiracion = $fecha->modify('+' . self::MESES_EXTENSION_SEGUIMIENTO . ' months');
    $hoy = new \DateTimeImmutable('today');
    $diff = $hoy->diff($expiracion);

    return $diff->invert ? -$diff->days : $diff->days;
  }

  /**
   * Registra una métrica de acceso en State API.
   *
   * @param int $uid
   *   ID del usuario.
   * @param string $vertical
   *   Vertical accedido.
   */
  private function registrarAcceso(int $uid, string $vertical): void {
    $key = "jaraba_andalucia_ei.vertical_access.{$uid}.{$vertical}";

    $data = $this->state->get($key, [
      'first_access' => NULL,
      'last_access' => NULL,
      'access_count' => 0,
    ]);

    $now = time();
    if ($data['first_access'] === NULL) {
      $data['first_access'] = $now;
    }
    $data['last_access'] = $now;
    $data['access_count']++;

    $this->state->set($key, $data);
  }

}
