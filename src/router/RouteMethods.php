<?php


namespace Arbor\router;


trait RouteMethods
{
    /**
     * Adds a GET route.
     *
     * @param string $path    The route path.
     * @param mixed  $handler The route handler.
     *
     * @return void
     */
    public function get(string $path, mixed $handler): self
    {
        return $this->addRoute($path, $handler, 'GET');
    }

    /**
     * Adds a POST route.
     *
     * @param string $path    The route path.
     * @param mixed  $handler The route handler.
     *
     * @return void
     */
    public function post(string $path, mixed $handler): self
    {
        return $this->addRoute($path, $handler, 'POST');
    }


    /**
     * Adds a PUT route.
     *
     * @param string $path The route path.
     * @param mixed $handler The route handler.
     * @return self
     */
    public function put(string $path, mixed $handler): self
    {
        return $this->addRoute($path, $handler, 'PUT');
    }


    /**
     * Adds a PATCH route.
     *
     * @param string $path The route path.
     * @param mixed $handler The route handler.
     * @return self
     */
    public function patch(string $path, mixed $handler): self
    {
        return $this->addRoute($path, $handler, 'PATCH');
    }


    /**
     * Adds a DELETE route.
     *
     * @param string $path The route path.
     * @param mixed $handler The route handler.
     * @return self
     */
    public function delete(string $path, mixed $handler): self
    {
        return $this->addRoute($path, $handler, 'DELETE');
    }

    /**
     * Adds an OPTIONS route.
     *
     * @param string $path The route path.
     * @param mixed $handler The route handler.
     * @return self
     */
    public function options(string $path, mixed $handler): self
    {
        return $this->addRoute($path, $handler, 'OPTIONS');
    }

    /**
     * Adds a HEAD route.
     *
     * @param string $path The route path.
     * @param mixed $handler The route handler.
     * @return self
     */
    public function head(string $path, mixed $handler): self
    {
        return $this->addRoute($path, $handler, 'HEAD');
    }

    /**
     * Adds a route for any HTTP method.
     */
    public function any(string $path, mixed $handler): self
    {
        return $this->addRoute($path, $handler, 'ANY');
    }
}
