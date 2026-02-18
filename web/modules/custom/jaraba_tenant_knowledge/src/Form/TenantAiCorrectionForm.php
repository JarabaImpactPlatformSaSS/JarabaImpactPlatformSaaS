<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * FORMULARIO CORRECCIÓN DE IA.
 *
 * Permite al tenant registrar cuando el copiloto da una respuesta incorrecta.
 * Genera automáticamente una regla para mejorar futuras respuestas.
 */
class TenantAiCorrectionForm extends ContentEntityForm
{


    /**
     * Tenant context service. // AUDIT-CONS-N10: Proper DI for tenant context.
     */
    protected ?TenantContextService $tenantContext = NULL;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        $instance = parent::create($container);
        if ($container->has('ecosistema_jaraba_core.tenant_context')) {
            $instance->tenantContext = $container->get('ecosistema_jaraba_core.tenant_context'); // AUDIT-CONS-N10: Proper DI for tenant context.
        }
        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form = parent::buildForm($form, $form_state);

        $form['#attributes']['class'][] = 'jaraba-premium-form';
        $form['#attributes']['class'][] = 'slide-panel__form';
        $form['#attributes']['class'][] = 'ai-correction-form';

        $entity = $this->getEntity();

        // Asignar tenant automáticamente.
        if ($entity->isNew()) {
            $tenantId = $this->getCurrentTenantId();
            if ($tenantId) {
                $entity->set('tenant_id', $tenantId);
            }
        }

        // Información de ayuda.
        $form['help'] = [
            '#type' => 'markup',
            '#markup' => '<div class="ai-correction-form__help">' .
                '<p>' . $this->t('¿El copiloto dio una respuesta incorrecta? Registra la corrección para que aprenda y no repita el error.') . '</p>' .
                '</div>',
            '#weight' => -20,
        ];

        // Grupo: Identificación.
        $form['identification'] = [
            '#type' => 'details',
            '#title' => $this->t('¿Qué pasó?'),
            '#open' => TRUE,
            '#weight' => -10,
        ];

        foreach (['title', 'correction_type', 'related_topic', 'priority'] as $field) {
            if (isset($form[$field])) {
                $form['identification'][$field] = $form[$field];
                unset($form[$field]);
            }
        }

        // Grupo: Contexto.
        $form['context'] = [
            '#type' => 'details',
            '#title' => $this->t('Contexto de la conversación'),
            '#open' => TRUE,
            '#weight' => 0,
        ];

        foreach (['original_query', 'incorrect_response', 'correct_response'] as $field) {
            if (isset($form[$field])) {
                $form['context'][$field] = $form[$field];
                unset($form[$field]);
            }
        }

        // Mostrar regla generada si existe (solo en edición).
        if (!$entity->isNew() && $entity->isApplied()) {
            $form['generated_rule_display'] = [
                '#type' => 'details',
                '#title' => $this->t('Regla Generada'),
                '#open' => TRUE,
                '#weight' => 5,
            ];

            $form['generated_rule_display']['rule'] = [
                '#type' => 'markup',
                '#markup' => '<pre>' . htmlspecialchars($entity->getGeneratedRule()) . '</pre>',
            ];
        }

        // Botón para aplicar corrección.
        if (!$entity->isNew() && !$entity->isApplied()) {
            $form['actions']['apply'] = [
                '#type' => 'submit',
                '#value' => $this->t('Aplicar Corrección'),
                '#submit' => ['::applyCorrection'],
                '#weight' => 5,
                '#button_type' => 'primary',
            ];
        }

        // Ocultar campos de sistema.
        $hiddenFields = ['tenant_id', 'status', 'applied_at', 'hit_count', 'generated_rule'];
        foreach ($hiddenFields as $field) {
            if (isset($form[$field])) {
                $form[$field]['#access'] = FALSE;
            }
        }

        return $form;
    }

    /**
     * Submit handler para aplicar la corrección.
     */
    public function applyCorrection(array &$form, FormStateInterface $form_state): void
    {
        /** @var \Drupal\jaraba_tenant_knowledge\Entity\TenantAiCorrection $entity */
        $entity = $this->getEntity();

        // Guardar primero los cambios del formulario.
        $this->copyFormValuesToEntity($entity, $form, $form_state);
        $entity->save();

        // Aplicar la corrección (genera la regla).
        $entity->apply();

        \Drupal::messenger()->addStatus($this->t('Corrección aplicada. El copiloto ha aprendido de este error.'));
        $form_state->setRedirectUrl(Url::fromRoute('jaraba_tenant_knowledge.corrections'));
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $entity = $this->getEntity();

        $status = parent::save($form, $form_state);

        if ($entity->isNew()) {
            \Drupal::messenger()->addStatus($this->t('Corrección "@title" registrada. Haz clic en "Aplicar" para que el copiloto aprenda.', [
                '@title' => $entity->getTitle(),
            ]));
        } else {
            \Drupal::messenger()->addStatus($this->t('Corrección "@title" actualizada.', [
                '@title' => $entity->getTitle(),
            ]));
        }

        $form_state->setRedirectUrl(Url::fromRoute('jaraba_tenant_knowledge.corrections'));

        return $status;
    }

    /**
     * Obtiene el tenant ID actual.
     */
    protected function getCurrentTenantId(): ?int
    {
        if ($this->tenantContext !== NULL) {
            $tenantContext = $this->tenantContext;
            $tenant = $tenantContext->getCurrentTenant();
            return $tenant ? (int) $tenant->id() : NULL;
        }
        return NULL;
    }

}
