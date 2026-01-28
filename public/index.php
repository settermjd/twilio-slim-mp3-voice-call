<?php

declare(strict_types=1);

use App\Application;
use DI\Container;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
$dotenv->required(
    [
        'AUDIO_FILE',
        'PUBLIC_URL',
        'TWILIO_ASSETS_URL',
    ],
)->notEmpty();

$container   = new Container();
$application = new Application($container);

$application->setupRoutes();
$application->run();
