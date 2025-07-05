<?php

namespace Arbor\http\traits;

use Arbor\http\components\Stream;
use Exception;

/**
 * Trait for handling HTTP message bodies.
 *
 * This trait provides functionality for working with HTTP message bodies,
 * including initialization, retrieval, and immutable updates.
 *
 * @package Arbor\http\traits
 */
trait BodyTrait
{
    /**
     * The message body as a Stream instance or null.
     *
     * @var Stream|null
     */
    protected ?Stream $body = null;

    /**
     * Retrieves the message body.
     *
     * @return Stream The message body as a Stream instance.
     * @throws Exception If the body has not been initialized.
     */
    public function getBody(): Stream
    {
        if (!isset($this->body)) {
            throw new Exception('Body have not been initialized.');
        }

        return $this->body;
    }

    /**
     * Ensures the provided body is converted to a Stream instance.
     *
     * @param Stream|string|null $body The body to ensure as a Stream.
     * @return Stream|null A Stream instance or null if body was null.
     */
    protected function ensureStreamBody(Stream|string|null $body): ?Stream
    {
        if ($body === null) return null;
        return $body instanceof Stream ? $body : new Stream($body);
    }

    /**
     * Returns a new instance with the specified body.
     *
     * @param Stream|string|null $body The body to use.
     * @return static A new instance with the specified body.
     */
    public function withBody(Stream|string|null $body): static
    {
        $new = clone $this;
        $new->body = $this->ensureStreamBody($body);
        return $new;
    }
}
