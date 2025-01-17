<?php

declare(strict_types=1);

use Fschmtt\Keycloak\Keycloak;

require_once __DIR__ . '/../vendor/autoload.php';

$keycloak = new Keycloak(
    'http://keycloak:8080',
    'admin',
    'admin',
);

$keycloak->attackDetection()->clear(
    realm: 'master'
);
