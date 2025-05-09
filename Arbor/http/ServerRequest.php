<?php

namespace Arbor\http;

use Arbor\http\components\Uri;
use Arbor\http\components\Stream;
use Arbor\http\components\Headers;
use Arbor\http\components\Cookies;
use Arbor\http\components\Attributes;
use Arbor\http\components\UploadedFile;
use InvalidArgumentException;

/**
 * ServerRequest class represents an HTTP request as received by a server.
 * 
 * This class extends the base Request class and adds functionality specific to
 * server-side request handling, including access to server parameters, cookies,
 * query parameters, uploaded files, and the parsed request body.
 * 
 * @package Arbor\http
 */
class ServerRequest extends Request
{
    /**
     * Server parameters (typically derived from $_SERVER).
     * 
     * @var array<string, mixed>
     */
    protected array $serverParams = [];

    /**
     * Request cookies container.
     * 
     * @var Cookies
     */
    protected Cookies $cookies;

    /**
     * Query parameters from the URL.
     * 
     * @var array<string, mixed>
     */
    protected array $queryParams = [];

    /**
     * Normalized array of uploaded files.
     * 
     * @var array<string, UploadedFile|array<string, UploadedFile>>
     */
    protected array $uploadedFiles = [];

    /**
     * Parsed request body.
     * 
     * @var null|array<string, mixed>|object
     */
    protected null|array|object $parsedBody;


    protected string $baseURI;

    /**
     * ServerRequest constructor.
     *
     * Creates a new server request instance with the provided parameters.
     * 
     * @param string $method HTTP method
     * @param Uri|null $uri Request URI
     * @param Headers|array<string, string|string[]> $headers Request headers
     * @param Stream|string|null $body Request body
     * @param Attributes|null $attributes Request attributes
     * @param string $version HTTP protocol version
     * @param array<string, mixed> $serverParams Server parameters
     * @param Cookies|null $cookies Request cookies
     * @param array<string, mixed> $queryParams Query parameters
     * @param array<string, mixed>|object $parsedBody Parsed request body
     * @param array<string, mixed> $uploadedFiles Uploaded files
     */
    public function __construct(
        string $method = 'GET',
        ?Uri $uri = null,
        Headers|array $headers = [],
        Stream|string|null $body = null,
        ?Attributes $attributes = null,
        string $version = '1.1',
        array $serverParams = [],
        ?Cookies $cookies = null,
        array $queryParams = [],
        array $parsedBody = [],
        array $uploadedFiles = [],
        string $baseURI
    ) {
        // Construct the parent Request class
        parent::__construct($method, $uri, $headers, $body, $attributes, $version);

        // Initialize server request specific properties
        $this->serverParams = $serverParams;
        $this->cookies = $cookies ?? new Cookies();
        $this->queryParams = $queryParams;
        $this->parsedBody = $parsedBody;

        // Normalize the uploaded files array to UploadedFile instances
        $this->uploadedFiles = $this->normalizeFiles($uploadedFiles);
        $this->baseURI = $baseURI;
    }

    /**
     * Retrieves all server parameters.
     *
     * @return array<string, mixed> Array of server parameters
     */
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    /**
     * Retrieves a specific server parameter.
     *
     * @param string $key The server parameter key
     * @param mixed $default Default value if parameter doesn't exist
     * @return mixed The server parameter value or default if not found
     */
    public function getServerParam(string $key, mixed $default = null): mixed
    {
        return $this->serverParams[$key] ?? $default;
    }

    /**
     * Retrieves all cookies.
     *
     * @return array<string, mixed> Array of cookies
     */
    public function getCookieParams(): array
    {
        return $this->cookies->getAll();
    }

    /**
     * Returns a new instance with the specified cookies.
     *
     * @param array<string, mixed> $cookies Array of key/value pairs representing cookies
     * @return static
     */
    public function withCookieParams(array $cookies): static
    {
        $new = clone $this;
        $new->cookies = new Cookies($cookies);
        return $new;
    }

    /**
     * Retrieves the query parameters.
     *
     * @return array<string, mixed> Query parameters
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * Returns a new instance with the specified query parameters.
     *
     * @param array<string, mixed> $query Query parameters
     * @return static
     */
    public function withQueryParams(array $query): static
    {
        $new = clone $this;
        $new->queryParams = $query;
        return $new;
    }

    /**
     * Retrieves normalized file upload data.
     *
     * @return array<string, UploadedFile|array<string, UploadedFile>> Normalized uploaded files
     */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /**
     * Returns a new instance with the specified uploaded files.
     *
     * @param array<string, UploadedFile|array<string, UploadedFile>> $uploadedFiles Normalized uploaded files
     * @return static
     */
    public function withUploadedFiles(array $uploadedFiles): static
    {
        $new = clone $this;
        $new->uploadedFiles = $uploadedFiles;
        return $new;
    }

    /**
     * Retrieves the parsed body data.
     *
     * @return null|array<string, mixed>|object The parsed body data
     */
    public function getParsedBody(): null|array|object
    {
        return $this->parsedBody;
    }

    /**
     * Returns a new instance with the specified parsed body.
     *
     * @param null|array<string, mixed>|object $data The parsed body data
     * @return static
     * @throws InvalidArgumentException If the data is not an array, object, or null
     */
    public function withParsedBody($data): static
    {
        if (!is_array($data) && !is_object($data) && $data !== null) {
            throw new InvalidArgumentException('Parsed body must be an array, object, or null');
        }

        $new = clone $this;
        $new->parsedBody = $data;
        return $new;
    }

    /**
     * Normalize uploaded files array from PHP's $_FILES structure to an array of UploadedFile instances.
     *
     * @param array<string, mixed> $files Files array from $_FILES
     * @return array<string, UploadedFile|array<string, UploadedFile>> Normalized array of UploadedFile instances
     */
    protected static function normalizeFiles(array $files): array
    {
        $normalized = [];

        foreach ($files as $key => $value) {
            if (isset($value['tmp_name'])) {
                if (is_array($value['tmp_name'])) {
                    // Handle nested file arrays (multiple file upload with the same name)
                    $normalized[$key] = self::normalizeNestedFiles($value);
                } else {
                    // Single file upload
                    $normalized[$key] = new UploadedFile(
                        $value['tmp_name'],
                        $value['size'] ?? null,
                        $value['error'] ?? UPLOAD_ERR_OK,
                        $value['name'] ?? null,
                        $value['type'] ?? null
                    );
                }
            }
        }

        return $normalized;
    }

    /**
     * Normalize nested files array for multiple file uploads with the same name.
     *
     * @param array<string, mixed> $files Nested files array
     * @return array<string, UploadedFile> Normalized array of UploadedFile instances
     */
    protected static function normalizeNestedFiles(array $files): array
    {
        $normalized = [];

        foreach (array_keys($files['tmp_name']) as $key) {
            $normalized[$key] = new UploadedFile(
                $files['tmp_name'][$key],
                $files['size'][$key] ?? null,
                $files['error'][$key] ?? UPLOAD_ERR_OK,
                $files['name'][$key] ?? null,
                $files['type'][$key] ?? null
            );
        }

        return $normalized;
    }


    public function getBaseURI(): string
    {
        return $this->baseURI;
    }
}
