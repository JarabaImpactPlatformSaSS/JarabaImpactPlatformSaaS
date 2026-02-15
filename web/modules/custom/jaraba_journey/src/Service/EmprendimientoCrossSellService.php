<?php

declare(strict_types=1);

namespace Drupal\jaraba_journey\Service;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\jaraba_journey\JourneyDefinition\EmprendimientoJourneyDefinition;
use Psr\Log\LoggerInterface;

/**
 * Servicio de ejecucion de ofertas cross-sell para emprendimiento.
 *
 * Escucha transiciones de estado del journey y ejecuta las reglas
 * de cross-sell definidas en EmprendimientoJourneyDefinition::getEmprendedorJourney().
 *
 * ARQUITECTURA:
 * - Se invoca desde JourneyEngineService tras cada transicion de estado.
 * - Presenta ofertas via notificacion in-app y email.
 * - Registra cada oferta presentada para analytics de conversion.
 *
 * PATRON DE REFERENCIA:
 * - jaraba_agroconecta_core.cross_sell_engine (CrossSellEngine.php)
 *
 * @see \Drupal\jaraba_journey\JourneyDefinition\EmprendimientoJourneyDefinition
 */
class EmprendimientoCrossSellService {

  use StringTranslationTrait;

  /**
   * Mapeo de eventos de transicion a claves cross-sell.
   *
   * Las claves 'after' en EMPRENDEDOR_JOURNEY['cross_sell'] se mapean
   * a eventos del journey engine para determinar cuando disparar.
   */
  protected const EVENT_TO_CROSS_SELL = [
    'diagnostic_completed' => 'diagnostic_completed',
    'plan_received' => 'diagnostic_completed',
    'mvp_validated' => 'before_mvp',
    'funding_secured' => 'funding_search',
    'scaling' => 'launch',
  ];

  /**
   * Constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del modulo.
   */
  public function __construct(
    protected LoggerInterface $logger,
  ) {}

  /**
   * Evalua y ejecuta ofertas cross-sell tras una transicion de estado.
   *
   * @param int $userId
   *   ID del usuario.
   * @param string $eventType
   *   Tipo de evento que disparo la transicion.
   * @param string $oldState
   *   Estado anterior del journey.
   * @param string $newState
   *   Estado nuevo del journey.
   *
   * @return array
   *   Array con las ofertas presentadas, vacio si no aplica ninguna.
   */
  public function evaluateOnTransition(int $userId, string $eventType, string $oldState, string $newState): array {
    $crossSellKey = self::EVENT_TO_CROSS_SELL[$eventType] ?? NULL;

    if (!$crossSellKey) {
      return [];
    }

    $journey = EmprendimientoJourneyDefinition::getEmprendedorJourney();
    $crossSellRules = $journey['cross_sell'] ?? [];
    $offersToPresent = [];

    foreach ($crossSellRules as $rule) {
      if ($rule['after'] === $crossSellKey) {
        $offersToPresent[] = $rule;
      }
    }

    if (empty($offersToPresent)) {
      return [];
    }

    $presentedOffers = [];

    foreach ($offersToPresent as $offer) {
      $result = $this->presentOffer($userId, $offer, $eventType);
      if ($result['presented']) {
        $presentedOffers[] = $result;
      }
    }

    if (!empty($presentedOffers)) {
      $this->logger->info('Cross-sell para usuario @user tras @event: @count ofertas presentadas.', [
        '@user' => $userId,
        '@event' => $eventType,
        '@count' => count($presentedOffers),
      ]);
    }

    return $presentedOffers;
  }

  /**
   * Presenta una oferta cross-sell al usuario.
   *
   * Crea una notificacion in-app y, si el servicio de email esta
   * disponible, envia un email con la oferta.
   *
   * @param int $userId
   *   ID del usuario.
   * @param array $offer
   *   Regla de cross-sell con 'after' y 'offer'.
   * @param string $eventType
   *   Evento que disparo la oferta.
   *
   * @return array
   *   Resultado con 'presented', 'offer', 'channels'.
   */
  protected function presentOffer(int $userId, array $offer, string $eventType): array {
    $channels = [];

    // Canal 1: Notificacion in-app via Drupal messages.
    try {
      $message = $this->t('Basandonos en tu progreso, te recomendamos: @offer', [
        '@offer' => $offer['offer'],
      ]);

      if (\Drupal::hasService('jaraba_notifications.service')) {
        /** @var \Drupal\jaraba_notifications\Service\NotificationService $notificationService */
        $notificationService = \Drupal::service('jaraba_notifications.service');
        $notificationService->create($userId, 'cross_sell', (string) $message, [
          'offer' => $offer['offer'],
          'trigger_event' => $eventType,
          'vertical' => 'emprendimiento',
        ]);
        $channels[] = 'in_app';
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('Error presentando notificacion cross-sell: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    // Canal 2: Email via jaraba_email.template_loader.
    try {
      if (\Drupal::hasService('jaraba_email.newsletter')) {
        $user = \Drupal::entityTypeManager()->getStorage('user')->load($userId);
        if ($user) {
          /** @var \Drupal\jaraba_email\Service\NewsletterService $newsletter */
          $newsletter = \Drupal::service('jaraba_email.newsletter');
          $newsletter->send($user->getEmail(), $this->t('Recomendacion para tu proyecto'), (string) $message);
          $channels[] = 'email';
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('Error enviando email cross-sell: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    // Registrar la oferta para analytics.
    $this->recordOfferPresentation($userId, $offer, $eventType, $channels);

    return [
      'presented' => TRUE,
      'offer' => $offer['offer'],
      'trigger' => $offer['after'],
      'channels' => $channels,
    ];
  }

  /**
   * Registra la presentacion de una oferta para analytics de conversion.
   *
   * @param int $userId
   *   ID del usuario.
   * @param array $offer
   *   Regla de cross-sell.
   * @param string $eventType
   *   Evento disparador.
   * @param array $channels
   *   Canales usados para la presentacion.
   */
  protected function recordOfferPresentation(int $userId, array $offer, string $eventType, array $channels): void {
    try {
      $state = \Drupal::state();
      $key = 'cross_sell_emprendimiento_log';
      $log = $state->get($key, []);

      $log[] = [
        'user_id' => $userId,
        'offer' => $offer['offer'],
        'trigger_event' => $eventType,
        'channels' => $channels,
        'timestamp' => \Drupal::time()->getRequestTime(),
        'converted' => FALSE,
      ];

      // Mantener maximo 1000 entradas.
      if (count($log) > 1000) {
        $log = array_slice($log, -1000);
      }

      $state->set($key, $log);
    }
    catch (\Exception $e) {
      $this->logger->warning('Error registrando oferta cross-sell: @error', [
        '@error' => $e->getMessage(),
      ]);
    }
  }

}
