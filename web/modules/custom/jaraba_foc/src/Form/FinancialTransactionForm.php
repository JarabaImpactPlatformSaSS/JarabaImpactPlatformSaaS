<?php

declare(strict_types=1);

namespace Drupal\jaraba_foc\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear transacciones financieras.
 *
 * PROPÓSITO:
 * Permite la creación manual de transacciones para:
 * - Importación de datos históricos
 * - Registro de ingresos no automatizados
 * - Ajustes contables (asientos compensatorios)
 *
 * NOTA IMPORTANTE:
 * Este formulario SOLO permite crear transacciones.
 * La edición y eliminación están bloqueadas por el AccessHandler
 * para garantizar la inmutabilidad del libro mayor.
 */
class FinancialTransactionForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form = parent::buildForm($form, $form_state);

        // ═══════════════════════════════════════════════════════════════════════
        // MENSAJE INFORMATIVO SOBRE INMUTABILIDAD
        // ═══════════════════════════════════════════════════════════════════════
        $form['immutability_notice'] = [
            '#type' => 'markup',
            '#markup' => '<div class="messages messages--warning">' .
                '<strong>' . $this->t('Aviso de Inmutabilidad') . ':</strong> ' .
                $this->t('Las transacciones financieras son inmutables. Una vez creada, no podrá ser editada ni eliminada. Para correcciones, registre un asiento compensatorio.') .
                '</div>',
            '#weight' => -100,
        ];

        // Agrupar campos monetarios
        $form['monetary'] = [
            '#type' => 'details',
            '#title' => $this->t('Datos Monetarios'),
            '#open' => TRUE,
            '#weight' => 0,
        ];

        if (isset($form['amount'])) {
            $form['monetary']['amount'] = $form['amount'];
            unset($form['amount']);
        }

        if (isset($form['currency'])) {
            $form['monetary']['currency'] = $form['currency'];
            unset($form['currency']);
        }

        // Agrupar campos de clasificación
        $form['classification'] = [
            '#type' => 'details',
            '#title' => $this->t('Clasificación'),
            '#open' => TRUE,
            '#weight' => 1,
        ];

        if (isset($form['transaction_type'])) {
            $form['classification']['transaction_type'] = $form['transaction_type'];
            unset($form['transaction_type']);
        }

        if (isset($form['is_recurring'])) {
            $form['classification']['is_recurring'] = $form['is_recurring'];
            unset($form['is_recurring']);
        }

        // Agrupar campos de relaciones
        $form['relations'] = [
            '#type' => 'details',
            '#title' => $this->t('Relaciones'),
            '#open' => TRUE,
            '#weight' => 2,
        ];

        if (isset($form['related_tenant'])) {
            $form['relations']['related_tenant'] = $form['related_tenant'];
            unset($form['related_tenant']);
        }

        if (isset($form['related_vertical'])) {
            $form['relations']['related_vertical'] = $form['related_vertical'];
            unset($form['related_vertical']);
        }

        if (isset($form['related_campaign'])) {
            $form['relations']['related_campaign'] = $form['related_campaign'];
            unset($form['related_campaign']);
        }

        // Agrupar campos de trazabilidad
        $form['traceability'] = [
            '#type' => 'details',
            '#title' => $this->t('Trazabilidad'),
            '#open' => FALSE,
            '#weight' => 3,
        ];

        if (isset($form['source_system'])) {
            $form['traceability']['source_system'] = $form['source_system'];
            unset($form['source_system']);
        }

        if (isset($form['external_id'])) {
            $form['traceability']['external_id'] = $form['external_id'];
            unset($form['external_id']);
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $entity = $this->entity;
        $status = parent::save($form, $form_state);

        $this->messenger()->addStatus($this->t('Transacción financiera @id registrada correctamente.', [
            '@id' => $entity->id(),
        ]));

        $form_state->setRedirect('jaraba_foc.transactions');

        return $status;
    }

}
