<?php
/**
 * Script to install gamification tables.
 * Run with: drush scr web/install_gamification_tables.php
 */

$connection = \Drupal::database();
$schema = $connection->schema();

// Tabla de log de XP
if (!$schema->tableExists('gamification_xp_log')) {
    $schema->createTable('gamification_xp_log', [
        'description' => 'Log of XP transactions for gamification',
        'fields' => [
            'id' => [
                'type' => 'serial',
                'not null' => TRUE,
            ],
            'user_id' => [
                'type' => 'int',
                'unsigned' => TRUE,
                'not null' => TRUE,
            ],
            'action' => [
                'type' => 'varchar',
                'length' => 64,
                'not null' => TRUE,
            ],
            'xp_amount' => [
                'type' => 'int',
                'not null' => TRUE,
                'default' => 0,
            ],
            'context_data' => [
                'type' => 'text',
                'size' => 'medium',
            ],
            'created' => [
                'type' => 'int',
                'unsigned' => TRUE,
                'not null' => TRUE,
            ],
        ],
        'primary key' => ['id'],
        'indexes' => [
            'user_id' => ['user_id'],
            'action' => ['action'],
            'created' => ['created'],
        ],
    ]);
    echo "✅ Created: gamification_xp_log\n";
} else {
    echo "ℹ️ Already exists: gamification_xp_log\n";
}

// Tabla de stats de usuario
if (!$schema->tableExists('gamification_user_stats')) {
    $schema->createTable('gamification_user_stats', [
        'description' => 'User gamification stats',
        'fields' => [
            'user_id' => [
                'type' => 'int',
                'unsigned' => TRUE,
                'not null' => TRUE,
            ],
            'total_xp' => [
                'type' => 'int',
                'unsigned' => TRUE,
                'not null' => TRUE,
                'default' => 0,
            ],
            'current_level' => [
                'type' => 'int',
                'unsigned' => TRUE,
                'not null' => TRUE,
                'default' => 1,
            ],
            'streak_days' => [
                'type' => 'int',
                'unsigned' => TRUE,
                'not null' => TRUE,
                'default' => 0,
            ],
            'longest_streak' => [
                'type' => 'int',
                'unsigned' => TRUE,
                'not null' => TRUE,
                'default' => 0,
            ],
            'last_activity_date' => [
                'type' => 'varchar',
                'length' => 10,
            ],
            'updated' => [
                'type' => 'int',
                'unsigned' => TRUE,
            ],
        ],
        'primary key' => ['user_id'],
    ]);
    echo "✅ Created: gamification_user_stats\n";
} else {
    echo "ℹ️ Already exists: gamification_user_stats\n";
}

echo "\nDone!\n";
