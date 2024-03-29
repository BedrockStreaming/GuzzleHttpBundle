<?php

declare(strict_types=1);

namespace M6Web\Bundle\GuzzleHttpBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This is the class that loads and manages bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class M6WebGuzzleHttpExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $isDebugEnabled = $container->getParameter('kernel.debug');

        if ($useSameHandler = $config['clients_share_the_same_handler']) {
            // this Handler will be used by all clients
            $this->setGuzzleProxyHandler($container, null, $config, $isDebugEnabled);
        }

        foreach ($config['clients'] as $clientId => $clientConfig) {
            $this->loadClient($container, $clientId, $clientConfig, $isDebugEnabled, $useSameHandler);
        }

        if ($isDebugEnabled) {
            $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
            $loader->load('datacollector.yml');
        }
    }

    public function getAlias(): string
    {
        return 'm6web_guzzlehttp';
    }

    protected function loadClient(ContainerBuilder $container, string $clientId, array $config, bool $isDebugEnabled, bool $useSameHandler)
    {
        // clear empty arrays
        foreach ($config as $key => $item) {
            if (\is_array($item) && \count($item) == 0) {
                unset($config[$key]);
            }
        }

        if ($config['allow_redirects']['max'] == 0) {
            $config['allow_redirects'] = false;
        }

        if ($useSameHandler) {
            $container
                ->setAlias($this->buildGuzzleProxyHandlerServiceName($clientId), $this->buildGuzzleProxyHandlerServiceName(null))
                ->setPublic(true);
        } else {
            $this->setGuzzleProxyHandler($container, $clientId, $config, $isDebugEnabled);
        }

        // a different HandlerStack is built for each client because each client has its own middlewares,
        // but all HandlerStacks may use the same underlying Handler depending on the value of $useSameHandler
        $handlerStackDefinition = new Definition('%m6web_guzzlehttp.guzzle.handlerstack.class%');
        $handlerStackDefinition->setPublic(true);
        $handlerStackDefinition->setFactory(['%m6web_guzzlehttp.guzzle.handlerstack.class%', 'create']);
        $handlerStackDefinition->setArguments([new Reference($this->buildGuzzleProxyHandlerServiceName($clientId))]);
        $container->setDefinition($this->buildGuzzleHandlerStackServiceName($clientId), $handlerStackDefinition);

        $handlerStackReference = new Reference($this->buildGuzzleHandlerStackServiceName($clientId));

        $middlewareEventDispatcherDefinition = new Definition('%m6web_guzzlehttp.middleware.eventdispatcher.class%');
        $middlewareEventDispatcherDefinition->setPublic(true);
        $middlewareEventDispatcherDefinition->setArguments([new Reference('event_dispatcher'), $clientId]);
        $middlewareEventDispatcherDefinition->addMethodCall('push', [$handlerStackReference]);

        $middlewareHostForwarderDefinition = new Definition('%m6web_guzzlehttp.middleware.hostforwarder.class%');
        $middlewareHostForwarderDefinition->setPublic(true);
        $middlewareHostForwarderDefinition->setArguments([$config]);
        $middlewareHostForwarderDefinition->addMethodCall('push', [$handlerStackReference]);

        // we must assign middleware for build process
        $config['middleware'][] = $middlewareEventDispatcherDefinition;
        $config['middleware'][] = $middlewareHostForwarderDefinition;
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
        if (!empty($config['cookies']) && \is_array($config['cookies'])) {
            $config['cookies'] = $this->getCookiesJarServiceReference($container, $config['cookies'], $clientId);
        }

        // String or service entries
        foreach (['body', 'sink'] as $key) {
            if (!empty($config[$key])
                && $service = $this->getServiceReference($config[$key])
            ) {
                $config[$key] = $service;
            }
        }

        // Services entries
        foreach (['on_headers', 'on_stats'] as $key) {
            if (!empty($config[$key])) {
                if (\is_null($serviceReference = $this->getServiceReference($config[$key]))) {
                    throw new \InvalidArgumentException(sprintf('"%s" configuration entry requires a valid service reference, "%s" given', $key, $config[$key]));
                }
                $config[$key] = $serviceReference;
            }
        }

        $guzzleClientDefinition = new Definition('%m6web_guzzlehttp.guzzle.client.class%');
        $guzzleClientDefinition->setPublic(true);
        $guzzleClientDefinition->addArgument($config);
        $guzzleClientDefinition->addTag('m6web_guzzlehttp.client');

        $containerKey = ($clientId == 'default') ? 'm6web_guzzlehttp' : 'm6web_guzzlehttp_'.$clientId;

        $container->setDefinition($containerKey, $guzzleClientDefinition);
    }

    /**
     * Set proxy handler definition for the client
     */
    protected function setGuzzleProxyHandler(ContainerBuilder $container, ?string $clientId, array $config, bool $isDebugEnabled)
    {
        $curlHandler = new Definition('%m6web_guzlehttp.handler.curlhandler.class%');
        $curlHandler->setPublic(true);
        $curlHandler->setArguments([new Reference('event_dispatcher')]);
        $curlHandler->addMethodCall('setDebug', [$isDebugEnabled]);

        $curlMultiHandler = new Definition('%m6web_guzlehttp.handler.curlmultihandler.class%');
        $curlMultiHandler->setPublic(true);
        $curlMultiHandler->setArguments([new Reference('event_dispatcher')]);
        $curlMultiHandler->addMethodCall('setDebug', [$isDebugEnabled]);

        if (isset($config['guzzlehttp_cache'])) {
            if (
                !isset($config['guzzlehttp_cache']['service'])
                || \is_null($cacheService = $this->getServiceReference($config['guzzlehttp_cache']['service']))
            ) {
                throw new \InvalidArgumentException(sprintf('"guzzlehttp_cache.service" requires a valid service reference, "%s" given', $config['guzzlehttp_cache']['service']));
            }

            $defaultTtl = $config['guzzlehttp_cache']['default_ttl'];
            $headerTtl = $config['guzzlehttp_cache']['use_header_ttl'];
            $cacheServerErrors = $config['guzzlehttp_cache']['cache_server_errors'];
            $cacheClientErrors = $config['guzzlehttp_cache']['cache_client_errors'];
            $ignoreCacheErrors = $config['guzzlehttp_cache']['ignore_cache_errors'];

            $curlHandler->addMethodCall('setCache', [$cacheService, $defaultTtl, $headerTtl, $cacheServerErrors, $cacheClientErrors, $ignoreCacheErrors]);
            $curlMultiHandler->addMethodCall('setCache', [$cacheService, $defaultTtl, $headerTtl, $cacheServerErrors, $cacheClientErrors, $ignoreCacheErrors]);
        }

        $proxyHandler = new Definition('%m6web_guzzlehttp.guzzle.proxyhandler.class%');
        $proxyHandler->setPublic(true);
        $proxyHandler->setFactory(['%m6web_guzzlehttp.guzzle.proxyhandler.class%', 'wrapSync']);
        $proxyHandler->setArguments([$curlMultiHandler, $curlHandler]);

        $container->setDefinition($this->buildGuzzleProxyHandlerServiceName($clientId), $proxyHandler);
    }

    protected function getCurlConfig(array $config)
    {
        $followLocation = (!empty($config['allow_redirects']));
        $maxRedir = $followLocation ? $config['allow_redirects']['max'] : 5;
        $autoReferer = $followLocation ? $config['allow_redirects']['referer'] : true;

        $curlInfo = [
            CURLOPT_FOLLOWLOCATION => $followLocation,
            CURLOPT_MAXREDIRS => $maxRedir,
            CURLOPT_AUTOREFERER => $autoReferer,
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

            if (\in_array('http', $protocols)) {
                $redirProtocols |= CURLPROTO_HTTP;
            }

            if (\in_array('https', $protocols)) {
                $redirProtocols |= CURLPROTO_HTTPS;
            }

            $curlInfo[CURLOPT_REDIR_PROTOCOLS] = $redirProtocols;
        }

        return $curlInfo;
    }

    protected function getServiceReference(string $id)
    {
        if (str_starts_with($id, '@')) {
            return new Reference(substr($id, 1));
        }

        return null;
    }

    protected function getCookiesJarServiceReference(ContainerBuilder $container, array $cookies, $clientId)
    {
        array_walk($cookies, function (&$item) {
            $item = array_combine(
                array_map(
                    fn ($key) => ucwords($key, ' -'),
                    array_keys($item)
                ),
                array_values($item)
            );
        });

        $container->register(
            $id = sprintf('m6web_guzzlehttp.guzzle.cookies_jar.%s', $clientId),
            \GuzzleHttp\Cookie\CookieJar::class
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

    private function buildGuzzleProxyHandlerServiceName(?string $clientId): string
    {
        return sprintf(
            'm6web_guzzlehttp.guzzle.proxyhandler%s',
            null !== $clientId ? '_'.$clientId : ''
        );
    }

    private function buildGuzzleHandlerStackServiceName(?string $clientId): string
    {
        return sprintf(
            'm6web_guzzlehttp.guzzle.handlerstack%s',
            null !== $clientId ? '.'.$clientId : ''
        );
    }
}
