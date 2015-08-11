<?php

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use RouteHandler\ControllerHandlerHook;
use Slim\Slim;
use Slim\Views\Twig;
use Slim\Views\TwigExtension;

require '../vendor/autoload.php';

// Prepare app
$app = new Slim(
    [
        'templates.path' => '../templates',
    ]
);

// Create monolog logger and store logger in container as singleton 
// (Singleton resources retrieve the same log resource definition each time)
$app->container->singleton(
    'log',
    function () {
        $log = new Logger('slim-skeleton');
        $log->pushHandler(new StreamHandler('../logs/app.log', Logger::DEBUG));

        return $log;
    }
);

// Prepare view
$app->view(new Twig());
$app->view->parserOptions    = [
    'charset'          => 'utf-8',
    'cache'            => realpath('../templates/cache'),
    'auto_reload'      => true,
    'strict_variables' => false,
    'autoescape'       => true
];
$app->view->parserExtensions = [new TwigExtension()];

$routerConfig = __DIR__ . '/../config/routes.yml';
$app->hook('slim.before.router', new ControllerHandlerHook($app, $routerConfig));

// Run app
$app->run();
