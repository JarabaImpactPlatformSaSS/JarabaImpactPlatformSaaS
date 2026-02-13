<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * FORMULARIO FAQ EN SLIDE-PANEL
 *
 * PROPÓSITO:
 * Formulario para crear/editar FAQs del tenant.
 * Se abre en slide-panel desde el dashboard de Knowledge Training.
 *
 * PATRÓN:
 * Sigue el patrón slide-panel del proyecto:
 * - Clases CSS para styling premium
 * - Redirect que el slide-panel.js intercepta
 *
 * @see .agent/workflows/slide-panel-modales.md
 */
class TenantFaqForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form = parent::buildForm($form, $form_state);

        // Clases para slide-panel styling premium.
        $form['#attributes']['class'][] = 'jaraba-premium-form';
        $form['#attributes']['class'][] = 'slide-panel__form';
        $form['#attributes']['class'][] = 'faq-form';

        // Asignar tenant automáticamente si es nueva entidad.
        $entity = $this->getEntity();
        if ($entity->isNew()) {
            $tenantId = $this->getCurrentTenantId();
            if ($tenantId) {
                $entity->set('tenant_id', $tenantId);
            }
        }

        // Fieldset para organización visual.
        $form['content_wrapper'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['faq-form__content']],
        ];

        // Mover campos al wrapper.
        if (isset($form['question'])) {
            $form['content_wrapper']['question'] = $form['question'];
            unset($form['question']);
        }

        if (isset($form['answer'])) {
            $form['content_wrapper']['answer'] = $form['answer'];
            unset($form['answer']);
        }

        if (isset($form['question_variants'])) {
            $form['content_wrapper']['question_variants'] = $form['question_variants'];
            $form['content_wrapper']['question_variants']['#title'] = $this->t('Variantes de la pregunta (opcional)');
            $form['content_wrapper']['question_variants']['#description'] = $this->t('Otras formas de hacer la misma pregunta. Ayuda al copiloto a entender mejor.');
            unset($form['question_variants']);
        }

        // Fieldset de configuración.
        $form['settings_wrapper'] = [
            '#type' => 'details',
            '#title' => $this->t('Configuración'),
            '#open' => FALSE,
            '#attributes' => ['class' => ['faq-form__settings']],
        ];

        if (isset($form['category'])) {
            $form['settings_wrapper']['category'] = $form['category'];
            unset($form['category']);
        }

        if (isset($form['priority'])) {
            $form['settings_wrapper']['priority'] = $form['priority'];
            unset($form['priority']);
        }

        if (isset($form['is_published'])) {
            $form['settings_wrapper']['is_published'] = $form['is_published'];
            unset($form['is_published']);
        }

        // Ocultar campos de sistema.
        if (isset($form['tenant_id'])) {
            $form['tenant_id']['#access'] = FALSE;
        }
        if (isset($form['content_hash'])) {
            $form['content_hash']['#access'] = FALSE;
        }
        if (isset($form['qdrant_point_id'])) {
            $form['qdrant_point_id']['#access'] = FALSE;
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $entity = $this->getEntity();
        $isNew = $entity->isNew();

        // Actualizar hash de contenido.
        $entity->updateContentHash();

        $status = parent::save($form, $form_state);

        // Mensaje de confirmación.
        $messenger = \Drupal::messenger();
        if ($isNew) {
            $messenger->addStatus($this->t('FAQ creada correctamente. Se indexará automáticamente.'));
        } else {
            $messenger->addStatus($this->t('FAQ actualizada correctamente.'));
        }

        // Redirect al listado de FAQs.
        $form_state->setRedirectUrl(Url::fromRoute('jaraba_tenant_knowledge.faqs'));

        return $status;
    }

    /**
     * Obtiene el tenant ID actual.
     */
    protected function getCurrentTenantId(): ?int
    {
        if (\Drupal::hasService('ecosistema_jaraba_core.tenant_context')) {
            $tenantContext = \Drupal::service('ecosistema_jaraba_core.tenant_context');
            $tenant = $tenantContext->getCurrentTenant();
            return $tenant ? (int) $tenant->id() : NULL;
        }
        return NULL;
    }

}
