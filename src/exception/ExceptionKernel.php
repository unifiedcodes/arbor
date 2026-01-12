<?php

namespace Arbor\exception;


use Arbor\facades\Respond;
use Arbor\attributes\ConfigValue;
use Arbor\http\context\RequestContext;
use Arbor\exception\Renderer;
use Arbor\http\Response;

use Throwable;


class ExceptionKernel
{
    protected Renderer $renderer;


    public function __construct(
        #[ConfigValue('root.is_debug')]
        protected bool $isDebug
    ) {
        $this->renderer = new Renderer();
    }


    public function handle(Throwable $error, RequestContext $requestContext): Response
    {
        if ($this->isDebug) {
            return $this->renderer->render(
                $error,
                $requestContext
            );
        }

        return Respond::error(500, 'something went wrong !');
    }
}
