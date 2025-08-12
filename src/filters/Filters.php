<?php

namespace Arbor\filters;

use Arbor\pipeline\PipelineFactory;
use InvalidArgumentException;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

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
     * @var PipelineFactory
     */
    protected PipelineFactory $pipelineFactory;

    /**
     * Maps filter names to their corresponding class names.
     * 
     * @var array<string, string>
     */
    protected array $stageMap = [];

    /**
     * Initialize the Filters instance with a pipeline factory.
     * 
     * Automatically registers all filter stages found in the default stages directory.
     * 
     * @param PipelineFactory $pipelineFactory Factory for creating pipeline instances
     */
    public function __construct(PipelineFactory $pipelineFactory)
    {
        $this->pipelineFactory = $pipelineFactory;

        $this->registerByDir(realpath(__DIR__ . '/stages/'), 'Arbor\filters\stages');
    }

    /**
     * Register a single filter stage with a given name.
     * 
     * @param string $name The name to register the filter under (used for lookup)
     * @param string $class The fully qualified class name of the filter stage
     * @return void
     */
    public function register(string $name, string $class): void
    {
        $this->stageMap[$name] = $class;
    }

    /**
     * Automatically register all PHP classes in a directory as filter stages.
     * 
     * Recursively scans the given directory for PHP files and registers each class
     * as a filter stage using the lowercase filename (without extension) as the filter name.
     * 
     * @param string $dir The directory path to scan for filter stage classes
     * @param string $namespace The namespace prefix for the classes in the directory
     * @return void
     * @throws InvalidArgumentException If the specified directory does not exist
     */
    public function registerByDir(string $dir, string $namespace)
    {
        if (!is_dir($dir)) {
            throw new InvalidArgumentException("Directory {$dir} not found.");
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $className = $namespace . '\\' . $file->getBasename('.php');

                if (!class_exists($className)) {
                    continue;
                }

                $filterName = strtolower($file->getBasename('.php'));
                $this->register($filterName, $className);
            }
        }
    }

    /**
     * Apply filters to multiple input fields in batch.
     * 
     * Processes multiple input fields, applying their respective filter definitions
     * and returning the filtered results in the same structure.
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
     * filter becoming the input to the next.
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

        foreach ($filters as $filterName) {
            if (!isset($this->stageMap[$filterName])) {
                throw new InvalidArgumentException("Filter stage '{$filterName}' is not registered.");
            }

            $stages[] = $this->stageMap[$filterName];
        }

        $pipeline = $this->pipelineFactory->create();

        return $pipeline
            ->send($input)
            ->through($stages)
            ->via('process')
            ->then(fn($output) => $output);
    }
}
