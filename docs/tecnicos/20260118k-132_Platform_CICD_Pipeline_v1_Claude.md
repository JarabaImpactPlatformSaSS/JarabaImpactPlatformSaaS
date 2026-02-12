CI/CD PIPELINE
Integración y Despliegue Continuo con GitHub Actions

Campo	Valor
Versión:	1.0
Fecha:	Enero 2026
Estado:	Ready for Implementation
Código:	132_Platform_CICD_Pipeline
Dependencias:	GitHub, Docker Hub, IONOS Server
 
1. Arquitectura del Pipeline
1.1 Flujo General
┌─────────────────────────────────────────────────────────────────────────────┐
│                         CI/CD PIPELINE FLOW                                 │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  DEVELOPER                                                                  │
│      │                                                                      │
│      │ git push                                                             │
│      ▼                                                                      │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                      GITHUB REPOSITORY                              │   │
│  │  main ──────────────────────────────────────────────► Production    │   │
│  │  develop ───────────────────────────────────────────► Staging       │   │
│  │  feature/* ─────────────────────────────────────────► PR Preview    │   │
│  └──────────────────────────────┬──────────────────────────────────────┘   │
│                                 │                                           │
│                                 │ Trigger                                   │
│                                 ▼                                           │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                     GITHUB ACTIONS                                  │   │
│  │                                                                     │   │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌───────────┐  │   │
│  │  │   LINT &    │  │    TEST     │  │    BUILD    │  │  DEPLOY   │  │   │
│  │  │   ANALYZE   │──▶│             │──▶│   DOCKER   │──▶│           │  │   │
│  │  │             │  │             │  │             │  │           │  │   │
│  │  │ • PHP CS    │  │ • PHPUnit   │  │ • Build     │  │ • Push    │  │   │
│  │  │ • ESLint    │  │ • Cypress   │  │   image     │  │   image   │  │   │
│  │  │ • PHPSTAN   │  │ • Behat     │  │ • Tag       │  │ • Deploy  │  │   │
│  │  │             │  │             │  │             │  │   to env  │  │   │
│  │  └─────────────┘  └─────────────┘  └─────────────┘  └───────────┘  │   │
│  │         │                │                │               │         │   │
│  │         └────────────────┴────────────────┴───────────────┘         │   │
│  │                              │                                      │   │
│  │                         On Failure                                  │   │
│  │                              │                                      │   │
│  │                    ┌─────────▼─────────┐                           │   │
│  │                    │  SLACK + EMAIL    │                           │   │
│  │                    │  NOTIFICATION     │                           │   │
│  │                    └───────────────────┘                           │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
1.2 Entornos
Entorno	Branch	URL	Deploy	Propósito
Development	feature/*	PR preview	Automático	Testing de features
Staging	develop	staging.jarabaimpact.com	Automático	QA, integración
Production	main	app.jarabaimpact.com	Manual approve	Producción
 
2. GitHub Actions Workflows
2.1 CI Workflow (ci.yml)
# .github/workflows/ci.yml
name: CI Pipeline
 
on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]
 
env:
  PHP_VERSION: '8.3'
  NODE_VERSION: '20'
  COMPOSER_CACHE_DIR: ~/.composer/cache
 
jobs:
  # ═══════════════════════════════════════════════════════════════
  # LINT & STATIC ANALYSIS
  # ═══════════════════════════════════════════════════════════════
  lint:
    name: Lint & Analyze
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ env.PHP_VERSION }}
          tools: composer, phpcs, phpstan
          coverage: none
      
      - name: Cache Composer
        uses: actions/cache@v4
        with:
          path: ${{ env.COMPOSER_CACHE_DIR }}
          key: composer-${{ hashFiles('**/composer.lock') }}
      
      - name: Install dependencies
        run: composer install --no-progress --prefer-dist
      
      - name: PHP CodeSniffer
        run: vendor/bin/phpcs --standard=Drupal,DrupalPractice web/modules/custom
      
      - name: PHPStan
        run: vendor/bin/phpstan analyse web/modules/custom --level=6
      
      - name: Setup Node
        uses: actions/setup-node@v4
        with:
          node-version: ${{ env.NODE_VERSION }}
          cache: 'npm'
      
      - name: ESLint
        run: |
          npm ci
          npm run lint
 
  # ═══════════════════════════════════════════════════════════════
  # UNIT & INTEGRATION TESTS
  # ═══════════════════════════════════════════════════════════════
  test-unit:
    name: Unit Tests
    runs-on: ubuntu-latest
    needs: lint
    services:
      mariadb:
        image: mariadb:11.2
        env:
          MARIADB_ROOT_PASSWORD: root
          MARIADB_DATABASE: drupal_test
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ env.PHP_VERSION }}
          extensions: gd, pdo_mysql, redis
          coverage: xdebug
      
      - name: Install dependencies
        run: composer install --no-progress --prefer-dist
      
      - name: Run PHPUnit
        run: |
          vendor/bin/phpunit --configuration phpunit.xml \
            --coverage-clover coverage.xml \
            --testsuite unit
        env:
          SIMPLETEST_DB: mysql://root:root@127.0.0.1:3306/drupal_test
      
      - name: Upload coverage
        uses: codecov/codecov-action@v4
        with:
          files: coverage.xml
          flags: unittests
 
  test-integration:
    name: Integration Tests
    runs-on: ubuntu-latest
    needs: lint
    services:
      mariadb:
        image: mariadb:11.2
        env:
          MARIADB_ROOT_PASSWORD: root
          MARIADB_DATABASE: drupal_test
        ports:
          - 3306:3306
      redis:
        image: redis:7-alpine
        ports:
          - 6379:6379
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ env.PHP_VERSION }}
          extensions: gd, pdo_mysql, redis
      
      - name: Install dependencies
        run: composer install --no-progress --prefer-dist
      
      - name: Install Drupal
        run: |
          vendor/bin/drush site:install --yes \
            --db-url=mysql://root:root@127.0.0.1:3306/drupal_test
      
      - name: Run Integration Tests
        run: vendor/bin/phpunit --testsuite kernel
        env:
          SIMPLETEST_DB: mysql://root:root@127.0.0.1:3306/drupal_test
 
  # ═══════════════════════════════════════════════════════════════
  # E2E TESTS
  # ═══════════════════════════════════════════════════════════════
  test-e2e:
    name: E2E Tests
    runs-on: ubuntu-latest
    needs: [test-unit, test-integration]
    steps:
      - uses: actions/checkout@v4
      
      - name: Build and start containers
        run: |
          docker-compose -f docker-compose.test.yml up -d
          sleep 30  # Wait for services
      
      - name: Run Cypress
        uses: cypress-io/github-action@v6
        with:
          wait-on: 'http://localhost:8080'
          wait-on-timeout: 120
          browser: chrome
          spec: cypress/e2e/**/*.cy.js
      
      - name: Upload screenshots
        if: failure()
        uses: actions/upload-artifact@v4
        with:
          name: cypress-screenshots
          path: cypress/screenshots
 
2.2 Deploy Workflow (deploy.yml)
# .github/workflows/deploy.yml
name: Deploy
 
on:
  push:
    branches: [main, develop]
  workflow_dispatch:
    inputs:
      environment:
        description: 'Target environment'
        required: true
        default: 'staging'
        type: choice
        options:
          - staging
          - production
 
env:
  REGISTRY: ghcr.io
  IMAGE_NAME: jarabaimpact/drupal
 
jobs:
  # ═══════════════════════════════════════════════════════════════
  # BUILD DOCKER IMAGE
  # ═══════════════════════════════════════════════════════════════
  build:
    name: Build Docker Image
    runs-on: ubuntu-latest
    permissions:
      contents: read
      packages: write
    outputs:
      image_tag: ${{ steps.meta.outputs.tags }}
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Login to GitHub Container Registry
        uses: docker/login-action@v3
        with:
          registry: ${{ env.REGISTRY }}
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}
      
      - name: Extract metadata
        id: meta
        uses: docker/metadata-action@v5
        with:
          images: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}
          tags: |
            type=ref,event=branch
            type=sha,prefix=
            type=raw,value=latest,enable=${{ github.ref == 'refs/heads/main' }}
      
      - name: Build and push
        uses: docker/build-push-action@v5
        with:
          context: .
          push: true
          tags: ${{ steps.meta.outputs.tags }}
          labels: ${{ steps.meta.outputs.labels }}
          cache-from: type=gha
          cache-to: type=gha,mode=max
 
  # ═══════════════════════════════════════════════════════════════
  # DEPLOY TO STAGING
  # ═══════════════════════════════════════════════════════════════
  deploy-staging:
    name: Deploy to Staging
    runs-on: ubuntu-latest
    needs: build
    if: github.ref == 'refs/heads/develop'
    environment:
      name: staging
      url: https://staging.jarabaimpact.com
    
    steps:
      - name: Deploy to staging server
        uses: appleboy/ssh-action@v1.0.3
        with:
          host: ${{ secrets.STAGING_HOST }}
          username: ${{ secrets.SSH_USERNAME }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            cd /opt/jaraba
            docker-compose pull
            docker-compose up -d
            docker-compose exec -T drupal drush updb -y
            docker-compose exec -T drupal drush cim -y
            docker-compose exec -T drupal drush cr
      
      - name: Notify Slack
        uses: slackapi/slack-github-action@v1.25.0
        with:
          payload: |
            {
              "text": "✅ Deployed to staging: ${{ github.sha }}"
            }
        env:
          SLACK_WEBHOOK_URL: ${{ secrets.SLACK_WEBHOOK }}
 
  # ═══════════════════════════════════════════════════════════════
  # DEPLOY TO PRODUCTION
  # ═══════════════════════════════════════════════════════════════
  deploy-production:
    name: Deploy to Production
    runs-on: ubuntu-latest
    needs: build
    if: github.ref == 'refs/heads/main'
    environment:
      name: production
      url: https://app.jarabaimpact.com
    
    steps:
      - name: Create backup
        uses: appleboy/ssh-action@v1.0.3
        with:
          host: ${{ secrets.PROD_HOST }}
          username: ${{ secrets.SSH_USERNAME }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            cd /opt/jaraba
            ./scripts/backup.sh --label "pre-deploy-${{ github.sha }}"
      
      - name: Deploy to production
        uses: appleboy/ssh-action@v1.0.3
        with:
          host: ${{ secrets.PROD_HOST }}
          username: ${{ secrets.SSH_USERNAME }}
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            cd /opt/jaraba
            docker-compose exec -T drupal drush state:set system.maintenance_mode 1
            docker-compose pull
            docker-compose up -d
            docker-compose exec -T drupal drush updb -y
            docker-compose exec -T drupal drush cim -y
            docker-compose exec -T drupal drush cr
            docker-compose exec -T drupal drush state:set system.maintenance_mode 0
      
      - name: Health check
        run: |
          for i in {1..10}; do
            status=$(curl -s -o /dev/null -w "%{http_code}" https://app.jarabaimpact.com/health)
            if [ "$status" = "200" ]; then
              echo "Health check passed"
              exit 0
            fi
            sleep 10
          done
          echo "Health check failed"
          exit 1
      
      - name: Notify team
        uses: slackapi/slack-github-action@v1.25.0
        with:
          payload: |
            {
              "text": "🚀 Production deployed: ${{ github.sha }}"
            }
        env:
          SLACK_WEBHOOK_URL: ${{ secrets.SLACK_WEBHOOK }}
 
3. Configuración de Secrets
Secret	Descripción	Entorno
SSH_PRIVATE_KEY	Clave SSH para acceso a servidores	All
SSH_USERNAME	Usuario SSH (deploy)	All
STAGING_HOST	IP/hostname del servidor staging	Staging
PROD_HOST	IP/hostname del servidor producción	Production
SLACK_WEBHOOK	URL del webhook de Slack	All
CODECOV_TOKEN	Token para cobertura de código	All
STRIPE_TEST_KEY	Stripe test key para tests	Staging
STRIPE_LIVE_KEY	Stripe live key	Production
4. Dockerfile de Producción
# Dockerfile
FROM php:8.3-fpm-alpine AS base
 
# Install dependencies
RUN apk add --no-cache \
    nginx \
    mariadb-client \
    redis \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql zip intl opcache
 
# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
 
# Production stage
FROM base AS production
 
WORKDIR /var/www/html
 
# Copy application
COPY --chown=www-data:www-data . .
 
# Install dependencies (no dev)
RUN composer install --no-dev --optimize-autoloader --no-interaction
 
# PHP production config
COPY docker/php/php-prod.ini /usr/local/etc/php/conf.d/
 
# Nginx config
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
 
EXPOSE 80
 
CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]
5. Checklist de Implementación
•	[ ] Crear repositorio GitHub privado
•	[ ] Configurar branch protection en main y develop
•	[ ] Añadir todos los secrets en GitHub Settings
•	[ ] Crear archivo ci.yml en .github/workflows/
•	[ ] Crear archivo deploy.yml en .github/workflows/
•	[ ] Configurar environments en GitHub (staging, production)
•	[ ] Añadir required reviewers para production
•	[ ] Configurar Slack webhook para notificaciones
•	[ ] Test del pipeline completo en feature branch
•	[ ] Documentar proceso para el equipo

--- Fin del Documento ---
