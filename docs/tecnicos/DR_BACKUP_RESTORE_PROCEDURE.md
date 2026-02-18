# Disaster Recovery: Backup & Restore Procedure

**Jaraba Impact Platform SaaS**
Version: 1.0
Last updated: 2026-02-18
Classification: INTERNAL - Operations Team

---

## Table of Contents

1. [Backup Strategy](#1-backup-strategy)
2. [Backup Procedure](#2-backup-procedure)
3. [Restore Procedure](#3-restore-procedure)
4. [Verification Checklist](#4-verification-checklist)
5. [RTO/RPO Targets](#5-rtorpo-targets)
6. [Contact & Escalation Matrix](#6-contact--escalation-matrix)

---

## 1. Backup Strategy

### 1.1 Backup Scope

| Component            | What is backed up                                      | Frequency       | Retention  |
|----------------------|--------------------------------------------------------|-----------------|------------|
| **Database**         | Full MariaDB dump (all Drupal tables + custom schemas) | Every 6 hours   | 30 days    |
| **Public Files**     | `web/sites/default/files/` (uploads, media, generated) | Daily incremental| 30 days    |
| **Private Files**    | `private://` directory (sensitive documents, exports)  | Daily incremental| 90 days    |
| **Configuration**    | Drupal config export (`config/sync/`)                  | Every deploy     | Git history|
| **Codebase**         | Full application code                                  | Every commit     | Git history|
| **Redis Snapshot**   | RDB dump (if persistent cache data needed)             | Daily            | 7 days     |
| **SSL Certificates** | TLS certs and private keys                             | On renewal       | Until expiry|

### 1.2 Backup Storage

- **Primary**: Object storage (S3-compatible) in same region as production.
- **Secondary**: Cross-region replication to a geographically separate data center.
- **Encryption**: All backups encrypted at rest using AES-256. In transit via TLS 1.3.
- **Access**: Backup buckets restricted to operations team IAM roles only.

### 1.3 Backup Naming Convention

```
jaraba-{component}-{environment}-{YYYYMMDD}-{HHMMSS}.{ext}
```

Examples:
- `jaraba-db-production-20260218-060000.sql.gz`
- `jaraba-files-production-20260218-020000.tar.gz`
- `jaraba-config-production-20260218-143000.tar.gz`

### 1.4 Automated Backup Schedule

| Time (UTC) | Action                                    |
|------------|-------------------------------------------|
| 00:00      | Full database dump + files incremental    |
| 06:00      | Database dump                             |
| 12:00      | Database dump                             |
| 18:00      | Database dump + Redis RDB snapshot        |

Automation is managed via system cron. See `scripts/setup-cron-production.sh` for the cron configuration.

---

## 2. Backup Procedure

### 2.1 Database Backup

```bash
#!/usr/bin/env bash
# Database backup using Drush
set -euo pipefail

TIMESTAMP=$(date +%Y%m%d-%H%M%S)
BACKUP_DIR="/backups/db"
FILENAME="jaraba-db-production-${TIMESTAMP}.sql"

# Export database
cd /var/www/jaraba
vendor/bin/drush sql:dump \
  --gzip \
  --result-file="${BACKUP_DIR}/${FILENAME}" \
  --extra-dump="--single-transaction --quick --lock-tables=false"

# Verify dump integrity
gunzip -t "${BACKUP_DIR}/${FILENAME}.gz"
echo "[OK] Database backup created: ${FILENAME}.gz"

# Upload to object storage
aws s3 cp "${BACKUP_DIR}/${FILENAME}.gz" \
  s3://jaraba-backups/db/ \
  --storage-class STANDARD_IA \
  --sse AES256

# Clean local backups older than 7 days
find "$BACKUP_DIR" -name "*.sql.gz" -mtime +7 -delete
```

### 2.2 Files Backup

```bash
#!/usr/bin/env bash
# Incremental files backup using rsync
set -euo pipefail

TIMESTAMP=$(date +%Y%m%d-%H%M%S)
BACKUP_DIR="/backups/files"
SOURCE="/var/www/jaraba/web/sites/default/files/"
PRIVATE_SOURCE="/var/www/jaraba/private/"

# Public files - incremental sync
rsync -az --delete \
  --exclude='css/' \
  --exclude='js/' \
  --exclude='php/' \
  --exclude='styles/.tmp/' \
  "$SOURCE" "${BACKUP_DIR}/public/"

# Private files - incremental sync
rsync -az --delete \
  "$PRIVATE_SOURCE" "${BACKUP_DIR}/private/"

# Archive and upload
tar czf "${BACKUP_DIR}/jaraba-files-production-${TIMESTAMP}.tar.gz" \
  -C "${BACKUP_DIR}" public/ private/

aws s3 cp "${BACKUP_DIR}/jaraba-files-production-${TIMESTAMP}.tar.gz" \
  s3://jaraba-backups/files/ \
  --storage-class STANDARD_IA \
  --sse AES256

echo "[OK] Files backup completed: ${TIMESTAMP}"
```

### 2.3 Configuration Export

```bash
#!/usr/bin/env bash
# Configuration export (typically run as part of deployment)
set -euo pipefail

cd /var/www/jaraba

# Export active configuration
vendor/bin/drush config:export -y

# Verify config directory
CONFIG_DIR="config/sync"
CONFIG_COUNT=$(find "$CONFIG_DIR" -name "*.yml" | wc -l)
echo "[OK] Exported $CONFIG_COUNT configuration files"

# Commit to version control (if not in CI/CD pipeline)
# git add config/sync/ && git commit -m "chore: config export $(date +%Y%m%d)"
```

### 2.4 Manual On-Demand Backup

For pre-deployment or pre-migration backups:

```bash
cd /var/www/jaraba

# Full manual backup with labeled tag
LABEL="pre-deploy-v2.5"
vendor/bin/drush sql:dump \
  --gzip \
  --result-file="/backups/manual/jaraba-db-${LABEL}.sql"

rsync -az web/sites/default/files/ "/backups/manual/files-${LABEL}/"
vendor/bin/drush config:export --destination="/backups/manual/config-${LABEL}"

echo "[OK] Manual backup '${LABEL}' completed"
```

---

## 3. Restore Procedure

### 3.1 Pre-Restore Preparation

1. **Notify the team** via the escalation matrix (Section 6).
2. **Put the site in maintenance mode**:
   ```bash
   vendor/bin/drush state:set system.maintenance_mode 1
   vendor/bin/drush cr
   ```
3. **Identify the correct backup** to restore from:
   ```bash
   # List available backups
   aws s3 ls s3://jaraba-backups/db/ --recursive | sort -k1,2 | tail -20
   ```
4. **Take a backup of the CURRENT state** before restoring (so you can revert if needed):
   ```bash
   vendor/bin/drush sql:dump --gzip --result-file="/backups/pre-restore-safety.sql"
   ```

### 3.2 Step 1: Restore Database

```bash
#!/usr/bin/env bash
set -euo pipefail

BACKUP_FILE="$1"  # Path to .sql.gz file

cd /var/www/jaraba

# Download from object storage if needed
# aws s3 cp s3://jaraba-backups/db/${BACKUP_FILE} /backups/restore/

# Drop existing database and reimport
vendor/bin/drush sql:drop -y

# Import the backup
gunzip -c "$BACKUP_FILE" | vendor/bin/drush sql:cli

echo "[OK] Database restored from: $BACKUP_FILE"
```

### 3.3 Step 2: Restore Files

```bash
#!/usr/bin/env bash
set -euo pipefail

FILES_BACKUP="$1"  # Path to .tar.gz file

cd /var/www/jaraba

# Extract files backup
tar xzf "$FILES_BACKUP" -C /tmp/restore-files/

# Restore public files
rsync -az --delete \
  /tmp/restore-files/public/ \
  web/sites/default/files/

# Restore private files
rsync -az --delete \
  /tmp/restore-files/private/ \
  private/

# Fix permissions
chown -R www-data:www-data web/sites/default/files/
chmod -R 755 web/sites/default/files/

echo "[OK] Files restored"
```

### 3.4 Step 3: Import Configuration

```bash
cd /var/www/jaraba

# Import configuration from sync directory
vendor/bin/drush config:import -y

# If restoring from a specific config backup:
# vendor/bin/drush config:import --source=/backups/manual/config-LABEL -y

echo "[OK] Configuration imported"
```

### 3.5 Step 4: Post-Restore Operations

```bash
cd /var/www/jaraba

# Run database updates (in case schema changed)
vendor/bin/drush updatedb -y

# Rebuild caches
vendor/bin/drush cr

# Rebuild entity definitions
vendor/bin/drush entity:updates -y 2>/dev/null || true

# Rebuild search indexes (if applicable)
vendor/bin/drush search-api:reset-tracker --all 2>/dev/null || true
vendor/bin/drush search-api:index 2>/dev/null || true

# Disable maintenance mode
vendor/bin/drush state:set system.maintenance_mode 0
vendor/bin/drush cr

echo "[OK] Post-restore operations completed"
echo "[OK] Site is back online"
```

---

## 4. Verification Checklist

After completing a restore, execute every check below before declaring the restore successful.

### 4.1 Infrastructure Checks

| # | Check                                      | Command / Method                                                  | Expected Result         | Status |
|---|--------------------------------------------|-------------------------------------------------------------------|-------------------------|--------|
| 1 | Site is accessible (HTTP 200)              | `curl -sI https://platform.jaraba.com \| head -1`                | `HTTP/2 200`            | [ ]    |
| 2 | HTTPS certificate valid                    | `curl -vI https://platform.jaraba.com 2>&1 \| grep "SSL"`        | Valid cert chain        | [ ]    |
| 3 | Database connection healthy                | `drush sql:query "SELECT 1"`                                     | Returns `1`             | [ ]    |
| 4 | Redis connected                            | `redis-cli ping`                                                  | `PONG`                  | [ ]    |
| 5 | No PHP errors in log                       | `tail -50 /var/log/php/error.log`                                 | No fatal/critical       | [ ]    |

### 4.2 Application Checks

| # | Check                                      | Command / Method                                                  | Expected Result         | Status |
|---|--------------------------------------------|-------------------------------------------------------------------|-------------------------|--------|
| 6 | Admin login works                          | Log in as admin user via browser                                  | Dashboard loads         | [ ]    |
| 7 | Regular user login works                   | Log in as test user via browser                                   | Profile loads           | [ ]    |
| 8 | Homepage renders correctly                 | Visit / in browser                                                | No broken layout        | [ ]    |
| 9 | Content is visible                         | Navigate to known content (article, course, job)                  | Content displays        | [ ]    |
| 10| Content Hub dashboard loads                | Visit /content-hub authenticated                                  | Dashboard renders       | [ ]    |
| 11| Media/images display                       | Check images on content pages                                     | Images load (no 404)    | [ ]    |
| 12| File downloads work                        | Download a known file (credential PDF, export)                    | File downloads          | [ ]    |

### 4.3 Integration Checks

| # | Check                                      | Command / Method                                                  | Expected Result         | Status |
|---|--------------------------------------------|-------------------------------------------------------------------|-------------------------|--------|
| 13| API health endpoint                        | `curl https://platform.jaraba.com/api/v1/health`                  | `{"status":"ok"}`       | [ ]    |
| 14| Cron is running                            | `drush cron` or check `/admin/config/system/cron`                 | Last run < 15 min ago   | [ ]    |
| 15| Email delivery works                       | Trigger a test email (password reset or test module)              | Email received          | [ ]    |
| 16| Search index functional                    | Execute a search query                                            | Results returned        | [ ]    |
| 17| Stripe webhook reachable                   | Check Stripe dashboard webhook status                             | 200 response            | [ ]    |
| 18| AI/RAG endpoint responding                 | `curl -X POST /api/v1/jaraba-rag/query` with test payload        | Response received       | [ ]    |

### 4.4 Data Integrity Checks

| # | Check                                      | Command / Method                                                  | Expected Result         | Status |
|---|--------------------------------------------|-------------------------------------------------------------------|-------------------------|--------|
| 19| User count matches expectation             | `drush sql:query "SELECT COUNT(*) FROM users_field_data"`         | Within expected range   | [ ]    |
| 20| Node count matches expectation             | `drush sql:query "SELECT COUNT(*) FROM node_field_data"`          | Within expected range   | [ ]    |
| 21| Config matches expected state              | `drush config:status`                                             | No unexpected changes   | [ ]    |
| 22| No orphaned entities                       | `drush entity:check` (if available)                               | No orphans              | [ ]    |

### 4.5 Sign-Off

| Role                   | Name       | Sign-Off | Date       |
|------------------------|------------|----------|------------|
| Operations Lead        | __________ | [ ]      | __________ |
| Platform Lead          | __________ | [ ]      | __________ |
| QA / Verification Lead | __________ | [ ]      | __________ |

---

## 5. RTO/RPO Targets

### 5.1 Definitions

- **RTO (Recovery Time Objective)**: Maximum acceptable time from incident detection to full service restoration.
- **RPO (Recovery Point Objective)**: Maximum acceptable amount of data loss measured in time.

### 5.2 Targets by Severity

| Severity | Scenario                           | RTO Target | RPO Target | Backup Source              |
|----------|------------------------------------|------------|------------|----------------------------|
| P1       | Complete production failure        | 2 hours    | 6 hours    | Latest 6-hour DB dump      |
| P2       | Database corruption                | 1 hour     | 6 hours    | Latest 6-hour DB dump      |
| P3       | File system loss                   | 4 hours    | 24 hours   | Daily incremental backup   |
| P4       | Configuration drift / bad deploy   | 30 minutes | 0 (git)    | Git + config sync          |
| P5       | Single tenant data issue           | 4 hours    | 6 hours    | DB dump + tenant isolation |

### 5.3 RTO Breakdown (P1 Scenario)

| Phase                           | Time Budget |
|---------------------------------|-------------|
| Incident detection & alerting   | 15 min      |
| Team assembly & triage          | 15 min      |
| Backup identification & download| 20 min      |
| Database restore                | 20 min      |
| Files restore                   | 20 min      |
| Config import + cache rebuild   | 10 min      |
| Verification checklist          | 15 min      |
| Sign-off & maintenance mode off | 5 min       |
| **Total**                       | **~2 hours**|

### 5.4 RPO Improvement Roadmap

| Phase   | Target RPO | Method                                      | Timeline    |
|---------|------------|---------------------------------------------|-------------|
| Current | 6 hours    | Scheduled mysqldump every 6 hours            | Now         |
| Phase 2 | 1 hour     | Hourly incremental backups + WAL shipping    | Q2 2026     |
| Phase 3 | 5 minutes  | Real-time replication to standby             | Q3 2026     |
| Phase 4 | Near-zero  | Multi-region active-passive with auto failover| Q4 2026    |

---

## 6. Contact & Escalation Matrix

### 6.1 Escalation Levels

| Level | Trigger                                         | Response Time | Who is Notified             |
|-------|--------------------------------------------------|---------------|-----------------------------|
| L1    | Monitoring alert fires                           | Immediate     | On-call Operations Engineer |
| L2    | L1 cannot resolve within 30 min                  | 30 min        | Platform Lead + DevOps Lead |
| L3    | Service down > 1 hour or data loss confirmed     | 1 hour        | CTO + Engineering Manager   |
| L4    | Multi-tenant impact or regulatory implications    | 1.5 hours     | CEO + Legal + Compliance    |

### 6.2 Contact Directory

| Role                       | Name              | Phone            | Email                      | Availability     |
|----------------------------|-------------------|------------------|----------------------------|------------------|
| On-call Operations (L1)    | _[TBD]_           | _[TBD]_          | ops@jaraba.com             | 24/7 rotation    |
| Platform Lead (L2)         | _[TBD]_           | _[TBD]_          | platform@jaraba.com        | Business hours   |
| DevOps Lead (L2)           | _[TBD]_           | _[TBD]_          | devops@jaraba.com          | Business hours   |
| CTO (L3)                   | _[TBD]_           | _[TBD]_          | cto@jaraba.com             | Escalation only  |
| Engineering Manager (L3)   | _[TBD]_           | _[TBD]_          | eng-manager@jaraba.com     | Escalation only  |
| CEO (L4)                   | _[TBD]_           | _[TBD]_          | ceo@jaraba.com             | Escalation only  |

### 6.3 External Contacts

| Service                    | Provider           | Support Channel         | SLA               |
|----------------------------|--------------------|-------------------------|--------------------|
| Cloud Hosting              | _[TBD]_            | _[TBD]_                 | 99.9% uptime       |
| DNS Provider               | _[TBD]_            | _[TBD]_                 | 100% uptime        |
| CDN                        | _[TBD]_            | _[TBD]_                 | 99.9% uptime       |
| Stripe (Payments)          | Stripe             | support@stripe.com      | Per contract       |
| Email (SendGrid)           | Twilio SendGrid    | support@sendgrid.com    | 99.95% uptime      |

### 6.4 Communication Channels

- **Primary**: Slack channel `#incident-response`
- **Secondary**: Phone bridge (details in PagerDuty runbook)
- **Status Page**: status.jaraba.com (update within 15 min of confirmed incident)
- **Post-Mortem**: Document within 48 hours in `docs/tecnicos/auditorias/` directory

---

## Appendix A: Quick Reference Commands

```bash
# === BACKUP ===
# Full DB backup
drush sql:dump --gzip --result-file=/backups/db/manual-$(date +%s).sql

# Config export
drush config:export -y

# === RESTORE ===
# Enable maintenance mode
drush state:set system.maintenance_mode 1 && drush cr

# Restore DB
drush sql:drop -y && gunzip -c /path/to/backup.sql.gz | drush sql:cli

# Import config
drush config:import -y

# Post-restore
drush updatedb -y && drush cr && drush state:set system.maintenance_mode 0 && drush cr

# === VERIFY ===
# Quick health check
drush status && curl -sI https://platform.jaraba.com | head -1
```

---

## Document History

| Version | Date       | Author   | Changes                          |
|---------|------------|----------|----------------------------------|
| 1.0     | 2026-02-18 | Platform | Initial DR procedure document    |
