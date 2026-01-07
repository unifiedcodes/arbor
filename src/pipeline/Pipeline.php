<?php

namespace Arbor\pipeline;


use InvalidArgumentException;
use Arbor\container\ServiceContainer;

/**
 * Class Pipeline
 *
 * A lightweight pipeline system that allows passing data through a sequence of stages/middlewares.
 * Each stage can process the input and pass it to the next stage.
 *
 * The Pipeline class follows a fluent interface pattern allowing method chaining for configuration.
 * It supports various types of middleware stages including callables, class names, and arrays.
 * The pipeline resolves dependencies through a service container and can customize method names
 * and parameters for each stage.
 *
 * Example usage:
 * ```php
 * $result = $pipeline
 *     ->send($data)
 *     ->through([Stage1::class, Stage2::class])
 *     ->via('handle')
 *     ->then(FinalStage::class);
 * ```
 *
 * @package Arbor\pipeline
 * 
 */
class Pipeline
{
    /**
     * The service container used for resolving dependencies.
     *
     * @var ServiceContainer
     */
    protected ServiceContainer $container;

    /**
     * The input data that will be processed by the pipeline.
     *
     * @var mixed
     */
    protected mixed $input;

    /**
     * The list of middleware stages in the pipeline.
     *
     * @var array
     */
    protected array $stages = [];

    /**
     * The method name that each stage should call.
     *
     * @var string
     */
    protected string $methodName = 'process';

    /**
     * Pipeline constructor.
     *
     * @param ServiceContainer $container The service container for resolving dependencies.
     */
    public function __construct(ServiceContainer $container)
    {
        $this->container = $container;
    }

    /**
     * Set the input data to be processed.
     *
     * @param mixed $input The input data.
     * @return self Returns the pipeline instance for method chaining.
     */
    public function send(mixed $input): self
    {
        $this->input = $input;
        return $this;
    }

    /**
     * Define the middleware stages the pipeline should pass through.
     *
     * @param array $stages A list of middleware classes.
     * @return self Returns the pipeline instance for method chaining.
     */
    public function through(array $stages): self
    {
        $this->stages = $stages;
        return $this;
    }

    /**
     * Define the method name to be called on each middleware stage.
     *
     * @param string $methodName The method name (default: 'process').
     * @return self Returns the pipeline instance for method chaining.
     */
    public function via(string $methodName): self
    {
        $this->methodName = $methodName;
        return $this;
    }

    /**
     * Execute the pipeline with the specified destination (final stage).
     *
     * This method builds and executes the complete pipeline, passing the input data
     * through all configured stages and finally to the destination. The destination
     * can be a callable, class name, or array format [class, method].
     *
     * @param callable|string|array $destination The final destination for the pipeline.
     *                                          Can be:
     *                                          - A callable function/closure
     *                                          - A class name string (will use configured method or __invoke)
     *                                          - An array [class, method] format
     * @param array $customParams Additional parameters to pass to the destination stage.
     * @return mixed The result from executing the complete pipeline.
     * @throws InvalidArgumentException If the destination format is invalid.
     */
    public function then(callable|string|array $destination, array $customParams = []): mixed
    {
        $destination = $this->normalizeStage($destination, $customParams, true);
        $pipeline = $this->buildPipeline($destination);
        return $pipeline($this->input);
    }

    /**
     * Normalize a stage into a consistent callable format.
     *
     * This method converts various stage formats (callable, class name, array) into
     * a standardized callable that can be executed within the pipeline. It handles
     * dependency injection through the service container and manages parameter passing.
     *
     * @param callable|string|array $stage The stage to normalize. Can be:
     *                                    - A callable function/closure
     *                                    - A class name string
     *                                    - An array [class, method] format
     * @param array $customParams Custom parameters to merge with standard parameters.
     * @param bool $isFinal Whether this is the final stage (affects parameter building).
     * @return callable A normalized callable that accepts ($input, $next) parameters.
     * @throws InvalidArgumentException If the stage format is not supported.
     */
    protected function normalizeStage(callable|string|array $stage, array $customParams = [], bool $isFinal = false): callable
    {
        // Always return a closure (uniform API)
        if (is_callable($stage)) {
            return function ($input, $next) use ($stage, $customParams, $isFinal) {

                $parameters = $this->buildParameters($input, $next, $customParams, $isFinal);

                return $this->container->call($stage, $parameters);
            };
        }

        if (is_string($stage) && class_exists($stage)) {
            return function ($input, $next) use ($stage, $customParams, $isFinal) {

                $parameters = $this->buildParameters($input, $next, $customParams, $isFinal);

                $methodName = $this->methodName;

                if (method_exists($stage, '__invoke')) {
                    $methodName = '__invoke';
                }

                return $this->container->call([$stage, $methodName], $parameters);
            };
        }

        if (is_array($stage) && count($stage) === 2) {
            return function ($input, $next) use ($stage, $customParams, $isFinal) {

                $parameters = $this->buildParameters($input, $next, $customParams, $isFinal);

                return $this->container->call([$stage[0], $stage[1]], $parameters);
            };
        }

        throw new InvalidArgumentException('Invalid stage format.');
    }

    /**
     * Build the parameter array for stage execution.
     *
     * Creates a standardized parameter array that includes the input data,
     * optional next callback (for non-final stages), and any custom parameters.
     * The parameters are used by the service container for dependency injection.
     *
     * @param mixed $input The input data being processed through the pipeline.
     * @param callable $next The next callable in the pipeline chain.
     * @param array $customParams Additional custom parameters to include.
     * @param bool $isFinal Whether this is the final stage (excludes 'next' parameter).
     * @return array The built parameter array with keys for dependency injection.
     */
    private function buildParameters($input, $next, array $customParams, bool $isFinal): array
    {
        $parameters = array_merge(['input' => $input], $customParams);
        if (!$isFinal) $parameters['next'] = $next;
        return $parameters;
    }

    /**
     * Build the complete pipeline execution chain.
     *
     * This method constructs the pipeline by wrapping each stage around the next,
     * starting from the destination and working backwards through the stages.
     * It creates a nested structure of callables where each stage can process
     * the input and decide whether to pass it to the next stage.
     *
     * The pipeline is built in reverse order (last to first) to ensure proper
     * execution flow when called.
     *
     * @param callable $destination The final destination callable for the pipeline.
     * @return callable A callable that executes the complete pipeline when invoked with input.
     */
    protected function buildPipeline(callable $destination): callable
    {
        // Final handler: calls destination with ONLY input
        $pipeline = function ($input) use ($destination) {
            return $destination($input, null);
        };

        foreach (array_reverse($this->stages) as $stage) {

            $stageCallable = $this->normalizeStage($stage);
            $next = $pipeline;

            $pipeline = function ($input) use ($stageCallable, $next) {
                return $stageCallable(
                    $input,
                    function ($input) use ($next) {
                        return $next($input); // MUST return Response
                    }
                );
            };
        }

        return fn($input) => $pipeline($input);
    }
}
