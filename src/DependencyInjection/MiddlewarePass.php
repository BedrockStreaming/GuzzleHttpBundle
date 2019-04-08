<?php

namespace M6Web\Bundle\GuzzleHttpBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class MiddlewarePass
 * @package M6Web\Bundle\GuzzleHttpBundle\DependencyInjection
 */
class MiddlewarePass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $services = $container->findTaggedServiceIds('m6web_guzzlehttp.middleware');
        $middlewareArguments = [];

        foreach ($services as $serviceId => $tags) {
            foreach ($tags as $tagAttributes) {
                $this->registerMiddleware($container, $serviceId, $tagAttributes['client'], $middlewareArguments);
            }
        }

        foreach ($middlewareArguments as $clientId => $middlewares) {
            $clientDefinition = $container->getDefinition('m6web_guzzlehttp_'.$clientId);
            $clientDefinition->addArgument($middlewares);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string $serviceId
     * @param string $clientId
     * @param array $middlewareArguments
     */
    private function registerMiddleware(ContainerBuilder $container, string $serviceId, string $clientId, array &$middlewareArguments)
    {
        $middlewareDefinition = $container->getDefinition($serviceId);
        $middlewareDefinition->addMethodCall('push', [new Reference('m6web_guzzlehttp.guzzle.handlerstack.'.$clientId)]);

        if (!isset($middlewareArguments[$clientId])) {
            $middlewareArguments[$clientId] = [];
        }

        $middlewareArguments[$clientId][] = $middlewareDefinition;
    }
}