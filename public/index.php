<?php

require_once __DIR__ . '/../vendor/autoload.php';

$pop = new Popcorn\Pop();

$pop->post('/', [
    'controller' => 'Phire\Updater\Controller\IndexController',
    'action'     => 'index'
]);

$pop->post('/fetch', [
    'controller' => 'Phire\Updater\Controller\IndexController',
    'action'     => 'fetch'
]);

$pop->addRoutes('get,post', '*', [
    'controller' => 'Phire\Updater\Controller\IndexController',
    'action'     => 'error'
]);

$pop->run();
