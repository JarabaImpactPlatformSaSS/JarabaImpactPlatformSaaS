# =============================================================================
# JARABA - Setup Scheduled Tasks (Windows)
# =============================================================================
# Configura tareas programadas para ejecutar AIOps y Self-Healing automáticamente.
#
# NOTA: Los health checks son muy ligeros y no impactan el rendimiento.
#       Las acciones de remediación pesadas solo se ejecutan en horario de 
#       bajo tráfico (02:00-06:00) o cuando hay emergencia crítica.
#
# Ejecutar como Administrador:
#   powershell -ExecutionPolicy Bypass -File scripts\setup-scheduler.ps1
# =============================================================================

param(
    [switch]$Uninstall,  # Para desinstalar las tareas
    [switch]$24x7        # Ejecutar 24/7 sin restricción de horario
)

$ErrorActionPreference = "Stop"

# Configuración de horarios
$LOW_IMPACT_START = "02:00"  # Hora inicio bajo tráfico
$LOW_IMPACT_END = "06:00"    # Hora fin bajo tráfico

$ScriptsPath = Split-Path -Parent $PSScriptRoot
$AIOpsScript = Join-Path $ScriptsPath "aiops\run-aiops.ps1"
$SelfHealScript = Join-Path $ScriptsPath "self-healing\test-local.ps1"

# Verificar que los scripts existen
if (-not (Test-Path $AIOpsScript)) {
    Write-Host "ERROR: AIOps script not found at: $AIOpsScript" -ForegroundColor Red
    exit 1
}

function Install-ScheduledTasks {
    Write-Host ""
    Write-Host "============================================================" -ForegroundColor Cyan
    Write-Host "  JARABA - Installing Scheduled Tasks" -ForegroundColor Cyan
    Write-Host "============================================================" -ForegroundColor Cyan
    Write-Host ""

    # Task 1: Self-Healing (cada 1 minuto)
    Write-Host "[1/2] Creating Self-Healing task (every 1 minute)..." -ForegroundColor Yellow
    
    $selfHealAction = New-ScheduledTaskAction `
        -Execute "powershell.exe" `
        -Argument "-ExecutionPolicy Bypass -WindowStyle Hidden -File `"$SelfHealScript`" -All" `
        -WorkingDirectory $ScriptsPath

    $selfHealTrigger = New-ScheduledTaskTrigger `
        -Once -At (Get-Date) `
        -RepetitionInterval (New-TimeSpan -Minutes 1) `
        -RepetitionDuration (New-TimeSpan -Days 9999)

    $selfHealSettings = New-ScheduledTaskSettingsSet `
        -AllowStartIfOnBatteries `
        -DontStopIfGoingOnBatteries `
        -StartWhenAvailable `
        -MultipleInstances IgnoreNew

    try {
        Unregister-ScheduledTask -TaskName "Jaraba-SelfHealing" -Confirm:$false -ErrorAction SilentlyContinue
        Register-ScheduledTask `
            -TaskName "Jaraba-SelfHealing" `
            -Action $selfHealAction `
            -Trigger $selfHealTrigger `
            -Settings $selfHealSettings `
            -Description "Jaraba Self-Healing: Health checks every minute" | Out-Null
        Write-Host "  [OK] Jaraba-SelfHealing created" -ForegroundColor Green
    } catch {
        Write-Host "  [FAIL] Could not create task: $_" -ForegroundColor Red
    }

    # Task 2: AIOps (cada 5 minutos)
    Write-Host "[2/2] Creating AIOps task (every 5 minutes)..." -ForegroundColor Yellow
    
    $aiopsAction = New-ScheduledTaskAction `
        -Execute "powershell.exe" `
        -Argument "-ExecutionPolicy Bypass -WindowStyle Hidden -File `"$AIOpsScript`" -AutoRemediate" `
        -WorkingDirectory $ScriptsPath

    $aiopsTrigger = New-ScheduledTaskTrigger `
        -Once -At (Get-Date) `
        -RepetitionInterval (New-TimeSpan -Minutes 5) `
        -RepetitionDuration (New-TimeSpan -Days 9999)

    $aiopsSettings = New-ScheduledTaskSettingsSet `
        -AllowStartIfOnBatteries `
        -DontStopIfGoingOnBatteries `
        -StartWhenAvailable `
        -MultipleInstances IgnoreNew

    try {
        Unregister-ScheduledTask -TaskName "Jaraba-AIOps" -Confirm:$false -ErrorAction SilentlyContinue
        Register-ScheduledTask `
            -TaskName "Jaraba-AIOps" `
            -Action $aiopsAction `
            -Trigger $aiopsTrigger `
            -Settings $aiopsSettings `
            -Description "Jaraba AIOps: Metrics, anomaly detection, and capacity prediction" | Out-Null
        Write-Host "  [OK] Jaraba-AIOps created" -ForegroundColor Green
    } catch {
        Write-Host "  [FAIL] Could not create task: $_" -ForegroundColor Red
    }

    Write-Host ""
    Write-Host "============================================================" -ForegroundColor Cyan
    Write-Host "  Scheduled Tasks Installed!" -ForegroundColor Green
    Write-Host "============================================================" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "  Tasks created:" -ForegroundColor White
    Write-Host "  - Jaraba-SelfHealing (every 1 min)" -ForegroundColor Cyan
    Write-Host "  - Jaraba-AIOps (every 5 min)" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "  To view: Task Scheduler > Task Scheduler Library" -ForegroundColor Gray
    Write-Host "  To uninstall: .\setup-scheduler.ps1 -Uninstall" -ForegroundColor Gray
    Write-Host ""
}

function Uninstall-ScheduledTasks {
    Write-Host ""
    Write-Host "============================================================" -ForegroundColor Yellow
    Write-Host "  JARABA - Removing Scheduled Tasks" -ForegroundColor Yellow
    Write-Host "============================================================" -ForegroundColor Yellow
    Write-Host ""

    try {
        Unregister-ScheduledTask -TaskName "Jaraba-SelfHealing" -Confirm:$false -ErrorAction SilentlyContinue
        Write-Host "  [OK] Jaraba-SelfHealing removed" -ForegroundColor Green
    } catch {
        Write-Host "  [SKIP] Jaraba-SelfHealing not found" -ForegroundColor Gray
    }

    try {
        Unregister-ScheduledTask -TaskName "Jaraba-AIOps" -Confirm:$false -ErrorAction SilentlyContinue
        Write-Host "  [OK] Jaraba-AIOps removed" -ForegroundColor Green
    } catch {
        Write-Host "  [SKIP] Jaraba-AIOps not found" -ForegroundColor Gray
    }

    Write-Host ""
    Write-Host "  All Jaraba scheduled tasks removed." -ForegroundColor Green
    Write-Host ""
}

# Main
if ($Uninstall) {
    Uninstall-ScheduledTasks
} else {
    Install-ScheduledTasks
}
