<?php

namespace Arbor\execution;

use Arbor\execution\ExecutionType;
use Arbor\facades\Config;
use Arbor\facades\Container;
use Arbor\facades\Scope;
use Arbor\http\RequestFactory;
use Arbor\http\RequestContext;
use Arbor\http\HttpKernel;
use Arbor\http\Response;
use Arbor\exception\ExceptionKernel;
use Throwable;


class ExecutionKernel
{
    public function run(
        ExecutionType $type,
        callable $callback
    ): mixed {
        $initialOBLevel = ob_get_level();

        Scope::enter();

        try {
            Scope::set(
                ExecutionContext::class,
                new ExecutionContext($type)
            );

            return $callback();

            // normal execution.

        } catch (Throwable $exception) {

            return $this->handleException($exception);
            // handling exceptions.

        } finally {

            // cleaning output buffer and leaving current scope.

            $this->cleanOutputBuffer($initialOBLevel);
            Scope::leave();
        }
    }

    protected function cleanOutputBuffer(int $initialOBLevel): void
    {
        while (ob_get_level() > $initialOBLevel) {
            ob_end_clean();
        }
    }

    protected function handleException(Throwable $exception): mixed
    {
        return Container::get(ExceptionKernel::class)
            ->handle($exception);
    }

    public function http(): Response
    {
        $request = RequestFactory::fromGlobals();

        // handle.
        $handle = static function () use ($request) {

            $requestContext = RequestContext::from($request, Config::get('app.url_prefix'));

            Scope::set(
                RequestContext::class,
                $requestContext
            );

            $httpKernel = Container::get(HttpKernel::class);

            return $httpKernel->handle($requestContext);
        };

        return $this->run(ExecutionType::HTTP, $handle);
    }
}
