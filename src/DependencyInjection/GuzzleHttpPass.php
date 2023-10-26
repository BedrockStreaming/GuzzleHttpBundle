<?php

declare(strict_types=1);

namespace M6Web\Bundle\GuzzleHttpBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class GuzzleHttpPass implements CompilerPassInterface
{
    public function __construct(private readonly string $clientTag = 'm6web_guzzlehttp.client')
    {
    }

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('m6web.data_collector.guzzlehttp')) {
            return;
        }

        foreach ($container->findTaggedServiceIds($this->clientTag) as $id => $tags) {
            $container->getDefinition('m6web.data_collector.guzzlehttp')
                ->addMethodCall('registerClient', [$id, new Reference($id)]);
        }
    }
}
