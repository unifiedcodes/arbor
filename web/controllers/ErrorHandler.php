<?php


namespace web\controllers;


use Arbor\contracts\handlers\ControllerInterface;
use Arbor\http\Response;
use Arbor\http\context\RequestContext;


class ErrorHandler implements ControllerInterface
{
    public function process(RequestContext $input): Response
    {
        return new Response(
            'something went wrong.',
            500,
            ['Content-Type' => 'text/plain'],
        );
    }


    public function notFound(): Response
    {
        return new Response(
            'not found !',
            404,
            ['Content-Type' => 'text/plain'],
        );
    }


    public function notAllowed(): Response
    {
        return new Response(
            'not allowed !',
            403,
            ['Content-Type' => 'text/plain'],
        );
    }


    public function methodNotAllowed(): Response
    {
        return new Response(
            'method not allowed !',
            405,
            ['Content-Type' => 'text/plain'],
        );
    }
}
