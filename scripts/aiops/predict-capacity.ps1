# =============================================================================
# JARABA AIOPS - Predictive Capacity Planning
# =============================================================================
# Sistema de predicción de capacidad usando análisis de tendencias.
# Predice cuándo se alcanzarán umbrales críticos y alerta previamente.
#
# Sprint: Level 5 - Sprint 6 (Predictive Capacity Planning)
# =============================================================================

param(
    [int]$ForecastHours = 24,    # Cuántas horas predecir
    [int]$WindowSize = 20,       # Muestras para calcular tendencia
    [switch]$Verbose
)

$ErrorActionPreference = "Continue"

# Configuración
$METRICS_DIR = Join-Path $PSScriptRoot "data"
$CAPACITY_THRESHOLDS = @{
    response_time_ms = @{
        warning = 1000
        critical = 3000
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
        "PREDICT" { "Magenta" }
        "ALERT" { "Red" }
        "OK" { "Green" }
        default { "White" }
    }
    Write-Host "[$timestamp] [$Level] $Message" -ForegroundColor $color
}

function Get-HistoricalData {
    param([string]$MetricPath, [int]$MaxSamples = 100)
    
    $allData = @()
    
    # Cargar datos de hoy y ayer
    $dates = @(
        (Get-Date -Format 'yyyy-MM-dd'),
        (Get-Date).AddDays(-1).ToString('yyyy-MM-dd')
    )
    
    foreach ($date in $dates) {
        $logFile = Join-Path $METRICS_DIR "metrics_$date.jsonl"
        if (Test-Path $logFile) {
            $lines = Get-Content $logFile -ErrorAction SilentlyContinue
            foreach ($line in $lines) {
                if ($line.Trim()) {
                    try {
                        $data = $line | ConvertFrom-Json
                        $parts = $MetricPath -split "\."
                        $value = $data
                        foreach ($part in $parts) {
                            $value = $value.$part
                        }
                        if ($null -ne $value -and $value -is [double] -or $value -is [int]) {
                            $allData += @{
                                timestamp = $data.unix_timestamp
                                value = [double]$value
                            }
                        }
                    } catch {}
                }
            }
        }
    }
    
    # Retornar últimas N muestras ordenadas
    return $allData | Sort-Object timestamp | Select-Object -Last $MaxSamples
}

function Calculate-Trend {
    param([array]$Data)
    
    if ($Data.Count -lt 3) {
        return @{
            slope = 0
            intercept = 0
            r_squared = 0
            valid = $false
        }
    }
    
    # Regresión lineal simple
    $n = $Data.Count
    $startTime = $Data[0].timestamp
    
    $x = @()
    $y = @()
    foreach ($point in $Data) {
        $x += ($point.timestamp - $startTime) / 3600  # Horas desde inicio
        $y += $point.value
    }
    
    $sumX = ($x | Measure-Object -Sum).Sum
    $sumY = ($y | Measure-Object -Sum).Sum
    $sumXY = 0
    $sumX2 = 0
    $sumY2 = 0
    
    for ($i = 0; $i -lt $n; $i++) {
        $sumXY += $x[$i] * $y[$i]
        $sumX2 += $x[$i] * $x[$i]
        $sumY2 += $y[$i] * $y[$i]
    }
    
    $denominator = ($n * $sumX2 - $sumX * $sumX)
    if ($denominator -eq 0) {
        return @{ slope = 0; intercept = ($sumY / $n); r_squared = 0; valid = $true }
    }
    
    $slope = ($n * $sumXY - $sumX * $sumY) / $denominator
    $intercept = ($sumY - $slope * $sumX) / $n
    
    # R² (coeficiente de determinación)
    $yMean = $sumY / $n
    $ssTot = 0
    $ssRes = 0
    for ($i = 0; $i -lt $n; $i++) {
        $predicted = $slope * $x[$i] + $intercept
        $ssTot += [math]::Pow($y[$i] - $yMean, 2)
        $ssRes += [math]::Pow($y[$i] - $predicted, 2)
    }
    
    $r_squared = if ($ssTot -gt 0) { 1 - ($ssRes / $ssTot) } else { 0 }
    
    return @{
        slope = [math]::Round($slope, 4)
        intercept = [math]::Round($intercept, 2)
        r_squared = [math]::Round($r_squared, 3)
        current_value = $y[-1]
        hours_analyzed = [math]::Round($x[-1], 1)
        valid = $true
    }
}

function Predict-TimeToThreshold {
    param(
        [hashtable]$Trend,
        [double]$Threshold
    )
    
    if (-not $Trend.valid -or $Trend.slope -le 0) {
        return @{
            will_reach = $false
            hours = -1
            reason = "No upward trend"
        }
    }
    
    $currentValue = $Trend.current_value
    if ($currentValue -ge $Threshold) {
        return @{
            will_reach = $true
            hours = 0
            reason = "Already at or above threshold"
        }
    }
    
    # Calcular horas hasta alcanzar umbral
    $hoursToThreshold = ($Threshold - $currentValue) / $Trend.slope
    
    return @{
        will_reach = $true
        hours = [math]::Round($hoursToThreshold, 1)
        reason = "Trending at $($Trend.slope)/hour"
    }
}

# =============================================================================
# MAIN
# =============================================================================

Write-Host ""
Write-Host "============================================================" -ForegroundColor Magenta
Write-Host "  JARABA AIOPS - PREDICTIVE CAPACITY PLANNING" -ForegroundColor Magenta
Write-Host "  Forecast window: $ForecastHours hours" -ForegroundColor Magenta
Write-Host "============================================================" -ForegroundColor Magenta
Write-Host ""

$predictions = @()

# Analizar cada métrica
$metricsToAnalyze = @(
    @{ name = "Response Time"; path = "site.response_time_ms"; thresholds = $CAPACITY_THRESHOLDS.response_time_ms },
    @{ name = "CPU Usage"; path = "containers.appserver.cpu_percent"; thresholds = $CAPACITY_THRESHOLDS.cpu_percent },
    @{ name = "Memory Usage"; path = "containers.appserver.mem_percent"; thresholds = $CAPACITY_THRESHOLDS.mem_percent }
)

foreach ($metric in $metricsToAnalyze) {
    Write-Log "INFO" "Analyzing: $($metric.name)"
    
    # Obtener datos históricos
    $data = Get-HistoricalData -MetricPath $metric.path -MaxSamples $WindowSize
    
    if ($data.Count -lt 5) {
        Write-Log "WARN" "  Insufficient data ($($data.Count) samples)"
        continue
    }
    
    Write-Log "INFO" "  Samples: $($data.Count)"
    
    # Calcular tendencia
    $trend = Calculate-Trend -Data $data
    
    if ($Verbose) {
        Write-Log "INFO" "  Current: $($trend.current_value), Slope: $($trend.slope)/h, R²: $($trend.r_squared)"
    }
    
    # Predecir tiempo hasta umbrales
    $warningPred = Predict-TimeToThreshold -Trend $trend -Threshold $metric.thresholds.warning
    $criticalPred = Predict-TimeToThreshold -Trend $trend -Threshold $metric.thresholds.critical
    
    $prediction = @{
        metric = $metric.name
        current = $trend.current_value
        slope = $trend.slope
        r_squared = $trend.r_squared
        warning_threshold = $metric.thresholds.warning
        critical_threshold = $metric.thresholds.critical
        hours_to_warning = $warningPred.hours
        hours_to_critical = $criticalPred.hours
    }
    $predictions += $prediction
    
    # Mostrar predicciones
    if ($warningPred.will_reach -and $warningPred.hours -gt 0 -and $warningPred.hours -le $ForecastHours) {
        Write-Log "PREDICT" "  WARNING in $($warningPred.hours)h (threshold: $($metric.thresholds.warning))"
    }
    if ($criticalPred.will_reach -and $criticalPred.hours -gt 0 -and $criticalPred.hours -le $ForecastHours) {
        Write-Log "ALERT" "  CRITICAL in $($criticalPred.hours)h (threshold: $($metric.thresholds.critical))"
    }
    if (-not $warningPred.will_reach -or $warningPred.hours -gt $ForecastHours) {
        Write-Log "OK" "  No capacity issues predicted in next ${ForecastHours}h"
    }
}

# Resumen
Write-Host ""
Write-Host "============================================================" -ForegroundColor Magenta
Write-Host "  CAPACITY FORECAST SUMMARY" -ForegroundColor Magenta
Write-Host "============================================================" -ForegroundColor Magenta
Write-Host ""

$alertCount = 0
foreach ($p in $predictions) {
    if ($p.hours_to_warning -gt 0 -and $p.hours_to_warning -le $ForecastHours) {
        $alertCount++
        $icon = "[!]"
        $color = "Yellow"
    } elseif ($p.hours_to_critical -gt 0 -and $p.hours_to_critical -le $ForecastHours) {
        $alertCount++
        $icon = "[!!]"
        $color = "Red"
    } else {
        $icon = "[OK]"
        $color = "Green"
    }
    
    Write-Host "  $icon $($p.metric): " -ForegroundColor $color -NoNewline
    Write-Host "$($p.current) (trend: $($p.slope)/h)" -ForegroundColor White
}

Write-Host ""
if ($alertCount -eq 0) {
    Write-Host "  All metrics within safe capacity for next ${ForecastHours}h" -ForegroundColor Green
} else {
    Write-Host "  $alertCount metric(s) approaching capacity limits" -ForegroundColor Yellow
}
Write-Host ""
Write-Host "============================================================" -ForegroundColor Magenta

return $predictions
