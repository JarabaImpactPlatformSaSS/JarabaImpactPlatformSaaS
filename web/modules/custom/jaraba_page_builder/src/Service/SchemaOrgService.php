<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Service;

use Drupal\Component\Serialization\Json;

/**
 * Servicio para generación de Schema.org JSON-LD.
 *
 * DIRECTRICES:
 * - Genera structured data válido para Google Rich Results
 * - Soporta FAQPage, BreadcrumbList, y schemas por vertical
 * - Los schemas se inyectan automáticamente en templates Twig
 *
 * @package Drupal\jaraba_page_builder\Service
 */
class SchemaOrgService
{

    /**
     * Genera schema FAQPage para Rich Snippets en Google.
     *
     * @param array $items
     *   Array de items FAQ con 'question' y 'answer'.
     *
     * @return string
     *   JSON-LD válido para FAQPage.
     *
     * @see https://schema.org/FAQPage
     * @see https://developers.google.com/search/docs/appearance/structured-data/faqpage
     */
    public function generateFAQSchema(array $items): string
    {
        if (empty($items)) {
            return '';
        }

        $mainEntity = [];
        foreach ($items as $item) {
            if (empty($item['question']) || empty($item['answer'])) {
                continue;
            }

            $mainEntity[] = [
                '@type' => 'Question',
                'name' => strip_tags($item['question']),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => strip_tags($item['answer']),
                ],
            ];
        }

        if (empty($mainEntity)) {
            return '';
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $mainEntity,
        ];

        return Json::encode($schema);
    }

    /**
     * Genera schema BreadcrumbList para navegación.
     *
     * @param array $items
     *   Array de items breadcrumb con 'title' y 'url'.
     *
     * @return string
     *   JSON-LD válido para BreadcrumbList.
     *
     * @see https://schema.org/BreadcrumbList
     */
    public function generateBreadcrumbSchema(array $items): string
    {
        if (empty($items)) {
            return '';
        }

        $itemListElement = [];
        $position = 1;
        foreach ($items as $item) {
            if (empty($item['title']) || empty($item['url'])) {
                continue;
            }

            $itemListElement[] = [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $item['title'],
                'item' => $item['url'],
            ];
            $position++;
        }

        if (empty($itemListElement)) {
            return '';
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $itemListElement,
        ];

        return Json::encode($schema);
    }

    /**
     * Genera schema JobPosting para ofertas de empleo.
     *
     * @param array $job
     *   Datos de la oferta de empleo.
     * @param array $tenant
     *   Datos del tenant/organización.
     *
     * @return string
     *   JSON-LD válido para JobPosting.
     *
     * @see https://schema.org/JobPosting
     * @see https://developers.google.com/search/docs/appearance/structured-data/job-posting
     */
    public function generateJobPostingSchema(array $job, array $tenant = []): string
    {
        if (empty($job['title'])) {
            return '';
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'JobPosting',
            'title' => $job['title'],
            'description' => strip_tags($job['description'] ?? ''),
            'datePosted' => $job['date_posted'] ?? date('Y-m-d'),
            'validThrough' => $job['valid_through'] ?? date('Y-m-d\TH:i:sP', strtotime('+30 days')),
            'employmentType' => strtoupper($job['employment_type'] ?? 'FULL_TIME'),
            'hiringOrganization' => [
                '@type' => 'Organization',
                'name' => $tenant['name'] ?? $job['company_name'] ?? '',
                'sameAs' => $tenant['url'] ?? $job['company_url'] ?? '',
                'logo' => $tenant['logo'] ?? $job['company_logo'] ?? '',
            ],
            'directApply' => TRUE,
        ];

        // Ubicación del trabajo.
        if (!empty($job['city'])) {
            $schema['jobLocation'] = [
                '@type' => 'Place',
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => $job['address'] ?? '',
                    'addressLocality' => $job['city'],
                    'addressRegion' => $job['region'] ?? '',
                    'postalCode' => $job['postal_code'] ?? '',
                    'addressCountry' => 'ES',
                ],
            ];
        }

        // Trabajo remoto.
        if (!empty($job['remote_work'])) {
            $schema['jobLocationType'] = 'TELECOMMUTE';
            $schema['applicantLocationRequirements'] = [
                '@type' => 'Country',
                'name' => 'Spain',
            ];
        }

        // Salario.
        if (!empty($job['salary_min']) && !empty($job['salary_max'])) {
            $schema['baseSalary'] = [
                '@type' => 'MonetaryAmount',
                'currency' => 'EUR',
                'value' => [
                    '@type' => 'QuantitativeValue',
                    'minValue' => (int) $job['salary_min'],
                    'maxValue' => (int) $job['salary_max'],
                    'unitText' => strtoupper($job['salary_period'] ?? 'YEAR'),
                ],
            ];
        }

        // Skills y requisitos.
        if (!empty($job['skills'])) {
            $schema['skills'] = is_array($job['skills']) ? implode(', ', $job['skills']) : $job['skills'];
        }
        if (!empty($job['requirements'])) {
            $schema['qualifications'] = $job['requirements'];
        }
        if (!empty($job['responsibilities'])) {
            $schema['responsibilities'] = $job['responsibilities'];
        }
        if (!empty($job['industry'])) {
            $schema['industry'] = $job['industry'];
        }

        return Json::encode($schema);
    }

    /**
     * Genera schema Course para formación.
     *
     * @param array $course
     *   Datos del curso.
     * @param array $tenant
     *   Datos del tenant/organización.
     *
     * @return string
     *   JSON-LD válido para Course.
     *
     * @see https://schema.org/Course
     */
    public function generateCourseSchema(array $course, array $tenant = []): string
    {
        if (empty($course['title'])) {
            return '';
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Course',
            'name' => $course['title'],
            'description' => strip_tags($course['description'] ?? ''),
            'provider' => [
                '@type' => 'Organization',
                'name' => $tenant['name'] ?? '',
                'sameAs' => $tenant['url'] ?? '',
            ],
        ];

        // Precio.
        if (isset($course['price'])) {
            $schema['offers'] = [
                '@type' => 'Offer',
                'price' => (string) $course['price'],
                'priceCurrency' => 'EUR',
                'availability' => 'https://schema.org/InStock',
            ];
        }

        // Instancia del curso.
        if (!empty($course['start_date'])) {
            $schema['hasCourseInstance'] = [
                '@type' => 'CourseInstance',
                'courseMode' => $course['mode'] ?? 'online',
                'startDate' => $course['start_date'],
                'endDate' => $course['end_date'] ?? '',
            ];

            if (!empty($course['duration_hours'])) {
                $schema['hasCourseInstance']['courseWorkload'] = 'PT' . $course['duration_hours'] . 'H';
            }

            if (!empty($course['instructor_name'])) {
                $schema['hasCourseInstance']['instructor'] = [
                    '@type' => 'Person',
                    'name' => $course['instructor_name'],
                ];
            }
        }

        // Nivel educativo.
        if (!empty($course['level'])) {
            $schema['educationalLevel'] = $course['level'];
        }

        // Habilidades enseñadas.
        if (!empty($course['skills'])) {
            $schema['teaches'] = is_array($course['skills']) ? implode(', ', $course['skills']) : $course['skills'];
        }

        // Rating agregado.
        if (!empty($course['rating']) && !empty($course['review_count'])) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (string) $course['rating'],
                'reviewCount' => (string) $course['review_count'],
            ];
        }

        return Json::encode($schema);
    }

    /**
     * Genera schema LocalBusiness para negocios locales.
     *
     * @param array $business
     *   Datos del negocio.
     *
     * @return string
     *   JSON-LD válido para LocalBusiness.
     *
     * @see https://schema.org/LocalBusiness
     */
    public function generateLocalBusinessSchema(array $business): string
    {
        if (empty($business['name'])) {
            return '';
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $business['type'] ?? 'LocalBusiness',
            'name' => $business['name'],
            'description' => strip_tags($business['description'] ?? ''),
            'url' => $business['website'] ?? '',
            'telephone' => $business['phone'] ?? '',
            'email' => $business['email'] ?? '',
        ];

        // Logo.
        if (!empty($business['logo'])) {
            $schema['image'] = $business['logo'];
            $schema['logo'] = $business['logo'];
        }

        // Rango de precios.
        if (!empty($business['price_range'])) {
            $schema['priceRange'] = $business['price_range'];
        }

        // Dirección.
        if (!empty($business['address'])) {
            $schema['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $business['address'],
                'addressLocality' => $business['city'] ?? '',
                'addressRegion' => $business['region'] ?? '',
                'postalCode' => $business['postal_code'] ?? '',
                'addressCountry' => 'ES',
            ];
        }

        // Geo coordenadas.
        if (!empty($business['latitude']) && !empty($business['longitude'])) {
            $schema['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $business['latitude'],
                'longitude' => (float) $business['longitude'],
            ];
            $schema['hasMap'] = 'https://www.google.com/maps?q=' . $business['latitude'] . ',' . $business['longitude'];
        }

        // Horarios de apertura.
        if (!empty($business['opening_hours'])) {
            $schema['openingHoursSpecification'] = [];
            foreach ($business['opening_hours'] as $hours) {
                if (!empty($hours['day']) && !empty($hours['opens']) && !empty($hours['closes'])) {
                    $schema['openingHoursSpecification'][] = [
                        '@type' => 'OpeningHoursSpecification',
                        'dayOfWeek' => $hours['day'],
                        'opens' => $hours['opens'],
                        'closes' => $hours['closes'],
                    ];
                }
            }
        }

        // Rating agregado.
        if (!empty($business['rating']) && !empty($business['review_count'])) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (string) $business['rating'],
                'reviewCount' => (string) $business['review_count'],
            ];
        }

        // Redes sociales.
        if (!empty($business['social_links'])) {
            $schema['sameAs'] = $business['social_links'];
        }

        return Json::encode($schema);
    }

    /**
     * Genera schema Product para productos de comercio.
     *
     * @param array $product
     *   Datos del producto.
     * @param array $tenant
     *   Datos del tenant/vendedor.
     *
     * @return string
     *   JSON-LD válido para Product.
     *
     * @see https://schema.org/Product
     * @see https://developers.google.com/search/docs/appearance/structured-data/product
     */
    public function generateProductSchema(array $product, array $tenant = []): string
    {
        if (empty($product['name'])) {
            return '';
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product['name'],
            'description' => strip_tags($product['description'] ?? ''),
        ];

        // Imagen del producto.
        if (!empty($product['image'])) {
            $schema['image'] = $product['image'];
        }

        // SKU.
        if (!empty($product['sku'])) {
            $schema['sku'] = $product['sku'];
        }

        // Marca/Brand.
        if (!empty($product['brand'])) {
            $schema['brand'] = [
                '@type' => 'Brand',
                'name' => $product['brand'],
            ];
        } elseif (!empty($tenant['name'])) {
            $schema['brand'] = [
                '@type' => 'Brand',
                'name' => $tenant['name'],
            ];
        }

        // Categoría.
        if (!empty($product['category'])) {
            $schema['category'] = $product['category'];
        }

        // Oferta/Precio.
        if (!empty($product['price'])) {
            $offer = [
                '@type' => 'Offer',
                'price' => (string) $product['price'],
                'priceCurrency' => $product['currency'] ?? 'EUR',
                'availability' => $product['in_stock'] ?? TRUE
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
            ];

            // Precio válido hasta.
            if (!empty($product['price_valid_until'])) {
                $offer['priceValidUntil'] = $product['price_valid_until'];
            }

            // URL del producto.
            if (!empty($product['url'])) {
                $offer['url'] = $product['url'];
            }

            // Vendedor.
            if (!empty($tenant['name'])) {
                $offer['seller'] = [
                    '@type' => 'Organization',
                    'name' => $tenant['name'],
                ];
            }

            // Condición del producto.
            $offer['itemCondition'] = $product['condition'] === 'used'
                ? 'https://schema.org/UsedCondition'
                : 'https://schema.org/NewCondition';

            $schema['offers'] = $offer;
        }

        // Rating agregado.
        if (!empty($product['rating']) && !empty($product['review_count'])) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (string) $product['rating'],
                'reviewCount' => (string) $product['review_count'],
            ];
        }

        // Especificaciones adicionales.
        if (!empty($product['weight'])) {
            $schema['weight'] = [
                '@type' => 'QuantitativeValue',
                'value' => $product['weight'],
                'unitCode' => $product['weight_unit'] ?? 'KGM',
            ];
        }

        // Material (para productos físicos).
        if (!empty($product['material'])) {
            $schema['material'] = $product['material'];
        }

        // Color.
        if (!empty($product['color'])) {
            $schema['color'] = $product['color'];
        }

        // GTIN/EAN.
        if (!empty($product['gtin'])) {
            $schema['gtin13'] = $product['gtin'];
        }

        return Json::encode($schema);
    }

    /**
     * GAP-AUD-006: Genera schema LocalBusiness con GEO enriquecido.
     *
     * Añade areaServed (multi-region) y hasMap a negocios locales.
     *
     * @param array $business
     *   Datos del negocio.
     * @param array $areaServed
     *   Regiones servidas (array de strings: nombres de regiones/países).
     * @param string|null $mapUrl
     *   URL de Google Maps opcional.
     *
     * @return string
     *   JSON-LD válido para LocalBusiness con GEO enriquecido.
     */
    public function generateLocalBusinessGeoSchema(array $business, array $areaServed = [], ?string $mapUrl = NULL): string
    {
        $baseJson = $this->generateLocalBusinessSchema($business);
        if (empty($baseJson)) {
            return '';
        }

        $schema = Json::decode($baseJson);

        // GAP-AUD-006: Multi-region areaServed.
        if (!empty($areaServed)) {
            if (count($areaServed) === 1) {
                $schema['areaServed'] = [
                    '@type' => 'AdministrativeArea',
                    'name' => $areaServed[0],
                ];
            }
            else {
                $schema['areaServed'] = array_map(function (string $region) {
                    return [
                        '@type' => 'AdministrativeArea',
                        'name' => $region,
                    ];
                }, $areaServed);
            }
        }

        // GAP-AUD-006: Google Maps link.
        if (!empty($mapUrl)) {
            $schema['hasMap'] = $mapUrl;
        }

        return Json::encode($schema);
    }

    /**
     * GAP-AUD-006: Genera schema HowTo para tutoriales LMS.
     *
     * @param array $howTo
     *   Datos del tutorial con 'title', 'description', 'steps'.
     *   Cada step: ['name' => string, 'text' => string, 'image' => string|null].
     * @param array $tenant
     *   Datos del tenant/organización.
     *
     * @return string
     *   JSON-LD válido para HowTo.
     *
     * @see https://schema.org/HowTo
     * @see https://developers.google.com/search/docs/appearance/structured-data/how-to
     */
    public function generateHowToSchema(array $howTo, array $tenant = []): string
    {
        if (empty($howTo['title']) || empty($howTo['steps'])) {
            return '';
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'HowTo',
            'name' => $howTo['title'],
            'description' => strip_tags($howTo['description'] ?? ''),
        ];

        // Estimated total time.
        if (!empty($howTo['total_time_minutes'])) {
            $schema['totalTime'] = 'PT' . (int) $howTo['total_time_minutes'] . 'M';
        }

        // Steps.
        $steps = [];
        $position = 1;
        foreach ($howTo['steps'] as $step) {
            if (empty($step['name']) || empty($step['text'])) {
                continue;
            }

            $howToStep = [
                '@type' => 'HowToStep',
                'position' => $position,
                'name' => strip_tags($step['name']),
                'text' => strip_tags($step['text']),
            ];

            if (!empty($step['image'])) {
                $howToStep['image'] = $step['image'];
            }

            if (!empty($step['url'])) {
                $howToStep['url'] = $step['url'];
            }

            $steps[] = $howToStep;
            $position++;
        }

        if (empty($steps)) {
            return '';
        }

        $schema['step'] = $steps;

        // Supply (materials needed).
        if (!empty($howTo['supplies'])) {
            $schema['supply'] = array_map(function (string $supply) {
                return [
                    '@type' => 'HowToSupply',
                    'name' => strip_tags($supply),
                ];
            }, $howTo['supplies']);
        }

        // Tool (tools needed).
        if (!empty($howTo['tools'])) {
            $schema['tool'] = array_map(function (string $tool) {
                return [
                    '@type' => 'HowToTool',
                    'name' => strip_tags($tool),
                ];
            }, $howTo['tools']);
        }

        // Estimated cost.
        if (isset($howTo['estimated_cost'])) {
            $schema['estimatedCost'] = [
                '@type' => 'MonetaryAmount',
                'currency' => 'EUR',
                'value' => (string) $howTo['estimated_cost'],
            ];
        }

        // Provider organization.
        if (!empty($tenant['name'])) {
            $schema['author'] = [
                '@type' => 'Organization',
                'name' => $tenant['name'],
            ];
            if (!empty($tenant['url'])) {
                $schema['author']['url'] = $tenant['url'];
            }
        }

        return Json::encode($schema);
    }

    /**
     * Envuelve JSON-LD en script tag para insertar en HTML.
     *
     * @param string $jsonLd
     *   JSON-LD generado por cualquier método generate*.
     *
     * @return string
     *   Script tag con JSON-LD listo para insertar.
     */
    public function wrapInScriptTag(string $jsonLd): string
    {
        if (empty($jsonLd)) {
            return '';
        }

        return '<script type="application/ld+json">' . $jsonLd . '</script>';
    }

}
