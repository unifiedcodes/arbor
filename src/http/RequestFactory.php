<?php

namespace Arbor\http;

use Arbor\http\ServerRequest;
use Arbor\http\components\Uri;
use Arbor\http\components\Headers;
use Arbor\http\components\Cookies;
use Arbor\http\components\Attributes;

use Arbor\stream\StreamFactory;
use Arbor\stream\StreamInterface;

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
        return new ServerRequest(
            // base request.
            method: $_SERVER['REQUEST_METHOD'] ?? 'GET',
            uri: self::URIfromGlobals($_SERVER),
            headers: self::HeadersFromGlobals($_SERVER),
            body: StreamFactory::fromPhpInput(),
            attributes: new Attributes(),
            version: self::protocolVersion($_SERVER),

            // server specific data.
            serverParams: $_SERVER,
            cookies: new Cookies($_COOKIE),
            queryParams: $_GET,
            parsedBody: $_POST,
            uploadedFiles: $_FILES
        );
    }

    /**
     * Creates a new Request instance with the provided parameters
     * 
     * @param string $uri The request URI
     * @param string $method The HTTP method
     * @param array<string, string|array<string>> $headers HTTP headers
     * @param ?StreamInterface $body Request body content
     * @param array<string, mixed> $attributes Request attributes
     * @param string|null $version HTTP protocol version
     * 
     * @return Request The created request object
     */
    public static function make(
        string $uri,
        string $method = 'GET',
        Headers|array $headers = [],
        ?StreamInterface $body = null,
        Attributes|array|null $attributes = null,
        ?string $version = null,
    ): Request {
        return new Request(
            method: $method,
            uri: new Uri($uri),
            headers: $headers instanceof Headers ? $headers : new Headers($headers),
            body: $body,
            attributes: $attributes instanceof Attributes
                ? $attributes
                : new Attributes($attributes ?? []),
            version: $version ?? '1.1'
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
        return self::make(
            uri: $data['uri'] instanceof Uri ? $data['uri'] : new Uri($data['uri'] ?? '/'),
            method: $data['method'] ?? 'GET',
            headers: $data['headers'] ?? [],
            body: $data['body'] ?? null,
            attributes: $data['attributes'] ?? null,
            version: $data['version'] ?? null,
        );
    }


    protected static function protocolVersion(array $server): string
    {
        return isset($server['SERVER_PROTOCOL'])
            ? str_replace('HTTP/', '', $server['SERVER_PROTOCOL'])
            : '1.1';
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

        $port = $server['SERVER_PORT'] ?? null;
        $portPart = ($port && !in_array((int)$port, [80, 443], true)) ? ':' . $port : '';

        $uri = $server['REQUEST_URI'] ?? '/';

        return new Uri("{$scheme}://{$host}{$portPart}{$uri}");
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
