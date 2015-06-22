<?php
namespace M6Web\Bundle\GuzzleHttpBundle\Handler;

use GuzzleHttp\Handler\CurlHandler as GuzzleCurlHandler;

/**
 * Extends the guzzle curl handler
 */
class CurlHandler extends GuzzleCurlHandler
{
    use CacheTrait;
}