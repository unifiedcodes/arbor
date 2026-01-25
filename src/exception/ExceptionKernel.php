<?php

namespace Arbor\exception;


use Arbor\config\ConfigValue;
use Arbor\http\Response;
use Arbor\exception\Renderer;
use Arbor\exception\Normalizer;
use Arbor\facades\RequestStack;
use Arbor\http\context\RequestContext;
use Throwable;
use ErrorException;

/**
 * ExceptionKernel
 * 
 * Central exception and error handling kernel for the Arbor framework.
 * This class manages the registration and processing of exceptions, errors,
 * and fatal shutdown errors throughout the application lifecycle.
 * 
 * The kernel intercepts all exceptions and PHP errors, converts them into
 * a normalized format, and renders appropriate responses based on the
 * application's debug mode configuration.
 * 
 * @package Arbor\exception
 */
class ExceptionKernel
{
    /**
     * Renderer instance for generating exception responses
     * 
     * @var Renderer
     */
    protected Renderer $renderer;

    /**
     * Normalizer instance for converting exceptions into ExceptionContext objects
     * 
     * @var Normalizer
     */
    protected Normalizer $normalizer;

    /**
     * Constructor
     * 
     * Initializes the exception kernel with debug mode configuration and
     * instantiates the renderer and normalizer components.
     * 
     * @param bool $isDebug Debug mode flag injected from configuration (root.is_debug)
     */
    public function __construct(
        #[ConfigValue('root.is_debug')]
        protected bool $isDebug
    ) {
        $this->renderer = new Renderer();
        $this->normalizer = new Normalizer();
    }

    /**
     * Bind exception handlers
     * 
     * Registers this kernel's methods as the global exception handler,
     * error handler, and shutdown function to catch all errors and
     * exceptions throughout the application.
     * 
     * @return void
     */
    public function bind(): void
    {
        set_exception_handler([$this, 'handleException']);
        set_error_handler([$this, 'handleError']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    /**
     * Handle uncaught exceptions
     * 
     * Processes an uncaught exception, converts it to a response,
     * and sends the response to the client. This method is registered
     * as the global exception handler via set_exception_handler().
     * 
     * @param Throwable $e The exception to handle
     * @return void
     */
    public function handleException($e)
    {
        $this->handle($e)->send();
    }

    /**
     * Handle PHP errors
     * 
     * Converts PHP errors into ErrorException instances to be handled
     * uniformly with exceptions. This method is registered as the global
     * error handler via set_error_handler().
     * 
     * Respects the error_reporting() configuration and only handles errors
     * that match the current error reporting level.
     * 
     * @param int $severity The severity level of the error (E_WARNING, E_NOTICE, etc.)
     * @param string $message The error message
     * @param string $file The filename where the error occurred
     * @param int $line The line number where the error occurred
     * @return bool Returns false if the error should not be handled
     * @throws ErrorException Always throws an exception for reportable errors
     */
    public function handleError(
        int $severity,
        string $message,
        string $file,
        int $line
    ): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new ErrorException(
            $message,
            0,
            $severity,
            $file,
            $line
        );
    }

    /**
     * Handle fatal errors during shutdown
     * 
     * Captures fatal errors that occur during script shutdown (E_ERROR, E_PARSE,
     * E_CORE_ERROR, E_COMPILE_ERROR) and processes them as exceptions. This method
     * is registered via register_shutdown_function().
     * 
     * Non-fatal errors are ignored as they should have been handled by handleError().
     * 
     * @return void
     */
    public function handleShutdown(): void
    {
        $error = error_get_last();

        if (!$error) {
            return;
        }

        if (!in_array($error['type'], [
            E_ERROR,
            E_PARSE,
            E_CORE_ERROR,
            E_COMPILE_ERROR
        ], true)) {
            return;
        }

        $exception = new ErrorException(
            $error['message'],
            0,
            $error['type'],
            $error['file'],
            $error['line']
        );

        $this->handle($exception);
    }

    /**
     * Handle an exception and generate a response
     * 
     * Core exception handling logic that normalizes the exception within
     * the current request context and renders an appropriate HTTP response.
     * 
     * @param Throwable $error The exception or error to handle
     * @return Response The HTTP response containing the rendered exception
     */
    public function handle(Throwable $error): Response
    {
        $requestContext = RequestStack::getCurrent();

        $exceptionContext = $this->normalizer->normalize($error, $requestContext);

        $response = $this->render($exceptionContext);

        return $response;
    }

    /**
     * Render an exception context as an HTTP response
     * 
     * Delegates to the renderer to produce either a debug response
     * (with detailed error information) or a production response
     * (with sanitized error information) based on the debug mode configuration.
     * 
     * @param ExceptionContext $exceptionContext The normalized exception context
     * @return Response The rendered HTTP response
     */
    protected function render(ExceptionContext $exceptionContext): Response
    {
        if ($this->isDebug) {
            return $this->renderer->debugRender($exceptionContext);
        }

        return $this->renderer->render($exceptionContext);
    }
}
