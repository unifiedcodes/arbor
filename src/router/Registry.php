<?php

namespace Arbor\router;

use Arbor\router\Node;
use Exception;

/**
 * Class Registry
 *
 * Manages the registration and lookup of routes by maintaining a routing tree.
 * Allows adding new routes, matching paths, and resolving route handlers and middlewares.
 *
 * @package Arbor\router
 */
class Registry
{
    /**
     * List of allowed HTTP verbs.
     *
     * @var string[]
     */
    private array $allowedVerbs = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'ANY'];

    /**
     * The root node of the route tree.
     *
     * @var Node
     */
    private Node $routeTree;


    /**
     * repository for error pages..
     *
     * @var array
     */
    protected array $errorPages = [];

    /**
     * Registry constructor.
     *
     * Initializes the route tree with a new root node.
     */
    public function __construct()
    {
        $this->routeTree = new Node();
    }

    /**
     * Adds a new route to the registry.
     *
     * Splits the provided path into segments and traverses the route tree to
     * register each segment. The final node is assigned a handler, HTTP verb,
     * middlewares, and an optional group ID.
     *
     * @param string                    $path         The route path.
     * @param callable|string|array|null $handler     The route handler.
     * @param string                    $verb         The HTTP verb (default is 'GET').
     * @param array                     $middlewares  An array of middlewares.
     * @param string|null               $groupId      Optional group identifier.
     *
     * @return void
     *
     * @throws Exception If the HTTP verb is invalid.
     */
    public function add(
        string $path,
        callable|string|array|null $handler,
        string $verb = 'GET',
        array $middlewares = [],
        ?string $groupId = null
    ): void {
        $this->validateHandler($handler);

        // Break path into segments.
        $segments = $this->getSegments($path);

        // Start from the route tree root.
        $currentNode = $this->routeTree;

        // Traverse the route tree.
        foreach ($segments as $segment) {
            if ($currentNode->getChild($segment)) {
                $currentNode = $currentNode->getChild($segment);
                continue;
            }
            $currentNode = $this->registerNode($currentNode, $segment);
        }

        // Set group ID and meta data for the final node.
        $currentNode->setGroupId($groupId);
        $currentNode->setMeta(
            $this->filterVerb($verb),
            [
                'handler' => $handler,
                'middlewares' => $middlewares,
            ]
        );
    }


    protected function validateHandler(mixed $handler): void
    {
        // Accept callables directly.
        if (is_callable($handler)) {
            return;
        }

        // If handler is a string (like a controller class name) or an array [Class, method],
        // you might want to add further validations.
        if (is_string($handler) && class_exists($handler)) {
            return;
        }

        if (is_array($handler) && count($handler) === 2 && class_exists($handler[0]) && method_exists($handler[0], $handler[1])) {
            return;
        }

        throw new \InvalidArgumentException('Invalid route handler provided.');
    }


    /**
     * Checks if a segment represents a parameterized route.
     *
     * The segment should be in the format: {parameterName} or {parameterName?}.
     *
     * @param string $segment The route segment.
     *
     * @return array|null An associative array with keys 'name' and 'isOptional'
     *                    if the segment is parameterized; otherwise, null.
     */
    protected function isParameterNode(string $segment): ?array
    {
        if (preg_match('/^\{(\w+)(\?)?\}$/', $segment, $matches)) {
            return [
                'name' => $matches[1],
                'isOptional' => isset($matches[2]) // '?' indicates optional
            ];
        }
        return null;
    }

    /**
     * Registers a new node in the route tree.
     *
     * Creates a new node for the given segment and attaches it as a child of the current node.
     * If the segment is parameterized, additional parameter settings are applied.
     *
     * @param Node   $currentNode The current node in the route tree.
     * @param string $segment     The segment name to register.
     *
     * @return Node The newly registered node.
     *
     * @throws Exception If multiple parameter nodes are detected for the same endpoint.
     */
    protected function registerNode(Node $currentNode, string $segment): Node
    {
        $newNode = new Node($segment);

        $parameter = $this->isParameterNode($segment);

        if ($parameter) {
            // Enforce a single parameter per endpoint.
            if ($currentNode->hasParameterChild()) {
                throw new Exception('Multiple Parameters on same endpoint are not allowed.');
            }
            // Flag current node as having a parameter child.
            $currentNode->setParameterChild($segment);
            $newNode->setParameter($parameter['name'], $parameter['isOptional']);
        }

        return $currentNode->addChild($segment, $newNode);
    }

    /**
     * Splits a path string into its segments.
     *
     * Trims the leading and trailing slashes and explodes the string by '/'.
     *
     * @param string $path The route path.
     *
     * @return string[] An array of path segments.
     */
    protected function getSegments(string $path): array
    {
        return explode('/', trim($path, '/'));
    }

    /**
     * Validates and normalizes an HTTP verb.
     *
     * Converts the verb to uppercase and checks if it is allowed.
     *
     * @param string $verb The HTTP verb.
     *
     * @return string The normalized HTTP verb.
     *
     * @throws Exception If the HTTP verb is invalid.
     */
    protected function filterVerb(string $verb): string
    {
        $verb = strtoupper($verb);
        if (!in_array($verb, $this->allowedVerbs, true)) {
            throw new Exception("Invalid HTTP verb: $verb");
        }
        return $verb;
    }


    /**
     * Registers error page handlers for specific HTTP error codes.
     *
     * This method accepts an associative array where each key represents an HTTP error code 
     * (e.g., 404, 405) and the corresponding value is a handler definition for that error.
     * Each handler is validated using the `validateHandler` method to ensure it conforms to the expected format.
     *
     * @param array $errorPages An associative array mapping HTTP error codes to handler definitions.
     *
     * @return void
     *
     * @throws \InvalidArgumentException If any handler fails validation.
     */
    public function setErrorPages(array $errorPages): void
    {
        foreach ($errorPages as $code => $handler) {
            $this->validateHandler($handler);
            $this->errorPages[(int) $code] = $handler;
        }
    }


    public function setErrorPage($errorCode, $handler): void
    {
        $this->validateHandler($handler);
        $this->errorPages[(int) $errorCode] = $handler;
    }


    /**
     * Finds a node corresponding to the given path.
     *
     * Traverses the route tree using the provided path segments. Supports
     * static and parameterized segment matching.
     *
     * @param string $path The route path.
     *
     * @return array|null An associative array containing the 'node' and 'parameters'
     *                    if a matching node is found; otherwise, null.
     */
    public function findNode(string $path): ?array
    {
        $segments = $this->getSegments($path);
        $parameters = [];
        $currentNode = $this->routeTree;

        foreach ($segments as $segment) {
            // Check for a static match.
            if ($currentNode->getChild($segment)) {
                $currentNode = $currentNode->getChild($segment);
                continue;
            }
            // Check for a parameterized match.
            if ($paramChildKey = $currentNode->hasParameterChild()) {
                $currentNode = $currentNode->getChild($paramChildKey);
                $parameters[$currentNode->getParameterName()] = $segment;
                continue;
            }
            return null;
        }

        // Process any remaining optional parameter nodes.
        while ($paramChildName = $currentNode->hasParameterChild()) {
            $parameterChild = $currentNode->getChild($paramChildName);
            if (!$parameterChild->isParameterOptional()) {
                break;
            }
            $currentNode = $parameterChild;
        }

        return [
            'node' => $currentNode,
            'parameters' => $parameters
        ];
    }


    /**
     * Matches a path and HTTP verb to a registered route.
     *
     * Attempts to locate a node for the given path and then checks if the node
     * supports the specified HTTP verb. Returns the route's handler, middlewares,
     * and any parameters extracted from the path.
     *
     * @param string $path The route path.
     * @param string $verb The HTTP verb.
     *
     * @return array An associative array with keys: 'node', 'handler', 'middlewares', and 'parameters'.
     *
     * @throws Exception If no matching route is found or the HTTP verb is not allowed.
     */
    public function matchPath(string $path, string $verb): array
    {
        $verb = strtoupper($verb);
        $found = $this->findNode($path);

        if (!$found) {
            throw new Exception('Not Found', 404);
        }

        // Destructure the found route data.
        ['node' => $node, 'parameters' => $parameters] = $found;

        if (!$node) {
            throw new Exception('Not Found', 404);
        }

        $metaArray = $node->hasAnyMeta();
        if (!$metaArray) {
            throw new Exception('Not Found', 404);
        }

        // If a verb-specific handler exists, use it. Otherwise, if an ANY handler exists, use that.
        if (!$node->hasVerb($verb)) {
            if (isset($metaArray['ANY'])) {
                $verb = 'ANY';
            } else {
                throw new Exception('Method Not Allowed', 405);
            }
        }

        $meta = $node->getMeta($verb);
        if (!$meta) {
            throw new Exception('Not Found', 404);
        }

        $handler = $meta->getHandler();
        if (!$handler) {
            throw new Exception('Not Found', 404);
        }

        $middlewares = $meta->getMiddlewares();

        return [
            'node' => $node,
            'handler' => $handler,
            'middlewares' => $middlewares,
            'parameters' => $parameters
        ];
    }


    /**
     * Retrieves the error page handler associated with a given HTTP error code.
     *
     * This method looks up the error page repository for a handler that matches the provided error code.
     * If a handler is found, it returns the callable, string, or array that defines the error page logic;
     * otherwise, it returns null.
     *
     * @param int $code The HTTP error code (e.g., 404, 405) for which to retrieve the handler.
     *
     * @return callable|string|array|null The handler for the specified error code, or null if none is found.
     */
    public function getErrorPage(int $code): callable|string|array|null
    {
        return $this->errorPages[$code] ?? null;
    }


    public function getRouteTree()
    {
        return $this->routeTree;
    }
}
