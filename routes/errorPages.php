<?php


use app\controllers\ErrorHandler;


return [
    403 => [ErrorHandler::class, 'notAllowed'],
    404 => [ErrorHandler::class, 'notFound'],
    405 => [ErrorHandler::class, 'methodNotAllowed'],
];
