<?php

namespace Drupal\ecosistema_jaraba_core;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Lista de Style Presets con galería visual de presets por vertical.
 *
 * Muestra cada preset como una fila con:
 * - Paleta de colores (swatch visual)
 * - Nombre, vertical, sector, mood tags
 * - Tipografía principal
 * - Estado activo/inactivo
 */
class StylePresetListBuilder extends ConfigEntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['color_preview'] = [
            'data' => $this->t('Paleta'),
            'class' => ['style-preset-color-preview'],
        ];
        $header['label'] = $this->t('Nombre');
        $header['vertical'] = [
            'data' => $this->t('Vertical'),
            'class' => [RESPONSIVE_PRIORITY_MEDIUM],
        ];
        $header['sector'] = [
            'data' => $this->t('Sector'),
            'class' => [RESPONSIVE_PRIORITY_MEDIUM],
        ];
        $header['mood'] = [
            'data' => $this->t('Mood'),
            'class' => [RESPONSIVE_PRIORITY_LOW],
        ];
        $header['fonts'] = [
            'data' => $this->t('Tipografía'),
            'class' => [RESPONSIVE_PRIORITY_LOW],
        ];
        $header['status'] = $this->t('Estado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\ecosistema_jaraba_core\Entity\StylePresetInterface $entity */
        $colors = $entity->getColorTokens();
        $typography = $entity->getTypographyTokens();
        $mood = $entity->getMood();

        // Generar swatches de colores.
        $swatches = '';
        foreach (['primary', 'secondary', 'accent'] as $colorKey) {
            if (!empty($colors[$colorKey])) {
                $hex = htmlspecialchars($colors[$colorKey], ENT_QUOTES);
                $swatches .= "<span style=\"display:inline-block;width:20px;height:20px;border-radius:50%;background:{$hex};border:1px solid rgba(0,0,0,0.1);margin-right:4px;\" title=\"{$colorKey}: {$hex}\"></span>";
            }
        }

        // Vertical con etiqueta legible.
        $verticalLabels = [
            'agroconecta' => 'AgroConecta',
            'comercioconecta' => 'ComercioConecta',
            'serviciosconecta' => 'ServiciosConecta',
        ];
        $verticalLabel = $verticalLabels[$entity->getVertical()] ?? $entity->getVertical();

        // Tipografía principal.
        $fontHeading = $typography['family-heading'] ?? '';
        $fontBody = $typography['family-body'] ?? '';
        $fontDisplay = $fontHeading;
        if ($fontBody && $fontBody !== $fontHeading) {
            $fontDisplay .= ' / ' . $fontBody;
        }

        // Mood tags.
        $moodHtml = '';
        foreach (array_slice($mood, 0, 3) as $tag) {
            $tag = htmlspecialchars($tag, ENT_QUOTES);
            $moodHtml .= "<span style=\"display:inline-block;padding:2px 8px;background:#F1F5F9;border-radius:4px;font-size:11px;margin-right:4px;\">{$tag}</span>";
        }

        $row['color_preview'] = [
            'data' => ['#markup' => $swatches],
        ];
        $row['label'] = $entity->label();
        $row['vertical'] = $verticalLabel;
        $row['sector'] = ucfirst($entity->getSector());
        $row['mood'] = [
            'data' => ['#markup' => $moodHtml],
        ];
        $row['fonts'] = $fontDisplay;
        $row['status'] = $entity->status() ? $this->t('Activo') : $this->t('Inactivo');

        return $row + parent::buildRow($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function render(): array
    {
        $build = parent::render();
        $build['table']['#empty'] = $this->t('No hay style presets configurados. Importa los presets con drush config:import.');
        return $build;
    }

}
