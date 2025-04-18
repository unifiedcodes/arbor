<?php

namespace Arbor\http;


use Arbor\http\HttpKernel;
use Arbor\http\Response;


// convinience class only uses requestfactory and httpkernel internally..
class HttpSubKernel extends HttpKernel
{
    // convinience method only.
    // delegates to requestfactory::sub
    public function create(
        string $uri,
        string $method = 'GET',
        array $headers = [],
        string $body = '',
        array $attributes = [],
        ?string $version = null,
    ) {
        $request = $this->requestFactory::make(
            uri: $uri,
            method: $method,
            headers: $headers,
            body: $body,
            attributes: $attributes,
            version: $version,
        );

        return $request;
    }


    public function handle(Request $request, bool $isSubRequest = true): Response
    {
        return parent::handle($request, $isSubRequest);
    }
}
