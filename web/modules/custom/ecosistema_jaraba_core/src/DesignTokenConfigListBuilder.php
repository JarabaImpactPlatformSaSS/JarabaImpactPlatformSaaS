<?php

namespace Drupal\ecosistema_jaraba_core;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Lista las configuraciones de Design Tokens.
 *
 * Muestra las configuraciones de tokens organizadas por scope
 * (Platform, Vertical, Plan, Tenant) con indicadores de estado.
 */
class DesignTokenConfigListBuilder extends ConfigEntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader()
    {
        $header['label'] = $this->t('Nombre');
        $header['scope'] = $this->t('Alcance');
        $header['vertical'] = $this->t('Vertical');
        $header['preset'] = $this->t('Preset');
        $header['tokens'] = $this->t('Tokens definidos');
        $header['status'] = $this->t('Estado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity)
    {
        /** @var \Drupal\ecosistema_jaraba_core\Entity\DesignTokenConfigInterface $entity */

        // Etiquetas y badges visuales por scope.
        $scopeLabels = [
            'platform' => '🌐 Platform',
            'vertical' => '📂 Vertical',
            'plan' => '💎 Plan',
            'tenant' => '🏢 Tenant',
        ];
        $scope = $entity->getScope();

        $row['label'] = $entity->label();
        $row['scope'] = $scopeLabels[$scope] ?? $scope;
        $row['vertical'] = $entity->getVerticalId() ?: '—';
        $row['preset'] = $entity->getPresetId() ?: '—';

        // Contar tokens definidos por categoría.
        $tokenCount = count($entity->getColorTokens())
            + count($entity->getTypographyTokens())
            + count($entity->getSpacingTokens())
            + count($entity->getEffectTokens());
        $variantCount = count($entity->getComponentVariants());
        $row['tokens'] = $this->t('@tokens tokens, @variants variantes', [
            '@tokens' => $tokenCount,
            '@variants' => $variantCount,
        ]);

        $row['status'] = $entity->status() ? $this->t('✅ Activo') : $this->t('❌ Inactivo');

        return $row + parent::buildRow($entity);
    }

}
