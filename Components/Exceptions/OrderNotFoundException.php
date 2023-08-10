<?php

namespace Mond1SWR5\Components\Exceptions;

class OrderNotFoundException extends \RuntimeException
{
    /**
     * @param string $parameter
     * @param string $value
     * @param int    $code
     */
    public function __construct($parameter, $value, $code = 0, \Exception $previous = null)
    {
        $message = \sprintf('Could not find order with search parameter "%s" and value "%s"', $parameter, $value);
        parent::__construct($message, $code, $previous);
    }
}
