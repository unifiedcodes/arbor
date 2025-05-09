<?php

use Arbor\Autoloader;
use Arbor\bootstrap\App;


require_once '../Arbor/Autoloader.php';
$autoloader = new Autoloader('../');


$app = (new App())
    ->withConfig('../configs/')
    
    //environment setting effect configuration merging from all the apps installed.
    ->onEnvironment('development') 

    // uses file instead of array from global configs/app.php.
    ->useAppConfig('admin/configs/app.php')
    ->useAppConfig('web/configs/app.php')

    ->boot();


$response = $app->handleHTTP();
$response->send();
