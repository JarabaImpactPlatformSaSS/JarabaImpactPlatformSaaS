<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulario para crear o editar una suscripcion de Funding Intelligence.
 *
 * PROPOSITO:
 * Permite al usuario crear o editar una suscripcion a alertas de
 * subvenciones, definiendo su perfil de beneficiario con regiones,
 * sectores, tipo de beneficiario, tamano de empresa y canal de alerta.
 *
 * CAMPOS:
 * - label: Nombre de la suscripcion
 * - regions: Regiones de interes (checkboxes)
 * - sectors: Sectores de interes (checkboxes)
 * - beneficiary_type: Tipo de beneficiario (select)
 * - employee_count: Numero de empleados (number)
 * - annual_revenue: Facturacion anual (number)
 * - company_description: Descripcion de la empresa (textarea)
 * - alert_channel: Canal de alerta (select)
 * - alert_frequency: Frecuencia de alerta (select)
 *
 * RUTA:
 * - /funding/subscription/add
 * - /funding/subscription/{subscription_id}/edit
 *
 * @package Drupal\jaraba_funding\Form
 */
class FundingSubscriptionForm extends FormBase {

  /**
   * El gestor de tipos de entidad.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * El usuario actual.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * El servicio de contexto de tenant.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService
   */
  protected TenantContextService $tenantContext;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = new static();
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->currentUser = $container->get('current_user');
    $instance->tenantContext = $container->get('ecosistema_jaraba_core.tenant_context');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jaraba_funding_subscription_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?int $subscription_id = NULL) {
    // Cargar suscripcion existente si se esta editando.
    $subscription = NULL;
    if ($subscription_id) {
      $subscription = $this->entityTypeManager
        ->getStorage('funding_subscription')
        ->load($subscription_id);

      if (!$subscription) {
        $this->messenger()->addError($this->t('Suscripcion no encontrada.'));
        return $form;
      }

      $form_state->set('subscription_id', $subscription_id);
    }

    // --- Nombre de la suscripcion ---
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nombre de la suscripcion'),
      '#default_value' => $subscription ? $subscription->get('label')->value : '',
      '#description' => $this->t('Nombre descriptivo para identificar esta suscripcion de alertas.'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#placeholder' => $this->t('Mi suscripcion de subvenciones'),
    ];

    // --- Regiones de interes ---
    $form['regions'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Regiones de interes'),
      '#options' => [
        'nacional' => $this->t('Nacional'),
        'andalucia' => $this->t('Andalucia'),
        'aragon' => $this->t('Aragon'),
        'asturias' => $this->t('Asturias'),
        'baleares' => $this->t('Islas Baleares'),
        'canarias' => $this->t('Canarias'),
        'cantabria' => $this->t('Cantabria'),
        'castilla_leon' => $this->t('Castilla y Leon'),
        'castilla_la_mancha' => $this->t('Castilla-La Mancha'),
        'cataluna' => $this->t('Cataluna'),
        'comunidad_valenciana' => $this->t('Comunidad Valenciana'),
        'extremadura' => $this->t('Extremadura'),
        'galicia' => $this->t('Galicia'),
        'madrid' => $this->t('Comunidad de Madrid'),
        'murcia' => $this->t('Region de Murcia'),
        'navarra' => $this->t('Comunidad Foral de Navarra'),
        'pais_vasco' => $this->t('Pais Vasco'),
        'la_rioja' => $this->t('La Rioja'),
        'ceuta' => $this->t('Ceuta'),
        'melilla' => $this->t('Melilla'),
        'europeo' => $this->t('Europeo'),
      ],
      '#default_value' => $subscription ? $this->getJsonFieldArray($subscription, 'regions') : [],
      '#description' => $this->t('Seleccione las regiones donde busca subvenciones. Debe seleccionar al menos una.'),
      '#required' => FALSE,
    ];

    // --- Sectores de interes ---
    $form['sectors'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Sectores de interes'),
      '#options' => [
        'agricultura' => $this->t('Agricultura y ganaderia'),
        'comercio' => $this->t('Comercio'),
        'construccion' => $this->t('Construccion'),
        'cultura' => $this->t('Cultura y patrimonio'),
        'educacion' => $this->t('Educacion y formacion'),
        'energia' => $this->t('Energia y medio ambiente'),
        'hosteleria' => $this->t('Hosteleria y turismo'),
        'industria' => $this->t('Industria y manufactura'),
        'innovacion' => $this->t('Innovacion y I+D+i'),
        'salud' => $this->t('Salud y servicios sociales'),
        'servicios' => $this->t('Servicios profesionales'),
        'tecnologia' => $this->t('Tecnologia y digitalizacion'),
        'transporte' => $this->t('Transporte y logistica'),
        'empleo' => $this->t('Empleo y emprendimiento'),
        'igualdad' => $this->t('Igualdad y diversidad'),
        'cooperacion' => $this->t('Cooperacion internacional'),
      ],
      '#default_value' => $subscription ? $this->getJsonFieldArray($subscription, 'sectors') : [],
      '#description' => $this->t('Seleccione los sectores relevantes para su actividad. Debe seleccionar al menos uno.'),
      '#required' => FALSE,
    ];

    // --- Tipo de beneficiario ---
    $form['beneficiary_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Tipo de beneficiario'),
      '#options' => [
        '' => $this->t('- Seleccione -'),
        'autonomo' => $this->t('Autonomo'),
        'micropyme' => $this->t('Micropyme (< 10 empleados)'),
        'pyme' => $this->t('PYME (< 250 empleados)'),
        'gran_empresa' => $this->t('Gran empresa (250+ empleados)'),
        'asociacion' => $this->t('Asociacion / ONG'),
        'fundacion' => $this->t('Fundacion'),
        'cooperativa' => $this->t('Cooperativa'),
        'comunidad_bienes' => $this->t('Comunidad de bienes'),
        'administracion_publica' => $this->t('Administracion publica'),
        'universidad' => $this->t('Universidad / Centro de investigacion'),
        'persona_fisica' => $this->t('Persona fisica'),
      ],
      '#default_value' => $subscription ? $subscription->get('beneficiary_type')->value : '',
      '#description' => $this->t('Tipo de entidad beneficiaria.'),
      '#required' => TRUE,
    ];

    // --- Numero de empleados ---
    $form['employee_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Numero de empleados'),
      '#default_value' => $subscription ? $subscription->get('employee_count')->value : '',
      '#min' => 0,
      '#max' => 999999,
      '#step' => 1,
      '#description' => $this->t('Numero actual de empleados de su organizacion. Se utiliza para filtrar elegibilidad por tamano.'),
    ];

    // --- Facturacion anual ---
    $form['annual_revenue'] = [
      '#type' => 'number',
      '#title' => $this->t('Facturacion anual (EUR)'),
      '#default_value' => $subscription ? $subscription->get('annual_revenue')->value : '',
      '#min' => 0,
      '#max' => 999999999999,
      '#step' => 0.01,
      '#description' => $this->t('Facturacion anual en euros. Se utiliza para filtrar elegibilidad por volumen de negocio.'),
    ];

    // --- Descripcion de la empresa ---
    $form['company_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Descripcion de la empresa'),
      '#default_value' => $subscription ? $subscription->get('company_description')->value : '',
      '#description' => $this->t('Descripcion breve de la actividad y proyectos de su organizacion. Se utiliza para el matching semantico con IA.'),
      '#rows' => 4,
      '#maxlength' => 2000,
    ];

    // --- Canal de alerta ---
    $form['alert_channel'] = [
      '#type' => 'select',
      '#title' => $this->t('Canal de alerta'),
      '#options' => [
        'email' => $this->t('Correo electronico'),
        'dashboard' => $this->t('Solo dashboard'),
        'email_dashboard' => $this->t('Correo electronico + Dashboard'),
      ],
      '#default_value' => $subscription ? $subscription->get('alert_channel')->value : 'email_dashboard',
      '#description' => $this->t('Canal por el que desea recibir las alertas de nuevos matches.'),
      '#required' => TRUE,
    ];

    // --- Frecuencia de alerta ---
    $form['alert_frequency'] = [
      '#type' => 'select',
      '#title' => $this->t('Frecuencia de alerta'),
      '#options' => [
        'immediate' => $this->t('Inmediata'),
        'daily' => $this->t('Diaria'),
        'weekly' => $this->t('Semanal'),
      ],
      '#default_value' => $subscription ? $subscription->get('alert_frequency')->value : 'daily',
      '#description' => $this->t('Con que frecuencia desea recibir notificaciones de nuevos matches.'),
      '#required' => TRUE,
    ];

    // --- Botones de accion ---
    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $subscription ? $this->t('Guardar cambios') : $this->t('Crear suscripcion'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Validar que al menos una region esta seleccionada.
    $regions = array_filter($form_state->getValue('regions', []));
    if (empty($regions)) {
      $form_state->setErrorByName('regions', $this->t('Debe seleccionar al menos una region de interes.'));
    }

    // Validar que al menos un sector esta seleccionado.
    $sectors = array_filter($form_state->getValue('sectors', []));
    if (empty($sectors)) {
      $form_state->setErrorByName('sectors', $this->t('Debe seleccionar al menos un sector de interes.'));
    }

    // Validar tipo de beneficiario.
    $beneficiary_type = $form_state->getValue('beneficiary_type');
    if (empty($beneficiary_type)) {
      $form_state->setErrorByName('beneficiary_type', $this->t('Debe seleccionar un tipo de beneficiario.'));
    }

    // Validar que la descripcion no exceda el limite.
    $description = $form_state->getValue('company_description');
    if (!empty($description) && mb_strlen($description) > 2000) {
      $form_state->setErrorByName('company_description', $this->t('La descripcion no puede exceder los 2000 caracteres.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $tenant = $this->tenantContext->getCurrentTenant();
    $tenant_id = $tenant ? (int) $tenant->id() : 0;
    $uid = (int) $this->currentUser->id();

    $subscription_id = $form_state->get('subscription_id');
    $storage = $this->entityTypeManager->getStorage('funding_subscription');

    // Preparar regiones y sectores como JSON.
    $regions = array_values(array_filter($form_state->getValue('regions', [])));
    $sectors = array_values(array_filter($form_state->getValue('sectors', [])));

    if ($subscription_id) {
      // Actualizar suscripcion existente.
      $subscription = $storage->load($subscription_id);

      if ($subscription) {
        $subscription->set('label', $form_state->getValue('label'));
        $subscription->set('regions', json_encode($regions));
        $subscription->set('sectors', json_encode($sectors));
        $subscription->set('beneficiary_type', $form_state->getValue('beneficiary_type'));
        $subscription->set('employee_count', $form_state->getValue('employee_count') ?: NULL);
        $subscription->set('annual_revenue', $form_state->getValue('annual_revenue') ?: NULL);
        $subscription->set('company_description', $form_state->getValue('company_description'));
        $subscription->set('alert_channel', $form_state->getValue('alert_channel'));
        $subscription->set('alert_frequency', $form_state->getValue('alert_frequency'));
        $subscription->save();

        $this->messenger()->addStatus($this->t('Suscripcion actualizada correctamente.'));
      }
    }
    else {
      // Crear nueva suscripcion.
      $subscription = $storage->create([
        'label' => $form_state->getValue('label'),
        'uid' => $uid,
        'tenant_id' => $tenant_id,
        'regions' => json_encode($regions),
        'sectors' => json_encode($sectors),
        'beneficiary_type' => $form_state->getValue('beneficiary_type'),
        'employee_count' => $form_state->getValue('employee_count') ?: NULL,
        'annual_revenue' => $form_state->getValue('annual_revenue') ?: NULL,
        'company_description' => $form_state->getValue('company_description'),
        'alert_channel' => $form_state->getValue('alert_channel'),
        'alert_frequency' => $form_state->getValue('alert_frequency'),
        'status' => 1,
      ]);
      $subscription->save();

      $this->messenger()->addStatus($this->t('Suscripcion creada correctamente.'));
    }

    $form_state->setRedirectUrl(\Drupal\Core\Url::fromRoute('jaraba_funding.dashboard'));
  }

  /**
   * Obtiene un array JSON de un campo de la entidad.
   *
   * @param mixed $entity
   *   La entidad de suscripcion.
   * @param string $field_name
   *   Nombre del campo.
   *
   * @return array
   *   Array decodificado del JSON, o array vacio si no existe.
   */
  protected function getJsonFieldArray($entity, string $field_name): array {
    $value = $entity->get($field_name)->value ?? '';
    if (empty($value)) {
      return [];
    }

    $decoded = json_decode($value, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

}
