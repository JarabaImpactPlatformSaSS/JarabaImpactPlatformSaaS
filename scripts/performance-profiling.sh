#!/usr/bin/env bash
# =============================================================================
# Jaraba Impact Platform SaaS - Performance Profiling Script
# Phase 5 Go-Live Preparation
# =============================================================================
#
# This script runs a comprehensive performance audit against the current
# environment and outputs a summary report. It checks PHP OPcache, MariaDB
# slow-query configuration, Drupal caches, Redis connectivity, and CSS/JS
# aggregation settings.
#
# Usage:
#   chmod +x scripts/performance-profiling.sh
#   ./scripts/performance-profiling.sh [--json]
#
# Requirements:
#   - PHP CLI with OPcache extension
#   - MySQL/MariaDB client
#   - Drush (available in PATH or via vendor/bin/drush)
#   - redis-cli (optional, for Redis checks)
# =============================================================================

set -euo pipefail

# ---------------------------------------------------------------------------
# Configuration
# ---------------------------------------------------------------------------
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
DRUSH="${PROJECT_ROOT}/vendor/bin/drush"
REPORT_FILE="${PROJECT_ROOT}/performance-report-$(date +%Y%m%d-%H%M%S).txt"
JSON_OUTPUT=false
PASS_COUNT=0
WARN_COUNT=0
FAIL_COUNT=0

# Colors (only when stdout is a terminal)
if [ -t 1 ]; then
  GREEN='\033[0;32m'
  YELLOW='\033[1;33m'
  RED='\033[0;31m'
  CYAN='\033[0;36m'
  BOLD='\033[1m'
  NC='\033[0m'
else
  GREEN='' YELLOW='' RED='' CYAN='' BOLD='' NC=''
fi

# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------
log_header() {
  echo ""
  echo -e "${CYAN}${BOLD}=== $1 ===${NC}"
  echo "=== $1 ===" >> "$REPORT_FILE"
}

log_pass() {
  echo -e "  ${GREEN}[PASS]${NC} $1"
  echo "  [PASS] $1" >> "$REPORT_FILE"
  ((PASS_COUNT++)) || true
}

log_warn() {
  echo -e "  ${YELLOW}[WARN]${NC} $1"
  echo "  [WARN] $1" >> "$REPORT_FILE"
  ((WARN_COUNT++)) || true
}

log_fail() {
  echo -e "  ${RED}[FAIL]${NC} $1"
  echo "  [FAIL] $1" >> "$REPORT_FILE"
  ((FAIL_COUNT++)) || true
}

log_info() {
  echo -e "  ${BOLD}[INFO]${NC} $1"
  echo "  [INFO] $1" >> "$REPORT_FILE"
}

parse_args() {
  for arg in "$@"; do
    case "$arg" in
      --json) JSON_OUTPUT=true ;;
      --help|-h)
        echo "Usage: $0 [--json]"
        echo "  --json  Output results as JSON (for CI pipelines)"
        exit 0
        ;;
    esac
  done
}

# ---------------------------------------------------------------------------
# 1. PHP OPcache Status
# ---------------------------------------------------------------------------
check_opcache() {
  log_header "PHP OPcache Status"

  if ! command -v php &>/dev/null; then
    log_fail "PHP CLI not found in PATH"
    return
  fi

  local php_version
  php_version=$(php -r 'echo PHP_VERSION;' 2>/dev/null || echo "unknown")
  log_info "PHP version: $php_version"

  local opcache_enabled
  opcache_enabled=$(php -r '
    if (function_exists("opcache_get_status")) {
      $s = @opcache_get_status(false);
      if ($s && isset($s["opcache_enabled"]) && $s["opcache_enabled"]) {
        echo "1";
      } else {
        echo "0";
      }
    } else {
      echo "na";
    }
  ' 2>/dev/null || echo "error")

  case "$opcache_enabled" in
    1)
      log_pass "OPcache is enabled"
      # Gather statistics
      php -r '
        $s = opcache_get_status(false);
        $c = opcache_get_configuration();
        $mem = $s["memory_usage"];
        $stats = $s["opcache_statistics"];
        $interned = $s["interned_strings_usage"] ?? [];
        printf("  Memory used: %.1f MB / %.1f MB (%.0f%%)\n",
          $mem["used_memory"]/1048576,
          ($mem["used_memory"]+$mem["free_memory"])/1048576,
          $mem["used_memory"]/($mem["used_memory"]+$mem["free_memory"])*100);
        printf("  Cached scripts: %d / max %d\n",
          $stats["num_cached_scripts"],
          $c["directives"]["opcache.max_accelerated_files"]);
        printf("  Hit rate: %.1f%%\n", $stats["opcache_hit_rate"]);
        if ($stats["opcache_hit_rate"] < 90) {
          echo "  WARNING: Hit rate below 90%, consider warming cache\n";
        }
      ' 2>/dev/null | while IFS= read -r line; do
        log_info "$line"
      done
      ;;
    0)
      log_warn "OPcache extension loaded but NOT enabled (opcache.enable=0)"
      ;;
    na)
      log_fail "OPcache extension is not loaded"
      ;;
    *)
      log_fail "Could not determine OPcache status"
      ;;
  esac

  # Check JIT (PHP 8.x)
  local jit_enabled
  jit_enabled=$(php -r '
    if (function_exists("opcache_get_status")) {
      $s = @opcache_get_status(false);
      if (isset($s["jit"]["enabled"]) && $s["jit"]["enabled"]) {
        echo "1";
      } else {
        echo "0";
      }
    } else {
      echo "na";
    }
  ' 2>/dev/null || echo "na")

  case "$jit_enabled" in
    1) log_pass "JIT compiler is enabled" ;;
    0) log_info "JIT compiler is disabled (optional for web workloads)" ;;
  esac
}

# ---------------------------------------------------------------------------
# 2. MariaDB / MySQL Slow Query Log
# ---------------------------------------------------------------------------
check_mariadb_slow_query() {
  log_header "MariaDB Slow Query Log Configuration"

  if ! command -v mysql &>/dev/null; then
    log_warn "MySQL/MariaDB client not found in PATH; skipping DB checks"
    return
  fi

  # Attempt connection using Drupal's settings
  local db_url=""
  if [ -x "$DRUSH" ]; then
    db_url=$("$DRUSH" sql:connect 2>/dev/null || echo "")
  fi

  # Fallback: try default socket connection
  local mysql_cmd="mysql"
  if [ -n "$db_url" ]; then
    mysql_cmd="$DRUSH sql:cli"
  fi

  local slow_query_log
  slow_query_log=$(echo "SHOW VARIABLES LIKE 'slow_query_log';" | $mysql_cmd 2>/dev/null | grep -i slow_query_log | awk '{print $2}' || echo "unknown")

  if [ "$slow_query_log" = "ON" ]; then
    log_pass "Slow query log is enabled"

    local long_query_time
    long_query_time=$(echo "SHOW VARIABLES LIKE 'long_query_time';" | $mysql_cmd 2>/dev/null | grep -i long_query_time | awk '{print $2}' || echo "unknown")
    log_info "long_query_time = ${long_query_time}s"

    if command -v bc &>/dev/null && [ "$long_query_time" != "unknown" ]; then
      local is_high
      is_high=$(echo "$long_query_time > 2" | bc 2>/dev/null || echo "0")
      if [ "$is_high" = "1" ]; then
        log_warn "long_query_time is > 2s; consider lowering to 1s for profiling"
      fi
    fi

    local slow_log_file
    slow_log_file=$(echo "SHOW VARIABLES LIKE 'slow_query_log_file';" | $mysql_cmd 2>/dev/null | grep -i slow_query_log_file | awk '{print $2}' || echo "unknown")
    log_info "Slow log file: $slow_log_file"

  elif [ "$slow_query_log" = "OFF" ]; then
    log_warn "Slow query log is DISABLED; enable for performance profiling"
    log_info "  Run: SET GLOBAL slow_query_log = 'ON'; SET GLOBAL long_query_time = 1;"
  else
    log_warn "Could not determine slow query log status (connection issue?)"
  fi

  # Check query cache (MariaDB)
  local query_cache
  query_cache=$(echo "SHOW VARIABLES LIKE 'query_cache_type';" | $mysql_cmd 2>/dev/null | grep -i query_cache_type | awk '{print $2}' || echo "unknown")
  if [ "$query_cache" != "unknown" ]; then
    log_info "Query cache type: $query_cache"
  fi
}

# ---------------------------------------------------------------------------
# 3. Drupal Cache Status
# ---------------------------------------------------------------------------
check_drupal_cache() {
  log_header "Drupal Cache Status"

  if [ ! -x "$DRUSH" ] && ! command -v drush &>/dev/null; then
    log_fail "Drush not found at $DRUSH and not in PATH"
    return
  fi

  # Use project drush or global
  local drush_bin="$DRUSH"
  if [ ! -x "$drush_bin" ]; then
    drush_bin="drush"
  fi

  # Check Drupal bootstrap
  local status
  status=$($drush_bin status --format=json 2>/dev/null || echo "{}")

  local drupal_version
  drupal_version=$(echo "$status" | php -r '
    $d = json_decode(file_get_contents("php://stdin"), true);
    echo $d["drupal-version"] ?? "unknown";
  ' 2>/dev/null || echo "unknown")
  log_info "Drupal version: $drupal_version"

  local db_status
  db_status=$(echo "$status" | php -r '
    $d = json_decode(file_get_contents("php://stdin"), true);
    echo $d["db-status"] ?? "unknown";
  ' 2>/dev/null || echo "unknown")

  if [ "$db_status" = "Connected" ]; then
    log_pass "Database connection: Connected"
  else
    log_warn "Database connection: $db_status"
  fi

  # CSS/JS query string (cache buster)
  local css_js_query
  css_js_query=$($drush_bin state:get system.css_js_query_string 2>/dev/null || echo "not set")
  if [ -n "$css_js_query" ] && [ "$css_js_query" != "not set" ]; then
    log_pass "CSS/JS query string is set: $css_js_query"
  else
    log_warn "CSS/JS query string not set; cache may not be properly built"
  fi

  # Internal page cache
  local page_cache
  page_cache=$($drush_bin pm:list --type=module --status=enabled --format=list 2>/dev/null | grep -c "page_cache" || echo "0")
  if [ "$page_cache" -ge 1 ]; then
    log_pass "Internal Page Cache module is enabled"
  else
    log_warn "Internal Page Cache module is NOT enabled"
  fi

  # Dynamic page cache
  local dynamic_cache
  dynamic_cache=$($drush_bin pm:list --type=module --status=enabled --format=list 2>/dev/null | grep -c "dynamic_page_cache" || echo "0")
  if [ "$dynamic_cache" -ge 1 ]; then
    log_pass "Dynamic Page Cache module is enabled"
  else
    log_warn "Dynamic Page Cache module is NOT enabled"
  fi

  # Big Pipe
  local bigpipe
  bigpipe=$($drush_bin pm:list --type=module --status=enabled --format=list 2>/dev/null | grep -c "big_pipe" || echo "0")
  if [ "$bigpipe" -ge 1 ]; then
    log_pass "BigPipe module is enabled"
  else
    log_info "BigPipe module is not enabled (recommended for authenticated users)"
  fi

  # Cache rebuild timing
  log_info "Running cache rebuild (timing)..."
  local start_time end_time rebuild_time
  start_time=$(date +%s%N)
  $drush_bin cr 2>/dev/null || true
  end_time=$(date +%s%N)
  rebuild_time=$(( (end_time - start_time) / 1000000 ))
  log_info "Cache rebuild completed in ${rebuild_time}ms"

  if [ "$rebuild_time" -gt 30000 ]; then
    log_warn "Cache rebuild took > 30s; investigate module count and hooks"
  elif [ "$rebuild_time" -gt 10000 ]; then
    log_info "Cache rebuild took > 10s; acceptable but monitor in production"
  else
    log_pass "Cache rebuild time is healthy (< 10s)"
  fi
}

# ---------------------------------------------------------------------------
# 4. Enabled Modules Count
# ---------------------------------------------------------------------------
check_module_count() {
  log_header "Enabled Modules"

  local drush_bin="$DRUSH"
  if [ ! -x "$drush_bin" ]; then
    drush_bin="drush"
  fi

  if ! command -v "$drush_bin" &>/dev/null && [ ! -x "$drush_bin" ]; then
    log_warn "Drush not available; skipping module count"
    return
  fi

  local total_enabled core_enabled contrib_enabled custom_enabled
  total_enabled=$($drush_bin pm:list --status=enabled --format=list 2>/dev/null | wc -l || echo "0")
  core_enabled=$($drush_bin pm:list --status=enabled --format=list --core 2>/dev/null | wc -l || echo "0")
  contrib_enabled=$($drush_bin pm:list --status=enabled --package="Other" --format=list 2>/dev/null | wc -l || echo "0")

  log_info "Total enabled modules: $total_enabled"
  log_info "Core modules: $core_enabled"

  # Custom module count from filesystem
  if [ -d "$PROJECT_ROOT/web/modules/custom" ]; then
    custom_enabled=$(find "$PROJECT_ROOT/web/modules/custom" -maxdepth 2 -name "*.info.yml" 2>/dev/null | wc -l || echo "0")
    log_info "Custom modules (info.yml count): $custom_enabled"
  fi

  total_enabled=$(echo "$total_enabled" | tr -d '[:space:]')
  if [ "$total_enabled" -gt 200 ]; then
    log_warn "Over 200 modules enabled ($total_enabled); review for unnecessary modules"
  elif [ "$total_enabled" -gt 150 ]; then
    log_info "Module count ($total_enabled) is moderate; review periodically"
  else
    log_pass "Module count ($total_enabled) is within healthy range"
  fi
}

# ---------------------------------------------------------------------------
# 5. Redis Connectivity
# ---------------------------------------------------------------------------
check_redis() {
  log_header "Redis Cache Backend"

  # Check if Redis module is enabled in Drupal
  local drush_bin="$DRUSH"
  if [ ! -x "$drush_bin" ]; then
    drush_bin="drush"
  fi

  local redis_module_enabled
  redis_module_enabled=$($drush_bin pm:list --status=enabled --format=list 2>/dev/null | grep -c "^redis$" || echo "0")

  if [ "$redis_module_enabled" -ge 1 ]; then
    log_pass "Redis Drupal module is enabled"
  else
    log_warn "Redis Drupal module is NOT enabled"
  fi

  # Check redis-cli connectivity
  if command -v redis-cli &>/dev/null; then
    local redis_host="${REDIS_HOST:-127.0.0.1}"
    local redis_port="${REDIS_PORT:-6379}"

    local ping_result
    ping_result=$(redis-cli -h "$redis_host" -p "$redis_port" ping 2>/dev/null || echo "FAILED")

    if [ "$ping_result" = "PONG" ]; then
      log_pass "Redis server is reachable at ${redis_host}:${redis_port}"

      # Memory info
      local redis_mem
      redis_mem=$(redis-cli -h "$redis_host" -p "$redis_port" info memory 2>/dev/null | grep "used_memory_human" | cut -d: -f2 | tr -d '[:space:]' || echo "unknown")
      log_info "Redis memory usage: $redis_mem"

      # Key count
      local redis_keys
      redis_keys=$(redis-cli -h "$redis_host" -p "$redis_port" dbsize 2>/dev/null | grep -oP '\d+' || echo "unknown")
      log_info "Redis key count: $redis_keys"

      # Check eviction policy
      local eviction_policy
      eviction_policy=$(redis-cli -h "$redis_host" -p "$redis_port" config get maxmemory-policy 2>/dev/null | tail -1 || echo "unknown")
      log_info "Eviction policy: $eviction_policy"
      if [ "$eviction_policy" = "noeviction" ]; then
        log_warn "Eviction policy is 'noeviction'; consider 'allkeys-lru' for cache workloads"
      fi
    else
      log_fail "Redis server is NOT reachable at ${redis_host}:${redis_port}"
    fi
  else
    log_info "redis-cli not found in PATH; install to enable Redis connectivity checks"

    # Alternative: check settings.php for Redis config
    local settings_file="$PROJECT_ROOT/web/sites/default/settings.php"
    if [ -f "$settings_file" ]; then
      local redis_in_settings
      redis_in_settings=$(grep -c "redis" "$settings_file" 2>/dev/null || echo "0")
      if [ "$redis_in_settings" -gt 0 ]; then
        log_info "Redis references found in settings.php ($redis_in_settings occurrences)"
      fi
    fi
  fi
}

# ---------------------------------------------------------------------------
# 6. CSS/JS Aggregation
# ---------------------------------------------------------------------------
check_css_js_aggregation() {
  log_header "CSS/JS Aggregation Settings"

  local drush_bin="$DRUSH"
  if [ ! -x "$drush_bin" ]; then
    drush_bin="drush"
  fi

  if ! command -v "$drush_bin" &>/dev/null && [ ! -x "$drush_bin" ]; then
    log_warn "Drush not available; skipping aggregation checks"
    return
  fi

  # CSS aggregation
  local css_preprocess
  css_preprocess=$($drush_bin config:get system.performance css.preprocess 2>/dev/null || echo "unknown")
  if [ "$css_preprocess" = "true" ] || [ "$css_preprocess" = "1" ]; then
    log_pass "CSS aggregation is ENABLED"
  elif [ "$css_preprocess" = "false" ] || [ "$css_preprocess" = "0" ]; then
    log_fail "CSS aggregation is DISABLED; enable for production"
    log_info "  Run: drush config:set system.performance css.preprocess 1"
  else
    log_warn "Could not determine CSS aggregation status: $css_preprocess"
  fi

  # JS aggregation
  local js_preprocess
  js_preprocess=$($drush_bin config:get system.performance js.preprocess 2>/dev/null || echo "unknown")
  if [ "$js_preprocess" = "true" ] || [ "$js_preprocess" = "1" ]; then
    log_pass "JS aggregation is ENABLED"
  elif [ "$js_preprocess" = "false" ] || [ "$js_preprocess" = "0" ]; then
    log_fail "JS aggregation is DISABLED; enable for production"
    log_info "  Run: drush config:set system.performance js.preprocess 1"
  else
    log_warn "Could not determine JS aggregation status: $js_preprocess"
  fi

  # Page cache max age
  local page_cache_max_age
  page_cache_max_age=$($drush_bin config:get system.performance cache.page.max_age 2>/dev/null || echo "unknown")
  if [ "$page_cache_max_age" != "unknown" ]; then
    log_info "Page cache max age: ${page_cache_max_age}s"
    if [ "$page_cache_max_age" = "0" ]; then
      log_warn "Page cache max age is 0 (no caching); set to at least 300 for production"
    elif [ "$page_cache_max_age" -lt 300 ] 2>/dev/null; then
      log_warn "Page cache max age ($page_cache_max_age) is low; consider 900+ for production"
    else
      log_pass "Page cache max age ($page_cache_max_age) is adequate"
    fi
  fi

  # Gzip compression
  local gzip
  gzip=$($drush_bin config:get system.performance css.gzip 2>/dev/null || echo "unknown")
  if [ "$gzip" = "true" ] || [ "$gzip" = "1" ]; then
    log_pass "CSS gzip compression is enabled"
  else
    log_info "CSS gzip compression status: $gzip"
  fi

  local js_gzip
  js_gzip=$($drush_bin config:get system.performance js.gzip 2>/dev/null || echo "unknown")
  if [ "$js_gzip" = "true" ] || [ "$js_gzip" = "1" ]; then
    log_pass "JS gzip compression is enabled"
  else
    log_info "JS gzip compression status: $js_gzip"
  fi
}

# ---------------------------------------------------------------------------
# 7. Additional Checks
# ---------------------------------------------------------------------------
check_additional() {
  log_header "Additional Performance Indicators"

  # PHP memory limit
  local memory_limit
  memory_limit=$(php -r 'echo ini_get("memory_limit");' 2>/dev/null || echo "unknown")
  log_info "PHP memory_limit: $memory_limit"

  # PHP max execution time
  local max_exec
  max_exec=$(php -r 'echo ini_get("max_execution_time");' 2>/dev/null || echo "unknown")
  log_info "PHP max_execution_time: ${max_exec}s"

  # Realpath cache
  local realpath_size
  realpath_size=$(php -r 'echo ini_get("realpath_cache_size");' 2>/dev/null || echo "unknown")
  log_info "PHP realpath_cache_size: $realpath_size"
  if [ "$realpath_size" = "4096k" ] || [ "$realpath_size" = "4M" ]; then
    log_pass "Realpath cache size ($realpath_size) is adequate"
  elif [ "$realpath_size" = "16k" ] || [ "$realpath_size" = "16K" ]; then
    log_warn "Realpath cache size ($realpath_size) is low; set to 4096K+ for Drupal"
  fi

  # Check for Xdebug (should be OFF in production)
  local xdebug_loaded
  xdebug_loaded=$(php -m 2>/dev/null | grep -ci "xdebug" || echo "0")
  if [ "$xdebug_loaded" -ge 1 ]; then
    log_fail "Xdebug is loaded! Disable in production for 2-3x performance gain"
  else
    log_pass "Xdebug is not loaded (correct for production)"
  fi

  # Composer autoload optimization
  if [ -f "$PROJECT_ROOT/vendor/composer/autoload_classmap.php" ]; then
    local classmap_size
    classmap_size=$(wc -l < "$PROJECT_ROOT/vendor/composer/autoload_classmap.php" 2>/dev/null || echo "0")
    if [ "$classmap_size" -gt 100 ]; then
      log_pass "Composer classmap is optimized ($classmap_size entries)"
    else
      log_warn "Composer classmap seems small; run 'composer dump-autoload --optimize'"
    fi
  fi

  # File permissions on sites/default/files
  local files_dir="$PROJECT_ROOT/web/sites/default/files"
  if [ -d "$files_dir" ]; then
    local files_count
    files_count=$(find "$files_dir" -maxdepth 1 -type d | wc -l || echo "0")
    log_info "Public files directory exists with $files_count subdirectories"
  fi
}

# ---------------------------------------------------------------------------
# Summary
# ---------------------------------------------------------------------------
print_summary() {
  log_header "Performance Profiling Summary"

  local total=$((PASS_COUNT + WARN_COUNT + FAIL_COUNT))
  echo ""
  echo -e "${BOLD}Results:${NC}"
  echo -e "  ${GREEN}PASS: $PASS_COUNT${NC}"
  echo -e "  ${YELLOW}WARN: $WARN_COUNT${NC}"
  echo -e "  ${RED}FAIL: $FAIL_COUNT${NC}"
  echo -e "  Total checks: $total"
  echo ""

  {
    echo ""
    echo "Results:"
    echo "  PASS: $PASS_COUNT"
    echo "  WARN: $WARN_COUNT"
    echo "  FAIL: $FAIL_COUNT"
    echo "  Total checks: $total"
  } >> "$REPORT_FILE"

  if [ "$FAIL_COUNT" -gt 0 ]; then
    echo -e "${RED}${BOLD}Action required: $FAIL_COUNT critical issues found.${NC}"
    echo "Action required: $FAIL_COUNT critical issues found." >> "$REPORT_FILE"
  elif [ "$WARN_COUNT" -gt 0 ]; then
    echo -e "${YELLOW}${BOLD}Review recommended: $WARN_COUNT warnings found.${NC}"
    echo "Review recommended: $WARN_COUNT warnings found." >> "$REPORT_FILE"
  else
    echo -e "${GREEN}${BOLD}All checks passed. System is production-ready.${NC}"
    echo "All checks passed. System is production-ready." >> "$REPORT_FILE"
  fi

  echo ""
  echo -e "Full report saved to: ${BOLD}${REPORT_FILE}${NC}"
  echo ""

  if [ "$JSON_OUTPUT" = true ]; then
    echo "{"
    echo "  \"timestamp\": \"$(date -u +%Y-%m-%dT%H:%M:%SZ)\","
    echo "  \"pass\": $PASS_COUNT,"
    echo "  \"warn\": $WARN_COUNT,"
    echo "  \"fail\": $FAIL_COUNT,"
    echo "  \"total\": $total,"
    echo "  \"report_file\": \"$REPORT_FILE\""
    echo "}"
  fi
}

# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------
main() {
  parse_args "$@"

  echo -e "${BOLD}Jaraba Impact Platform - Performance Profiling${NC}"
  echo -e "Date: $(date)"
  echo -e "Environment: ${PROJECT_ROOT}"
  echo ""

  {
    echo "Jaraba Impact Platform - Performance Profiling"
    echo "Date: $(date)"
    echo "Environment: ${PROJECT_ROOT}"
  } > "$REPORT_FILE"

  check_opcache
  check_mariadb_slow_query
  check_drupal_cache
  check_module_count
  check_redis
  check_css_js_aggregation
  check_additional
  print_summary

  # Exit code: 1 if any FAIL, 0 otherwise
  if [ "$FAIL_COUNT" -gt 0 ]; then
    exit 1
  fi
  exit 0
}

main "$@"
