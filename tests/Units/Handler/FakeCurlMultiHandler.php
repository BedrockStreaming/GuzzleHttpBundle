<?php

namespace M6Web\Bundle\GuzzleHttpBundle\tests\Units\Handler;

use GuzzleHttp\Psr7\Request;
use M6Web\Bundle\GuzzleHttpBundle\Handler\CurlMultiHandler as BaseClass;

class FakeCurlMultiHandler extends BaseClass
{
    public static function getPublicKey(Request $request)
    {
        return self::getKey($request);
    }
}
