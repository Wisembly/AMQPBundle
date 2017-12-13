<?php
namespace Wisembly\AmqpBundle;

use Throwable;
//use RuntimeException; // to uncomment when bc is gone

use Wisembly\AmqpBundle\Exception\MessagingException as BC;

final class MessagingException extends BC
{
    public function __construct(Throwable $t)
    {
        parent::__construct('There was an error while trying to use the Messaging service', $t->getCode(), $t);
    }

    /** @deprecated Use $e->getPrevious()->getMessage() instead... */
    public function getMessagingExceptionMessage(): string
    {
        @trigger_error(sprintf('The method %s is deprecated since 1.4.0, please use getPrevious()->getMessage() instead', __CLASS__, __METHOD__), \E_USER_DEPRECATED);

        return $this->getPrevious()->getMessage();
    }
}
