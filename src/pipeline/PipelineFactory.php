<?php


namespace Arbor\pipeline;


use Arbor\container\Invoker;
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
    public function create(): Pipeline
    {
        return new Pipeline();
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
