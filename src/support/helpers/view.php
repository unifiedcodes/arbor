<?php

use Arbor\facades\View;
use Arbor\view\Document;

/**
 * Initializes a new component with the given URI and optional data.
 * Delegates to the View facade to start component rendering.
 */
if (!function_exists('startComponent')) {
    function startComponent(string $uri, array $data = []): void
    {
        View::startComponent($uri, $data);
    }
}

/**
 * Completes component rendering and finalizes output.
 * Delegates to the View facade to end component rendering.
 */
if (!function_exists('endComponent')) {
    function endComponent(): void
    {
        View::endComponent();
    }
}

/**
 * Begins capturing content for a named slot.
 * Content captured between startSlot() and endSlot() will be stored in the slot.
 *
 * @param string $name The name of the slot to start capturing for
 */
if (!function_exists('startSlot')) {
    function startSlot(string $name): void
    {
        View::startSlot($name);
    }
}

/**
 * Stops capturing content for the current slot.
 * Delegates to the View facade to finalize slot capture.
 */
if (!function_exists('endSlot')) {
    function endSlot(): void
    {
        View::endSlot();
    }
}

/**
 * Begins capturing content to be pushed to a named stack.
 * Content captured between startPush() and endPush() will be appended to the stack.
 *
 * @param string $name The name of the stack to push content to
 */
if (!function_exists('startPush')) {
    function startPush(string $name): void
    {
        View::startPush($name);
    }
}

/**
 * Stops capturing content for the current push operation.
 * Delegates to the View facade to finalize the push.
 */
if (!function_exists('endPush')) {
    function endPush(): void
    {
        View::endPush();
    }
}

/**
 * Retrieves the content of a named slot.
 *
 * @param string $name The name of the slot to retrieve (defaults to 'default')
 * @return string The content stored in the named slot
 */
if (!function_exists('slot')) {
    function slot(string $name = 'default'): string
    {
        return View::slot($name);
    }
}

/**
 * Renders a view file with the given URI and optional data.
 *
 * @param string $uri The path/URI of the view file to render
 * @param array $data Optional associative array of data to pass to the view
 * @return string The rendered view output as a string
 */
if (!function_exists('render')) {
    function render(string $uri, array $data = []): string
    {
        return View::render($uri, $data);
    }
}

/**
 * Retrieves the current Document instance from the View facade.
 *
 * @return Document The Document object for managing document-level settings
 */
if (!function_exists('document')) {
    function document(): Document
    {
        return View::document();
    }
}

/**
 * Renders a component with the given URI and optional data.
 * Similar to render() but specifically for component files.
 *
 * @param string $uri The path/URI of the component file to render
 * @param array $data Optional associative array of data to pass to the component
 * @return string The rendered component output as a string
 */
if (!function_exists('component')) {
    function component(string $uri, array $data = []): string
    {
        return View::component($uri, $data);
    }
}

/**
 * Generates an asset URL for the given resource URI.
 * Useful for constructing paths to CSS, JavaScript, images, and other static assets.
 *
 * @param string $uri The relative URI of the asset
 * @return string The complete URL path to the asset
 */
if (!function_exists('asset')) {
    function asset(string $uri): string
    {
        return View::asset($uri);
    }
}

/**
 * Escapes HTML special characters in the given value to prevent XSS attacks.
 * Converts the value to a string and escapes quotes and other special characters.
 *
 * @param mixed $value The value to escape (will be cast to string)
 * @return string The HTML-escaped string
 */
if (!function_exists('escapeHtml')) {
    function esc(mixed $value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }
}
