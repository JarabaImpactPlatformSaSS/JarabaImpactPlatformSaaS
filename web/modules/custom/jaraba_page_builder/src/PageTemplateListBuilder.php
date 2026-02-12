<?php

namespace Drupal\jaraba_page_builder;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * List builder para PageTemplate.
 */
class PageTemplateListBuilder extends ConfigEntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader()
    {
        $header['label'] = $this->t('Nombre');
        $header['category'] = $this->t('Categoría');
        $header['is_premium'] = $this->t('Premium');
        $header['plans'] = $this->t('Planes');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity)
    {
        /** @var \Drupal\jaraba_page_builder\PageTemplateInterface $entity */
        $row['label'] = $entity->label();
        $row['category'] = $this->getCategoryLabel($entity->getCategory());
        $row['is_premium'] = $entity->isPremium() ? '⭐ ' . $this->t('Sí') : $this->t('No');
        $row['plans'] = implode(', ', $entity->getPlansRequired());
        return $row + parent::buildRow($entity);
    }

    /**
     * Obtiene la etiqueta de una categoría.
     *
     * @param string $category
     *   El ID de la categoría.
     *
     * @return string
     *   La etiqueta traducida.
     */
    protected function getCategoryLabel(string $category): string
    {
        $categories = [
            'hero' => $this->t('Hero'),
            'features' => $this->t('Features'),
            'stats' => $this->t('Estadísticas'),
            'testimonials' => $this->t('Testimonios'),
            'pricing' => $this->t('Precios'),
            'cta' => $this->t('Call to Action'),
            'content' => $this->t('Contenido'),
            'landing' => $this->t('Landing Page'),
            'dashboard' => $this->t('Dashboard'),
        ];
        return $categories[$category] ?? $category;
    }

}
