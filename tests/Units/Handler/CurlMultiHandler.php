<?php
namespace M6Web\Bundle\GuzzleHttpBundle\tests\Units\Handler;
use atoum\test;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use M6Web\Bundle\GuzzleHttpBundle\Handler\CurlMultiHandler as TestedClass;

/**
 * Class CurlMultiHandler
 * Used for testing trait and curlFactory
 */
class CurlMultiHandler extends test
{

    public function testNoCache()
    {
        $curlFactoryMock = new \mock\M6Web\Bundle\GuzzleHttpBundle\Handler\CurlFactory(3);

        $this
            ->if($testedClass = new TestedClass(['handle_factory' => $curlFactoryMock]))
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
            ->if($testedClass = new TestedClass(['handle_factory' => $curlFactoryMock]))
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
                    ->call('has')
                        ->withArguments(md5($request->getUri()))
                            ->once()
                    ->call('set')
                        ->withArguments(md5($request->getUri()), $this->getSerializedResponse($response), 500)
                            ->once()
        ;
    }

    public function testCacheGet()
    {
        $curlFactoryMock = new \mock\M6Web\Bundle\GuzzleHttpBundle\Handler\CurlFactory(3);

        $cacheMock = new \mock\M6Web\Bundle\GuzzleHttpBundle\Cache\CacheInterface();


        $cacheMock->getMockController()->has = true;
        $cacheMock->getMockController()->get = $this->getSerializedResponse(new Response(200, [], "The answer is 42", '1.1', 'OK'));
        $cacheMock->getMockController()->ttl = 256;

        $this
            ->if($testedClass = new TestedClass(['handle_factory' => $curlFactoryMock]))
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
                    ->call('has')
                        ->withArguments(md5($request->getUri()))
                            ->once()
                    ->call('get')
                        ->withArguments(md5($request->getUri()))
                            ->once()
                    ->call('ttl')
                        ->withArguments(md5($request->getUri()))
                            ->once()
                ->mock($curlFactoryMock)
                    ->call('release')
                        ->never()
        ;

    }

    public function testForceCache()
    {
        $curlFactoryMock = new \mock\M6Web\Bundle\GuzzleHttpBundle\Handler\CurlFactory(3);

        $cacheMock = new \mock\M6Web\Bundle\GuzzleHttpBundle\Cache\CacheInterface();


        $cacheMock->getMockController()->has = false;
        $cacheMock->getMockController()->get = null;

        $this
            ->if($testedClass = new TestedClass(['handle_factory' => $curlFactoryMock]))
            ->and($testedClass->setCache($cacheMock, 500, false))
            ->and($request = new Request('GET', 'http://httpbin.org'))
            ->then
                ->object($response = $testedClass($request, ['cache_force' => true])->wait())
                    ->isInstanceOf('GuzzleHttp\Psr7\Response')
                ->mock($cacheMock)
                    ->call('remove')
                        ->withArguments(md5($request->getUri()))
                            ->once()
                    ->call('set')
                        ->withArguments(md5($request->getUri()), $this->getSerializedResponse($response), 500)
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
            ->if($testedClass = new TestedClass(['handle_factory' => $curlFactoryMock]))
            ->and($testedClass->setCache($cacheMock, 500, false))
            ->and($request = new Request('GET', 'http://httpbin.org'))
            ->then
                ->object($response = $testedClass($request, ['cache_ttl' => 200])->wait())
                    ->isInstanceOf('GuzzleHttp\Psr7\Response')
                ->mock($cacheMock)
                    ->call('set')
                        ->withArguments(md5($request->getUri()), $this->getSerializedResponse($response), 200)
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

        $this
            ->if($testedClass = new TestedClass(['handle_factory' => $curlFactoryMock]))
            ->and($testedClass->setCache($cacheMock, 500, true))
            ->and($request = new Request('GET', 'http://httpbin.org/cache/200'))
            ->then
                ->object($response = $testedClass($request, [])->wait())
                    ->isInstanceOf('GuzzleHttp\Psr7\Response')
                ->mock($cacheMock)
                    ->call('set')
                        ->withArguments(md5($request->getUri()), $this->getSerializedResponse($response), 200)
                            ->once()
                ->mock($curlFactoryMock)
                    ->call('release')
                        ->once()
        ;
    }

    public function testCacheGetDebugOff()
    {
        $curlFactoryMock = new \mock\M6Web\Bundle\GuzzleHttpBundle\Handler\CurlFactory(3);

        $cacheMock = new \mock\M6Web\Bundle\GuzzleHttpBundle\Cache\CacheInterface();


        $cacheMock->getMockController()->has = true;
        $cacheMock->getMockController()->get = $this->getSerializedResponse(new Response(200, [], "The answer is 42", '1.1', 'OK'));
        $cacheMock->getMockController()->ttl = 256;

        $this
            ->if($testedClass = new TestedClass(['handle_factory' => $curlFactoryMock]))
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
                    ->call('has')
                        ->withArguments(md5($request->getUri()))
                        ->once()
                    ->call('get')
                        ->withArguments(md5($request->getUri()))
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

}