<?php

namespace App\Exceptions;

/**
 * Wrong request parameter exception
 *
 */
class WrongRequestParameterException extends \Exception {
    /**
     * @var int
     */
    protected $code = 401;

    /**
     * @var string
     */
    protected $message = 'The requested url parameters are wrong.';
}
