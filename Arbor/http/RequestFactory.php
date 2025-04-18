<?php

namespace Arbor\http;

use Arbor\http\ServerRequest;
use Arbor\http\components\Uri;
use Arbor\http\components\Headers;
use Arbor\http\components\Stream;
use Arbor\http\components\Cookies;
use Arbor\http\components\Attributes;

/**
 * Class RequestFactory
 * 
 * Factory class responsible for creating HTTP Request and ServerRequest instances
 * from various sources (globals, arrays, or manual configuration).
 * 
 * @package Arbor\http
 */
class RequestFactory
{
    /**
     * Creates a ServerRequest instance from PHP global variables
     * 
     * This method uses $_SERVER, $_COOKIE, $_GET, $_POST, and $_FILES 
     * to construct a request object representing the current HTTP request.
     * 
     * @return ServerRequest The server request object
     */
    public static function fromGlobals(): ServerRequest
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = RequestFactory::URIfromGlobals($_SERVER);
        $headers = RequestFactory::HeadersFromGlobals($_SERVER);
        $body = new Stream(fopen('php://input', 'r+'));
        $cookies = new Cookies($_COOKIE);
        $version = isset($_SERVER['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL']) : '1.1';
        $attributes = new Attributes();

        return new ServerRequest(
            // Request construct params
            $method,
            $uri,
            $headers,
            $body,
            $attributes,
            $version,

            // ServerRequest construct params
            $_SERVER,
            $cookies,
            $_GET,
            $_POST,
            $_FILES,
        );
    }

    /**
     * Creates a new Request instance with the provided parameters
     * 
     * @param string $uri The request URI
     * @param string $method The HTTP method
     * @param array<string, string|array<string>> $headers HTTP headers
     * @param string $body Request body content
     * @param array<string, mixed> $attributes Request attributes
     * @param string|null $version HTTP protocol version
     * @param string|null $baseURI Base URI to use (overrides the factory's base URI)
     * 
     * @return Request The created request object
     */
    public static function make(
        string $uri,
        string $method = 'GET',
        array $headers = [],
        string $body = '',
        array $attributes = [],
        ?string $version = null,
    ): Request {
        return new Request(
            $method,
            $uri,
            $headers,
            $body,
            $attributes,
            $version
        );
    }

    /**
     * Creates a Request instance from an associative array of parameters
     * 
     * @param array<string, mixed> $data Array containing request parameters
     * @return Request The created request object
     */
    public static function fromArray(array $data): Request
    {
        return RequestFactory::make(
            uri: $data['uri'] ?? '',
            method: $data['method'] ?? 'GET',
            headers: $data['headers'] ?? [],
            body: $data['body'] ?? '',
            attributes: $data['attributes'] ?? [],
            version: $data['version'] ?? null,
        );
    }

    /**
     * Creates a Uri object from $_SERVER data
     * 
     * @param array<string, mixed> $server The $_SERVER array
     * @return Uri The created Uri object
     */
    protected static function URIfromGlobals(array $server): Uri
    {
        $scheme = (!empty($server['HTTPS']) && $server['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $server['HTTP_HOST'] ?? $server['SERVER_NAME'] ?? 'localhost';
        $port = isset($server['SERVER_PORT']) ? ':' . $server['SERVER_PORT'] : '';
        $requestUri = $server['REQUEST_URI'] ?? '/';
        $query = isset($server['QUERY_STRING']) ? '?' . $server['QUERY_STRING'] : '';

        // Combine full URI string
        $uriString = "{$scheme}://{$host}{$port}{$requestUri}";

        // Fallback if REQUEST_URI doesn't contain query string
        if ($query && !str_contains($uriString, '?')) {
            $uriString .= $query;
        }

        return new Uri($uriString);
    }

    /**
     * Creates a Headers object from $_SERVER data
     * 
     * Extracts HTTP header information from the $_SERVER array
     * by converting HTTP_* keys and special content headers.
     * 
     * @param array<string, mixed> $server The $_SERVER array
     * @return Headers The created Headers object
     */
    protected static function HeadersFromGlobals(array $server): Headers
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[$name] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $name = str_replace('_', '-', $key);
                $headers[$name] = $value;
            }
        }

        return new Headers($headers);
    }
}
