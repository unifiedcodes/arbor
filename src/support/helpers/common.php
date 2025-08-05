<?php

use Arbor\facades\Config;


if (!function_exists('crypto_random')) {
    function crypto_random(int $min, int $max)
    {
        // Use a cryptographically secure random number generator
        $rnd = random_int($min, $max);
        return $rnd;
    }
}


if (!function_exists('random_token')) {
    function random_token(int $length, string $type = 'all'): string
    {
        $alphabets = [
            'numeric'      => '0123456789',
            'alpha'        => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'symbols'      => '!@#$%^&*()-_=+[]{}|.<>?',
        ];

        // Build alphabet based on type
        switch (strtolower($type)) {
            case 'numeric':
                $characters = $alphabets['numeric'];
                break;

            case 'alpha':
                $characters = $alphabets['alpha'];
                break;

            case 'alphaNumeric':
                $characters = $alphabets['numeric'] . $alphabets['alpha'];
                break;

            case 'all':
            default:
                $characters = implode('', $alphabets); // use all categories
                break;
        }

        $token = '';
        $max = strlen($characters) - 1;

        for ($i = 0; $i < $length; $i++) {
            $token .= $characters[crypto_random(0, $max)];
        }

        return $token;
    }
}


if (!function_exists('normalizeDirPath')) {
    function normalizeDirPath(string $path): string
    {
        // Replace all slashes with the system-specific separator
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        // Normalize double slashes
        $path = preg_replace('#' . preg_quote(DIRECTORY_SEPARATOR) . '+#', DIRECTORY_SEPARATOR, $path);

        // Trim trailing slashes and add exactly one
        return rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }
}


if (!function_exists('normalizeFilePath')) {
    function normalizeFilePath(string $path): string
    {
        // Replace all slashes with the system-specific separator
        $path = normalizeDirPath($path);
        // Trim trailing slashes for file
        return rtrim($path, DIRECTORY_SEPARATOR);
    }
}


if (!function_exists('ensureDir')) {
    function ensureDir(string $path): string
    {
        if (is_file($path)) {
            throw new \InvalidArgumentException("Path '$path' is a file, not a directory.");
        }

        if (!is_dir($path)) {
            if (!mkdir($path, 0755, true)) {
                throw new \RuntimeException("Failed to create directory: $path");
            }
        }

        return $path;
    }
}


if (!function_exists('normalizeURLSlashes')) {
    function normalizeURLSlashes(string $url): string
    {
        return preg_replace('#(?<!:)//+#', '/', trim($url));
    }
}


if (!function_exists('config')) {
    function config(string $key): mixed
    {
        return Config::get($key);
    }
}
