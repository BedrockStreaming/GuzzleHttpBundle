<?php
namespace M6Web\Bundle\GuzzleHttpBundle\tests\Units\DependencyInjection;

use atoum\test;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
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
                ->hasSize(3)
            ->string($arguments['base_uri'])
                ->isEqualTo('http://domain.tld')
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

    public function testOverrideConfig()
    {
        $container = $this->getContainerForConfiguation('override-config');
        $container->compile();

        $this
            ->boolean($container->has('m6web_guzzlehttp'))
                ->isTrue()
            ->array($arguments = $container->getDefinition('m6web_guzzlehttp')->getArgument(0))
                ->hasSize(3)
            ->string($arguments['base_uri'])
                ->isEqualTo('http://domain.tld')
            ->integer($arguments['timeout'])
                ->isEqualTo(2)
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
                ->hasSize(3)
            ->string($arguments['base_uri'])
                ->isEqualTo('http://domain.tld')
            ->integer($arguments['timeout'])
                ->isEqualTo(2)
            ->boolean($arguments['allow_redirects'])
                ->isFalse()
            ->boolean($container->has('m6web_guzzlehttp_myclient'))
                ->isTrue()
            ->array($arguments = $container->getDefinition('m6web_guzzlehttp_myclient')->getArgument(0))
                ->hasSize(3)
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