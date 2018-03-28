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
                            ->enumNode('redirect_handler')
                                ->values(['curl', 'guzzle'])
                                ->defaultValue('curl')
                            ->end()
                            ->scalarNode('handler_stack')->end()
                            ->arrayNode('guzzlehttp_cache')
                                ->children()
                                    ->integerNode('default_ttl')->defaultValue(3600)->end()
                                    ->booleanNode('use_header_ttl')->defaultValue(false)->end()
                                    ->booleanNode('cache_server_errors')->defaultValue(true)->end()
                                    ->booleanNode('cache_client_errors')->defaultValue(true)->end()
                                    ->scalarNode('service')->cannotBeEmpty()->end()
                                ->end()
                            ->end()
                            ->scalarNode('proxy')->defaultValue("")->end()
                            ->booleanNode('http_errors')->defaultValue(true)->end()
                            ->floatNode('timeout')->defaultValue(5.0)->end()
                            ->arrayNode('headers')
                                ->prototype('scalar')->end()
                            ->end()
                            ->arrayNode('auth')
                                ->prototype('scalar')
                                ->end()
                            ->end()
                            ->scalarNode('body')->end()
                            ->variableNode('cert')
                                ->validate()
                                    ->ifTrue(function ($v) {
                                        return !is_string($v) && (!is_array($v) || count($v) != 2);
                                    })
                                    ->theninvalid('Requires a string or a two entries array')
                                ->end()
                            ->end()
                            ->floatNode('connect_timeout')
                            ->end()
                            ->variableNode('debug')
                                ->validate()
                                    ->ifTrue(function ($v) {
                                        return !is_string($v) && !is_bool($v);
                                    })
                                    ->theninvalid('Requires an invokable service id or a bolean value')
                                ->end()
                            ->end()
                            ->variableNode('decode_content')
                                ->validate()
                                    ->ifTrue(function ($v) {
                                        return !is_string($v) && !is_bool($v);
                                    })
                                    ->theninvalid('Requires a string or a boolean')
                                ->end()
                            ->end()
                            ->floatNode('delay')->end()
                            ->variableNode('expect')
                                ->validate()
                                    ->ifTrue(function ($v) {
                                        return !is_int($v) && !is_bool($v);
                                    })
                                    ->theninvalid('Requires an integer or a boolean')
                                ->end()
                            ->end()
                            ->enumNode('force_ip_resolve')
                                ->values(['v4', 'v6'])
                            ->end()
                            ->arrayNode('form_params')
                                ->prototype('variable')->end()
                            ->end()
                            ->variableNode('cookies')
                                ->validate()
                                    ->ifTrue(function ($v) {
                                        return !is_array($v) && !is_bool($v);
                                    })
                                    ->theninvalid('Requires an array or a boolean')
                                ->end()
                            ->end()
                            ->variableNode('json')->end()
                            ->arrayNode('multipart')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('name')->isRequired()->end()
                                        ->scalarNode('contents')->isRequired()->end()
                                        ->arrayNode('headers')
                                            ->prototype('scalar')
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->scalarNode('on_headers')->end()
                            ->scalarNode('on_stats')->end()
                            ->variableNode('proxy')
                                ->validate()
                                    ->ifTrue(function ($v) {
                                        return !is_array($v) && !is_string($v);
                                    })
                                    ->theninvalid('Requires an array or a string')
                                ->end()
                            ->end()
                            ->arrayNode('query')
                                ->prototype('variable')
                                ->end()
                            ->end()
                            ->scalarNode('sink')->end()
                            ->variableNode('ssl_key')
                                ->validate()
                                    ->ifTrue(function ($v) {
                                        return !is_string($v) && (!is_array($v) || count($v) != 2);
                                    })
                                    ->theninvalid('Requires a string or a two entries array')
                                ->end()
                            ->end()
                            ->booleanNode('stream')->end()
                            ->booleanNode('synchronous')->end()
                            ->variableNode('verify')
                                ->validate()
                                    ->ifTrue(function ($v) {
                                        return !is_string($v) && !is_bool($v);
                                    })
                                    ->theninvalid('Requires a string or a boolean')
                                ->end()
                            ->end()
                            ->scalarNode('version')->end()
                            ->arrayNode('allow_redirects')
                                ->beforeNormalization()
                                    ->ifInArray([true, false])
                                    ->then(function ($v) {
                                        return ['max' => 0];
                                    })
                                ->end()
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->integerNode('max')->defaultValue(5)->end()
                                    ->booleanNode('strict')->defaultValue(false)->end()
                                    ->booleanNode('referer')->defaultValue(true)->end()
                                    ->arrayNode('protocols')->requiresAtLeastOneElement()
                                        ->prototype('scalar')->end()
                                        ->defaultValue(['http', 'https'])
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end() // end prototype
                ->end()
            ->end();

        return $treeBuilder;
    }
}
