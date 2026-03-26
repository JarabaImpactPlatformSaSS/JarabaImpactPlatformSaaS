#!/bin/bash
# Queue worker wrapper: runs drush queue:run, then sleeps before exit.
# This prevents Supervisor from hot-looping on empty queues.
# SUPERVISOR-SLEEP-001: Workers MUST have sleep 30-60s between executions.
#
# Usage: scripts/queue-worker.sh <queue_name> [time_limit] [sleep_seconds]
# Example: scripts/queue-worker.sh jaraba_i18n_canvas_translation 300 45
QUEUE="$1"
TIME_LIMIT="${2:-300}"
SLEEP_BETWEEN="${3:-30}"

cd /var/www/jaraba
while true; do
  vendor/bin/drush queue:run "$QUEUE" --time-limit="$TIME_LIMIT" 2>&1
  # Sleep between runs to avoid CPU burn on empty queues
  sleep "$SLEEP_BETWEEN"
done
