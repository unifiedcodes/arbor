<?php

namespace Arbor\http;

use Arbor\http\components\Stream;
use Arbor\http\components\Headers;
use RuntimeException;
use InvalidArgumentException;

/**
 * HTTP Response Factory class
 * 
 * This class provides static methods for creating various types of Response objects.
 * It simplifies the creation of common HTTP responses like JSON, HTML, text, and redirects.
 * 
 * @package Arbor\http
 */
class ResponseFactory
{
    /**
     * Ensures headers are an array, even if Headers object is passed
     *
     * @param array<string,string|string[]>|Headers $headers The headers to convert
     * @return array<string,string|string[]> The headers as an array
     */
    protected static function ensureHeaderIsArray(array|Headers $headers): array
    {
        return ($headers instanceof Headers) ? $headers->toArray() : $headers;
    }

    /**
     * Creates a generic HTTP response with customizable parameters
     *
     * @param Stream|string|null $body The response body
     * @param int $statusCode The HTTP status code
     * @param array<string,string|string[]>|Headers $headers The response headers
     * @param string $protocolVersion The HTTP protocol version
     * @param string $reasonPhrase The reason phrase to use with the status code
     * @return Response The created Response object
     */
    public static function create(
        Stream|string|null $body = null,
        int $statusCode = 200,
        array|Headers $headers = [],
        string $protocolVersion = '1.1',
        string $reasonPhrase = ''
    ): Response {
        return new Response(
            body: $body,
            statusCode: $statusCode,
            headers: self::ensureHeaderIsArray($headers),
            protocolVersion: $protocolVersion,
            reasonPhrase: $reasonPhrase
        );
    }

    /**
     * Creates a JSON response from an array or object
     *
     * @param array|object $data The data to encode as JSON
     * @param int $statusCode The HTTP status code
     * @param array<string,string|string[]>|Headers $headers Additional response headers
     * @return Response The created JSON Response object
     * @throws RuntimeException If JSON encoding fails
     */
    public static function json(
        array|object $data,
        int $statusCode = 200,
        array|Headers $headers = []
    ): Response {
        $jsonContent = json_encode($data);
        if ($jsonContent === false) {
            throw new RuntimeException('Failed to encode data as JSON');
        }

        $headers = self::ensureHeaderIsArray($headers);
        $headers['Content-Type'] = 'application/json';

        return new Response(
            body: $jsonContent,
            statusCode: $statusCode,
            headers: $headers
        );
    }

    /**
     * Creates an HTML response
     *
     * @param string $html The HTML content
     * @param int $statusCode The HTTP status code
     * @param array<string,string|string[]>|Headers $headers Additional response headers
     * @return Response The created HTML Response object
     */
    public static function html(
        string $html,
        int $statusCode = 200,
        array|Headers $headers = []
    ): Response {
        $headers = self::ensureHeaderIsArray($headers);
        $headers['Content-Type'] = 'text/html; charset=utf-8';

        return new Response(
            body: $html,
            statusCode: $statusCode,
            headers: $headers
        );
    }

    /**
     * Creates a plain text response
     *
     * @param string $text The text content
     * @param int $statusCode The HTTP status code
     * @param array<string,string|string[]>|Headers $headers Additional response headers
     * @return Response The created text Response object
     */
    public static function text(
        string $text,
        int $statusCode = 200,
        array|Headers $headers = []
    ): Response {
        $headers = self::ensureHeaderIsArray($headers);
        $headers['Content-Type'] = 'text/plain; charset=utf-8';

        return new Response(
            body: $text,
            statusCode: $statusCode,
            headers: $headers
        );
    }

    /**
     * Creates a redirect response
     *
     * @param string $location The URL to redirect to
     * @param int $statusCode The HTTP redirect status code (301, 302, 303, 307, 308)
     * @param array<string,string|string[]>|Headers $headers Additional response headers
     * @return Response The created redirect Response object
     * @throws InvalidArgumentException If an invalid redirect status code is provided
     */
    public static function redirect(
        string $location,
        int $statusCode = 307,
        array|Headers $headers = [],
        array $parameters = []
    ): Response {
        if (!in_array($statusCode, [301, 302, 303, 307, 308])) {
            throw new InvalidArgumentException('Invalid redirect status code');
        }


        // Append parameters to the URL
        if (!empty($parameters)) {
            $queryString = http_build_query($parameters);
            $separator = str_contains($location, '?') ? '&' : '?';
            $location .= $separator . $queryString;
        }


        $headers = self::ensureHeaderIsArray($headers);
        $headers['Location'] = $location;

        return new Response(
            body: null,
            statusCode: $statusCode,
            headers: $headers
        );
    }

    /**
     * Creates a 204 No Content response
     *
     * @param array<string,string|string[]>|Headers $headers Response headers
     * @return Response The created No Content Response object
     */
    public static function noContent(
        array|Headers $headers = []
    ): Response {
        return new Response(
            body: null,
            statusCode: 204,
            headers: self::ensureHeaderIsArray($headers)
        );
    }

    /**
     * Creates a JSON error response
     *
     * @param int $statusCode The HTTP error status code
     * @param string $reasonPhrase A custom reason phrase for the status code
     * @param array<string,string|string[]>|Headers $headers Additional response headers
     * @return Response The created error Response object with JSON content type
     */
    public static function errorJson(
        int $statusCode = 400,
        string $reasonPhrase = '',
        array|Headers $headers = [],
    ): Response {
        $headers = self::ensureHeaderIsArray($headers);
        $headers['Content-Type'] = 'application/json';

        return new Response(
            statusCode: $statusCode,
            headers: $headers,
            reasonPhrase: $reasonPhrase
        );
    }

    /**
     * Creates an HTML error response using a template
     *
     * @param int $statusCode The HTTP error status code
     * @param string $templateContent The HTML template content for the error page
     * @param array<string,string|string[]>|Headers $headers Additional response headers
     * @return Response The created error Response object with HTML content
     */
    public static function errorTemplate(
        int $statusCode = 404,
        string $templateContent = '',
        array|Headers $headers = []
    ): Response {
        $headers = self::ensureHeaderIsArray($headers);
        $headers['Content-Type'] = 'text/html; charset=utf-8';

        return new Response(
            body: $templateContent,
            statusCode: $statusCode,
            headers: $headers
        );
    }

    /**
     * Creates a generic error response with customizable content
     *
     * @param int $statusCode The HTTP error status code
     * @param string|null $body The error message body (null will use default reason phrase)
     * @param array<string,string|string[]>|Headers $headers Additional response headers
     * @return Response The created error Response object
     */
    public function error(
        int $statusCode = 500,
        ?string $body = null,
        array|Headers $headers = []
    ): Response {
        $headers = self::ensureHeaderIsArray($headers);
        $headers['Content-Type'] ??= 'text/plain; charset=utf-8';

        // If body is null, Response will automatically fallback to its phrase map
        return new Response(
            body: $body,
            statusCode: $statusCode,
            headers: $headers
        );
    }
}
