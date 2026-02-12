<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_knowledge\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Servicio de disclaimers legales para respuestas del asistente juridico.
 *
 * Genera textos de descargo de responsabilidad traducibles segun el
 * nivel configurado (standard, enhanced, critical). Estos disclaimers
 * se anaden automaticamente a las respuestas del pipeline RAG legal.
 *
 * NIVELES:
 * - standard: Aviso basico de que no es asesoramiento profesional.
 * - enhanced: Anade referencia a posible desactualizacion de datos.
 * - critical: Anade recomendacion de consultar con un profesional.
 */
class LegalDisclaimerService {

  use StringTranslationTrait;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Factory de configuracion.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Obtiene el texto de disclaimer segun el nivel configurado o indicado.
   *
   * @param string|null $level
   *   Nivel de disclaimer ('standard', 'enhanced', 'critical').
   *   Si es NULL, usa el nivel de la configuracion del modulo.
   *
   * @return string
   *   Texto del disclaimer traducido.
   */
  public function getDisclaimer(?string $level = NULL): string {
    if ($level === NULL) {
      $config = $this->configFactory->get('jaraba_legal_knowledge.settings');
      $level = $config->get('disclaimer_level') ?: 'standard';
    }

    $disclaimer = (string) $this->t(
      'Esta informacion es orientativa y no constituye asesoramiento juridico profesional.'
    );

    if (in_array($level, ['enhanced', 'critical'], TRUE)) {
      $disclaimer .= ' ' . (string) $this->t(
        'Los datos provienen del BOE y pueden estar desactualizados. Consulte siempre la version oficial.'
      );
    }

    if ($level === 'critical') {
      $disclaimer .= ' ' . (string) $this->t(
        'En caso de duda, consulte con un profesional del derecho antes de tomar decisiones.'
      );
    }

    return $disclaimer;
  }

}
