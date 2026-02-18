<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\ecosistema_jaraba_core\Service\LeadMagnetEmailService;
use Drupal\ecosistema_jaraba_core\Service\LeadMagnetPdfService;
use Drupal\ecosistema_jaraba_core\Service\SeoAuditService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador para lead magnets publicos por vertical.
 *
 * Cada vertical tiene un lead magnet especifico que genera valor inmediato
 * al visitante anonimo antes de pedir registro. El patron comun es:
 *
 * 1. Ruta publica (sin autenticacion)
 * 2. Formulario con captura de email (obligatorio) + nombre
 * 3. Procesamiento del resultado (scoring, PDF, template)
 * 4. Envio por email + CTA "Ver mas detalles"
 * 5. Tracking: lead_magnet_start / lead_magnet_complete via jaraba_pixels
 *
 * DIRECTRICES:
 * - i18n: $this->t() en todos los textos
 * - Rutas publicas con _access: 'TRUE'
 * - Templates limpios sin regiones Drupal
 * - Tracking via jaraba_pixels
 * - PHP 8.4 strict types
 * - Controller thin: delega a LeadMagnetEmailService
 *
 * @see docs/implementacion/2026-02-12_F3_Visitor_Journey_Complete_Doc178_Implementacion.md
 */
class LeadMagnetController extends ControllerBase
{

    /**
     * Servicio de envio de emails para lead magnets.
     */
    protected LeadMagnetEmailService $leadMagnetEmail;

    /**
     * Servicio de generacion de HTML/PDF para lead magnets.
     */
    protected LeadMagnetPdfService $leadMagnetPdf;

    /**
     * Servicio de auditoria SEO.
     */
    protected SeoAuditService $seoAudit;

    /**
     * Logger del modulo.
     */
    protected LoggerInterface $leadLogger;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        $instance = new static();
        $instance->formBuilder = $container->get('form_builder');
        $instance->leadMagnetEmail = $container->get('ecosistema_jaraba_core.lead_magnet_email');
        $instance->leadMagnetPdf = $container->get('ecosistema_jaraba_core.lead_magnet_pdf');
        $instance->seoAudit = $container->get('ecosistema_jaraba_core.seo_audit');
        $instance->leadLogger = $container->get('logger.channel.ecosistema_jaraba_core');
        return $instance;
    }

    /**
     * Lead Magnet: Calculadora de Madurez Digital (Emprendimiento).
     *
     * Formulario interactivo multi-paso que evalua la madurez digital
     * del visitante y genera un score 0-100 con recomendaciones.
     *
     * Ruta: /emprendimiento/calculadora-madurez
     * Conversion target: > 18%
     */
    public function calculadoraMadurez(): array
    {
        return [
            '#theme' => 'lead_magnet_calculadora_madurez',
            '#magnet_data' => [
                'vertical' => 'emprendimiento',
                'magnet_type' => 'calculadora_madurez',
                'title' => $this->t('Calculadora de Madurez Digital'),
                'subtitle' => $this->t('Descubre en 3 minutos el nivel de digitalizacion de tu negocio'),
                'icon' => [
                    'category' => 'verticals',
                    'name' => 'emprendimiento',
                    'variant' => 'duotone',
                    'color' => 'naranja-impulso',
                ],
                'questions' => $this->getCalculadoraQuestions(),
                'cta_register' => [
                    'text' => $this->t('Empieza gratis'),
                    'url' => '/registro?vertical=emprendimiento&source=calculadora_madurez',
                ],
            ],
            '#attached' => [
                'library' => [
                    'ecosistema_jaraba_core/lead-magnet',
                ],
            ],
        ];
    }

    /**
     * Lead Magnet: Guia "Vende Online sin Intermediarios" (AgroConecta).
     *
     * PDF descargable tras captura de email.
     *
     * Ruta: /agroconecta/guia-vende-online
     * Conversion target: > 15%
     */
    public function guiaAgroconecta(): array
    {
        return [
            '#theme' => 'lead_magnet_guia_agro',
            '#magnet_data' => [
                'vertical' => 'agroconecta',
                'magnet_type' => 'guia_vende_online',
                'title' => $this->t('Guia: Vende Online sin Intermediarios'),
                'subtitle' => $this->t('Descarga gratis la guia para vender tus productos del campo directamente al consumidor'),
                'icon' => [
                    'category' => 'verticals',
                    'name' => 'agroconecta',
                    'variant' => 'duotone',
                    'color' => 'verde-agro',
                ],
                'benefits' => [
                    $this->t('Elimina intermediarios y gana mas'),
                    $this->t('Monta tu tienda online en 10 minutos'),
                    $this->t('Cobra directamente sin comisiones ocultas'),
                    $this->t('Llega a clientes de toda España'),
                ],
                'cta_register' => [
                    'text' => $this->t('Crea tu tienda gratis'),
                    'url' => '/registro?vertical=agroconecta&source=guia_vende_online',
                ],
            ],
            '#attached' => [
                'library' => [
                    'ecosistema_jaraba_core/lead-magnet',
                ],
            ],
        ];
    }

    /**
     * Lead Magnet: Auditoria SEO Local (ComercioConecta).
     *
     * Herramienta automatizada que analiza la presencia SEO del negocio.
     *
     * Ruta: /comercioconecta/auditoria-seo
     * Conversion target: > 22%
     */
    public function auditoriaSeo(): array
    {
        return [
            '#theme' => 'lead_magnet_auditoria_seo',
            '#magnet_data' => [
                'vertical' => 'comercioconecta',
                'magnet_type' => 'auditoria_seo',
                'title' => $this->t('Auditoria SEO Local Gratuita'),
                'subtitle' => $this->t('Analiza gratis la visibilidad online de tu negocio en menos de 2 minutos'),
                'icon' => [
                    'category' => 'verticals',
                    'name' => 'comercioconecta',
                    'variant' => 'duotone',
                    'color' => 'naranja-impulso',
                ],
                'checks' => [
                    $this->t('Presencia en Google Maps'),
                    $this->t('SEO basico de tu web'),
                    $this->t('Reseñas y reputacion online'),
                    $this->t('Comparativa con competidores locales'),
                ],
                'cta_register' => [
                    'text' => $this->t('Mejora tu visibilidad gratis'),
                    'url' => '/registro?vertical=comercioconecta&source=auditoria_seo',
                ],
            ],
            '#attached' => [
                'library' => [
                    'ecosistema_jaraba_core/lead-magnet',
                ],
            ],
        ];
    }

    /**
     * Lead Magnet: Template Propuesta Profesional (ServiciosConecta).
     *
     * Documento descargable tras captura de email.
     *
     * Ruta: /serviciosconecta/template-propuesta
     * Conversion target: > 12%
     */
    public function templatePropuesta(): array
    {
        return [
            '#theme' => 'lead_magnet_template_propuesta',
            '#magnet_data' => [
                'vertical' => 'serviciosconecta',
                'magnet_type' => 'template_propuesta',
                'title' => $this->t('Template: Propuesta Profesional'),
                'subtitle' => $this->t('Descarga gratis una plantilla profesional para enviar presupuestos a tus clientes'),
                'icon' => [
                    'category' => 'verticals',
                    'name' => 'serviciosconecta',
                    'variant' => 'duotone',
                    'color' => 'verde-innovacion',
                ],
                'includes' => [
                    $this->t('Plantilla de presupuesto profesional'),
                    $this->t('Estructura de propuesta de servicios'),
                    $this->t('Clausulas legales basicas'),
                    $this->t('Guia de personalizacion'),
                ],
                'cta_register' => [
                    'text' => $this->t('Gestiona tus clientes gratis'),
                    'url' => '/registro?vertical=serviciosconecta&source=template_propuesta',
                ],
            ],
            '#attached' => [
                'library' => [
                    'ecosistema_jaraba_core/lead-magnet',
                ],
            ],
        ];
    }

    /**
     * Devuelve las preguntas de la calculadora de madurez digital.
     *
     * @return array
     *   Array de preguntas con opciones y pesos.
     */
    protected function getCalculadoraQuestions(): array
    {
        return [
            [
                'id' => 'q1_web',
                'question' => $this->t('¿Tu negocio tiene presencia en internet?'),
                'options' => [
                    ['label' => $this->t('No tengo web ni redes'), 'score' => 0],
                    ['label' => $this->t('Solo redes sociales'), 'score' => 10],
                    ['label' => $this->t('Web basica + redes'), 'score' => 20],
                    ['label' => $this->t('Web con tienda online'), 'score' => 30],
                ],
            ],
            [
                'id' => 'q2_ventas',
                'question' => $this->t('¿Como gestionas tus ventas?'),
                'options' => [
                    ['label' => $this->t('Presencial, libreta o Excel'), 'score' => 0],
                    ['label' => $this->t('Facturacion basica digital'), 'score' => 10],
                    ['label' => $this->t('CRM o software de gestion'), 'score' => 20],
                    ['label' => $this->t('Sistema integrado con automatizaciones'), 'score' => 30],
                ],
            ],
            [
                'id' => 'q3_clientes',
                'question' => $this->t('¿Como captas nuevos clientes?'),
                'options' => [
                    ['label' => $this->t('Solo boca a boca'), 'score' => 0],
                    ['label' => $this->t('Publicidad local basica'), 'score' => 8],
                    ['label' => $this->t('Marketing digital basico'), 'score' => 16],
                    ['label' => $this->t('Estrategia digital multicanal'), 'score' => 24],
                ],
            ],
            [
                'id' => 'q4_equipo',
                'question' => $this->t('¿Tu equipo usa herramientas digitales?'),
                'options' => [
                    ['label' => $this->t('No, todo es manual'), 'score' => 0],
                    ['label' => $this->t('Email y alguna herramienta basica'), 'score' => 5],
                    ['label' => $this->t('Herramientas de colaboracion (Slack, Teams)'), 'score' => 10],
                    ['label' => $this->t('Suite completa de productividad'), 'score' => 16],
                ],
            ],
        ];
    }

    // =========================================================================
    // SUBMISSION HANDLERS (POST)
    // =========================================================================

    /**
     * Procesa el envio del formulario de Calculadora de Madurez Digital.
     *
     * Acepta JSON POST con: email, name, score, answers.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La peticion HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con resultado de la operacion.
     */
    public function submitCalculadora(Request $request): JsonResponse
    {
        return $this->processSubmission($request, 'calculadora_madurez', ['score', 'answers']);
    }

    /**
     * Procesa el envio del formulario de Guia Vende Online.
     *
     * Acepta JSON POST con: email, name, product_type (opcional).
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La peticion HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con resultado de la operacion.
     */
    public function submitGuia(Request $request): JsonResponse
    {
        return $this->processSubmission($request, 'guia_vende_online', ['product_type']);
    }

    /**
     * Procesa el envio del formulario de Auditoria SEO Local.
     *
     * Acepta JSON POST con: email, name, business_name, website_url.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La peticion HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con resultado de la operacion.
     */
    public function submitAuditoria(Request $request): JsonResponse
    {
        return $this->processSubmission($request, 'auditoria_seo', ['business_name', 'website_url']);
    }

    /**
     * Procesa el envio del formulario de Template Propuesta Profesional.
     *
     * Acepta JSON POST con: email, name, service_type (opcional).
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La peticion HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con resultado de la operacion.
     */
    public function submitPropuesta(Request $request): JsonResponse
    {
        return $this->processSubmission($request, 'template_propuesta', ['service_type']);
    }

    /**
     * Procesa un envio generico de lead magnet.
     *
     * Patron comun para todos los lead magnets:
     * 1. Parsear JSON body
     * 2. Validar email y name
     * 3. Almacenar lead en State API
     * 4. Enviar email con resultados via LeadMagnetEmailService
     * 5. Registrar evento de tracking (jaraba_pixels)
     * 6. Retornar JsonResponse
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La peticion HTTP.
     * @param string $type
     *   Tipo de lead magnet.
     * @param array $extraFields
     *   Campos adicionales a extraer del JSON body.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON.
     */
    protected function processSubmission(Request $request, string $type, array $extraFields = []): JsonResponse
    {
        try {
            // 1. Parsear JSON body.
            $content = $request->getContent();
            $payload = json_decode($content, TRUE);

            if (empty($payload) || !is_array($payload)) {
                // AUDIT-CONS-N08: Standardized JSON envelope.
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => ['code' => 'INVALID_BODY', 'message' => (string) $this->t('Cuerpo de la peticion invalido. Se esperaba JSON.')],
                ], 400);
            }

            // 2. Extraer y validar campos requeridos.
            $email = trim((string) ($payload['email'] ?? ''));
            $name = trim((string) ($payload['name'] ?? ''));

            if (empty($email)) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => ['code' => 'VALIDATION_ERROR', 'message' => (string) $this->t('El campo email es obligatorio.')],
                ], 422);
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => ['code' => 'VALIDATION_ERROR', 'message' => (string) $this->t('El formato del email no es valido.')],
                ], 422);
            }

            if (empty($name)) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => ['code' => 'VALIDATION_ERROR', 'message' => (string) $this->t('El campo nombre es obligatorio.')],
                ], 422);
            }

            // 3. Extraer campos adicionales del payload.
            $data = [];
            foreach ($extraFields as $field) {
                if (isset($payload[$field])) {
                    $data[$field] = $payload[$field];
                }
            }

            // 4. Almacenar lead en State API para persistencia ligera.
            $this->storeLead($type, $email, $name, $data);

            // 5. Enviar email con resultados.
            $emailSent = $this->leadMagnetEmail->sendResults($type, $email, $name, $data);

            // 6. Registrar evento de tracking via UpgradeTriggerService o logging.
            $this->trackLeadMagnetEvent($type, $email, $data);

            return new JsonResponse([
                'success' => TRUE,
                'data' => [
                    'message' => (string) $this->t('Tus resultados han sido enviados a @email', [
                        '@email' => $email,
                    ]),
                    'email_sent' => $emailSent,
                    'type' => $type,
                ],
                'meta' => ['timestamp' => time()],
            ], 200);
        }
        catch (\Exception $e) {
            $this->leadLogger->error('Lead magnet submission error (@type): @error', [
                '@type' => $type,
                '@error' => $e->getMessage(),
            ]);

            return new JsonResponse([
                'success' => FALSE,
                'error' => ['code' => 'INTERNAL_ERROR', 'message' => (string) $this->t('Ha ocurrido un error al procesar tu solicitud. Por favor, intentalo de nuevo.')],
            ], 500);
        }
    }

    /**
     * Almacena un lead en State API.
     *
     * Usa una clave por tipo de lead magnet con un array de leads.
     * Cada lead contiene: email, name, data, timestamp, ip.
     *
     * @param string $type
     *   Tipo de lead magnet.
     * @param string $email
     *   Email del lead.
     * @param string $name
     *   Nombre del lead.
     * @param array $data
     *   Datos adicionales del lead.
     */
    protected function storeLead(string $type, string $email, string $name, array $data): void
    {
        try {
            $state = \Drupal::state();
            $stateKey = 'lead_magnet_leads_' . $type;
            $leads = $state->get($stateKey, []);

            $leads[] = [
                'email' => $email,
                'name' => $name,
                'data' => $data,
                'timestamp' => time(),
                'ip' => \Drupal::request()->getClientIp(),
            ];

            // Mantener maximo 10000 leads por tipo para no sobrecargar State.
            if (count($leads) > 10000) {
                $leads = array_slice($leads, -10000);
            }

            $state->set($stateKey, $leads);
        }
        catch (\Exception $e) {
            $this->leadLogger->warning('Lead magnet: error almacenando lead @type: @error', [
                '@type' => $type,
                '@error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Registra un evento de tracking para el lead magnet completado.
     *
     * Intenta usar UpgradeTriggerService si esta disponible, o registra
     * directamente en el logger como evento de analytics.
     *
     * @param string $type
     *   Tipo de lead magnet.
     * @param string $email
     *   Email del lead.
     * @param array $data
     *   Datos del lead magnet.
     */
    protected function trackLeadMagnetEvent(string $type, string $email, array $data): void
    {
        try {
            // Intentar usar UpgradeTriggerService para tracking si esta disponible.
            if (\Drupal::hasService('ecosistema_jaraba_core.upgrade_trigger')) {
                $state = \Drupal::state();
                $stateKey = 'lead_magnet_events_' . date('Y-m');
                $events = $state->get($stateKey, []);
                $events[] = [
                    'event' => 'lead_magnet_complete',
                    'type' => $type,
                    'email_hash' => hash('sha256', $email),
                    'score' => $data['score'] ?? NULL,
                    'timestamp' => time(),
                ];
                $state->set($stateKey, $events);
            }

            // Log del evento para analytics.
            $this->leadLogger->info('Lead magnet completado: @type | email_hash=@hash | score=@score', [
                '@type' => $type,
                '@hash' => substr(hash('sha256', $email), 0, 12),
                '@score' => $data['score'] ?? 'N/A',
            ]);
        }
        catch (\Exception $e) {
            // Non-blocking: tracking failure should not break the submission.
            $this->leadLogger->warning('Lead magnet tracking error: @error', [
                '@error' => $e->getMessage(),
            ]);
        }
    }

    // =========================================================================
    // PDF DOWNLOAD HANDLERS (GET)
    // =========================================================================

    /**
     * Descarga la Guia AgroConecta como HTML print-ready (PDF via browser).
     *
     * Genera un documento HTML profesional con CSS @media print optimizado
     * que el usuario puede convertir a PDF desde el navegador (Ctrl+P).
     *
     * Los parametros name y email se pasan como query parameters:
     *   /agroconecta/guia-vende-online/descargar?name=Juan&email=juan@ejemplo.com
     *
     * Ruta: /agroconecta/guia-vende-online/descargar
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La peticion HTTP con query params name y email.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *   Respuesta HTML print-ready.
     */
    public function downloadGuiaPdf(Request $request): Response
    {
        try {
            $name = trim($request->query->get('name', ''));
            $email = trim($request->query->get('email', ''));

            if (empty($name)) {
                $name = (string) $this->t('Estimado/a usuario/a');
            }

            if (empty($email)) {
                $email = 'visitante@ecosistemajaraba.org';
            }

            $html = $this->leadMagnetPdf->generateGuiaAgroHtml($name, $email);

            return new Response($html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]);
        }
        catch (\Throwable $e) {
            $this->leadLogger->error('Error generating Guia AgroConecta PDF: @error', [
                '@error' => $e->getMessage(),
            ]);

            return new Response(
                '<html><body><h1>' . $this->t('Error al generar el documento') . '</h1>'
                . '<p>' . $this->t('Ha ocurrido un error. Por favor, intentalo de nuevo.') . '</p>'
                . '<a href="/agroconecta/guia-vende-online">' . $this->t('Volver') . '</a>'
                . '</body></html>',
                500,
                ['Content-Type' => 'text/html; charset=UTF-8'],
            );
        }
    }

    /**
     * Descarga el Template Propuesta como HTML print-ready (PDF via browser).
     *
     * Genera un documento HTML de propuesta profesional con secciones
     * rellenables, tabla de precios, clausulas legales y bloque de firma.
     *
     * Los parametros se pasan como query parameters:
     *   /serviciosconecta/template-propuesta/descargar?name=Ana&email=ana@ejemplo.com
     *
     * Parametros opcionales: business_name, service_type, client_name.
     *
     * Ruta: /serviciosconecta/template-propuesta/descargar
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La peticion HTTP con query params.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *   Respuesta HTML print-ready.
     */
    public function downloadPropuestaPdf(Request $request): Response
    {
        try {
            $name = trim($request->query->get('name', ''));
            $email = trim($request->query->get('email', ''));

            if (empty($name)) {
                $name = (string) $this->t('Estimado/a profesional');
            }

            if (empty($email)) {
                $email = 'visitante@ecosistemajaraba.org';
            }

            $serviceData = [
                'business_name' => trim($request->query->get('business_name', '')),
                'service_type' => trim($request->query->get('service_type', '')),
                'client_name' => trim($request->query->get('client_name', '')),
            ];

            // Remove empty values so service uses its own defaults.
            $serviceData = array_filter($serviceData, fn($v) => $v !== '');

            $html = $this->leadMagnetPdf->generatePropuestaHtml($name, $email, $serviceData);

            return new Response($html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]);
        }
        catch (\Throwable $e) {
            $this->leadLogger->error('Error generating Propuesta PDF: @error', [
                '@error' => $e->getMessage(),
            ]);

            return new Response(
                '<html><body><h1>' . $this->t('Error al generar el documento') . '</h1>'
                . '<p>' . $this->t('Ha ocurrido un error. Por favor, intentalo de nuevo.') . '</p>'
                . '<a href="/serviciosconecta/template-propuesta">' . $this->t('Volver') . '</a>'
                . '</body></html>',
                500,
                ['Content-Type' => 'text/html; charset=UTF-8'],
            );
        }
    }

    // =========================================================================
    // SEO AUDIT HANDLER (POST)
    // =========================================================================

    /**
     * Ejecuta una auditoria SEO sobre la URL proporcionada.
     *
     * Acepta JSON POST con: url (obligatorio), email (opcional), name (opcional).
     * Delega al SeoAuditService y devuelve resultados JSON con score,
     * checks individuales y recomendaciones priorizadas.
     *
     * Ruta: /api/v1/lead-magnet/auditoria-seo/analizar (AUDIT-CONS-N07)
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La peticion HTTP con JSON body.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con resultados de la auditoria.
     */
    public function analyzeUrl(Request $request): JsonResponse
    {
        try {
            $content = $request->getContent();
            $payload = json_decode($content, TRUE);

            if (empty($payload) || !is_array($payload)) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => ['code' => 'INVALID_BODY', 'message' => (string) $this->t('Cuerpo de la peticion invalido. Se esperaba JSON.')],
                ], 400);
            }

            $url = trim((string) ($payload['url'] ?? ''));

            if (empty($url)) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => ['code' => 'VALIDATION_ERROR', 'message' => (string) $this->t('El campo url es obligatorio.')],
                ], 422);
            }

            // Run the SEO audit.
            $auditResult = $this->seoAudit->audit($url);

            // If the visitor provided email/name, store the lead.
            $email = trim((string) ($payload['email'] ?? ''));
            $name = trim((string) ($payload['name'] ?? ''));

            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->storeLead('auditoria_seo', $email, $name ?: 'Anonimo', [
                    'website_url' => $url,
                    'score' => $auditResult['score'],
                    'grade' => $auditResult['grade'],
                ]);

                $this->trackLeadMagnetEvent('auditoria_seo', $email, [
                    'score' => $auditResult['score'],
                ]);
            }

            $isError = !empty($auditResult['error']);
            if ($isError) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => ['code' => 'AUDIT_ERROR', 'message' => $auditResult['error'] ?? 'Audit failed'],
                ], 422);
            }
            return new JsonResponse([
                'success' => TRUE,
                'data' => $auditResult,
                'meta' => ['timestamp' => time()],
            ]);
        }
        catch (\Throwable $e) {
            $this->leadLogger->error('SEO audit controller error: @error', [
                '@error' => $e->getMessage(),
            ]);

            return new JsonResponse([
                'success' => FALSE,
                'error' => ['code' => 'INTERNAL_ERROR', 'message' => (string) $this->t('Ha ocurrido un error al ejecutar la auditoria. Por favor, intentalo de nuevo.')],
            ], 500);
        }
    }

}
