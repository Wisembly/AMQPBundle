<?php
namespace Wisembly\AmqpBundle\Processor;

use PHPUnit\Framework\TestCase;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Console\Output\OutputInterface;

use Psr\Log\LoggerInterface;

use Swarrot\Broker\Message;
use Swarrot\Broker\MessageProvider\MessageProviderInterface;
use Swarrot\Broker\MessagePublisher\MessagePublisherInterface;

use Swarrot\Processor\ProcessorInterface;

use Wisembly\AmqpBundle\Processor\CommandProcessor;

class CommandProcessorTest extends TestCase
{
    public function testItIsInitializable()
    {
        $processor = new CommandProcessor($this->createMock(LoggerInterface::class),
                                          $this->createMock(ProcessBuilder::class),
                                          $this->createMock(MessageProviderInterface::class),
                                          $this->createMock(MessagePublisherInterface::class),
                                          'path/to/command',
                                          'dev');

        $this->assertInstanceOf(ProcessorInterface::class, $processor);
        $this->assertInstanceOf(CommandProcessor::class, $processor);
    }

    public function testAckIfSuccessful()
    {
        $body         = ['command' => 'foo', 'arguments' => []];
        $modifiedBody = array_merge($body, ['arguments' => ['--env', 'dev']]);

        $message = new Message(json_encode($body), ['wisembly_attempts' => CommandProcessor::MAX_ATTEMPTS]);

        $logger   = $this->createMock(LoggerInterface::class);
        $provider = $this->createMock(MessageProviderInterface::class);

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
                                          $this->createMock(MessagePublisherInterface::class),
                                          'path/to/command',
                                          'dev'
        );

        $processor->process($message, []);
    }

    public function testNackIfUnsuccessful()
    {
        $body         = ['command' => 'foo', 'arguments' => []];
        $modifiedBody = array_merge($body, ['arguments' => ['--env', 'prod']]);

        $message = new Message(json_encode($body), ['wisembly_attempts' => CommandProcessor::MAX_ATTEMPTS]);

        $logger   = $this->createMock(LoggerInterface::class);
        $provider = $this->createMock(MessageProviderInterface::class);

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
                                          $this->createMock(MessagePublisherInterface::class),
                                          'path/to/command',
                                          'prod');

        $processor->process($message, []);
    }

    public function testNackAndRequeueIfUnsuccessfulAndRequeuable()
    {
        $body         = ['command' => 'foo', 'arguments' => []];
        $modifiedBody = array_merge($body, ['arguments' => ['--env', 'dev']]);

        $message = new Message(json_encode($body));

        $logger    = $this->createMock(LoggerInterface::class);
        $provider  = $this->createMock(MessageProviderInterface::class);
        $publisher = $this->createMock(MessagePublisherInterface::class);

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
                                          'dev');

        $processor->process($message, []);
    }

    private function getProcessBuilderMock($success = true, array $arguments = [])
    {
        $process = $this->createPartialMock(Process::class, ['run', 'isSuccessful', 'getExitCode', 'getErrorOutput']);
        $builder = $this->createPartialMock(ProcessBuilder::class, ['setPrefix', 'setArguments', 'getProcess']);

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
                ->with(static::logicalOr([PHP_BINARY, 'path/to/command', 'foo'], []));

        $builder->expects(static::exactly(2))
                ->method('setArguments')
                ->with(static::logicalOr($arguments, []));

        $builder->expects(static::once())
                ->method('getProcess')
                ->will(static::returnValue($process));

        return $builder;
    }
}

