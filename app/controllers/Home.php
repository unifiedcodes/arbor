<?php


namespace app\controllers;


use Arbor\contracts\handlers\ControllerInterface;
use Arbor\http\Response;
use Arbor\http\context\RequestContext;



class Home implements ControllerInterface
{
    protected $db_host;
    protected $router;

    public function __construct()
    {
        // define DI bound dependencies here
    }


    public function process(RequestContext $input): Response
    {
        return new Response(
            'Welcome to Arbor !',
            200,
            ['Content-Type' => 'text/plain'],
        );
    }
}
