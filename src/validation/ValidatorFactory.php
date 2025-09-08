<?php

namespace Arbor\validation;

use Closure;
use Exception;
use Arbor\validation\Parser;
use Arbor\validation\Registry;
use Arbor\validation\Evaluator;
use Arbor\validation\ErrorsFormatter;

/**
 * ValidatorFactory - A factory class for creating and managing Validator instances
 * 
 * This class provides a fluent interface for configuring and building validators
 * with different rule sets and configurations. It maintains a registry of
 * validator configurations and allows for reusable validator creation.
 * 
 * @package Arbor\validation
 * 
 */
class ValidatorFactory
{
    /**
     * Configuration map storing validator configurations indexed by key
     * 
     * Each entry contains:
     * - 'registry': Registry instance with validation rules
     * - 'closure': Optional closure for additional validator configuration
     * 
     * @var array<string, array{registry: Registry|null, closure: Closure|null}>
     */
    protected array $configMap = [];

    /**
     * The key of the currently active configuration being built
     * 
     * @var string|null
     */
    protected ?string $lastKey = null;

    /**
     * The registry instance for the currently active configuration
     * 
     * @var Registry|null
     */
    protected ?Registry $lastRegistry = null;

    /**
     * Parser instance for parsing validation rules
     * 
     * @var Parser
     */
    protected Parser $parser;

    /**
     * Formatter for validation error messages
     * 
     * @var ErrorsFormatter
     */
    protected ErrorsFormatter $errorsFormatter;

    /**
     * Initialize the ValidatorFactory with required dependencies
     * 
     * Sets up the errors formatter and parser instances that will be used
     * by all validators created by this factory.
     */
    public function __construct()
    {
        $this->errorsFormatter = new ErrorsFormatter();
        $this->parser = new Parser();
    }

    /**
     * Start configuration for a new validator with the given key
     * 
     * This method begins the fluent interface chain for configuring a validator.
     * The key must be unique - attempting to use an existing key will throw an exception.
     * 
     * @param string $key Unique identifier for this validator configuration
     * 
     * @return self Returns this instance for method chaining
     * 
     * @throws Exception If a configuration with the given key already exists
     */
    public function for(string $key): self
    {
        if (isset($this->configMap[$key])) {
            throw new Exception("Entry already exists with the key: '{$key}'");
        }

        $this->configMap[$key] = [
            'registry' => null,
            'closure' => null
        ];

        $this->lastKey = $key;
        $this->lastRegistry = null;

        return $this;
    }

    /**
     * Check if a configuration exists for the given key
     * 
     * @param string $key The configuration key to check
     * @param bool $throw Whether to throw an exception if the key doesn't exist
     * 
     * @return bool True if the configuration exists, false otherwise
     * 
     * @throws Exception If $throw is true and the configuration doesn't exist
     */
    public function hasConfig(string $key, bool $throw = false): bool
    {
        $exists = isset($this->configMap[$key]);

        if (!$exists && $throw) {
            throw new Exception("Entry does not exist with key: '{$key}'");
        }

        return $exists;
    }

    /**
     * Get the Registry instance for a given configuration key
     * 
     * @param string $key The configuration key
     * 
     * @return Registry The registry instance containing validation rules
     * 
     * @throws Exception If the configuration doesn't exist or registry is not initialized
     */
    public function getRegistry(string $key): Registry
    {
        $this->hasConfig($key, true);

        $registry = $this->configMap[$key]['registry'];

        if (!$registry instanceof Registry) {
            throw new Exception("Registry not initialized for key: '{$key}'");
        }

        return $registry;
    }

    /**
     * Define validation rules for the current configuration
     * 
     * This method accepts either:
     * 1. A string key referencing an existing configuration's registry
     * 2. A closure that receives a Registry instance to define rules
     * 
     * @param Closure|string $key Either a configuration key or a closure for defining rules
     * 
     * @return self Returns this instance for method chaining
     * 
     * @throws Exception If called without first calling for() method
     */
    public function rules(Closure|string $key): self
    {
        if ($this->lastKey === null) {
            throw new Exception("Cannot call rules() without calling for(\$key) first.");
        }

        if (is_string($key)) {
            $this->lastRegistry = $this->getRegistry($key);
        }

        if ($key instanceof Closure) {
            $this->lastRegistry = new Registry();

            // letting closure mutate $registry.
            $key($this->lastRegistry);
        }

        // persisit in configMap.
        $this->configMap[$this->lastKey]['registry'] = $this->lastRegistry;

        return $this;
    }

    /**
     * Complete the current validator configuration
     * 
     * Finalizes the current configuration with an optional closure for
     * additional validator setup. This closure will be called when
     * the validator is retrieved via get().
     * 
     * @param Closure|null $closure Optional closure for additional validator configuration
     * 
     * @return void
     * 
     * @throws Exception If called without proper initialization (missing key or registry)
     */
    public function make(Closure|null $closure): void
    {
        if ($this->lastKey === null || $this->lastRegistry === null) {
            throw new Exception("Cannot call make() without initializing key and registry. Call for() and rules() first.");
        }

        $this->configMap[$this->lastKey]['closure'] = $closure;

        $this->reset();
    }

    /**
     * Get a configured Validator instance
     * 
     * Builds and returns a Validator instance using the configuration
     * associated with the given key. If a closure was provided during
     * configuration, it will be called with the validator instance.
     * 
     * @param string $key The configuration key
     * 
     * @return Validator A fully configured validator instance
     * 
     * @throws Exception If the configuration doesn't exist
     */
    public function get($key): Validator
    {
        $validator = $this->buildValidator(
            $this->getRegistry($key)
        );

        $closure = $this->configMap[$key]['closure'];

        if ($closure instanceof Closure) {
            $closure($validator);
        }

        return $validator;
    }

    /**
     * Build a Validator instance with the given registry
     * 
     * Creates a new Validator with all necessary dependencies injected.
     * 
     * @param Registry $registry The registry containing validation rules
     * 
     * @return Validator A new validator instance
     */
    protected function buildValidator(Registry $registry): Validator
    {
        return new Validator(
            registry: $registry,
            parser: $this->parser,
            evaluator: new Evaluator($registry),
            errorsFormatter: $this->errorsFormatter
        );
    }

    /**
     * Reset the internal state for the next configuration
     * 
     * Clears the last key and registry references to prepare for
     * configuring a new validator.
     * 
     * @return void
     */
    protected function reset()
    {
        $this->lastKey = null;
        $this->lastRegistry = null;
    }
}
