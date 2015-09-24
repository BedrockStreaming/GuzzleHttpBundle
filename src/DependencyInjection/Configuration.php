<?php
namespace M6Web\Bundle\GuzzleHttpBundle\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * http://symfony.com/fr/doc/current/components/config/definition.html
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('m6web_guzzlehttp');

        $rootNode
            ->children()
                ->arrayNode('clients')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('id', false)
                    ->prototype('array')
                        ->children()
                            ->scalarNode('base_uri')->defaultValue("")->end()
                            ->floatNode('timeout')->defaultValue(5.0)->end()
                            ->booleanNode('http_errors')->defaultValue(true)->end()
                            ->booleanNode('allow_redirects')->defaultValue(true)->end()
                            ->scalarNode('proxy')->defaultValue("")->end()
                            ->enumNode('redirect_handler')
                                ->values(['curl', 'guzzle'])
                                ->defaultValue('curl')
                            ->end()
                            ->arrayNode('redirects')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->integerNode('max')->cannotBeEmpty()->defaultValue(5)->end()
                                    ->booleanNode('strict')->defaultValue(false)->end()
                                    ->booleanNode('referer')->defaultValue(true)->end()
                                    ->arrayNode('protocols')->requiresAtLeastOneElement()
                                        ->prototype('scalar')->end()
                                        ->defaultValue(['http', 'https'])
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('guzzlehttp_cache')
                                ->children()
                                    ->integerNode('default_ttl')->defaultValue(3600)->end()
                                    ->booleanNode('use_header_ttl')->defaultValue(false)->end()
                                    ->scalarNode('service')->cannotBeEmpty()->end()
                                ->end()
                            ->end()
                            ->arrayNode('default_headers')
                                ->useAttributeAsKey('headerKey', true)
                                ->prototype('scalar')->end()
                            ->end() // end arrayNode('default_headers')
                        ->end()
                    ->end() // end prototype
                ->end()
            ->end();

        return $treeBuilder;
    }
}