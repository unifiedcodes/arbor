<?php

namespace Arbor\filters;

use Arbor\pipeline\PipelineFactory;
use Arbor\filters\Registry;
use InvalidArgumentException;
use Arbor\contracts\filters\StageInterface;
use Arbor\contracts\filters\StageListInterface;
use Arbor\filters\StageList;

/**
 * Filters class for managing and applying data filtering operations through a pipeline system.
 * 
 * This class provides a flexible framework for registering filter stages and applying them
 * to data through a pipeline pattern. It supports automatic discovery of filter classes
 * from directories and batch processing of multiple inputs.
 * 
 * @package Arbor\filters
 */
class Filters
{
    /**
     * Factory for creating pipeline instances.
     * 
     * Used to create new pipeline instances for processing data through registered filter stages.
     * 
     * @var PipelineFactory
     */
    protected PipelineFactory $pipelineFactory;

    /**
     * Registry for managing and storing filter stage instances.
     * 
     * Handles the registration, storage, and resolution of filter stages. Acts as a
     * container for all available filter stages that can be applied to data.
     * 
     * @var Registry
     */
    protected Registry $registry;

    /**
     * Initialize the Filters instance with a pipeline factory and registry.
     * 
     * Sets up the core dependencies required for filter processing. The pipeline factory
     * is used to create processing pipelines, while the registry manages available filter stages.
     * 
     * @param PipelineFactory $pipelineFactory Factory for creating pipeline instances
     * @param Registry $registry Registry for managing filter stage instances
     */
    public function __construct(PipelineFactory $pipelineFactory, Registry $registry)
    {
        $this->pipelineFactory = $pipelineFactory;
        $this->registry = $registry;
    }

    /**
     * Register a filter stage or stage list with the registry.
     * 
     * Adds a new filter stage or collection of stages to the registry, making them
     * available for use in filter operations. Supports both individual stages
     * implementing StageInterface and collections implementing StageListInterface.
     * 
     * @param StageInterface|StageListInterface $class The filter stage or stage list to register
     * @return mixed The result of the registry registration operation
     */
    public function register(StageInterface|StageListInterface $class)
    {
        return $this->registry->register($class);
    }

    /**
     * Register the default collection of filter stages.
     * 
     * Convenience method that registers a new StageList instance containing
     * the default set of filter stages. This provides a quick way to load
     * commonly used filters without manual registration.
     * 
     * @return void
     */
    public function withStages()
    {
        $this->register(new StageList());
    }

    /**
     * Apply filters to multiple input fields in batch.
     * 
     * Processes multiple input fields, applying their respective filter definitions
     * and returning the filtered results in the same structure. This method is useful
     * for processing form data, API requests, or any scenario where multiple fields
     * need different filter treatments.
     * 
     * @param array<string, mixed> $inputs Associative array of input data keyed by field names
     * @param array<string, string|array> $definitions Filter definitions for each field
     * @return array<string, mixed> Filtered results with the same keys as input
     */
    public function filterBatch(array $inputs, array $definitions): array
    {
        $results = [];

        foreach ($definitions as $field => $filters) {
            $value = $inputs[$field] ?? null;
            $results[$field] = $this->apply($value, $filters);
        }

        return $results;
    }

    /**
     * Apply a series of filters to a single input value.
     * 
     * Filters can be specified as a comma-separated string or an array of filter names.
     * Each filter is applied sequentially through a pipeline, with the output of one
     * filter becoming the input to the next. The pipeline processes data through all
     * registered stages in the order specified.
     * 
     * @param mixed $input The input value to be filtered
     * @param string|array<string> $filters Filter names as comma-separated string or array
     * @return mixed The filtered output after applying all specified filters
     * @throws InvalidArgumentException If any specified filter stage is not registered
     */
    public function apply(mixed $input, string|array $filters): mixed
    {
        if (is_string($filters)) {
            $filters = explode(',', $filters);
        }

        $stages = $this->registry->resolveAll($filters);

        $pipeline = $this->pipelineFactory->create();

        return $pipeline
            ->send($input)
            ->through($stages)
            ->via('process')
            ->then(fn($output) => $output);
    }
}
