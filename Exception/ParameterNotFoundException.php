<?php
namespace Wisembly\AmqpBundle\Exception;

use Exception;
use OutOfBoundsException;

class ParameterNotFoundException extends OutOfBoundsException
{
    public function __construct($path, Exception $previous = null)
    {
        parent::__construct('The path "' . $path . '" was not found in this bag.', 0, $previous);
    }
}

