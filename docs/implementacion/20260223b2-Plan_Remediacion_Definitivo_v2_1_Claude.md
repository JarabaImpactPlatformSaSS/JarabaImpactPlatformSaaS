ECOSISTEMA JARABA

**Plan de Remediación Definitivo**

v2.1 --- Corrección Arquitectura de Precios

**Tiers, features y precios 100% configurables desde UI**

  ----------------------- -----------------------------------------------
  **Fecha**               23 de febrero de 2026

  **Autor**               Claude (Anthropic) --- Consolidación Codex +
                          Contra-Auditoría

  **Versión**             2.1 (corrige hardcoding de precios en v2.0)

  **Esfuerzo**            160-200 horas \| 60 días (4 fases)

  **Equipo**              2 Backend Drupal Senior + 1 QA + 0.5 DevOps

  **Estado**              DEFINITIVO --- Listo para implementación por
                          Claude Code
  ----------------------- -----------------------------------------------

> **⚠️ CAMBIO CRÍTICO vs v2.0**
>
> Los precios NO están hardcodeados. Cada vertical tiene precios
> independientes. Tiers, features y límites son Config Entities
> editables desde el Admin Center (Doc 104, sección 10.2). Stripe es la
> fuente de verdad para billing. La UI es la fuente de verdad para
> features/límites.

**1. Arquitectura de Planes Configurable desde UI**

Esta sección reemplaza el prerrequisito de la v2.0 que tenía precios
hardcodeados en YAML. La nueva arquitectura separa tres conceptos:

  ----------------------- ----------------------- --------------------------------------------
  **Concepto**            **Fuente de verdad**    **Quién lo gestiona**

  Tiers (starter,         Config Entity:          Admin UI: /admin/config/jaraba/plans
  professional,           saas_plan_tier          
  enterprise)                                     

  Features y límites por  Config Entity:          Admin UI:
  tier+vertical           saas_plan_features      /admin/config/jaraba/plans/{tier}/features

  Precios (mensual,       Stripe Products +       Stripe Dashboard + sync automático
  anual, add-ons)         Prices                  
  ----------------------- ----------------------- --------------------------------------------

**1.1 Entidad SaasPlanTier (Config Entity editable desde UI)**

Esta entidad define los tiers disponibles en la plataforma. Se gestiona
desde el Admin Center (Doc 104, sección 10.2 Billing Plans). NO es un
simple config YAML --- es una Config Entity con formulario de
administración.

**Definición de la entidad**

> //
> web/modules/custom/ecosistema_jaraba_core/src/Entity/SaasPlanTier.php
>
> namespace Drupal\\ecosistema_jaraba_core\\Entity;
>
> use Drupal\\Core\\Config\\Entity\\ConfigEntityBase;
>
> /\*\*
>
> \* \@ConfigEntityType(
>
> \* id = \"saas_plan_tier\",
>
> \* label = \@Translation(\"Plan SaaS\"),
>
> \* handlers = {
>
> \* \"list_builder\" =
> \"Drupal\\ecosistema_jaraba_core\\SaasPlanTierListBuilder\",
>
> \* \"form\" = {
>
> \* \"add\" =
> \"Drupal\\ecosistema_jaraba_core\\Form\\SaasPlanTierForm\",
>
> \* \"edit\" =
> \"Drupal\\ecosistema_jaraba_core\\Form\\SaasPlanTierForm\",
>
> \* \"delete\" = \"Drupal\\Core\\Entity\\EntityDeleteForm\"
>
> \* }
>
> \* },
>
> \* config_prefix = \"plan_tier\",
>
> \* admin_permission = \"administer saas plans\",
>
> \* entity_keys = {
>
> \* \"id\" = \"id\",
>
> \* \"label\" = \"label\"
>
> \* },
>
> \* config_export = {
>
> \* \"id\", \"label\", \"label_es\", \"weight\", \"is_active\",
>
> \* \"aliases\", \"description\", \"badge_color\",
>
> \* \"stripe_product_ids\"
>
> \* },
>
> \* links = {
>
> \* \"collection\" = \"/admin/config/jaraba/plans\",
>
> \* \"add-form\" = \"/admin/config/jaraba/plans/add\",
>
> \* \"edit-form\" = \"/admin/config/jaraba/plans/{saas_plan_tier}\",
>
> \* \"delete-form\" =
> \"/admin/config/jaraba/plans/{saas_plan_tier}/delete\"
>
> \* }
>
> \* )
>
> \*/
>
> class SaasPlanTier extends ConfigEntityBase implements
> SaasPlanTierInterface {
>
> protected string \$id;
>
> protected string \$label;
>
> protected string \$label_es = \'\';
>
> protected int \$weight = 0;
>
> protected bool \$is_active = TRUE;
>
> protected array \$aliases = \[\];
>
> protected string \$description = \'\';
>
> protected string \$badge_color = \'#00A9A5\';
>
> protected array \$stripe_product_ids = \[\];
>
> // stripe_product_ids es un array asociativo: vertical =\>
> stripe_product_id
>
> // Ejemplo: \[\'empleabilidad\' =\> \'prod_emp_starter\',
> \'agroconecta\' =\> \'prod_agro_starter\'\]
>
> public function getStripeProductId(string \$vertical): ?string {
>
> return \$this-\>stripe_product_ids\[\$vertical\] ?? NULL;
>
> }
>
> public function getAliases(): array {
>
> return \$this-\>aliases;
>
> }
>
> public function isAlias(string \$name): bool {
>
> return in_array(strtolower(\$name), array_map(\'strtolower\',
> \$this-\>aliases), TRUE);
>
> }
>
> }

**Formulario de administración (editable desde UI)**

> //
> web/modules/custom/ecosistema_jaraba_core/src/Form/SaasPlanTierForm.php
>
> namespace Drupal\\ecosistema_jaraba_core\\Form;
>
> use Drupal\\Core\\Entity\\EntityForm;
>
> use Drupal\\Core\\Form\\FormStateInterface;
>
> class SaasPlanTierForm extends EntityForm {
>
> public function form(array \$form, FormStateInterface \$form_state):
> array {
>
> \$form = parent::form(\$form, \$form_state);
>
> \$entity = \$this-\>entity;
>
> \$form\[\'label\'\] = \[
>
> \'#type\' =\> \'textfield\',
>
> \'#title\' =\> \$this-\>t(\'Nombre del plan (EN)\'),
>
> \'#required\' =\> TRUE,
>
> \'#default_value\' =\> \$entity-\>label(),
>
> \];
>
> \$form\[\'label_es\'\] = \[
>
> \'#type\' =\> \'textfield\',
>
> \'#title\' =\> \$this-\>t(\'Nombre del plan (ES)\'),
>
> \'#default_value\' =\> \$entity-\>get(\'label_es\'),
>
> \];
>
> \$form\[\'id\'\] = \[
>
> \'#type\' =\> \'machine_name\',
>
> \'#title\' =\> \$this-\>t(\'Machine name (canónico)\'),
>
> \'#default_value\' =\> \$entity-\>id(),
>
> \'#disabled\' =\> !\$entity-\>isNew(),
>
> \'#machine_name\' =\> \[\'exists\' =\> \[SaasPlanTier::class,
> \'load\'\]\],
>
> \'#description\' =\> \$this-\>t(\'Ejemplo: starter, professional,
> enterprise. NO cambiar después de creado.\'),
>
> \];
>
> \$form\[\'description\'\] = \[
>
> \'#type\' =\> \'textarea\',
>
> \'#title\' =\> \$this-\>t(\'Descripción del plan\'),
>
> \'#default_value\' =\> \$entity-\>get(\'description\'),
>
> \'#rows\' =\> 3,
>
> \];
>
> \$form\[\'aliases\'\] = \[
>
> \'#type\' =\> \'textfield\',
>
> \'#title\' =\> \$this-\>t(\'Aliases (separados por coma)\'),
>
> \'#default_value\' =\> implode(\', \', \$entity-\>getAliases()),
>
> \'#description\' =\> \$this-\>t(\'Nombres históricos que mapean a este
> plan. Ej: basico, basic, free\'),
>
> \];
>
> \$form\[\'badge_color\'\] = \[
>
> \'#type\' =\> \'color\',
>
> \'#title\' =\> \$this-\>t(\'Color del badge\'),
>
> \'#default_value\' =\> \$entity-\>get(\'badge_color\') ?: \'#00A9A5\',
>
> \];
>
> \$form\[\'is_active\'\] = \[
>
> \'#type\' =\> \'checkbox\',
>
> \'#title\' =\> \$this-\>t(\'Plan activo\'),
>
> \'#default_value\' =\> \$entity-\>get(\'is_active\'),
>
> \];
>
> \$form\[\'weight\'\] = \[
>
> \'#type\' =\> \'weight\',
>
> \'#title\' =\> \$this-\>t(\'Orden de visualización\'),
>
> \'#default_value\' =\> \$entity-\>get(\'weight\'),
>
> \];
>
> // Stripe Product IDs por vertical
>
> \$form\[\'stripe_product_ids\'\] = \[
>
> \'#type\' =\> \'details\',
>
> \'#title\' =\> \$this-\>t(\'Stripe Product IDs por Vertical\'),
>
> \'#open\' =\> TRUE,
>
> \];
>
> \$verticals = \[\'empleabilidad\', \'emprendimiento\',
> \'agroconecta\', \'comercioconecta\', \'serviciosconecta\'\];
>
> foreach (\$verticals as \$vertical) {
>
> \$form\[\'stripe_product_ids\'\]\[\$vertical\] = \[
>
> \'#type\' =\> \'textfield\',
>
> \'#title\' =\> ucfirst(\$vertical),
>
> \'#default_value\' =\> \$entity-\>getStripeProductId(\$vertical) ??
> \'\',
>
> \'#description\' =\> \$this-\>t(\'Stripe Product ID (ej:
> prod_emp_starter)\'),
>
> \];
>
> }
>
> return \$form;
>
> }
>
> public function save(array \$form, FormStateInterface \$form_state):
> int {
>
> // Convertir aliases de string a array
>
> \$aliases = \$form_state-\>getValue(\'aliases\');
>
> \$this-\>entity-\>set(\'aliases\', array_filter(array_map(\'trim\',
> explode(\',\', \$aliases))));
>
> \$result = parent::save(\$form, \$form_state);
>
> \$this-\>messenger()-\>addStatus(\$this-\>t(\'Plan \@label
> guardado.\', \[\'@label\' =\> \$this-\>entity-\>label()\]));
>
> \$form_state-\>setRedirectUrl(\$this-\>entity-\>toUrl(\'collection\'));
>
> return \$result;
>
> }
>
> }

**1.2 Entidad SaasPlanFeatures (Features/Límites por Tier+Vertical)**

Cada combinación tier+vertical tiene su propio conjunto de features y
límites, editable desde UI. Esto permite que Empleabilidad Starter tenga
5 cursos mientras AgroConecta Starter tenga 50 productos, sin tocar
código.

> **PRINCIPIO DE DISEÑO**
>
> Los precios se gestionan en Stripe (source of truth para billing). Los
> features y límites se gestionan desde esta entidad (source of truth
> para lógica de negocio). NUNCA hardcodear ninguno de los dos.

**Definición de la entidad**

> //
> web/modules/custom/ecosistema_jaraba_core/src/Entity/SaasPlanFeatures.php
>
> namespace Drupal\\ecosistema_jaraba_core\\Entity;
>
> use Drupal\\Core\\Config\\Entity\\ConfigEntityBase;
>
> /\*\*
>
> \* Features y límites por combinación tier+vertical.
>
> \*
>
> \* ID format: {vertical}\_{tier} (ej: empleabilidad_starter,
> agroconecta_enterprise)
>
> \* También soporta \'\_default\_{tier}\' como fallback cross-vertical.
>
> \*
>
> \* \@ConfigEntityType(
>
> \* id = \"saas_plan_features\",
>
> \* label = \@Translation(\"Plan Features\"),
>
> \* handlers = {
>
> \* \"list_builder\" =
> \"Drupal\\ecosistema_jaraba_core\\SaasPlanFeaturesListBuilder\",
>
> \* \"form\" = {
>
> \* \"edit\" =
> \"Drupal\\ecosistema_jaraba_core\\Form\\SaasPlanFeaturesForm\",
>
> \* }
>
> \* },
>
> \* config_prefix = \"plan_features\",
>
> \* admin_permission = \"administer saas plans\",
>
> \* entity_keys = { \"id\" = \"id\", \"label\" = \"label\" },
>
> \* config_export = {
>
> \* \"id\", \"label\", \"vertical\", \"tier\",
>
> \* \"limits\", \"feature_flags\", \"stripe_prices\",
>
> \* \"platform_fee_percent\", \"sla\"
>
> \* },
>
> \* links = {
>
> \* \"collection\" = \"/admin/config/jaraba/plan-features\",
>
> \* \"edit-form\" =
> \"/admin/config/jaraba/plan-features/{saas_plan_features}\"
>
> \* }
>
> \* )
>
> \*/
>
> class SaasPlanFeatures extends ConfigEntityBase {
>
> protected string \$id; // \'empleabilidad_starter\'
>
> protected string \$label;
>
> protected string \$vertical; // \'empleabilidad\'
>
> protected string \$tier; // \'starter\'
>
> // Límites numéricos (-1 = ilimitado, 0 = deshabilitado)
>
> protected array \$limits = \[
>
> \'max_users\' =\> 3,
>
> \'max_pages\' =\> 5,
>
> \'max_products\' =\> 50,
>
> \'max_courses\' =\> 5,
>
> \'max_job_postings\' =\> 3,
>
> \'max_services\' =\> 5,
>
> \'storage_gb\' =\> 5,
>
> \'api_calls\' =\> 10000,
>
> \'ai_credits\' =\> 1000,
>
> \'orders_per_month\' =\> 100,
>
> \'candidates_per_month\' =\> 50,
>
> \'bookings_per_month\' =\> 50,
>
> \'mentoring_hours_month\' =\> 0,
>
> \];
>
> // Feature flags (true/false)
>
> protected array \$feature_flags = \[
>
> \'webhooks\' =\> FALSE,
>
> \'api_access\' =\> FALSE,
>
> \'api_write_access\' =\> FALSE,
>
> \'white_label\' =\> FALSE,
>
> \'ai_copilot\' =\> FALSE,
>
> \'premium_blocks\' =\> FALSE,
>
> \'video_conferencing\' =\> FALSE,
>
> \'digital_signature\' =\> FALSE,
>
> \'matching_engine\' =\> FALSE,
>
> \'learning_paths\' =\> FALSE,
>
> \'auto_certificates\' =\> FALSE,
>
> \'financial_projections\' =\> FALSE,
>
> \'competitive_analysis\' =\> FALSE,
>
> \'qr_traceability\' =\> FALSE,
>
> \'priority_support\' =\> FALSE,
>
> \'dedicated_support\' =\> FALSE,
>
> \];
>
> // Stripe Price IDs (cargados desde Stripe, editables por admin)
>
> protected array \$stripe_prices = \[
>
> \'monthly\' =\> \'\', // price_emp_starter_monthly
>
> \'yearly\' =\> \'\', // price_emp_starter_yearly
>
> \];
>
> protected float \$platform_fee_percent = 8.0;
>
> protected ?string \$sla = NULL;
>
> public function getLimit(string \$key): int {
>
> return (int) (\$this-\>limits\[\$key\] ?? 0);
>
> }
>
> public function hasFeature(string \$key): bool {
>
> return (bool) (\$this-\>feature_flags\[\$key\] ?? FALSE);
>
> }
>
> public function getStripePriceId(string \$cycle = \'monthly\'): string
> {
>
> return \$this-\>stripe_prices\[\$cycle\] ?? \'\';
>
> }
>
> public function isUnlimited(string \$key): bool {
>
> return \$this-\>getLimit(\$key) === -1;
>
> }
>
> }

**Formulario de features (editable desde UI)**

> //
> web/modules/custom/ecosistema_jaraba_core/src/Form/SaasPlanFeaturesForm.php
>
> class SaasPlanFeaturesForm extends EntityForm {
>
> public function form(array \$form, FormStateInterface \$form_state):
> array {
>
> \$form = parent::form(\$form, \$form_state);
>
> \$entity = \$this-\>entity;
>
> \$form\[\'info\'\] = \[
>
> \'#markup\' =\> \'\<h3\>\' . \$entity-\>label() . \'\</h3\>\'
>
> . \'\<p\>Vertical: \<strong\>\' . \$entity-\>get(\'vertical\') .
> \'\</strong\>\'
>
> . \' \| Tier: \<strong\>\' . \$entity-\>get(\'tier\') .
> \'\</strong\>\</p\>\',
>
> \];
>
> // === STRIPE PRICES ===
>
> \$form\[\'stripe_prices\'\] = \[
>
> \'#type\' =\> \'details\',
>
> \'#title\' =\> \$this-\>t(\'Precios Stripe\'),
>
> \'#open\' =\> TRUE,
>
> \'#description\' =\> \$this-\>t(\'Los IDs de precio vienen de Stripe
> Dashboard. No incluir importes aquí.\'),
>
> \];
>
> \$form\[\'stripe_prices\'\]\[\'monthly\'\] = \[
>
> \'#type\' =\> \'textfield\',
>
> \'#title\' =\> \$this-\>t(\'Stripe Price ID (Mensual)\'),
>
> \'#default_value\' =\> \$entity-\>getStripePriceId(\'monthly\'),
>
> \'#description\' =\> \$this-\>t(\'Ej: price_emp_starter_monthly\'),
>
> \];
>
> \$form\[\'stripe_prices\'\]\[\'yearly\'\] = \[
>
> \'#type\' =\> \'textfield\',
>
> \'#title\' =\> \$this-\>t(\'Stripe Price ID (Anual)\'),
>
> \'#default_value\' =\> \$entity-\>getStripePriceId(\'yearly\'),
>
> \];
>
> // === LÍMITES NUMÉRICOS ===
>
> \$form\[\'limits\'\] = \[
>
> \'#type\' =\> \'details\',
>
> \'#title\' =\> \$this-\>t(\'Límites (-1 = ilimitado)\'),
>
> \'#open\' =\> TRUE,
>
> \];
>
> \$limitLabels = \[
>
> \'max_users\' =\> \'Usuarios máximos\',
>
> \'max_pages\' =\> \'Páginas máximas\',
>
> \'max_products\' =\> \'Productos máximos\',
>
> \'max_courses\' =\> \'Cursos máximos\',
>
> \'max_job_postings\' =\> \'Ofertas de empleo activas\',
>
> \'max_services\' =\> \'Servicios publicados\',
>
> \'storage_gb\' =\> \'Almacenamiento (GB)\',
>
> \'api_calls\' =\> \'API calls/mes\',
>
> \'ai_credits\' =\> \'Créditos IA/mes\',
>
> \'orders_per_month\' =\> \'Pedidos/mes\',
>
> \'candidates_per_month\' =\> \'Candidaturas/mes\',
>
> \'bookings_per_month\' =\> \'Reservas/mes\',
>
> \'mentoring_hours_month\' =\> \'Horas mentoría/mes\',
>
> \];
>
> foreach (\$limitLabels as \$key =\> \$label) {
>
> \$form\[\'limits\'\]\[\$key\] = \[
>
> \'#type\' =\> \'number\',
>
> \'#title\' =\> \$this-\>t(\$label),
>
> \'#default_value\' =\> \$entity-\>getLimit(\$key),
>
> \'#min\' =\> -1,
>
> \'#description\' =\> \$this-\>t(\'-1 = ilimitado, 0 =
> deshabilitado\'),
>
> \];
>
> }
>
> // === FEATURE FLAGS ===
>
> \$form\[\'feature_flags\'\] = \[
>
> \'#type\' =\> \'details\',
>
> \'#title\' =\> \$this-\>t(\'Features habilitados\'),
>
> \'#open\' =\> TRUE,
>
> \];
>
> \$featureLabels = \[
>
> \'webhooks\' =\> \'Webhooks salientes\',
>
> \'api_access\' =\> \'Acceso API (lectura)\',
>
> \'api_write_access\' =\> \'Acceso API (escritura)\',
>
> \'white_label\' =\> \'Marca blanca\',
>
> \'ai_copilot\' =\> \'AI Copilot\',
>
> \'premium_blocks\' =\> \'Bloques premium (Page Builder)\',
>
> \'video_conferencing\' =\> \'Videoconferencia\',
>
> \'digital_signature\' =\> \'Firma digital PAdES\',
>
> \'matching_engine\' =\> \'Motor de matching\',
>
> \'learning_paths\' =\> \'Rutas de aprendizaje\',
>
> \'auto_certificates\' =\> \'Certificados automáticos\',
>
> \'financial_projections\' =\> \'Proyecciones financieras\',
>
> \'competitive_analysis\' =\> \'Análisis competitivo\',
>
> \'qr_traceability\' =\> \'Trazabilidad QR\',
>
> \'priority_support\' =\> \'Soporte prioritario\',
>
> \'dedicated_support\' =\> \'Soporte dedicado + SLA\',
>
> \];
>
> foreach (\$featureLabels as \$key =\> \$label) {
>
> \$form\[\'feature_flags\'\]\[\$key\] = \[
>
> \'#type\' =\> \'checkbox\',
>
> \'#title\' =\> \$this-\>t(\$label),
>
> \'#default_value\' =\> \$entity-\>hasFeature(\$key),
>
> \];
>
> }
>
> // === COMISIÓN Y SLA ===
>
> \$form\[\'platform_fee_percent\'\] = \[
>
> \'#type\' =\> \'number\',
>
> \'#title\' =\> \$this-\>t(\'Comisión plataforma (%)\'),
>
> \'#default_value\' =\> \$entity-\>get(\'platform_fee_percent\'),
>
> \'#step\' =\> 0.5,
>
> \'#min\' =\> 0,
>
> \'#max\' =\> 100,
>
> \];
>
> \$form\[\'sla\'\] = \[
>
> \'#type\' =\> \'select\',
>
> \'#title\' =\> \$this-\>t(\'SLA garantizado\'),
>
> \'#options\' =\> \[\'\' =\> \'Sin SLA\', \'99.5\' =\> \'99.5%\',
> \'99.9\' =\> \'99.9%\', \'99.99\' =\> \'99.99%\'\],
>
> \'#default_value\' =\> \$entity-\>get(\'sla\') ?? \'\',
>
> \];
>
> return \$form;
>
> }
>
> }

**1.3 PlanResolverService v2 (lee de Config Entities, no de YAML)**

El servicio central de resolución de planes ahora carga todo desde las
Config Entities, sin ningún valor hardcodeado.

> //
> web/modules/custom/ecosistema_jaraba_core/src/Service/PlanResolverService.php
>
> namespace Drupal\\ecosistema_jaraba_core\\Service;
>
> use Drupal\\Core\\Entity\\EntityTypeManagerInterface;
>
> class PlanResolverService {
>
> private array \$aliasMap = \[\];
>
> private bool \$initialized = FALSE;
>
> public function \_\_construct(
>
> protected EntityTypeManagerInterface \$entityTypeManager,
>
> ) {}
>
> private function initialize(): void {
>
> if (\$this-\>initialized) return;
>
> \$this-\>initialized = TRUE;
>
> // Cargar TODOS los tiers desde Config Entities
>
> \$tiers =
> \$this-\>entityTypeManager-\>getStorage(\'saas_plan_tier\')-\>loadMultiple();
>
> foreach (\$tiers as \$tier) {
>
> \$id = \$tier-\>id();
>
> \$this-\>aliasMap\[\$id\] = \$id;
>
> foreach (\$tier-\>getAliases() as \$alias) {
>
> \$this-\>aliasMap\[strtolower(\$alias)\] = \$id;
>
> }
>
> }
>
> }
>
> /\*\*
>
> \* Normaliza cualquier nombre de plan a su machine_name canónico.
>
> \* Lee los aliases desde SaasPlanTier entities (configurados en UI).
>
> \*/
>
> public function normalize(string \$planName): string {
>
> \$this-\>initialize();
>
> return \$this-\>aliasMap\[strtolower(trim(\$planName))\] ??
> strtolower(trim(\$planName));
>
> }
>
> /\*\*
>
> \* Obtiene las features para una combinación vertical+tier.
>
> \* Cascada: vertical_tier -\> \_default_tier -\> NULL
>
> \*/
>
> public function getFeatures(string \$vertical, string \$tier):
> ?SaasPlanFeatures {
>
> \$tier = \$this-\>normalize(\$tier);
>
> \$storage =
> \$this-\>entityTypeManager-\>getStorage(\'saas_plan_features\');
>
> // 1. Buscar features específicas: empleabilidad_starter
>
> \$specific = \$storage-\>load(\$vertical . \'\_\' . \$tier);
>
> if (\$specific) return \$specific;
>
> // 2. Fallback a defaults: \_default_starter
>
> \$default = \$storage-\>load(\'\_default\_\' . \$tier);
>
> return \$default ?: NULL;
>
> }
>
> /\*\*
>
> \* Verifica un límite para el tenant actual.
>
> \*/
>
> public function checkLimit(string \$vertical, string \$tier, string
> \$limitKey): int {
>
> \$features = \$this-\>getFeatures(\$vertical, \$tier);
>
> if (!\$features) {
>
> \\Drupal::logger(\'plan_resolver\')-\>error(
>
> \'No features configured for \@vertical/@tier. Configure at
> /admin/config/jaraba/plan-features\',
>
> \[\'@vertical\' =\> \$vertical, \'@tier\' =\> \$tier\]
>
> );
>
> return 0; // Fail-safe: denegar
>
> }
>
> return \$features-\>getLimit(\$limitKey);
>
> }
>
> /\*\*
>
> \* Resuelve plan desde un evento de Stripe subscription.
>
> \*/
>
> public function resolveFromStripeSubscription(object \$sub): string {
>
> \$priceId = \$sub-\>items-\>data\[0\]-\>price-\>id ?? \'\';
>
> // Buscar en SaasPlanFeatures cuál tiene este price_id
>
> \$allFeatures = \$this-\>entityTypeManager
>
> -\>getStorage(\'saas_plan_features\')-\>loadMultiple();
>
> foreach (\$allFeatures as \$features) {
>
> if (\$features-\>getStripePriceId(\'monthly\') === \$priceId
>
> \|\| \$features-\>getStripePriceId(\'yearly\') === \$priceId) {
>
> return \$features-\>get(\'tier\');
>
> }
>
> }
>
> // Fallback: metadata
>
> \$meta = \$sub-\>metadata\[\'plan\'\] ?? \'\';
>
> return \$meta ? \$this-\>normalize(\$meta) : \'starter\';
>
> }
>
> }

**1.4 QuotaManagerService v2 (delega en PlanResolverService)**

Sin ningún array de límites hardcodeado. Todo viene de las Config
Entities.

> //
> web/modules/custom/jaraba_page_builder/src/Service/QuotaManagerService.php
>
> class QuotaManagerService {
>
> public function \_\_construct(
>
> protected TenantContextServiceInterface \$tenantContext,
>
> protected PlanResolverService \$planResolver,
>
> ) {}
>
> public function canCreatePage(): bool {
>
> \$tenant = \$this-\>tenantContext-\>getCurrentTenant();
>
> if (!\$tenant) return FALSE;
>
> \$vertical = \$tenant-\>get(\'vertical\')-\>value ?? \'\_default\';
>
> \$tier = \$tenant-\>get(\'plan_type\')-\>value ?? \'starter\';
>
> \$limit = \$this-\>planResolver-\>checkLimit(\$vertical, \$tier,
> \'max_pages\');
>
> if (\$limit === -1) return TRUE; // ilimitado
>
> if (\$limit === 0) return FALSE; // deshabilitado
>
> return \$this-\>countTenantPages(\$tenant-\>id()) \< \$limit;
>
> }
>
> public function canUseFeature(string \$featureKey): bool {
>
> \$tenant = \$this-\>tenantContext-\>getCurrentTenant();
>
> if (!\$tenant) return FALSE;
>
> \$features = \$this-\>planResolver-\>getFeatures(
>
> \$tenant-\>get(\'vertical\')-\>value ?? \'\_default\',
>
> \$tenant-\>get(\'plan_type\')-\>value ?? \'starter\'
>
> );
>
> return \$features ? \$features-\>hasFeature(\$featureKey) : FALSE;
>
> }
>
> }

**1.5 Config por Defecto (install, NO hardcode)**

Estos archivos se instalan con el módulo pero son editables desde UI
desde el primer momento. Sirven como seed data.

**Tiers por defecto**

> \# config/install/ecosistema_jaraba_core.plan_tier.starter.yml
>
> id: starter
>
> label: Starter
>
> label_es: Básico
>
> weight: 10
>
> is_active: true
>
> aliases: \[basico, basic, free\]
>
> description: Plan de entrada para pequeños negocios
>
> badge_color: \'#00A9A5\'
>
> stripe_product_ids:
>
> empleabilidad: \'\'
>
> emprendimiento: \'\'
>
> agroconecta: \'\'
>
> comercioconecta: \'\'
>
> serviciosconecta: \'\'
>
> \# config/install/ecosistema_jaraba_core.plan_tier.professional.yml
>
> id: professional
>
> label: Professional
>
> label_es: Profesional
>
> weight: 20
>
> is_active: true
>
> aliases: \[profesional, growth, pro\]
>
> description: Para negocios en crecimiento
>
> badge_color: \'#FF8C42\'
>
> \# config/install/ecosistema_jaraba_core.plan_tier.enterprise.yml
>
> id: enterprise
>
> label: Enterprise
>
> label_es: Enterprise
>
> weight: 30
>
> is_active: true
>
> aliases: \[business, premium\]
>
> description: Para organizaciones con necesidades avanzadas
>
> badge_color: \'#6B3FA0\'

**Features por defecto (Empleabilidad como ejemplo)**

> \#
> config/install/ecosistema_jaraba_core.plan_features.empleabilidad_starter.yml
>
> id: empleabilidad_starter
>
> label: \'Empleabilidad - Starter\'
>
> vertical: empleabilidad
>
> tier: starter
>
> limits:
>
> max_users: 2
>
> max_courses: 5
>
> max_job_postings: 3
>
> candidates_per_month: 50
>
> max_pages: 5
>
> storage_gb: 1
>
> api_calls: 10000
>
> ai_credits: 0
>
> feature_flags:
>
> webhooks: false
>
> api_access: false
>
> ai_copilot: false
>
> matching_engine: false
>
> learning_paths: false
>
> auto_certificates: false
>
> premium_blocks: false
>
> white_label: false
>
> priority_support: false
>
> stripe_prices:
>
> monthly: \'\' \# price_emp_starter_monthly (configurar en Stripe
> Dashboard)
>
> yearly: \'\' \# price_emp_starter_yearly
>
> platform_fee_percent: 8
>
> sla: null

*Se necesitan 15 archivos (5 verticales × 3 tiers) + 3 defaults
(\_default_starter, \_default_professional, \_default_enterprise). Los
defaults sirven como fallback si un vertical no tiene config
específica.*

**1.6 Rutas de administración**

> \# ecosistema_jaraba_core.routing.yml
>
> \# Lista de planes
>
> entity.saas_plan_tier.collection:
>
> path: \'/admin/config/jaraba/plans\'
>
> defaults:
>
> \_entity_list: \'saas_plan_tier\'
>
> \_title: \'Planes SaaS\'
>
> requirements:
>
> \_permission: \'administer saas plans\'
>
> \# Añadir plan
>
> entity.saas_plan_tier.add_form:
>
> path: \'/admin/config/jaraba/plans/add\'
>
> defaults:
>
> \_entity_form: \'saas_plan_tier.add\'
>
> \_title: \'Añadir Plan\'
>
> requirements:
>
> \_permission: \'administer saas plans\'
>
> \# Editar plan
>
> entity.saas_plan_tier.edit_form:
>
> path: \'/admin/config/jaraba/plans/{saas_plan_tier}\'
>
> defaults:
>
> \_entity_form: \'saas_plan_tier.edit\'
>
> \_title: \'Editar Plan\'
>
> requirements:
>
> \_permission: \'administer saas plans\'
>
> \# Lista de features por tier+vertical
>
> entity.saas_plan_features.collection:
>
> path: \'/admin/config/jaraba/plan-features\'
>
> defaults:
>
> \_entity_list: \'saas_plan_features\'
>
> \_title: \'Features por Plan y Vertical\'
>
> requirements:
>
> \_permission: \'administer saas plans\'

**1.7 Impacto en estimaciones**

Añadir las Config Entities con formularios de admin suma 10-15h al plan.
Sin embargo, elimina para siempre la necesidad de que un desarrollador
intervenga para cambiar precios, límites o features. El desglose
actualizado:

  ------------------------------- ----------- ----------- ---------------
  **Tarea**                       **v2.0**    **v2.1**    **Razón del
                                                          cambio**

  Entidades SaasPlanTier + Form   N/A         6-8h        NUEVO: Config
                                                          Entity + admin
                                                          form

  Entidades SaasPlanFeatures +    N/A         8-10h       NUEVO: Features
  Form                                                    configurables
                                                          desde UI

  PlanResolverService v2          8-10h       6-8h        Más simple: lee
                                                          de entities

  Config install (18 YAMLs seed)  2-3h        4-5h        15+3 archivos
                                                          en vez de 3

  QuotaManager refactor           10-12h      6-8h        Delega todo a
                                                          PlanResolver

  Tests                           16-20h      16-20h      Sin cambio
  ------------------------------- ----------- ----------- ---------------

**Esfuerzo total Fase 1+2 v2.1: 80-100h** (vs 70-85h en v2.0).
Incremento de \~12h que se amortiza en la primera semana de operación.

**2. Cambios en Fases 1-4 respecto a v2.0**

Las tareas REM-P0-01 a REM-P2-04 del documento v2.0 se mantienen con los
siguientes ajustes:

**2.1 Ajustes en REM-P0-04 (Mapping Stripe)**

El SubscriptionUpdatedHandler ahora resuelve el plan usando
PlanResolverService v2, que busca el stripe_price_id en las Config
Entities SaasPlanFeatures en lugar de un array hardcodeado.

**2.2 Ajustes en REM-P0-05 (Canonizar IDs)**

Los archivos YAML de config/sync con nombres en español se reemplazan
por los archivos config/install de las nuevas Config Entities. La tabla
de equivalencias vive en los aliases de SaasPlanTier, no en un YAML
estático.

**2.3 Ajustes en REM-P1-01 (Config Page Builder)**

El jaraba_page_builder.settings.yml de la v2.0 con plan_limits
hardcodeados se elimina. Los límites del Page Builder se leen de
SaasPlanFeatures (campo max_pages y feature_flag premium_blocks). El
QuotaManagerService ya no necesita su propia config.

**2.4 Ajustes en REM-P1-02 (Fallback cuotas)**

El fallback \"fail-safe a 0\" de la v2.0 se mantiene, pero ahora el
error log indica exactamente qué URL visitar para configurar los
features que faltan: /admin/config/jaraba/plan-features.

**2.5 Nueva tarea: REM-P0-07 (Config Entities + Admin UI)**

Se añade como tarea P0 porque sin las entidades configurables, todo lo
demás sigue hardcodeado.

  ------------------------------- ---------- ---------- ----------------------------
  **Tarea**                       **Est.**   **Fase**   **Criterio de aceptación**

  REM-P0-07: Config Entities      14-18h     Fase 1     Admin puede
  SaasPlanTier +                                        crear/editar/eliminar planes
  SaasPlanFeatures + Admin                              y features desde
  Forms + Install configs                               /admin/config/jaraba/plans
                                                        sin tocar código
  ------------------------------- ---------- ---------- ----------------------------

**3. Precios por Vertical según Doc 158**

Referencia de precios actuales por vertical para cargar como seed data
en las Config Entities. Estos valores son editables desde UI tras la
instalación.

  ------------------ ------------- ------------- ---------------- -------------
  **Vertical**       **Starter**   **Pro**       **Enterprise**   **Comisión
                                                                  S/P/E**

  Empleabilidad      €29/mes       €79/mes       €149/mes         8% / 5% / 3%

  Emprendimiento     €39/mes       €99/mes       €199/mes         8% / 5% / 3%

  AgroConecta        €29/mes       €79/mes       €149/mes         8% / 5% / 3%

  ComercioConecta    €29/mes       €79/mes       €149/mes         8% / 5% / 3%

  ServiciosConecta   €29/mes       €79/mes       €149/mes         10% / 7% / 4%
  ------------------ ------------- ------------- ---------------- -------------

> **IMPORTANTE**
>
> Estos precios son la fotografía actual del Doc 158. Tras implementar
> las Config Entities, Pepe puede cambiarlos desde
> /admin/config/jaraba/plan-features sin ningún deploy de código. Los
> precios reales de billing viven en Stripe y se vinculan por
> stripe_price_id.

**4. Tests de Contrato Actualizados**

Se añaden tests que verifican la infraestructura de configuración:

> // Nuevo test de contrato
>
> public function testAllVerticalsHaveFeatureConfigs(): void {
>
> \$verticals = \[\'empleabilidad\', \'emprendimiento\',
> \'agroconecta\',
>
> \'comercioconecta\', \'serviciosconecta\'\];
>
> \$tiers = \[\'starter\', \'professional\', \'enterprise\'\];
>
> \$storage = \$this-\>container-\>get(\'entity_type.manager\')
>
> -\>getStorage(\'saas_plan_features\');
>
> foreach (\$verticals as \$v) {
>
> foreach (\$tiers as \$t) {
>
> \$id = \$v . \'\_\' . \$t;
>
> \$entity = \$storage-\>load(\$id);
>
> // Debe existir la config específica O el default
>
> \$default = \$storage-\>load(\'\_default\_\' . \$t);
>
> \$this-\>assertTrue(
>
> \$entity !== NULL \|\| \$default !== NULL,
>
> \"No hay features para \$id ni default para \$t\"
>
> );
>
> }
>
> }
>
> }
>
> public function testPlanResolverUsesNoHardcodedValues(): void {
>
> \$ref = new \\ReflectionClass(PlanResolverService::class);
>
> \$src = file_get_contents(\$ref-\>getFileName());
>
> // No debe contener precios
>
> \$this-\>assertDoesNotMatchRegularExpression(\'/\[0-9\]+\\.?\[0-9\]\*\\s\*€/\',
> \$src);
>
> // No debe contener arrays de límites
>
> \$this-\>assertStringNotContainsString(\"\'max_users\'\", \$src);
>
> \$this-\>assertStringNotContainsString(\"\'storage_gb\'\", \$src);
>
> }

**5. Resumen de Esfuerzo Actualizado v2.1**

  --------------------------- -------------- ---------- ------------------------
  **Fase**                    **Horas**      **Días**   **Entregables clave**

  **Fase 1: Aislamiento +     50-60h         1-15       Tenant isolation +
  Config Entities**                                     SaasPlanTier/Features
                                                        entities + Admin UI

  **Fase 2: Billing           30-40h         16-30      PlanResolverService v2 +
  coherente**                                           Stripe mapping +
                                                        pricing/trial fixes

  **Fase 3: Entitlements sin  25-35h         31-50      QuotaManager v2 +
  drift**                                               validación pre-deploy +
                                                        flags analytics

  **Fase 4: CI + Tests        35-45h         51-60      Pipeline CI completo + 8
  contrato**                                            tests de contrato +
                                                        auditoría vertical

  **TOTAL**                   **140-180h**   **60       **Plataforma con
                                             días**     tiers/features/precios
                                                        100% configurables sin
                                                        código**
  --------------------------- -------------- ---------- ------------------------

**6. Registro de Cambios**

  -------------- ------------- ----------- ---------------------------------------
  **Fecha**      **Versión**   **Autor**   **Cambio**

  2026-02-23     2.1.0         Claude      Corrección crítica: precios NO
                                           hardcodeados. Config Entities
                                           SaasPlanTier + SaasPlanFeatures
                                           editables desde Admin UI.
                                           PlanResolverService v2 sin arrays
                                           estáticos. +10-15h por entities/forms
                                           pero elimina dependencia de
                                           desarrollador para cambios de
                                           precios/features.

  2026-02-23     2.0.0         Claude      Plan definitivo consolidado: Codex
                                           v1.0 + Codex v1.1 + Contra-Auditoría
                                           Claude.

  2026-02-23     1.1.0         Codex GPT-5 Recalibración tras contra-auditoría.

  2026-02-23     1.0.0         Codex GPT-5 Plan original.
  -------------- ------------- ----------- ---------------------------------------
