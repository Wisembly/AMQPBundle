<?php

namespace Wisembly\AmqpBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;

use Wisembly\AmqpBundle\DependencyInjection\Compiler\BrokerPass;

class WisemblyAmqpBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new BrokerPass);
    }
}

