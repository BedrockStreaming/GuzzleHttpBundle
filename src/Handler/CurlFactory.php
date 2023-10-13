<?php

declare(strict_types=1);

namespace M6Web\Bundle\GuzzleHttpBundle\Handler;

use GuzzleHttp\Handler\CurlFactory as GuzzleCurlFactory;
// @TODO use something else since EasyHandle class is @internal (=> BC is not ensured)
use GuzzleHttp\Handler\EasyHandle;

/**
 * Extends the Guzzle curl factory to set curl info in response
 *
 * @deprecated the curlInfo dynamic property will be deleted
 * in favor of [the native on_stat option](https://docs.guzzlephp.org/en/latest/request-options.html#on-stats)
 */
class CurlFactory extends GuzzleCurlFactory
{
    public function release(EasyHandle $easy): void
    {
        if (!\is_null($easy->response)) {
            $easy->response->curlInfo = curl_getinfo($easy->handle);
        }

        parent::release($easy);
    }
}
