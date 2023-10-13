<?php

declare(strict_types=1);

namespace M6Web\Bundle\GuzzleHttpBundle;

use M6Web\Bundle\GuzzleHttpBundle\DependencyInjection\GuzzleHttpPass;
use M6Web\Bundle\GuzzleHttpBundle\DependencyInjection\MiddlewarePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class M6WebGuzzleHttpBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new DependencyInjection\M6WebGuzzleHttpExtension();
    }

    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new MiddlewarePass());
        $container->addCompilerPass(new GuzzleHttpPass());
    }
}
