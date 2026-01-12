<?php

namespace Arbor\exception;


class View
{
    static public function HTML(array $request, array $exceptiontrail)
    {
        $html = '';

        $html .= self::requestInfo($request);
        $html .= self::exceptionTrail($exceptiontrail);

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


    static public function requestInfo(array $request): string
    {
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
    ";
    }


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
                    <hr>
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
