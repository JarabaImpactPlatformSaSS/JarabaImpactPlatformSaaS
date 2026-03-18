<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\jaraba_billing\Entity\KitDigitalAgreement;

/**
 * Listado de acuerdos Kit Digital en admin.
 */
class KitDigitalAgreementListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['agreement_number'] = $this->t('Nº Acuerdo');
    $header['beneficiary_name'] = $this->t('Beneficiario');
    $header['paquete'] = $this->t('Paquete');
    $header['segmento'] = $this->t('Segmento');
    $header['bono_digital_amount'] = $this->t('Bono (EUR)');
    $header['status'] = $this->t('Estado');
    $header['start_date'] = $this->t('Inicio');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $paqueteLabels = KitDigitalAgreement::PAQUETES;
    $segmentoLabels = KitDigitalAgreement::SEGMENTOS;
    $statusLabels = KitDigitalAgreement::STATUSES;

    $paquete = $entity->get('paquete')->value;
    $segmento = $entity->get('segmento')->value;
    $status = $entity->get('status')->value;
    $startDate = $entity->get('start_date')->value;

    $row['agreement_number'] = $entity->get('agreement_number')->value ?? '-';
    $row['beneficiary_name'] = $entity->get('beneficiary_name')->value ?? '-';
    $row['paquete'] = $paqueteLabels[$paquete] ?? $paquete;
    $row['segmento'] = $segmentoLabels[$segmento] ?? $segmento;
    $row['bono_digital_amount'] = $entity->get('bono_digital_amount')->value
      ? number_format((float) $entity->get('bono_digital_amount')->value, 2, ',', '.') . ' €'
      : '-';
    $row['status'] = $statusLabels[$status] ?? $status;
    $row['start_date'] = $startDate ? date('d/m/Y', strtotime(str_replace('T', ' ', $startDate))) : '-';

    return $row + parent::buildRow($entity);
  }

}
