# Content Entities en Drupal - Implementación CandidateSkill

**Fecha:** 2026-01-25  
**Categoría:** Arquitectura Drupal

---

## Resumen

Documentación del patrón de implementación de Content Entities en Drupal 11, con el caso práctico de `CandidateSkill` para gestionar habilidades de candidatos.

---

## ¿Cuándo usar Content Entity vs Config Entity?

| Tipo | Uso | Ejemplos |
|------|-----|----------|
| **Content Entity** | Datos de negocio editables por usuarios | Skills, Perfiles, Productos |
| **Config Entity** | Configuración técnica exportable | Features, AI Agents, Permisos |

**Regla:** Si necesitas Field UI, Views, o Entity Reference → **Content Entity**.

---

## Estructura Mínima de Content Entity

```
src/Entity/
├── CandidateSkill.php           # Entidad principal con anotaciones
└── CandidateSkillInterface.php  # Interface (opcional pero recomendado)

src/
├── CandidateSkillListBuilder.php
├── CandidateSkillAccessControlHandler.php
└── Form/
    ├── CandidateSkillForm.php
    └── CandidateSkillSettingsForm.php
```

---

## Anotaciones Clave (@ContentEntityType)

```php
/**
 * @ContentEntityType(
 *   id = "candidate_skill",
 *   label = @Translation("Candidate Skill"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_candidate\CandidateSkillListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",  // ✅ Habilita Views
 *     "form" = {
 *       "default" = "...\CandidateSkillForm",
 *       "add" = "...\CandidateSkillForm",
 *       "edit" = "...\CandidateSkillForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "...\CandidateSkillAccessControlHandler",
 *   },
 *   base_table = "candidate_skill",
 *   admin_permission = "administer candidate skills",
 *   fieldable = TRUE,  // ✅ Habilita Field UI
 *   field_ui_base_route = "entity.candidate_skill.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/candidate-skill/{candidate_skill}",
 *     "add-form" = "/admin/content/candidate-skill/add",
 *     "edit-form" = "/admin/content/candidate-skill/{candidate_skill}/edit",
 *     "delete-form" = "/admin/content/candidate-skill/{candidate_skill}/delete",
 *     "collection" = "/admin/content/candidate-skills",
 *   },
 * )
 */
```

---

## Base Fields vs Field UI

| Tipo | Definición | Ejemplo |
|------|------------|---------|
| **Base Field** | En código (`baseFieldDefinitions()`) | `id`, `user_id`, `skill_id` |
| **Field UI** | Desde admin | Campos adicionales configurables |

```php
public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
  $fields = parent::baseFieldDefinitions($entity_type);
  
  // Entity Reference al usuario
  $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('User'))
    ->setSetting('target_type', 'user')
    ->setRequired(TRUE);
  
  // Entity Reference a taxonomía
  $fields['skill_id'] = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Skill'))
    ->setSetting('target_type', 'taxonomy_term')
    ->setSetting('handler_settings', ['target_bundles' => ['skills' => 'skills']]);
  
  // Campo de lista (opciones)
  $fields['level'] = BaseFieldDefinition::create('list_string')
    ->setLabel(t('Level'))
    ->setSetting('allowed_values', [
      'beginner' => 'Principiante',
      'intermediate' => 'Intermedio',
      'advanced' => 'Avanzado',
      'expert' => 'Experto',
    ])
    ->setDefaultValue('intermediate');
    
  return $fields;
}
```

---

## Archivos de Configuración Necesarios

### routing.yml
```yaml
entity.candidate_skill.collection:
  path: '/admin/content/candidate-skills'
  defaults:
    _entity_list: 'candidate_skill'
    _title: 'Candidate Skills'
  requirements:
    _permission: 'administer candidate skills'
```

### links.menu.yml
```yaml
entity.candidate_skill.collection:
  title: 'Candidate Skills'
  route_name: entity.candidate_skill.collection
  parent: system.admin_content
```

### permissions.yml
```yaml
administer candidate skills:
  title: 'Administer Candidate Skills'
```

---

## Lecciones Aprendidas

1. **Siempre usar `fieldable = TRUE`** si quieres que admins añadan campos
2. **Definir `views_data` handler** para integración con Views
3. **Entity Reference** permite relaciones potentes (user, taxonomy, otras entidades)
4. **`field_ui_base_route`** es necesario para que Field UI sepa dónde mostrar la config
5. **Permisos granulares** vía AccessControlHandler para operaciones CRUD

---

## Comandos Útiles

```bash
# Limpiar caché después de crear entidad
drush cr

# Ver entidades registradas
drush ev "print_r(array_keys(\Drupal::entityTypeManager()->getDefinitions()));"

# Actualizar esquema de BD
drush updb
```
