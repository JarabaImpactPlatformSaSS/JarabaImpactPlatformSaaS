<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Psr\Log\LoggerInterface;

/**
 * Calculadora del punto de equilibrio contextualizada por pack de servicio.
 *
 * Servicio de cálculo puro (sin acceso a BD) que determina cuántos clientes
 * necesita un/a participante para cubrir sus gastos fijos mensuales como
 * autónomo/a, según el pack de servicio elegido y la modalidad de precio.
 *
 * Fuentes de datos:
 * - Doc 20260324d (catálogo de servicios): precios por pack y tier.
 * - Doc 20260324e (catálogo de integración): gastos fijos base autónomo.
 *
 * Ejemplo validación (Lucía, doc 20260324e):
 * ─────────────────────────────────────────────────────────────────────
 *   Lucía elige Pack 1 (Contenido Digital), modalidad estándar (250 €).
 *   Gastos fijos base: 144 €/mes (cuota SS 80 + SaaS 19 + internet 30 + móvil 15).
 *   Margen bruto: 100% (servicio puro, sin COGS).
 *   Punto de equilibrio: 144 / 250 = 0.576 → 1 cliente.
 *   Con 1 cliente: ingresos 250 €, beneficio neto 106 €.
 *   Con 2 clientes: ingresos 500 €, beneficio neto 356 €.
 *   Con 3 clientes: ingresos 750 €, beneficio neto 606 €.
 *   Con 5 clientes: ingresos 1250 €, beneficio neto 1106 €.
 * ─────────────────────────────────────────────────────────────────────
 *
 * PACK-VERTEBRADOR-001: Los 5 Packs de servicios son el eje vertebrador
 * de TODA la formación.
 */
class CalculadoraPuntoEquilibrioService {

  /**
   * Gastos fijos mensuales base para un/a nuevo/a autónomo/a.
   *
   * - cuota_ss: Cuota Cero primer año (~80 €), luego ~90 €.
   * - plataforma_saas: Jaraba Impact Platform tier starter.
   * - internet: Conexión fibra doméstica.
   * - movil: Tarifa móvil profesional.
   *
   * @var array<string, float>
   */
  private const GASTOS_FIJOS_BASE = [
    'cuota_ss' => 80.0,
    'plataforma_saas' => 19.0,
    'internet' => 30.0,
    'movil' => 15.0,
  ];

  /**
   * Precios sugeridos por pack y modalidad/tier (€/mes por cliente).
   *
   * Origen: Doc 20260324d §3 — Catálogo de servicios Andalucía +ei.
   *
   * @var array<string, array<string, float>>
   */
  private const PRECIOS_PACK = [
    'contenido_digital' => [
      'basico' => 150.0,
      'estandar' => 250.0,
      'premium' => 400.0,
    ],
    'asistente_virtual' => [
      'basico' => 150.0,
      'estandar' => 250.0,
      'premium' => 350.0,
    ],
    'presencia_online' => [
      'basico' => 150.0,
      'setup' => 300.0,
    ],
    'tienda_digital' => [
      'basico' => 300.0,
      'setup' => 500.0,
    ],
    'community_manager' => [
      'basico' => 150.0,
      'estandar' => 200.0,
      'premium' => 350.0,
    ],
  ];

  /**
   * Escenarios de clientes para la proyección.
   *
   * @var int[]
   */
  private const ESCENARIOS_CLIENTES = [1, 2, 3, 5];

  /**
   * Constructs a CalculadoraPuntoEquilibrioService.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   */
  public function __construct(
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Calcula el punto de equilibrio para un pack y modalidad dados.
   *
   * @param string $packTipo
   *   Tipo de pack (e.g., 'contenido_digital', 'tienda_digital').
   * @param string $modalidad
   *   Modalidad/tier del pack (e.g., 'basico', 'estandar', 'premium', 'setup').
   *   Default: 'estandar'.
   * @param float|null $precioCustom
   *   Precio personalizado por cliente (€/mes). Si se proporciona, sobreescribe
   *   el precio del catálogo. Útil cuando el participante ajusta precios.
   * @param float|null $gastosExtra
   *   Gastos fijos adicionales mensuales (€). Se suman a GASTOS_FIJOS_BASE.
   *   Ejemplos: coworking, herramientas premium, seguro RC.
   *
   * @return array{
   *   gastos_fijos: float,
   *   precio_cliente: float,
   *   margen_bruto: float,
   *   punto_equilibrio_clientes: float,
   *   punto_equilibrio_euros: float,
   *   escenarios: list<array{clientes: int, ingresos: float, beneficio_neto: float}>,
   *   }
   *   Resultado del cálculo con punto de equilibrio y escenarios.
   *
   * @throws \InvalidArgumentException
   *   Si el pack o la modalidad no existen y no se proporciona precio custom.
   */
  public function calcularPuntoEquilibrio(
    string $packTipo,
    string $modalidad = 'estandar',
    ?float $precioCustom = NULL,
    ?float $gastosExtra = NULL,
  ): array {
    try {
      // Resolver precio por cliente.
      $precioCliente = $precioCustom;
      if ($precioCliente === NULL) {
        if (!isset(self::PRECIOS_PACK[$packTipo])) {
          throw new \InvalidArgumentException(sprintf(
            'Pack tipo "%s" no encontrado. Válidos: %s',
            $packTipo,
            implode(', ', array_keys(self::PRECIOS_PACK)),
          ));
        }
        if (!isset(self::PRECIOS_PACK[$packTipo][$modalidad])) {
          throw new \InvalidArgumentException(sprintf(
            'Modalidad "%s" no encontrada para pack "%s". Válidas: %s',
            $modalidad,
            $packTipo,
            implode(', ', array_keys(self::PRECIOS_PACK[$packTipo])),
          ));
        }
        $precioCliente = self::PRECIOS_PACK[$packTipo][$modalidad];
      }

      if ($precioCliente <= 0.0) {
        throw new \InvalidArgumentException('El precio por cliente debe ser mayor que 0.');
      }

      // Calcular gastos fijos totales.
      $gastosFijos = $this->getGastosFijosBase();
      if ($gastosExtra !== NULL && $gastosExtra > 0.0) {
        $gastosFijos += $gastosExtra;
      }

      // Margen bruto: 100% para servicios puros (sin COGS).
      $margenBruto = 1.0;

      // Punto de equilibrio en clientes (redondeo hacia arriba).
      $puntoEquilibrioExacto = $gastosFijos / ($precioCliente * $margenBruto);
      $puntoEquilibrioClientes = ceil($puntoEquilibrioExacto);

      // Punto de equilibrio en euros.
      $puntoEquilibrioEuros = $gastosFijos / $margenBruto;

      // Generar escenarios de proyección.
      $escenarios = [];
      foreach (self::ESCENARIOS_CLIENTES as $numClientes) {
        $ingresos = $numClientes * $precioCliente;
        $escenarios[] = [
          'clientes' => $numClientes,
          'ingresos' => round($ingresos, 2),
          'beneficio_neto' => round($ingresos - $gastosFijos, 2),
        ];
      }

      return [
        'gastos_fijos' => round($gastosFijos, 2),
        'precio_cliente' => round($precioCliente, 2),
        'margen_bruto' => $margenBruto,
        'punto_equilibrio_clientes' => $puntoEquilibrioClientes,
        'punto_equilibrio_euros' => round($puntoEquilibrioEuros, 2),
        'escenarios' => $escenarios,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error calculando punto de equilibrio para pack @pack (@modalidad): @message', [
        '@pack' => $packTipo,
        '@modalidad' => $modalidad,
        '@message' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

  /**
   * Devuelve la tabla de precios por pack y modalidad.
   *
   * Útil para renderizar selectores de pack/tier en formularios y
   * dashboards del participante.
   *
   * @return array<string, array<string, float>>
   *   Array indexado por pack_tipo, cada uno con sus modalidades y precios.
   */
  public function getPackPricing(): array {
    return self::PRECIOS_PACK;
  }

  /**
   * Devuelve el total de gastos fijos base mensuales.
   *
   * @return float
   *   Suma de todos los conceptos en GASTOS_FIJOS_BASE (€/mes).
   */
  public function getGastosFijosBase(): float {
    return array_sum(self::GASTOS_FIJOS_BASE);
  }

  /**
   * Devuelve el desglose de gastos fijos base.
   *
   * @return array<string, float>
   *   Array con cada concepto y su importe mensual.
   */
  public function getGastosFijosDesglose(): array {
    return self::GASTOS_FIJOS_BASE;
  }

}
