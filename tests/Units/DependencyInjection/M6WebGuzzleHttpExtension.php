<?php
namespace M6Web\Bundle\GuzzleHttpBundle\tests\Units\DependencyInjection;

use atoum\test;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use GuzzleHttp\Promise;
use M6Web\Bundle\GuzzleHttpBundle\DependencyInjection\M6WebGuzzleHttpExtension as TestedClass;

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
                ->hasSize(7)
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
                ->hasSize(8)
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
                'test2' => $client->getAsync('http://httpbin.org')
            ])
            ->and($rep = Promise\unwrap($promises))
            ->then
                ->mock($mockDispatcher)
                    ->call('dispatch')
                        ->twice()
        ;
    }

    protected function getContainerForConfiguation($fixtureName)
    {
        $extension = new TestedClass();

        $parameterBag = new ParameterBag(array('kernel.debug' => true));
        $container = new ContainerBuilder($parameterBag);
        $container->set('event_dispatcher', new \mock\Symfony\Component\EventDispatcher\EventDispatcherInterface());
        $container->registerExtension($extension);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../Fixtures/'));
        $loader->load($fixtureName.'.yml');

        return $container;
    }
}