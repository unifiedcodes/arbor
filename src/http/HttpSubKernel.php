<?php

namespace Arbor\http;

use Arbor\http\HttpKernel;
use Arbor\http\Request;
use Arbor\http\RequestFactory;
use Arbor\http\Response;

/**
 * Convenience subclass of HttpKernel for creating and handling internal sub-requests.
 */
class HttpSubKernel extends HttpKernel
{
    /**
     * Create a new Request instance via the RequestFactory.
     *
     * @param string      $uri         The request URI
     * @param string      $method      HTTP method (GET, POST, etc.)
     * @param array       $headers     HTTP headers as an associative array
     * @param string      $body        Raw request body
     * @param array       $attributes  Additional request attributes
     * @param string|null $version     HTTP protocol version (e.g. '1.1', '2.0')
     *
     * @return Request   A new Request object ready for handling
     */
    public function create(
        string $uri,
        string $method = 'GET',
        array $headers = [],
        string $body = '',
        array $attributes = [],
        ?string $version = null,
    ): Request {
        return $this->requestFactory::make(
            uri: $uri,
            method: $method,
            headers: $headers,
            body: $body,
            attributes: $attributes,
            version: $version
        );
    }

    /**
     * Handle the given sub-request, ensuring the parent HttpKernel logic
     * treats it as an internal (sub) request.
     *
     * @param Request $request       The Request to handle
     * @param bool    $isSubRequest  Flag forcing sub-request behavior (always true here)
     *
     * @return Response The resulting HTTP response
     */
    public function handle(Request $request, bool $isSubRequest = true): Response
    {
        return parent::handle($request, $isSubRequest);
    }
}
