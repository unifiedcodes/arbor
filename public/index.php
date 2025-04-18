<?php

use Arbor\Autoloader;
use Arbor\bootstrap\App;


require_once '../Arbor/Autoloader.php';
$autoloader = new Autoloader('../');


$app = (new App())
    ->withConfig('../configs/')
    ->onEnvironment('development')
    ->boot();

// Handling Incoming Request
$response = $app->handleHTTP();
$response->send();