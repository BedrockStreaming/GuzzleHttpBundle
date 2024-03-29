<?php

declare(strict_types=1);

namespace M6Web\Bundle\GuzzleHttpBundle\tests\Units\DependencyInjection;

use GuzzleHttp\Promise;
use GuzzleHttp\Psr7\Response;
use M6Web\Bundle\GuzzleHttpBundle\DependencyInjection\M6WebGuzzleHttpExtension as TestedClass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class M6WebGuzzleHttpExtension extends \atoum
{
    public function testDefaultConfig()
    {
        $container = $this->getContainerForConfiguration('default-config');
        $container->compile();

        $this
            ->boolean($container->has('m6web_guzzlehttp'))
                ->isTrue()
            ->array($arguments = $container->getDefinition('m6web_guzzlehttp')->getArgument(0))
                ->hasSize(10)
            ->string($arguments['base_uri'])
                ->isEqualTo('')
            ->float($arguments['timeout'])
                ->isEqualTo(5.0)
            ->float($arguments['connect_timeout'])
                ->isEqualTo(5.0)
            ->float($arguments['read_timeout'])
                ->isEqualTo(5.0)
            ->boolean($arguments['http_errors'])
                ->isTrue()
            ->string($arguments['redirect_handler'])
                ->isEqualTo('curl')
            ->array($redirect = $arguments['allow_redirects'])
                ->hasSize(4)
                ->hasKeys(['max', 'strict', 'referer', 'protocols'])
            ->integer($redirect['max'])
                ->isEqualTo(5)
            ->boolean($redirect['strict'])
                ->isFalse()
            ->boolean($redirect['referer'])
                ->isTrue()
            ->array($redirect['protocols'])
                ->hasSize(2)
                ->isEqualTo(['http', 'https'])
            ->array($curlOpt = $arguments['curl'])
                ->hasSize(4)
                ->hasKeys([CURLOPT_FOLLOWLOCATION, CURLOPT_MAXREDIRS, CURLOPT_REDIR_PROTOCOLS, CURLOPT_AUTOREFERER])
            ->boolean($curlOpt[CURLOPT_FOLLOWLOCATION])
                ->isTrue()
            ->integer($curlOpt[CURLOPT_MAXREDIRS])
                ->isEqualTo(5)
            ->integer($curlOpt[CURLOPT_REDIR_PROTOCOLS])
                ->isEqualTo(CURLPROTO_HTTP | CURLPROTO_HTTPS)
            ->boolean($curlOpt[CURLOPT_AUTOREFERER])
                ->isTrue()
        ;
    }

    public function testOverrideConfig()
    {
        $container = $this->getContainerForConfiguration('override-config');
        $container->compile();

        $this
            ->boolean($container->has('m6web_guzzlehttp'))
                ->isTrue()
            ->array($arguments = $container->getDefinition('m6web_guzzlehttp')->getArgument(0))
                ->hasSize(11)
            ->string($arguments['base_uri'])
                ->isEqualTo('http://domain.tld')
            ->integer($arguments['timeout'])
                ->isEqualTo(2)
            ->integer($arguments['connect_timeout'])
                ->isEqualTo(7)
            ->integer($arguments['read_timeout'])
                ->isEqualTo(12)
            ->boolean($arguments['http_errors'])
                ->isFalse()
            ->string($arguments['redirect_handler'])
                ->isEqualTo('guzzle')
            ->array($redirect = $arguments['allow_redirects'])
                ->hasSize(4)
                ->hasKeys(['max', 'strict', 'referer', 'protocols'])
            ->string($arguments['proxy'])
                ->isEqualTo('127.0.0.1:80')
            ->integer($redirect['max'])
                ->isEqualTo(2)
            ->boolean($redirect['strict'])
                ->isTrue()
            ->boolean($redirect['referer'])
                ->isFalse()
            ->array($redirect['protocols'])
                ->hasSize(1)
                ->isEqualTo(['http'])
            ->array($headers = $arguments['headers'])
                ->hasSize(4)
                ->hasKeys(['User-Agent', 'X-Question', 'X-Answer', 'Escape_Underscore'])
            ->string($headers['User-Agent'])
                ->isEqualTo('Towel/1.0')
            ->string($headers['X-Question'])
                ->isEqualTo('The Ultimate Question of Life, the Universe and Everything')
            ->integer($headers['X-Answer'])
                ->isEqualTo(42)
            ->string($headers['Escape_Underscore'])
                ->isEqualTo('Why not?')
        ;
    }

    public function testTimeoutConfig()
    {
        $container = $this->getContainerForConfiguration('timeout-config');
        $container->compile();

        $this
            ->boolean($container->has('m6web_guzzlehttp'))
                ->isTrue()
            ->array($arguments = $container->getDefinition('m6web_guzzlehttp')->getArgument(0))
                ->hasSize(10)
            ->float($arguments['timeout'])
                ->isEqualTo(0.6)
            ->float($arguments['connect_timeout'])
                ->isEqualTo(0.5)
            ->float($arguments['read_timeout'])
                ->isEqualTo(1.0)
            ->array($curlOpt = $arguments['curl'])
                ->hasKey(CURLOPT_NOSIGNAL)
            ->array($arguments = $container->getDefinition('m6web_guzzlehttp_myclient')->getArgument(0))
                ->hasSize(10)
            ->float($arguments['timeout'])
                ->isEqualTo(1.2)
            ->float($arguments['connect_timeout'])
                ->isEqualTo(3.0)
            ->float($arguments['read_timeout'])
                ->isEqualTo(0.9)
            ->array($curlOpt = $arguments['curl'])
                ->notHasKey(CURLOPT_NOSIGNAL)
        ;
    }

    public function testMultiClientSameHandlerConfig()
    {
        $container = $this->getContainerForConfiguration('multiclient-samehandler-config');
        $container->compile();

        $this
            ->boolean($container->has('m6web_guzzlehttp'))
                ->isTrue()
            ->array($arguments = $container->getDefinition('m6web_guzzlehttp')->getArgument(0))
                ->hasSize(10)
            ->string($arguments['base_uri'])
                ->isEqualTo('http://domain.tld')
            ->integer($arguments['timeout'])
                ->isEqualTo(2)
            ->integer($arguments['connect_timeout'])
                ->isEqualTo(8)
            ->integer($arguments['read_timeout'])
                ->isEqualTo(12)
            ->boolean($arguments['http_errors'])
                ->isTrue()
            ->boolean($arguments['allow_redirects'])
                ->isFalse()
            ->boolean($container->has('m6web_guzzlehttp.guzzle.handlerstack.default'))
                ->isTrue()
            ->boolean($container->has('m6web_guzzlehttp.guzzle.proxyhandler_default'))
                ->isTrue()
            ->boolean($container->has('m6web_guzzlehttp_myclient'))
                ->isTrue()
            ->array($arguments = $container->getDefinition('m6web_guzzlehttp_myclient')->getArgument(0))
                ->hasSize(11)
            ->string($arguments['base_uri'])
                ->isEqualTo('http://domain2.tld')
            ->float($arguments['timeout'])
                ->isEqualTo(5.0)
            ->float($arguments['connect_timeout'])
                ->isEqualTo(5.0)
            ->float($arguments['read_timeout'])
                ->isEqualTo(5.0)
            ->array($redirect = $arguments['allow_redirects'])
                ->hasSize(4)
                ->hasKeys(['max', 'strict', 'referer', 'protocols'])
            ->integer($redirect['max'])
                ->isEqualTo(5)
            ->boolean($redirect['strict'])
                ->isFalse()
            ->boolean($redirect['referer'])
                ->isTrue()
            ->array($redirect['protocols'])
                ->hasSize(2)
                ->isEqualTo(['http', 'https'])
            ->array($headers = $arguments['headers'])
                ->hasSize(1)
                ->hasKey('User-Agent')
            ->boolean($container->has('m6web_guzzlehttp.guzzle.handlerstack.myclient'))
                ->isTrue()
            ->boolean($container->has('m6web_guzzlehttp.guzzle.proxyhandler_myclient'))
                ->isTrue()
            ->object($container->get('m6web_guzzlehttp.guzzle.handlerstack.myclient'))
                ->isNotIdenticalTo($container->get('m6web_guzzlehttp.guzzle.handlerstack.default'))
            ->object($container->get('m6web_guzzlehttp.guzzle.proxyhandler_myclient'))
                ->isIdenticalTo($container->get('m6web_guzzlehttp.guzzle.proxyhandler_default'))
        ;
    }

    public function testMultiClientConfig()
    {
        $container = $this->getContainerForConfiguration('multiclient-config');
        $container->compile();

        $this
            ->boolean($container->has('m6web_guzzlehttp'))
                ->isTrue()
            ->array($arguments = $container->getDefinition('m6web_guzzlehttp')->getArgument(0))
                ->hasSize(10)
            ->string($arguments['base_uri'])
                ->isEqualTo('http://domain.tld')
            ->integer($arguments['timeout'])
                ->isEqualTo(2)
            ->integer($arguments['connect_timeout'])
                ->isEqualTo(8)
            ->integer($arguments['read_timeout'])
                ->isEqualTo(12)
            ->boolean($arguments['http_errors'])
                ->isTrue()
            ->boolean($arguments['allow_redirects'])
                ->isFalse()
            ->boolean($container->has('m6web_guzzlehttp.guzzle.handlerstack.default'))
                ->isTrue()
            ->boolean($container->has('m6web_guzzlehttp.guzzle.proxyhandler_default'))
                ->isTrue()
            ->boolean($container->has('m6web_guzzlehttp_myclient'))
                ->isTrue()
            ->array($arguments = $container->getDefinition('m6web_guzzlehttp_myclient')->getArgument(0))
                ->hasSize(11)
            ->string($arguments['base_uri'])
                ->isEqualTo('http://domain2.tld')
            ->float($arguments['timeout'])
                ->isEqualTo(5.0)
            ->float($arguments['connect_timeout'])
                ->isEqualTo(5.0)
            ->float($arguments['read_timeout'])
                ->isEqualTo(5.0)
            ->array($redirect = $arguments['allow_redirects'])
                ->hasSize(4)
                ->hasKeys(['max', 'strict', 'referer', 'protocols'])
            ->integer($redirect['max'])
                ->isEqualTo(5)
            ->boolean($redirect['strict'])
                ->isFalse()
            ->boolean($redirect['referer'])
                ->isTrue()
            ->array($redirect['protocols'])
                ->hasSize(2)
                ->isEqualTo(['http', 'https'])
            ->array($headers = $arguments['headers'])
                ->hasSize(1)
                ->hasKey('User-Agent')
            ->boolean($container->has('m6web_guzzlehttp.guzzle.handlerstack.myclient'))
                ->isTrue()
            ->boolean($container->has('m6web_guzzlehttp.guzzle.proxyhandler_myclient'))
                ->isTrue()
            ->object($container->get('m6web_guzzlehttp.guzzle.handlerstack.myclient'))
                ->isNotIdenticalTo($container->get('m6web_guzzlehttp.guzzle.handlerstack.default'))
            ->object($container->get('m6web_guzzlehttp.guzzle.proxyhandler_myclient'))
                ->isNotIdenticalTo($container->get('m6web_guzzlehttp.guzzle.proxyhandler_default'))
        ;
    }

    public function testCacheConfig()
    {
        $mockCache = new \mock\M6Web\Bundle\GuzzleHttpBundle\Cache\CacheInterface();
        $mockCache2 = new \mock\M6Web\Bundle\GuzzleHttpBundle\Cache\CacheInterface();

        $container = $this->getContainerForConfiguration('cache-config');
        $container->set('cache_service', $mockCache);
        $container->set('cache_service2', $mockCache2);
        $container->compile();

        $this
            ->boolean($container->has('m6web_guzzlehttp'))
                ->isTrue()
            ->array($arguments = $container->getDefinition('m6web_guzzlehttp')->getArgument(0))
                ->hasSize(12)
            ->array($cacheConfig = $arguments['guzzlehttp_cache'])
                ->hasSize(6)
            ->integer($cacheConfig['default_ttl'])
                ->isEqualTo(100)
            ->boolean($cacheConfig['use_header_ttl'])
                ->isFalse()
            ->string($cacheConfig['service'])
                ->isEqualTo('@cache_service')
            ->boolean($cacheConfig['cache_server_errors'])
                ->isTrue()
            ->boolean($cacheConfig['cache_client_errors'])
                ->isTrue()

            ->boolean($container->has('m6web_guzzlehttp_myclient'))
                ->isTrue()
            ->array($arguments = $container->getDefinition('m6web_guzzlehttp_myclient')->getArgument(0))
                ->hasSize(11)
            ->array($cacheConfig = $arguments['guzzlehttp_cache'])
                ->hasSize(6)
            ->integer($cacheConfig['default_ttl'])
                ->isEqualTo(300)
            ->boolean($cacheConfig['use_header_ttl'])
                ->isTrue()
            ->string($cacheConfig['service'])
                ->isEqualTo('@cache_service2')
            ->boolean($cacheConfig['cache_server_errors'])
                ->isTrue()
            ->boolean($cacheConfig['cache_client_errors'])
                ->isTrue()
        ;

        $container = $this->getContainerForConfiguration('cache-config-no-server-errors');
        $container->set('cache_service', $mockCache);
        $container->compile();

        $this
            ->boolean($container->has('m6web_guzzlehttp'))
                ->isTrue()
            ->array($arguments = $container->getDefinition('m6web_guzzlehttp')->getArgument(0))
                ->hasSize(11)
            ->array($cacheConfig = $arguments['guzzlehttp_cache'])
                ->hasSize(6)
            ->integer($cacheConfig['default_ttl'])
                ->isEqualTo(100)
            ->boolean($cacheConfig['use_header_ttl'])
                ->isFalse()
            ->string($cacheConfig['service'])
                ->isEqualTo('@cache_service')
            ->boolean($cacheConfig['cache_server_errors'])
                ->isFalse()
        ;
    }

    public function testClientConfiguration()
    {
        $container = $this->getContainerForConfiguration('default-config');
        $container->compile();

        $this
            ->object($client = $container->get('m6web_guzzlehttp'))
                ->isInstanceOf('\GuzzleHttp\Client')
        ;
    }

    public function testClientConfigurationWithHeaders()
    {
        $container = $this->getContainerForConfiguration('override-config');
        $container->compile();

        $this
            ->object($client = $container->get('m6web_guzzlehttp'))
                ->isInstanceOf('\GuzzleHttp\Client')
            ->array($config = $client->getConfig())
                ->hasKey('headers')
            ->array($headers = $config['headers'])
                ->hasKeys(['User-Agent', 'X-Question', 'X-Answer', 'Escape_Underscore'])
            ->string($headers['User-Agent'])
                ->isEqualTo('Towel/1.0')
            ->string($headers['X-Question'])
                ->isEqualTo('The Ultimate Question of Life, the Universe and Everything')
            ->integer($headers['X-Answer'])
                ->isEqualTo(42)
            ->string($headers['Escape_Underscore'])
                ->isEqualTo('Why not?')
        ;
    }

    public function testEventDispatcherMiddleWare()
    {
        $container = $this->getContainerForConfiguration('default-config');
        $container->compile();

        $this
            ->if($client = $container->get('m6web_guzzlehttp'))
            ->and($promises = [
                'test' => $client->getAsync('http://httpbin.org'),
                'test2' => $client->getAsync('http://httpbin.org/ip'),
            ])
            ->and($rep = Promise\Utils::unwrap($promises))
            ->then
                ->mock($container->get('event_dispatcher'))
                    ->call('dispatch')
                        ->twice()
        ;
    }

    public function testEventDispatcherMultiClient()
    {
        $container = $this->getContainerForConfiguration('multiclient-config');
        $container->compile();

        $this
            ->if($client = $container->get('m6web_guzzlehttp'))
            ->and($client2 = $container->get('m6web_guzzlehttp_myclient'))
            ->and($promises = [
                'test' => $client->getAsync('http://httpbin.org'),
                'test2' => $client->getAsync('http://httpbin.org/ip'),
            ])
            ->and($rep = Promise\Utils::unwrap($promises))
            ->and($client2->get('http://httpbin.org'))
            ->then
                ->mock($container->get('event_dispatcher'))
                ->call('dispatch')
                    ->exactly(3)
        ;
    }

    public function testMulticlientCache()
    {
        $mockCache = new \mock\M6Web\Bundle\GuzzleHttpBundle\Cache\CacheInterface();
        $mockCache2 = new \mock\M6Web\Bundle\GuzzleHttpBundle\Cache\CacheInterface();

        $container = $this->getContainerForConfiguration('cache-config');
        $container->set('cache_service', $mockCache);
        $container->set('cache_service2', $mockCache2);
        $container->compile();

        $this
            ->if($client = $container->get('m6web_guzzlehttp'))
            ->and($client2 = $container->get('m6web_guzzlehttp_myclient'))
            ->and($response = $client->get('http://httpbin.org/robots.txt'))
            ->and($response2 = $client->get('http://httpbin.org/cache/10'))
            ->then
                ->mock($mockCache)
                    ->call('set')
                        ->withAtLeastArguments(['1' => $this->getSerializedResponse($response), '2' => 100])
                            ->once()
                        ->withAtLeastArguments(['1' => $this->getSerializedResponse($response2), '2' => 100])
                            ->once()
                        ->withAnyArguments()
                            ->twice()
            ->if($response = $client2->get('http://httpbin.org'))
            ->and($response2 = $client2->get('http://httpbin.org/cache/10'))
            ->then
                ->mock($mockCache2)
                    ->call('set')
                        ->withAtLeastArguments(['1' => $this->getSerializedResponse($response), '2' => 300])
                            ->once()
                        ->withAtLeastArguments(['1' => $this->getSerializedResponse($response2), '2' => 10])
                            ->once()
                        ->withAnyArguments()
                            ->twice()
        ;
    }

    public function testNoServerErrorsCache()
    {
        $mockCache = new \mock\M6Web\Bundle\GuzzleHttpBundle\Cache\CacheInterface();
        $container = $this->getContainerForConfiguration('cache-config-no-server-errors');
        $container->set('cache_service', $mockCache);
        $container->compile();

        $this
            ->if($client = $container->get('m6web_guzzlehttp'))
            ->and($response = $client->get('http://httpbin.org/status/500'))
            ->then
                ->mock($mockCache)
                    ->call('set')
                        ->never()
            ->and($this->resetMock($mockCache))
        ;

        $this
            ->if($client = $container->get('m6web_guzzlehttp'))
            ->and($response = $client->get('http://httpbin.org/status/201'))
            ->then
                ->mock($mockCache)
                    ->call('set')
                        ->once()
            ->and($this->resetMock($mockCache))
        ;

        $this
            ->if($client = $container->get('m6web_guzzlehttp_myclient'))
            ->and($response = $client->get('http://httpbin.org/status/500'))
            ->then
                ->mock($mockCache)
                    ->call('set')
                        ->once()
            ->and($this->resetMock($mockCache))
        ;
    }

    protected function getSerializedResponse(Response $response)
    {
        $cached = new \SplFixedArray(5);
        $cached[0] = $response->getStatusCode();
        $cached[1] = $response->getHeaders();
        $cached[2] = $response->getBody()->__toString();
        $cached[3] = $response->getProtocolVersion();
        $cached[4] = $response->getReasonPhrase();

        return serialize($cached);
    }

    public function testRequestConfig()
    {
        $container = $this->getContainerBuilder();
        $container->set('invokable.service.id', new \stdClass());

        $container = $this->getContainerForConfiguration('request-config', $container);
        $container->compile();

        $this
            ->boolean($container->has('m6web_guzzlehttp'))
                ->isTrue()
            ->array($arguments = $container->getDefinition('m6web_guzzlehttp')->getArgument(0))
                ->hasSize(32)
            ->string($arguments['base_uri'])
                ->isEqualTo('http://foo.bar/')
            ->float($arguments['timeout'])
                ->isEqualTo(3.1)
            ->float($arguments['connect_timeout'])
                ->isEqualTo(5.4)
            ->float($arguments['read_timeout'])
                ->isEqualTo(1.1)
            ->array($arguments['auth'])
                ->isEqualTo(['user', 'passwd'])
            ->boolean($arguments['allow_redirects'])
                ->isFalse()
            ->string($arguments['body'])
                ->isEqualTo('body')
            ->array($arguments['cert'])
                ->isEqualTo(['/path/to/.pem', 'password'])
            ->boolean($arguments['debug'])
                ->isTrue()
            ->boolean($arguments['decode_content'])
                ->isTrue()
            ->integer($arguments['delay'])
                ->isEqualTo(10)
            ->boolean($arguments['expect'])
                ->isTrue()
            ->array($arguments['form_params'])
                ->isEqualTo(['foo' => 'bar'])
            ->array($arguments['headers'])
                ->isEqualTo(['X-Foo' => 'bar', 'X-bar' => 'foo'])
            ->boolean($arguments['http_errors'])
                ->isFalse()
            ->array($arguments['json'])
                ->isEqualTo([['foo' => 'bar']])
            ->array($arguments['multipart'])
                ->isEqualTo([
                    [
                        'name' => 'foo',
                        'contents' => 'bar',
                        'headers' => [
                            'X-foo' => 'bar',
                            'X-bar' => 'foo',
                        ],
                    ],
                ])
            ->object($arguments['on_headers'])
                ->isInstanceOf('Symfony\Component\DependencyInjection\Reference')
            ->object($arguments['on_stats'])
                ->isInstanceOf('Symfony\Component\DependencyInjection\Reference')
            ->array($arguments['proxy'])
                ->isEqualTo(['http' => 'tcp://localhost:8125'])
            ->array($arguments['query'])
                ->isEqualTo(['foo' => 'bar'])
            ->string($arguments['sink'])
                ->isEqualTo('/path/to/file')
            ->array($arguments['ssl_key'])
                ->isEqualTo(['/path/to/.pem', 'password'])
            ->boolean($arguments['stream'])
                ->isTrue()
            ->boolean($arguments['synchronous'])
                ->isTrue()
            ->boolean($arguments['verify'])
                ->isTrue()
            ->float($arguments['version'])
                ->isEqualTo(1.0)
            ->object($cookiesReference = $arguments['cookies'])
                ->isInstanceOf('Symfony\Component\DependencyInjection\Reference')
            ->string($cookiesServiceId = (string) $cookiesReference)
                ->isEqualTo('m6web_guzzlehttp.guzzle.cookies_jar.default')
            ->object($cookiesJar = $container->get($cookiesServiceId))
                ->isInstanceOf('GuzzleHttp\Cookie\CookieJar')
            ->array($cookies = $cookiesJar->toArray())
                ->isEqualTo([
                    [
                        'Name' => 'bar',
                        'Value' => 'foo',
                        'Domain' => 'foobar.com',
                        'Path' => '/my/path',
                        'Max-Age' => null,
                        'Expires' => null,
                        'Secure' => false,
                        'Discard' => false,
                        'HttpOnly' => false,
                        'Max' => 100,
                    ],
                    [
                        'Name' => 'tracker',
                        'Value' => 'trackerid',
                        'Domain' => 'foobar.com',
                        'Path' => '/',
                        'Max-Age' => null,
                        'Expires' => null,
                        'Secure' => false,
                        'Discard' => false,
                        'HttpOnly' => false,
                    ],
                ])
        ;
    }

    protected function getContainerForConfiguration($fixtureName, ContainerBuilder $container = null)
    {
        $extension = new TestedClass();

        if (\is_null($container)) {
            $container = $this->getContainerBuilder();
        }

        $mockDispatcher = new \mock\Symfony\Component\EventDispatcher\EventDispatcherInterface();
        $mockDispatcher->getMockController()->dispatch = function ($event) {
            return $event;
        };
        $container->set('event_dispatcher', $mockDispatcher);
        $container->set('cache_service', new \mock\M6Web\Bundle\GuzzleHttpBundle\Cache\CacheInterface());
        $container->set('cache_service2', new \mock\M6Web\Bundle\GuzzleHttpBundle\Cache\CacheInterface());
        $container->registerExtension($extension);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../Fixtures/'));
        $loader->load($fixtureName.'.yml');

        return $container;
    }

    protected function getContainerBuilder()
    {
        $parameterBag = new ParameterBag(['kernel.debug' => true]);

        return new ContainerBuilder($parameterBag);
    }
}
