#!/usr/bin/env php
<?php

/**
 * @file
 * CI/CD alert email sender.
 *
 * Sends failure notifications via Symfony Mailer SMTP transport.
 * Called from GitHub Actions workflows on failure, via SSH.
 *
 * Usage:
 *   php scripts/ci-notify-email.php "Subject" "Body HTML"
 *
 * Bootstraps Drupal to access SMTP config, then sends directly
 * via Symfony Mailer transport (no Drupal mail pipeline needed).
 */

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

// Validate arguments.
if ($argc < 3) {
  fwrite(STDERR, "Usage: php ci-notify-email.php \"Subject\" \"Body HTML\"\n");
  exit(1);
}

$subject = $argv[1];
$bodyHtml = $argv[2];
$to = 'contacto@plataformadeecosistemas.com';

// Bootstrap Drupal minimally.
$autoloader = require __DIR__ . '/../web/autoload.php';
$request = Request::createFromGlobals();
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
$kernel->boot();
$container = $kernel->getContainer();

// Read SMTP config.
$config = $container->get('config.factory')->get('symfony_mailer.mailer_transport.smtp_ionos');
$host = $config->get('configuration.host');
$port = $config->get('configuration.port');
$user = $config->get('configuration.user');
$pass = $config->get('configuration.pass');

if (empty($host) || empty($user)) {
  fwrite(STDERR, "ERROR: SMTP not configured\n");
  exit(1);
}

try {
  $dsn = sprintf('smtp://%s:%s@%s:%d', urlencode($user), urlencode($pass), $host, $port);
  $transport = Transport::fromDsn($dsn);
  $mailer = new Mailer($transport);

  $email = (new Email())
    ->from(new Address($user, 'Jaraba CI/CD'))
    ->to($to)
    ->subject($subject)
    ->html($bodyHtml);

  $mailer->send($email);
  echo "SENT\n";
}
catch (\Throwable $e) {
  fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
  exit(1);
}
