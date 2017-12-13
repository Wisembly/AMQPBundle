<?php
namespace Wisembly\AmqpBundle\DataCollector;

use Exception;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Collect all the data when sending a message through our publisher
 *
 * @author Baptiste Clavié <baptiste@wisembly.com>
 */
class AMQPDataCollector extends DataCollector
{
    private $messages = [];

    public function __construct()
    {
        $this->data = [
            'count' => 0,
            'messages' => []
        ];
    }

    public function collect(Request $request, Response $response, Exception $exception = null)
    {
        $this->data = [
            'messages' => $this->messages,
            'count' => count($this->messages)
        ];
    }

    public function reset()
    {
        $this->data = [
            'count' => 0,
            'messages' => []
        ];
    }

    /**
     * Add some data to the collector
     *
     * @param string[] $data message to collect
     */
    public function addMessage(array $data)
    {
        $this->messages[] = $data;
    }

    public function getMessages()
    {
        return $this->data['messages'];
    }

    public function getCount()
    {
        return $this->data['count'];
    }

    public function getName()
    {
        return 'amqp_collector';
    }
}
