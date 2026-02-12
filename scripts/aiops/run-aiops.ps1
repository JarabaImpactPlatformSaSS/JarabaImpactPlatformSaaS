# =============================================================================
# JARABA AIOPS - Main Runner
# =============================================================================
# Script principal que orquesta todo el pipeline de AIOps:
# 1. Recolectar métricas
# 2. Detectar anomalías
# 3. Predecir capacidad
# 4. Auto-remediar si es posible
# 5. Notificar si es necesario
#
# HORARIOS: Las acciones pesadas (cache rebuild, restart) solo se ejecutan:
#   - En horario de bajo tráfico (02:00-06:00)
#   - O cuando hay una emergencia CRÍTICA
#
# Sprint: Level 5 - Sprint 5 (Piloto AIOps)
# =============================================================================

param(
    [switch]$Verbose,
    [switch]$AutoRemediate,  # Ejecutar self-healing automáticamente
    [switch]$Notify,         # Enviar notificaciones
    [switch]$Force24x7       # Ignorar restricción de horario
)

$ErrorActionPreference = "Continue"

# Configuración de horarios de bajo impacto
$LOW_IMPACT_START = 2   # 02:00
$LOW_IMPACT_END = 6     # 06:00

function Test-LowImpactHours {
    $hour = (Get-Date).Hour
    return ($hour -ge $LOW_IMPACT_START -and $hour -lt $LOW_IMPACT_END)
}

function Test-CanExecuteHeavyAction {
    param([string]$Severity)
    
    # Emergencias críticas siempre se ejecutan
    if ($Severity -eq "CRITICAL") { return $true }
    
    # Si forzado 24x7, ejecutar
    if ($Force24x7) { return $true }
    
    # Solo en horario de bajo impacto
    return Test-LowImpactHours
}

function Write-Log {
    param([string]$Level, [string]$Message)
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $color = switch ($Level) {
        "INFO" { "Cyan" }
        "WARN" { "Yellow" }
        "ERROR" { "Red" }
        "OK" { "Green" }
        "ACTION" { "Magenta" }
        default { "White" }
    }
    Write-Host "[$timestamp] [$Level] $Message" -ForegroundColor $color
}

# =============================================================================
# MAIN
# =============================================================================

$startTime = Get-Date

Write-Host ""
Write-Host "============================================================" -ForegroundColor Magenta
Write-Host "  JARABA AIOPS - INTELLIGENT OPERATIONS PIPELINE" -ForegroundColor Magenta
Write-Host "============================================================" -ForegroundColor Magenta
Write-Host ""

# Step 1: Collect metrics
Write-Log "INFO" "Step 1/5: Collecting metrics..."
$metrics = & (Join-Path $PSScriptRoot "collect-metrics.ps1") -Verbose:$Verbose

# Step 2: Detect anomalies
Write-Log "INFO" "Step 2/5: Detecting anomalies..."
$anomalies = & (Join-Path $PSScriptRoot "detect-anomalies.ps1") -Verbose:$Verbose

# Step 2.5: Predict capacity (if enough data)
Write-Log "INFO" "Step 3/5: Predicting capacity..."
$predictions = & (Join-Path $PSScriptRoot "predict-capacity.ps1") -ForecastHours 24 -Verbose:$Verbose

# Step 4: Auto-remediate if enabled
if ($AutoRemediate -and $anomalies.Count -gt 0) {
    Write-Log "ACTION" "Step 4/5: Auto-remediating..."
    
    $inLowImpactHours = Test-LowImpactHours
    if (-not $inLowImpactHours -and -not $Force24x7) {
        Write-Log "INFO" "  Outside low-impact hours (02:00-06:00) - heavy actions restricted"
    }
    
    foreach ($anomaly in $anomalies) {
        switch ($anomaly.name) {
            "Database Container" {
                # DB unpause siempre se ejecuta (CRÍTICO)
                if ($anomaly.value -eq "PAUSED") {
                    Write-Log "ACTION" "Executing: docker unpause jarabasaas_database_1"
                    docker unpause jarabasaas_database_1 2>$null
                    Write-Log "OK" "Database container unpaused"
                }
            }
            "Site Availability" {
                # Site down es CRÍTICO
                Write-Log "ACTION" "Executing self-healing checks..."
                & (Join-Path $PSScriptRoot "..\self-healing\test-local.ps1") -All 2>$null
            }
            "Site Response Time" {
                # Cache rebuild solo en horario bajo o si es CRÍTICO
                if (Test-CanExecuteHeavyAction -Severity $anomaly.severity) {
                    Write-Log "ACTION" "Executing: drush cr (cache rebuild)"
                    docker exec jarabasaas_appserver_1 drush cr 2>$null
                    Write-Log "OK" "Cache rebuilt"
                } else {
                    Write-Log "INFO" "  Cache rebuild deferred (outside low-impact hours)"
                }
            }
            "Appserver Memory" {
                if ($anomaly.severity -eq "CRITICAL") {
                    Write-Log "WARN" "High memory detected - consider restarting PHP-FPM"
                }
            }
        }
    }
} else {
    Write-Log "INFO" "Step 4/5: Auto-remediation skipped (no anomalies or not enabled)"
}

# Step 4: Notify if enabled
if ($Notify -and $anomalies.Count -gt 0) {
    Write-Log "INFO" "Step 4/4: Sending notifications..."
    # TODO: Integrar con sistema de email
    Write-Log "WARN" "Email notification pending integration"
} else {
    Write-Log "INFO" "Step 4/4: Notification skipped"
}

# Summary
$elapsed = ((Get-Date) - $startTime).TotalSeconds

Write-Host ""
Write-Host "============================================================" -ForegroundColor Magenta
Write-Host "  AIOPS PIPELINE COMPLETE" -ForegroundColor Magenta
Write-Host "============================================================" -ForegroundColor Magenta
Write-Host ""
Write-Host "  Metrics collected: YES" -ForegroundColor Green
Write-Host "  Anomalies found:   $($anomalies.Count)" -ForegroundColor $(if ($anomalies.Count -gt 0) { "Yellow" } else { "Green" })
Write-Host "  Auto-remediation:  $(if ($AutoRemediate) { 'ENABLED' } else { 'DISABLED' })" -ForegroundColor Cyan
Write-Host "  Elapsed time:      $([math]::Round($elapsed, 2))s" -ForegroundColor Cyan
Write-Host ""
Write-Host "============================================================" -ForegroundColor Magenta
