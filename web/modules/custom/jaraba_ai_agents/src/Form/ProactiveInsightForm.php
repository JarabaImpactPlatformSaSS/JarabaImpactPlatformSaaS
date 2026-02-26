<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for ProactiveInsight entity.
 *
 * GAP-AUD-010: Follows PREMIUM-FORMS-PATTERN-001.
 */
class ProactiveInsightForm extends PremiumEntityFormBase
{

    /**
     * {@inheritdoc}
     */
    protected function getSectionDefinitions(): array
    {
        return [
            'insight_info' => [
                'label' => $this->t('Insight'),
                'icon' => ['category' => 'ui', 'name' => 'sparkles'],
                'description' => $this->t('Insight title, type, body, and severity.'),
                'fields' => ['title', 'insight_type', 'body', 'severity'],
            ],
            'targeting' => [
                'label' => $this->t('Targeting'),
                'icon' => ['category' => 'ui', 'name' => 'user'],
                'description' => $this->t('Target user and action URL.'),
                'fields' => ['target_user', 'action_url', 'read_status'],
            ],
            'metadata' => [
                'label' => $this->t('Metadata'),
                'icon' => ['category' => 'business', 'name' => 'clipboard'],
                'description' => $this->t('Tenant, AI model, and confidence.'),
                'fields' => ['tenant_id', 'ai_model', 'ai_confidence'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormIcon(): array
    {
        return ['category' => 'ui', 'name' => 'sparkles'];
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $result = parent::save($form, $form_state);
        $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
        return $result;
    }

}
