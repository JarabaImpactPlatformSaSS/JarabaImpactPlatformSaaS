<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\jaraba_andalucia_ei\Service\SolicitudTriageService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulario público de solicitud para el Programa Andalucía +ei.
 *
 * Accesible por usuarios anónimos en /andalucia-ei/solicitar.
 * Crea una entidad SolicitudEi, envía notificación al admin,
 * email de confirmación al solicitante, ejecuta triaje IA,
 * y protege contra spam con honeypot + time gate.
 */
class SolicitudEiPublicForm extends FormBase
{

    /**
     * The entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * The mail manager.
     */
    protected MailManagerInterface $mailManager;

    /**
     * The AI triage service.
     */
    protected SolicitudTriageService $triageService;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        $instance = parent::create($container);
        $instance->entityTypeManager = $container->get('entity_type.manager');
        $instance->mailManager = $container->get('plugin.manager.mail');
        $instance->triageService = $container->get('jaraba_andalucia_ei.solicitud_triage');
        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'solicitud_ei_public_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['#attributes']['class'][] = 'aei-solicitud-form';

        // === ANTI-SPAM: HONEYPOT ===
        // Campo invisible para bots. Si se rellena, es spam.
        $form['website_url'] = [
            '#type' => 'textfield',
            '#title' => 'Website',
            '#attributes' => [
                'class' => ['aei-hp-field'],
                'tabindex' => '-1',
                'autocomplete' => 'off',
            ],
        ];

        // === ANTI-SPAM: TIME GATE ===
        // #value se regenera en cada rebuild, pero el timestamp original
        // persiste en el POST (user input). validateForm() lee de getUserInput().
        $form['form_token_ts'] = [
            '#type' => 'hidden',
            '#value' => time(),
        ];

        // === DATOS PERSONALES ===
        $form['personal'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Datos personales'),
            '#attributes' => ['class' => ['aei-solicitud-form__section']],
        ];

        $form['personal']['nombre'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Nombre completo'),
            '#required' => TRUE,
            '#maxlength' => 255,
            '#attributes' => ['placeholder' => $this->t('Nombre y apellidos')],
        ];

        $form['personal']['email'] = [
            '#type' => 'email',
            '#title' => $this->t('Correo electrónico'),
            '#required' => TRUE,
            '#attributes' => ['placeholder' => $this->t('tu@email.com')],
        ];

        $form['personal']['telefono'] = [
            '#type' => 'tel',
            '#title' => $this->t('Teléfono'),
            '#required' => TRUE,
            '#maxlength' => 20,
            '#attributes' => ['placeholder' => $this->t('600 000 000')],
        ];

        $form['personal']['fecha_nacimiento'] = [
            '#type' => 'date',
            '#title' => $this->t('Fecha de nacimiento'),
            '#required' => TRUE,
            '#description' => $this->t('Para determinar tu grupo de participación.'),
        ];

        $form['personal']['dni_nie'] = [
            '#type' => 'textfield',
            '#title' => $this->t('DNI/NIE'),
            '#maxlength' => 12,
            '#description' => $this->t('Opcional en esta fase. Será necesario para la inscripción definitiva.'),
            '#attributes' => ['placeholder' => $this->t('12345678A')],
        ];

        // === DATOS TERRITORIALES ===
        $form['territorial'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Localización'),
            '#attributes' => ['class' => ['aei-solicitud-form__section']],
        ];

        $form['territorial']['provincia'] = [
            '#type' => 'select',
            '#title' => $this->t('Provincia'),
            '#required' => TRUE,
            '#empty_option' => $this->t('- Selecciona tu provincia -'),
            '#options' => [
                'almeria' => $this->t('Almería'),
                'cadiz' => $this->t('Cádiz'),
                'cordoba' => $this->t('Córdoba'),
                'granada' => $this->t('Granada'),
                'huelva' => $this->t('Huelva'),
                'jaen' => $this->t('Jaén'),
                'malaga' => $this->t('Málaga'),
                'sevilla' => $this->t('Sevilla'),
            ],
        ];

        $form['territorial']['municipio'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Municipio'),
            '#required' => TRUE,
            '#maxlength' => 100,
            '#attributes' => ['placeholder' => $this->t('Tu municipio de residencia')],
        ];

        // === PERFIL PROFESIONAL ===
        $form['profesional'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Perfil profesional'),
            '#attributes' => ['class' => ['aei-solicitud-form__section']],
        ];

        $form['profesional']['situacion_laboral'] = [
            '#type' => 'select',
            '#title' => $this->t('Situación laboral actual'),
            '#required' => TRUE,
            '#empty_option' => $this->t('- Selecciona tu situación -'),
            '#options' => [
                'desempleado' => $this->t('Desempleado/a'),
                'empleado' => $this->t('Empleado/a por cuenta ajena'),
                'autonomo' => $this->t('Autónomo/a'),
                'estudiante' => $this->t('Estudiante'),
            ],
        ];

        $form['profesional']['tiempo_desempleo'] = [
            '#type' => 'select',
            '#title' => $this->t('Tiempo en desempleo'),
            '#empty_option' => $this->t('- Selecciona -'),
            '#options' => [
                'menos_6_meses' => $this->t('Menos de 6 meses'),
                '6_12_meses' => $this->t('Entre 6 y 12 meses'),
                'mas_12_meses' => $this->t('Más de 12 meses'),
            ],
            '#states' => [
                'visible' => [
                    ':input[name="situacion_laboral"]' => ['value' => 'desempleado'],
                ],
                'required' => [
                    ':input[name="situacion_laboral"]' => ['value' => 'desempleado'],
                ],
            ],
        ];

        $form['profesional']['nivel_estudios'] = [
            '#type' => 'select',
            '#title' => $this->t('Nivel de estudios'),
            '#required' => TRUE,
            '#empty_option' => $this->t('- Selecciona tu nivel -'),
            '#options' => [
                'sin_estudios' => $this->t('Sin estudios'),
                'eso' => $this->t('ESO / Graduado Escolar'),
                'bachillerato' => $this->t('Bachillerato'),
                'fp_medio' => $this->t('FP Grado Medio'),
                'fp_superior' => $this->t('FP Grado Superior'),
                'grado' => $this->t('Grado universitario'),
                'master' => $this->t('Máster / Postgrado'),
                'doctorado' => $this->t('Doctorado'),
            ],
        ];

        $form['profesional']['es_migrante'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Soy persona migrante'),
            '#description' => $this->t('Marca esta casilla si eres persona migrante residiendo en Andalucía.'),
        ];

        $form['profesional']['percibe_prestacion'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Percibo prestación, subsidio por desempleo o Renta Activa de Inserción (RAI)'),
            '#description' => $this->t('Marca si actualmente recibes alguna de estas ayudas.'),
        ];

        $form['profesional']['experiencia_sector'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Experiencia profesional'),
            '#description' => $this->t('Breve descripción de tu experiencia laboral relevante.'),
            '#rows' => 3,
            '#attributes' => ['placeholder' => $this->t('Describe brevemente tu experiencia...')],
        ];

        // === MOTIVACIÓN ===
        $form['motivacion_wrapper'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Tu motivación'),
            '#attributes' => ['class' => ['aei-solicitud-form__section']],
        ];

        $form['motivacion_wrapper']['motivacion'] = [
            '#type' => 'textarea',
            '#title' => $this->t('¿Por qué quieres participar en Andalucía +ei?'),
            '#required' => TRUE,
            '#rows' => 4,
            '#attributes' => ['placeholder' => $this->t('Cuéntanos tu interés en emprender o trabajar por cuenta propia...')],
        ];

        // === PRIVACIDAD ===
        $form['privacidad'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Acepto la <a href="/politica-privacidad" target="_blank">política de privacidad</a> y el tratamiento de mis datos para la gestión de esta solicitud.'),
            '#required' => TRUE,
        ];

        $form['actions'] = [
            '#type' => 'actions',
        ];

        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Enviar solicitud'),
            '#attributes' => ['class' => ['aei-solicitud-form__submit']],
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state): void
    {
        parent::validateForm($form, $form_state);

        // === ANTI-SPAM: HONEYPOT ===
        if (!empty($form_state->getValue('website_url'))) {
            $form_state->setErrorByName('', $this->t('Ha ocurrido un error. Por favor, inténtalo de nuevo.'));
            return;
        }

        // === ANTI-SPAM: TIME GATE (< 3 segundos = bot) ===
        $userInput = $form_state->getUserInput();
        $ts = (int) ($userInput['form_token_ts'] ?? 0);
        if ($ts > 0 && (time() - $ts) < 3) {
            $form_state->setErrorByName('', $this->t('Por favor, espera un momento antes de enviar el formulario.'));
            return;
        }

        // Validar teléfono.
        $telefono = $form_state->getValue('telefono');
        if ($telefono && !preg_match('/^[0-9\s\+\-]{6,20}$/', $telefono)) {
            $form_state->setErrorByName('telefono', $this->t('Por favor, introduce un número de teléfono válido.'));
        }

        // Validar DNI/NIE si se proporciona.
        $dni = $form_state->getValue('dni_nie');
        if ($dni && !preg_match('/^[0-9XYZ][0-9]{7}[A-Z]$/i', $dni)) {
            $form_state->setErrorByName('dni_nie', $this->t('El formato del DNI/NIE no parece correcto.'));
        }

        // Validar fecha de nacimiento (mínimo 16 años).
        $fecha = $form_state->getValue('fecha_nacimiento');
        if ($fecha) {
            $birth = new \DateTime($fecha);
            $now = new \DateTime();
            $age = (int) $now->diff($birth)->y;
            if ($age < 16) {
                $form_state->setErrorByName('fecha_nacimiento', $this->t('Debes tener al menos 16 años para solicitar la participación.'));
            }
        }

        // Validar tiempo desempleo si desempleado.
        $situacion = $form_state->getValue('situacion_laboral');
        $tiempo = $form_state->getValue('tiempo_desempleo');
        if ($situacion === 'desempleado' && empty($tiempo)) {
            $form_state->setErrorByName('tiempo_desempleo', $this->t('Por favor, indica cuánto tiempo llevas en desempleo.'));
        }

        // Control de duplicados: no permitir más de una solicitud por email.
        $email = $form_state->getValue('email');
        if ($email) {
            $existing = $this->entityTypeManager
                ->getStorage('solicitud_ei')
                ->loadByProperties(['email' => $email]);
            if (!empty($existing)) {
                $form_state->setErrorByName('email', $this->t('Ya existe una solicitud registrada con este email. Si necesitas hacer cambios, contacta con nosotros.'));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $storage = $this->entityTypeManager->getStorage('solicitud_ei');

        // Resolve tenant_id from context (Regla de Oro #4: tenant_id obligatorio).
        // Andalucía +ei es un programa de plataforma (no de tenant individual),
        // por lo que tenant_id será NULL en el dominio principal.
        // Si en el futuro un tenant específico gestiona su propio programa,
        // tenant_manager resolverá por dominio.
        $tenantId = NULL;
        if (\Drupal::hasService('ecosistema_jaraba_core.tenant_manager')) {
            try {
                $tenant = \Drupal::service('ecosistema_jaraba_core.tenant_manager')->getCurrentTenant();
                $tenantId = $tenant?->id();
            }
            catch (\Throwable $e) {
                // Non-critical — solicitud se crea sin tenant.
            }
        }

        /** @var \Drupal\jaraba_andalucia_ei\Entity\SolicitudEiInterface $solicitud */
        $solicitud = $storage->create([
            'nombre' => $form_state->getValue('nombre'),
            'email' => $form_state->getValue('email'),
            'telefono' => $form_state->getValue('telefono'),
            'fecha_nacimiento' => $form_state->getValue('fecha_nacimiento'),
            'dni_nie' => $form_state->getValue('dni_nie'),
            'provincia' => $form_state->getValue('provincia'),
            'municipio' => $form_state->getValue('municipio'),
            'situacion_laboral' => $form_state->getValue('situacion_laboral'),
            'tiempo_desempleo' => $form_state->getValue('tiempo_desempleo'),
            'nivel_estudios' => $form_state->getValue('nivel_estudios'),
            'es_migrante' => (bool) $form_state->getValue('es_migrante'),
            'percibe_prestacion' => (bool) $form_state->getValue('percibe_prestacion'),
            'experiencia_sector' => $form_state->getValue('experiencia_sector'),
            'motivacion' => $form_state->getValue('motivacion'),
            'estado' => 'pendiente',
            'ip_address' => $this->getRequest()->getClientIp() ?? '',
            'tenant_id' => $tenantId,
        ]);

        // Inferir colectivo automáticamente.
        $solicitud->setColectivoInferido($solicitud->inferirColectivo());
        $solicitud->save();

        // Triaje IA: evaluar solicitud y guardar resultado.
        try {
            $triage = $this->triageService->triageSolicitud($solicitud);
            if ($triage['score'] !== NULL) {
                $solicitud->set('ai_score', $triage['score']);
            }
            $solicitud->set('ai_justificacion', $triage['justificacion']);
            $solicitud->set('ai_recomendacion', $triage['recomendacion']);
            $solicitud->save();
        } catch (\Throwable $e) {
            // El triaje IA no debe bloquear la solicitud.
            \Drupal::logger('jaraba_andalucia_ei')->error('Error en triaje IA: @msg', [
                '@msg' => $e->getMessage(),
            ]);
        }

        // Enviar notificación al admin.
        $this->notifyAdmin($solicitud);

        // Enviar email de confirmación al solicitante.
        $this->notifyApplicant($solicitud);

        $this->messenger()->addStatus($this->t(
            '¡Gracias @nombre! Tu solicitud ha sido recibida. Te hemos enviado un email de confirmación. Nos pondremos en contacto contigo pronto.',
            ['@nombre' => $form_state->getValue('nombre')]
        ));

        $form_state->setRedirect('jaraba_andalucia_ei.dashboard');
    }

    /**
     * Envía notificación al administrador del programa.
     */
    protected function notifyAdmin($solicitud): void
    {
        $site_mail = $this->configFactory()->get('system.site')->get('mail');
        if (!$site_mail) {
            return;
        }

        $provincias = [
            'almeria' => 'Almería',
            'cadiz' => 'Cádiz',
            'cordoba' => 'Córdoba',
            'granada' => 'Granada',
            'huelva' => 'Huelva',
            'jaen' => 'Jaén',
            'malaga' => 'Málaga',
            'sevilla' => 'Sevilla',
        ];

        $colectivos = [
            'larga_duracion' => 'Larga duración',
            'mayores_45' => 'Mayores de 45',
            'migrantes' => 'Personas migrantes',
            'perceptores_prestaciones' => 'Perceptores prestaciones/RAI',
            'otros' => 'Otros',
        ];

        $params = [
            'nombre' => $solicitud->getNombre(),
            'email' => $solicitud->getEmail(),
            'telefono' => $solicitud->getTelefono(),
            'provincia' => $provincias[$solicitud->getProvincia()] ?? $solicitud->getProvincia(),
            'colectivo' => $colectivos[$solicitud->getColectivoInferido()] ?? 'Sin determinar',
            'solicitud_url' => $solicitud->toUrl('canonical', ['absolute' => TRUE])->toString(),
            'ai_score' => $solicitud->get('ai_score')->value,
            'ai_recomendacion' => $solicitud->get('ai_recomendacion')->value ?? 'pendiente',
            'ai_justificacion' => $solicitud->get('ai_justificacion')->value ?? '',
        ];

        $this->mailManager->mail(
            'jaraba_andalucia_ei',
            'nueva_solicitud',
            $site_mail,
            'es',
            $params,
        );
    }

    /**
     * Envía email de confirmación al solicitante.
     */
    protected function notifyApplicant($solicitud): void
    {
        $email = $solicitud->getEmail();
        if (empty($email)) {
            return;
        }

        $colectivos = [
            'larga_duracion' => 'Desempleados de larga duración',
            'mayores_45' => 'Mayores de 45 años',
            'migrantes' => 'Personas migrantes',
            'perceptores_prestaciones' => 'Perceptores de prestaciones/subsidio/RAI',
            'otros' => 'Otros',
        ];

        $params = [
            'nombre' => $solicitud->getNombre(),
            'colectivo' => $colectivos[$solicitud->getColectivoInferido()] ?? 'Por determinar',
            'dashboard_url' => \Drupal\Core\Url::fromRoute(
                'jaraba_andalucia_ei.dashboard',
                [],
                ['absolute' => TRUE]
            )->toString(),
        ];

        $this->mailManager->mail(
            'jaraba_andalucia_ei',
            'confirmacion_solicitud',
            $email,
            'es',
            $params,
        );
    }
}
