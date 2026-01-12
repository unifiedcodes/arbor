<?php

namespace Arbor\exception;

use Arbor\exception\template\HTML;
use Arbor\http\Response;
use Arbor\facades\Respond;


class Renderer
{
    protected Normalizer $normalizer;

    public function __construct()
    {
        $this->normalizer = new Normalizer();
    }


    public function httpRender(ExceptionContext $exceptionContext): Response
    {
        // if current requestContext already handling error, respond general error.
        // find error page from router, if found dispatch errorpage.

        print_r($exceptionContext->code());

        return Respond::error(500, 'Something Went Wrong !');
    }


    public function httpTrailRender(ExceptionContext $exceptionContext): Response
    {
        return Respond::html(HTML::page($exceptionContext), 500);
    }
}
