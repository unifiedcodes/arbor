<?php

namespace Arbor\router;


use Arbor\router\Registry;
use Arbor\router\Group;
use Arbor\router\Dispatcher;
use Arbor\router\URLBuilder;
use Arbor\http\context\RequestContext;
use Arbor\pipeline\PipelineFactory;
use Arbor\router\RouteMethods;
use Arbor\http\context\RequestStack;
use Exception;

/**
 * Class Router (Router Facade)
 *
 * Manages route registration, grouping, resolution, and dispatching.
 * This class handles grouping of routes, adding individual routes,
 * and resolving incoming HTTP requests to the corresponding route handlers.
 *
 * @package Arbor\router
 */
class Router
{
    /**
     * Trait providing route registration methods such as get(), put(), post(), etc.
     */
    use RouteMethods;

    /**
     * The registry that holds the route tree.
     *
     * @var Registry
     */
    protected Registry $registry;

    /**
     * The group manager for handling route groups.
     *
     * @var Group
     */
    protected Group $group;

    /**
     * URLBuilder instance.
     *
     * @var URLBuilder
     */
    protected URLBuilder $URLBuilder;


    /**
     * Pending group options to be applied for route grouping.
     *
     * @var array
     */
    protected array $groupOptions = [];


    /**
     * Keeps last added path to store in urlbuilder if name method is chained.
     *
     * @var string
     */
    protected string $lastPath;

    /**
     * requeststack instance.
     *
     * @var RequestStack
     */
    protected RequestStack $requestStack;

    /**
     * Router constructor.
     *
     * Initializes the registry and group manager.
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;

        $this->registry = new Registry();
        $this->group = new Group();
        $this->URLBuilder = new URLBuilder();
    }

    /**
     * Sets options for the next group of routes.
     *
     * @param array $options The group options (e.g., prefix, namespace, middlewares).
     *
     * @return self
     */
    public function groupOptions(array $options): self
    {
        $this->groupOptions = $options;
        return $this;
    }

    /**
     * Groups routes together using the provided callback.
     *
     * Applies any pending group options, executes the callback to define
     * the routes within the group, and then removes the group from the stack.
     *
     * @param callable $callback The callback that defines the grouped routes.
     *
     * @return void
     */
    public function group(callable $callback): void
    {
        $this->group->push($this->groupOptions);

        // Clear pending options after applying.
        $this->groupOptions = [];

        $callback($this);

        $this->group->pop();
    }

    /**
     * Loads and groups routes from a file.
     *
     * The file should return or define routes using the provided router instance.
     *
     * @param string $filePath The file path containing route definitions.
     *
     * @return void
     */
    public function groupByFile(string $filePath): void
    {
        $this->group(function () use ($filePath): void {
            // Explicitly set $router for use within the required file.
            $router = $this;
            require_once $filePath;
        });
    }

    /**
     * Adds a new route to the registry.
     *
     * If a group is active, the route path is prefixed with the group's prefix,
     * and the group ID is applied.
     *
     * @param string               $path    The route path.
     * @param mixed                $handler The route handler (callable, string, or array).
     * @param string               $verb    The HTTP verb for the route.
     *
     * @return self
     */
    public function addRoute(string $path, mixed $handler, string $verb): self
    {
        // Check if a group is active and apply group options.
        if ($this->group->isGroupActive()) {
            $path = $this->group->getGroupedPath($path);
            $groupId = $this->group->getCurrentGroupId();
        }


        // Push groupID to registry.
        $this->registry->add(
            $path,
            $handler,
            $verb,
            [],
            isset($groupId) ? $groupId : null
        );


        $this->lastPath = !empty($path) ? $path : '/';

        return $this;
    }

    /**
     * Resolves an incoming HTTP request to a matching route.
     *
     * Extracts the path and verb from the request, finds a corresponding route,
     * aggregates middlewares from any associated group, and returns the match.
     *
     * @param RequestContext $request The incoming HTTP request.
     *
     * @return array|null An associative array containing route details (node, handler, middlewares, parameters)
     *                    if a match is found; otherwise, null.
     *
     * @throws Exception If route matching fails.
     */
    public function resolve(RequestContext $request): ?array
    {
        try {
            // Extract path and verb from the request.
            $path = $request->getRelativePath();
            $verb = $request->getMethod();

            // Find a route match.
            $foundMatch = $this->registry->matchPath($path, $verb);

            $node = $foundMatch['node'];

            // Aggregate group middlewares and combine with route middlewares.
            $groupMiddlewares = [];

            $groupId = $node->getGroupId();

            if ($groupId) {
                $groupMiddlewares = $this->group->getMiddlewares($groupId);
            }

            $foundMatch['middlewares'] = array_unique(
                array_merge($groupMiddlewares, $foundMatch['middlewares'])
            );

            return $foundMatch;
        } catch (Exception $e) {

            // Retrieve error page handler based on the exception code.
            $errorHandler = $this->registry->getErrorPage($e->getCode());

            if ($errorHandler !== null) {
                return [
                    'node'         => null,
                    'handler'      => $errorHandler,
                    'middlewares'  => [],
                    'parameters'   => []
                ];
            }

            throw $e;
        }
    }


    /**
     * Loads error page handlers from a file and registers them with the Registry.
     *
     * The file at the specified path must return an associative array where the keys 
     * are HTTP error codes and the values are valid route handler definitions. This 
     * allows for centralized, file-based configuration of global error handling.
     *
     * @param string $filePath The path to the file containing error page definitions.
     *
     * @throws Exception If the file does not return an array.
     *
     * @return void
     */
    public function errorPagesByFile(string $filePath): void
    {
        // Explicitly set $router for use within the required file.
        $router = $this;
        $errorPages = require_once $filePath;

        if (!is_array($errorPages)) {
            throw new Exception("The file '{$filePath}' must return an array.");
        }

        $this->registry->setErrorPages($errorPages);
    }


    /**
     * Dispatches the HTTP request using the provided pipeline factory.
     *
     * Delegates the request handling to the Dispatcher, which processes the route
     * and returns a response.
     *
     * @param RequestContext         $request         The HTTP request.
     * @param PipelineFactory $pipelineFactory The pipeline factory instance.
     *
     * @return mixed The response returned by the dispatcher.
     * 
     */
    public function dispatch(RequestContext $request, PipelineFactory $pipelineFactory): mixed
    {
        $route = $this->resolve($request);
        $dispatcher = new Dispatcher($pipelineFactory);
        return $dispatcher->dispatch($route, $request);
    }


    /**
     * Adds named registry to URLBuilder.
     * 
     * @param string $name
     * 
     * @return void
     */
    public function name(string $name): void
    {
        if ($this->lastPath) {
            $this->URLBuilder->add($name, $this->lastPath);
        }
    }

    /**
     * Generates an absolute URL for a named route.
     *
     * @param string $name The name of the route.
     * @param array $parameters Optional parameters for the route.
     * @return string|null The absolute URL if found, otherwise null.
     */
    public function url(string $name, array $parameters = []): ?string
    {
        $baseURI = $this->requestStack->getCurrent()->getBaseUri();

        $routeURL = $this->URLBuilder->getAbsoluteURL($baseURI, $name, $parameters);

        return $routeURL;
    }

    /**
     * Generates a relative URL for a named route.
     *
     * @param string $name The name of the route.
     * @param array $parameters Optional parameters for the route.
     * @return string|null The relative URL if found, otherwise null.
     */
    public function relativeURL(string $name, array $parameters = []): ?string
    {
        $routeURL = $this->URLBuilder->getRelativeURL($name, $parameters);
        return $routeURL;
    }


    public function getRouteTree()
    {
        return $this->registry->getRouteTree();
    }


    public function getGroupById($id)
    {
        return $this->group->getGroupById($id);
    }
}
