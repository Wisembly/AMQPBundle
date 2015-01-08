<?php

namespace Wisembly\AmqpBundle\Command;

use Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,

    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
 * RabbitMQ Consumer
 *
 * Consumer for rabbitmq messages. Dispatch it to the right command.
 *
 * @author Baptiste Clavié <baptiste@wisembly.com>
 */
class ConfigFilesHandlerCommand extends ContainerAwareCommand
{
    const UPDATE_SH = 0b01;
    const UPDATE_JSON = 0b10;

    const UPDATE_ALL = 0b11; // php 5.6 for UPDATE_SH | UPDATE_JSON :(

    protected function configure()
    {
        $this->setName('wisembly:amqp:config-handler')
             ->setDescription('Build the amqp config files with your configuration (connection, main vhost, ...)')
             ->setHelp(<<<HELP
Create the two files needed to build your amqp configuration. Once it is done,
you can run `\$ sh bin/rabbit.sh` to have everything configured, and letting you
launch the appropriate consumers.

You can specify some flags :
    - json to update / create the json file
    - sh to update / create the sh file
    - all to update everything (which is the default)
HELP
                );

        $this->addOption('connection', 'c', InputOption::VALUE_REQUIRED, 'Which connection should we use ?', null)
             ->addOption('filter', 'f', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'What should we update ?', ['all']);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $flag = self::UPDATE_ALL;
        $container = $this->getContainer();
        $filters = $input->getOption('filter');
        $rootPath = dirname($this->getApplication()->getKernel()->getRootDir());

        if (!in_array('all', $filters)) {
            $flag = 0;

            if (in_array('sh', $filters)) {
                $flag |= self::UPDATE_SH;
            }

            if (in_array('json', $filters)) {
                $flag |= self::UPDATE_JSON;
            }
        }

        $templating = $container->get('templating');
        $connections = $container->getParameter('wisembly.amqp.connections');
        $connection = $input->getOption('connection') ?: $container->getParameter('wisembly.amqp.default_connection');

        if (!isset($connections[$connection])) {
            throw new \InvalidArgumentException(sprintf('Wrong connection "%s" given. Available ones : ["%s"]', $connection, implode('", "', array_keys($connections))));
        }

        $connection = $connections[$connection];

        if ($flag & self::UPDATE_SH) {
            $file = $templating->render('WisemblyAmqpBundle:config:rabbit.sh.twig', $connection + ['path' => $rootPath]);
            file_put_contents(sprintf('%s/bin/rabbit.sh', $rootPath), $file);
        }

        if ($flag & self::UPDATE_JSON) {
            $file = $templating->render('WisemblyAmqpBundle:config:rabbit.json.twig', $connection);
            file_put_contents(sprintf('%s/bin/rabbit.json', $rootPath), $file);
        }
    }
}

