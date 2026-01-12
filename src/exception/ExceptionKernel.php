<?php

namespace Arbor\exception;


use Arbor\attributes\ConfigValue;
use Arbor\http\Response;
use Arbor\exception\Renderer;
use Arbor\exception\Normalizer;
use Arbor\facades\RequestStack;
use Arbor\http\context\RequestContext;
use Throwable;
use ErrorException;


class ExceptionKernel
{
    protected Renderer $renderer;
    protected Normalizer $normalizer;


    public function __construct(
        #[ConfigValue('root.is_debug')]
        protected bool $isDebug
    ) {
        $this->renderer = new Renderer();
        $this->normalizer = new Normalizer();
    }


    public function bind(): void
    {
        set_exception_handler([$this, 'handleException']);
        set_error_handler([$this, 'handleError']);
        register_shutdown_function([$this, 'handleShutdown']);
    }


    public function handleException($e)
    {
        $this->handle($e)->send();
    }


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

    public function handle(Throwable $error): Response
    {
        $requestContext = RequestStack::getCurrent();

        $exceptionContext = $this->normalizer->normalize($error, $requestContext);

        $response = $this->render($exceptionContext);

        return $response;
    }


    protected function render(ExceptionContext $exceptionContext): Response
    {
        if ($this->isDebug) {
            return $this->renderer->debugRender($exceptionContext);
        }

        return $this->renderer->render($exceptionContext);
    }
}
