# =============================================================================
# JARABA AIOPS - Anomaly Detector
# =============================================================================
# Detecta anomalías en las métricas usando análisis estadístico simple.
# Versión piloto que usa umbrales dinámicos basados en histórico.
#
# Sprint: Level 5 - Sprint 5 (Piloto AIOps)
# =============================================================================

param(
    [switch]$Verbose,
    [switch]$Notify,  # Enviar notificación si hay anomalía
    [int]$WindowSize = 10  # Número de muestras para calcular baseline
)

$ErrorActionPreference = "Continue"

# Configuración
$METRICS_DIR = Join-Path $PSScriptRoot "data"
$ANOMALY_LOG = Join-Path $METRICS_DIR "anomalies.log"
$NOTIFY_EMAIL = "contacto@pepejaraba.es"

# Umbrales por defecto (se ajustan dinámicamente con histórico)
$THRESHOLDS = @{
    response_time_ms = @{
        warning = 1000   # 1 segundo
        critical = 3000  # 3 segundos
        std_multiplier = 2.5  # veces la desviación estándar
    }
    cpu_percent = @{
        warning = 70
        critical = 90
    }
    mem_percent = @{
        warning = 80
        critical = 95
    }
}

function Write-Log {
    param([string]$Level, [string]$Message)
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $color = switch ($Level) {
        "INFO" { "Cyan" }
        "WARN" { "Yellow" }
        "ANOMALY" { "Red" }
        "OK" { "Green" }
        default { "White" }
    }
    Write-Host "[$timestamp] [$Level] $Message" -ForegroundColor $color
    
    if ($Level -eq "ANOMALY") {
        "[$timestamp] $Message" | Out-File -FilePath $ANOMALY_LOG -Append -Encoding UTF8
    }
}

function Get-HistoricalBaseline {
    param([string]$MetricPath)
    
    # Cargar últimas N muestras del archivo de métricas
    $todayLog = Join-Path $METRICS_DIR "metrics_$(Get-Date -Format 'yyyy-MM-dd').jsonl"
    
    if (-not (Test-Path $todayLog)) {
        return $null
    }
    
    try {
        $lines = Get-Content $todayLog -Tail $WindowSize -ErrorAction Stop
        $values = @()
        
        foreach ($line in $lines) {
            if ($line.Trim()) {
                $data = $line | ConvertFrom-Json
                # Navegar al metric path (ej: "site.response_time_ms")
                $parts = $MetricPath -split "\."
                $value = $data
                foreach ($part in $parts) {
                    $value = $value.$part
                }
                if ($value -is [int] -or $value -is [double]) {
                    $values += $value
                }
            }
        }
        
        if ($values.Count -ge 3) {
            $mean = ($values | Measure-Object -Average).Average
            $variance = ($values | ForEach-Object { [math]::Pow($_ - $mean, 2) } | Measure-Object -Average).Average
            $std = [math]::Sqrt($variance)
            
            return @{
                mean = [math]::Round($mean, 2)
                std = [math]::Round($std, 2)
                min = ($values | Measure-Object -Minimum).Minimum
                max = ($values | Measure-Object -Maximum).Maximum
                samples = $values.Count
            }
        }
    } catch {}
    
    return $null
}

function Test-Anomaly {
    param(
        [string]$Name,
        [double]$Value,
        [hashtable]$Threshold,
        [hashtable]$Baseline
    )
    
    $result = @{
        name = $Name
        value = $Value
        is_anomaly = $false
        severity = "OK"
        reason = ""
    }
    
    # Check static thresholds first
    if ($Value -ge $Threshold.critical) {
        $result.is_anomaly = $true
        $result.severity = "CRITICAL"
        $result.reason = "Value $Value exceeds critical threshold $($Threshold.critical)"
    }
    elseif ($Value -ge $Threshold.warning) {
        $result.is_anomaly = $true
        $result.severity = "WARNING"
        $result.reason = "Value $Value exceeds warning threshold $($Threshold.warning)"
    }
    # Check dynamic threshold (statistical)
    elseif ($Baseline -and $Baseline.std -gt 0) {
        $upper = $Baseline.mean + ($Baseline.std * $Threshold.std_multiplier)
        if ($Value -gt $upper) {
            $result.is_anomaly = $true
            $result.severity = "ANOMALY"
            $result.reason = "Value $Value is $([math]::Round(($Value - $Baseline.mean) / $Baseline.std, 1)) std devs above mean ($([math]::Round($Baseline.mean, 0)))"
        }
    }
    
    return $result
}

function Send-AnomalyNotification {
    param([array]$Anomalies)
    
    $body = "JARABA AIOPS - Anomaly Detection Alert`n"
    $body += "=" * 50 + "`n`n"
    $body += "Detected at: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')`n`n"
    
    foreach ($a in $Anomalies) {
        $body += "[$($a.severity)] $($a.name): $($a.value)`n"
        $body += "  Reason: $($a.reason)`n`n"
    }
    
    $body += "---`n"
    $body += "Jaraba Impact Platform - AIOps Pilot"
    
    # Por ahora solo logueamos (integrar con sistema de email después)
    Write-Log "INFO" "Notification would be sent to: $NOTIFY_EMAIL"
    Write-Log "INFO" "Anomalies: $($Anomalies.Count)"
}

# =============================================================================
# MAIN
# =============================================================================

Write-Host ""
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "  JARABA AIOPS - ANOMALY DETECTOR" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

# 1. Collect current metrics
Write-Log "INFO" "Collecting current metrics..."
$metrics = & (Join-Path $PSScriptRoot "collect-metrics.ps1")

# 2. Get baselines
Write-Log "INFO" "Calculating baselines (window: $WindowSize samples)..."
$responseBaseline = Get-HistoricalBaseline -MetricPath "site.response_time_ms"
$cpuBaseline = Get-HistoricalBaseline -MetricPath "containers.appserver.cpu_percent"
$memBaseline = Get-HistoricalBaseline -MetricPath "containers.appserver.mem_percent"

if ($responseBaseline) {
    Write-Log "INFO" "Response time baseline: mean=$($responseBaseline.mean)ms, std=$($responseBaseline.std)ms"
}

# 3. Check for anomalies
Write-Log "INFO" "Checking for anomalies..."
$anomalies = @()

# Response time
if ($metrics.site.response_time_ms -gt 0) {
    $check = Test-Anomaly -Name "Site Response Time" `
        -Value $metrics.site.response_time_ms `
        -Threshold $THRESHOLDS.response_time_ms `
        -Baseline $responseBaseline
    
    if ($check.is_anomaly) {
        $anomalies += $check
        Write-Log "ANOMALY" "$($check.name): $($check.reason)"
    } else {
        Write-Log "OK" "Site Response Time: $($metrics.site.response_time_ms)ms"
    }
}

# CPU Usage (appserver)
$check = Test-Anomaly -Name "Appserver CPU" `
    -Value $metrics.containers.appserver.cpu_percent `
    -Threshold $THRESHOLDS.cpu_percent `
    -Baseline $cpuBaseline

if ($check.is_anomaly) {
    $anomalies += $check
    Write-Log "ANOMALY" "$($check.name): $($check.reason)"
} else {
    Write-Log "OK" "Appserver CPU: $($metrics.containers.appserver.cpu_percent)%"
}

# Memory Usage (appserver)
$check = Test-Anomaly -Name "Appserver Memory" `
    -Value $metrics.containers.appserver.mem_percent `
    -Threshold $THRESHOLDS.mem_percent `
    -Baseline $memBaseline

if ($check.is_anomaly) {
    $anomalies += $check
    Write-Log "ANOMALY" "$($check.name): $($check.reason)"
} else {
    Write-Log "OK" "Appserver Memory: $($metrics.containers.appserver.mem_percent)%"
}

# Database paused (critical!)
if ($metrics.containers.database.paused) {
    $anomalies += @{
        name = "Database Container"
        value = "PAUSED"
        is_anomaly = $true
        severity = "CRITICAL"
        reason = "Database container is paused - site will hang!"
    }
    Write-Log "ANOMALY" "Database container is PAUSED!"
}

# Site availability
if (-not $metrics.site.available) {
    $anomalies += @{
        name = "Site Availability"
        value = "DOWN"
        is_anomaly = $true
        severity = "CRITICAL"
        reason = "Site is not responding"
    }
    Write-Log "ANOMALY" "Site is DOWN!"
}

# 4. Summary
Write-Host ""
Write-Host "============================================================" -ForegroundColor Cyan
if ($anomalies.Count -eq 0) {
    Write-Host "  [OK] NO ANOMALIES DETECTED" -ForegroundColor Green
} else {
    Write-Host "  [ALERT] $($anomalies.Count) ANOMALY(IES) DETECTED" -ForegroundColor Red
    
    if ($Notify) {
        Send-AnomalyNotification -Anomalies $anomalies
    }
}
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

# Return anomalies for pipeline
return $anomalies
