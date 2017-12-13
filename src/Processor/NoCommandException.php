<?php declare(strict_types=1);
namespace Wisembly\AmqpBundle\Processor;

use Throwable;
use InvalidArgumentException;

use Wisembly\AmqpBundle\AmqpExceptionInterface;

final class NoCommandException extends InvalidArgumentException implements AmqpExceptionInterface
{
    public function __construct(Throwable $previous = null)
    {
        parent::__construct('No commands found in the message body', 0, $previous);
    }
}
