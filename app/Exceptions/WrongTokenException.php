<?php

namespace App\Exceptions;

class WrongTokenException extends \Exception 
{
    /**
     * @var int
     */
    protected $code = 401;

    /**
     * @var string
     */
    protected $message = 'Wrong API token';
}