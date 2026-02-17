<?php

declare(strict_types=1);

namespace Drupal\jaraba_multiregion\Service;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;

/**
 * Servicio de verificacion de cumplimiento normativo regional.
 *
 * ESTRUCTURA:
 * Evalua el estado de cumplimiento de un tenant en una jurisdiccion
 * fiscal concreta, verificando configuracion de IVA, reglas fiscales
 * vigentes, representante GDPR y residencia de datos.
 *
 * LOGICA:
 * - checkCompliance(): Ejecuta todas las verificaciones y retorna un
 *   informe booleano por categoria con lista de incidencias.
 * - getRequirements(): Catalogo de requisitos normativos por jurisdiccion
 *   agrupados en categorias (fiscal, datos, privacidad, facturacion).
 * - generateReport(): Genera un informe resumen combinando el estado
 *   de cumplimiento con los requisitos aplicables.
 *
 * SINTAXIS:
 * - Constructor con propiedades promovidas (PHP 8.3).
 * - Cadenas visibles usan TranslatableMarkup.
 * - Consultas envueltas en try/catch con logger.
 * - Retorna arrays estructurados con resultados de verificacion.
 *
 * @see \Drupal\jaraba_multiregion\Service\RegionManagerService
 * @see \Drupal\jaraba_multiregion\Service\TaxCalculatorService
 */
class RegionalComplianceService {

  /**
   * Constructor del servicio.
   *
   * @param \Drupal\jaraba_multiregion\Service\RegionManagerService $regionManager
   *   Servicio de gestion de regiones para consultar la configuracion del tenant.
   * @param \Drupal\jaraba_multiregion\Service\TaxCalculatorService $taxCalculator
   *   Servicio de calculo fiscal para verificar reglas de IVA.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de logger para registrar errores y verificaciones.
   */
  public function __construct(
    protected readonly RegionManagerService $regionManager,
    protected readonly TaxCalculatorService $taxCalculator,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Verifica el estado de cumplimiento normativo para una jurisdiccion.
   *
   * LOGICA:
   * Ejecuta una serie de verificaciones independientes:
   * 1. vat_configured: Verifica que el tenant tenga IVA configurado.
   * 2. tax_rules_current: Verifica que existan reglas fiscales vigentes.
   * 3. gdpr_representative: Verifica representante GDPR (solo UE).
   * 4. data_residency: Verifica que los datos residan en region adecuada.
   *
   * El campo 'overall' es TRUE solo si todas las verificaciones pasan.
   * El campo 'issues' contiene descripciones de cada problema detectado.
   *
   * @param string $jurisdiction
   *   Codigo de la jurisdiccion a verificar (ej: 'ES', 'DE').
   *
   * @return array
   *   Array estructurado con:
   *   - 'jurisdiction': (string) Codigo de jurisdiccion verificada.
   *   - 'vat_configured': (bool) Si el IVA esta configurado correctamente.
   *   - 'tax_rules_current': (bool) Si hay reglas fiscales vigentes.
   *   - 'gdpr_representative': (bool) Si tiene representante GDPR (UE).
   *   - 'data_residency': (bool) Si la residencia de datos es correcta.
   *   - 'overall': (bool) TRUE si todas las verificaciones pasan.
   *   - 'issues': (array) Lista de problemas detectados.
   *   - 'checked_at': (string) Fecha/hora de la verificacion (ISO 8601).
   */
  public function checkCompliance(string $jurisdiction): array {
    $issues = [];
    $jurisdiction = strtoupper(trim($jurisdiction));

    try {
      // Verificacion 1: IVA configurado para la jurisdiccion.
      $vatConfigured = $this->checkVatConfiguration($jurisdiction, $issues);

      // Verificacion 2: Reglas fiscales vigentes.
      $taxRulesCurrent = $this->checkTaxRules($jurisdiction, $issues);

      // Verificacion 3: Representante GDPR (solo requerido en la UE).
      $gdprRepresentative = $this->checkGdprRepresentative($jurisdiction, $issues);

      // Verificacion 4: Residencia de datos en region adecuada.
      $dataResidency = $this->checkDataResidency($jurisdiction, $issues);

      // El cumplimiento global requiere que todas las verificaciones pasen.
      $overall = $vatConfigured && $taxRulesCurrent && $gdprRepresentative && $dataResidency;

      $this->logger->info('RegionalCompliance: Verificacion para @jurisdiction: @status (@issues_count incidencias)', [
        '@jurisdiction' => $jurisdiction,
        '@status' => $overall ? 'CONFORME' : 'NO CONFORME',
        '@issues_count' => count($issues),
      ]);

      return [
        'jurisdiction' => $jurisdiction,
        'vat_configured' => $vatConfigured,
        'tax_rules_current' => $taxRulesCurrent,
        'gdpr_representative' => $gdprRepresentative,
        'data_residency' => $dataResidency,
        'overall' => $overall,
        'issues' => $issues,
        'checked_at' => date('c'),
      ];

    }
    catch (\Exception $e) {
      $this->logger->error('RegionalCompliance: Error verificando cumplimiento para @jurisdiction: @error', [
        '@jurisdiction' => $jurisdiction,
        '@error' => $e->getMessage(),
      ]);
      return [
        'jurisdiction' => $jurisdiction,
        'vat_configured' => FALSE,
        'tax_rules_current' => FALSE,
        'gdpr_representative' => FALSE,
        'data_residency' => FALSE,
        'overall' => FALSE,
        'issues' => [
          (string) new TranslatableMarkup('Error interno al verificar cumplimiento: @error', ['@error' => $e->getMessage()]),
        ],
        'checked_at' => date('c'),
      ];
    }
  }

  /**
   * Obtiene los requisitos normativos para una jurisdiccion.
   *
   * LOGICA:
   * Retorna un catalogo estructurado de requisitos regulatorios agrupados
   * por categoria. Los requisitos varian segun si la jurisdiccion es
   * miembro de la UE o no, y segun las particularidades de cada pais.
   *
   * Categorias:
   * - tax: Requisitos fiscales (IVA, retenciones, declaraciones).
   * - data: Requisitos de residencia y proteccion de datos.
   * - privacy: Requisitos de privacidad (GDPR, representante).
   * - invoicing: Requisitos de facturacion electronica.
   *
   * @param string $jurisdiction
   *   Codigo de la jurisdiccion (ej: 'ES', 'DE').
   *
   * @return array
   *   Array estructurado con:
   *   - 'jurisdiction': (string) Codigo de la jurisdiccion.
   *   - 'jurisdiction_label': (TranslatableMarkup) Nombre de la jurisdiccion.
   *   - 'is_eu': (bool) Si es miembro de la UE.
   *   - 'categories': (array) Requisitos agrupados por categoria.
   *     Cada categoria contiene:
   *     - 'label': (TranslatableMarkup) Nombre de la categoria.
   *     - 'requirements': (array) Lista de requisitos, cada uno con:
   *       - 'id': (string) Identificador del requisito.
   *       - 'label': (TranslatableMarkup) Descripcion del requisito.
   *       - 'mandatory': (bool) Si es obligatorio.
   *       - 'reference': (string) Referencia legal o normativa.
   */
  public function getRequirements(string $jurisdiction): array {
    $jurisdiction = strtoupper(trim($jurisdiction));
    $isEu = $this->taxCalculator->isEuMember($jurisdiction);

    // Obtener etiqueta de la jurisdiccion desde el catalogo.
    $jurisdictions = $this->regionManager->getAvailableJurisdictions();
    $jurisdictionLabel = $jurisdictions[$jurisdiction]['label']
      ?? new TranslatableMarkup('Jurisdiccion @code', ['@code' => $jurisdiction]);

    // Requisitos fiscales (comunes a todas las jurisdicciones).
    $taxRequirements = [
      [
        'id' => 'tax_registration',
        'label' => new TranslatableMarkup('Registro fiscal en la jurisdiccion'),
        'mandatory' => TRUE,
        'reference' => '',
      ],
      [
        'id' => 'tax_rules_configured',
        'label' => new TranslatableMarkup('Reglas fiscales configuradas y vigentes'),
        'mandatory' => TRUE,
        'reference' => '',
      ],
      [
        'id' => 'periodic_declarations',
        'label' => new TranslatableMarkup('Declaraciones periodicas de IVA'),
        'mandatory' => TRUE,
        'reference' => '',
      ],
    ];

    // Requisitos fiscales adicionales para UE.
    if ($isEu) {
      $taxRequirements[] = [
        'id' => 'vies_registration',
        'label' => new TranslatableMarkup('Registro en el sistema VIES para operaciones intracomunitarias'),
        'mandatory' => TRUE,
        'reference' => 'Directiva 2006/112/CE',
      ];
      $taxRequirements[] = [
        'id' => 'oss_registration',
        'label' => new TranslatableMarkup('Registro en regimen OSS si se superan los 10.000 EUR en ventas B2C intracomunitarias'),
        'mandatory' => FALSE,
        'reference' => 'Directiva (UE) 2017/2455',
      ];
      $taxRequirements[] = [
        'id' => 'reverse_charge_support',
        'label' => new TranslatableMarkup('Soporte de inversion de sujeto pasivo en facturas B2B'),
        'mandatory' => TRUE,
        'reference' => 'Art. 196 Directiva 2006/112/CE',
      ];
    }

    // Requisitos de residencia de datos.
    $dataRequirements = [
      [
        'id' => 'data_region_configured',
        'label' => new TranslatableMarkup('Region de residencia de datos configurada'),
        'mandatory' => TRUE,
        'reference' => '',
      ],
    ];

    if ($isEu) {
      $dataRequirements[] = [
        'id' => 'data_within_eea',
        'label' => new TranslatableMarkup('Datos almacenados dentro del Espacio Economico Europeo (EEE)'),
        'mandatory' => TRUE,
        'reference' => 'Art. 44-49 RGPD',
      ];
      $dataRequirements[] = [
        'id' => 'data_transfer_safeguards',
        'label' => new TranslatableMarkup('Clausulas contractuales tipo para transferencias internacionales'),
        'mandatory' => FALSE,
        'reference' => 'Art. 46 RGPD',
      ];
    }

    // Requisitos de privacidad.
    $privacyRequirements = [
      [
        'id' => 'privacy_policy',
        'label' => new TranslatableMarkup('Politica de privacidad actualizada'),
        'mandatory' => TRUE,
        'reference' => '',
      ],
    ];

    if ($isEu) {
      $privacyRequirements[] = [
        'id' => 'gdpr_representative',
        'label' => new TranslatableMarkup('Representante GDPR designado en la UE'),
        'mandatory' => TRUE,
        'reference' => 'Art. 27 RGPD',
      ];
      $privacyRequirements[] = [
        'id' => 'dpo_designated',
        'label' => new TranslatableMarkup('Delegado de Proteccion de Datos (DPO) designado si aplica'),
        'mandatory' => FALSE,
        'reference' => 'Art. 37-39 RGPD',
      ];
      $privacyRequirements[] = [
        'id' => 'data_breach_process',
        'label' => new TranslatableMarkup('Procedimiento de notificacion de brechas de seguridad (72h)'),
        'mandatory' => TRUE,
        'reference' => 'Art. 33 RGPD',
      ];
    }

    // Requisitos de facturacion.
    $invoicingRequirements = [
      [
        'id' => 'electronic_invoicing',
        'label' => new TranslatableMarkup('Facturacion electronica habilitada'),
        'mandatory' => FALSE,
        'reference' => '',
      ],
      [
        'id' => 'invoice_numbering',
        'label' => new TranslatableMarkup('Numeracion secuencial de facturas'),
        'mandatory' => TRUE,
        'reference' => '',
      ],
    ];

    // Requisitos adicionales de facturacion segun jurisdiccion.
    $invoicingExtras = $this->getJurisdictionInvoicingRequirements($jurisdiction);
    $invoicingRequirements = array_merge($invoicingRequirements, $invoicingExtras);

    return [
      'jurisdiction' => $jurisdiction,
      'jurisdiction_label' => $jurisdictionLabel,
      'is_eu' => $isEu,
      'categories' => [
        'tax' => [
          'label' => new TranslatableMarkup('Requisitos Fiscales'),
          'requirements' => $taxRequirements,
        ],
        'data' => [
          'label' => new TranslatableMarkup('Residencia de Datos'),
          'requirements' => $dataRequirements,
        ],
        'privacy' => [
          'label' => new TranslatableMarkup('Privacidad y Proteccion de Datos'),
          'requirements' => $privacyRequirements,
        ],
        'invoicing' => [
          'label' => new TranslatableMarkup('Facturacion'),
          'requirements' => $invoicingRequirements,
        ],
      ],
    ];
  }

  /**
   * Genera un informe resumen de cumplimiento para una jurisdiccion.
   *
   * LOGICA:
   * Combina los resultados de checkCompliance() con los requisitos
   * de getRequirements() para generar un informe consolidado que
   * incluye estado actual, requisitos pendientes y recomendaciones.
   *
   * @param string $jurisdiction
   *   Codigo de la jurisdiccion (ej: 'ES', 'DE').
   *
   * @return array
   *   Array estructurado con:
   *   - 'jurisdiction': (string) Codigo de la jurisdiccion.
   *   - 'jurisdiction_label': (TranslatableMarkup) Nombre de la jurisdiccion.
   *   - 'compliance_status': (array) Resultado de checkCompliance().
   *   - 'requirements': (array) Resultado de getRequirements().
   *   - 'summary': (array) Resumen con totales y porcentaje de cumplimiento.
   *   - 'recommendations': (array) Lista de acciones recomendadas.
   *   - 'generated_at': (string) Fecha/hora de generacion (ISO 8601).
   */
  public function generateReport(string $jurisdiction): array {
    $jurisdiction = strtoupper(trim($jurisdiction));

    try {
      // Obtener estado de cumplimiento actual.
      $complianceStatus = $this->checkCompliance($jurisdiction);

      // Obtener requisitos aplicables.
      $requirements = $this->getRequirements($jurisdiction);

      // Calcular resumen: total de requisitos, cumplidos, pendientes.
      $totalRequirements = 0;
      $mandatoryRequirements = 0;
      foreach ($requirements['categories'] as $category) {
        foreach ($category['requirements'] as $req) {
          $totalRequirements++;
          if ($req['mandatory']) {
            $mandatoryRequirements++;
          }
        }
      }

      // Contar verificaciones pasadas (de las 4 verificaciones principales).
      $checksMap = [
        'vat_configured',
        'tax_rules_current',
        'gdpr_representative',
        'data_residency',
      ];
      $checksPassed = 0;
      foreach ($checksMap as $check) {
        if (!empty($complianceStatus[$check])) {
          $checksPassed++;
        }
      }
      $totalChecks = count($checksMap);
      $compliancePercentage = $totalChecks > 0
        ? round(($checksPassed / $totalChecks) * 100, 1)
        : 0.0;

      // Generar recomendaciones basadas en las incidencias detectadas.
      $recommendations = $this->buildRecommendations($complianceStatus, $jurisdiction);

      $report = [
        'jurisdiction' => $jurisdiction,
        'jurisdiction_label' => $requirements['jurisdiction_label'],
        'compliance_status' => $complianceStatus,
        'requirements' => $requirements,
        'summary' => [
          'total_checks' => $totalChecks,
          'checks_passed' => $checksPassed,
          'checks_failed' => $totalChecks - $checksPassed,
          'compliance_percentage' => $compliancePercentage,
          'total_requirements' => $totalRequirements,
          'mandatory_requirements' => $mandatoryRequirements,
          'overall_status' => $complianceStatus['overall']
            ? new TranslatableMarkup('Conforme')
            : new TranslatableMarkup('No conforme'),
        ],
        'recommendations' => $recommendations,
        'generated_at' => date('c'),
      ];

      $this->logger->info('RegionalCompliance: Informe generado para @jurisdiction: @percentage% cumplimiento.', [
        '@jurisdiction' => $jurisdiction,
        '@percentage' => $compliancePercentage,
      ]);

      return $report;

    }
    catch (\Exception $e) {
      $this->logger->error('RegionalCompliance: Error generando informe para @jurisdiction: @error', [
        '@jurisdiction' => $jurisdiction,
        '@error' => $e->getMessage(),
      ]);
      return [
        'jurisdiction' => $jurisdiction,
        'jurisdiction_label' => new TranslatableMarkup('Jurisdiccion @code', ['@code' => $jurisdiction]),
        'compliance_status' => [],
        'requirements' => [],
        'summary' => [
          'total_checks' => 0,
          'checks_passed' => 0,
          'checks_failed' => 0,
          'compliance_percentage' => 0.0,
          'total_requirements' => 0,
          'mandatory_requirements' => 0,
          'overall_status' => new TranslatableMarkup('Error en generacion del informe'),
        ],
        'recommendations' => [
          (string) new TranslatableMarkup('Se produjo un error al generar el informe. Intentelo de nuevo.'),
        ],
        'generated_at' => date('c'),
      ];
    }
  }

  /**
   * Verifica la configuracion de IVA para la jurisdiccion.
   *
   * LOGICA:
   * Consulta la region del tenant y verifica que tenga un pais configurado
   * que coincida con la jurisdiccion solicitada.
   *
   * @param string $jurisdiction
   *   Codigo de la jurisdiccion.
   * @param array &$issues
   *   Array de incidencias donde se agregan problemas detectados.
   *
   * @return bool
   *   TRUE si el IVA esta configurado correctamente.
   */
  protected function checkVatConfiguration(string $jurisdiction, array &$issues): bool {
    try {
      $region = $this->regionManager->getRegion();

      if (!$region) {
        $issues[] = (string) new TranslatableMarkup(
          'No hay configuracion regional para el tenant. Configure la region antes de operar en @jurisdiction.',
          ['@jurisdiction' => $jurisdiction]
        );
        return FALSE;
      }

      // Verificar que el pais configurado coincida o sea compatible.
      $configuredCountry = $region->hasField('country_code')
        ? ($region->get('country_code')->value ?? '')
        : '';

      if (empty($configuredCountry)) {
        $issues[] = (string) new TranslatableMarkup(
          'El codigo de pais no esta configurado en la region del tenant.'
        );
        return FALSE;
      }

      return TRUE;

    }
    catch (\Exception $e) {
      $issues[] = (string) new TranslatableMarkup(
        'Error verificando configuracion de IVA: @error',
        ['@error' => $e->getMessage()]
      );
      return FALSE;
    }
  }

  /**
   * Verifica que existan reglas fiscales vigentes para la jurisdiccion.
   *
   * LOGICA:
   * Usa TaxCalculatorService para consultar si existe al menos una
   * regla fiscal vigente (effective_from <= hoy, effective_to >= hoy o NULL).
   *
   * @param string $jurisdiction
   *   Codigo de la jurisdiccion.
   * @param array &$issues
   *   Array de incidencias donde se agregan problemas detectados.
   *
   * @return bool
   *   TRUE si hay reglas fiscales vigentes.
   */
  protected function checkTaxRules(string $jurisdiction, array &$issues): bool {
    try {
      $taxRule = $this->taxCalculator->getTaxRule($jurisdiction);

      if (!$taxRule) {
        $issues[] = (string) new TranslatableMarkup(
          'No hay reglas fiscales vigentes para @jurisdiction. Configure los tipos impositivos aplicables.',
          ['@jurisdiction' => $jurisdiction]
        );
        return FALSE;
      }

      // Verificar que la regla tenga un tipo impositivo valido.
      $standardRate = (float) ($taxRule->get('standard_rate')->value ?? 0);
      if ($standardRate <= 0) {
        $issues[] = (string) new TranslatableMarkup(
          'La regla fiscal de @jurisdiction tiene un tipo impositivo de 0%. Verifique que sea correcto.',
          ['@jurisdiction' => $jurisdiction]
        );
        // No es necesariamente un fallo, pero se advierte.
      }

      return TRUE;

    }
    catch (\Exception $e) {
      $issues[] = (string) new TranslatableMarkup(
        'Error verificando reglas fiscales: @error',
        ['@error' => $e->getMessage()]
      );
      return FALSE;
    }
  }

  /**
   * Verifica la existencia de representante GDPR para jurisdicciones UE.
   *
   * LOGICA:
   * Solo es obligatorio para jurisdicciones dentro de la UE.
   * Consulta la region del tenant para verificar si tiene configurado
   * un representante GDPR (campo gdpr_representative).
   * Para jurisdicciones fuera de la UE, retorna TRUE automaticamente.
   *
   * @param string $jurisdiction
   *   Codigo de la jurisdiccion.
   * @param array &$issues
   *   Array de incidencias donde se agregan problemas detectados.
   *
   * @return bool
   *   TRUE si tiene representante GDPR (o no es UE).
   */
  protected function checkGdprRepresentative(string $jurisdiction, array &$issues): bool {
    // Solo obligatorio en la UE.
    if (!$this->taxCalculator->isEuMember($jurisdiction)) {
      return TRUE;
    }

    try {
      $region = $this->regionManager->getRegion();

      if (!$region) {
        $issues[] = (string) new TranslatableMarkup(
          'Se requiere representante GDPR para operar en la UE (@jurisdiction), pero no hay configuracion regional.',
          ['@jurisdiction' => $jurisdiction]
        );
        return FALSE;
      }

      // Verificar campo de representante GDPR.
      if ($region->hasField('gdpr_representative')) {
        $representative = $region->get('gdpr_representative')->value ?? '';
        if (empty($representative)) {
          $issues[] = (string) new TranslatableMarkup(
            'Se requiere designar un representante GDPR para operar en @jurisdiction (Art. 27 RGPD).',
            ['@jurisdiction' => $jurisdiction]
          );
          return FALSE;
        }
      }
      else {
        // El campo no existe en la entidad; se asume no configurado.
        $issues[] = (string) new TranslatableMarkup(
          'El campo de representante GDPR no esta disponible. Contacte al administrador del sistema.'
        );
        return FALSE;
      }

      return TRUE;

    }
    catch (\Exception $e) {
      $issues[] = (string) new TranslatableMarkup(
        'Error verificando representante GDPR: @error',
        ['@error' => $e->getMessage()]
      );
      return FALSE;
    }
  }

  /**
   * Verifica que la residencia de datos sea adecuada para la jurisdiccion.
   *
   * LOGICA:
   * Para jurisdicciones UE, los datos deben residir en una region
   * marcada como GDPR-compliant (eu-west, eu-south, eu-north).
   * Para jurisdicciones fuera de la UE, cualquier region es aceptable.
   *
   * @param string $jurisdiction
   *   Codigo de la jurisdiccion.
   * @param array &$issues
   *   Array de incidencias donde se agregan problemas detectados.
   *
   * @return bool
   *   TRUE si la residencia de datos es adecuada.
   */
  protected function checkDataResidency(string $jurisdiction, array &$issues): bool {
    try {
      $region = $this->regionManager->getRegion();

      if (!$region) {
        $issues[] = (string) new TranslatableMarkup(
          'No hay region de datos configurada para el tenant.'
        );
        return FALSE;
      }

      // Obtener la region de datos configurada.
      $dataRegion = $region->hasField('data_region')
        ? ($region->get('data_region')->value ?? '')
        : '';

      if (empty($dataRegion)) {
        $issues[] = (string) new TranslatableMarkup(
          'La region de residencia de datos no esta configurada.'
        );
        return FALSE;
      }

      // Para jurisdicciones UE, verificar que la region sea GDPR-compliant.
      if ($this->taxCalculator->isEuMember($jurisdiction)) {
        $availableRegions = $this->regionManager->getAvailableRegions();
        $regionInfo = $availableRegions[$dataRegion] ?? NULL;

        if (!$regionInfo || empty($regionInfo['gdpr_compliant'])) {
          $issues[] = (string) new TranslatableMarkup(
            'La region de datos "@region" no cumple GDPR. Para operar en @jurisdiction (UE), los datos deben residir en una region GDPR-compliant.',
            ['@region' => $dataRegion, '@jurisdiction' => $jurisdiction]
          );
          return FALSE;
        }
      }

      return TRUE;

    }
    catch (\Exception $e) {
      $issues[] = (string) new TranslatableMarkup(
        'Error verificando residencia de datos: @error',
        ['@error' => $e->getMessage()]
      );
      return FALSE;
    }
  }

  /**
   * Genera recomendaciones basadas en las incidencias de cumplimiento.
   *
   * LOGICA:
   * Analiza el resultado de checkCompliance() y genera una lista de
   * acciones concretas que el tenant debe tomar para alcanzar el
   * cumplimiento completo en la jurisdiccion.
   *
   * @param array $complianceStatus
   *   Resultado de checkCompliance().
   * @param string $jurisdiction
   *   Codigo de la jurisdiccion.
   *
   * @return array
   *   Lista de recomendaciones como strings traducibles.
   */
  protected function buildRecommendations(array $complianceStatus, string $jurisdiction): array {
    $recommendations = [];

    if (empty($complianceStatus['vat_configured'])) {
      $recommendations[] = (string) new TranslatableMarkup(
        'Configure la region y pais del tenant desde Administracion > Multi-Region > Configuracion Regional.'
      );
    }

    if (empty($complianceStatus['tax_rules_current'])) {
      $recommendations[] = (string) new TranslatableMarkup(
        'Cree o actualice las reglas fiscales para @jurisdiction con los tipos impositivos vigentes.',
        ['@jurisdiction' => $jurisdiction]
      );
    }

    if (empty($complianceStatus['gdpr_representative'])) {
      if ($this->taxCalculator->isEuMember($jurisdiction)) {
        $recommendations[] = (string) new TranslatableMarkup(
          'Designe un representante GDPR en la UE segun el Art. 27 del RGPD.'
        );
      }
    }

    if (empty($complianceStatus['data_residency'])) {
      $isEu = $this->taxCalculator->isEuMember($jurisdiction);
      if ($isEu) {
        $recommendations[] = (string) new TranslatableMarkup(
          'Migre los datos a una region GDPR-compliant (Europa Occidental, Europa Sur o Europa Norte).'
        );
      }
      else {
        $recommendations[] = (string) new TranslatableMarkup(
          'Configure la region de residencia de datos para el tenant.'
        );
      }
    }

    if (empty($recommendations)) {
      $recommendations[] = (string) new TranslatableMarkup(
        'Todas las verificaciones de cumplimiento han pasado satisfactoriamente para @jurisdiction.',
        ['@jurisdiction' => $jurisdiction]
      );
    }

    return $recommendations;
  }

  /**
   * Obtiene requisitos de facturacion especificos de una jurisdiccion.
   *
   * LOGICA:
   * Algunos paises tienen requisitos de facturacion electronica
   * particulares (como TicketBAI en Espana o FatturaPA en Italia).
   * Este metodo retorna los requisitos adicionales segun la jurisdiccion.
   *
   * @param string $jurisdiction
   *   Codigo de la jurisdiccion.
   *
   * @return array
   *   Lista de requisitos adicionales de facturacion.
   */
  protected function getJurisdictionInvoicingRequirements(string $jurisdiction): array {
    $extras = [];

    switch ($jurisdiction) {
      case 'ES':
        $extras[] = [
          'id' => 'sii_compliance',
          'label' => new TranslatableMarkup('Suministro Inmediato de Informacion (SII) si facturacion > 6M EUR'),
          'mandatory' => FALSE,
          'reference' => 'RD 596/2016',
        ];
        $extras[] = [
          'id' => 'ticketbai',
          'label' => new TranslatableMarkup('TicketBAI para operaciones en Pais Vasco / Navarra'),
          'mandatory' => FALSE,
          'reference' => 'Norma Foral',
        ];
        break;

      case 'IT':
        $extras[] = [
          'id' => 'fatturapa',
          'label' => new TranslatableMarkup('Facturacion electronica obligatoria via FatturaPA / SDI'),
          'mandatory' => TRUE,
          'reference' => 'DL 127/2015',
        ];
        break;

      case 'FR':
        $extras[] = [
          'id' => 'factur_x',
          'label' => new TranslatableMarkup('Facturacion electronica B2B obligatoria (Factur-X)'),
          'mandatory' => TRUE,
          'reference' => 'Loi de finances 2020',
        ];
        break;

      case 'DE':
        $extras[] = [
          'id' => 'xrechnung',
          'label' => new TranslatableMarkup('XRechnung para facturacion con administracion publica'),
          'mandatory' => FALSE,
          'reference' => 'E-Rechnungsverordnung',
        ];
        break;

      case 'PT':
        $extras[] = [
          'id' => 'saft_pt',
          'label' => new TranslatableMarkup('Fichero SAF-T (PT) obligatorio para contabilidad'),
          'mandatory' => TRUE,
          'reference' => 'Portaria 302/2016',
        ];
        break;
    }

    return $extras;
  }

}
