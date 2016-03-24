<?php

namespace M6Web\Bundle\GuzzleHttpBundle\tests\Units\Handler;

use M6Web\Bundle\GuzzleHttpBundle\Handler\CurlMultiHandler as BaseClass;
use GuzzleHttp\Psr7\Request;

class FakeCurlMultiHandler extends BaseClass
{
    public static function getPublicKey(Request $request) {
        return self::getKey($request);
    }

}
