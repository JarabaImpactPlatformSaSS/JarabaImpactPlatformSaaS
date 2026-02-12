<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_knowledge\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\jaraba_legal_knowledge\Service\TaxCalculatorService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador de calculadoras fiscales (frontend + API).
 *
 * PROPOSITO:
 * Renderiza la pagina de calculadoras fiscales para el usuario final
 * (no admin) y expone los endpoints API para calcular IRPF e IVA.
 *
 * FUNCIONALIDADES:
 * - Pagina frontend con interfaz de calculadora IRPF e IVA
 * - API de calculo IRPF con tramos, deducciones y situacion personal
 * - API de calculo IVA con tipo general, reducido y superreducido
 *
 * RUTAS:
 * - GET /legal/calculadoras -> page()
 * - POST /api/v1/legal/calculators/irpf -> irpf()
 * - POST /api/v1/legal/calculators/iva -> iva()
 *
 * @package Drupal\jaraba_legal_knowledge\Controller
 */
class TaxCalculatorController extends ControllerBase {

  /**
   * El servicio de calculadora fiscal.
   *
   * @var \Drupal\jaraba_legal_knowledge\Service\TaxCalculatorService
   */
  protected TaxCalculatorService $taxCalculator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->taxCalculator = $container->get('jaraba_legal_knowledge.tax_calculator');
    return $instance;
  }

  /**
   * Renderiza la pagina de calculadoras fiscales.
   *
   * Pagina frontend (no admin) que muestra las calculadoras
   * de IRPF e IVA con formularios interactivos.
   *
   * @return array
   *   Render array con #theme => 'legal_calculator_panel'.
   */
  public function page(): array {
    return [
      '#theme' => 'legal_calculator_panel',
      '#calculator_type' => 'irpf',
      '#data' => [],
      '#labels' => [
        'title' => $this->t('Calculadoras Fiscales'),
        'subtitle' => $this->t('Herramientas de calculo orientativo para IRPF e IVA'),
        'irpf_tab' => $this->t('IRPF'),
        'iva_tab' => $this->t('IVA'),
        'calculate' => $this->t('Calcular'),
        'reset' => $this->t('Limpiar'),
        'disclaimer' => $this->t('Los calculos son orientativos y no sustituyen el asesoramiento fiscal profesional.'),
        'gross_income' => $this->t('Ingresos brutos anuales'),
        'personal_situation' => $this->t('Situacion personal'),
        'num_children' => $this->t('Numero de hijos'),
        'disability_percentage' => $this->t('Porcentaje de discapacidad'),
        'autonomous_community' => $this->t('Comunidad Autonoma'),
        'base_amount' => $this->t('Importe base'),
        'iva_type' => $this->t('Tipo de IVA'),
        'result_title' => $this->t('Resultado'),
        'loading' => $this->t('Calculando...'),
      ],
      '#urls' => [
        'api_irpf' => Url::fromRoute('jaraba_legal_knowledge.api.calculators.irpf')->toString(),
        'api_iva' => Url::fromRoute('jaraba_legal_knowledge.api.calculators.iva')->toString(),
        'legal_page' => Url::fromRoute('jaraba_legal_knowledge.query_page')->toString(),
      ],
      '#attached' => [
        'library' => [
          'jaraba_legal_knowledge/legal-calculators',
        ],
        'drupalSettings' => [
          'jarabaLegalCalculators' => [
            'apiIrpfUrl' => Url::fromRoute('jaraba_legal_knowledge.api.calculators.irpf')->toString(),
            'apiIvaUrl' => Url::fromRoute('jaraba_legal_knowledge.api.calculators.iva')->toString(),
          ],
        ],
      ],
    ];
  }

  /**
   * Endpoint API: Calculo de IRPF.
   *
   * POST /api/v1/legal/calculators/irpf
   *
   * Calcula la cuota de IRPF en base a los ingresos brutos,
   * situacion personal, hijos a cargo, discapacidad y comunidad
   * autonoma del contribuyente.
   *
   * Body JSON esperado:
   * {
   *   "gross_income": 35000.00,
   *   "personal_situation": "single|married_joint|married_separate|single_parent",
   *   "num_children": 2,
   *   "disability_percentage": 0,
   *   "autonomous_community": "andalucia"
   * }
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP con body JSON.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con estructura {success: bool, data: {gross_income, taxable_base, brackets, total_tax, effective_rate, net_income, deductions}}.
   */
  public function irpf(Request $request): JsonResponse {
    try {
      $content = json_decode($request->getContent(), TRUE);

      if (!$content || !isset($content['gross_income'])) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => $this->t('Datos invalidos: se requiere el campo gross_income.'),
        ], 400);
      }

      // Validar gross_income.
      $gross_income = (float) $content['gross_income'];
      if ($gross_income < 0 || $gross_income > 10000000) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => $this->t('Los ingresos brutos deben estar entre 0 y 10.000.000.'),
        ], 400);
      }

      // Validar personal_situation.
      $allowed_situations = ['single', 'married_joint', 'married_separate', 'single_parent'];
      $personal_situation = $content['personal_situation'] ?? 'single';
      if (!in_array($personal_situation, $allowed_situations, TRUE)) {
        $personal_situation = 'single';
      }

      // Validar num_children.
      $num_children = max(0, min((int) ($content['num_children'] ?? 0), 20));

      // Validar disability_percentage.
      $disability_percentage = max(0.0, min((float) ($content['disability_percentage'] ?? 0), 100.0));

      // Validar autonomous_community.
      $autonomous_community = mb_substr($content['autonomous_community'] ?? 'general', 0, 50);

      // Calcular IRPF.
      $result = $this->taxCalculator->calculateIrpf([
        'gross_income' => $gross_income,
        'personal_situation' => $personal_situation,
        'num_children' => $num_children,
        'disability_percentage' => $disability_percentage,
        'autonomous_community' => $autonomous_community,
      ]);

      return new JsonResponse([
        'success' => TRUE,
        'data' => $result,
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_legal_knowledge')->error('Error al calcular IRPF: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'success' => FALSE,
        'message' => $this->t('Error al realizar el calculo de IRPF.'),
      ], 500);
    }
  }

  /**
   * Endpoint API: Calculo de IVA.
   *
   * POST /api/v1/legal/calculators/iva
   *
   * Calcula el IVA aplicable a un importe base segun el tipo
   * de IVA seleccionado (general 21%, reducido 10%, superreducido 4%).
   *
   * Body JSON esperado:
   * {
   *   "base_amount": 1000.00,
   *   "iva_type": "general|reducido|superreducido"
   * }
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP con body JSON.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con estructura {success: bool, data: {base_amount, iva_type, iva_rate, iva_amount, total}}.
   */
  public function iva(Request $request): JsonResponse {
    try {
      $content = json_decode($request->getContent(), TRUE);

      if (!$content || !isset($content['base_amount'])) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => $this->t('Datos invalidos: se requiere el campo base_amount.'),
        ], 400);
      }

      // Validar base_amount.
      $base_amount = (float) $content['base_amount'];
      if ($base_amount < 0 || $base_amount > 100000000) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => $this->t('El importe base debe estar entre 0 y 100.000.000.'),
        ], 400);
      }

      // Validar iva_type.
      $allowed_types = ['general', 'reducido', 'superreducido'];
      $iva_type = $content['iva_type'] ?? 'general';
      if (!in_array($iva_type, $allowed_types, TRUE)) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => $this->t('Tipo de IVA invalido. Valores permitidos: @types', [
            '@types' => implode(', ', $allowed_types),
          ]),
        ], 400);
      }

      // Calcular IVA.
      $result = $this->taxCalculator->calculateIva([
        'base_amount' => $base_amount,
        'iva_type' => $iva_type,
      ]);

      return new JsonResponse([
        'success' => TRUE,
        'data' => $result,
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_legal_knowledge')->error('Error al calcular IVA: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'success' => FALSE,
        'message' => $this->t('Error al realizar el calculo de IVA.'),
      ], 500);
    }
  }

}
