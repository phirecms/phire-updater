<?php

require_once __DIR__ . '/../vendor/autoload.php';

$pop = new Popcorn\Pop();

$pop->get('/', [
    'controller' => 'Phire\Updater\Controller\IndexController',
    'action'     => 'index'
]);

$pop->get('/latest/:resource', [
    'controller' => 'Phire\Updater\Controller\IndexController',
    'action'     => 'latest'
]);

$pop->post('/fetch', [
    'controller' => 'Phire\Updater\Controller\IndexController',
    'action'     => 'fetch'
]);

$pop->get('*', [
    'controller' => 'Phire\Updater\Controller\IndexController',
    'action'     => 'error'
]);

$pop->run();
