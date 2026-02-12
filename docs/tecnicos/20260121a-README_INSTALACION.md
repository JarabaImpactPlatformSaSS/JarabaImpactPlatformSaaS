# 🚀 Copiloto de Emprendimiento v2.0

## Sistema de Asistencia Inteligente para Validación de Modelos de Negocio

**Programa Andalucía +ei | Jaraba Impact Platform**

---

## 📋 Índice

1. [Descripción General](#descripción-general)
2. [Requisitos del Sistema](#requisitos-del-sistema)
3. [Arquitectura](#arquitectura)
4. [Instalación](#instalación)
5. [Configuración](#configuración)
6. [Uso de la API](#uso-de-la-api)
7. [Componentes Frontend](#componentes-frontend)
8. [Pruebas](#pruebas)
9. [Despliegue](#despliegue)
10. [Troubleshooting](#troubleshooting)

---

## 📖 Descripción General

El Copiloto de Emprendimiento es un sistema de IA conversacional diseñado para asistir a emprendedores en la validación sistemática de sus modelos de negocio utilizando metodologías como:

- **Efectuación** (Sarasvathy)
- **Customer Development** (Steve Blank)
- **Business Model Canvas** (Osterwalder)
- **Testing Business Ideas** (Strategyzer)
- **La Empresa Invencible** (Osterwalder)

### Características principales

| Característica | Descripción |
|---------------|-------------|
| 🤖 **Chat Inteligente** | 5 modos adaptativos según contexto del emprendedor |
| 📊 **Dashboard BMC** | Visualización en tiempo real de validación por bloque |
| 🧪 **44 Experimentos** | Biblioteca completa de técnicas de validación |
| 🎯 **Priorizador de Hipótesis** | Algoritmo de priorización automática |
| 💚 **Soporte Emocional** | Detección de bloqueos y kit de primeros auxilios |
| 🎮 **Gamificación** | Sistema de puntos de impacto |

---

## 💻 Requisitos del Sistema

### Backend

| Componente | Versión Mínima | Recomendada |
|------------|----------------|-------------|
| PHP | 8.1 | 8.2+ |
| Drupal | 10.x | 11.x |
| MySQL/MariaDB | 8.0 / 10.5 | 8.0+ / 10.6+ |
| Composer | 2.0 | 2.5+ |
| Node.js | 18.x | 20.x |

### Frontend

| Componente | Versión |
|------------|---------|
| React | 18.x |
| Tailwind CSS | 3.x |
| Lucide React | 0.263+ |

### Servicios Externos

| Servicio | Propósito |
|----------|-----------|
| API de Claude (Anthropic) | Motor de IA |
| Redis (opcional) | Cache de sesiones |

---

## 🏗️ Arquitectura

```
┌─────────────────────────────────────────────────────────────────┐
│                        CAPA DE PRESENTACIÓN                      │
├───────────────────┬────────────────────┬────────────────────────┤
│  Chat Widget      │  Dashboard BMC     │  Herramientas          │
│  (React)          │  (React)           │  (HTML/React)          │
└───────────────────┴────────────────────┴────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                        CAPA DE SERVICIOS                         │
├────────────┬────────────┬────────────┬────────────┬─────────────┤
│ Copilot    │ Context    │ Experiment │ Hypothesis │ BMC         │
│ Engine     │ Builder    │ Library    │ Prioritizer│ Validation  │
└────────────┴────────────┴────────────┴────────────┴─────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                        CAPA DE DATOS                             │
├────────────────────────┬────────────────────────────────────────┤
│  Drupal 11 + MySQL     │  Claude API (Anthropic)                │
│  - entrepreneur_profile │  - Procesamiento NLP                   │
│  - hypothesis          │  - Generación de respuestas            │
│  - experiment          │  - Detección de emociones              │
│  - bmc_validation_state│                                        │
└────────────────────────┴────────────────────────────────────────┘
```

---

## 📦 Instalación

### Paso 1: Clonar el repositorio

```bash
git clone https://github.com/jaraba-impact/copiloto-emprendimiento.git
cd copiloto-emprendimiento
```

### Paso 2: Instalar dependencias PHP

```bash
composer install
```

### Paso 3: Configurar base de datos

```bash
# Crear la base de datos
mysql -u root -p -e "CREATE DATABASE copiloto_ei CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Ejecutar migraciones
mysql -u root -p copiloto_ei < database/migraciones_sql_copiloto_v2.sql
```

### Paso 4: Instalar módulo Drupal

```bash
# Copiar módulo al directorio de módulos
cp -r drupal_module/copilot_integration web/modules/custom/

# Habilitar el módulo
drush en copilot_integration -y

# Limpiar caché
drush cr
```

### Paso 5: Instalar dependencias frontend

```bash
cd frontend
npm install
npm run build
```

### Paso 6: Importar biblioteca de experimentos

```bash
drush copilot:import-experiments database/experiment_library_complete.json
```

---

## ⚙️ Configuración

### Variables de entorno

Crear archivo `.env` en la raíz del proyecto:

```env
# =============================================================================
# CONFIGURACIÓN COPILOTO v2.0
# =============================================================================

# Base de datos
DB_HOST=localhost
DB_PORT=3306
DB_NAME=copiloto_ei
DB_USER=copiloto_user
DB_PASSWORD=tu_password_seguro

# Drupal
DRUPAL_BASE_URL=https://tu-dominio.com
DRUPAL_HASH_SALT=genera_un_hash_aleatorio_largo

# API de Claude (Anthropic)
ANTHROPIC_API_KEY=sk-ant-api03-xxxxxxxxxxxxxxxxxxxxxxxx
ANTHROPIC_MODEL=claude-3-5-sonnet-20241022
ANTHROPIC_MAX_TOKENS=4096

# Copiloto
COPILOT_STREAMING_ENABLED=true
COPILOT_MAX_CONVERSATION_LENGTH=50
COPILOT_SESSION_TIMEOUT=3600

# Redis (opcional, para cache)
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=

# Debug (solo desarrollo)
COPILOT_DEBUG=false
COPILOT_LOG_PROMPTS=false
```

### Configuración en Drupal Admin

1. Ir a `/admin/config/copilot`
2. Configurar:
   - API Key de Anthropic
   - Modelo de Claude a usar
   - Límites de tokens
   - Activar/desactivar streaming

### Configuración de permisos

Asignar permisos a roles en `/admin/people/permissions`:

| Permiso | Emprendedor | Facilitador | Admin |
|---------|-------------|-------------|-------|
| use copilot chat | ✅ | ✅ | ✅ |
| view bmc dashboard | ✅ | ✅ | ✅ |
| manage hypotheses | ✅ | ✅ | ✅ |
| manage experiments | ✅ | ✅ | ✅ |
| view experiment library | ✅ | ✅ | ✅ |
| administer copilot settings | ❌ | ❌ | ✅ |

---

## 🔌 Uso de la API

### Autenticación

Todas las peticiones requieren JWT token:

```bash
curl -X POST https://tu-dominio.com/api/copilot/chat \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"user_id": "uuid", "message": "Hola"}'
```

### Endpoints principales

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/copilot/chat` | Enviar mensaje al copiloto |
| GET | `/api/copilot/context/{userId}` | Obtener contexto del emprendedor |
| GET | `/api/bmc/validation/{userId}` | Estado de validación BMC |
| POST | `/api/hypotheses` | Crear hipótesis |
| POST | `/api/experiments` | Crear experimento (Test Card) |
| PATCH | `/api/experiments/{id}/result` | Registrar resultado (Learning Card) |
| GET | `/api/experiments/library` | Catálogo de 44 experimentos |
| POST | `/api/experiments/suggest` | Sugerir experimentos para hipótesis |

### Ejemplo: Enviar mensaje

```javascript
const response = await fetch('/api/copilot/chat', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify({
    user_id: '550e8400-e29b-41d4-a716-446655440000',
    session_id: 'session-123',
    message: 'No sé cuánto cobrar por mis servicios'
  })
});

const data = await response.json();
// {
//   response: "Entiendo esa duda, es muy común...",
//   mode_detected: "CFO_SINTETICO",
//   emotion_detected: "miedo_precio",
//   experiment_suggested: { id: 28, name: "Test A/B de Precio" }
// }
```

---

## 🎨 Componentes Frontend

### Chat Widget

```jsx
import CopilotChatWidget from './components/CopilotChatWidget';

function App() {
  return (
    <CopilotChatWidget
      entrepreneurId="uuid-del-emprendedor"
      entrepreneurName="María García"
      carril="IMPULSO"
      position="bottom-right"
      onExperimentSuggested={(exp) => console.log('Experimento:', exp)}
    />
  );
}
```

### Dashboard BMC

```jsx
import BMCValidationDashboard from './components/BMCValidationDashboard';

function Dashboard() {
  return (
    <BMCValidationDashboard
      entrepreneurId="uuid"
      validationData={bmcData}
      hypotheses={hypothesesList}
      onBlockClick={(blockId) => openBlockDetail(blockId)}
      onAddHypothesis={(blockId) => openHypothesisForm(blockId)}
    />
  );
}
```

### Props disponibles

#### CopilotChatWidget

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| entrepreneurId | string | requerido | UUID del emprendedor |
| entrepreneurName | string | 'Emprendedor' | Nombre para personalización |
| carril | 'IMPULSO' \| 'ACELERA' | 'IMPULSO' | Carril asignado |
| position | string | 'bottom-right' | Posición del widget |
| apiEndpoint | string | '/api/copilot/chat' | URL del API |
| onExperimentSuggested | function | null | Callback experimento |
| onModeChanged | function | null | Callback cambio modo |

#### BMCValidationDashboard

| Prop | Tipo | Default | Descripción |
|------|------|---------|-------------|
| entrepreneurId | string | requerido | UUID del emprendedor |
| validationData | array | [] | Estado de validación |
| hypotheses | array | [] | Lista de hipótesis |
| onBlockClick | function | null | Callback clic en bloque |
| onAddHypothesis | function | null | Callback añadir hipótesis |

---

## 🧪 Pruebas

### Ejecutar tests unitarios

```bash
# PHP/Drupal
./vendor/bin/phpunit web/modules/custom/copilot_integration/tests

# JavaScript
cd frontend && npm test
```

### Ejecutar tests de integración

```bash
./vendor/bin/phpunit --testsuite integration
```

### Test manual del Copiloto

```bash
# Enviar mensaje de prueba
curl -X POST http://localhost/api/copilot/chat \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": "test-user-id",
    "message": "Tengo miedo de cobrar por mis servicios"
  }'

# Respuesta esperada:
# - mode_detected: "CFO_SINTETICO" o "COACH_EMOCIONAL"
# - emotion_detected: "miedo_precio"
```

---

## 🚀 Despliegue

### Producción con Docker

```bash
# Build de la imagen
docker build -t copiloto-ei:v2.0 .

# Ejecutar con docker-compose
docker-compose up -d
```

### docker-compose.yml

```yaml
version: '3.8'
services:
  drupal:
    image: copiloto-ei:v2.0
    ports:
      - "80:80"
    environment:
      - DB_HOST=db
      - ANTHROPIC_API_KEY=${ANTHROPIC_API_KEY}
    depends_on:
      - db
      - redis

  db:
    image: mariadb:10.6
    environment:
      - MYSQL_DATABASE=copiloto_ei
      - MYSQL_USER=copiloto
      - MYSQL_PASSWORD=${DB_PASSWORD}
      - MYSQL_ROOT_PASSWORD=${DB_ROOT_PASSWORD}
    volumes:
      - db_data:/var/lib/mysql

  redis:
    image: redis:7-alpine

volumes:
  db_data:
```

### Checklist de despliegue

- [ ] Variables de entorno configuradas
- [ ] Base de datos migrada
- [ ] Módulo Drupal habilitado
- [ ] Frontend compilado
- [ ] Permisos configurados
- [ ] SSL/HTTPS activo
- [ ] Backups configurados
- [ ] Monitoreo activo

---

## 🔧 Troubleshooting

### Error: "API Key inválida"

```bash
# Verificar la API key
drush config:get copilot_integration.settings anthropic_api_key

# Actualizar
drush config:set copilot_integration.settings anthropic_api_key "sk-ant-..."
```

### Error: "Base de datos no encontrada"

```bash
# Verificar conexión
drush sql:connect

# Re-ejecutar migraciones
mysql -u root -p copiloto_ei < database/migraciones_sql_copiloto_v2.sql
```

### Chat no responde

1. Verificar logs: `drush watchdog:show --type=copilot_integration`
2. Verificar API de Anthropic: `curl https://api.anthropic.com/v1/messages`
3. Verificar permisos del usuario

### Dashboard BMC vacío

```bash
# Inicializar estado de validación
drush copilot:init-bmc USER_ID
```

---

## 📚 Documentación adicional

| Documento | Ubicación |
|-----------|-----------|
| Especificaciones técnicas | `docs/Especificaciones_Tecnicas_Copiloto_v2.docx` |
| Prompt maestro | `docs/copilot_prompt_master_v2.md` |
| OpenAPI/Swagger | `docs/openapi_copiloto_v2.yaml` |
| Catálogo de experimentos | `database/experiment_library_complete.json` |

---

## 👥 Soporte

- **Email técnico:** tech@jaraba.io
- **Documentación:** https://docs.jaraba.io/copiloto
- **Issues:** https://github.com/jaraba-impact/copiloto/issues

---

## 📄 Licencia

Proprietary - Jaraba Impact Platform © 2026

---

*Última actualización: Enero 2026*
