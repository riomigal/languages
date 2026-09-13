<?php

namespace Riomigal\Languages\Exceptions;

class MissingSettingsException extends BaseException
{
    public function __construct()
    {
        $message = 'The languages settings row is missing. Restore it in the configured settings table before running package operations.';
        parent::__construct($message, $message);
    }
}
