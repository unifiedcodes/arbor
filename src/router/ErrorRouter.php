<?php

namespace Arbor\router;

use Arbor\facades\Route;

/**
 * ErrorRouter
 * 
 * Provides a specialized routing interface for registering error handlers based on HTTP status codes.
 * This class translates error code-based route registration into standard route definitions with
 * a special path prefix, allowing the application to handle different HTTP error responses
 * through the normal routing system.
 * 
 * Error routes are registered with paths in the format: /__error__/{code}
 * where {code} is either an HTTP status code (e.g., 404, 500) or 'default' for catch-all errors.
 * 
 * @package Arbor\router
 */
class ErrorRouter
{
    /**
     * Constant representing a default/catch-all error handler.
     * 
     * Use this constant when registering an error handler that should catch
     * all errors that don't have a specific handler registered.
     * 
     * @var int
     */
    public const DEFAULT = -1;

    /**
     * Register a GET request error handler for the specified HTTP status code.
     * 
     * @param int $code The HTTP status code (e.g., 404, 500) or ErrorRouter::DEFAULT for catch-all
     * @param mixed $handler The handler to execute when this error occurs (callable, controller, etc.)
     * @return mixed The result of Route::get() registration
     */
    public function get(int $code, mixed $handler)
    {
        return Route::get($this->buildPath($code), $handler);
    }

    /**
     * Register a POST request error handler for the specified HTTP status code.
     * 
     * @param int $code The HTTP status code (e.g., 404, 500) or ErrorRouter::DEFAULT for catch-all
     * @param mixed $handler The handler to execute when this error occurs (callable, controller, etc.)
     * @return mixed The result of Route::post() registration
     */
    public function post(int $code, mixed $handler)
    {
        return Route::post($this->buildPath($code), $handler);
    }

    /**
     * Register a PUT request error handler for the specified HTTP status code.
     * 
     * @param int $code The HTTP status code (e.g., 404, 500) or ErrorRouter::DEFAULT for catch-all
     * @param mixed $handler The handler to execute when this error occurs (callable, controller, etc.)
     * @return self The result of Route::put() registration
     */
    public function put(int $code, mixed $handler): self
    {
        return Route::put($this->buildPath($code), $handler);
    }

    /**
     * Register a PATCH request error handler for the specified HTTP status code.
     * 
     * @param int $code The HTTP status code (e.g., 404, 500) or ErrorRouter::DEFAULT for catch-all
     * @param mixed $handler The handler to execute when this error occurs (callable, controller, etc.)
     * @return self The result of Route::patch() registration
     */
    public function patch(int $code, mixed $handler): self
    {
        return Route::patch($this->buildPath($code), $handler);
    }

    /**
     * Register a DELETE request error handler for the specified HTTP status code.
     * 
     * @param int $code The HTTP status code (e.g., 404, 500) or ErrorRouter::DEFAULT for catch-all
     * @param mixed $handler The handler to execute when this error occurs (callable, controller, etc.)
     * @return self The result of Route::delete() registration
     */
    public function delete(int $code, mixed $handler): self
    {
        return Route::delete($this->buildPath($code), $handler);
    }

    /**
     * Register an OPTIONS request error handler for the specified HTTP status code.
     * 
     * @param int $code The HTTP status code (e.g., 404, 500) or ErrorRouter::DEFAULT for catch-all
     * @param mixed $handler The handler to execute when this error occurs (callable, controller, etc.)
     * @return self The result of Route::options() registration
     */
    public function options(int $code, mixed $handler): self
    {
        return Route::options($this->buildPath($code), $handler);
    }

    /**
     * Register a HEAD request error handler for the specified HTTP status code.
     * 
     * @param int $code The HTTP status code (e.g., 404, 500) or ErrorRouter::DEFAULT for catch-all
     * @param mixed $handler The handler to execute when this error occurs (callable, controller, etc.)
     * @return self The result of Route::head() registration
     */
    public function head(int $code, mixed $handler): self
    {
        return Route::head($this->buildPath($code), $handler);
    }

    /**
     * Register an error handler for all HTTP methods for the specified status code.
     * 
     * This method registers the handler to respond to any HTTP method (GET, POST, PUT, etc.)
     * when the specified error code occurs.
     * 
     * @param int $code The HTTP status code (e.g., 404, 500) or ErrorRouter::DEFAULT for catch-all
     * @param mixed $handler The handler to execute when this error occurs (callable, controller, etc.)
     * @return mixed The result of Route::any() registration
     */
    public function any(int $code, mixed $handler)
    {
        return Route::any($this->buildPath($code), $handler);
    }

    /**
     * Build the internal route path for an error handler.
     * 
     * Transforms an HTTP status code into a special internal route path.
     * If the code is DEFAULT (-1), it converts to the string 'default'.
     * 
     * @param int $code The HTTP status code or ErrorRouter::DEFAULT
     * @return string The internal route path in format: /__error__/{code}
     */
    protected function buildPath(int $code): string
    {
        if ($code === self::DEFAULT) {
            $code = 'default';
        }

        return "/__error__/{$code}";
    }
}
