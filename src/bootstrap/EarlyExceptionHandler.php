<?php

namespace Arbor\bootstrap;


use ErrorException;


class EarlyExceptionHandler
{
    public function bind(bool $isDebug): void
    {
        // Configure error reporting
        if ($isDebug) {
            ini_set('display_errors', '1');
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', '0');
        }

        // Convert PHP errors to exceptions
        set_error_handler(function ($severity, $message, $file, $line) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        // Handle uncaught exceptions
        set_exception_handler(function ($e) use ($isDebug) {
            http_response_code(500);

            if ($isDebug) {
                echo "<pre>" . $e . "</pre>";
            } else {
                echo "Something went wrong.";
            }
        });

        // Handle fatal errors
        register_shutdown_function(function () use ($isDebug) {
            $error = error_get_last();

            if ($error && in_array($error['type'], [
                E_ERROR,
                E_PARSE,
                E_CORE_ERROR,
                E_COMPILE_ERROR
            ])) {
                http_response_code(500);

                if ($isDebug) {
                    echo "<pre>Fatal Error: {$error['message']} in {$error['file']}:{$error['line']}</pre>";
                } else {
                    echo "Something went wrong.";
                }
            }
        });
    }
}
