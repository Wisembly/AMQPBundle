<?php
namespace Wisembly\AmqpBundle\Tests\Processor;

use PHPUnit_Framework_TestCase;

use Symfony\Component\Console\Output\OutputInterface;

use Swarrot\Broker\Message;

use Wisembly\CoreBundle\Traits\Tests\MockWithoutConstructor;
use Wisembly\AmqpBundle\Processor\CommandProcessor;

class CommandProcessorTest extends PHPUnit_Framework_TestCase
{
    use MockWithoutConstructor;

    public function testItIsInitializable()
    {
        $processor = new CommandProcessor($this->getMockWithoutConstructor('Psr\\Log\\LoggerInterface'),
                                          $this->getMockWithoutConstructor('Symfony\\Component\\Process\\ProcessBuilder'),
                                          $this->getMockWithoutConstructor('Swarrot\\Broker\\MessageProvider\\MessageProviderInterface'),
                                          $this->getMockWithoutConstructor('Swarrot\\Broker\\MessagePublisher\\MessagePublisherInterface'),
                                          'path/to/command',
                                          'path/to/php',
                                          'dev');

        $this->assertInstanceOf('Swarrot\\Processor\\ProcessorInterface', $processor);
        $this->assertInstanceOf('Wisembly\\AmqpBundle\\Processor\\CommandProcessor', $processor);
    }

    public function testAckIfSuccessful()
    {
        $body         = ['command' => 'foo', 'arguments' => []];
        $modifiedBody = array_merge($body, ['arguments' => ['--env', 'dev']]);

        $message = new Message(json_encode($body), ['wisembly_attempts' => CommandProcessor::MAX_ATTEMPTS]);

        $logger   = $this->getMockWithoutConstructor('Psr\\Log\\LoggerInterface');
        $provider = $this->getMockWithoutConstructor('Swarrot\\Broker\\MessageProvider\\MessageProviderInterface');

        $logger->expects(static::exactly(2))
               ->method('info')
               ->with(static::logicalOr('Dispatching command', 'The process was successful'), static::logicalOr($modifiedBody, []));

        $logger->expects(static::never())
               ->method('error');

        $logger->expects(static::never())
               ->method('notice');

        $provider->expects(static::once())
                 ->method('ack')
                 ->with($message);

        $provider->expects(static::never())
                 ->method('nack');

        $processor = new CommandProcessor($logger,
                                          $this->getProcessBuilderMock(true, $modifiedBody['arguments']),
                                          $provider,
                                          $this->getMockWithoutConstructor('Swarrot\\Broker\\MessagePublisher\\MessagePublisherInterface'),
                                          'path/to/command',
                                          'path/to/php',
                                          'dev'
        );

        $processor->process($message, []);
    }

    public function testNackIfUnsuccessful()
    {
        $body         = ['command' => 'foo', 'arguments' => []];
        $modifiedBody = array_merge($body, ['arguments' => ['--env', 'prod']]);

        $message = new Message(json_encode($body), ['wisembly_attempts' => CommandProcessor::MAX_ATTEMPTS]);

        $logger   = $this->getMockWithoutConstructor('Psr\\Log\\LoggerInterface');
        $provider = $this->getMockWithoutConstructor('Swarrot\\Broker\\MessageProvider\\MessageProviderInterface');

        $logger->expects(static::once())
               ->method('info')
               ->with('Dispatching command', $modifiedBody);

        $logger->expects(static::once())
               ->method('error')
               ->with('The command failed ; aborting', ['body' => $modifiedBody, 'code' => CommandProcessor::REQUEUE]);

        $logger->expects(static::never())
               ->method('notice');

        $provider->expects(static::never())
                 ->method('ack');

        $provider->expects(static::once())
                 ->method('nack')
                 ->with($message, false);

        $processor = new CommandProcessor($logger,
                                          $this->getProcessBuilderMock(false, $modifiedBody['arguments']),
                                          $provider,
                                          $this->getMockWithoutConstructor('Swarrot\\Broker\\MessagePublisher\\MessagePublisherInterface'),
                                          'path/to/command',
                                          'path/to/php',
                                          'prod');

        $processor->process($message, []);
    }

    public function testNackAndRequeueIfUnsuccessfulAndRequeuable()
    {
        $body         = ['command' => 'foo', 'arguments' => []];
        $modifiedBody = array_merge($body, ['arguments' => ['--env', 'dev']]);

        $message = new Message(json_encode($body));

        $logger    = $this->getMockWithoutConstructor('Psr\\Log\\LoggerInterface');
        $provider  = $this->getMockWithoutConstructor('Swarrot\\Broker\\MessageProvider\\MessageProviderInterface');
        $publisher = $this->getMockWithoutConstructor('Swarrot\\Broker\\MessagePublisher\\MessagePublisherInterface');

        $logger->expects(static::once())
               ->method('info')
               ->with('Dispatching command', $modifiedBody);

        $logger->expects(static::once())
               ->method('error')
               ->with('The command failed ; aborting', ['body' => $modifiedBody, 'code' => CommandProcessor::REQUEUE]);

        $logger->expects(static::once())
               ->method('notice')
               ->with('Retrying...', $modifiedBody);

        $provider->expects(static::never())
                 ->method('ack');

        $provider->expects(static::once())
                 ->method('nack')
                 ->with($message, false);

        $publisher->expects(static::once())
                  ->method('publish')
                  ->with(static::isInstanceOf('Swarrot\\Broker\\Message'));

        $processor = new CommandProcessor($logger,
                                          $this->getProcessBuilderMock(false, $modifiedBody['arguments']),
                                          $provider,
                                          $publisher,
                                          'path/to/command',
                                          'path/to/php',
                                          'dev');

        $processor->process($message, []);
    }

    private function getProcessBuilderMock($success = true, array $arguments = [])
    {
        $process  = $this->getMockWithoutConstructor('Symfony\\Component\\Process\\Process', 'run', 'isSuccessful', 'getExitCode', 'getErrorOutput');
        $builder  = $this->getMockWithoutConstructor('Symfony\\Component\\Process\\ProcessBuilder', 'setPrefix', 'setArguments', 'getProcess');

        $process->expects(static::once())
                ->method('run')
                ->with(static::isType('callable'));

        $process->expects(static::once())
                ->method('isSuccessful')
                ->will(static::returnValue($success));

        $mock = $process->expects(true === $success ? static::never() : static::once())
                        ->method('getExitCode');

        if (false === $success) {
            $mock->will(static::returnValue(CommandProcessor::REQUEUE));
        }

        $builder->expects(static::exactly(2))
                ->method('setPrefix')
                ->with(static::logicalOr(['path/to/php', 'path/to/command', 'foo'], []));

        $builder->expects(static::exactly(2))
                ->method('setArguments')
                ->with(static::logicalOr($arguments, []));

        $builder->expects(static::once())
                ->method('getProcess')
                ->will(static::returnValue($process));

        return $builder;
    }
}

