<?php

namespace Arbor\exception;

use Arbor\exception\template\HTML;
use Arbor\http\Response;
use Arbor\facades\Respond;
use Arbor\facades\Route;
use Arbor\facades\RequestStack;
use Arbor\http\context\RequestContext;

/**
 * Renderer class responsible for rendering exceptions into HTTP responses.
 * 
 * This class handles the transformation of exceptions into appropriate HTTP responses,
 * supporting both debug mode with detailed error trails and production mode with
 * custom error pages or fallback responses. It prevents infinite error recursion
 * by tracking error handling state in the request context.
 */
class Renderer
{
    /**
     * Normalizer instance for standardizing exception data.
     *
     * @var Normalizer
     */
    protected Normalizer $normalizer;

    /**
     * Initialize the renderer with a normalizer instance.
     */
    public function __construct()
    {
        $this->normalizer = new Normalizer();
    }


    /**
     * Render an exception into an HTTP response.
     * 
     * Determines the appropriate rendering strategy based on whether a request
     * context exists. With a context, attempts to use custom error pages;
     * without one, falls back to a basic HTTP response.
     *
     * @param ExceptionContext $exceptionContext The exception context to render
     * @return Response The HTTP response containing the rendered exception
     */
    public function render(ExceptionContext $exceptionContext): Response
    {
        $requestContext = RequestStack::getCurrent();

        if ($requestContext) {
            return $this->httpRender($exceptionContext, $requestContext);
        }

        return $this->httpResponse($exceptionContext);
    }


    /**
     * Render an exception with full debug information.
     * 
     * Used in development/debug mode to provide detailed error trails
     * including stack traces and exception details.
     *
     * @param ExceptionContext $exceptionContext The exception context to render
     * @return Response The HTTP response with detailed debug information
     */
    public function debugRender(ExceptionContext $exceptionContext): Response
    {
        return $this->httpTrailRender($exceptionContext);
    }


    /**
     * Render an exception as an HTML error trail page.
     * 
     * Generates a detailed HTML page showing the exception information,
     * stack trace, and other debugging details. Always returns a 500 status.
     *
     * @param ExceptionContext $exceptionContext The exception context to render
     * @return Response The HTTP response containing the HTML error trail
     */
    public function httpTrailRender(ExceptionContext $exceptionContext): Response
    {
        return Respond::html(HTML::page($exceptionContext), 500);
    }


    /**
     * Render an exception within an HTTP request context.
     * 
     * This method implements a multi-step error handling strategy:
     * 1. Checks for infinite error recursion and prevents it
     * 2. Marks the request as handling an error
     * 3. Attempts to resolve and dispatch a custom error page route
     * 4. Falls back to default error response if no custom page exists
     *
     * @param ExceptionContext $exceptionContext The exception context to render
     * @param RequestContext $requestContext The current HTTP request context
     * @return Response The HTTP response for the exception
     */
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


    /**
     * Generate a basic HTTP error response.
     * 
     * Creates a simple error response with the appropriate HTTP status code
     * and message. Validates the status code and defaults to 500 if the code
     * is outside the valid HTTP error range (400-599).
     *
     * @param ExceptionContext $errCtx The exception context containing error details
     * @return Response The HTTP error response
     */
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
