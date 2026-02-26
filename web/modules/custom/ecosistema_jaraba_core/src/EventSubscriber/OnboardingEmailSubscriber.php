<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Onboarding email sequence subscriber.
 *
 * Reacts to user registration to enqueue a welcome email sequence.
 * This provides the foundational hooks for automated onboarding.
 *
 * Sprint 4 — Remaining Audit Items (#14).
 *
 * SEQUENCE PLAN:
 * - T+0h:  Welcome email with quick-start guide
 * - T+24h: Vertical recommendation based on profile
 * - T+72h: First action nudge (complete profile / try copilot)
 * - T+7d:  Success story from their vertical
 *
 * DESIGN DECISIONS:
 * - Uses hook_mail() + MailManager for Drupal-native sending
 * - Queue-based via QueueFactory for deferred emails
 * - Configurable enable/disable from admin
 * - Templates can be overridden in jaraba_crm module
 *
 * @see Drupal\ecosistema_jaraba_core\Plugin\QueueWorker\OnboardingEmailWorker
 */
class OnboardingEmailSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The mail manager.
   */
  protected MailManagerInterface $mailManager;

  /**
   * The config factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs the subscriber.
   */
  public function __construct(
    MailManagerInterface $mail_manager,
    ConfigFactoryInterface $config_factory
  ) {
    $this->mailManager = $mail_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Listen to kernel.request for now; the actual trigger
    // will be wired to user.insert hook via hook_user_insert().
    return [];
  }

  /**
   * Enqueues the onboarding email sequence for a new user.
   *
   * Called from ecosistema_jaraba_core_user_insert().
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The newly created user account.
   */
  public function enqueueOnboardingSequence(AccountInterface $account): void {
    $config = $this->configFactory->get('ecosistema_jaraba_core.onboarding');
    if (!$config->get('email_sequence_enabled')) {
      return;
    }

    // Send immediate welcome email.
    $this->sendWelcomeEmail($account);

    // Queue deferred emails via Drupal Queue API.
    $queue_factory = \Drupal::service('queue');

    // T+24h: Vertical recommendation.
    $queue = $queue_factory->get('onboarding_email_sequence');
    $queue->createItem([
      'uid' => $account->id(),
      'email' => $account->getEmail(),
      'template' => 'onboarding_vertical_recommendation',
      'delay_hours' => 24,
      'created' => \Drupal::time()->getRequestTime(),
    ]);

    // T+72h: First action nudge.
    $queue->createItem([
      'uid' => $account->id(),
      'email' => $account->getEmail(),
      'template' => 'onboarding_first_action',
      'delay_hours' => 72,
      'created' => \Drupal::time()->getRequestTime(),
    ]);

    // T+7d: Success story.
    $queue->createItem([
      'uid' => $account->id(),
      'email' => $account->getEmail(),
      'template' => 'onboarding_success_story',
      'delay_hours' => 168,
      'created' => \Drupal::time()->getRequestTime(),
    ]);
  }

  /**
   * Sends the immediate welcome email.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   */
  protected function sendWelcomeEmail(AccountInterface $account): void {
    $params = [
      'subject' => $this->t('¡Bienvenido/a a Jaraba Impact Platform!'),
      'account' => $account,
      'template' => 'onboarding_welcome',
    ];

    $this->mailManager->mail(
      'ecosistema_jaraba_core',
      'onboarding_welcome',
      $account->getEmail(),
      $account->getPreferredLangcode(),
      $params,
      NULL,
      TRUE
    );
  }

}
