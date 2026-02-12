#!/usr/bin/env python3
"""
JARABA ARCHITECTURE DRIFT DETECTOR
===================================
Compara el estado definido en architecture.yaml con el estado real del sistema.

Uso:
    python scripts/validate-architecture.py
    python scripts/validate-architecture.py --verbose
    python scripts/validate-architecture.py --ci  # Para CI/CD (exit code 1 si hay drift)

Sprint: Level 5 - Sprint 4 (Architecture as Code)
"""

import subprocess
import sys
import yaml
import json
import argparse
from pathlib import Path
from typing import Dict, List, Tuple

# Colores para output
RED = '\033[91m'
GREEN = '\033[92m'
YELLOW = '\033[93m'
BLUE = '\033[94m'
RESET = '\033[0m'


def load_architecture() -> Dict:
    """Carga el archivo architecture.yaml"""
    arch_file = Path(__file__).parent.parent / 'architecture.yaml'
    if not arch_file.exists():
        print(f"{RED}ERROR: architecture.yaml not found{RESET}")
        sys.exit(1)
    
    with open(arch_file, 'r', encoding='utf-8') as f:
        return yaml.safe_load(f)


def run_command(cmd: str) -> Tuple[bool, str]:
    """Ejecuta un comando y retorna (success, output)"""
    try:
        result = subprocess.run(
            cmd, shell=True, capture_output=True, text=True, timeout=30
        )
        return result.returncode == 0, result.stdout.strip()
    except Exception as e:
        return False, str(e)


def check_docker_service(name: str, expected_status: str = 'running') -> Dict:
    """Verifica el estado de un contenedor Docker"""
    container_name = f"jarabasaas_{name}_1"
    
    # Check status
    success, status = run_command(
        f"docker inspect -f '{{{{.State.Status}}}}' {container_name}"
    )
    
    # Check if paused
    _, paused = run_command(
        f"docker inspect -f '{{{{.State.Paused}}}}' {container_name}"
    )
    
    return {
        'name': name,
        'container': container_name,
        'expected': expected_status,
        'actual': status if success else 'not_found',
        'paused': paused == 'true',
        'healthy': success and status == expected_status and paused != 'true'
    }


def check_drupal_modules(expected_modules: List[str]) -> Dict:
    """Verifica módulos Drupal habilitados"""
    success, output = run_command(
        "docker exec jarabasaas_appserver_1 drush pm:list --status=enabled --format=json"
    )
    
    if not success:
        return {'healthy': False, 'error': 'Cannot get module list'}
    
    try:
        modules = json.loads(output)
        enabled = list(modules.keys())
        missing = [m for m in expected_modules if m not in enabled]
        
        return {
            'expected': expected_modules,
            'enabled': [m for m in expected_modules if m in enabled],
            'missing': missing,
            'healthy': len(missing) == 0
        }
    except:
        return {'healthy': False, 'error': 'Cannot parse module list'}


def check_drupal_version(expected: str) -> Dict:
    """Verifica versión de Drupal"""
    success, output = run_command(
        "docker exec jarabasaas_appserver_1 drush status --field=drupal-version"
    )
    
    return {
        'expected': expected,
        'actual': output if success else 'unknown',
        'healthy': success and output.startswith(expected.split('.')[0])
    }


def check_qdrant_connection(config: Dict) -> Dict:
    """Verifica conexión a Qdrant"""
    success, output = run_command(
        f"curl -s http://localhost:{config.get('http_port', 6333)}/ -o /dev/null -w '%{{http_code}}'"
    )
    
    return {
        'port': config.get('http_port', 6333),
        'status_code': output if success else 'error',
        'healthy': success and output == '200'
    }


def check_self_healing_scripts() -> Dict:
    """Verifica que los scripts de self-healing existen"""
    scripts_dir = Path(__file__).parent / 'self-healing'
    expected_scripts = [
        'config.sh',
        'db-health.sh',
        'qdrant-health.sh',
        'cache-recovery.sh',
        'run-all.sh'
    ]
    
    existing = [s for s in expected_scripts if (scripts_dir / s).exists()]
    missing = [s for s in expected_scripts if s not in existing]
    
    return {
        'expected': expected_scripts,
        'existing': existing,
        'missing': missing,
        'healthy': len(missing) == 0
    }


def validate_architecture(verbose: bool = False) -> List[Dict]:
    """Ejecuta todas las validaciones"""
    arch = load_architecture()
    results = []
    
    print(f"\n{BLUE}{'='*60}{RESET}")
    print(f"{BLUE}  JARABA ARCHITECTURE DRIFT DETECTOR{RESET}")
    print(f"{BLUE}{'='*60}{RESET}\n")
    
    # 1. Docker Services
    print(f"{BLUE}[1/5] Checking Docker Services...{RESET}")
    for service_name in ['appserver', 'database', 'qdrant']:
        result = check_docker_service(service_name)
        results.append({'check': f'docker_{service_name}', **result})
        
        status = f"{GREEN}✓{RESET}" if result['healthy'] else f"{RED}✗{RESET}"
        print(f"  {status} {service_name}: {result['actual']}", end='')
        if result['paused']:
            print(f" {YELLOW}(PAUSED){RESET}", end='')
        print()
    
    # 2. Drupal Version
    print(f"\n{BLUE}[2/5] Checking Drupal Version...{RESET}")
    drupal_config = arch.get('services', {}).get('drupal', {})
    version_result = check_drupal_version(drupal_config.get('version', '11'))
    results.append({'check': 'drupal_version', **version_result})
    
    status = f"{GREEN}✓{RESET}" if version_result['healthy'] else f"{RED}✗{RESET}"
    print(f"  {status} Drupal: {version_result['actual']} (expected: {version_result['expected']})")
    
    # 3. Drupal Modules
    print(f"\n{BLUE}[3/5] Checking Drupal Modules...{RESET}")
    core_modules = drupal_config.get('modules', {}).get('core', [])
    modules_result = check_drupal_modules(core_modules)
    results.append({'check': 'drupal_modules', **modules_result})
    
    if modules_result['healthy']:
        print(f"  {GREEN}✓{RESET} All core modules enabled")
    else:
        print(f"  {RED}✗{RESET} Missing modules: {modules_result.get('missing', [])}")
    
    # 4. Qdrant Connection
    print(f"\n{BLUE}[4/5] Checking Qdrant Connection...{RESET}")
    qdrant_config = arch.get('services', {}).get('qdrant', {}).get('config', {})
    qdrant_result = check_qdrant_connection(qdrant_config)
    results.append({'check': 'qdrant_connection', **qdrant_result})
    
    status = f"{GREEN}✓{RESET}" if qdrant_result['healthy'] else f"{RED}✗{RESET}"
    print(f"  {status} Qdrant HTTP: {qdrant_result['status_code']}")
    
    # 5. Self-Healing Scripts
    print(f"\n{BLUE}[5/5] Checking Self-Healing Scripts...{RESET}")
    scripts_result = check_self_healing_scripts()
    results.append({'check': 'self_healing_scripts', **scripts_result})
    
    if scripts_result['healthy']:
        print(f"  {GREEN}✓{RESET} All self-healing scripts present")
    else:
        print(f"  {RED}✗{RESET} Missing scripts: {scripts_result['missing']}")
    
    return results


def main():
    parser = argparse.ArgumentParser(description='Validate architecture.yaml against reality')
    parser.add_argument('--verbose', '-v', action='store_true', help='Verbose output')
    parser.add_argument('--ci', action='store_true', help='CI mode (exit 1 on drift)')
    args = parser.parse_args()
    
    results = validate_architecture(verbose=args.verbose)
    
    # Summary
    healthy = sum(1 for r in results if r.get('healthy', False))
    total = len(results)
    
    print(f"\n{BLUE}{'='*60}{RESET}")
    if healthy == total:
        print(f"{GREEN}  ✓ ALL CHECKS PASSED ({healthy}/{total}){RESET}")
        print(f"{GREEN}  No architecture drift detected{RESET}")
    else:
        print(f"{RED}  ✗ DRIFT DETECTED ({healthy}/{total} passed){RESET}")
        print(f"{YELLOW}  Review failed checks above{RESET}")
    print(f"{BLUE}{'='*60}{RESET}\n")
    
    if args.ci and healthy < total:
        sys.exit(1)
    
    sys.exit(0)


if __name__ == '__main__':
    main()
