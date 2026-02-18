<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * FORMULARIO DE ARTÍCULO KB
 *
 * PROPÓSITO:
 * Formulario para crear/editar artículos de la base de conocimiento.
 * Se abre en slide-panel desde el dashboard de Knowledge Training.
 *
 * PATRÓN:
 * Sigue el patrón slide-panel del proyecto con clases CSS premium.
 */
class KbArticleForm extends ContentEntityForm
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

        // Clases para slide-panel styling premium.
        $form['#attributes']['class'][] = 'jaraba-premium-form';
        $form['#attributes']['class'][] = 'slide-panel__form';
        $form['#attributes']['class'][] = 'kb-article-form';

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
            '#attributes' => ['class' => ['kb-article-form__content']],
        ];

        // Mover campos al wrapper.
        foreach (['title', 'slug', 'body', 'summary'] as $field) {
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
            '#attributes' => ['class' => ['kb-article-form__settings']],
        ];

        foreach (['category_id', 'author_id', 'article_status', 'tags'] as $field) {
            if (isset($form[$field])) {
                $form['settings_wrapper'][$field] = $form[$field];
                unset($form[$field]);
            }
        }

        // Ocultar campos de sistema.
        foreach (['tenant_id', 'view_count', 'helpful_count', 'not_helpful_count'] as $field) {
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
            $messenger->addStatus($this->t('Artículo KB creado correctamente.'));
        }
        else {
            $messenger->addStatus($this->t('Artículo KB actualizado correctamente.'));
        }

        $form_state->setRedirectUrl(Url::fromRoute('entity.kb_article.collection'));

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
