<?php
namespace M6Web\Bundle\GuzzleHttpBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Config\FileLocator;

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

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        foreach ($config['clients'] as $clientId => $clientConfig) {
            $this->loadClient($container, $clientId, $clientConfig);
        }

        if ($container->getParameter('kernel.debug')) {
            $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
            $loader->load('datacollector.yml');
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $clientId
     * @param array            $config
     */
    protected function loadClient(ContainerBuilder $container, $clientId, array $config)
    {
        // clear empty arrays
        foreach ($config as $key => $item) {
            if (is_array($item) && count($item) == 0) {
                unset($config[$key]);
            }
        }

        if ($config['allow_redirects']['max'] == 0) {
            $config['allow_redirects'] = false;
        }

        $this->setGuzzleProxyHandler($container, $clientId, $config);

        $handlerStackDefinition = new Definition('%m6web_guzzlehttp.guzzle.handlerstack.class%');
        $handlerStackDefinition->setPublic(true);
        $handlerStackDefinition->setFactory(['%m6web_guzzlehttp.guzzle.handlerstack.class%', 'create']);
        $handlerStackDefinition->setArguments([new Reference('m6web_guzzlehttp.guzzle.proxyhandler_'.$clientId)]);

        $container->setDefinition('m6web_guzzlehttp.guzzle.handlerstack.'.$clientId, $handlerStackDefinition);

        $handlerStackReference = new Reference('m6web_guzzlehttp.guzzle.handlerstack.'.$clientId);

        $middlewareEventDispatcherDefinition = new Definition('%m6web_guzzlehttp.middleware.eventdispatcher.class%');
        $middlewareEventDispatcherDefinition->setPublic(true);
        $middlewareEventDispatcherDefinition->setArguments([new Reference('event_dispatcher'), $clientId]);
        $middlewareEventDispatcherDefinition->addMethodCall('push', [$handlerStackReference]);

        // we must assign middleware for build process
        $config['middleware'][] = $middlewareEventDispatcherDefinition;
        $config['handler'] = $handlerStackReference;

        if ($config['redirect_handler'] == 'curl') {
            $config['curl'] = $this->getCurlConfig($config);
        }

        // process default headers if set
        if (!empty($config['headers'])) {
            $config['headers'] = $this->parseHeaders($config['headers']);
        }

        // process multipart headers
        if (!empty($config['multipart'])) {
            foreach ($config['multipart'] as &$multipart) {
                if (!empty($multipart['headers'])) {
                    $multipart['headers'] = $this->parseHeaders($multipart['headers']);
                }
            }
        }
        // Create cookies jar if required
        if (!empty($config['cookies']) && is_array($config['cookies'])) {
            $config['cookies'] = $this->getCookiesJarServiceReference($container, $config['cookies'], $clientId);
        }

        // String or service entries
        foreach (['body', 'sink'] as $key) {
            if (!empty($config[$key])
                && $service = $this->getServiceReference($container, $config[$key])
            ) {
                $config[$key] = $service;
            }
        }

        // Services entries
        foreach (['on_headers', 'on_stats'] as $key) {
            if (!empty($config[$key])) {
                if (is_null($serviceReference = $this->getServiceReference($container, $config[$key]))) {
                    throw new \InvalidArgumentException(sprintf(
                        '"%s" configuration entry requires a valid service reference, "%s" given',
                        $key,
                        $config[$key]
                    ));
                }
                $config[$key] = $serviceReference;
            }
        }

        $guzzleClientDefinition = new Definition('%m6web_guzzlehttp.guzzle.client.class%');
        $guzzleClientDefinition->setPublic(true);
        $guzzleClientDefinition->addArgument($config);

        $containerKey = ($clientId == 'default') ? 'm6web_guzzlehttp' : 'm6web_guzzlehttp_'.$clientId;

        $container->setDefinition($containerKey, $guzzleClientDefinition);
    }

    /**
     * Set proxy handler definition for the client
     *
     * @param ContainerBuilder $container
     * @param string           $clientId
     * @param array            $config
     */
    protected function setGuzzleProxyHandler(ContainerBuilder $container, $clientId, array $config)
    {
        // arguments (3 and 50) in handler factories below represents the maximum number of idle handles.
        // the values are the default defined in guzzle CurlHanddler and CurlMultiHandler
        $handlerFactorySync = new Definition('%m6web_guzlehttp.handler.curlfactory.class%');
        $handlerFactorySync->setPublic(true);
        $handlerFactorySync->setArguments([3]);

        $handlerFactoryNormal = new Definition('%m6web_guzlehttp.handler.curlfactory.class%');
        $handlerFactoryNormal->setPublic(true);
        $handlerFactoryNormal->setArguments([50]);

        $curlhandler = new Definition('%m6web_guzlehttp.handler.curlhandler.class%');
        $curlhandler->setPublic(true);
        $curlhandler->setArguments([ ['handle_factory' => $handlerFactorySync] ]);
        $curlhandler->addMethodCall('setDebug', [$container->getParameter('kernel.debug')]);

        $curlMultihandler = new Definition('%m6web_guzlehttp.handler.curlmultihandler.class%');
        $curlMultihandler->setPublic(true);
        $curlMultihandler->setArguments([ ['handle_factory' => $handlerFactoryNormal] ]);
        $curlMultihandler->addMethodCall('setDebug', [$container->getParameter('kernel.debug')]);

        if (array_key_exists('guzzlehttp_cache', $config)) {
            $defaultTtl = $config['guzzlehttp_cache']['default_ttl'];
            $headerTtl = $config['guzzlehttp_cache']['use_header_ttl'];
            $cacheServerErrors = $config['guzzlehttp_cache']['cache_server_errors'];
            $cacheClientErrors = $config['guzzlehttp_cache']['cache_client_errors'];
            if (is_null($cacheService = $this->getServiceReference($container, $config['guzzlehttp_cache']['service']))) {
                throw new \InvalidArgumentException(sprintf(
                    '"guzzlehttp_cache.service" requires a valid service reference, "%s" given',
                    $config['guzzlehttp_cache']['service']
                ));
            }

            $curlhandler->addMethodCall('setCache', [$cacheService, $defaultTtl, $headerTtl, $cacheServerErrors, $cacheClientErrors]);
            $curlMultihandler->addMethodCall('setCache', [$cacheService, $defaultTtl, $headerTtl, $cacheServerErrors, $cacheClientErrors]);
        }

        $proxyHandler = new Definition('%m6web_guzzlehttp.guzzle.proxyhandler.class%');
        $proxyHandler->setPublic(true);
        $proxyHandler->setFactory(['%m6web_guzzlehttp.guzzle.proxyhandler.class%', 'wrapSync']);
        $proxyHandler->setArguments([$curlMultihandler, $curlhandler]);

        $container->setDefinition('m6web_guzzlehttp.guzzle.proxyhandler_'.$clientId, $proxyHandler);
    }

    protected function getCurlConfig(array $config)
    {
        $followLocation = (!empty($config['allow_redirects']));
        $maxRedir = $followLocation ? $config['allow_redirects']['max'] : 5;
        $autoReferer = $followLocation ? $config['allow_redirects']['referer'] : true;

        $curlInfo = [
            CURLOPT_FOLLOWLOCATION => $followLocation,
            CURLOPT_MAXREDIRS => $maxRedir,
            CURLOPT_AUTOREFERER => $autoReferer
        ];

        // There is a bug/"feature" on "Unix-like systems" that causes libcurl to timeout immediately
        // if the value is < 1000 ms
        // The solution is to disable signals using CURLOPT_NOSIGNAL (no guarantee)
        if ($config['timeout'] < 1) {
            $curlInfo[CURLOPT_NOSIGNAL] = 1;
        }

        if ($followLocation) {
            $redirProtocols = 0;

            $protocols = array_map('strtolower', $config['allow_redirects']['protocols']);

            if (in_array('http', $protocols)) {
                $redirProtocols |= CURLPROTO_HTTP;
            }

            if (in_array('https', $protocols)) {
                $redirProtocols |= CURLPROTO_HTTPS;
            }

            $curlInfo[CURLOPT_REDIR_PROTOCOLS] = $redirProtocols;
        }

        return $curlInfo;
    }

    protected function getServiceReference(ContainerBuilder $container, $id)
    {
        if (substr($id, 0, 1) == '@') {
            return new Reference(substr($id, 1));
        }

        return null;
    }

    protected function getCookiesJarServiceReference(ContainerBuilder $container, array $cookies, $clientId)
    {
        array_walk($cookies, function (&$item) {
            $item = array_combine(
                array_map(
                    function ($key) {
                        return ucwords($key, ' -');
                    },
                    array_keys($item)
                ),
                array_values($item)
            );
        });

        $container->register(
            $id = sprintf('m6web_guzzlehttp.guzzle.cookies_jar.%s', $clientId),
            'GuzzleHttp\Cookie\CookieJar'
        )
        ->setArguments([false, $cookies])
        ->setPublic(true);

        return new Reference($id);
    }

    protected function parseHeaders(array $headers)
    {
        $newHeaders = [];
        array_walk($headers, function ($value, $key) use (&$newHeaders) {
            // replace underscore by hyphen in key
            $key = preg_replace('`(?<!\\\)_`', '-', $key);
            // replace escaped underscore by underscore
            $key = str_replace('\\_', '_', $key);

            $newHeaders[$key] = $value;
        });

        return $newHeaders;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return 'm6web_guzzlehttp';
    }
}
