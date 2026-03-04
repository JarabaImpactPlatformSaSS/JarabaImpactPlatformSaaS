<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\ecosistema_jaraba_core\ValueObject\FeatureGateResult;

/**
 * Router que despacha feature gate checks a servicios verticales.
 *
 * Centraliza el acceso a los 10 servicios de feature gating
 * existentes (uno por vertical) mediante un mapa vertical → service_id.
 */
class FeatureGateRouterService {

  /**
   * Mapa de vertical canonico → service_id.
   */
  private const VERTICAL_SERVICES = [
    'empleabilidad' => 'ecosistema_jaraba_core.employability_feature_gate',
    'emprendimiento' => 'ecosistema_jaraba_core.emprendimiento_feature_gate',
    'comercioconecta' => 'ecosistema_jaraba_core.comercio_conecta_feature_gate',
    'agroconecta' => 'ecosistema_jaraba_core.agroconecta_feature_gate',
    'jarabalex' => 'ecosistema_jaraba_core.jarabalex_feature_gate',
    'serviciosconecta' => 'ecosistema_jaraba_core.servicios_conecta_feature_gate',
    'andalucia_ei' => 'ecosistema_jaraba_core.andalucia_ei_feature_gate',
    'jaraba_content_hub' => 'ecosistema_jaraba_core.content_hub_feature_gate',
    'formacion' => 'ecosistema_jaraba_core.formacion_feature_gate',
    'demo' => 'ecosistema_jaraba_core.demo_feature_gate',
  ];

  /**
   * Verifica si una feature esta permitida en un vertical.
   *
   * @param string $vertical
   *   Vertical canonico (VERTICAL-CANONICAL-001).
   * @param string $featureKey
   *   Clave de la feature a verificar.
   *
   * @return \Drupal\ecosistema_jaraba_core\ValueObject\FeatureGateResult
   *   Resultado con allowed/denied y metadata.
   */
  public function check(string $vertical, string $featureKey): FeatureGateResult {
    $service = $this->getServiceForVertical($vertical);
    if (!$service) {
      return FeatureGateResult::allowed($featureKey, 'unknown', -1, 0);
    }

    try {
      $userId = \Drupal::currentUser()->id();
      return $service->check($userId, $featureKey);
    }
    catch (\Throwable) {
      // Graceful degradation: allow by default on service error.
      return FeatureGateResult::allowed($featureKey, 'unknown', -1, 0);
    }
  }

  /**
   * Obtiene el servicio de feature gate para un vertical.
   *
   * @param string $vertical
   *   Vertical canonico.
   *
   * @return object|null
   *   El servicio de feature gate, o NULL si no existe.
   */
  public function getServiceForVertical(string $vertical): ?object {
    $serviceId = self::VERTICAL_SERVICES[$vertical] ?? NULL;
    if ($serviceId === NULL) {
      return NULL;
    }

    if (!\Drupal::hasService($serviceId)) {
      return NULL;
    }

    try {
      return \Drupal::service($serviceId);
    }
    catch (\Throwable) {
      return NULL;
    }
  }

}
