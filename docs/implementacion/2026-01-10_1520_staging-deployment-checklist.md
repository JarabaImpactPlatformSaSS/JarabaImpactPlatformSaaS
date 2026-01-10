# Staging Deployment Checklist

> **Fecha**: 2026-01-10  
> **Proyecto**: Jaraba SaaS Platform

## Pre-Deployment

### Code Review
- [ ] All Phase 1-7 tasks completed and verified
- [ ] No uncommitted changes in working directory
- [ ] All new files added to git
- [ ] SCSS compiled to CSS
- [ ] No console.log() or debug statements

### Database
- [ ] Export current database state
- [ ] Document pending drush updb hooks
- [ ] Backup config exports

## Deployment Steps

### 1. Git Operations
```bash
git add -A
git commit -m "Phase 7: Post-sprint optimization - SCSS, responsive, docs"
git push origin develop
```

### 2. Staging Server
```bash
# SSH to staging
ssh staging-server

# Pull latest code
cd /var/www/jaraba-saas
git pull origin develop

# Composer install
composer install --no-dev

# Run database updates
drush updb -y

# Import config
drush cim -y

# Clear caches
drush cr
```

### 3. Post-Deployment Verification

| Check | Command/Action | Expected |
|-------|----------------|----------|
| Site loads | Visit homepage | 200 OK |
| Admin access | `/user/login` | Login works |
| Tenant dashboard | `/tenant/dashboard` | Renders correctly |
| Features admin | `/admin/structure/features` | Lists 7 features |
| AI Agents admin | `/admin/structure/ai-agents` | Lists 5 agents |
| CSS loaded | Check network tab | ecosistema-jaraba-core.css |
| Responsive | Resize browser | Cards stack properly |

### 4. Smoke Tests

- [ ] Create new tenant via onboarding
- [ ] Verify Group created
- [ ] Verify Domain created
- [ ] Edit Vertical - checkboxes show entities
- [ ] Save Vertical - no errors
- [ ] View tenant dashboard - metrics display

### 5. Rollback Plan

```bash
# If issues found:
git revert HEAD
drush cim -y
drush cr
```

## Environment-Specific Notes

### Staging Environment
- URL: `staging.jaraba.io`
- Database: Separate from production
- Stripe: Test mode only

### Production Checklist (Before Go-Live)
- [ ] Stripe production keys configured
- [ ] DNS configured for custom domains
- [ ] SSL certificates installed
- [ ] Backup strategy verified
- [ ] Monitoring alerts configured
