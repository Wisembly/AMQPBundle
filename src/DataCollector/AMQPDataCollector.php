<?php
namespace Wisembly\AmqpBundle\DataCollector;

use Exception;
use InvalidArgumentException;

use DatetimeInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\DataCollector\DataCollector;

use Swarrot\Broker\Message;

use Wisembly\AmqpBundle\Gate;
use Wisembly\AmqpBundle\Connection;

/**
 * Collect all the data when sending a message through our publisher
 *
 * @author Baptiste Clavié <baptiste@wisembly.com>
 */
class AMQPDataCollector extends DataCollector
{
    public function __construct()
    {
        $this->reset();
    }

    public function collect(Request $request, Response $response, Exception $exception = null)
    {
    }

    public function reset()
    {
        $this->data = [
            'count' => 0,
            'messages' => []
        ];
    }

    public function addMessage(/*Gate */$gate/*, Message $message, DatetimeInterface $publishedAt*/): void
    {
        if (1 < ($num = func_num_args())) {
            if (3 > $num) {
                throw new InvalidArgumentException("Expected at least 3 arguments, got {$num}");
            }

            if (!$gate instanceof Gate) {
                $type = is_object($gate) ? get_class($gate) : gettype($gate);

                throw new InvalidArgumentException("The first parameter should be a Wisembly\AmqpBundle\Gate, {$type} given.");
            }

            $message = func_get_arg(1);

            if (!$message instanceof Message) {
                $type = is_object($message) ? get_class($message) : gettype($message);

                throw new InvalidArgumentException("The second parameter should be a Swarrot\Broker\Message, {$type} given.");
            }

            $publishedAt = func_get_arg(2);

            if (!$publishedAt instanceof DatetimeInterface) {
                $type = is_object($publishedAt) ? get_class($publishedAt) : gettype($publishedAt);

                throw new InvalidArgumentException("The third parameter should be a DatetimeInterface, {$type} given.");
            }
        } else {
            if (!is_array($gate)) {
                $type = is_object($gate) ? get_class($gate) : gettype($gate);

                throw new InvalidArgumentException("Expected an array as sole parameter, got {$type}");
            }

            @trigger_error('Passing only one array parameter is deprecated since 2.1 and will be removed on 3.0. Please pass at least 3 arguments.', \E_USER_DEPRECATED);

            list(
                'gate' => $gate,
                'message' => $message,
                'published_at' => $publishedAt
            ) = $gate;
        }

        $this->data['messages'][] = new CollectedData($gate, $message, $publishedAt);
        $this->data['count'] = count($this->data['messages']);
    }

    /** @return CollectedData[] */
    public function getMessages(): iterable
    {
        return $this->data['messages'];
    }

    public function getCount(): int
    {
        return $this->data['count'];
    }

    public function getName()
    {
        return 'amqp_collector';
    }
}
