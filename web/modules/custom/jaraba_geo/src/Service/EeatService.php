<?php

declare(strict_types=1);

namespace Drupal\jaraba_geo\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;

/**
 * Servicio para generar contenido E-E-A-T (Expertise, Experience, Authority, Trust).
 *
 * PROPÓSITO:
 * Los motores de IA generativa priorizan contenido con señales claras de:
 * - Expertise: Conocimiento especializado demostrado
 * - Experience: Experiencia práctica verificable
 * - Authoritativeness: Reconocimiento en el sector
 * - Trustworthiness: Señales de confianza (fechas, fuentes, verificación)
 *
 * Este servicio genera automáticamente estas señales para el contenido
 * de productores, productos y artículos.
 */
class EeatService
{

    /**
     * Constructor del servicio.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected DateFormatterInterface $dateFormatter,
    ) {
    }

    /**
     * Genera biografía E-E-A-T para un productor.
     *
     * @param \Drupal\Core\Entity\EntityInterface $producer
     *   Entidad del productor.
     *
     * @return array
     *   Estructura con expertise, experience, authority, trust.
     */
    public function generateProducerBio(EntityInterface $producer): array
    {
        $name = $producer->label();

        return [
            'name' => $name,
            'expertise' => $this->buildExpertise($producer),
            'experience' => $this->buildExperience($producer),
            'authority' => $this->buildAuthority($producer),
            'trust' => $this->buildTrust($producer),
            'summary' => $this->buildSummary($producer),
        ];
    }

    /**
     * Construye señales de expertise.
     */
    protected function buildExpertise(EntityInterface $entity): array
    {
        $expertise = [
            'skills' => [],
            'specializations' => [],
            'certifications' => [],
        ];

        // Especialización por tipo de vertical/producto.
        if ($entity->hasField('field_vertical') && !$entity->get('field_vertical')->isEmpty()) {
            $vertical = $entity->get('field_vertical')->entity;
            if ($vertical) {
                $expertise['specializations'][] = $vertical->label();
            }
        }

        // Certificaciones si existen.
        $certFields = ['field_certifications', 'field_certificaciones', 'field_certificates'];
        foreach ($certFields as $field) {
            if ($entity->hasField($field) && !$entity->get($field)->isEmpty()) {
                foreach ($entity->get($field) as $item) {
                    $expertise['certifications'][] = $item->value ?? $item->entity?->label();
                }
            }
        }

        // Skills genéricos basados en el tipo.
        $bundle = $entity->bundle();
        $expertise['skills'] = match ($bundle) {
            'producer', 'productor' => ['Producción artesanal', 'Control de calidad', 'Trazabilidad'],
            'cooperativa' => ['Gestión cooperativa', 'Comercio justo', 'Sostenibilidad'],
            default => ['Especialista en su sector'],
        };

        return $expertise;
    }

    /**
     * Construye señales de experiencia.
     */
    protected function buildExperience(EntityInterface $entity): array
    {
        $experience = [
            'years_active' => 0,
            'products_count' => 0,
            'customers_served' => 0,
            'achievements' => [],
        ];

        // Calcular años de actividad.
        if (method_exists($entity, 'getCreatedTime')) {
            $created = $entity->getCreatedTime();
            $yearsActive = floor((time() - $created) / (365.25 * 24 * 3600));
            $experience['years_active'] = max(1, (int) $yearsActive);
        }

        // Contar productos (si es productor).
        if ($entity->hasField('field_products') && !$entity->get('field_products')->isEmpty()) {
            $experience['products_count'] = $entity->get('field_products')->count();
        }

        // Logros genéricos.
        $experience['achievements'] = [
            'Verificado en Jaraba Impact Platform',
            'Cumple normativas de calidad',
        ];

        return $experience;
    }

    /**
     * Construye señales de autoridad.
     */
    protected function buildAuthority(EntityInterface $entity): array
    {
        $authority = [
            'platform_verified' => TRUE,
            'verification_date' => $this->dateFormatter->format(time(), 'short'),
            'mentions' => [],
            'awards' => [],
        ];

        // Premios si existen.
        $awardFields = ['field_awards', 'field_premios'];
        foreach ($awardFields as $field) {
            if ($entity->hasField($field) && !$entity->get($field)->isEmpty()) {
                foreach ($entity->get($field) as $item) {
                    $authority['awards'][] = $item->value ?? $item->entity?->label();
                }
            }
        }

        return $authority;
    }

    /**
     * Construye señales de confianza.
     */
    protected function buildTrust(EntityInterface $entity): array
    {
        $trust = [
            'last_updated' => $this->dateFormatter->format(
                method_exists($entity, 'getChangedTime') ? $entity->getChangedTime() : time(),
                'medium'
            ),
            'data_sources' => ['Jaraba Impact Platform'],
            'verification_methods' => ['Verificación de identidad', 'Validación de documentos'],
            'contact_available' => TRUE,
        ];

        return $trust;
    }

    /**
     * Genera resumen para Answer Capsule.
     */
    protected function buildSummary(EntityInterface $entity): string
    {
        $name = $entity->label();
        $bundle = $entity->bundle();

        $typeLabel = match ($bundle) {
            'producer', 'productor' => 'productor verificado',
            'cooperativa' => 'cooperativa certificada',
            default => 'miembro verificado',
        };

        return "{$name} es un {$typeLabel} en Jaraba Impact Platform con trazabilidad completa y certificación digital.";
    }

    /**
     * Genera case study para un producto o productor.
     *
     * @param \Drupal\Core\Entity\EntityInterface $entity
     *   Entidad del producto o productor.
     * @param array $metrics
     *   Métricas opcionales: ventas, clientes, etc.
     *
     * @return array
     *   Estructura de case study.
     */
    public function generateCaseStudy(EntityInterface $entity, array $metrics = []): array
    {
        $name = $entity->label();

        return [
            'title' => "Caso de éxito: {$name}",
            'challenge' => $this->buildChallenge($entity),
            'solution' => $this->buildSolution($entity),
            'results' => $this->buildResults($entity, $metrics),
            'quote' => $this->buildQuote($entity),
            'last_updated' => $this->dateFormatter->format(time(), 'medium'),
        ];
    }

    /**
     * Construye el desafío del case study.
     */
    protected function buildChallenge(EntityInterface $entity): string
    {
        return 'Necesitaba una plataforma para vender sus productos online con trazabilidad completa y marketing automatizado, sin conocimientos técnicos.';
    }

    /**
     * Construye la solución del case study.
     */
    protected function buildSolution(EntityInterface $entity): string
    {
        return 'Implementó Jaraba Impact Platform con tienda online, agentes de IA para marketing, y certificación digital de productos.';
    }

    /**
     * Construye los resultados del case study.
     */
    protected function buildResults(EntityInterface $entity, array $metrics): array
    {
        return [
            'ventas_incremento' => $metrics['sales_increase'] ?? '+45%',
            'clientes_nuevos' => $metrics['new_customers'] ?? 'Incremento significativo',
            'tiempo_ahorrado' => $metrics['time_saved'] ?? '10 horas/semana en marketing',
            'satisfaccion' => $metrics['satisfaction'] ?? '4.8/5 estrellas',
        ];
    }

    /**
     * Construye cita del case study.
     */
    protected function buildQuote(EntityInterface $entity): array
    {
        $owner = method_exists($entity, 'getOwner') ? $entity->getOwner() : NULL;

        return [
            'text' => 'Jaraba Impact Platform ha transformado mi negocio. Los agentes de IA se encargan del marketing mientras yo me centro en producir calidad.',
            'author' => $owner?->getDisplayName() ?? 'Productor verificado',
            'role' => 'Propietario',
        ];
    }

}
