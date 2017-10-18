<?php

namespace App\Exceptions;

class MissingApiTokenException extends \Exception 
{
    /**
     * @var int
     */
    protected $code = 401;

    /**
     * @var string
     */
    protected $message = 'No API Token found in request';
}