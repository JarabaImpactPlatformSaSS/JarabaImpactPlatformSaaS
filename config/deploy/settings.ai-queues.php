<?php

/**
 * @file
 * GAP-AUD-025: Redis queue routing for AI workloads.
 *
 * Include this file in settings.php to route AI queues to Redis
 * for horizontal scaling with dedicated supervisor workers.
 *
 * Usage: Add to settings.php:
 *   if (file_exists($app_root . '/../config/deploy/settings.ai-queues.php')) {
 *     include $app_root . '/../config/deploy/settings.ai-queues.php';
 *   }
 *
 * Requirements:
 *   - Redis module enabled and configured
 *   - redis_reliable queue service available
 *   - Supervisor running with supervisor-ai-workers.conf
 */

// Route AI-intensive queues to Redis for persistence and horizontal scaling.
// These queues are processed by dedicated supervisor workers, NOT by cron.
$settings['queue_service_a2a_task_worker'] = 'queue.redis_reliable';
$settings['queue_service_proactive_insight_engine'] = 'queue.redis_reliable';
$settings['queue_service_quality_evaluation'] = 'queue.redis_reliable';
$settings['queue_service_scheduled_agent'] = 'queue.redis_reliable';

// Queue reliability settings.
// Items are visible again after this many seconds if not acknowledged.
// Prevents stuck items from blocking the queue permanently.
$settings['queue_reliable_lease_time'] = 600;
