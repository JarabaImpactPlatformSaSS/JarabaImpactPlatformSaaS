# =============================================================================
# JARABA AIOPS - Metrics Collector
# =============================================================================
# Recolecta métricas del sistema para análisis y detección de anomalías.
#
# Sprint: Level 5 - Sprint 5 (Piloto AIOps)
# =============================================================================

param(
    [switch]$Verbose,
    [string]$OutputFile = ""
)

$ErrorActionPreference = "Continue"

# Configuración
$SITE_URL = "https://jaraba-saas.lndo.site"
$QDRANT_URL = "http://localhost:6333"
$METRICS_DIR = Join-Path $PSScriptRoot "data"

# Crear directorio de datos si no existe
if (-not (Test-Path $METRICS_DIR)) {
    New-Item -ItemType Directory -Path $METRICS_DIR -Force | Out-Null
}

function Get-Timestamp {
    return (Get-Date -Format "yyyy-MM-dd HH:mm:ss")
}

function Get-UnixTimestamp {
    return [int][double]::Parse((Get-Date -UFormat %s))
}

function Measure-SiteResponseTime {
    try {
        $start = Get-Date
        $response = Invoke-WebRequest -Uri $SITE_URL -UseBasicParsing -TimeoutSec 30
        $elapsed = ((Get-Date) - $start).TotalMilliseconds
        
        return @{
            response_time_ms = [math]::Round($elapsed, 2)
            status_code = $response.StatusCode
            success = $true
        }
    } catch {
        return @{
            response_time_ms = -1
            status_code = 0
            success = $false
        }
    }
}

function Get-DockerStats {
    param([string]$Container)
    
    try {
        $stats = docker stats $Container --no-stream --format "{{.CPUPerc}},{{.MemPerc}},{{.MemUsage}}" 2>$null
        if ($stats) {
            $parts = $stats -split ","
            return @{
                cpu_percent = [double]($parts[0] -replace '%', '')
                mem_percent = [double]($parts[1] -replace '%', '')
                mem_usage = $parts[2]
                success = $true
            }
        }
    } catch {}
    
    return @{
        cpu_percent = 0
        mem_percent = 0
        mem_usage = "0"
        success = $false
    }
}

function Get-QdrantMetrics {
    try {
        $response = Invoke-RestMethod -Uri "$QDRANT_URL/collections/jaraba_kb" -TimeoutSec 5
        return @{
            points_count = $response.result.points_count
            indexed_vectors = $response.result.indexed_vectors_count
            status = $response.result.status
            success = $true
        }
    } catch {
        return @{
            points_count = 0
            indexed_vectors = 0
            status = "error"
            success = $false
        }
    }
}

function Get-DatabaseMetrics {
    try {
        # Verificar conexión
        $status = docker inspect -f '{{.State.Status}}' jarabasaas_database_1 2>$null
        $paused = docker inspect -f '{{.State.Paused}}' jarabasaas_database_1 2>$null
        
        # Intentar obtener info de conexiones activas
        $connections = docker exec jarabasaas_database_1 mysql -u drupal -pdrupal -e "SHOW STATUS LIKE 'Threads_connected';" 2>$null
        $connCount = 0
        if ($connections -match "(\d+)") {
            $connCount = [int]$Matches[1]
        }
        
        return @{
            status = $status
            paused = $paused -eq "true"
            connections = $connCount
            success = $true
        }
    } catch {
        return @{
            status = "error"
            paused = $false
            connections = 0
            success = $false
        }
    }
}

# =============================================================================
# MAIN - Collect all metrics
# =============================================================================

$timestamp = Get-Timestamp
$unixTime = Get-UnixTimestamp

if ($Verbose) {
    Write-Host "[$timestamp] Collecting metrics..." -ForegroundColor Cyan
}

# Collect metrics
$siteMetrics = Measure-SiteResponseTime
$appserverStats = Get-DockerStats -Container "jarabasaas_appserver_1"
$databaseStats = Get-DockerStats -Container "jarabasaas_database_1"
$qdrantStats = Get-DockerStats -Container "jarabasaas_qdrant_1"
$qdrantMetrics = Get-QdrantMetrics
$dbMetrics = Get-DatabaseMetrics

# Build metrics object
$metrics = @{
    timestamp = $timestamp
    unix_timestamp = $unixTime
    
    site = @{
        response_time_ms = $siteMetrics.response_time_ms
        status_code = $siteMetrics.status_code
        available = $siteMetrics.success
    }
    
    containers = @{
        appserver = @{
            cpu_percent = $appserverStats.cpu_percent
            mem_percent = $appserverStats.mem_percent
        }
        database = @{
            cpu_percent = $databaseStats.cpu_percent
            mem_percent = $databaseStats.mem_percent
            status = $dbMetrics.status
            paused = $dbMetrics.paused
            connections = $dbMetrics.connections
        }
        qdrant = @{
            cpu_percent = $qdrantStats.cpu_percent
            mem_percent = $qdrantStats.mem_percent
            points_count = $qdrantMetrics.points_count
            status = $qdrantMetrics.status
        }
    }
}

# Output
$json = $metrics | ConvertTo-Json -Depth 4

if ($OutputFile) {
    $json | Out-File -FilePath $OutputFile -Encoding UTF8
    if ($Verbose) {
        Write-Host "Metrics saved to: $OutputFile" -ForegroundColor Green
    }
} else {
    # Append to daily log
    $dailyLog = Join-Path $METRICS_DIR "metrics_$(Get-Date -Format 'yyyy-MM-dd').jsonl"
    $json -replace "`n", "" | Out-File -FilePath $dailyLog -Append -Encoding UTF8
    
    if ($Verbose) {
        Write-Host $json
        Write-Host ""
        Write-Host "Appended to: $dailyLog" -ForegroundColor Green
    }
}

# Return metrics for pipeline
return $metrics
