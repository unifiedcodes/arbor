<?php

namespace Arbor\exception;

use Arbor\exception\template\HTML;
use Arbor\http\Response;
use Arbor\facades\Respond;
use Arbor\facades\Route;
use Arbor\facades\RequestStack;
use Arbor\http\context\RequestContext;

class Renderer
{
    protected Normalizer $normalizer;

    public function __construct()
    {
        $this->normalizer = new Normalizer();
    }


    public function render(ExceptionContext $exceptionContext): Response
    {
        $requestContext = RequestStack::getCurrent();

        if ($requestContext) {
            return $this->httpRender($exceptionContext, $requestContext);
        }

        return $this->httpResponse($exceptionContext);
    }


    public function debugRender(ExceptionContext $exceptionContext): Response
    {
        return $this->httpTrailRender($exceptionContext);
    }


    public function httpTrailRender(ExceptionContext $exceptionContext): Response
    {
        return Respond::html(HTML::page($exceptionContext), 500);
    }


    public function httpRender(ExceptionContext $exceptionContext, RequestContext $requestContext): Response
    {
        // 1. Prevent infinite error recursion
        if ($requestContext->isErrorRequest()) {
            return $this->httpResponse($exceptionContext);
        }

        // 2. Mark current request as handling an error
        $errorRequest = $requestContext->withError();
        RequestStack::replaceCurrent($errorRequest);

        // 3. Try resolving a dedicated error page
        $errorRoute = Route::resolveErrorPage($exceptionContext->code());

        if ($errorRoute !== null) {
            return Route::dispatchRoute($errorRoute, $errorRequest);
        }

        // 4. Fallback to default error response
        return $this->httpResponse($exceptionContext);
    }


    protected function httpResponse(ExceptionContext $errCtx): Response
    {
        $code = $errCtx->code();
        $message = $errCtx->message();

        if ($code < 400 || $code >= 600) {
            $code = 500;
            $message = 'Something went wrong';
        }

        return Respond::error(
            $code,
            $message
        );
    }
}
