<?php


namespace Arbor\pipeline;


use Arbor\container\ServiceContainer;
use Arbor\pipeline\Pipeline;

/**
 * Class PipelineFactory
 *
 * Factory class responsible for creating instances of the Pipeline.
 * This factory utilizes the provided ServiceContainer to instantiate
 * a Pipeline, ensuring that dependencies are properly managed.
 *
 * @package Arbor\pipeline
 */
class PipelineFactory
{
    /**
     * The service container instance.
     *
     * @var ServiceContainer
     */
    protected ServiceContainer $container;

    /**
     * PipelineFactory constructor.
     *
     * Initializes the factory with the given service container.
     *
     * @param ServiceContainer $container The service container instance used for dependency injection.
     */
    public function __construct(ServiceContainer $container)
    {
        $this->container = $container;
    }


    public function create(): Pipeline
    {
        return new Pipeline($this->container);
    }

    /**
     * Creates and returns a new Pipeline instance.
     *
     * This method is invoked when the factory is used as a callable,
     * providing a convenient way to instantiate a Pipeline with its
     * required dependencies.
     *
     * @return Pipeline A new instance of Pipeline.
     */
    public function __invoke(): Pipeline
    {
        return $this->create();
    }
}
