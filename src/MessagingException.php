<?php
namespace Wisembly\AmqpBundle;

use Throwable;
use RuntimeException;

final class MessagingException extends RuntimeException
{
    public function __construct(Throwable $t)
    {
        parent::__construct('There was an error while trying to use the Messaging service', $t->getCode(), $t);
    }
}
