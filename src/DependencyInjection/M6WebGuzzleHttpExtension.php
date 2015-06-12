<?php
namespace M6Web\Bundle\GuzzleHttpBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;

/**
 * This is the class that loads and manages bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class M6WebGuzzleHttpExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        foreach ($config['clients'] as $clientId => $clientConfig) {
            $this->loadClient($container, $clientId, $clientConfig);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $clientId
     * @param array            $config
     */
    protected function loadClient(ContainerBuilder $container, $clientId, array $config)
    {

        if ($config['allow_redirects']) {
            $config['allow_redirects'] = $config['redirects'];
        }
        unset($config['redirects']);

        $class = 'M6Web\Bundle\GuzzleHttp\Client\Http';
        $definition = new Definition($class);
        $definition->addArgument($config);
        $definition->setConfigurator('M6Web\Bundle\GuzzleHttp\Client\Configurator', 'configureClient');

        $containerKey = ($clientId == 'default') ? 'm6web_guzzlehttp' : 'm6web_guzzlehttp_'.$clientId;

        $container->setDefinition($containerKey, $definition);

    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return 'm6web_guzzlehttp';
    }
}