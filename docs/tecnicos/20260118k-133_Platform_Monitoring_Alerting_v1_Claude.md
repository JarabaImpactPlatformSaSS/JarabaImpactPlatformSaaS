MONITORING & ALERTING
Observabilidad con Prometheus, Grafana y Loki

Campo	Valor
Versión:	1.0
Fecha:	Enero 2026
Código:	133_Platform_Monitoring_Alerting
 
1. Stack de Observabilidad
Componente	Tecnología	Puerto	Propósito
Métricas	Prometheus	9090	Recolección y almacenamiento de métricas
Visualización	Grafana	3000	Dashboards y exploración
Logs	Loki + Promtail	3100	Agregación de logs
Alertas	AlertManager	9093	Routing y notificación de alertas
Uptime	Blackbox Exporter	9115	Monitoreo de endpoints HTTP
Node Metrics	Node Exporter	9100	Métricas del servidor
2. Docker Compose Monitoring
# docker-compose.monitoring.yml
version: '3.8'
 
services:
  prometheus:
    image: prom/prometheus:v2.48.0
    container_name: prometheus
    restart: always
    command:
      - '--config.file=/etc/prometheus/prometheus.yml'
      - '--storage.tsdb.path=/prometheus'
      - '--storage.tsdb.retention.time=30d'
      - '--web.enable-lifecycle'
    volumes:
      - ./config/prometheus:/etc/prometheus
      - prometheus-data:/prometheus
    ports:
      - "9090:9090"
    networks:
      - jaraba-network
 
  grafana:
    image: grafana/grafana:10.2.2
    container_name: grafana
    restart: always
    environment:
      - GF_SECURITY_ADMIN_PASSWORD=${GRAFANA_PASSWORD}
      - GF_SERVER_ROOT_URL=https://grafana.jarabaimpact.com
      - GF_SMTP_ENABLED=true
      - GF_SMTP_HOST=smtp.sendgrid.net:587
    volumes:
      - grafana-data:/var/lib/grafana
      - ./config/grafana/provisioning:/etc/grafana/provisioning
    ports:
      - "3000:3000"
    networks:
      - jaraba-network
 
  loki:
    image: grafana/loki:2.9.2
    container_name: loki
    restart: always
    command: -config.file=/etc/loki/local-config.yaml
    volumes:
      - ./config/loki:/etc/loki
      - loki-data:/loki
    ports:
      - "3100:3100"
    networks:
      - jaraba-network
 
  promtail:
    image: grafana/promtail:2.9.2
    container_name: promtail
    restart: always
    volumes:
      - ./config/promtail:/etc/promtail
      - /var/log:/var/log:ro
      - /var/lib/docker/containers:/var/lib/docker/containers:ro
    command: -config.file=/etc/promtail/config.yml
    networks:
      - jaraba-network
 
  alertmanager:
    image: prom/alertmanager:v0.26.0
    container_name: alertmanager
    restart: always
    volumes:
      - ./config/alertmanager:/etc/alertmanager
    command:
      - '--config.file=/etc/alertmanager/alertmanager.yml'
    ports:
      - "9093:9093"
    networks:
      - jaraba-network
 
  node-exporter:
    image: prom/node-exporter:v1.7.0
    container_name: node-exporter
    restart: always
    command:
      - '--path.rootfs=/host'
    volumes:
      - '/:/host:ro,rslave'
    ports:
      - "9100:9100"
    networks:
      - jaraba-network
 
  blackbox-exporter:
    image: prom/blackbox-exporter:v0.24.0
    container_name: blackbox-exporter
    restart: always
    volumes:
      - ./config/blackbox:/etc/blackbox_exporter
    ports:
      - "9115:9115"
    networks:
      - jaraba-network
 
volumes:
  prometheus-data:
  grafana-data:
  loki-data:
 
networks:
  jaraba-network:
    external: true
 
3. Configuración de Prometheus
# config/prometheus/prometheus.yml
global:
  scrape_interval: 15s
  evaluation_interval: 15s
 
alerting:
  alertmanagers:
    - static_configs:
        - targets: ['alertmanager:9093']
 
rule_files:
  - '/etc/prometheus/alerts/*.yml'
 
scrape_configs:
  # Prometheus itself
  - job_name: 'prometheus'
    static_configs:
      - targets: ['localhost:9090']
 
  # Node Exporter (servidor)
  - job_name: 'node'
    static_configs:
      - targets: ['node-exporter:9100']
 
  # Drupal (PHP-FPM metrics)
  - job_name: 'drupal'
    static_configs:
      - targets: ['drupal:9253']
    metrics_path: /metrics
 
  # MariaDB
  - job_name: 'mariadb'
    static_configs:
      - targets: ['mariadb-exporter:9104']
 
  # Redis
  - job_name: 'redis'
    static_configs:
      - targets: ['redis-exporter:9121']
 
  # Nginx
  - job_name: 'nginx'
    static_configs:
      - targets: ['nginx-exporter:9113']
 
  # Blackbox (HTTP probes)
  - job_name: 'blackbox-http'
    metrics_path: /probe
    params:
      module: [http_2xx]
    static_configs:
      - targets:
          - https://app.jarabaimpact.com
          - https://app.jarabaimpact.com/health
          - https://api.jarabaimpact.com/api/v1/status
    relabel_configs:
      - source_labels: [__address__]
        target_label: __param_target
      - source_labels: [__param_target]
        target_label: instance
      - target_label: __address__
        replacement: blackbox-exporter:9115
4. Reglas de Alertas
# config/prometheus/alerts/jaraba.yml
groups:
  - name: jaraba-critical
    rules:
      # ═══ DISPONIBILIDAD ═══
      - alert: ServiceDown
        expr: probe_success == 0
        for: 2m
        labels:
          severity: critical
        annotations:
          summary: "Servicio caído: {{ $labels.instance }}"
          description: "El endpoint {{ $labels.instance }} no responde"
 
      - alert: HighErrorRate
        expr: |
          sum(rate(http_requests_total{status=~"5.."}[5m])) 
          / sum(rate(http_requests_total[5m])) > 0.05
        for: 5m
        labels:
          severity: critical
        annotations:
          summary: "Tasa de errores alta: {{ $value | humanizePercentage }}"
 
      # ═══ RECURSOS ═══
      - alert: HighCPUUsage
        expr: 100 - (avg(irate(node_cpu_seconds_total{mode="idle"}[5m])) * 100) > 85
        for: 10m
        labels:
          severity: warning
        annotations:
          summary: "CPU alto: {{ $value | humanize }}%"
 
      - alert: HighMemoryUsage
        expr: (1 - node_memory_MemAvailable_bytes / node_memory_MemTotal_bytes) * 100 > 90
        for: 5m
        labels:
          severity: critical
        annotations:
          summary: "Memoria alta: {{ $value | humanize }}%"
 
      - alert: DiskSpaceLow
        expr: (1 - node_filesystem_avail_bytes / node_filesystem_size_bytes) * 100 > 85
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "Disco al {{ $value | humanize }}%"
 
      # ═══ BASE DE DATOS ═══
      - alert: DatabaseConnectionsHigh
        expr: mysql_global_status_threads_connected > 400
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "Conexiones DB altas: {{ $value }}"
 
      - alert: DatabaseSlowQueries
        expr: rate(mysql_global_status_slow_queries[5m]) > 1
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "Queries lentas: {{ $value }}/s"
 
      # ═══ APLICACIÓN ═══
      - alert: HighResponseTime
        expr: histogram_quantile(0.95, rate(http_request_duration_seconds_bucket[5m])) > 2
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "Response time p95: {{ $value | humanizeDuration }}"
 
      - alert: QueueBacklog
        expr: drupal_queue_items_total > 1000
        for: 10m
        labels:
          severity: warning
        annotations:
          summary: "Cola con backlog: {{ $value }} items"
 
5. Configuración de AlertManager
# config/alertmanager/alertmanager.yml
global:
  smtp_smarthost: 'smtp.sendgrid.net:587'
  smtp_from: 'alerts@jarabaimpact.com'
  smtp_auth_username: 'apikey'
  smtp_auth_password: '${SENDGRID_API_KEY}'
 
route:
  group_by: ['alertname', 'severity']
  group_wait: 30s
  group_interval: 5m
  repeat_interval: 4h
  receiver: 'default'
  routes:
    # Críticas -> Slack + Email + SMS
    - match:
        severity: critical
      receiver: 'critical'
      continue: true
    
    # Warnings -> Slack
    - match:
        severity: warning
      receiver: 'slack-warnings'
 
receivers:
  - name: 'default'
    email_configs:
      - to: 'devops@jarabaimpact.com'
 
  - name: 'critical'
    slack_configs:
      - api_url: '${SLACK_WEBHOOK_URL}'
        channel: '#alerts-critical'
        title: '🚨 CRITICAL: {{ .GroupLabels.alertname }}'
        text: '{{ range .Alerts }}{{ .Annotations.summary }}{{ end }}'
    email_configs:
      - to: 'devops@jarabaimpact.com,cto@jarabaimpact.com'
    webhook_configs:
      # SMS via Twilio/similar
      - url: 'https://api.jarabaimpact.com/webhooks/alert-sms'
 
  - name: 'slack-warnings'
    slack_configs:
      - api_url: '${SLACK_WEBHOOK_URL}'
        channel: '#alerts-warnings'
        title: '⚠️ Warning: {{ .GroupLabels.alertname }}'
 
inhibit_rules:
  - source_match:
      severity: 'critical'
    target_match:
      severity: 'warning'
    equal: ['alertname']
6. Dashboards de Grafana
Dashboard	ID	Métricas Clave
Jaraba Overview	jaraba-main	Requests/s, Error rate, Response time, Active users
Infrastructure	jaraba-infra	CPU, Memory, Disk, Network
Database	jaraba-db	Connections, QPS, Slow queries, Replication lag
Business Metrics	jaraba-business	Signups, Subscriptions, Revenue, Churn
Vertical: Empleabilidad	jaraba-emp	Job posts, Applications, Matches
Vertical: AgroConecta	jaraba-agro	Orders, GMV, Producers active
7. Checklist de Implementación
•	[ ] Crear directorio config/ con todas las configuraciones
•	[ ] Deploy docker-compose.monitoring.yml
•	[ ] Configurar Grafana datasources (Prometheus, Loki)
•	[ ] Importar dashboards predefinidos
•	[ ] Configurar AlertManager con credenciales
•	[ ] Test de alertas (firing manual)
•	[ ] Configurar retención de datos (30 días Prometheus, 14 días Loki)
•	[ ] Documentar runbooks para cada alerta

--- Fin del Documento ---
