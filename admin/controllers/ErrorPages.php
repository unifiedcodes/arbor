<?php


namespace admin\controllers;


use Arbor\contracts\handlers\ControllerInterface;
use Arbor\http\Response;
use Arbor\http\context\RequestContext;


class ErrorPages implements ControllerInterface
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
            'handler reached for not found !',
            404,
            ['Content-Type' => 'text/plain'],
        );
    }


    public function notAllowed(): Response
    {
        return new Response(
            'handler reached for not allowed !',
            403,
            ['Content-Type' => 'text/plain'],
        );
    }


    public function methodNotAllowed(): Response
    {
        return new Response(
            'handler reached for method not allowed !',
            405,
            ['Content-Type' => 'text/plain'],
        );
    }
}
