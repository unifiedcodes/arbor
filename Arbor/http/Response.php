<?php

namespace Arbor\http;

use Arbor\http\components\Stream;
use Arbor\http\components\Headers;
use Arbor\http\traits\HeaderTrait;
use Arbor\http\traits\BodyTrait;

/**
 * HTTP Response class
 * 
 * This class represents an HTTP response with status code, headers, and body.
 * It follows PSR-7 response interface conventions and provides utility methods
 * for creating common response types.
 * 
 * @package Arbor\http
 * 
 */
class Response
{
    use HeaderTrait;
    use BodyTrait;

    /**
     * HTTP protocol version
     * 
     * @var string
     */
    private string $protocolVersion;
    
    /**
     * HTTP status code
     * 
     * @var int
     */
    private int $statusCode;
    
    /**
     * HTTP reason phrase
     * 
     * @var string
     */
    private string $reasonPhrase;

    /**
     * Map of standard HTTP status code/reason phrases
     *
     * @var array<int, string>
     */
    private static array $phrases = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        511 => 'Network Authentication Required',
    ];

    /**
     * Response constructor.
     *
     * Creates a new HTTP response with the specified body, status code, headers, protocol, and reason phrase.
     *
     * @param Stream|string|null $body Response body content
     * @param int $statusCode HTTP status code
     * @param array<string,string|string[]>|Headers $headers Response headers
     * @param string $protocolVersion HTTP protocol version
     * @param string $reasonPhrase Reason phrase (when empty, will use the standard phrase)
     */
    public function __construct(
        Stream|string|null $body = null,
        int $statusCode = 200,
        array|Headers $headers = [],
        string $protocolVersion = '1.1',
        string $reasonPhrase = ''
    ) {
        $this->statusCode = $statusCode;

        // Ensures body is a Stream instance (implementation in BodyTrait)
        $this->body = $this->ensureStreamBody($body);

        $this->protocolVersion = $protocolVersion;

        // Set headers using Headers class
        if ($headers instanceof Headers) {
            $this->headers = $headers;
        } else {
            $this->headers = new Headers($headers);
        }

        // Set reason phrase, use standard if not provided
        if ($reasonPhrase === '' && isset(self::$phrases[$this->statusCode])) {
            $this->reasonPhrase = self::$phrases[$this->statusCode];
        } else {
            $this->reasonPhrase = $reasonPhrase;
        }
    }

    /**
     * Gets the HTTP protocol version
     *
     * @return string HTTP protocol version
     */
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * Returns a new instance with the specified HTTP protocol version
     *
     * @param string $version HTTP protocol version
     * @return self
     */
    public function withProtocolVersion(string $version): self
    {
        $new = clone $this;
        $new->protocolVersion = $version;
        return $new;
    }

    /**
     * Gets the response status code
     *
     * @return int Status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Returns a new instance with the specified status code and optional reason phrase
     * 
     * If no reason phrase is provided, the standard reason phrase for the status code is used.
     *
     * @param int $code HTTP status code
     * @param string $reasonPhrase Reason phrase (optional)
     * @return self
     */
    public function withStatus(int $code, string $reasonPhrase = ''): self
    {
        $new = clone $this;
        $new->statusCode = $code;

        if ($reasonPhrase === '' && isset(self::$phrases[$new->statusCode])) {
            $reasonPhrase = self::$phrases[$new->statusCode];
        }

        $new->reasonPhrase = $reasonPhrase;
        return $new;
    }

    /**
     * Gets the response reason phrase
     *
     * @return string Reason phrase
     */
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    /**
     * Returns the string representation of the response
     * 
     * Formats the status line, headers, and body into a complete HTTP response string.
     *
     * @return string The formatted HTTP response
     */
    public function __toString(): string
    {
        $statusLine = sprintf('HTTP/%s %d %s', $this->protocolVersion, $this->statusCode, $this->reasonPhrase);

        $headers = '';
        foreach ($this->getHeaders() as $name => $values) {
            foreach ((array) $values as $value) {
                $headers .= $name . ': ' . $value . "\r\n";
            }
        }

        return $statusLine . "\r\n" . $headers . "\r\n" . (string) $this->body;
    }

    /**
     * Creates a JSON response with the provided data
     *
     * @param array|object $data Data to encode as JSON
     * @return self
     */
    public function asJson(array|object $data): self
    {
        return $this
            ->withHeader('Content-Type', 'application/json')
            ->withBody(json_encode($data));
    }

    /**
     * Creates an HTML response with the provided content
     *
     * @param string $html HTML content
     * @return self
     */
    public function asHtml(string $html): self
    {
        return $this
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withBody($html);
    }

    /**
     * Creates a plain text response with the provided content
     *
     * @param string $text Plain text content
     * @return self
     */
    public function asText(string $text): self
    {
        return $this
            ->withHeader('Content-Type', 'text/plain; charset=utf-8')
            ->withBody($text);
    }

    /**
     * Creates a redirect response to the specified location
     *
     * @param string $location Target URL for the redirect
     * @param int $statusCode HTTP status code to use (default: 302 Found)
     * @return self
     */
    public function asRedirect(string $location, int $statusCode = 302): self
    {
        $new = clone $this;

        // Set Location header
        $new->headers = $new->headers->set('Location', $location);

        // Set status code and reason phrase
        $new->statusCode = $statusCode;

        if (isset(self::$phrases[$statusCode])) {
            $new->reasonPhrase = self::$phrases[$statusCode];
        } else {
            $new->reasonPhrase = 'Redirect';
        }

        return $new;
    }

    /**
     * Sends the response to the client
     * 
     * Outputs the status line, headers, and body content to the client.
     * Throws an exception if headers have already been sent.
     *
     * @throws \RuntimeException If headers have already been sent
     * @return void
     */
    public function send(): void
    {
        if (headers_sent()) {
            throw new \RuntimeException("Headers already sent.");
        }

        // Status line
        header(sprintf('HTTP/%s %d %s', $this->protocolVersion, $this->statusCode, $this->reasonPhrase), true, $this->statusCode);

        // Headers
        foreach ($this->getHeaders() as $name => $values) {
            foreach ((array) $values as $value) {
                header("$name: $value", false);
            }
        }

        echo (string) $this->body;
    }
}