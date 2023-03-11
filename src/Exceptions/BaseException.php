<?php

namespace Riomigal\Languages\Exceptions;

class BaseException extends \Exception
{

    /**
     * This message will be shown to the UI users
     * @var string
     */
    protected string $publicMessage;

    function __construct(string $message = "", string $publicMessage, int $code = 0, ?Throwable $previous = null)
    {
        $this->publicMessage = $publicMessage;
        parent::__construct($message, $code, $previous);
    }

    public function getPublicMessage(): string
    {
        return $this->publicMessage;
    }
}
