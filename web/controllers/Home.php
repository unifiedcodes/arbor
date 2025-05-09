<?php


namespace web\controllers;


use Arbor\contracts\handlers\ControllerInterface;
use Arbor\http\Response;
use Arbor\http\context\RequestContext;
use Arbor\fragment\Fragment;



class Home implements ControllerInterface
{
    protected $db_host;
    protected $router;

    protected $fragment;


    public function __construct(Fragment $fragment)
    {
        $this->fragment = $fragment;
    }
    

    public function process(RequestContext $input): Response
    {
        return new Response(
            'Welcome to Arbor website !',
            200,
            ['Content-Type' => 'text/plain'],
        );
    }
}
