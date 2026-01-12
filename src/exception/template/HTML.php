<?php

namespace Arbor\exception\template;

use Arbor\exception\ExceptionContext;

/**
 * HTML exception template renderer
 * 
 * Generates formatted HTML pages for displaying exception information including
 * request details, exception messages, and stack traces.
 */
class HTML
{
    /**
     * Generate a complete HTML error page
     * 
     * Creates a full HTML document with embedded CSS that displays request information
     * and the complete exception trail.
     * 
     * @param ExceptionContext $exceptionContext The exception context containing request and exception data
     * @return string The complete HTML document as a string
     */
    static public function page(ExceptionContext $exceptionContext)
    {
        $html = '';

        $html .= self::requestInfo($exceptionContext->request());
        $html .= self::exceptionTrail($exceptionContext->exceptions());

        $css = file_get_contents(__DIR__ . '/exception.css');

        return "
            <!doctype html>
            <html id='errorpage'>
            <head>
                <meta charset='utf-8'>
                <title>Application Error</title>
                <style>
                    $css
                </style>
            </head>
            <body>
                <main>
                    {$html}
                </main>
            </body>
            </html>
        ";
    }


    /**
     * Generate HTML section displaying request information
     * 
     * Creates a formatted section showing HTTP method, URI, and route information
     * from the request that triggered the exception.
     * 
     * @param array $request Array containing request data with keys: method, uri, route
     * @return string HTML string containing the request information section
     */
    static public function requestInfo(array $request): string
    {
        if (empty($request)) {
            return "";
        }

        $method = $request['method'] ?? 'N/A';
        $uri    = $request['uri'] ?? 'N/A';
        $route  = $request['route'] ?? 'N/A';

        return "
        <section>
            <h3>Request</h3>
            <ul>
                <li><strong>Method:</strong> {$method}</li>
                <li><strong>URI:</strong> {$uri}</li>
                <li><strong>Route:</strong> {$route}</li>
            </ul>
        </section>
        <hr>";
    }


    /**
     * Generate HTML sections displaying the complete exception trail
     * 
     * Iterates through all exceptions in the chain and creates formatted sections
     * for each, including the exception class, code, message, file location, and
     * complete stack trace.
     * 
     * @param array $exceptions Array of exception data, each containing class, code, message, file, line, and trace
     * @return string HTML string containing all exception sections with stack traces
     */
    static public function exceptionTrail(array $exceptions): string
    {
        $exceptionsHtml = '';

        foreach ($exceptions as $level => $exception) {
            $traceHtml = '';

            foreach ($exception['trace'] as $frame) {
                $traceHtml .= "
                    <li>
                        <strong>{$frame['class']}{$frame['type']}{$frame['function']}</strong>
                        <div>{$frame['file']}:{$frame['line']}</div>
                    </li>
                ";
            }

            $exceptionsHtml .= "
                <section>
                    <div><strong>{$exception['class']} : {$exception['code']}</strong></div>
                    <div>{$exception['message']}</div>
                    <div>{$exception['file']}:{$exception['line']}</div>
                    <ul>{$traceHtml}</ul>
                </section>
            ";
        }

        return $exceptionsHtml;
    }
}
