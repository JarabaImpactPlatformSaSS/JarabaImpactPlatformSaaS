<?php

namespace Drupal\jaraba_page_builder\Service;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Servicio para generar formularios dinámicos desde JSON Schema.
 *
 * PROPÓSITO:
 * Transforma el `fields_schema` de una PageTemplate en elementos de
 * formulario Drupal Form API. Esto permite que las plantillas definan
 * qué campos son editables sin código PHP adicional.
 *
 * ESTÁNDARES:
 * - Soporta tipos: string, text, url, email, number, boolean, select, image, repeater
 * - Valida según JSON Schema (required, minLength, maxLength, pattern)
 * - Genera campos traducibles cuando corresponde
 */
class FormBuilderService
{

    use StringTranslationTrait;

    /**
     * Tipos de campo soportados y su mapeo a Form API.
     */
    protected const FIELD_TYPE_MAP = [
        'string' => 'textfield',
        'text' => 'textarea',
        'url' => 'url',
        'email' => 'email',
        'number' => 'number',
        'boolean' => 'checkbox',
        'select' => 'select',
        'image' => 'managed_file',
        'color' => 'color',
        'date' => 'date',
    ];

    /**
     * Genera un formulario desde un JSON Schema.
     *
     * @param array $schema
     *   El JSON Schema de la plantilla (fields_schema).
     * @param array $values
     *   Valores actuales de los campos (para edición).
     *
     * @return array
     *   Elementos de formulario Form API.
     */
    public function buildForm(array $schema, array $values = []): array
    {
        $form = [];

        if (!isset($schema['properties']) || !is_array($schema['properties'])) {
            return $form;
        }

        $required_fields = $schema['required'] ?? [];

        foreach ($schema['properties'] as $field_name => $field_schema) {
            $form[$field_name] = $this->buildField(
                $field_name,
                $field_schema,
                $values[$field_name] ?? NULL,
                in_array($field_name, $required_fields, TRUE)
            );
        }

        return $form;
    }

    /**
     * Construye un campo individual desde su definición de schema.
     *
     * @param string $field_name
     *   Nombre del campo.
     * @param array $field_schema
     *   Definición del campo en JSON Schema.
     * @param mixed $value
     *   Valor actual del campo.
     * @param bool $required
     *   Si el campo es requerido.
     *
     * @return array
     *   Elemento de formulario Form API.
     */
    protected function buildField(string $field_name, array $field_schema, $value, bool $required): array
    {
        $type = $field_schema['type'] ?? 'string';
        $form_type = self::FIELD_TYPE_MAP[$type] ?? 'textfield';

        // Manejar tipos especiales.
        if ($type === 'array' && isset($field_schema['items'])) {
            return $this->buildRepeaterField($field_name, $field_schema, $value);
        }

        if ($type === 'object' && isset($field_schema['properties'])) {
            return $this->buildGroupField($field_name, $field_schema, $value);
        }

        $element = [
            '#type' => $form_type,
            '#title' => $field_schema['title'] ?? $this->humanizeFieldName($field_name),
            '#description' => $field_schema['description'] ?? '',
            '#required' => $required,
            '#default_value' => $value ?? ($field_schema['default'] ?? NULL),
        ];

        // Propiedades específicas por tipo.
        switch ($type) {
            case 'string':
                if (isset($field_schema['maxLength'])) {
                    $element['#maxlength'] = $field_schema['maxLength'];
                }
                break;

            case 'text':
                $element['#rows'] = $field_schema['rows'] ?? 4;
                break;

            case 'number':
                if (isset($field_schema['minimum'])) {
                    $element['#min'] = $field_schema['minimum'];
                }
                if (isset($field_schema['maximum'])) {
                    $element['#max'] = $field_schema['maximum'];
                }
                if (isset($field_schema['step'])) {
                    $element['#step'] = $field_schema['step'];
                }
                break;

            case 'select':
                $element['#options'] = $this->buildSelectOptions($field_schema);
                $element['#empty_option'] = $this->t('- Select -');
                break;

            case 'image':
                $element['#upload_location'] = 'public://page-builder/images';
                $element['#upload_validators'] = [
                    'file_validate_extensions' => ['png jpg jpeg gif webp svg'],
                    'file_validate_size' => [5 * 1024 * 1024], // 5MB
                ];
                break;

            case 'color':
                // Color picker.
                break;
        }

        // Placeholder desde schema.
        if (isset($field_schema['placeholder'])) {
            $element['#placeholder'] = $field_schema['placeholder'];
        }

        // Patrón de validación.
        if (isset($field_schema['pattern'])) {
            $element['#pattern'] = $field_schema['pattern'];
        }

        // Prefijo/sufijo para UI.
        if (isset($field_schema['prefix'])) {
            $element['#field_prefix'] = $field_schema['prefix'];
        }
        if (isset($field_schema['suffix'])) {
            $element['#field_suffix'] = $field_schema['suffix'];
        }

        return $element;
    }

    /**
     * Construye un campo de grupo (object con properties).
     *
     * @param string $field_name
     *   Nombre del campo.
     * @param array $field_schema
     *   Schema del grupo.
     * @param mixed $value
     *   Valores actuales.
     *
     * @return array
     *   Elemento details con subcampos.
     */
    protected function buildGroupField(string $field_name, array $field_schema, $value): array
    {
        $values = is_array($value) ? $value : [];
        $required_fields = $field_schema['required'] ?? [];

        $element = [
            '#type' => 'details',
            '#title' => $field_schema['title'] ?? $this->humanizeFieldName($field_name),
            '#description' => $field_schema['description'] ?? '',
            '#open' => $field_schema['open'] ?? TRUE,
        ];

        foreach ($field_schema['properties'] as $sub_name => $sub_schema) {
            $element[$sub_name] = $this->buildField(
                $sub_name,
                $sub_schema,
                $values[$sub_name] ?? NULL,
                in_array($sub_name, $required_fields, TRUE)
            );
        }

        return $element;
    }

    /**
     * Construye un campo repetidor (array de items).
     *
     * @param string $field_name
     *   Nombre del campo.
     * @param array $field_schema
     *   Schema del array.
     * @param mixed $value
     *   Valores actuales (array de items).
     *
     * @return array
     *   Elemento fieldset con items dinámicos.
     */
    protected function buildRepeaterField(string $field_name, array $field_schema, $value): array
    {
        $items = is_array($value) ? $value : [];
        $min_items = $field_schema['minItems'] ?? 0;
        $max_items = $field_schema['maxItems'] ?? 10;
        $item_schema = $field_schema['items'] ?? [];

        $element = [
            '#type' => 'fieldset',
            '#title' => $field_schema['title'] ?? $this->humanizeFieldName($field_name),
            '#description' => $field_schema['description'] ?? '',
            '#prefix' => '<div id="' . $field_name . '-wrapper" class="repeater-wrapper">',
            '#suffix' => '</div>',
            '#tree' => TRUE,
            '#attributes' => [
                'class' => ['repeater-field'],
                'data-min-items' => $min_items,
                'data-max-items' => $max_items,
            ],
        ];

        // Generar items existentes o mínimos.
        $items_count = max(count($items), $min_items, 1);

        for ($i = 0; $i < $items_count; $i++) {
            $item_value = $items[$i] ?? [];
            $element['items'][$i] = $this->buildRepeaterItem($i, $item_schema, $item_value);
        }

        // Botón para añadir más items.
        $element['add_more'] = [
            '#type' => 'button',
            '#value' => $this->t('Add @title', ['@title' => $field_schema['itemLabel'] ?? 'item']),
            '#name' => $field_name . '_add',
            '#attributes' => [
                'class' => ['repeater-add-btn', 'button--small'],
            ],
            '#ajax' => [
                'callback' => '::ajaxAddRepeaterItem',
                'wrapper' => $field_name . '-wrapper',
            ],
        ];

        return $element;
    }

    /**
     * Construye un item individual del repeater.
     *
     * @param int $delta
     *   Índice del item.
     * @param array $item_schema
     *   Schema del item.
     * @param array $value
     *   Valores del item.
     *
     * @return array
     *   Elemento del item.
     */
    protected function buildRepeaterItem(int $delta, array $item_schema, array $value): array
    {
        $item = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['repeater-item'],
                'data-delta' => $delta,
            ],
        ];

        // Si el item es un objeto, generar sus propiedades.
        if (($item_schema['type'] ?? 'object') === 'object' && isset($item_schema['properties'])) {
            $required = $item_schema['required'] ?? [];
            foreach ($item_schema['properties'] as $prop_name => $prop_schema) {
                $item[$prop_name] = $this->buildField(
                    $prop_name,
                    $prop_schema,
                    $value[$prop_name] ?? NULL,
                    in_array($prop_name, $required, TRUE)
                );
            }
        } else {
            // Item simple (string, number, etc.)
            $item['value'] = $this->buildField('value', $item_schema, $value, FALSE);
        }

        // Botón de eliminar item.
        $item['remove'] = [
            '#type' => 'button',
            '#value' => $this->t('Remove'),
            '#name' => 'remove_' . $delta,
            '#attributes' => [
                'class' => ['repeater-remove-btn', 'button--danger', 'button--small'],
            ],
        ];

        return $item;
    }

    /**
     * Construye opciones para un campo select.
     *
     * @param array $field_schema
     *   Schema del campo.
     *
     * @return array
     *   Opciones para el select.
     */
    protected function buildSelectOptions(array $field_schema): array
    {
        // Desde enum.
        if (isset($field_schema['enum'])) {
            $labels = $field_schema['enumLabels'] ?? [];
            $options = [];
            foreach ($field_schema['enum'] as $index => $value) {
                $options[$value] = $labels[$index] ?? $value;
            }
            return $options;
        }

        // Desde oneOf.
        if (isset($field_schema['oneOf'])) {
            $options = [];
            foreach ($field_schema['oneOf'] as $option) {
                $options[$option['const'] ?? $option['value'] ?? ''] = $option['title'] ?? $option['label'] ?? '';
            }
            return $options;
        }

        return [];
    }

    /**
     * Humaniza un nombre de campo (snake_case a Title Case).
     *
     * @param string $field_name
     *   Nombre del campo.
     *
     * @return string
     *   Nombre humanizado.
     */
    protected function humanizeFieldName(string $field_name): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $field_name));
    }

    /**
     * Extrae los valores del formulario según el schema.
     *
     * @param array $schema
     *   El JSON Schema.
     * @param array $form_values
     *   Valores enviados en el formulario.
     *
     * @return array
     *   Valores procesados para guardar.
     */
    public function extractValues(array $schema, array $form_values): array
    {
        $values = [];

        if (!isset($schema['properties'])) {
            return $values;
        }

        foreach ($schema['properties'] as $field_name => $field_schema) {
            $type = $field_schema['type'] ?? 'string';

            if (!isset($form_values[$field_name])) {
                continue;
            }

            $raw_value = $form_values[$field_name];

            // Procesar según tipo.
            switch ($type) {
                case 'boolean':
                    $values[$field_name] = (bool) $raw_value;
                    break;

                case 'number':
                    $values[$field_name] = is_numeric($raw_value) ? (float) $raw_value : 0;
                    break;

                case 'array':
                    $values[$field_name] = $this->extractRepeaterValues($field_schema, $raw_value);
                    break;

                case 'object':
                    $values[$field_name] = $this->extractValues($field_schema, $raw_value);
                    break;

                case 'image':
                    // Manejar archivos subidos.
                    $values[$field_name] = is_array($raw_value) ? reset($raw_value) : $raw_value;
                    break;

                default:
                    $values[$field_name] = $raw_value;
            }
        }

        return $values;
    }

    /**
     * Extrae valores de un campo repeater.
     *
     * @param array $field_schema
     *   Schema del campo.
     * @param mixed $raw_value
     *   Valor crudo del formulario.
     *
     * @return array
     *   Items procesados.
     */
    protected function extractRepeaterValues(array $field_schema, $raw_value): array
    {
        if (!is_array($raw_value) || !isset($raw_value['items'])) {
            return [];
        }

        $items = [];
        $item_schema = $field_schema['items'] ?? [];

        foreach ($raw_value['items'] as $item) {
            if (($item_schema['type'] ?? 'object') === 'object') {
                $items[] = $this->extractValues($item_schema, $item);
            } else {
                $items[] = $item['value'] ?? $item;
            }
        }

        return $items;
    }

}
