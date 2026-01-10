#!/bin/bash
# =============================================================================
# JARABA SAAS - IONOS DEPLOYMENT SCRIPT
# =============================================================================
# Usage: ./deploy.sh [--force]
# 
# This script deploys the latest changes from the main branch to IONOS.
# Run from the project root directory on IONOS server.
# =============================================================================

set -e

# Configuration
SITE_DIR="${HOME}/JarabaImpactPlatformSaaS"
BACKUP_DIR="${HOME}/backups"
PHP_CLI="/usr/bin/php8.4-cli"
DRUSH="${PHP_CLI} ${SITE_DIR}/vendor/bin/drush.php"
COMPOSER="${PHP_CLI} ${HOME}/bin/composer.phar"
DATE=$(date +%Y%m%d_%H%M%S)

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}🚀 JARABA SAAS - Starting Deployment${NC}"
echo "=================================================="
echo "Date: $(date)"
echo "Directory: ${SITE_DIR}"
echo ""

# Create backup directory if it doesn't exist
mkdir -p ${BACKUP_DIR}

# Step 1: Pre-deployment backup
echo -e "${YELLOW}📦 Step 1: Creating pre-deployment backup...${NC}"
cd ${SITE_DIR}
${DRUSH} sql-dump --gzip > "${BACKUP_DIR}/db_pre_deploy_${DATE}.sql.gz"
echo "   Backup saved: ${BACKUP_DIR}/db_pre_deploy_${DATE}.sql.gz"

# Step 2: Enable maintenance mode
echo -e "${YELLOW}🔧 Step 2: Enabling maintenance mode...${NC}"
${DRUSH} state:set system.maintenance_mode 1

# Step 3: Pull latest changes
echo -e "${YELLOW}📥 Step 3: Pulling latest changes from Git...${NC}"
git fetch origin
git reset --hard origin/main

# Step 4: Install dependencies
echo -e "${YELLOW}📦 Step 4: Installing Composer dependencies...${NC}"
${COMPOSER} install --no-dev --optimize-autoloader

# Step 5: Run database updates
echo -e "${YELLOW}🗄️ Step 5: Running database updates...${NC}"
${DRUSH} updatedb -y

# Step 6: Import configuration
echo -e "${YELLOW}⚙️ Step 6: Importing configuration...${NC}"
${DRUSH} config:import -y || echo "   No config changes to import"

# Step 7: Clear caches
echo -e "${YELLOW}🧹 Step 7: Clearing caches...${NC}"
${DRUSH} cache:rebuild

# Step 8: Disable maintenance mode
echo -e "${YELLOW}✅ Step 8: Disabling maintenance mode...${NC}"
${DRUSH} state:set system.maintenance_mode 0

# Summary
echo ""
echo "=================================================="
echo -e "${GREEN}🎉 DEPLOYMENT COMPLETE!${NC}"
echo "=================================================="
echo "Time: $(date)"
echo "Backup: ${BACKUP_DIR}/db_pre_deploy_${DATE}.sql.gz"
echo ""
echo "Verify at: https://plataformadeecosistemas.com"
echo ""

# Cleanup old backups (keep last 7 days)
find ${BACKUP_DIR} -name "db_pre_deploy_*.sql.gz" -mtime +7 -delete 2>/dev/null || true
echo "Old backups cleaned (kept last 7 days)"
