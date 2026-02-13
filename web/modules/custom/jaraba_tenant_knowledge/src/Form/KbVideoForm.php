<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * FORMULARIO DE VÍDEO KB
 *
 * PROPÓSITO:
 * Formulario para crear/editar vídeos de la base de conocimiento.
 * Se abre en slide-panel desde el dashboard de Knowledge Training.
 *
 * PATRÓN:
 * Sigue el patrón slide-panel del proyecto con clases CSS premium.
 */
class KbVideoForm extends ContentEntityForm
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
        $form['#attributes']['class'][] = 'kb-video-form';

        // Asignar tenant automáticamente si es nueva entidad.
        $entity = $this->getEntity();
        if ($entity->isNew()) {
            $tenantId = $this->getCurrentTenantId();
            if ($tenantId) {
                $entity->set('tenant_id', $tenantId);
            }
        }

        // Fieldset para contenido principal.
        $form['content_wrapper'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['kb-video-form__content']],
        ];

        foreach (['title', 'video_url', 'thumbnail_url', 'description', 'duration_seconds'] as $field) {
            if (isset($form[$field])) {
                $form['content_wrapper'][$field] = $form[$field];
                unset($form[$field]);
            }
        }

        // Fieldset de configuración.
        $form['settings_wrapper'] = [
            '#type' => 'details',
            '#title' => $this->t('Configuración'),
            '#open' => FALSE,
            '#attributes' => ['class' => ['kb-video-form__settings']],
        ];

        foreach (['category_id', 'article_id', 'video_status'] as $field) {
            if (isset($form[$field])) {
                $form['settings_wrapper'][$field] = $form[$field];
                unset($form[$field]);
            }
        }

        // Ocultar campos de sistema.
        foreach (['tenant_id', 'view_count'] as $field) {
            if (isset($form[$field])) {
                $form[$field]['#access'] = FALSE;
            }
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

        $status = parent::save($form, $form_state);

        $messenger = \Drupal::messenger();
        if ($isNew) {
            $messenger->addStatus($this->t('Vídeo KB creado correctamente.'));
        }
        else {
            $messenger->addStatus($this->t('Vídeo KB actualizado correctamente.'));
        }

        $form_state->setRedirectUrl(Url::fromRoute('entity.kb_video.collection'));

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
