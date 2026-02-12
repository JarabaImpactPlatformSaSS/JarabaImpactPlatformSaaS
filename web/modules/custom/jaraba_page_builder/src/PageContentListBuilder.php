<?php

namespace Drupal\jaraba_page_builder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * List builder para PageContent.
 */
class PageContentListBuilder extends EntityListBuilder
{

    /**
     * El servicio de formateo de fechas.
     *
     * @var \Drupal\Core\Datetime\DateFormatterInterface
     */
    protected $dateFormatter;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeInterface $entity_type,
        EntityStorageInterface $storage,
        DateFormatterInterface $date_formatter
    ) {
        parent::__construct($entity_type, $storage);
        $this->dateFormatter = $date_formatter;
    }

    /**
     * {@inheritdoc}
     */
    public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type)
    {
        return new static(
            $entity_type,
            $container->get('entity_type.manager')->getStorage($entity_type->id()),
            $container->get('date.formatter')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildHeader()
    {
        $header['title'] = $this->t('Título');
        $header['template'] = $this->t('Plantilla');
        $header['status'] = $this->t('Estado');
        $header['author'] = $this->t('Autor');
        $header['changed'] = $this->t('Modificado');
        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity)
    {
        /** @var \Drupal\jaraba_page_builder\PageContentInterface $entity */
        $row['title'] = $entity->toLink();
        $row['template'] = $entity->getTemplateId();
        $row['status'] = $entity->isPublished()
            ? $this->t('Publicado')
            : $this->t('Borrador');
        $row['author'] = $entity->getOwner() ? $entity->getOwner()->getDisplayName() : '';
        $row['changed'] = $this->dateFormatter->format(
            $entity->getChangedTime(),
            'short'
        );
        return $row + parent::buildRow($entity);
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityIds()
    {
        $query = $this->getStorage()->getQuery()
            ->accessCheck(TRUE)
            ->sort('changed', 'DESC');

        // Filtrar por tenant si el usuario tiene uno asignado.
        $current_user = \Drupal::currentUser();
        if (!$current_user->hasPermission('administer page builder')) {
            // TODO: Filtrar por tenant del usuario actual.
        }

        return $query->execute();
    }

}
