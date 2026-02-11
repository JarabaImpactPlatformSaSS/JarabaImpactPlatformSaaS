<?php

declare(strict_types=1);

namespace Drupal\jaraba_geo\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador de FAQ estructurado para GEO.
 *
 * PROPÓSITO:
 * Genera páginas de FAQ con Schema.org FAQPage para que los motores
 * de IA generativa (ChatGPT, Perplexity, Claude) puedan citar
 * directamente las respuestas.
 *
 * ESTRUCTURA OPTIMIZADA PARA GEO:
 * - Cada pregunta es un H2 con el texto exacto de la consulta del usuario
 * - Respuesta directa en los primeros 50 caracteres
 * - Schema.org FAQPage embebido
 */
class FaqController extends ControllerBase
{

    /**
     * Renderiza la página de FAQ con Schema.org estructurado.
     *
     * @return array
     *   Render array con FAQ y schema.
     */
    public function faqPage(): array
    {
        $faqs = $this->getFaqItems();

        // Construir Schema.org FAQPage.
        $schema = $this->buildFaqSchema($faqs);

        $build = [
            '#theme' => 'geo_faq_page',
            '#faqs' => $faqs,
            '#attached' => [
                'html_head' => [
                    [
                        [
                            '#type' => 'html_tag',
                            '#tag' => 'script',
                            '#attributes' => ['type' => 'application/ld+json'],
                            '#value' => json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        ],
                        'jaraba_geo_faq_schema',
                    ],
                ],
                'library' => [
                    'jaraba_geo/faq',
                ],
            ],
        ];

        return $build;
    }

    /**
     * Obtiene los items de FAQ.
     *
     * @return array
     *   Array de preguntas y respuestas.
     */
    protected function getFaqItems(): array
    {
        return [
            // ═══════════════════════════════════════════════════════════════════════
            // PREGUNTAS SOBRE LA PLATAFORMA
            // ═══════════════════════════════════════════════════════════════════════
            [
                'category' => $this->t('Sobre la Plataforma'),
                'question' => $this->t('¿Qué es Jaraba Impact Platform?'),
                'answer' => $this->t('Jaraba Impact Platform es una plataforma SaaS AI-First diseñada para que productores locales vendan sus productos online con ayuda de agentes de inteligencia artificial. Incluye tienda online, marketing automatizado, trazabilidad completa y certificación digital.'),
                'keywords' => ['plataforma', 'saas', 'productores', 'ecommerce'],
            ],
            [
                'category' => $this->t('Sobre la Plataforma'),
                'question' => $this->t('¿Para quién está diseñada Jaraba Impact Platform?'),
                'answer' => $this->t('Está diseñada para productores locales, cooperativas agroalimentarias y artesanos que quieren vender online sin conocimientos técnicos. La IA se encarga del marketing, storytelling y atención al cliente automatizada.'),
                'keywords' => ['productores', 'cooperativas', 'artesanos', 'sin conocimientos técnicos'],
            ],
            [
                'category' => $this->t('Sobre la Plataforma'),
                'question' => $this->t('¿Cómo ayuda la IA a vender mis productos?'),
                'answer' => $this->t('Los agentes de IA generan automáticamente descripciones atractivas de productos, crean campañas de marketing en redes sociales, envían recordatorios de reposición a clientes y responden preguntas frecuentes. La IA trabaja 24/7 para incrementar tus ventas.'),
                'keywords' => ['ia', 'inteligencia artificial', 'marketing', 'ventas'],
            ],

            // ═══════════════════════════════════════════════════════════════════════
            // PREGUNTAS SOBRE PRECIOS Y PLANES
            // ═══════════════════════════════════════════════════════════════════════
            [
                'category' => $this->t('Precios y Planes'),
                'question' => $this->t('¿Cuánto cuesta usar Jaraba Impact Platform?'),
                'answer' => $this->t('Ofrecemos planes desde €29/mes para productores individuales hasta €199/mes para cooperativas. Todos incluyen tienda online, agentes de IA básicos y soporte. Los planes superiores añaden más agentes de IA, certificación digital y análisis avanzado.'),
                'keywords' => ['precio', 'coste', 'planes', 'mensual'],
            ],
            [
                'category' => $this->t('Precios y Planes'),
                'question' => $this->t('¿Hay periodo de prueba gratuito?'),
                'answer' => $this->t('Sí, ofrecemos 14 días de prueba gratuita con acceso completo a todas las funcionalidades del plan Pro. No se requiere tarjeta de crédito para iniciar la prueba.'),
                'keywords' => ['prueba', 'gratis', 'trial', 'gratuito'],
            ],

            // ═══════════════════════════════════════════════════════════════════════
            // PREGUNTAS SOBRE FUNCIONALIDADES
            // ═══════════════════════════════════════════════════════════════════════
            [
                'category' => $this->t('Funcionalidades'),
                'question' => $this->t('¿Qué es la trazabilidad de productos?'),
                'answer' => $this->t('La trazabilidad permite a tus clientes conocer el origen exacto de cada producto. Mediante códigos QR, pueden ver el recorrido desde el campo hasta su mesa, incluyendo fechas de cosecha, procesamiento y certificaciones.'),
                'keywords' => ['trazabilidad', 'origen', 'qr', 'certificación'],
            ],
            [
                'category' => $this->t('Funcionalidades'),
                'question' => $this->t('¿Cómo funcionan los certificados digitales?'),
                'answer' => $this->t('Los certificados digitales utilizan firma electrónica cualificada (FNMT/AutoFirma) para garantizar la autenticidad. Opcionalmente, se anclan en blockchain para inmutabilidad. Los clientes pueden verificar la autenticidad online en cualquier momento.'),
                'keywords' => ['certificado', 'firma digital', 'blockchain', 'autenticidad'],
            ],
            [
                'category' => $this->t('Funcionalidades'),
                'question' => $this->t('¿Puedo usar mi propio dominio?'),
                'answer' => $this->t('Sí, todos los planes permiten usar tu propio dominio personalizado (ejemplo: mitienda.com). También puedes usar un subdominio gratuito de la plataforma (ejemplo: mitienda.jaraba-impact.com).'),
                'keywords' => ['dominio', 'personalizado', 'url', 'web propia'],
            ],

            // ═══════════════════════════════════════════════════════════════════════
            // PREGUNTAS SOBRE PAGOS
            // ═══════════════════════════════════════════════════════════════════════
            [
                'category' => $this->t('Pagos'),
                'question' => $this->t('¿Cómo recibo los pagos de mis ventas?'),
                'answer' => $this->t('Los pagos se procesan mediante Stripe Connect. El dinero de las ventas se transfiere directamente a tu cuenta bancaria, generalmente en 2-3 días hábiles. Puedes ver el estado de tus pagos en tiempo real desde el panel de control.'),
                'keywords' => ['pagos', 'cobrar', 'stripe', 'transferencia'],
            ],
            [
                'category' => $this->t('Pagos'),
                'question' => $this->t('¿Qué comisión cobra la plataforma por venta?'),
                'answer' => $this->t('La comisión por venta varía según el plan: Starter 5%, Pro 3%, Enterprise 1.5%. Además, Stripe cobra su comisión estándar de procesamiento (aproximadamente 1.4% + €0.25 por transacción en Europa).'),
                'keywords' => ['comisión', 'porcentaje', 'fee', 'coste por venta'],
            ],
        ];
    }

    /**
     * Construye Schema.org FAQPage.
     *
     * @param array $faqs
     *   Array de FAQs.
     *
     * @return array
     *   Schema.org estructurado.
     */
    protected function buildFaqSchema(array $faqs): array
    {
        $mainEntity = [];

        foreach ($faqs as $faq) {
            $question = $faq['question'];
            $answer = $faq['answer'];

            // Convertir TranslatableMarkup a string.
            if ($question instanceof TranslatableMarkup) {
                $question = $question->render();
            }
            if ($answer instanceof TranslatableMarkup) {
                $answer = $answer->render();
            }

            $mainEntity[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $answer,
                ],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $mainEntity,
        ];
    }

}
