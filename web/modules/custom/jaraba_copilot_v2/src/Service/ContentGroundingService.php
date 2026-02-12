<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de grounding en contenido Drupal para el Copiloto.
 *
 * Busca contenido real en la base de datos para enriquecer
 * las respuestas del copiloto con información actualizada.
 *
 * Adaptado de AgroConecta getContentContext().
 */
class ContentGroundingService
{

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Obtiene contexto de contenido basado en el mensaje del usuario.
     *
     * @param string $userMessage
     *   Mensaje del usuario para extraer keywords.
     * @param string $vertical
     *   Vertical actual (empleabilidad, emprendimiento, comercio).
     *
     * @return string
     *   Contexto formateado para incluir en el prompt.
     */
    public function getContentContext(string $userMessage, string $vertical = 'all'): string
    {
        try {
            $keywords = $this->extractKeywords($userMessage);

            // Buscar según vertical
            $context = '';

            switch ($vertical) {
                case 'empleabilidad':
                    $context = $this->searchOffers($keywords);
                    break;
                case 'emprendimiento':
                    $context = $this->searchEmprendimientos($keywords);
                    break;
                case 'comercio':
                    $context = $this->searchProducts($keywords);
                    break;
                default:
                    // Buscar en todos los tipos
                    $offers = $this->searchOffers($keywords, 5);
                    $emprendimientos = $this->searchEmprendimientos($keywords, 5);
                    $products = $this->searchProducts($keywords, 5);
                    $context = $offers . $emprendimientos . $products;
            }

            return $context ?: '';
        } catch (\Exception $e) {
            $this->logger->warning('Error en ContentGroundingService: @error', [
                '@error' => $e->getMessage(),
            ]);
            return '';
        }
    }

    /**
     * Busca ofertas de empleo relevantes.
     */
    protected function searchOffers(array $keywords, int $limit = 10): string
    {
        try {
            $nodeStorage = $this->entityTypeManager->getStorage('node');

            $query = $nodeStorage->getQuery()
                ->accessCheck(TRUE)
                ->condition('type', 'offer')
                ->condition('status', 1)
                ->range(0, $limit)
                ->sort('created', 'DESC');

            // Añadir filtro por keywords si existen
            if (!empty($keywords)) {
                $orGroup = $query->orConditionGroup();
                foreach ($keywords as $keyword) {
                    $orGroup->condition('title', $keyword, 'CONTAINS');
                    $orGroup->condition('body.value', $keyword, 'CONTAINS');
                }
                $query->condition($orGroup);
            }

            $nids = $query->execute();
            if (empty($nids)) {
                return '';
            }

            $nodes = $nodeStorage->loadMultiple($nids);
            $context = "💼 OFERTAS DE EMPLEO DISPONIBLES:\n";

            foreach ($nodes as $node) {
                $title = $node->getTitle();
                $url = '/node/' . $node->id();

                try {
                    $url = $node->toUrl('canonical', ['absolute' => FALSE])->toString();
                } catch (\Exception $e) {
                }

                // Obtener campos específicos si existen
                $company = '';
                if ($node->hasField('field_company') && !$node->get('field_company')->isEmpty()) {
                    $company = $node->get('field_company')->value;
                }

                $location = '';
                if ($node->hasField('field_location') && !$node->get('field_location')->isEmpty()) {
                    $location = $node->get('field_location')->value;
                }

                $context .= "• **{$title}**";
                if ($company) {
                    $context .= " en {$company}";
                }
                if ($location) {
                    $context .= " ({$location})";
                }
                $context .= " [Ver oferta]({$url})\n";
            }

            return $context . "\n";
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Busca emprendimientos/ideas de negocio.
     */
    protected function searchEmprendimientos(array $keywords, int $limit = 10): string
    {
        try {
            $nodeStorage = $this->entityTypeManager->getStorage('node');

            $query = $nodeStorage->getQuery()
                ->accessCheck(TRUE)
                ->condition('type', 'emprendimiento')
                ->condition('status', 1)
                ->range(0, $limit)
                ->sort('created', 'DESC');

            if (!empty($keywords)) {
                $orGroup = $query->orConditionGroup();
                foreach ($keywords as $keyword) {
                    $orGroup->condition('title', $keyword, 'CONTAINS');
                }
                $query->condition($orGroup);
            }

            $nids = $query->execute();
            if (empty($nids)) {
                return '';
            }

            $nodes = $nodeStorage->loadMultiple($nids);
            $context = "🚀 EMPRENDIMIENTOS EN LA PLATAFORMA:\n";

            foreach ($nodes as $node) {
                $title = $node->getTitle();
                $url = '/node/' . $node->id();

                try {
                    $url = $node->toUrl('canonical', ['absolute' => FALSE])->toString();
                } catch (\Exception $e) {
                }

                // Sector si existe
                $sector = '';
                if ($node->hasField('field_sector') && !$node->get('field_sector')->isEmpty()) {
                    $sector = $node->get('field_sector')->entity?->getName() ?? '';
                }

                $context .= "• **{$title}**";
                if ($sector) {
                    $context .= " (Sector: {$sector})";
                }
                $context .= " [Ver más]({$url})\n";
            }

            return $context . "\n";
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Busca productos del marketplace.
     */
    protected function searchProducts(array $keywords, int $limit = 10): string
    {
        try {
            // Buscar producto commerce si existe, o nodo product
            $productStorage = NULL;

            try {
                $productStorage = $this->entityTypeManager->getStorage('commerce_product');
            } catch (\Exception $e) {
                // Commerce no instalado, buscar nodo
                return $this->searchProductNodes($keywords, $limit);
            }

            $query = $productStorage->getQuery()
                ->accessCheck(TRUE)
                ->condition('status', 1)
                ->range(0, $limit)
                ->sort('created', 'DESC');

            if (!empty($keywords)) {
                $orGroup = $query->orConditionGroup();
                foreach ($keywords as $keyword) {
                    $orGroup->condition('title', $keyword, 'CONTAINS');
                }
                $query->condition($orGroup);
            }

            $pids = $query->execute();
            if (empty($pids)) {
                return '';
            }

            $products = $productStorage->loadMultiple($pids);
            $context = "🛒 PRODUCTOS DEL MARKETPLACE:\n";

            foreach ($products as $product) {
                $title = $product->getTitle();
                $url = '/product/' . $product->id();

                try {
                    $url = $product->toUrl('canonical', ['absolute' => FALSE])->toString();
                } catch (\Exception $e) {
                }

                // Precio si existe
                $price = '';
                $variations = $product->getVariations();
                if (!empty($variations)) {
                    $firstVariation = reset($variations);
                    if ($firstVariation && $firstVariation->hasField('price')) {
                        $priceField = $firstVariation->get('price')->first();
                        if ($priceField) {
                            $price = number_format((float) $priceField->number, 2) . '€';
                        }
                    }
                }

                $context .= "• **{$title}**";
                if ($price) {
                    $context .= ": {$price}";
                }
                $context .= " [Ver producto]({$url})\n";
            }

            return $context . "\n";
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Busca nodos de tipo product (fallback sin Commerce).
     */
    protected function searchProductNodes(array $keywords, int $limit = 10): string
    {
        try {
            $nodeStorage = $this->entityTypeManager->getStorage('node');

            $query = $nodeStorage->getQuery()
                ->accessCheck(TRUE)
                ->condition('type', 'product')
                ->condition('status', 1)
                ->range(0, $limit);

            $nids = $query->execute();
            if (empty($nids)) {
                return '';
            }

            $nodes = $nodeStorage->loadMultiple($nids);
            $context = "🛒 PRODUCTOS:\n";

            foreach ($nodes as $node) {
                $context .= "• **{$node->getTitle()}**\n";
            }

            return $context . "\n";
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Extrae palabras clave del mensaje del usuario.
     */
    protected function extractKeywords(string $message): array
    {
        // Palabras a ignorar
        $stopWords = [
            'el',
            'la',
            'los',
            'las',
            'un',
            'una',
            'unos',
            'unas',
            'de',
            'del',
            'al',
            'a',
            'en',
            'con',
            'por',
            'para',
            'que',
            'qué',
            'como',
            'cómo',
            'donde',
            'dónde',
            'quiero',
            'busco',
            'necesito',
            'ver',
            'hay',
            'tiene',
            'me',
            'te',
            'se',
            'nos',
            'os',
            'le',
            'les',
            'y',
            'o',
            'pero',
            'si',
            'no',
            'es',
            'son',
            'está',
        ];

        // Limpiar y tokenizar
        $message = mb_strtolower($message);
        $message = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $message);
        $words = preg_split('/\s+/', $message, -1, PREG_SPLIT_NO_EMPTY);

        // Filtrar stop words y palabras cortas
        $keywords = array_filter($words, function ($word) use ($stopWords) {
            return mb_strlen($word) > 2 && !in_array($word, $stopWords);
        });

        return array_values(array_unique($keywords));
    }

}
