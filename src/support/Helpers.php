<?php

namespace Arbor\support;

/**
 * Helper functions loader utility class.
 * 
 * Provides functionality to automatically load all helper function files
 * from the helpers directory.
 */
class Helpers
{
    /**
     * Load all helper function files from the helpers directory.
     * 
     * Scans the helpers/ subdirectory and requires each PHP file found,
     * making all helper functions available for use throughout the application.
     *
     * @return void
     */
    public static function load(): void
    {
        foreach (glob(__DIR__ . '/helpers/*.php') as $file) {
            require_once $file;
        }
    }
}
