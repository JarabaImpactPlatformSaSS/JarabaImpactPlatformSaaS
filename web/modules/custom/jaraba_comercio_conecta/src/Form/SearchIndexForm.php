<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for search index entries.
 */
class SearchIndexForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'indice' => [
        'label' => $this->t('Datos del Indice'),
        'icon' => ['category' => 'analytics', 'name' => 'search'],
        'description' => $this->t('Entidad indexada y contenido de busqueda.'),
        'fields' => ['title', 'entity_type_ref', 'entity_id_ref', 'search_text', 'keywords', 'category_ids'],
      ],
      'ubicacion' => [
        'label' => $this->t('Ubicacion'),
        'icon' => ['category' => 'ui', 'name' => 'location'],
        'description' => $this->t('Coordenadas para busqueda por proximidad.'),
        'fields' => ['location_lat', 'location_lng'],
      ],
      'ranking' => [
        'label' => $this->t('Ranking'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Peso y factor de boost para relevancia en resultados.'),
        'fields' => ['weight', 'boost_factor', 'is_active'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'analytics', 'name' => 'search'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

}
