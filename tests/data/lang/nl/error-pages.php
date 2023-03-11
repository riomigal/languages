<?php

return [
    'button' => 'Close',
    '4xx' => [
        'code' => '400',
        'title' => 'Bad request',
        'message' => 'Whoops! I cannot process the request.',
    ],
    '401' => [
        'code' => '401',
        'title' => 'Unauthorized',
        'message' => 'Whoops! This action is not authorized.',
    ],
    '403' => [
        'code' => '403',
        'title' => 'Forbidden',
        'message' => 'Whoops! Access forbidden.',
    ],
    '404' => [
        'code' => '404',
        'title' => 'Not found',
        'message' => 'Whoops! That page doesn\'t exist.',
    ],
    '419' => [
        'code' => '419',
        'title' => 'Session expired',
        'message' => 'Whoops! Session expired please log in again.',
    ],
    '429' => [
        'code' => '429',
        'title' => 'Too many request',
        'message' => 'Whoops! Too many request please try again in a few min.',
    ],
    '5xx' => [
        'code' => '500',
        'title' => 'Internal Server Error',
        'message' => 'Whoops! Something went wrong, please contact admin.',
    ],
    '503' => [
        'code' => '503',
        'title' => 'Service Unavailable',
        'message' => 'Whoops! We are already working to solve the problem.',
    ],
];
