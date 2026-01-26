<?php

namespace Arbor\execution;


/**
 * Enumeration of execution types for the application.
 *
 * Defines the various contexts in which the application can be executed,
 * including web requests, command-line operations, and background jobs.
 */
enum ExecutionType: string
{
    /**
     * HTTP request execution.
     *
     * Represents execution triggered by an HTTP request in a web server context.
     * This includes GET, POST, and other HTTP methods from web clients.
     */
    case HTTP = 'http';

    /**
     * Command-line interface execution.
     *
     * Represents execution triggered from the command line or terminal,
     * typically through a CLI application or script invocation.
     */
    case CLI  = 'cli';

    /**
     * Background job execution.
     *
     * Represents execution of asynchronous tasks or jobs that run in the background,
     * independent of web requests or direct user interaction.
     */
    case JOB  = 'job';
}
