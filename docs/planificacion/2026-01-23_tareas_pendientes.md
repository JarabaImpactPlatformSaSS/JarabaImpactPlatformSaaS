# Tareas Pendientes - 2026-01-23

## Vertical Emprendimiento - Verificación Final

### 1. Pruebas Funcionales en Navegador
- [ ] **Mentorías** - Verificar flujo completo:
  - [ ] `/mentors` - Catálogo de mentores funciona
  - [ ] `/mentors/{id}` - Perfil público renderiza
  - [ ] Reserva de sesión - Calendario disponibilidad
  - [ ] Dashboard mentor - KPIs y pipeline

- [ ] **Business Tools** - Verificar flujo Canvas:
  - [ ] `/canvas/new` - Crear nuevo Canvas
  - [ ] Editor Canvas drag & drop funcionando
  - [ ] Análisis IA de coherencia
  - [ ] `/mvp` - Dashboard validación hipótesis

- [ ] **Copilot v2** - Verificar desbloqueo progresivo:
  - [ ] Usuario Semana 0 → solo ve DIME
  - [ ] Transición de semana desbloquea features
  - [ ] Widget chat flotante funciona
  - [ ] 5 modos responden correctamente

### 2. Datos de Prueba
- [ ] Crear 3-5 mentores de prueba con perfiles completos
- [ ] Crear 2-3 paquetes de mentoría por mentor
- [ ] Crear 1 Canvas de ejemplo con bloques poblados
- [ ] Crear 3-5 hipótesis MVP de ejemplo

### 3. Configuración Producción
- [ ] Stripe Connect - Verificar cuenta conectada en Dashboard
- [ ] Jitsi Meet - Decidir: público (meet.jit.si) vs servidor privado
- [ ] Verificar emails transaccionales funcionan (SendGrid/SMTP)

---

## Completado Hoy (2026-01-22)

### Sistema Límites IA por Plan ✅
- AIUsageLimitService implementado
- Dashboard Tenant con tarjeta Uso de IA
- CopilotController con bloqueo HTTP 429
- Email alerta al 80%

### FinOps Dashboard ✅
- 30+ iconos SVG creados (plug, heart, list, building, etc.)
- Emoticonos reemplazados por jaraba_icon()
- Colores visibles en fondo oscuro
- Todas las 8 features con iconos correctos

### Revisión Vertical Emprendimiento ✅
- jaraba_mentoring: 100% implementado (9 entidades, 4 servicios, 7 ECA automations)
- jaraba_business_tools: 100% implementado (6 entidades, 4 servicios, 6 ECA automations)
- jaraba_copilot_v2: 100% implementado (6 entidades, 8 servicios, feature unlock)
- Todos los módulos habilitados en Drupal
