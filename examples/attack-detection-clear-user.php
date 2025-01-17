<?php

declare(strict_types=1);

use Fschmtt\Keycloak\Keycloak;

require_once __DIR__ . '/../vendor/autoload.php';

$keycloak = new Keycloak(
    'http://keycloak:8080',
    'admin',
    'admin',
);

$keycloak->attackDetection()->clearUser(
    realm: 'master',
    userId: 'afab8ba7-e278-4dda-8970-bd5a2a4c7bfb',
);
