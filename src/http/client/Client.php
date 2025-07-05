<?php

namespace Arbor\http\client;

use Exception;
use InvalidArgumentException;
use Arbor\http\Response;

/**
 * Arbor HTTP Client
 * A modern, fluent HTTP client for the Arbor framework
 */
class Client
{
    private string $baseUrl = '';
    private array $defaultHeaders = [];
    private int $timeout = 30;
    private bool $followRedirects = true;
    private int $maxRedirects = 5;
    private array $cookies = [];
    private ?string $userAgent = null;

    public function __construct(array $config = [])
    {
        $this->baseUrl = $config['base_url'] ?? '';
        $this->defaultHeaders = $config['headers'] ?? [];
        $this->timeout = $config['timeout'] ?? 30;
        $this->followRedirects = $config['follow_redirects'] ?? true;
        $this->maxRedirects = $config['max_redirects'] ?? 5;
        $this->userAgent = !empty($config['user_agent']) ? $config['user_agent'] : 'Arbor-HTTP-Client/1.0';

        // Set default headers
        $this->defaultHeaders['User-Agent'] = $this->userAgent;
        $this->defaultHeaders['Accept'] = 'application/json, text/plain, */*';
    }

    /**
     * Set base URL for all requests
     */
    public function baseUrl(string $url): self
    {
        $this->baseUrl = rtrim($url, '/');
        return $this;
    }

    /**
     * Set default headers
     */
    public function withHeaders(array $headers): self
    {
        $this->defaultHeaders = array_merge($this->defaultHeaders, $headers);
        return $this;
    }

    /**
     * Set authorization header
     */
    public function withToken(string $token, string $type = 'Bearer'): self
    {
        $this->defaultHeaders['Authorization'] = "{$type} {$token}";
        return $this;
    }

    /**
     * Set basic authentication
     */
    public function withBasicAuth(string $username, string $password): self
    {
        $credentials = base64_encode("{$username}:{$password}");
        $this->defaultHeaders['Authorization'] = "Basic {$credentials}";
        return $this;
    }

    /**
     * Set timeout
     */
    public function timeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    /**
     * Set cookies
     */
    public function withCookies(array $cookies): self
    {
        $this->cookies = array_merge($this->cookies, $cookies);
        return $this;
    }

    /**
     * GET request
     */
    public function get(string $url, array $params = [], array $headers = []): Response
    {
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        return $this->request('GET', $url, null, $headers);
    }

    /**
     * POST request
     */
    public function post(string $url, $data = null, array $headers = []): Response
    {
        return $this->request('POST', $url, $data, $headers);
    }

    /**
     * PUT request
     */
    public function put(string $url, $data = null, array $headers = []): Response
    {
        return $this->request('PUT', $url, $data, $headers);
    }

    /**
     * PATCH request
     */
    public function patch(string $url, $data = null, array $headers = []): Response
    {
        return $this->request('PATCH', $url, $data, $headers);
    }

    /**
     * DELETE request
     */
    public function delete(string $url, array $headers = []): Response
    {
        return $this->request('DELETE', $url, null, $headers);
    }

    /**
     * HEAD request
     */
    public function head(string $url, array $headers = []): Response
    {
        return $this->request('HEAD', $url, null, $headers);
    }

    /**
     * OPTIONS request
     */
    public function options(string $url, array $headers = []): Response
    {
        return $this->request('OPTIONS', $url, null, $headers);
    }

    /**
     * Send JSON data
     */
    public function json(string $method, string $url, array $data, array $headers = []): Response
    {
        $headers['Content-Type'] = 'application/json';
        return $this->request($method, $url, json_encode($data), $headers);
    }

    /**
     * Send form data
     */
    public function form(string $method, string $url, array $data, array $headers = []): Response
    {
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        return $this->request($method, $url, http_build_query($data), $headers);
    }

    /**
     * Upload files (multipart/form-data)
     */
    public function upload(string $url, array $data, array $files = [], array $headers = []): Response
    {
        $boundary = uniqid();
        $headers['Content-Type'] = "multipart/form-data; boundary={$boundary}";

        $body = $this->buildMultipartBody($data, $files, $boundary);
        return $this->request('POST', $url, $body, $headers);
    }

    /**
     * Main request method
     */
    private function request(string $method, string $url, $data = null, array $headers = []): Response
    {
        $fullUrl = $this->buildUrl($url);
        $mergedHeaders = array_merge($this->defaultHeaders, $headers);

        $ch = curl_init();

        // Basic cURL options
        curl_setopt_array(
            $ch,
            [
                CURLOPT_URL => $fullUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_FOLLOWLOCATION => $this->followRedirects,
                CURLOPT_MAXREDIRS => $this->maxRedirects,
                CURLOPT_HEADER => true,
                CURLOPT_NOBODY => false,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_CUSTOMREQUEST => strtoupper($method),
            ]
        );

        // Set headers
        if (!empty($mergedHeaders)) {
            $headerStrings = [];
            foreach ($mergedHeaders as $key => $value) {
                $headerStrings[] = "{$key}: {$value}";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerStrings);
        }

        // Set cookies
        if (!empty($this->cookies)) {
            $cookieString = '';
            foreach ($this->cookies as $name => $value) {
                $cookieString .= "{$name}={$value}; ";
            }
            curl_setopt($ch, CURLOPT_COOKIE, rtrim($cookieString, '; '));
        }

        // Set request body for methods that support it
        if ($data !== null && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        $startTime = microtime(true);
        $response = curl_exec($ch);
        $duration = microtime(true) - $startTime;

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL Error: {$error}");
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $info = curl_getinfo($ch);
        curl_close($ch);

        $responseHeaders = substr($response, 0, $headerSize);
        $responseBody = substr($response, $headerSize);

        return new Response(
            $responseBody,
            $httpCode,
            $this->parseHeaders($responseHeaders),
            $info,
            $duration
        );
    }

    /**
     * Build full URL
     */
    private function buildUrl(string $url): string
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        if (empty($this->baseUrl)) {
            throw new InvalidArgumentException('Base URL is required for relative URLs');
        }

        return rtrim($this->baseUrl, '/') . '/' . ltrim($url, '/');
    }

    /**
     * Parse response headers
     */
    private function parseHeaders(string $headerString): array
    {
        $headers = [];
        $lines = explode("\r\n", $headerString);

        foreach ($lines as $line) {
            if (stripos($line, 'HTTP/') === 0) {
                $headers['Status-Line'] = $line;
                continue;
            }
            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);
                $headers[trim($key)] = trim($value);
            }
        }
        return $headers;
    }


    /**
     * Build multipart body for file uploads
     */
    private function buildMultipartBody(array $data, array $files, string $boundary): string
    {
        $body = '';

        // Add regular form fields
        foreach ($data as $key => $value) {
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Disposition: form-data; name=\"{$key}\"\r\n\r\n";
            $body .= "{$value}\r\n";
        }

        // Add file fields
        foreach ($files as $key => $file) {
            if (is_string($file) && file_exists($file)) {
                $filename = basename($file);
                $mimeType = mime_content_type($file);
                $content = file_get_contents($file);
            } elseif (is_array($file)) {
                $filename = $file['name'] ?? 'file';
                $mimeType = $file['type'] ?? 'application/octet-stream';
                $content = $file['content'] ?? '';
            } else {
                continue;
            }

            $body .= "--{$boundary}\r\n";
            $body .= "Content-Disposition: form-data; name=\"{$key}\"; filename=\"{$filename}\"\r\n";
            $body .= "Content-Type: {$mimeType}\r\n\r\n";
            $body .= "{$content}\r\n";
        }

        $body .= "--{$boundary}--\r\n";

        return $body;
    }
}
