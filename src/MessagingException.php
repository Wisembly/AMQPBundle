<?php
namespace Wisembly\AmqpBundle;

use Exception;
//use RuntimeException; // to uncomment when bc is gone

use Wisembly\AmqpBundle\Exception\MessagingException as BC;

final class MessagingException extends BC
{
    public function __construct(Exception $e)
    {
        parent::__construct('There was an error while trying to use the Messaging service', $e->getCode(), $e);
    }

    /** @deprecated Use $e->getPrevious()->getMessage() instead... */
    public function getMessagingExceptionMessage()
    {
        return $this->getPrevious()->getMessage();
    }
}
