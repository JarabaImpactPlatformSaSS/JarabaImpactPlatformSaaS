DEVELOPER ONBOARDING KIT
Guía de Arranque para EDI Google Antigravity
Tu primer día → Tu primer commit → Tu primer deploy
Campo	Valor
Proyecto:	Jaraba Impact Platform
Equipo:	EDI Google Antigravity
Stack:	Drupal 11 + Commerce 3.x + PHP 8.3 + MariaDB
Documentación:	170+ especificaciones técnicas
Filosofía:	Sin Humo - Código limpio, producción primero
 
1. Bienvenido al Proyecto
Jaraba Impact Platform es un ecosistema SaaS multi-tenant para la transformación digital de PYMEs rurales en España. Vas a trabajar en uno de los proyectos más ambiciosos de digitalización del sector agroalimentario y de servicios.
1.1 Qué vas a construir
•	Una plataforma que sirve a 6 verticales de negocio diferentes
•	Un sistema multi-tenant donde cada cliente tiene su propio espacio aislado
•	Integración nativa con IA (Claude/Gemini) para copilots de cada vertical
•	Marketplace con Stripe Connect para pagos entre compradores y vendedores
•	Cumplimiento con regulaciones españolas (SEPE, GDPR, facturación)
1.2 Filosofía 'Sin Humo'
Este proyecto se rige por la metodología 'Sin Humo'. Esto significa:
•	NO bloatware: Cada línea de código debe justificarse
•	NO over-engineering: Soluciones simples para problemas simples
•	NO frameworks innecesarios: Drupal Core + Commerce es el stack
•	SÍ código limpio: PSR-12, PHPStan level 6, tests
•	SÍ documentación: Cada módulo tiene su especificación técnica
•	SÍ producción primero: Si no funciona en prod, no existe
 
2. Setup del Entorno (Día 1)
2.1 Requisitos
Software	Versión	Comando de verificación
PHP	8.3+	php -v
Composer	2.x	composer --version
Node.js	20 LTS	node -v
Docker	24+	docker --version
Git	2.40+	git --version
MariaDB	11.2+	mariadb --version (o via Docker)
2.2 Clonar y Configurar
# 1. Clonar repositorio
git clone git@github.com:jaraba-impact/platform.git
cd platform
 
# 2. Copiar configuración de entorno
cp .env.example .env
 
# 3. Editar .env con tus credenciales locales
nano .env
 
# Variables mínimas a configurar:
# DATABASE_URL=mysql://drupal:drupal@127.0.0.1:3306/jaraba
# REDIS_URL=redis://127.0.0.1:6379
# STRIPE_SECRET_KEY=sk_test_xxx (pedir a tech lead)
# CLAUDE_API_KEY=sk-ant-xxx (pedir a tech lead)
2.3 Opción A: Docker (Recomendado)
# Levantar todos los servicios
docker-compose up -d
 
# Verificar que están corriendo
docker-compose ps
 
# Deberías ver:
# - drupal (web app)
# - mariadb (database)
# - redis (cache)
# - mailhog (email testing)
 
# Instalar Drupal
docker-compose exec drupal drush site:install jaraba_profile -y
 
# Importar configuración
docker-compose exec drupal drush config:import -y
 
# Acceder a http://localhost:8080
# Usuario: admin / Password: admin (solo en local!)
2.4 Opción B: Local (sin Docker)
# 1. Instalar dependencias PHP
composer install
 
# 2. Instalar dependencias frontend
cd web/themes/jaraba_theme && npm install && npm run build && cd ../../..
 
# 3. Crear base de datos
mysql -u root -p -e "CREATE DATABASE jaraba; GRANT ALL ON jaraba.* TO 'drupal'@'localhost';"
 
# 4. Instalar Drupal
./vendor/bin/drush site:install jaraba_profile \
  --db-url=mysql://drupal:password@localhost/jaraba -y
 
# 5. Importar configuración
./vendor/bin/drush config:import -y
 
# 6. Lanzar servidor
./vendor/bin/drush serve
 
3. Estructura del Proyecto
3.1 Directorios Clave
platform/
├── web/
│   ├── modules/
│   │   └── custom/              # 🎯 AQUÍ TRABAJAS
│   │       ├── jaraba_core/     # Entidades base, utilities
│   │       ├── jaraba_tenant/   # Multi-tenancy (Group)
│   │       ├── jaraba_billing/  # Stripe integration
│   │       ├── jaraba_ai/       # Claude/Gemini integration
│   │       ├── jaraba_empleabilidad/   # Vertical: Empleo
│   │       ├── jaraba_emprendimiento/  # Vertical: Emprendimiento
│   │       ├── jaraba_agroconecta/     # Vertical: Marketplace agro
│   │       ├── jaraba_comercio/        # Vertical: Comercio local
│   │       └── jaraba_servicios/       # Vertical: Servicios prof.
│   ├── themes/
│   │   └── jaraba_theme/        # Theme principal
│   └── sites/default/
│       ├── settings.php         # Config Drupal
│       └── files/               # Uploads (gitignored)
├── config/
│   └── sync/                    # Config exportada (YAML)
├── tests/                       # PHPUnit tests
├── docs/                        # 170+ especificaciones técnicas
├── docker-compose.yml
├── phpunit.xml
└── composer.json
3.2 Módulos Custom - Convención
Cada módulo sigue esta estructura:
jaraba_empleabilidad/
├── jaraba_empleabilidad.info.yml     # Definición del módulo
├── jaraba_empleabilidad.module       # Hooks (mínimos)
├── jaraba_empleabilidad.services.yml # Servicios
├── jaraba_empleabilidad.routing.yml  # Rutas
├── jaraba_empleabilidad.permissions.yml
├── config/
│   ├── install/                      # Config inicial
│   └── optional/                     # Config opcional
├── src/
│   ├── Entity/                       # Entidades
│   ├── Service/                      # Lógica de negocio
│   ├── Controller/                   # Controllers
│   ├── Form/                         # Forms
│   ├── Plugin/                       # Plugins (Block, Field, etc)
│   └── EventSubscriber/              # Event subscribers
└── tests/
    └── src/
        ├── Unit/
        └── Kernel/
 
4. Tu Primer Commit
4.1 Workflow de Git
# Branches
main        → Producción (protected, solo PR)
develop     → Staging (integración)
feature/*   → Features nuevas
bugfix/*    → Correcciones
hotfix/*    → Urgentes para producción
 
# Crear feature branch
git checkout develop
git pull origin develop
git checkout -b feature/EMP-123-matching-algorithm
 
# Commits (Conventional Commits)
git commit -m "feat(empleabilidad): add skill matching algorithm"
git commit -m "fix(billing): correct VAT calculation for Canarias"
git commit -m "docs(api): update endpoint documentation"
git commit -m "test(matching): add unit tests for score calculation"
 
# Push y PR
git push origin feature/EMP-123-matching-algorithm
# Crear PR en GitHub → develop
4.2 Checklist Pre-Commit
•	[ ] phpcs --standard=Drupal,DrupalPractice web/modules/custom/tu_modulo
•	[ ] phpstan analyse web/modules/custom/tu_modulo --level=6
•	[ ] ./vendor/bin/phpunit --testsuite=tu_modulo
•	[ ] drush cr (cache rebuild) funciona sin errores
•	[ ] La feature funciona en tu local
4.3 Code Review
Cada PR requiere:
•	1 aprobación de otro desarrollador
•	CI pasando (lint, tests, build)
•	Sin conflictos con develop
•	Descripción clara de qué hace y por qué
 
5. Documentación Técnica
5.1 Dónde Encontrar la Doc
Qué necesitas	Dónde buscar
Modelo de datos de una vertical	docs/XX_Vertical_NombreModulo_v1.docx
Endpoints de API	docs/03_Core_APIs_Contratos_v1.docx
Sistema de permisos	docs/04_Core_Permisos_RBAC_v1.docx
Integración Stripe	docs/134_Platform_Stripe_Billing_v1.docx
Sistema de IA	docs/128-130_AI_*.docx
CI/CD y deploys	docs/132_Platform_CICD_Pipeline_v1.docx
Índice completo	docs/141_Indice_Maestro_Consolidado_v1.docx
5.2 Cómo Leer una Especificación
Cada documento técnico sigue esta estructura:
•	Sección 1-2: Resumen y arquitectura general
•	Sección 3: Modelo de datos (entidades, campos, relaciones)
•	Sección 4-5: Servicios y lógica de negocio
•	Sección 6: APIs (endpoints, request/response)
•	Sección 7: Flujos ECA (automatizaciones)
•	Sección 8: UI/UX (wireframes, componentes)
•	Sección 9: Roadmap de sprints
5.3 Primera Semana - Lecturas Obligatorias
•	Día 1: Doc 01 (Esquema BD) + Doc 07 (Multi-Tenant)
•	Día 2: Doc 04 (RBAC) + Doc 06 (ECA Flows)
•	Día 3: Doc de tu vertical asignada (overview)
•	Día 4: Doc 131 (Infraestructura) + Doc 132 (CI/CD)
•	Día 5: Doc 134 (Stripe) si trabajas en billing
 
6. Comandos Útiles
6.1 Drush (CLI de Drupal)
# Cache
drush cr                    # Cache rebuild (usa MUCHO)
drush cc render             # Solo cache de render
 
# Configuración
drush cex                   # Exportar config a YAML
drush cim                   # Importar config desde YAML
drush cim --partial         # Import parcial
 
# Base de datos
drush updb                  # Ejecutar updates pendientes
drush sql-cli               # Entrar a MySQL CLI
drush sql-dump > backup.sql # Backup
 
# Usuarios
drush uli                   # Login link para admin
drush user:password admin newpass  # Cambiar password
 
# Módulos
drush en mi_modulo          # Habilitar módulo
drush pmu mi_modulo         # Deshabilitar módulo
 
# Debug
drush ws                    # Ver watchdog (logs)
drush php:cli               # REPL de PHP con Drupal cargado
6.2 Composer
composer install            # Instalar dependencias
composer update drupal/core-recommended --with-dependencies  # Update Drupal
composer require drupal/module_name   # Añadir módulo contrib
composer why-not drupal/module_name   # Por qué no se puede instalar
6.3 Testing
# Todos los tests
./vendor/bin/phpunit
 
# Solo un módulo
./vendor/bin/phpunit --testsuite=jaraba_empleabilidad
 
# Solo unit tests
./vendor/bin/phpunit --testsuite=unit
 
# Con coverage
./vendor/bin/phpunit --coverage-html coverage/
 
7. Contactos y Recursos
7.1 Equipo
Rol	Nombre	Contacto	Pregúntale sobre...
Product Owner	[Pepe Jaraba]	[email]	Requisitos, prioridades, negocio
Tech Lead	[Nombre]	[email]	Arquitectura, decisiones técnicas
DevOps	[Nombre]	[email]	Infra, deploys, CI/CD
QA Lead	[Nombre]	[email]	Testing, bugs, acceptance
7.2 Canales de Comunicación
•	Slack: #jaraba-dev (desarrollo), #jaraba-bugs (incidencias)
•	GitHub: Issues para tareas, PRs para código
•	Daily: 9:30 AM (15 min standup)
•	Sprint Planning: Lunes 10:00 AM (cada 2 semanas)
7.3 Recursos Externos
•	Drupal 11 Docs: https://www.drupal.org/docs
•	Drupal Commerce: https://docs.drupalcommerce.org/
•	Stripe API: https://stripe.com/docs/api
•	Claude API: https://docs.anthropic.com/
8. FAQ del Nuevo Desarrollador
¿Por qué Drupal y no Laravel/Symfony?
Drupal ofrece multi-tenancy nativo (Group module), Commerce integrado, y un ecosistema maduro para CMS + Commerce. Para este proyecto específico, reduce tiempo de desarrollo en 40%.
¿Qué hago si rompo algo?
1) No entres en pánico. 2) drush cr. 3) Si sigue roto, git stash y drush cim. 4) Pide ayuda en Slack.
¿Cuándo está algo 'terminado'?
Cuando: pasa los tests, tiene review aprobado, funciona en staging, y el PO da el OK. No antes.

¡Bienvenido al equipo! 🚀
