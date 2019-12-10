<?php
namespace M6Web\Bundle\GuzzleHttpBundle\Handler;

use GuzzleHttp\Handler\CurlFactory as GuzzleCurlFactory;
// @TODO use something else since EasyHandle class is @internal (=> BC is not ensured)
use GuzzleHttp\Handler\EasyHandle;

/**
 * Extends the Guzzle curl factory to set curl info in response
 */
class CurlFactory extends GuzzleCurlFactory
{
    /**
     * {@inheritdoc}
     */
    public function release(EasyHandle $easy)
    {
        if (!is_null($easy->response)) {
            $easy->response->curlInfo = curl_getinfo($easy->handle);
        }

        return parent::release($easy);
    }
}
