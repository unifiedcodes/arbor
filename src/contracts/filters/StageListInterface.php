<?php

namespace Arbor\contracts\filters;

/**
 * Interface for classes that provide a list of available processing stages.
 *
 * This interface defines the contract for stage providers in a filtering or
 * processing pipeline system. Implementing classes should return an array
 * of available stages that can be used within the pipeline.
 *
 * Each stage method should follow the middleware pattern, accepting two parameters:
 * - mixed $input: The input data to be processed
 * - callable $next: The next stage in the pipeline (closure/callback)
 *
 * @package Arbor\contracts\filters
 */
interface StageListInterface
{
    /**
     * Get the list of stages provided by this implementation.
     *
     * Returns an array containing the identifiers, names, or configuration
     * for stages that are available through this provider. The structure
     * and content of the returned array depends on the specific implementation
     * requirements.
     *
     * Each stage method referenced in the returned array should implement
     * the middleware pattern with the following signature:
     * public function stageMethod(mixed $input, callable $next): mixed
     *
     * @return array An array of available stage identifiers or configurations
     * 
     * @example
     * // Simple string identifiers
     * return ['stageMethod', 'stageMethod2', 'stageMethod3'];
     * 
     * @example
     * // Example stage method implementation:
     * public function stageMethod(mixed $input, callable $next): mixed
     * {
     *     // Process the input
     *     $processedInput = $this->processInput($input);
     *     
     *     // Call the next stage in the pipeline
     *     return $next($processedInput);
     * }
     */
    public function provides(): array;
}
