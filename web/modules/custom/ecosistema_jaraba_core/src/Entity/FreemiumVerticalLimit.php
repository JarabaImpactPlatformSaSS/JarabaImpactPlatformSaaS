<?php

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Define la entidad de configuracion FreemiumVerticalLimit.
 *
 * Almacena los limites freemium especificos por vertical y plan SaaS.
 * Permite configurar desde la UI de Drupal que puede hacer un usuario
 * en cada vertical sin pagar, y a partir de que umbral se incentiva
 * el upgrade.
 *
 * Convencion de ID: {vertical}_{plan}_{feature_key}
 * Ejemplo: agroconecta_free_products
 *
 * @ConfigEntityType(
 *   id = "freemium_vertical_limit",
 *   label = @Translation("Freemium Vertical Limit"),
 *   label_collection = @Translation("Freemium Limits"),
 *   label_singular = @Translation("freemium vertical limit"),
 *   label_plural = @Translation("freemium vertical limits"),
 *   handlers = {
 *     "list_builder" = "Drupal\ecosistema_jaraba_core\FreemiumVerticalLimitListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ecosistema_jaraba_core\Form\FreemiumVerticalLimitForm",
 *       "edit" = "Drupal\ecosistema_jaraba_core\Form\FreemiumVerticalLimitForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "freemium_vertical_limit",
 *   admin_permission = "administer freemium limits",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "weight" = "weight",
 *     "status" = "status",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "vertical",
 *     "plan",
 *     "feature_key",
 *     "limit_value",
 *     "description",
 *     "upgrade_message",
 *     "expected_conversion",
 *     "weight",
 *     "status",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/freemium-limits",
 *     "add-form" = "/admin/structure/freemium-limits/add",
 *     "edit-form" = "/admin/structure/freemium-limits/{freemium_vertical_limit}/edit",
 *     "delete-form" = "/admin/structure/freemium-limits/{freemium_vertical_limit}/delete",
 *   },
 * )
 */
class FreemiumVerticalLimit extends ConfigEntityBase implements FreemiumVerticalLimitInterface
{

    /**
     * El ID del limite (machine name, ej: agroconecta_free_products).
     *
     * @var string
     */
    protected $id;

    /**
     * Nombre legible del limite.
     *
     * @var string
     */
    protected $label;

    /**
     * Machine name de la vertical.
     *
     * @var string
     */
    protected $vertical = '';

    /**
     * Machine name del plan.
     *
     * @var string
     */
    protected $plan = '';

    /**
     * Clave del recurso limitado.
     *
     * @var string
     */
    protected $feature_key = '';

    /**
     * Valor del limite (-1 = ilimitado, 0 = no incluido).
     *
     * @var int
     */
    protected $limit_value = 0;

    /**
     * Descripcion del limite para administradores.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Mensaje mostrado al usuario al alcanzar el limite.
     *
     * @var string
     */
    protected $upgrade_message = '';

    /**
     * Tasa de conversion esperada (0-1).
     *
     * @var float
     */
    protected $expected_conversion = 0.0;

    /**
     * Peso para ordenacion.
     *
     * @var int
     */
    protected $weight = 0;

    /**
     * {@inheritdoc}
     */
    public function getVertical(): string
    {
        return $this->vertical ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setVertical(string $vertical): FreemiumVerticalLimitInterface
    {
        $this->vertical = $vertical;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPlan(): string
    {
        return $this->plan ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setPlan(string $plan): FreemiumVerticalLimitInterface
    {
        $this->plan = $plan;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFeatureKey(): string
    {
        return $this->feature_key ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setFeatureKey(string $feature_key): FreemiumVerticalLimitInterface
    {
        $this->feature_key = $feature_key;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLimitValue(): int
    {
        return (int) ($this->limit_value ?? 0);
    }

    /**
     * {@inheritdoc}
     */
    public function setLimitValue(int $limit_value): FreemiumVerticalLimitInterface
    {
        $this->limit_value = $limit_value;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setDescription(string $description): FreemiumVerticalLimitInterface
    {
        $this->description = $description;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getUpgradeMessage(): string
    {
        return $this->upgrade_message ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setUpgradeMessage(string $upgrade_message): FreemiumVerticalLimitInterface
    {
        $this->upgrade_message = $upgrade_message;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpectedConversion(): float
    {
        return (float) ($this->expected_conversion ?? 0.0);
    }

    /**
     * {@inheritdoc}
     */
    public function setExpectedConversion(float $expected_conversion): FreemiumVerticalLimitInterface
    {
        $this->expected_conversion = $expected_conversion;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getWeight(): int
    {
        return $this->weight ?? 0;
    }

    /**
     * {@inheritdoc}
     */
    public function setWeight(int $weight): FreemiumVerticalLimitInterface
    {
        $this->weight = $weight;
        return $this;
    }

}
