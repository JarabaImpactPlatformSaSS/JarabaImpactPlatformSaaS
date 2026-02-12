<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Service;

use Drupal\Core\Database\Connection;
use Psr\Log\LoggerInterface;

/**
 * Servicio de base de conocimiento normativo.
 *
 * Proporciona acceso a la información fiscal y de Seguridad Social
 * para enriquecer las respuestas de los modos expertos del Copiloto.
 *
 * La tabla normative_knowledge_base contiene datos verificados
 * con referencias legales y fechas de validez.
 *
 * @see 20260121d-Addendum_Tecnico_v2_1_EDI.md
 */
class NormativeKnowledgeService
{

    /**
     * Dominios de conocimiento.
     */
    const DOMAIN_TAX = 'TAX';
    const DOMAIN_SOCIAL_SECURITY = 'SOCIAL_SECURITY';

    /**
     * Temas fiscales.
     */
    const TAX_TOPICS = [
        'ALTA_CENSAL',
        'IVA',
        'IRPF',
        'CALENDARIO',
        'FACTURACION',
    ];

    /**
     * Temas de Seguridad Social.
     */
    const SS_TOPICS = [
        'CUOTA',
        'PRESTACIONES',
        'BONIFICACIONES',
        'COMPATIBILIDAD',
        'ALTA',
    ];

    /**
     * Database connection.
     */
    protected Connection $database;

    /**
     * Logger.
     */
    protected LoggerInterface $logger;

    /**
     * Constructor.
     */
    public function __construct(Connection $database, LoggerInterface $logger)
    {
        $this->database = $database;
        $this->logger = $logger;
    }

    /**
     * Obtiene conocimiento normativo por dominio y tema.
     *
     * @param string $domain
     *   Dominio (TAX o SOCIAL_SECURITY).
     * @param string|null $topic
     *   Tema opcional para filtrar.
     *
     * @return array
     *   Array de registros de conocimiento.
     */
    public function getKnowledge(string $domain, ?string $topic = NULL): array
    {
        try {
            $query = $this->database->select('normative_knowledge_base', 'n')
                ->fields('n', [
                    'content_key',
                    'content_es',
                    'legal_reference',
                    'valid_from',
                    'valid_until',
                    'last_verified',
                ])
                ->condition('domain', $domain);

            if ($topic) {
                $query->condition('topic', $topic);
            }

            // Solo registros válidos
            $query->condition(
                $query->orConditionGroup()
                    ->isNull('valid_until')
                    ->condition('valid_until', date('Y-m-d'), '>=')
            );

            $query->orderBy('topic');
            $query->orderBy('content_key');

            return $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->logger->warning('Error consultando base normativa: @message', [
                '@message' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Detecta temas mencionados en un mensaje.
     *
     * @param string $message
     *   Mensaje del usuario.
     * @param string $domain
     *   Dominio para buscar temas.
     *
     * @return array
     *   Temas detectados.
     */
    public function detectTopics(string $message, string $domain): array
    {
        $messageLower = mb_strtolower($message);
        $detectedTopics = [];

        $topicKeywords = $this->getTopicKeywords($domain);

        foreach ($topicKeywords as $topic => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($messageLower, $keyword)) {
                    $detectedTopics[] = $topic;
                    break;
                }
            }
        }

        return array_unique($detectedTopics);
    }

    /**
     * Enriquece el contexto con conocimiento normativo relevante.
     *
     * @param string $mode
     *   Modo del copiloto (TAX_EXPERT o SS_EXPERT).
     * @param string $message
     *   Mensaje del usuario.
     *
     * @return array
     *   Conocimiento normativo relevante.
     */
    public function enrichContext(string $mode, string $message): array
    {
        $domain = $this->getDomainForMode($mode);
        if (!$domain) {
            return [];
        }

        $topics = $this->detectTopics($message, $domain);
        $knowledge = [];

        foreach ($topics as $topic) {
            $topicKnowledge = $this->getKnowledge($domain, $topic);
            $knowledge = array_merge($knowledge, $topicKnowledge);
        }

        // Si no se detectaron temas específicos, devolver conocimiento general
        if (empty($knowledge)) {
            $knowledge = $this->getKnowledge($domain);
        }

        // Limitar a los más relevantes
        return array_slice($knowledge, 0, 5);
    }

    /**
     * Obtiene las palabras clave por tema.
     */
    protected function getTopicKeywords(string $domain): array
    {
        if ($domain === self::DOMAIN_TAX) {
            return [
                'ALTA_CENSAL' => ['036', '037', 'alta censal', 'epígrafe', 'iae', 'darme de alta'],
                'IVA' => ['iva', '303', '21%', '10%', '4%', 'repercutir', 'soportado'],
                'IRPF' => ['irpf', '130', '131', 'renta', 'deducir', 'gastos deducibles', 'retención'],
                'CALENDARIO' => ['trimestre', 'fecha', 'plazo', 'abril', 'julio', 'octubre', 'enero', 'declaración'],
                'FACTURACION' => ['factura', 'facturar', 'verifactu', 'electrónica', 'simplificada'],
            ];
        }

        if ($domain === self::DOMAIN_SOCIAL_SECURITY) {
            return [
                'CUOTA' => ['cuota', 'tarifa plana', '80€', 'base cotización', 'tramo', 'ingresos reales'],
                'PRESTACIONES' => ['baja', 'incapacidad', 'maternidad', 'paternidad', 'cese actividad', 'prestación'],
                'BONIFICACIONES' => ['bonificación', 'conciliación', 'discapacidad', 'reducción'],
                'COMPATIBILIDAD' => ['pluriactividad', 'cuenta ajena', 'jubilación activa', 'compatible'],
                'ALTA' => ['alta reta', 'darme de alta', 'autónomo', 'plazo alta', 'documentación'],
            ];
        }

        return [];
    }

    /**
     * Obtiene el dominio para un modo del copiloto.
     */
    protected function getDomainForMode(string $mode): ?string
    {
        return match ($mode) {
            'fiscal', 'TAX_EXPERT' => self::DOMAIN_TAX,
            'laboral', 'SS_EXPERT' => self::DOMAIN_SOCIAL_SECURITY,
            default => NULL,
        };
    }

    /**
     * Verifica si la tabla de conocimiento normativo existe.
     *
     * @return bool
     *   TRUE si existe.
     */
    public function tableExists(): bool
    {
        return $this->database->schema()->tableExists('normative_knowledge_base');
    }

    /**
     * Obtiene el disclaimer obligatorio para modos expertos.
     *
     * @param string $mode
     *   Modo del copiloto.
     *
     * @return string|null
     *   Disclaimer o NULL si no aplica.
     */
    public function getDisclaimer(string $mode): ?string
    {
        if (in_array($mode, ['fiscal', 'laboral', 'TAX_EXPERT', 'SS_EXPERT'])) {
            return t('⚠️ Esta información es orientativa y de carácter general. La normativa puede cambiar y cada situación es única. Para decisiones importantes, consulta con un profesional colegiado (asesor fiscal, gestor administrativo, graduado social).');
        }
        return NULL;
    }

}
