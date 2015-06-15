# GuzzleHttpBundle

[![Build Status](https://travis-ci.org/M6Web/GuzzleHttpBundle.svg?branch=master)](https://travis-ci.org/M6Web/GuzzleHttpBundle)

The GuzzleHttpBundle provide a guzzle client as symfony service.

## Installation

**NOTE :** Work in progress

Require the bundle in your composer.json file :

```json
{
    "require": {
        "m6web/guzzle-http-bundle"": "dev-master",
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
m6web_guzzlehttp:
    clients:
        default:
            base_uri: "http://domain.tld/"
```

All subkey under clients defines an instance of guzzle http client. These services are named `m6web_guzzlehttp_`+subkey expect for the
 `default` subkey that define the main service `m6web_guzzlehttp`. 

Then you can ask container for your client :

```php
// in a controller

$client = $this->get('m6web_guzzlehttp');
try {
    $response = $client->get('path/to/resource'); // call http://domain.tld/path/to/resource
    
    $promises = [
        'first' => $client->getAsync('path/to/resource'),
        'second' => $client->getAsync('http://other.domain.tld/path/to/resource')
    ];
    
    $result = \GuzzleHttp\Promise\unwrap($promises);
} catch(\GuzzleHttp\Exception\ConnectException $e) {
    // connection problem like timeout
} 
```

The service return a configured guzzle client, for more information on how to use it, you can read the [guzzle6 documentation](http://guzzle.readthedocs.org/en/latest/index.html).

The only difference with guzzle6 reside in usage of curl for the redirect responses. You can choose to have the guzzle behavior 
for redirection by setting the configuration key `redirect_handler` to `guzzle`.

## DataCollector

Datacollector is available when the symfony profiler is enabled. The collector allows you to see the following data :

 - Method
 - Url
 - Response code and reason
 - Execution time
 - Redirect count
 - Redirect time
 
**NOTE :** If you choose guzzle for `redirect_handler`, The redirect count and redirect time will always set to zero.
 
## Configuration reference

```yaml
m6web_guzzlehttp:
    clients:
        default:
            base_uri: ""                     # required. Base uri
            timeout: 5.0                     # request timeout
            http_errors: true                # enable or disable exception on http errors
            allow_redirects: true            # enable or disable follow redirect
            redirect_handler: curl           # guzzle or curl
            redirects:
                max: 5                       # Maximum redirect to follow
                strict: false                # use "strict" RFC compliant redirects. (guzzle only)
                referer: true                # add a Referer header
                protocols: ["http", "https"] # restrict redirect to a protocol
```

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