<?php


use admin\controllers\ErrorPages;


return [
    403 => [ErrorPages::class, 'notAllowed'],
    404 => [ErrorPages::class, 'notFound'],
    405 => [ErrorPages::class, 'methodNotAllowed'],
];
