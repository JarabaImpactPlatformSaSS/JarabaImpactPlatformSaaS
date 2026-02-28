---
description: >
  Scaffolding completo de ContentEntity Drupal 11 cumpliendo TODAS las reglas
  del proyecto: PremiumEntityFormBase, AccessControlHandler con tenant isolation,
  Views integration, Field UI, routing, preprocess hook, template.
  Uso: /create-entity <module_name> <entity_type_id> [--translatable] [--no-tenant]
argument-hint: "[module] [entity_id]"
allowed-tools: Read, Grep, Glob, Edit, Write, Bash(ls *), Bash(find *)
---

# /create-entity — Scaffolding Completo de ContentEntity

Genera una ContentEntity completa para el modulo `$ARGUMENTS[0]` con ID `$ARGUMENTS[1]`,
cumpliendo automaticamente 15+ directrices del proyecto.

## Analisis Previo

Antes de generar, verificar:
1. El modulo `$ARGUMENTS[0]` existe en `web/modules/custom/`
2. No existe ya una entidad con el mismo ID
3. El modulo tiene `.info.yml` y `.module` file

## Archivos a Generar (7 archivos)

### 1. Entity Class — `src/Entity/{EntityClass}.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\{module}\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the {Label} entity.
 *
 * @ContentEntityType(
 *   id = "{entity_type_id}",
 *   label = @Translation("{Label}"),
 *   label_collection = @Translation("{Label}s"),
 *   label_singular = @Translation("{label}"),
 *   label_plural = @Translation("{label}s"),
 *   label_count = @PluralTranslation(
 *     singular = "@count {label}",
 *     plural = "@count {label}s",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\{module}\{EntityClass}ListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\{module}\Access\{EntityClass}AccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\{module}\Form\{EntityClass}Form",
 *       "add" = "Drupal\{module}\Form\{EntityClass}Form",
 *       "edit" = "Drupal\{module}\Form\{EntityClass}Form",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "{entity_type_id}",
 *   [data_table = "{entity_type_id}_field_data",]  // Si translatable
 *   [translatable = TRUE,]                         // Si --translatable
 *   admin_permission = "administer {module}",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "label",
 *     "langcode" = "langcode",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/{entity-url}/{entity_type_id}",
 *     "add-form" = "/admin/content/{entity-url}/add",
 *     "edit-form" = "/admin/content/{entity-url}/{entity_type_id}/edit",
 *     "delete-form" = "/admin/content/{entity-url}/{entity_type_id}/delete",
 *     "collection" = "/admin/content/{entity-url}",
 *   },
 *   field_ui_base_route = "entity.{entity_type_id}.settings",
 * )
 */
class {EntityClass} extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityOwnerTrait;
  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Owner field (ENTITY-001).
    $fields += static::ownerBaseFieldDefinitions($entity_type);
    $fields['uid']
      ->setLabel(t('Author'))
      ->setDescription(t('The user who created this entity.'))
      ->setDefaultValueCallback(static::class . '::getDefaultEntityOwner');

    // Label.
    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Tenant ID (ENTITY-FK-001: entity_reference para tenant).
    // Omitir si --no-tenant.
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('The tenant this entity belongs to.'))
      ->setSetting('target_type', 'group')
      ->setRequired(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    // Status.
    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Published'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 90,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // Created/Changed timestamps.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
```

### 2. Form Class — `src/Form/{EntityClass}Form.php`

Extiende PremiumEntityFormBase (PREMIUM-FORMS-PATTERN-001):

```php
<?php

declare(strict_types=1);

namespace Drupal\{module}\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

class {EntityClass}Form extends PremiumEntityFormBase {

  protected function getSectionDefinitions(): array {
    return [
      'content' => [
        'label' => $this->t('Content'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Main content of the entity.'),
        'fields' => ['label'],
      ],
      'metadata' => [
        'label' => $this->t('Metadata'),
        'icon' => ['category' => 'ui', 'name' => 'info'],
        'description' => $this->t('Status and ownership.'),
        'fields' => ['uid', 'tenant_id', 'status'],
      ],
    ];
  }

  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'file'];
  }

  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->getEntity();
    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
```

### 3. Access Control Handler — `src/Access/{EntityClass}AccessControlHandler.php`

Con tenant isolation (TENANT-ISOLATION-ACCESS-001):

```php
<?php

declare(strict_types=1);

namespace Drupal\{module}\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class {EntityClass}AccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  public function __construct(
    EntityTypeInterface $entity_type,
    private readonly mixed $tenantContext = NULL,
  ) {
    parent::__construct($entity_type);
  }

  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): static {
    return new static(
      $entity_type,
      $container->has('ecosistema_jaraba_core.tenant_context')
        ? $container->get('ecosistema_jaraba_core.tenant_context')
        : NULL,
    );
  }

  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    $admin = AccessResult::allowedIfHasPermission($account, $this->entityType->getAdminPermission());
    if ($admin->isAllowed()) {
      return $admin;
    }

    // TENANT-ISOLATION-ACCESS-001: Tenant check for update/delete.
    if (in_array($operation, ['update', 'delete'], TRUE)) {
      if ($entity->hasField('tenant_id') && !$entity->get('tenant_id')->isEmpty()) {
        $entityTenantId = (int) $entity->get('tenant_id')->target_id;
        if ($this->tenantContext) {
          try {
            $currentTenantId = (int) $this->tenantContext->getCurrentTenantId();
            if ($entityTenantId !== $currentTenantId) {
              return AccessResult::forbidden('Cross-tenant access denied.')
                ->cachePerUser()
                ->addCacheableDependency($entity);
            }
          }
          catch (\Exception) {
            // PRESAVE-RESILIENCE-001: Don't block on service failure.
          }
        }
      }
    }

    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'access content')
        ->addCacheableDependency($entity),
      'update' => AccessResult::allowedIfHasPermission($account, 'administer {module}')
        ->addCacheableDependency($entity),
      'delete' => AccessResult::allowedIfHasPermission($account, 'administer {module}')
        ->addCacheableDependency($entity),
      default => AccessResult::neutral(),
    };
  }

}
```

### 4. List Builder — `src/{EntityClass}ListBuilder.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\{module};

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class {EntityClass}ListBuilder extends EntityListBuilder {

  public function buildHeader(): array {
    $header['label'] = $this->t('Label');
    $header['status'] = $this->t('Status');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity): array {
    $row['label'] = $entity->toLink();
    $row['status'] = $entity->get('status')->value ? $this->t('Published') : $this->t('Unpublished');
    $row['created'] = \Drupal::service('date.formatter')->format(
      $entity->get('created')->value,
      'short'
    );
    return $row + parent::buildRow($entity);
  }

}
```

### 5. Settings Form — `src/Form/{EntityClass}SettingsForm.php`

Requerido para field_ui_base_route (FIELD-UI-SETTINGS-TAB-001):

```php
<?php

declare(strict_types=1);

namespace Drupal\{module}\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class {EntityClass}SettingsForm extends FormBase {

  public function getFormId(): string {
    return '{entity_type_id}_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Use the tabs above to manage fields, form display, and view display for {Label} entities.') . '</p>',
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // No config to save — this form exists for Field UI tab navigation.
  }

}
```

### 6. Routing — Anadir a `{module}.routing.yml`

```yaml
entity.{entity_type_id}.settings:
  path: '/admin/structure/{entity-url}/settings'
  defaults:
    _form: '\Drupal\{module}\Form\{EntityClass}SettingsForm'
    _title: '{Label} Settings'
  requirements:
    _permission: 'administer {module}'
```

### 7. Menu Links — Anadir a `{module}.links.menu.yml`

```yaml
{module}.{entity_type_id}.admin:
  title: '{Label}s'
  description: 'Manage {label} entities.'
  route_name: entity.{entity_type_id}.collection
  parent: system.admin_content
  weight: 20

{module}.{entity_type_id}.structure:
  title: '{Label}s'
  description: 'Configure {label} entity fields and display.'
  route_name: entity.{entity_type_id}.settings
  parent: system.admin_structure
  weight: 20
```

### 8. Local Task Links — Anadir a `{module}.links.task.yml`

```yaml
entity.{entity_type_id}.settings:
  title: 'Settings'
  route_name: entity.{entity_type_id}.settings
  base_route: entity.{entity_type_id}.settings
```

### 9. Module File — Anadir preprocess a `{module}.module`

```php
/**
 * Implements hook_theme().
 */
function {module}_theme(): array {
  return [
    '{entity_type_id}' => [
      'render element' => 'elements',
    ],
  ];
}

/**
 * Prepares variables for {entity_type_id} templates.
 *
 * ENTITY-PREPROCESS-001.
 */
function template_preprocess_{entity_type_id}(array &$variables): void {
  $entity = $variables['elements']['#{entity_type_id}'] ?? NULL;
  if (!$entity) {
    return;
  }
  $variables['{entity_type_id}'] = $entity;
  $variables['label'] = $entity->label();
}
```

## Post-Generacion

1. **Ejecutar entity update**: `lando drush entity:updates` para crear tablas
2. **Cache rebuild**: `lando drush cr`
3. **Verificar admin**: Visitar `/admin/content/{entity-url}` y `/admin/structure/{entity-url}/settings`
4. **Field UI**: Verificar que las tabs "Manage fields" y "Manage display" funcionan
5. **Views**: Verificar que la entidad aparece como base table en Views

## Directrices Verificadas Automaticamente

| Directriz | Verificacion |
|-----------|-------------|
| PREMIUM-FORMS-PATTERN-001 | Form extiende PremiumEntityFormBase |
| AUDIT-CONS-001 | AccessControlHandler en anotacion |
| TENANT-ISOLATION-ACCESS-001 | Tenant check en checkAccess() |
| ENTITY-FK-001 | tenant_id como entity_reference a 'group' |
| ENTITY-001 | EntityOwnerInterface + EntityChangedInterface |
| ENTITY-PREPROCESS-001 | template_preprocess_{type}() en .module |
| FIELD-UI-SETTINGS-TAB-001 | Settings form + local task tab |
| LABEL-NULLSAFE-001 | Label en entity_keys |
| ACCESS-STRICT-001 | (int) === (int) en tenant check |
| PRESAVE-RESILIENCE-001 | try-catch en tenant context |
