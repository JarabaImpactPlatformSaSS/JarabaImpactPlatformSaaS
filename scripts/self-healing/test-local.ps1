# =============================================================================
# JARABA SELF-HEALING: Test Script (Windows/PowerShell)
# =============================================================================
# Este script es solo para TESTING en entorno local Windows.
# Los scripts bash (.sh) son para ejecutar en servidores Linux de producción.
# =============================================================================

param(
    [switch]$All,
    [switch]$Database,
    [switch]$Qdrant,
    [switch]$Cache,
    [switch]$SimulateFail
)

$ErrorActionPreference = "Stop"

# Configuración
$QDRANT_CONTAINER = "jarabasaas_qdrant_1"
$DATABASE_CONTAINER = "jarabasaas_database_1"
$APPSERVER_CONTAINER = "jarabasaas_appserver_1"
$SITE_URL = "https://jaraba-saas.lndo.site"

function Write-Log {
    param([string]$Level, [string]$Message)
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $color = switch ($Level) {
        "INFO" { "Cyan" }
        "WARN" { "Yellow" }
        "ERROR" { "Red" }
        "OK" { "Green" }
        default { "White" }
    }
    Write-Host "[$timestamp] [$Level] $Message" -ForegroundColor $color
}

function Test-Container {
    param([string]$Container)
    $status = docker inspect -f '{{.State.Status}}' $Container 2>$null
    $paused = docker inspect -f '{{.State.Paused}}' $Container 2>$null
    return @{ Status = $status; Paused = $paused }
}

function Test-DatabaseHealth {
    Write-Log "INFO" "=== Database Health Check ==="
    
    $state = Test-Container $DATABASE_CONTAINER
    
    if ($state.Paused -eq "true") {
        Write-Log "ERROR" "Database container is PAUSED!"
        Write-Log "WARN" "Attempting recovery: docker unpause $DATABASE_CONTAINER"
        docker unpause $DATABASE_CONTAINER
        Start-Sleep -Seconds 2
        $state = Test-Container $DATABASE_CONTAINER
        if ($state.Paused -eq "false") {
            Write-Log "OK" "Database recovered successfully!"
            return $true
        }
        return $false
    }
    
    if ($state.Status -ne "running") {
        Write-Log "ERROR" "Database container not running (status: $($state.Status))"
        return $false
    }
    
    Write-Log "OK" "Database is healthy"
    return $true
}

function Test-QdrantHealth {
    Write-Log "INFO" "=== Qdrant Health Check ==="
    
    $state = Test-Container $QDRANT_CONTAINER
    
    if ($state.Paused -eq "true") {
        Write-Log "ERROR" "Qdrant container is PAUSED!"
        Write-Log "WARN" "Attempting recovery: docker unpause $QDRANT_CONTAINER"
        docker unpause $QDRANT_CONTAINER
        Start-Sleep -Seconds 2
        $state = Test-Container $QDRANT_CONTAINER
        if ($state.Paused -eq "false") {
            Write-Log "OK" "Qdrant recovered successfully!"
            return $true
        }
        return $false
    }
    
    if ($state.Status -ne "running") {
        Write-Log "ERROR" "Qdrant container not running (status: $($state.Status))"
        return $false
    }
    
    # Test HTTP
    try {
        $response = Invoke-RestMethod -Uri "http://localhost:6333/" -TimeoutSec 3 -ErrorAction Stop
        Write-Log "OK" "Qdrant is healthy (HTTP OK)"
        return $true
    } catch {
        Write-Log "ERROR" "Qdrant HTTP check failed: $_"
        return $false
    }
}

function Test-CacheHealth {
    Write-Log "INFO" "=== Cache Health Check ==="
    
    try {
        $start = Get-Date
        $response = Invoke-WebRequest -Uri $SITE_URL -UseBasicParsing -TimeoutSec 30
        $elapsed = ((Get-Date) - $start).TotalMilliseconds
        
        if ($response.StatusCode -eq 200) {
            if ($elapsed -gt 3000) {
                Write-Log "WARN" "Site slow: ${elapsed}ms - may need cache rebuild"
                Write-Log "INFO" "Running: drush cr"
                docker exec $APPSERVER_CONTAINER drush cr
                Write-Log "OK" "Cache rebuilt"
            } else {
                Write-Log "OK" "Site responding in ${elapsed}ms"
            }
            return $true
        }
    } catch {
        Write-Log "ERROR" "Site check failed: $_"
        return $false
    }
}

function Simulate-Failure {
    param([string]$Service)
    
    Write-Log "WARN" "=== SIMULATING FAILURE: $Service ==="
    
    switch ($Service) {
        "qdrant" {
            Write-Log "INFO" "Pausing Qdrant container..."
            docker pause $QDRANT_CONTAINER
            Start-Sleep -Seconds 2
            Write-Log "INFO" "Running health check..."
            Test-QdrantHealth
        }
        "database" {
            Write-Log "INFO" "Pausing Database container..."
            docker pause $DATABASE_CONTAINER
            Start-Sleep -Seconds 2
            Write-Log "INFO" "Running health check..."
            Test-DatabaseHealth
        }
    }
}

# Main
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  JARABA SELF-HEALING TEST (Windows)   " -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

if ($SimulateFail) {
    Simulate-Failure -Service "qdrant"
    Write-Host ""
    Simulate-Failure -Service "database"
} elseif ($All -or (-not $Database -and -not $Qdrant -and -not $Cache)) {
    Test-DatabaseHealth
    Write-Host ""
    Test-QdrantHealth
    Write-Host ""
    Test-CacheHealth
} else {
    if ($Database) { Test-DatabaseHealth }
    if ($Qdrant) { Test-QdrantHealth }
    if ($Cache) { Test-CacheHealth }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  TEST COMPLETE                        " -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
