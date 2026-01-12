<?php

namespace Arbor\exception;


use Arbor\http\context\RequestContext;
use Throwable;

/**
 * Normalizes exceptions and request context into a structured format.
 * 
 * This class takes PHP exceptions and optional request context and converts them
 * into a standardized ExceptionContext object suitable for logging, error reporting,
 * or debugging purposes. It handles exception chains, stack traces, and request details.
 */
class Normalizer
{
    /**
     * Normalizes a throwable and optional request context into an ExceptionContext.
     * 
     * This is the main entry point for exception normalization. It combines the
     * exception details, request information, and a timestamp into a single
     * structured context object.
     * 
     * @param Throwable $exception The exception to normalize
     * @param RequestContext|null $request Optional request context for additional information
     * @return ExceptionContext The normalized exception context
     */
    public function normalize(

        Throwable $exception,

        ?RequestContext $request = null

    ): ExceptionContext {

        return new ExceptionContext(
            exceptions: $this->normalizeThrowable($exception),
            request: $this->normalizeRequest($request),
            timestamp: time(),
        );
    }

    /**
     * Normalizes a throwable and its entire exception chain.
     * 
     * Walks through the exception chain using getPrevious() and extracts key
     * information from each exception including class name, message, code,
     * file location, line number, and stack trace.
     * 
     * @param Throwable $error The exception to normalize
     * @return array Array of normalized exception data, with the original exception first
     */
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

    /**
     * Normalizes a stack trace into a structured array format.
     * 
     * Processes each frame in the stack trace and extracts file, line, class,
     * type (e.g., '::' or '->'), function name, and normalized arguments.
     * Handles missing values with appropriate defaults.
     * 
     * @param array $trace The raw stack trace from an exception
     * @return array Array of normalized stack trace frames with consistent structure
     */
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

    /**
     * Normalizes function arguments for safe serialization.
     * 
     * Converts complex types (objects, arrays, resources) into simple string
     * representations to prevent issues with serialization and to avoid exposing
     * sensitive data. Scalar values are preserved as-is.
     * 
     * @param array $args The function arguments to normalize
     * @return array Array of normalized argument representations
     */
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

    /**
     * Normalizes request context into a structured array.
     * 
     * Extracts HTTP method, URI, and route name from the request context.
     * If no context is provided, returns default 'N/A' values for all fields.
     * 
     * @param RequestContext|null $context Optional request context to normalize
     * @return array Normalized request data with method, uri, and route keys
     */
    protected function normalizeRequest(?RequestContext $context = null): array
    {
        if (!$context) {
            return [];
        }

        return [
            'method' => $context->getMethod(),
            'uri' => (string) $context->getUri(),
            'route' => $context->getRoute()?->routeName(),
        ];
    }
}
