<?php

declare(strict_types=1);

namespace Drupal\jaraba_facturae\ValueObject;

/**
 * Unidad organizativa DIR3 inmutable.
 *
 * Representa una unidad del Directorio Comun de Unidades Organicas
 * y Oficinas (DIR3) para facturacion B2G.
 *
 * Spec: Doc 180, Seccion 3.6.
 */
final class DIR3Unit {

  public function __construct(
    public readonly string $code,
    public readonly string $name,
    public readonly string $type,
    public readonly string $administration,
    public readonly bool $active,
  ) {}

  /**
   * Creates from a FACe SOAP response array.
   */
  public static function fromArray(array $data): self {
    return new self(
      code: $data['code'] ?? $data['codigo'] ?? '',
      name: $data['name'] ?? $data['nombre'] ?? '',
      type: $data['type'] ?? $data['tipo'] ?? '',
      administration: $data['administration'] ?? $data['administracion'] ?? '',
      active: (bool) ($data['active'] ?? $data['activo'] ?? TRUE),
    );
  }

  /**
   * Returns the result as an array.
   */
  public function toArray(): array {
    return [
      'code' => $this->code,
      'name' => $this->name,
      'type' => $this->type,
      'administration' => $this->administration,
      'active' => $this->active,
    ];
  }

}
