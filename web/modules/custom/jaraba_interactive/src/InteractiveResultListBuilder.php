<?php

declare(strict_types=1);

namespace Drupal\jaraba_interactive;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Define el listado de resultados interactivos.
 */
class InteractiveResultListBuilder extends EntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader(): array
    {
        $header['id'] = $this->t('ID');
        $header['user'] = $this->t('Usuario');
        $header['content'] = $this->t('Contenido');
        $header['score'] = $this->t('Puntuación');
        $header['passed'] = $this->t('Aprobado');
        $header['attempts'] = $this->t('Intentos');
        $header['created'] = $this->t('Fecha');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity): array
    {
        /** @var \Drupal\jaraba_interactive\Entity\InteractiveResult $entity */
        $row['id'] = $entity->id();

        $owner = $entity->getOwner();
        $row['user'] = $owner ? Link::createFromRoute(
            $owner->getDisplayName(),
            'entity.user.canonical',
            ['user' => $owner->id()]
        ) : $this->t('Usuario eliminado');

        $content = $entity->getInteractiveContent();
        $row['content'] = $content ? Link::createFromRoute(
            $content->label(),
            'entity.interactive_content.canonical',
            ['interactive_content' => $content->id()]
        ) : $this->t('Contenido eliminado');

        $row['score'] = $entity->getScore() . '%';
        $row['passed'] = $entity->hasPassed()
            ? ['#markup' => '<span class="badge badge--success">' . $this->t('Sí') . '</span>']
            : ['#markup' => '<span class="badge badge--danger">' . $this->t('No') . '</span>'];

        $row['attempts'] = $entity->get('attempts')->value ?? 1;
        $row['created'] = \Drupal::service('date.formatter')->format(
            $entity->get('created')->value,
            'short'
        );

        return $row + parent::buildRow($entity);
    }

}
