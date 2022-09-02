<?php

namespace M6Web\Bundle\GuzzleHttpBundle\tests\Units\Handler;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use M6Web\Bundle\GuzzleHttpBundle\Handler\CurlMultiHandler as TestedClass;

require_once 'FakeCurlMultiHandler.php';

/**
 * Class CurlMultiHandler
 * Used for testing trait and curlFactory
 */
class CurlMultiHandler extends \atoum
{
    public function testNoCache()
    {
        $curlFactoryMock = new \mock\M6Web\Bundle\GuzzleHttpBundle\Handler\CurlFactory(3);

        $this
            ->if($testedClass = new TestedClass($this->getMockDispatcher(), ['handle_factory' => $curlFactoryMock]))
            ->and($request = new Request('GET', 'http://httpbin.org'))
            ->then
                ->object($response = $testedClass($request, [])->wait())
                    ->isInstanceOf('GuzzleHttp\Psr7\Response')
                ->integer($response->getStatusCode())
                    ->isEqualTo(200)
                ->mock($curlFactoryMock)
                    ->call('release')
                        ->once()
                ->array($response->curlInfo)
                    ->isNotEmpty()
        ;
    }

    public function testCacheSet()
    {
        $curlFactoryMock = new \mock\M6Web\Bundle\GuzzleHttpBundle\Handler\CurlFactory(3);

        $cacheMock = new \mock\M6Web\Bundle\GuzzleHttpBundle\Cache\CacheInterface();

        $this
            ->if($testedClass = new TestedClass($this->getMockDispatcher(), ['handle_factory' => $curlFactoryMock]))
            ->and($testedClass->setCache($cacheMock, 500, false))
            ->and($request = new Request('GET', 'http://httpbin.org'))
                ->then
                ->object($response = $testedClass($request, [])->wait())
                    ->isInstanceOf('GuzzleHttp\Psr7\Response')
                ->integer($response->getStatusCode())
                    ->isEqualTo(200)
                ->mock($curlFactoryMock)
                    ->call('release')
                        ->once()
                ->array($response->curlInfo)
                    ->isNotEmpty()
                ->mock($cacheMock)
                    ->call('set')
                        ->withAtLeastArguments(['1' => $this->getSerializedResponse($response), '2' => 500])
                            ->once()
        ;
    }

    public function testCacheSetWithHeader()
    {
        $curlFactoryMock = new \mock\M6Web\Bundle\GuzzleHttpBundle\Handler\CurlFactory(3);

        $cacheData = [];
        $cacheMock = new \mock\M6Web\Bundle\GuzzleHttpBundle\Cache\CacheInterface();
        $cacheMock->getMockController()->set = function ($key, $value) use (&$cacheData) {
            $cacheData[$key] = $value;
        };
        $cacheMock->getMockController()->get = function ($key) use (&$cacheData) {
            if (isset($cacheData[$key])) {
                return $cacheData[$key];
            }

            return null;
        };

        // Add Header as part of the cache key but "X-" headers must be ignored
        $this
        ->if($testedClass = new TestedClass($this->getMockDispatcher(), ['handle_factory' => $curlFactoryMock]))
        ->and($testedClass->setCache($cacheMock, 500, false))
        ->and($request1 = new Request('GET', 'http://httpbin.org', ['user-agent' => 'Netscape 1', 'X-Ddos-Me' => uniqid()]))
        ->and($request2 = new Request('GET', 'http://httpbin.org', ['user-agent' => 'Netscape 1', 'X-Ddos-Me' => uniqid()]))
        ->and($request3 = new Request('GET', 'http://httpbin.org', ['user-agent' => 'Netscape 2', 'X-Ddos-Me' => uniqid()]))
            ->then
            ->object($response1 = $testedClass($request1, [])->wait())
                ->isInstanceOf('GuzzleHttp\Psr7\Response')
            ->object($response2 = $testedClass($request2, [])->wait())
                ->isInstanceOf('GuzzleHttp\Psr7\Response')
            ->object($response3 = $testedClass($request3, [])->wait())
                ->isInstanceOf('GuzzleHttp\Psr7\Response')
            ->integer($response1->getStatusCode())
                ->isEqualTo(200)
            ->integer($response2->getStatusCode())
                ->isEqualTo(200)
            ->integer($response3->getStatusCode())
                ->isEqualTo(200)
            ->mock($curlFactoryMock)
                ->call('release')
                    ->twice()
            ->mock($cacheMock)
                ->call('get')
                    ->thrice()
                ->call('set')
                    ->twice()
                        ->withAtLeastArguments(['1' => $this->getSerializedResponse($response1), '2' => 500])
                        ->withAtLeastArguments(['1' => $this->getSerializedResponse($response2), '2' => 500])
        ;

        // A header in the Vary should be in the cache even if it's an X-
        $cacheMock->getMockController()->resetCalls();
        $this
        ->if($testedClass = new TestedClass($this->getMockDispatcher(), ['handle_factory' => $curlFactoryMock]))
        ->and($testedClass->setCache($cacheMock, 500, false))
        ->and($request1 = new Request('GET', 'http://httpbin.org', ['user-agent' => 'Netscape 1', 'X-Important' => 'raoul', 'Vary' => 'X-Important']))
        ->and($request2 = new Request('GET', 'http://httpbin.org', ['user-agent' => 'Netscape 1', 'X-Important' => 'raoul2', 'Vary' => 'X-Important']))
            ->then
            ->object($response1 = $testedClass($request1, [])->wait())
                ->isInstanceOf('GuzzleHttp\Psr7\Response')
            ->object($response2 = $testedClass($request2, [])->wait())
                ->isInstanceOf('GuzzleHttp\Psr7\Response')
            ->mock($cacheMock)
                ->call('get')
                    ->twice()
                ->call('set')
                    ->twice()
        ;
    }

    public function testCacheGet()
    {
        $curlFactoryMock = new \mock\M6Web\Bundle\GuzzleHttpBundle\Handler\CurlFactory(3);

        $cacheMock = new \mock\M6Web\Bundle\GuzzleHttpBundle\Cache\CacheInterface();

        $cacheMock->getMockController()->has = true;
        $cacheMock->getMockController()->get = $this->getSerializedResponse(new Response(200, [], 'The answer is 42', '1.1', 'OK'));
        $cacheMock->getMockController()->ttl = 256;

        $this
            ->if($testedClass = new TestedClass($this->getMockDispatcher(), ['handle_factory' => $curlFactoryMock]))
            ->and($testedClass->setCache($cacheMock, 500, false))
            ->and($testedClass->setDebug(true))
            ->and($request = new Request('GET', 'http://httpbin.org'))
            ->then
                ->object($response = $testedClass($request, [])->wait())
                    ->isInstanceOf('GuzzleHttp\Psr7\Response')
                ->integer($response->getStatuscode())
                    ->isEqualTo(200)
                ->string($response->getReasonPhrase())
                    ->isEqualTo('OK')
                ->string($response->getBody()->__toString())
                    ->isEqualTo('The answer is 42')
                ->boolean($response->cached)
                    ->isTrue()
                ->integer($response->cacheTtl)
                    ->isEqualTo(256)
                ->mock($cacheMock)
                    ->call('get')
                        ->withAnyArguments()
                            ->once()
                    ->call('ttl')
                        ->withAnyArguments()
                            ->once()
                ->mock($curlFactoryMock)
                    ->call('release')
                        ->never()
        ;

        // Test unserialize issue
        $cacheMock->getMockController()->get = serialize(\SplFixedArray::fromArray([null, null, null, null, null]));

        $this
                ->object($response = $testedClass($request, [])->wait())
                    ->isInstanceOf('GuzzleHttp\Psr7\Response')
                ->integer($response->getStatuscode())
                    ->isEqualTo(200)
                ->boolean(isset($response->cached) ? $response->cached : false)
                    ->isFalse()
                ->mock($cacheMock)
                    ->call('get')
                        ->withAnyArguments()
                            ->twice()
                    ->call('ttl')
                        ->withAnyArguments()
                            ->once()
                ->mock($curlFactoryMock)
                    ->call('release')
                        ->once()
        ;
    }

    public function testForceCache()
    {
        $curlFactoryMock = new \mock\M6Web\Bundle\GuzzleHttpBundle\Handler\CurlFactory(3);

        $cacheMock = new \mock\M6Web\Bundle\GuzzleHttpBundle\Cache\CacheInterface();

        $cacheMock->getMockController()->has = false;
        $cacheMock->getMockController()->get = null;

        $this
            ->if($testedClass = new TestedClass($this->getMockDispatcher(), ['handle_factory' => $curlFactoryMock]))
            ->and($testedClass->setCache($cacheMock, 500, false))
            ->and($request = new Request('GET', 'http://httpbin.org'))
            ->then
                ->object($response = $testedClass($request, ['cache_force' => true])->wait())
                    ->isInstanceOf('GuzzleHttp\Psr7\Response')
                ->mock($cacheMock)
                    ->call('remove')
                        ->withAnyArguments()
                            ->once()
                    ->call('set')
                        ->withAtLeastArguments(['1' => $this->getSerializedResponse($response), '2' => 500])
                            ->once()
                ->mock($curlFactoryMock)
                    ->call('release')
                        ->once()
        ;
    }

    public function testCacheCustomTtl()
    {
        $curlFactoryMock = new \mock\M6Web\Bundle\GuzzleHttpBundle\Handler\CurlFactory(3);

        $cacheMock = new \mock\M6Web\Bundle\GuzzleHttpBundle\Cache\CacheInterface();

        $cacheMock->getMockController()->has = false;
        $cacheMock->getMockController()->get = null;

        $this
            ->if($testedClass = new TestedClass($this->getMockDispatcher(), ['handle_factory' => $curlFactoryMock]))
            ->and($testedClass->setCache($cacheMock, 500, false))
            ->and($request = new Request('GET', 'http://httpbin.org'))
            ->then
                ->object($response = $testedClass($request, ['cache_ttl' => 200])->wait())
                    ->isInstanceOf('GuzzleHttp\Psr7\Response')
                ->mock($cacheMock)
                    ->call('set')
                        ->withAtLeastArguments(['1' => $this->getSerializedResponse($response), '2' => 200])
                            ->once()
                ->mock($curlFactoryMock)
                    ->call('release')
                        ->once()
        ;
    }

    public function testCacheUseHeader()
    {
        $curlFactoryMock = new \mock\M6Web\Bundle\GuzzleHttpBundle\Handler\CurlFactory(3);

        $cacheMock = new \mock\M6Web\Bundle\GuzzleHttpBundle\Cache\CacheInterface();

        $cacheMock->getMockController()->has = false;
        $cacheMock->getMockController()->get = null;

        // use header ttl
        $this
            ->if($testedClass = new TestedClass($this->getMockDispatcher(), ['handle_factory' => $curlFactoryMock]))
            ->and($testedClass->setCache($cacheMock, 500, true))
            ->and($request = new Request('GET', 'http://httpbin.org/cache/200'))
            ->then
                ->object($response = $testedClass($request, [])->wait())
                    ->isInstanceOf('GuzzleHttp\Psr7\Response')
                ->mock($cacheMock)
                    ->call('set')
                        ->withAtLeastArguments(['1' => $this->getSerializedResponse($response), '2' => 200])
                            ->once()
                ->mock($curlFactoryMock)
                    ->call('release')
                        ->once()
            ->and($this->resetMock($cacheMock))
        ;

        // 200s of cache but force to 500
        $this
            ->if($testedClass = new TestedClass($this->getMockDispatcher(), ['handle_factory' => $curlFactoryMock]))
            ->and($testedClass->setCache($cacheMock, 500, false))
            ->and($request = new Request('GET', 'http://httpbin.org/cache/200'))
            ->then
                ->object($response = $testedClass($request, [])->wait())
                    ->isInstanceOf('GuzzleHttp\Psr7\Response')
                 ->mock($cacheMock)
                    ->call('set')
                        ->withAtLeastArguments(['1' => $this->getSerializedResponse($response), '2' => 500])
                     ->once()
            ->and($this->resetMock($cacheMock))
        ;

        // use header ttl and no cache
        $this
            ->if($testedClass = new TestedClass($this->getMockDispatcher(), ['handle_factory' => $curlFactoryMock]))
            ->and($testedClass->setCache($cacheMock, 500, true))
            ->and($request = new Request('GET', 'http://httpbin.org/cache/0'))
            ->then
               ->object($response = $testedClass($request, [])->wait())
                    ->isInstanceOf('GuzzleHttp\Psr7\Response')
                ->mock($cacheMock)
                    ->call('set')
                    ->never()
            ->and($this->resetMock($cacheMock))
        ;

        // no cache in header but forced to 500
        $this
            ->if($testedClass = new TestedClass($this->getMockDispatcher(), ['handle_factory' => $curlFactoryMock]))
            ->and($testedClass->setCache($cacheMock, 500, false))
            ->and($request = new Request('GET', 'http://httpbin.org/cache/0'))
            ->then
               ->object($response = $testedClass($request, [])->wait())
                    ->isInstanceOf('GuzzleHttp\Psr7\Response')
                ->mock($cacheMock)
                    ->call('set')
                        ->withAtLeastArguments(['1' => $this->getSerializedResponse($response), '2' => 500])
                    ->once()
            ->and($this->resetMock($cacheMock))
        ;
    }

    public function testCacheGetDebugOff()
    {
        $curlFactoryMock = new \mock\M6Web\Bundle\GuzzleHttpBundle\Handler\CurlFactory(3);

        $cacheMock = new \mock\M6Web\Bundle\GuzzleHttpBundle\Cache\CacheInterface();

        $cacheMock->getMockController()->has = true;
        $cacheMock->getMockController()->get = $this->getSerializedResponse(new Response(200, [], 'The answer is 42', '1.1', 'OK'));
        $cacheMock->getMockController()->ttl = 256;

        $this
            ->if($testedClass = new TestedClass($this->getMockDispatcher(), ['handle_factory' => $curlFactoryMock]))
            ->and($testedClass->setCache($cacheMock, 500, false))
            ->and($testedClass->setDebug(false))
            ->and($request = new Request('GET', 'http://httpbin.org'))
            ->then
                ->object($response = $testedClass($request, [])->wait())
                    ->isInstanceOf('GuzzleHttp\Psr7\Response')
                ->integer($response->getStatuscode())
                    ->isEqualTo(200)
                ->string($response->getReasonPhrase())
                    ->isEqualTo('OK')
                ->string($response->getBody()->__toString())
                    ->isEqualTo('The answer is 42')
                ->boolean($response->cached)
                    ->isTrue()
                ->mock($cacheMock)
                    ->call('get')
                        ->withAnyArguments()
                        ->once()
                    ->call('ttl')
                        ->never()
                ->mock($curlFactoryMock)
                    ->call('release')
                        ->never()
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

    public function testGetKey()
    {
        $curlFactoryMock = new \mock\M6Web\Bundle\GuzzleHttpBundle\Handler\CurlFactory(3);

        $testedClass = new FakeCurlMultiHandler($this->getMockDispatcher(), ['handle_factory' => $curlFactoryMock]);

        $this->if(
            $request = new \mock\GuzzleHttp\Psr7\Request(
                'GET',
                'https://httpbin.org/get'
            ))
            ->then
                ->string($testedClass->getPublicKey($request))
                ->isEqualTo('GET-https://httpbin.org/get-012c059df30be6f6c77e1b8447d7a15c')
        ;

        $this->if(
            $request = new \mock\GuzzleHttp\Psr7\Request(
                'GET',
                'https://httpbin.org/get',
                ['User-Agent' => 'Netscape4']
            ))
            ->then
            ->string($testedClass->getPublicKey($request))
            ->isEqualTo('GET-https://httpbin.org/get-c6876950b1556c0808a7f5f59b42ffb7')
        ;

        $this->if(
            $request = new \mock\GuzzleHttp\Psr7\Request(
                'POST',
                'https://httpbin.org/get',
                ['User-Agent' => 'Netscape4']
            ))
            ->then
            ->string($testedClass->getPublicKey($request))
            ->isEqualTo('POST-https://httpbin.org/get-c6876950b1556c0808a7f5f59b42ffb7')
        ;
    }

    /**
     * @return \mock\Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    private function getMockDispatcher()
    {
        return new \mock\Symfony\Component\EventDispatcher\EventDispatcherInterface();
    }
}
