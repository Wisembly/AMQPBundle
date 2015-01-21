<?php
namespace Wisembly\AmqpBundle\Command;

use InvalidArgumentException;

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

    const TARGET_DIRECTORY = '%s/bin/rabbit';

    protected function configure()
    {
        $this->setName('wisembly:amqp:config-handler')
             ->setDescription('Build the amqp config files with your configuration (connection, main vhost, ...)')
             ->setHelp(<<<HELP
Create the two files needed to build your amqp configuration. Once it is done,
you can run `\$ sudo sh bin/rabbit.sh` to have everything configured, and
leaving you to launch the appropriate consumers.

You can specify some flags :
    - json to update / create the json files
    - sh to update / create the sh files
    - all to update everything (which is the default)
HELP
                );

        $this->addOption('filter', 'f', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'What should we update ?', ['all'])
             ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'Which amqp user should be used ?')
             ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'Which amqp password should be used ?');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $flag = self::UPDATE_ALL;
        $container = $this->getContainer();
        $filters = $input->getOption('filter');
        $rootPath = dirname($this->getApplication()->getKernel()->getRootDir());

        $user = $password = null;

        if ($input->getOption('user')) {
            if (!$input->getOption('password')) {
                throw new InvalidArgumentException('A password must be provided with the user option');
            }

            $user = $input->getOption('user');
            $password = $input->getOption('password');
        }

        if (!in_array('all', $filters)) {
            $flag = 0;

            if (in_array('sh', $filters)) {
                $flag |= self::UPDATE_SH;
            }

            if (in_array('json', $filters)) {
                $flag |= self::UPDATE_JSON;
            }
        }


        $filesystem = $container->get('filesystem');
        $templating = $container->get('templating');
        $connections = $container->getParameter('wisembly.amqp.connections');

        if (!$filesystem->exists(sprintf(self::TARGET_DIRECTORY, $rootPath))) {
            $output->writeln('Creating new rabbit directory');
            $filesystem->mkdir(sprintf(self::TARGET_DIRECTORY, $rootPath));
        }

        $output->writeln('Dumping config into the bin directory...');

        foreach ($connections as $name => $connection) {
            if ($flag & self::UPDATE_SH) {
                $output->writeln(sprintf('Dumping the sh file for the <info>%s</info> connection', $name));

                if (null !== $user && null !== $password) {
                    $connection['login'] = $user;
                    $connection['password'] = $password;
                }

                $file = $templating->render('WisemblyAmqpBundle:config:rabbit.sh.twig', $connection + ['path' => $rootPath, 'name' => $name]);
                $filesystem->dumpFile(sprintf(self::TARGET_DIRECTORY . '/%s.sh', $rootPath, $name), $file, 0775);
                $filesystem->chmod(sprintf(self::TARGET_DIRECTORY . '/%s.sh', $rootPath, $name), 0775);
            }

            if ($flag & self::UPDATE_JSON) {
                if (null !== $user) {
                    $connection['login'] = $user;
                }

                $output->writeln(sprintf('Dumping the json configuration file for the <info>%s</info> connection', $name));

                $file = $templating->render(sprintf('WisemblyAmqpBundle:config:%s.json.twig', $name), $connection);
                $filesystem->dumpFile(sprintf(self::TARGET_DIRECTORY . '/%s.json', $rootPath, $name), $file);
            }
        }
    }
}

