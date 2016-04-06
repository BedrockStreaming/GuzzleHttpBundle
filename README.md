# GuzzleHttpBundle

[![Build Status](https://travis-ci.org/M6Web/GuzzleHttpBundle.svg?branch=master)](https://travis-ci.org/M6Web/GuzzleHttpBundle)

The GuzzleHttpBundle provide a guzzle client as symfony service.

## Installation

Require the bundle in your composer.json file :

```json
{
    "require": {
        "m6web/guzzle-http-bundle": "~1.0",
    }
}
```

Register the bundle in your kernel :

```php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        new M6Web\Bundle\GuzzleHttpBundle\M6WebGuzzleHttpBundle()
    );
}
```

Then install the bundle :

```shell
$ composer update m6web/guzzle-http-bundle
```

## Usage

Add the `m6web_guzzlehttp` section in your configuration file. Here is the minimal configuration required.

```yaml
# app/config/config.yml
m6web_guzzlehttp:
    clients:
        default: ~
        other:
            base_uri: "http://domain.tld/"
```

All subkey under clients defines an instance of guzzle http client. These services are named `m6web_guzzlehttp_`+subkey expect for the
 `default` subkey that define the main service `m6web_guzzlehttp`.

Then you can ask container for your client :

```php
// in a controller

$client = $this->get('m6web_guzzlehttp'); // default client

try {
    $response = $client->get('http://domain.tld/path/to/resource');

    $promises = [
        'first' => $client->getAsync('http://domain.tld/path/to/resource'),
        'second' => $client->getAsync('http://domain.tld/path/to/other/resource')
    ];

    $result = \GuzzleHttp\Promise\unwrap($promises);
} catch(\GuzzleHttp\Exception\ConnectException $e) {
    // connection problem like timeout
}

// use other client
$otherClient = $this->get('m6web_guzzlehttp_other');
$response = $otherClient->get('path/to/resource'); // call http://domain.tld/path/to/resource
```

The service return a configured guzzle client, for more information on how to use it, you can read the [guzzle6 documentation](http://guzzle.readthedocs.org/en/latest/index.html).

The only difference with guzzle6 reside in usage of curl for the redirect responses. You can choose to have the guzzle behavior
for redirection by setting the configuration key `redirect_handler` to `guzzle`.

When a cache system is available, you can use `force_cache` and `cache_ttl` in addition of guzzle options than respectively
 force clear cache before request and use a specific ttl to a request that override configuration.

 ```php
 $client = $this->get('m6web_guzzlehttp');

 $response = $client->get('http://domain.tld', ['force_cache' => true]); // remove cache entry and set a new one

 $response = $client->get('http://doamin.tld/path', ['cache_ttl' => 200]); // set ttl to 200 seconds instead the default one

 ```

## DataCollector

Datacollector is available when the symfony profiler is enabled. The collector allows you to see the following data :

 - Method
 - Url
 - Response code and reason
 - Execution time
 - Redirect count
 - Redirect time
 - Cache hit
 - Cache ttl

**NOTE :** If you choose guzzle for `redirect_handler`, The redirect count and redirect time will always set to zero.
Cache informations are available when a cache system is set.

## Cache system

You can set a cache for request by adding in the config the `guzzlehttp_cache` with `service` subkey who is a reference
 to a service implementing `M6Web\Bundle\GuzzleHttpBundle\Cache\CacheInterface`

```yaml
# app/config/config.yml
m6web_guzzlehttp:
    clients:
        default:
            base_uri: "http://domain.tld/"
            guzzlehttp_cache:
                service: my_cache_service
```

We provide an "In memory" cache class that you can use by defining a new cache service and use it in Guzzle configuration :

```yaml
# app/config/config.yml
services:
    company.guzzle.cache.inmemory:
        class: M6Web\Bundle\GuzzleHttpBundle\Cache\InMemory

m6web_guzzlehttp:
    clients:
        default:
            guzzlehttp_cache:
                service: company.guzzle.cache.inmemory
```

We also provide a cache interface for Redis with [our RedisBundle](https://github.com/M6Web/RedisBundle) >= 2.4, than you can use in this way:

```yaml
# app/config/config.yml
m6web_guzzlehttp:
    clients:
        default:
            base_uri: "http://domain.tld/"
            guzzlehttp_cache:
                service: m6_redis.guzzlehttp

m6_redis:
    servers:
        default:
            ip:   '127.0.0.1'
            port: 6379
    clients:
        guzzlehttp:
            servers:   ["default"]     # list of servers to use
            namespace: GuzzleHttp\
            timeout:   2               # timeout in second
            readwritetimeout: 2        # read-write timeout in second
            class: M6Web\Bundle\RedisBundle\CacheAdapters\M6WebGuzzleHttp

```

For more information on how to setup the RedisBundle, refer to the README in the project.

## Configuration reference

```yaml
m6web_guzzlehttp:
    clients:
        default:
            base_uri: ""                     # Base uri to prepend on request uri
            timeout: 5.0                     # request timeout
            http_errors: true                # enable or disable exception on http errors
            allow_redirects: true            # enable or disable follow redirect
            redirect_handler: curl           # guzzle or curl
            proxy: proxy:port                # Optional. Set the proxy for client.
            redirects:
                max: 5                       # Maximum redirect to follow
                strict: false                # use "strict" RFC compliant redirects. (guzzle redirect handler only)
                referer: true                # add a Referer header
                protocols: ["http", "https"] # restrict redirect to a protocol
            guzzlehttp_cache:                # optional cache
                service: my_cache_service    # reference to service who implements the cache interface
                default_ttl: 3600            # defautl ttl for cache entry in seconds
                use_header_ttl: false        # use the cache-control header to set the ttl
                cache_server_errors: true    # at false, no server errors will be cached
            default_headers:                 # optionnal. Default request headers
                User_Agent: "m6web/1.0"      # set header "User-Agent" with the value "m6web/1.0"
                header\_name: "my value"     # set header "header_name" with value "my value"

        otherclient:
            ...
```

For the `default_headers` options, the key in array represent the header name. The underscore will be transformed to hyphen
 except if is escaped by a backslash.

## Contributing

First of all, thank you for contributing !

Here are few rules to follow for a easier code review before the maintainers accept and merge your request.

- you MUST follow the Symfony2 coding standard : you can use `./bin/coke` to validate
- you MUST run the test
- you MUST write or update tests
- you MUST write or update documentation

## Running the test

Install the composer dev dependencies

```shell
$ composer install --dev
```

Then run the test with [atoum](https://github.com/atoum/atoum) unit test framework

```shell
./bin/atoum
```
