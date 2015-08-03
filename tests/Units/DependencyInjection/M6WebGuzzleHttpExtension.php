<?php
namespace M6Web\Bundle\GuzzleHttpBundle\tests\Units\DependencyInjection;

use atoum\test;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use GuzzleHttp\Promise;
use M6Web\Bundle\GuzzleHttpBundle\DependencyInjection\M6WebGuzzleHttpExtension as TestedClass;
use GuzzleHttp\Psr7\Response;

class M6WebGuzzleHttpExtension extends test
{
    public function testDefaultConfig()
    {
        $container = $this->getContainerForConfiguation('default-config');
        $container->compile();

        $this
            ->boolean($container->has('m6web_guzzlehttp'))
                ->isTrue()
            ->array($arguments = $container->getDefinition('m6web_guzzlehttp')->getArgument(0))
                ->hasSize(8)
            ->string($arguments['base_uri'])
                ->isEqualTo('')
            ->float($arguments['timeout'])
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
                ->isEqualTo((CURLPROTO_HTTP|CURLPROTO_HTTPS))
            ->boolean($curlOpt[CURLOPT_AUTOREFERER])
                ->isTrue()
        ;
    }

    public function testOverrideConfig()
    {
        $container = $this->getContainerForConfiguation('override-config');
        $container->compile();

        $this
            ->boolean($container->has('m6web_guzzlehttp'))
                ->isTrue()
            ->array($arguments = $container->getDefinition('m6web_guzzlehttp')->getArgument(0))
                ->hasSize(8)
            ->string($arguments['base_uri'])
                ->isEqualTo('http://domain.tld')
            ->integer($arguments['timeout'])
                ->isEqualTo(2)
            ->boolean($arguments['http_errors'])
                ->isFalse()
            ->string($arguments['redirect_handler'])
                ->isEqualTo('guzzle')
            ->array($redirect = $arguments['allow_redirects'])
                ->hasSize(4)
                ->hasKeys(['max', 'strict', 'referer', 'protocols'])
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

    public function testMulticlientConfig()
    {
        $container = $this->getContainerForConfiguation('multiclient-config');
        $container->compile();

        $this
            ->boolean($container->has('m6web_guzzlehttp'))
                ->isTrue()
            ->array($arguments = $container->getDefinition('m6web_guzzlehttp')->getArgument(0))
                ->hasSize(8)
            ->string($arguments['base_uri'])
                ->isEqualTo('http://domain.tld')
            ->integer($arguments['timeout'])
                ->isEqualTo(2)
            ->boolean($arguments['http_errors'])
                ->isTrue()
            ->boolean($arguments['allow_redirects'])
                ->isFalse()
            ->boolean($container->has('m6web_guzzlehttp_myclient'))
                ->isTrue()
            ->array($arguments = $container->getDefinition('m6web_guzzlehttp_myclient')->getArgument(0))
                ->hasSize(9)
            ->string($arguments['base_uri'])
                ->isEqualTo('http://domain2.tld')
            ->float($arguments['timeout'])
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
        ;
    }

    public function testCacheConfig()
    {
        $mockCache = new \mock\M6Web\Bundle\GuzzleHttpBundle\Cache\CacheInterface();
        $mockCache2 = new \mock\M6Web\Bundle\GuzzleHttpBundle\Cache\CacheInterface();

        $container = $this->getContainerForConfiguation('cache-config');
        $container->set('cache_service', $mockCache);
        $container->set('cache_service2', $mockCache2);
        $container->compile();

        $this
            ->boolean($container->has('m6web_guzzlehttp'))
                ->isTrue()
            ->array($arguments = $container->getDefinition('m6web_guzzlehttp')->getArgument(0))
                ->hasSize(9)
            ->array($cacheConfig = $arguments['guzzlehttp_cache'])
                ->hasSize(3)
            ->integer($cacheConfig['default_ttl'])
                ->isEqualTo(100)
            ->boolean($cacheConfig['use_header_ttl'])
                ->isFalse()
            ->string($cacheConfig['service'])
                ->isEqualTo('cache_service')

            ->boolean($container->has('m6web_guzzlehttp_myclient'))
                ->isTrue()
            ->array($arguments = $container->getDefinition('m6web_guzzlehttp_myclient')->getArgument(0))
                ->hasSize(9)
            ->array($cacheConfig = $arguments['guzzlehttp_cache'])
                ->hasSize(3)
            ->integer($cacheConfig['default_ttl'])
                ->isEqualTo(300)
            ->boolean($cacheConfig['use_header_ttl'])
                ->isTrue()
            ->string($cacheConfig['service'])
                ->isEqualTo('cache_service2')
        ;

    }

    public function testClientConfiguration()
    {
        $container = $this->getContainerForConfiguation('default-config');
        $container->compile();

        $this
            ->object($client = $container->get('m6web_guzzlehttp'))
                ->isInstanceOf('\GuzzleHttp\Client')
        ;
    }

    public function testClietnConfigurationWithHeaders()
    {
        $container = $this->getContainerForConfiguation('override-config');
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
        $mockDispatcher = new \mock\Symfony\Component\EventDispatcher\EventDispatcherInterface();

        $container = $this->getContainerForConfiguation('default-config');
        $container->set('event_dispatcher', $mockDispatcher);
        $container->compile();

        $this
            ->if($client = $container->get('m6web_guzzlehttp'))
            ->and($promises = [
                'test' => $client->getAsync('http://httpbin.org'),
                'test2' => $client->getAsync('http://httpbin.org/ip')
            ])
            ->and($rep = Promise\unwrap($promises))
            ->then
                ->mock($mockDispatcher)
                    ->call('dispatch')
                        ->twice()
        ;
    }

    public function testEventDispatcherMulticlient()
    {
        $mockDispatcher = new \mock\Symfony\Component\EventDispatcher\EventDispatcherInterface();

        $container = $this->getContainerForConfiguation('multiclient-config');
        $container->set('event_dispatcher', $mockDispatcher);
        $container->compile();

        $this
            ->if($client = $container->get('m6web_guzzlehttp'))
            ->and($client2 = $container->get('m6web_guzzlehttp_myclient'))
            ->and($promises = [
                'test' => $client->getAsync('http://httpbin.org'),
                'test2' => $client->getAsync('http://httpbin.org/ip')
            ])
            ->and($rep = Promise\unwrap($promises))
            ->and($client2->get('http://httpbin.org'))
            ->then
                ->mock($mockDispatcher)
                ->call('dispatch')
                    ->exactly(3)
        ;
    }

    public function testMulticlientCache()
    {
        $mockCache = new \mock\M6Web\Bundle\GuzzleHttpBundle\Cache\CacheInterface();
        $mockCache2 = new \mock\M6Web\Bundle\GuzzleHttpBundle\Cache\CacheInterface();

        $container = $this->getContainerForConfiguation('cache-config');
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
                        ->withArguments(md5('http://httpbin.org/robots.txt'), $this->getSerializedResponse($response), 100)
                            ->once()
                        ->withArguments(md5('http://httpbin.org/cache/10'), $this->getSerializedResponse($response2), 100)
                            ->once()
                        ->withAnyArguments()
                            ->twice()
            ->if($response = $client2->get('http://httpbin.org'))
            ->and($response2 = $client2->get('http://httpbin.org/cache/10'))
            ->then
                ->mock($mockCache2)
                    ->call('set')
                        ->withArguments(md5('http://httpbin.org'), $this->getSerializedResponse($response), 300)
                            ->once()
                        ->withArguments(md5('http://httpbin.org/cache/10'), $this->getSerializedResponse($response2), 10)
                            ->once()
                        ->withAnyArguments()
                            ->twice()
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

    protected function getContainerForConfiguation($fixtureName)
    {
        $extension = new TestedClass();

        $parameterBag = new ParameterBag(array('kernel.debug' => true));
        $container = new ContainerBuilder($parameterBag);
        $container->set('event_dispatcher', new \mock\Symfony\Component\EventDispatcher\EventDispatcherInterface());
        $container->set('cache_service', new \mock\M6Web\Bundle\GuzzleHttpBundle\Cache\CacheInterface());
        $container->set('cache_service2', new \mock\M6Web\Bundle\GuzzleHttpBundle\Cache\CacheInterface());
        $container->registerExtension($extension);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../Fixtures/'));
        $loader->load($fixtureName.'.yml');

        return $container;
    }
}