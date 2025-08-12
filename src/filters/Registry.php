<?php

namespace Arbor\filters;


use RuntimeException;
use InvalidArgumentException;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Arbor\contracts\filters\StageInterface;
use Arbor\contracts\filters\StageListInterface;

/**
 * Registry class for managing filter stages
 * 
 * This class serves as a central registry for filter stages, allowing registration
 * of both individual stages (StageInterface) and collections of stages (StageListInterface).
 * It provides a unified interface for registering and resolving filter stages by name.
 * 
 * @package Arbor\filters
 * 
 */
class Registry
{
    /**
     * Storage for registered filter stages
     * 
     * Array structure: [stage_name => [object_instance, method_name]]
     * - stage_name: string identifier for the stage
     * - object_instance: the stage object or stage list object
     * - method_name: the method to call on the object for filtering
     * 
     * @var array
     */
    protected $stages = [];

    /**
     * Register a filter stage or stage list
     * 
     * This method accepts either a single stage implementing StageInterface
     * or a collection of stages implementing StageListInterface. It dispatches
     * to the appropriate registration method based on the object type.
     * 
     * @param StageInterface|StageListInterface $class The stage or stage list to register
     * @return void
     * @throws InvalidArgumentException If the provided object doesn't implement required interfaces
     */
    public function register(StageInterface|StageListInterface $class)
    {
        // Handle single stage
        if ($class instanceof StageInterface) {
            return $this->registerStage($class);
        }

        // Handle stage list
        if ($class instanceof StageListInterface) {
            return $this->registerStageList($class);
        }

        // Throw exception for invalid types
        throw new InvalidArgumentException(
            sprintf(
                'Invalid class type passed to Registry::register. Expected StageInterface or StageListInterface, got %s',
                get_class($class)
            )
        );
    }

    /**
     * Register filter stages from a directory
     * 
     * Automatically discovers and registers filter stages from PHP files within
     * a specified directory. This method recursively scans the directory, converts
     * file paths to fully qualified class names using the provided namespace, and
     * registers any classes that implement the required filter interfaces.
     * 
     * The method assumes:
     * - All PHP files contain classes with no-argument constructors
     * - Class names match their file names
     * - Directory structure mirrors namespace structure
     * 
     * @param string $dir The directory path to scan for stage classes
     * @param string $namespace The base namespace for the discovered classes
     * @return void
     * @throws InvalidArgumentException If the provided path is not a directory
     * @throws RuntimeException If a discovered class cannot be found or instantiated
     */
    public function registerFromDir(string $dir, string $namespace): void
    {
        if (!is_dir($dir)) {
            throw new InvalidArgumentException("Provided path is not a directory: $dir");
        }

        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

        foreach ($rii as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $filePath = $file->getRealPath();

                // Get path relative to base directory
                $relativePath = substr($filePath, strlen(rtrim($dir, DIRECTORY_SEPARATOR)) + 1);

                // Convert directory separators to namespace separators
                $className = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

                // Remove the .php extension
                $className = substr($className, 0, -4);

                // Compose fully qualified class name
                $fqn = rtrim($namespace, '\\') . '\\' . $className;

                // Normalize namespace separators (in case)
                $fqn = str_replace('\\\\', '\\', $fqn);

                // Instantiate class (assumes no-argument constructor)
                if (class_exists($fqn)) {
                    $instance = new $fqn();

                    // Register the instance using this class's register method
                    $this->register($instance);
                } else {
                    throw new RuntimeException("Class $fqn not found.");
                }
            }
        }
    }


    /**
     * Register a single filter stage
     * 
     * Registers an individual stage by storing it with its name as the key.
     * The stage's test method will be called when the stage is resolved.
     * 
     * @param StageInterface $stage The stage to register
     * @return void
     * @throws InvalidArgumentException If a stage with the same name is already registered
     */
    protected function registerStage(StageInterface $stage): void
    {
        // Get the stage's identifier name
        $name = $stage->name();

        // Check for naming conflicts
        if (isset($this->stages[$name])) {
            throw new InvalidArgumentException("A stage named : '$name' is already registered.");
        }

        // Store stage with 'test' as the method to call
        $this->stages[$name] = [$stage, 'test'];
    }

    /**
     * Register multiple filter stages from a stage list
     * 
     * Processes a StageListInterface object that provides multiple stages.
     * The provides() method returns an array where:
     * - Numeric keys: value is both stage name and method name
     * - String keys: key is stage name, value is method name
     * 
     * @param StageListInterface $stageList The stage list to register
     * @return void
     */
    protected function registerStageList(StageListInterface $stageList): void
    {
        // Iterate through all stages provided by the stage list
        foreach ($stageList->provides() as $key => $value) {
            if (is_int($key)) {
                // Numeric key → $value is the stage name, method name same as stage name
                $this->stages[$value] = [$stageList, $value];
            } else {
                // String key → $key is the stage name, $value is the method name
                $this->stages[$key] = [$stageList, $value];
            }
        }
    }

    /**
     * Resolve a registered stage by name
     * 
     * Returns the callable array for a registered stage, which can be used
     * to invoke the filtering logic. The returned array contains the object
     * instance and method name that should be called.
     * 
     * @param string $stageName The name of the stage to resolve
     * @return callable|array Array containing [object_instance, method_name]
     * @throws InvalidArgumentException If no stage is registered with the given name
     */
    public function resolve(string $stageName): callable|array
    {
        // Check if the stage exists
        if (!isset($this->stages[$stageName])) {
            throw new InvalidArgumentException("No stage registered with name: '{$stageName}'.");
        }

        // Return the callable array [object, method]
        return $this->stages[$stageName];
    }

    /**
     * Resolve multiple stages by their names
     * 
     * Accepts an array of filter names and returns an array of resolved stages.
     * Each resolved stage is a callable array that can be used to invoke the
     * filtering logic for that particular stage.
     * 
     * @param array $filters Array of stage names to resolve
     * @return array Array of resolved stages, indexed by stage name
     * @throws InvalidArgumentException If any stage name is not registered
     */
    public function resolveAll(array $filters): array
    {
        $resolvedStages = [];

        foreach ($filters as $filter) {
            $resolvedStages[] = $this->resolve($filter);
        }

        return $resolvedStages;
    }
}
