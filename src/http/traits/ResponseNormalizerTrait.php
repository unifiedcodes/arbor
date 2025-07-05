<?php

namespace Arbor\http\traits;

use Arbor\http\Response;
use Throwable;

/**
 * Trait for normalizing responses into a standardized Response object format.
 * 
 * This trait provides methods to ensure that all responses follow a consistent
 * structure regardless of the original return type from controller methods.
 * 
 * @package Arbor\http\traits
 */
trait ResponseNormalizerTrait
{
    /**
     * Ensures that the given response is a valid Response object.
     *
     * If the response is not already a Response instance, it converts arrays to JSON,
     * strings to plain text, or returns an empty text response for other types.
     *
     * @param mixed $response The response to validate or convert.
     *
     * @return Response The validated or converted Response object.
     */
    protected function ensureValidResponse(mixed $response): Response
    {
        if ($response instanceof Response) {
            return $response;
        } elseif (is_array($response)) {
            return new Response(
                json_encode($response) ?: '',
                200,
                ['Content-Type' => 'application/json'],
            );
        } elseif (is_string($response)) {
            return new Response(
                $response,
                200,
                ['Content-Type' => 'text/plain'],
            );
        } else {
            return new Response(
                '',
                200,
                ['Content-Type' => 'text/plain'],
            );
        }
    }

    /**
     * Creates an HTTP error response based on the provided throwable.
     *
     * This method extracts an HTTP status code and message from the given error.
     * If the error does not provide a specific code or message, it defaults to a 500 status code 
     * and a generic message. The resulting Response object is configured with a 
     * 'text/plain' Content-Type header.
     *
     * @param Throwable $error The error that triggered this response.
     *
     * @return Response A Response object encapsulating the error details.
     */
    protected function createErrorResponse(Throwable $error): Response
    {
        // Determine a valid HTTP status code
        $code = $this->getValidHttpStatusCode($error->getCode());
        $message = $error->getMessage() ?: 'Internal Server Error';

        // Return a text/plain response with the error message and appropriate status code
        return new Response(
            $message,
            $code,
            ['Content-Type' => 'text/plain'],
        );
    }

    /**
     * Validates and returns a proper HTTP status code.
     * 
     * Ensures the provided code is within the valid HTTP status code range (100-599).
     * If not, defaults to 500 (Internal Server Error).
     * 
     * @param int|string $code The status code to validate
     * 
     * @return int A valid HTTP status code
     */
    private function getValidHttpStatusCode(int|string $code): int
    {
        $intCode = is_numeric($code) ? (int)$code : 0;

        // Valid HTTP status codes are between 100 and 599
        if ($intCode >= 100 && $intCode <= 599) {
            return $intCode;
        }

        return 500; // Default to Internal Server Error
    }
}
