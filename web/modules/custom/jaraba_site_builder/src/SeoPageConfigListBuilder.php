<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para la entidad SeoPageConfig.
 *
 * Muestra la tabla de configuraciones SEO por página en admin.
 */
class SeoPageConfigListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['id'] = $this->t('ID');
        $header['page_id'] = $this->t('Página');
        $header['meta_title'] = $this->t('Meta título');
        $header['schema_type'] = $this->t('Schema');
        $header['robots'] = $this->t('Robots');
        $header['last_audit_score'] = $this->t('Score SEO');
        $header['changed'] = $this->t('Actualizado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_site_builder\Entity\SeoPageConfig $entity */
        $row['id'] = $entity->id();

        // Mostrar referencia a página.
        $pageRef = $entity->get('page_id')->entity;
        $row['page_id'] = $pageRef ? $pageRef->label() : $this->t('(sin página)');

        $row['meta_title'] = $entity->getMetaTitle() ?: $this->t('(sin título)');
        $row['schema_type'] = $entity->getSchemaType();
        $row['robots'] = $entity->getRobots();

        $score = $entity->getLastAuditScore();
        $row['last_audit_score'] = $score . '/100';

        $changed = $entity->get('changed')->value;
        $row['changed'] = $changed
            ? \Drupal::service('date.formatter')->format((int) $changed, 'short')
            : '-';

        return $row + parent::buildRow($entity);
    }

}
