# Mushroom :mushroom: [![Travis](https://img.shields.io/travis/willwashburn/mushroom.svg)](https://travis-ci.org/willwashburn/mushroom) [![Packagist](https://img.shields.io/packagist/dt/willwashburn/mushroom.svg)](https://packagist.org/packages/willwashburn/mushroom) [![Packagist](https://img.shields.io/packagist/v/willwashburn/mushroom.svg)](https://packagist.org/packages/willwashburn/mushroom) [![MIT License](https://img.shields.io/packagist/l/willwashburn/mushroom.svg?style=flat-square)](https://github.com/willwashburn/mushroom/blob/master/LICENSE)
Expand a link.

Mushroom finds the final destination of a shortened (or not shortened) url.

> Note: Not for psychedelic drug use

## Usage
 ```PHP
 $mushroom = new Mushroom\Mushroom();

 /// With a single bitly link
 $mushroom->expand('bit.ly/xwzfs');
 //// http://www.yourlink.com

 /// With an array of links
 $mushroom->expand(['bit.ly/1asdf','goog.it/sdfsd','somefulllink.com/foo']);
 /// array http://somebitlylink.com, http://somegooglelink.com, http://somefulllink.com/foo
 
 // Find the canonical url of some link (or set of links)
 $mushroom->canonical('http://yourlink.com?utm_param=mushroom');
 // http://www.yourlink.com

```

## Installation
Use composer

```composer require willwashburn/mushroom```

Alternatively, add ```"willwashburn/mushroom": "~2.2"``` to your composer.json

## Change Log
- v2.2.0 - Ensure that canonical urls have a scheme
- v2.1.1 - Remove CURLOPT_NOBODY from defaults
- v2.1.0 - Allow setting curl handle options; set user agent as default 
- v2.0.0 - **Breaking change**: stopped removing slashes from the end of urls
- v1.1.0 - Add ability to find canonical url from tags in body of returned page
- v1.0.0 - Expand multiple links using multi_curl_* for faster responses
- v0.0.2 - Basic link expanding using curl
- v0.0.1 - Basic link expanding using "get_headers"

## Future Plans
- "Polite" mode to use common link shortening services api's to not count expanding in click counts
