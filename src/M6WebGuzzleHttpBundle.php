<?php
namespace M6Web\Bundle\GuzzleHttpBundle;

use M6Web\Bundle\GuzzleHttpBundle\DependencyInjection\MiddlewarePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class M6WebCassandraBundle
 */
class M6WebGuzzleHttpBundle extends Bundle
{
    /**
     * @return DependencyInjection\M6WebCassandraExtension|null|\Symfony\Component\DependencyInjection\Extension\ExtensionInterface
     */
    public function getContainerExtension()
    {
        return new DependencyInjection\M6WebGuzzleHttpExtension();
    }

    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new MiddlewarePass());
    }
}
