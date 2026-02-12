<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
 *
 * @see docs/implementacion/2026-02-12_F3_Visitor_Journey_Complete_Doc178_Implementacion.md
 */
class LeadMagnetController extends ControllerBase
{

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        $instance = new static();
        $instance->formBuilder = $container->get('form_builder');
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

}
