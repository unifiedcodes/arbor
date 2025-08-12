<?php

namespace Arbor\contracts\filters;

/**
 * Stage Interface
 * 
 * Defines the contract for pipeline stages that process input data through
 * a middleware-like pattern. Each stage can modify the input and pass it
 * to the next stage in the pipeline.
 * 
 * This interface follows the middleware pattern where each stage receives
 * the input data and a callable representing the next stage in the pipeline.
 * 
 * @package Arbor\contracts\filters
 */
interface StageInterface
{
    /**
     * Get the name identifier for this stage.
     * 
     * Returns a unique string identifier for this stage, which can be used
     * for logging, debugging, or pipeline introspection purposes.
     * 
     * @return string The unique name/identifier for this stage
     * 
     * @example
     * ```php
     * public function name(): string
     * {
     *     return 'filter-stage';
     * }
     * ```
     */
    public function name(): string;

    /**
     * Process the given input value through this stage.
     * 
     * This method implements the core logic of the stage. It receives the input
     * data and a callable representing the next stage in the pipeline. The stage
     * can:
     * - Modify the input before passing it to the next stage
     * - Perform filtering
     * - Add metadata or transform the data
     * - Short-circuit the pipeline by not calling $next
     * - Handle the response from subsequent stages
     * 
     * @param mixed $input The input data to be processed by this stage
     * @param callable $next A callable that represents the next stage in the pipeline.
     *                       Call this function with the (possibly modified) input to
     *                       continue the pipeline execution.
     * 
     * @return mixed The processed result, which may be the original input,
     *               a modified version, or the result from subsequent stages
     * 
     * @throws \Exception May throw exceptions if processing fails or validation errors occur
     * 
     * @example
     * ```php
     * public function process(mixed $input, callable $next): mixed
     * {
     *     // Pre-processing
     *     $processedInput = $this->preProcess($input);
     *     
     *     // Call next stage
     *     $result = $next($processedInput);
     *     
     *     // Post-processing
     *     return $this->postProcess($result);
     * }
     * ```
     * 
     * @example
     * ```php
     * // Example of conditional pipeline execution
     * public function process(mixed $input, callable $next): mixed
     * {
     *     if (!$this->shouldContinue($input)) {
     *         return $this->handleEarlyReturn($input);
     *     }
     *     
     *     return $next($input);
     * }
     * ```
     */
    public function process(mixed $input, callable $next): mixed;
}
