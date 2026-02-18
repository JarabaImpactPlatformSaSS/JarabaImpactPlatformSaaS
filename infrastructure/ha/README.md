# Jaraba Impact Platform - High Availability Infrastructure

**N3 Enterprise Class - Doc 197**

Production-grade HA stack for the Jaraba Impact Platform SaaS, providing zero-downtime deployments, automatic failover, and horizontal scaling.

## Architecture Overview

```
                    Internet
                       |
                   [HAProxy]          <- TLS termination, rate limiting, blue-green routing
                   /   |   \
              [App1] [App2] [App3]    <- Drupal application servers
                   \   |   /
                  [ProxySQL]          <- Read/write splitting, connection pooling, query cache
                   /   |   \
            [Galera1][Galera2][Galera3] <- Synchronous multi-master MariaDB
                       |
        [Redis Master] + [2 Replicas]  <- Cache, sessions, queues
              [Sentinel x3]            <- Automatic Redis failover
```

### Components

| Component | Purpose | Nodes |
|-----------|---------|-------|
| MariaDB Galera | Synchronous multi-master database cluster | 3 |
| ProxySQL | Read/write splitting, connection pooling, Galera health awareness | 1 (active) |
| HAProxy | L7 load balancing, TLS termination, blue-green traffic shifting | 1+ (keepalived for HA) |
| Redis + Sentinel | Cache/session store with automatic failover | 3 Redis + 3 Sentinel |
| Prometheus + Grafana | Metrics collection and dashboards | 1 each |

### Key Properties

- **RPO (Recovery Point Objective):** 0 - synchronous replication means zero data loss on node failure.
- **RTO (Recovery Time Objective):** < 30 seconds - automatic failover at every layer.
- **Deployment strategy:** Blue-green with instant rollback capability.
- **Session persistence:** Cookie-based affinity at HAProxy; Redis-backed sessions survive failover.

## File Structure

```
infrastructure/ha/
├── mariadb/
│   └── galera.cnf                  # MariaDB Galera Cluster configuration
├── proxysql/
│   └── proxysql.cnf                # ProxySQL read/write splitting config
├── haproxy/
│   └── haproxy.cfg                 # HAProxy load balancer config
├── redis/
│   ├── redis.conf                  # Redis server configuration
│   └── sentinel.conf               # Redis Sentinel auto-failover config
├── scripts/
│   └── blue-green-deploy.sh        # Blue-green deployment orchestrator
├── monitoring/
│   ├── prometheus-alerts.yml       # Prometheus alerting rules
│   └── grafana-dashboard.json      # Grafana HA dashboard
├── docker-compose.yml              # Local testing stack
└── README.md                       # This file
```

## Local Testing with Docker Compose

### Prerequisites

- Docker Engine 20.10+
- Docker Compose v2.0+
- 8 GB RAM minimum (16 GB recommended for the full stack)

### Quick Start

```bash
cd infrastructure/ha

# Start the full stack
docker-compose up -d

# Watch the logs
docker-compose logs -f

# Check cluster health
docker-compose logs healthcheck

# Access services
# - HAProxy stats:    http://localhost:8404/stats (admin/admin_dev)
# - ProxySQL admin:   mysql -h 127.0.0.1 -P 6032 -u admin -padmin_dev
# - ProxySQL MySQL:   mysql -h 127.0.0.1 -P 6033 -u drupal -pdrupal_dev
# - Galera node1:     mysql -h 127.0.0.1 -P 3307 -u root -pjaraba_root_dev
# - Redis:            redis-cli -h 127.0.0.1 -p 6379 -a redis_dev
# - Sentinel:         redis-cli -h 127.0.0.1 -p 26379
# - Grafana:          http://localhost:3000 (admin/admin_dev)
# - Prometheus:       http://localhost:9090
```

### Verifying Galera Cluster

```bash
# Check cluster size (should be 3)
docker exec galera-node1 mariadb -u root -pjaraba_root_dev \
  -e "SHOW STATUS LIKE 'wsrep_cluster_size';"

# Check node state (should be "Synced")
docker exec galera-node1 mariadb -u root -pjaraba_root_dev \
  -e "SHOW STATUS LIKE 'wsrep_local_state_comment';"

# Test write replication
docker exec galera-node1 mariadb -u root -pjaraba_root_dev drupal \
  -e "CREATE TABLE IF NOT EXISTS ha_test (id INT AUTO_INCREMENT PRIMARY KEY, ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP);"
docker exec galera-node1 mariadb -u root -pjaraba_root_dev drupal \
  -e "INSERT INTO ha_test VALUES ();"
docker exec galera-node3 mariadb -u root -pjaraba_root_dev drupal \
  -e "SELECT * FROM ha_test;"
```

### Verifying Redis Sentinel

```bash
# Check master identity
docker exec sentinel1 redis-cli -p 26379 sentinel master jaraba-master

# Simulate master failure
docker stop redis-master

# Verify failover (wait ~10 seconds)
docker exec sentinel1 redis-cli -p 26379 sentinel get-master-addr-by-name jaraba-master

# Restart old master (rejoins as replica)
docker start redis-master
```

### Cleanup

```bash
docker-compose down -v
```

## Production Deployment Checklist

### Pre-Deployment

- [ ] All environment variables populated in secrets manager (no defaults)
- [ ] TLS certificates generated and placed at `/etc/haproxy/certs/jaraba.pem`
- [ ] DNS records created for `node{1,2,3}.db.jaraba.io`, `app{1,2,3}.jaraba.io`, `redis-master.jaraba.io`
- [ ] Firewall rules: Galera ports 3306, 4444, 4567, 4568 open between DB nodes only
- [ ] Firewall rules: Redis 6379 and Sentinel 26379 open between Redis nodes only
- [ ] ProxySQL monitor user created: `GRANT USAGE ON *.* TO 'monitor'@'%';`
- [ ] MariaDB replication user created: `GRANT RELOAD, PROCESS, LOCK TABLES, REPLICATION CLIENT ON *.* TO 'repl'@'%';`
- [ ] InnoDB buffer pool sized to ~70% of available RAM per DB node
- [ ] Redis maxmemory set to ~70% of available RAM per Redis node

### Galera Bootstrap

```bash
# On node1 (bootstrap node ONLY - first time or after full cluster failure):
galera_new_cluster

# On node2 and node3:
systemctl start mariadb
```

### Post-Deployment Verification

- [ ] `SHOW STATUS LIKE 'wsrep_cluster_size'` returns 3 on all nodes
- [ ] `SHOW STATUS LIKE 'wsrep_ready'` returns ON on all nodes
- [ ] ProxySQL admin: `SELECT * FROM stats_mysql_connection_pool;` shows all backends ONLINE
- [ ] HAProxy stats page shows all backends UP (green)
- [ ] Redis Sentinel: `sentinel master jaraba-master` shows correct master
- [ ] Prometheus targets all UP
- [ ] Grafana dashboard loading with live data
- [ ] Blue-green deploy test: `./scripts/blue-green-deploy.sh deploy main`

### Monitoring Alert Channels

Configure Prometheus Alertmanager to route alerts:
- **critical + pager:** PagerDuty / Opsgenie (immediate page)
- **critical:** Slack #jaraba-infra-critical
- **warning:** Slack #jaraba-infra-warnings

## Environment Variables Reference

| Variable | Component | Description |
|----------|-----------|-------------|
| `MYSQL_ROOT_PASSWORD` | Galera | MariaDB root password |
| `DRUPAL_DB_USER` | ProxySQL, Galera | Application database user |
| `DRUPAL_DB_PASSWORD` | ProxySQL, Galera | Application database password |
| `REPLICATION_USER` | Galera | SST replication user |
| `REPLICATION_PASSWORD` | Galera | SST replication password |
| `MONITOR_USER` | ProxySQL | Health check user |
| `MONITOR_PASSWORD` | ProxySQL | Health check password |
| `PROXYSQL_ADMIN_PASSWORD` | ProxySQL | Admin interface password |
| `REDIS_PASSWORD` | Redis, Sentinel | Redis authentication password |
| `HAPROXY_STATS_USER` | HAProxy | Stats page username |
| `HAPROXY_STATS_PASSWORD` | HAProxy | Stats page password |
