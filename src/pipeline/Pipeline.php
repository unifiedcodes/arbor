<?php

namespace Arbor\pipeline;


use Arbor\container\ServiceContainer;

/**
 * Class Pipeline
 *
 * A lightweight pipeline system that allows passing data through a sequence of stages/middlewares.
 * Each stage can process the input and pass it to the next stage.
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
     * Execute the pipeline and return the final result.
     *
     * @param callable|string|array $destination The final callable that receives the processed input.
     * @return mixed The result of executing the pipeline.
     */
    public function then(callable|string|array $destination): mixed
    {
        $destination = $this->prepareDestination($destination);
        $pipeline = $this->buildPipeline($destination);
        return $pipeline($this->input);
    }


    /**
     * refactor to normalizeStage() which should normalise all stages and destination.
     * 
     * Prepares the final destination callable.
     *
     * This method ensures that the final handler in the pipeline is always a callable.
     * It supports:
     * - A closure or function (passed directly).
     * - A class name (resolved via the container and calls `handle()` method).
     * - An array `[ClassName::class, 'methodName']` to call a specific method.
     *
     * @param callable|string|array $destination The final stage of the pipeline.
     * @return callable A callable that can be used in the pipeline.
     */
    protected function prepareDestination(callable|string|array $destination): callable
    {
        // If destination is a class name, resolve it and use the 'process' method
        if (is_string($destination)) {
            return function ($input) use ($destination) {
                return $this->container->call([$destination, 'process'], ['input' => $input]);
            };
        }


        // If destination is given as [ClassName::class, 'methodName']
        if (is_array($destination) && count($destination) === 2) {
            return function ($input) use ($destination) {
                return $this->container->call([$destination[0], $destination[1]], ['input' => $input]);
            };
        }


        // If it's already a callable, return as is
        return $destination;
    }

    /**
     * Build the pipeline as a series of nested closures.
     *
     * @param callable $destination The final callable that receives the processed input.
     * @return callable The composed pipeline function.
     */
    protected function buildPipeline(callable $destination): callable
    {
        $reversedStages = array_reverse($this->stages);

        return array_reduce(
            $reversedStages,
            function ($next, $stage) {
                return function ($input) use ($stage, $next) {
                    return $this->container->call([$stage, $this->methodName], ['input' => $input, 'next' => $next]);
                };
            },
            $destination
        );
    }
}
