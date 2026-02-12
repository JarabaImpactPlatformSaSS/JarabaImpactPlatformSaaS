<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * List builder for Course entities with filters.
 */
class CourseListBuilder extends EntityListBuilder implements FormInterface
{

    /**
     * The form builder.
     *
     * @var \Drupal\Core\Form\FormBuilderInterface
     */
    protected $formBuilder;

    /**
     * {@inheritdoc}
     */
    public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type)
    {
        $instance = parent::createInstance($container, $entity_type);
        $instance->formBuilder = $container->get('form_builder');
        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'lms_course_filter_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $request = \Drupal::request();

        $form['filters'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['form--inline', 'clearfix']],
        ];

        $form['filters']['status'] = [
            '#type' => 'select',
            '#title' => $this->t('Status'),
            '#options' => [
                '' => $this->t('- All -'),
                '1' => $this->t('Published'),
                '0' => $this->t('Draft'),
            ],
            '#default_value' => $request->query->get('status', ''),
        ];

        $form['filters']['actions'] = [
            '#type' => 'actions',
        ];

        $form['filters']['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Filter'),
        ];

        $form['filters']['actions']['reset'] = [
            '#type' => 'submit',
            '#value' => $this->t('Reset'),
            '#submit' => ['::resetForm'],
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state): void
    {
        // No validation needed.
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $status = $form_state->getValue('status');
        $query = [];
        if ($status !== '' && $status !== NULL) {
            $query['status'] = $status;
        }
        $form_state->setRedirect('entity.lms_course.collection', [], ['query' => $query]);
    }

    /**
     * Reset form handler.
     */
    public function resetForm(array &$form, FormStateInterface $form_state): void
    {
        $form_state->setRedirect('entity.lms_course.collection');
    }

    /**
     * {@inheritdoc}
     */
    public function render(): array
    {
        $build['form'] = $this->formBuilder->getForm($this);
        $build['table'] = parent::render();
        return $build;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityIds(): array
    {
        $query = $this->getStorage()->getQuery()
            ->accessCheck(TRUE)
            ->sort('created', 'DESC');

        // Apply status filter.
        $request = \Drupal::request();
        $status = $request->query->get('status');
        if ($status !== NULL && $status !== '') {
            $query->condition('is_published', (int) $status);
        }

        // Only add the pager if a limit is specified.
        if ($this->limit) {
            $query->pager($this->limit);
        }

        return $query->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['id'] = $this->t('ID');
        $header['title'] = $this->t('Title');
        $header['status'] = $this->t('Status');
        $header['created'] = $this->t('Created');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        $row['id'] = $entity->id();
        $row['title'] = Link::createFromRoute(
            $entity->label() ?? $this->t('Untitled'),
            'entity.lms_course.canonical',
            ['lms_course' => $entity->id()]
        );
        $is_published = $entity->get('is_published')->value ?? FALSE;
        $row['status'] = $is_published ? $this->t('Published') : $this->t('Draft');
        $row['created'] = $entity->get('created')->value
            ? \Drupal::service('date.formatter')->format($entity->get('created')->value, 'short')
            : '';
        return $row + parent::buildRow($entity);
    }

}
