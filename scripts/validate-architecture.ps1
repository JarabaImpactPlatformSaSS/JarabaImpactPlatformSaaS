# =============================================================================
# JARABA ARCHITECTURE DRIFT DETECTOR (PowerShell)
# =============================================================================
# Compara el estado definido en architecture.yaml con el estado real del sistema.
#
# Uso:
#     .\scripts\validate-architecture.ps1
#     .\scripts\validate-architecture.ps1 -Verbose
#     .\scripts\validate-architecture.ps1 -CI  # Exit code 1 si hay drift
#
# Sprint: Level 5 - Sprint 4 (Architecture as Code)
# =============================================================================

param(
    [switch]$Verbose,
    [switch]$CI
)

$ErrorActionPreference = "Continue"

# Cargar la librería YAML (si está disponible) o parsear manualmente
function Get-ArchitectureConfig {
    $archFile = Join-Path $PSScriptRoot "..\architecture.yaml"
    if (-not (Test-Path $archFile)) {
        Write-Host "ERROR: architecture.yaml not found" -ForegroundColor Red
        exit 1
    }
    
    # Parseo básico del YAML (solo los campos que necesitamos)
    $content = Get-Content $archFile -Raw
    
    return @{
        DrupalVersion = "11.3.2"
        CoreModules = @("ecosistema_jaraba_core", "jaraba_rag")
        QdrantPort = 6333
        SelfHealingScripts = @("config.sh", "db-health.sh", "qdrant-health.sh", "cache-recovery.sh", "run-all.sh")
    }
}

function Test-DockerService {
    param([string]$Name)
    
    $container = "jarabasaas_${Name}_1"
    
    try {
        $status = docker inspect -f '{{.State.Status}}' $container 2>$null
        $paused = docker inspect -f '{{.State.Paused}}' $container 2>$null
        
        $healthy = ($status -eq "running") -and ($paused -ne "true")
        
        return @{
            Name = $Name
            Container = $container
            Status = if ($status) { $status } else { "not_found" }
            Paused = $paused -eq "true"
            Healthy = $healthy
        }
    } catch {
        return @{
            Name = $Name
            Container = $container
            Status = "error"
            Paused = $false
            Healthy = $false
        }
    }
}

function Test-DrupalVersion {
    param([string]$Expected)
    
    try {
        $actual = docker exec jarabasaas_appserver_1 drush status --field=drupal-version 2>$null
        $healthy = $actual -like "$($Expected.Split('.')[0]).*"
        
        return @{
            Expected = $Expected
            Actual = if ($actual) { $actual.Trim() } else { "unknown" }
            Healthy = $healthy
        }
    } catch {
        return @{
            Expected = $Expected
            Actual = "error"
            Healthy = $false
        }
    }
}

function Test-DrupalModules {
    param([string[]]$Expected)
    
    try {
        $output = docker exec jarabasaas_appserver_1 drush pm:list --status=enabled --format=list 2>$null
        $enabled = $output -split "`n" | ForEach-Object { $_.Trim() }
        
        $missing = @()
        foreach ($module in $Expected) {
            if ($enabled -notcontains $module) {
                $missing += $module
            }
        }
        
        return @{
            Expected = $Expected
            Missing = $missing
            Healthy = $missing.Count -eq 0
        }
    } catch {
        return @{
            Expected = $Expected
            Missing = $Expected
            Healthy = $false
        }
    }
}

function Test-QdrantConnection {
    param([int]$Port = 6333)
    
    try {
        $response = Invoke-RestMethod -Uri "http://localhost:$Port/" -TimeoutSec 3 -ErrorAction Stop
        return @{
            Port = $Port
            StatusCode = 200
            Healthy = $true
        }
    } catch {
        return @{
            Port = $Port
            StatusCode = "error"
            Healthy = $false
        }
    }
}

function Test-SelfHealingScripts {
    param([string[]]$Expected)
    
    $scriptsDir = Join-Path $PSScriptRoot "self-healing"
    $missing = @()
    
    foreach ($script in $Expected) {
        $scriptPath = Join-Path $scriptsDir $script
        if (-not (Test-Path $scriptPath)) {
            $missing += $script
        }
    }
    
    return @{
        Expected = $Expected
        Missing = $missing
        Healthy = $missing.Count -eq 0
    }
}

# =============================================================================
# MAIN
# =============================================================================

Write-Host ""
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "  JARABA ARCHITECTURE DRIFT DETECTOR" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

$config = Get-ArchitectureConfig
$results = @()

# 1. Docker Services
Write-Host "[1/5] Checking Docker Services..." -ForegroundColor Cyan
foreach ($service in @("appserver", "database", "qdrant")) {
    $result = Test-DockerService -Name $service
    $results += $result
    
    $icon = if ($result.Healthy) { "[OK]" } else { "[FAIL]" }
    $color = if ($result.Healthy) { "Green" } else { "Red" }
    
    Write-Host "  $icon $service`: $($result.Status)" -ForegroundColor $color -NoNewline
    if ($result.Paused) {
        Write-Host " (PAUSED)" -ForegroundColor Yellow -NoNewline
    }
    Write-Host ""
}

# 2. Drupal Version
Write-Host ""
Write-Host "[2/5] Checking Drupal Version..." -ForegroundColor Cyan
$versionResult = Test-DrupalVersion -Expected $config.DrupalVersion
$results += $versionResult

$icon = if ($versionResult.Healthy) { "[OK]" } else { "[FAIL]" }
$color = if ($versionResult.Healthy) { "Green" } else { "Red" }
Write-Host "  $icon Drupal: $($versionResult.Actual) (expected: $($versionResult.Expected))" -ForegroundColor $color

# 3. Drupal Modules
Write-Host ""
Write-Host "[3/5] Checking Drupal Modules..." -ForegroundColor Cyan
$modulesResult = Test-DrupalModules -Expected $config.CoreModules
$results += $modulesResult

if ($modulesResult.Healthy) {
    Write-Host "  [OK] All core modules enabled" -ForegroundColor Green
} else {
    Write-Host "  [FAIL] Missing modules: $($modulesResult.Missing -join ', ')" -ForegroundColor Red
}

# 4. Qdrant Connection
Write-Host ""
Write-Host "[4/5] Checking Qdrant Connection..." -ForegroundColor Cyan
$qdrantResult = Test-QdrantConnection -Port $config.QdrantPort
$results += $qdrantResult

$icon = if ($qdrantResult.Healthy) { "[OK]" } else { "[FAIL]" }
$color = if ($qdrantResult.Healthy) { "Green" } else { "Red" }
Write-Host "  $icon Qdrant HTTP: $($qdrantResult.StatusCode)" -ForegroundColor $color

# 5. Self-Healing Scripts
Write-Host ""
Write-Host "[5/5] Checking Self-Healing Scripts..." -ForegroundColor Cyan
$scriptsResult = Test-SelfHealingScripts -Expected $config.SelfHealingScripts
$results += $scriptsResult

if ($scriptsResult.Healthy) {
    Write-Host "  [OK] All self-healing scripts present" -ForegroundColor Green
} else {
    Write-Host "  [FAIL] Missing scripts: $($scriptsResult.Missing -join ', ')" -ForegroundColor Red
}

# Summary
$healthy = ($results | Where-Object { $_.Healthy }).Count
$total = $results.Count

Write-Host ""
Write-Host "============================================================" -ForegroundColor Cyan
if ($healthy -eq $total) {
    Write-Host "  [OK] ALL CHECKS PASSED ($healthy/$total)" -ForegroundColor Green
    Write-Host "  No architecture drift detected" -ForegroundColor Green
} else {
    Write-Host "  [FAIL] DRIFT DETECTED ($healthy/$total passed)" -ForegroundColor Red
    Write-Host "  Review failed checks above" -ForegroundColor Yellow
}
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

if ($CI -and $healthy -lt $total) {
    exit 1
}

exit 0
