<?php

namespace Arbor;


use Exception;

/**
 * Class Autoloader
 *
 * Automatically loads PHP classes based on namespace mappings.
 * It maps namespace prefixes to one or more base directories, and uses a fallback root directory
 * when no mapping is found.
 *
 * @package Arbor
 * 
 */
class Autoloader
{
    /**
     * Root directory used as a fallback when no namespace mapping is found.
     *
     * @var string
     * 
     */
    protected string $rootDir;

    /**
     * Namespace to base directories mappings.
     * Each namespace key maps to an array of directories.
     *
     * @var array<string, array<int, string>>
     */
    protected array $namespaces = [];

    /**
     * Constructor to initialize the autoloader.
     *
     * Registers the autoload function and sets the fallback root directory.
     *
     * @param string|null $rootDir The root directory. If null or invalid, the current working directory is used.
     */
    public function __construct(?string $rootDir)
    {
        $this->rootDir = realpath($rootDir) !== false ? (string) realpath($rootDir) : getcwd();
        spl_autoload_register([$this, 'load']);
    }

    /**
     * Registers a namespace prefix with its corresponding base directory.
     * Supports multiple base directories per namespace.
     *
     * @param string $namespace The namespace prefix.
     * @param string $baseDir   The base directory for the namespace.
     *
     * @return void
     *
     * @throws Exception If the provided directory is invalid.
     */
    public function addNamespace(string $namespace, string $baseDir): void
    {
        // Normalize and validate the directory.
        $normalizedDir = realpath($baseDir);
        if ($normalizedDir === false || !is_dir($normalizedDir)) {
            throw new Exception("Invalid directory provided for namespace '$namespace': $baseDir");
        }
        $normalizedDir = rtrim($normalizedDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        // Initialize the namespace mapping if not already set and add the directory.
        if (!isset($this->namespaces[$namespace])) {
            $this->namespaces[$namespace] = [];
        }
        $this->namespaces[$namespace][] = $normalizedDir;
    }

    /**
     * Generates the file path for a given fully-qualified class name based on registered namespaces.
     *
     * @param string $className The fully-qualified class name.
     *
     * @return string The resolved file path of the class file.
     */
    protected function getClassFilePath(string $className): string
    {
        // Iterate through registered namespaces to find a match.
        foreach ($this->namespaces as $namespace => $directories) {
            if (strpos($className, $namespace) === 0) {
                // Remove the namespace prefix from the class name.
                $relativeClass = substr($className, strlen($namespace));
                $relativeClass = ltrim(str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass), DIRECTORY_SEPARATOR);

                // Check each base directory for the corresponding file.
                foreach ($directories as $dir) {
                    $filePath = $dir . $relativeClass . '.php';
                    if (file_exists($filePath)) {
                        return $filePath;
                    }
                }
            }
        }

        // Fallback: use the root directory with namespace-to-path conversion.
        $relativeClass = ltrim(str_replace('\\', DIRECTORY_SEPARATOR, $className), DIRECTORY_SEPARATOR);
        return $this->rootDir . DIRECTORY_SEPARATOR . $relativeClass . '.php';
    }

    /**
     * Loads the class file corresponding to the given fully-qualified class name.
     *     
     *
     * @param string $className The fully-qualified class name.
     *
     * @return void
     *
     */
    public function load(string $className): void
    {
        $filePath = $this->getClassFilePath($className);

        if (file_exists($filePath)) {
            require $filePath;
        }
    }
}
