<?php

namespace Arbor\exception;

use Arbor\http\context\RequestContext;
use Arbor\exception\View;
use Arbor\facades\RequestStack;
use Arbor\http\Response;
use Throwable;


class Renderer
{
    public function render(Throwable $error): Response
    {
        $request = $this->normalizeRequest(RequestStack::getCurrent());
        $exceptionTrail = $this->normalizeThrowable($error);


        $html = View::HTML($request, $exceptionTrail);


        return new Response($html, 500, [
            'Content-Type' => 'text/html; charset=utf-8'
        ]);
    }


    protected function normalizeThrowable(Throwable $error): array
    {
        $exceptions = [];

        $current = $error;
        while ($current) {
            $exceptions[] = [
                'class'   => get_class($current),
                'message' => $current->getMessage(),
                'code'    => $current->getCode(),
                'file'    => $current->getFile(),
                'line'    => $current->getLine(),
                'trace'   => $this->normalizeTrace($current->getTrace()),
            ];

            $current = $current->getPrevious();
        }

        return $exceptions;
    }


    protected function normalizeTrace(array $trace): array
    {
        $frames = [];

        foreach ($trace as $index => $frame) {
            $frames[] = [
                'index'    => $index,
                'file'     => $frame['file']     ?? '[internal]',
                'line'     => $frame['line']     ?? '-',
                'class'    => $frame['class']    ?? '',
                'type'     => $frame['type']     ?? '',
                'function' => $frame['function'] ?? '',
                'args'     => $this->normalizeArgs($frame['args'] ?? []),
            ];
        }

        return $frames;
    }

    protected function normalizeArgs(array $args): array
    {
        $normalized = [];

        foreach ($args as $arg) {
            if (is_object($arg)) {
                $normalized[] = 'object(' . get_class($arg) . ')';
            } elseif (is_array($arg)) {
                $normalized[] = 'array(' . count($arg) . ')';
            } elseif (is_resource($arg)) {
                $normalized[] = 'resource';
            } else {
                $normalized[] = $arg;
            }
        }

        return $normalized;
    }

    protected function normalizeRequest(?RequestContext $context = null): array
    {
        if (!$context) {
            return [
                'method' => 'N/A',
                'uri'    => 'N/A',
                'route'  => 'N/A',
            ];
        }

        return [
            'method' => $context->getMethod(),
            'uri' => (string) $context->getUri(),
            'route' => $context->getRoute()?->routeName(),
        ];
    }
}
