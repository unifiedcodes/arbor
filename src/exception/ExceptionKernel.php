<?php

namespace Arbor\exception;


use Arbor\attributes\ConfigValue;
use Arbor\facades\Respond;
use Arbor\http\Response;
use Arbor\exception\Renderer;
use Arbor\exception\Normalizer;
use Arbor\facades\RequestStack;


use Throwable;


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


    public function handle(Throwable $error): Response
    {
        // update RequestStack -> current request handling error.

        $requestContext = RequestStack::getCurrent();

        $exceptionContext = $this->normalizer->normalize($error, $requestContext);

        return $this->render($exceptionContext);
    }


    protected function render(ExceptionContext $exceptionContext): Response
    {
        if ($this->isDebug) {
            return $this->renderer->httpTrailRender($exceptionContext);
        }

        return $this->renderer->httpRender($exceptionContext);
    }
}
