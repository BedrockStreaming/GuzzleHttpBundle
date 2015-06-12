<?php
namespace M6Web\Bundle\GuzzleHttpBundle;

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
}