GO-LIVE RUNBOOK
Procedimiento de Lanzamiento a Producción

🚀 Checklist Ejecutivo para el Día D
Campo	Valor
Versión:	1.0
Fecha:	Enero 2026
Estado:	Ready to Execute
Código:	139_Platform_GoLive_Runbook
Prioridad:	🔴 CRÍTICO
 
1. Resumen del Go-Live
Este documento detalla el procedimiento paso a paso para lanzar el ecosistema Jaraba a producción. Diseñado para ejecutarse en un fin de semana con mínimo impacto.
1.1 Cronograma de Lanzamiento
Fase	Duración	Horario	Responsable
Pre-Go-Live (Viernes)	4 horas	14:00 - 18:00	DevOps + QA
Deploy & Migration (Sábado)	6 horas	06:00 - 12:00	DevOps
Validation (Sábado)	4 horas	12:00 - 16:00	QA + Product
Soft Launch (Sábado)	2 horas	16:00 - 18:00	Marketing
Monitoring (Sábado-Domingo)	24 horas	18:00 Sáb - 18:00 Dom	DevOps
Public Launch (Lunes)	Full day	09:00	Marketing + Sales

1.2 Equipo de Go-Live
Rol	Persona	Contacto	Responsabilidad
Go-Live Lead	[NOMBRE]	[TELÉFONO]	Coordinación general, decisión Go/No-Go
DevOps Lead	[NOMBRE]	[TELÉFONO]	Infraestructura, deploy, monitoring
QA Lead	[NOMBRE]	[TELÉFONO]	Validación funcional, smoke tests
Product Owner	[NOMBRE]	[TELÉFONO]	Criterios de aceptación, decisiones producto
Support Lead	[NOMBRE]	[TELÉFONO]	Preparación soporte, escalaciones
Marketing Lead	[NOMBRE]	[TELÉFONO]	Comunicación, launch campaign
 
2. Pre-Go-Live (Viernes 14:00-18:00)
2.1 Verificación de Infraestructura
☐	Servidor IONOS operativo y accesible
☐	Docker y todos los contenedores en estado 'healthy'
☐	Certificados SSL válidos (más de 30 días)
☐	DNS configurado correctamente en Cloudflare
☐	Cloudflare WAF activo con reglas correctas
☐	Backup de staging completado y verificado
2.2 Verificación de Servicios Externos
☐	Stripe en modo LIVE (no test) con productos creados
☐	Claude API key de producción configurada
☐	ActiveCampaign conectado y listas creadas
☐	Qdrant Cloud (si aplica) operativo
☐	Credenciales en .env verificadas
2.3 Preparación de Datos
☐	Base de datos de staging exportada
☐	Scripts de migración probados
☐	Datos de demo/test eliminados
☐	Usuarios admin de producción creados
☐	Contenido inicial (KB, skills) verificado
2.4 Comunicaciones
☐	Email de aviso a beta testers enviado
☐	Página de 'coming soon' preparada
☐	Equipo de soporte notificado
☐	Canal de comunicación de emergencia definido (Slack/WhatsApp)
 
3. Deploy & Migration (Sábado 06:00-12:00)
3.1 Activar Modo Mantenimiento
# 06:00 - Iniciar modo mantenimiento
ssh jaraba-prod
 
# Activar página de mantenimiento
cd /opt/jaraba
docker-compose exec drupal drush state:set system.maintenance_mode 1
 
# Verificar que muestra página de mantenimiento
curl -I https://app.jarabaimpact.com
# Debe retornar 503 Service Unavailable
3.2 Backup Final
# 06:15 - Backup completo antes de cualquier cambio
./scripts/backup.sh --full --label "pre-golive"
 
# Verificar backup
ls -la /backups/
aws s3 ls s3://jaraba-backups/pre-golive/
3.3 Deploy de Código
# 06:30 - Pull latest images
docker-compose pull
 
# Stop current stack
docker-compose down
 
# Start new stack
docker-compose up -d
 
# Verificar containers
docker ps
docker-compose logs -f --tail=100
3.4 Migraciones de Base de Datos
# 07:00 - Ejecutar migraciones pendientes
docker-compose exec drupal drush updb -y
docker-compose exec drupal drush cim -y
docker-compose exec drupal drush cr
 
# Verificar estado
docker-compose exec drupal drush status
3.5 Sincronización de Assets
# 07:30 - Sincronizar archivos si es necesario
rsync -avz --progress staging:/var/www/files/ /var/www/files/
 
# Regenerar estilos de imagen
docker-compose exec drupal drush image-flush --all
3.6 Configuración Final
# 08:00 - Configuraciones de producción
docker-compose exec drupal drush cset system.site name "Jaraba Impact Platform" -y
docker-compose exec drupal drush cset system.site mail "noreply@jarabaimpact.com" -y
 
# Limpiar caches
docker-compose exec drupal drush cr
 
# Indexar búsqueda
docker-compose exec drupal drush search-api:index
3.7 Desactivar Modo Mantenimiento
# 08:30 - Quitar mantenimiento
docker-compose exec drupal drush state:set system.maintenance_mode 0
 
# Verificar sitio accesible
curl -I https://app.jarabaimpact.com
# Debe retornar 200 OK
 
4. Validation (Sábado 12:00-16:00)
4.1 Smoke Tests Críticos
Test	URL/Acción	Resultado Esperado	✓
Homepage carga	https://app.jarabaimpact.com	200 OK, < 3s	☐
Login funciona	Hacer login con admin	Redirect a dashboard	☐
Registro nuevo usuario	Crear cuenta de prueba	Email de verificación recibido	☐
Checkout Stripe	Suscribirse a plan Starter	Pago procesado, suscripción activa	☐
Crear tenant	Registrar nuevo negocio	Tenant creado con subdomain	☐
Upload de archivo	Subir imagen de producto	Archivo guardado correctamente	☐
AI Chat funciona	Enviar pregunta al copilot	Respuesta generada	☐
Búsqueda funciona	Buscar producto existente	Resultados relevantes	☐
Email transaccional	Trigger email de prueba	Email recibido	☐
Webhook Stripe	Simular evento en Stripe	Webhook procesado (logs)	☐

4.2 Tests por Vertical
Empleabilidad
☐	Crear oferta de empleo
☐	Aplicar a oferta como candidato
☐	Matching engine devuelve resultados
☐	Dashboard de empleador funciona
AgroConecta
☐	Crear producto como productor
☐	Añadir al carrito como consumidor
☐	Completar checkout
☐	Verificar split de pago (Stripe Connect)
Emprendimiento
☐	Completar diagnóstico de negocio
☐	Generar plan de digitalización
☐	Reservar sesión de mentoría
4.3 Verificación de Performance
Métrica	Target	Actual	Status
TTFB (Time to First Byte)	< 200ms	____ms	☐
LCP (Largest Contentful Paint)	< 2.5s	____s	☐
FID (First Input Delay)	< 100ms	____ms	☐
CLS (Cumulative Layout Shift)	< 0.1	____	☐
Homepage Load Time	< 3s	____s	☐
API Response Time (p95)	< 500ms	____ms	☐
 
5. Go/No-Go Decision
5.1 Criterios de Go
Criterio	Requerido	Estado
100% Smoke tests pasados	Sí	☐
Performance dentro de targets	Sí	☐
Stripe procesando pagos	Sí	☐
AI responses funcionando	Sí	☐
Emails enviándose	Sí	☐
Monitoring operativo	Sí	☐
Rollback probado	Sí	☐
Equipo de soporte listo	Sí	☐

5.2 Criterios de No-Go
☐	Cualquier smoke test crítico fallando
☐	Pagos no se procesan correctamente
☐	Performance > 2x de targets
☐	Errores 500 recurrentes en logs
☐	Pérdida de datos detectada

5.3 Decisión
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│   DECISIÓN GO-LIVE:  ☐ GO    ☐ NO-GO                       │
│                                                             │
│   Fecha/Hora: _______________________                       │
│                                                             │
│   Aprobado por (Go-Live Lead): _______________________      │
│                                                             │
│   Notas: ________________________________________________   │
│   _______________________________________________________   │
│                                                             │
└─────────────────────────────────────────────────────────────┘
 
6. Soft Launch (Sábado 16:00-18:00)
6.1 Activación Gradual
☐	Invitar a 10-20 beta testers seleccionados
☐	Monitorizar logs en tiempo real
☐	Equipo disponible para soporte inmediato
☐	Recoger feedback inicial
6.2 Monitorización Activa
# Terminal 1: Logs de aplicación
docker-compose logs -f drupal
 
# Terminal 2: Métricas de sistema
htop
 
# Terminal 3: Errores en tiempo real
tail -f /var/log/nginx/error.log
 
# Grafana Dashboard
https://grafana.jarabaimpact.com/d/jaraba-prod
7. Rollback Procedure
Si se detecta un problema crítico, ejecutar rollback inmediato.
7.1 Rollback Rápido (< 5 min)
# Activar mantenimiento
docker-compose exec drupal drush state:set system.maintenance_mode 1
 
# Rollback a imagen anterior
docker-compose down
docker tag jaraba/drupal:11-prod jaraba/drupal:11-prod-failed
docker tag jaraba/drupal:11-prod-backup jaraba/drupal:11-prod
docker-compose up -d
 
# Verificar
curl -I https://app.jarabaimpact.com
7.2 Rollback Completo (< 30 min)
# Restore de base de datos
docker-compose exec mariadb mysql -u root -p jaraba < /backups/pre-golive/db.sql
 
# Restore de archivos
rsync -avz /backups/pre-golive/files/ /var/www/files/
 
# Limpiar caches
docker-compose exec drupal drush cr
 
# Desactivar mantenimiento
docker-compose exec drupal drush state:set system.maintenance_mode 0
 
8. Post-Launch Monitoring
8.1 Métricas a Vigilar (24-48h)
Métrica	Umbral Alerta	Umbral Crítico	Acción
Error Rate	> 1%	> 5%	Investigar logs, posible rollback
Response Time p95	> 1s	> 3s	Escalar recursos, optimizar
CPU Usage	> 70%	> 90%	Añadir containers
Memory Usage	> 80%	> 95%	Investigar leaks, reiniciar
Disk Usage	> 80%	> 95%	Limpiar logs, añadir storage
Failed Payments	> 2%	> 10%	Verificar Stripe, contactar soporte

8.2 Alertas Configuradas
☐	Email + SMS a DevOps lead en cualquier alerta crítica
☐	Slack notification en alertas warning
☐	PagerDuty integration para on-call (futuro)
9. Comunicación de Lanzamiento
9.1 Comunicaciones Internas
☐	Email a todo el equipo: "Estamos LIVE"
☐	Actualizar status page si existe
☐	Notificar a partners/inversores
9.2 Comunicaciones Externas
☐	Post en LinkedIn de Pepe Jaraba
☐	Tweet/Post de cuenta oficial
☐	Email a lista de espera
☐	Actualizar web corporativa con CTAs

10. Contactos de Emergencia
Servicio	Contacto	SLA	Escalación
IONOS Soporte	+49 721 XXX XXXX	24/7	Portal cliente
Stripe Soporte	Dashboard > Help	24/7	support@stripe.com
Cloudflare	Dashboard > Support	Enterprise	emergency@cloudflare.com
Anthropic (Claude)	support@anthropic.com	Business hours	Account manager

--- Fin del Documento ---
